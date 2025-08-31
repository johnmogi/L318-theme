 create a more flexible system for managing different subscription durations. Let's set this up properly with clear documentation.

Current Setup Analysis
Currently, the [llm_manual_timed_access] shortcode with just course_id gives 30 days
You want to support different durations (2 weeks, yearly) via shortcode parameters
Proposed Solution
1. Shortcode Syntax
Let's modify the shortcode to support different durations:

php
// Basic usage (default 30 days)
[llm_manual_timed_access course_id="123"]

// Specific duration in days
[llm_manual_timed_access course_id="123" duration="14"]  // 14 days
[llm_manual_timed_access course_id="123" duration="365"] // 1 year

// Using predefined types
[llm_manual_timed_access course_id="123" type="trial"]    // 14 days
[llm_manual_timed_access course_id="123" type="yearly"]   // 365 days
[llm_manual_timed_access course_id="123" type="monthly"]  // 30 days
2. Implementation
Let's update the manual access shortcode:

php
// In manual-timed-access-shortcode.php

function llm_manual_timed_access_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'course_id' => 0,
        'duration'  => 30,  // Default 30 days
        'type'      => '',  // trial, monthly, yearly
    ), $atts, 'llm_manual_timed_access');

    // Get current user
    $user_id = get_current_user_id();
    if (!$user_id) {
        return __('Please log in to access this feature.', 'lilac');
    }

    // Handle duration types
    $durations = array(
        'trial'   => 14,   // 2 weeks
        'monthly' => 30,   // 1 month
        'yearly'  => 365,  // 1 year
    );

    // If type is set, override duration
    if (!empty($atts['type']) && isset($durations[$atts['type']])) {
        $atts['duration'] = $durations[$atts['type']];
    }

    // Ensure duration is numeric
    $duration_days = absint($atts['duration']);
    $course_id = absint($atts['course_id']);

    // Check if course exists
    if (!get_post($course_id)) {
        return __('Invalid course ID.', 'lilac');
    }

    // Check if user already has access
    if (ld_course_check_user_access($course_id, $user_id)) {
        return __('You already have access to this course.', 'lilac');
    }

    // Generate nonce for security
    $nonce = wp_create_nonce('lilac_manual_access_' . $course_id . '_' . $user_id);

    // Output the activation button
    ob_start();
    ?>
    <div class="lilac-manual-access">
        <button class="button lilac-activate-course" 
                data-course="<?php echo esc_attr($course_id); ?>"
                data-duration="<?php echo esc_attr($duration_days); ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>">
            <?php echo sprintf(__('Activate %d Days Access', 'lilac'), $duration_days); ?>
        </button>
        <div class="lilac-message"></div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('llm_manual_timed_access', 'llm_manual_timed_access_shortcode');
3. AJAX Handler Update
php
// Add this to your existing AJAX handler
add_action('wp_ajax_lilac_activate_course', 'lilac_activate_course_callback');
function lilac_activate_course_callback() {
    // Verify nonce
    check_ajax_referer('lilac_manual_access_' . $_POST['course_id'] . '_' . get_current_user_id(), 'nonce');

    $user_id = get_current_user_id();
    $course_id = intval($_POST['course_id']);
    $duration_days = intval($_POST['duration']);

    // Calculate dates
    $access_starts = current_time('mysql');
    $access_expires = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));

    // Insert into our custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_user_subscriptions';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id'       => $user_id,
            'course_id'     => $course_id,
            'product_id'    => 0, // Manual activation
            'access_starts' => $access_starts,
            'access_expires'=> $access_expires,
            'duration_days' => $duration_days,
            'status'        => 'active',
            'created_at'    => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
    );

    if ($result) {
        // Grant LearnDash access
        ld_update_course_access($user_id, $course_id);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Access granted for %d days!', 'lilac'),
                $duration_days
            )
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Error activating access.', 'lilac')
        ));
    }
}
4. JavaScript for Button Handling
javascript
jQuery(document).ready(function($) {
    $('.lilac-activate-course').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $container = $button.closest('.lilac-manual-access');
        var $message = $container.find('.lilac-message');
        
        $button.prop('disabled', true);
        $message.html('<p class="loading">' + lilac_vars.activating + '</p>');
        
        $.ajax({
            url: lilac_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'lilac_activate_course',
                course_id: $button.data('course'),
                duration: $button.data('duration'),
                nonce: $button.data('nonce')
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<p class="success">' + response.data.message + '</p>');
                    $button.remove();
                    // Optional: Reload page after delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $message.html('<p class="error">' + response.data.message + '</p>');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                $message.html('<p class="error">' + lilac_vars.error + '</p>');
                $button.prop('disabled', false);
            }
        });
    });
});
5. Localization Script
Add this to your theme's 
functions.php
 or a custom plugin:

php
function lilac_enqueue_scripts() {
    wp_enqueue_script('lilac-manual-access', get_stylesheet_directory_uri() . '/js/manual-access.js', array('jquery'), '1.0', true);
    
    wp_localize_script('lilac-manual-access', 'lilac_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'activating' => __('Activating access, please wait...', 'lilac'),
        'error' => __('An error occurred. Please try again.', 'lilac')
    ));
}
add_action('wp_enqueue_scripts', 'lilac_enqueue_scripts');
Usage Examples
Basic Usage (30 days)
php
[llm_manual_timed_access course_id="123"]
Specific Duration (14 days)
php
[llm_manual_timed_access course_id="123" duration="14"]
Using Predefined Types
php
[llm_manual_timed_access course_id="123" type="trial"]   // 14 days
[llm_manual_timed_access course_id="123" type="monthly"] // 30 days
[llm_manual_timed_access course_id="123" type="yearly"]  // 365 days
Next Steps
Create a settings page to manage default durations
Add email notifications for subscription events
Create admin interface to view/manage all subscriptions
Add reporting for subscription statistics
Would you like me to implement any of these additional features next?


a shortcode that will set the access to expire tomorrow:

php
[llm_manual_timed_access course_id="123" duration="1"]
To answer your question about system time:

Yes, if you change your computer's local time to tomorrow, it will affect the expiration check because WordPress uses the server's system time by default.
However, for a production environment, it's better to use WordPress's time functions which can be set to use UTC regardless of server time.
If you want to test with a specific date range, you could modify the shortcode to accept a specific end date:

php
[llm_manual_timed_access course_id="123" end_date="2025-06-18 23:59:59"]
Would you like me to show you how to implement this end_date parameter in the shortcode? This would give you more control for testing specific date ranges.