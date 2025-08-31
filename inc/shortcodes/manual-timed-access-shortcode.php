<?php
/**
 * Manual Timed Access Shortcode
 * 
 * Displays manual activation button when:
 * 1. Course has manual activation enabled
 * 2. User has purchased specific required products
 * 
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Manual Timed Access Shortcode
 * 
 * Usage: [llm_manual_timed_access product_ids="123,456"]
 * 
 * @param array $atts Shortcode attributes
 * @return string Output HTML
 */
function llm_manual_timed_access_shortcode($atts) {
    // Only show to logged in users
    if (!is_user_logged_in()) {
        return '';
    }
    
    global $post;
    
    // Check if we're on a course or lesson/topic page
    $course_id = 0;
    if (function_exists('learndash_get_course_id')) {
        $course_id = learndash_get_course_id($post->ID);
    }
    
    if (!$course_id) {
        return '';
    }
    
    // Check if manual activation is required for this course
    $requires_activation = get_post_meta($course_id, '_lilac_requires_activation', true);
    if ($requires_activation !== 'yes') {
        return '';
    }
    
    // Get shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'product_ids' => '',       // Comma-separated product IDs
        'durations'   => '30',    // Comma-separated durations in days, matching product_ids
        'debug'       => 'no'     // Enable debug mode
    ), $atts, 'llm_manual_timed_access');
    
    $debug_mode = $atts['debug'] === 'yes' && current_user_can('manage_options');
    $required_product_ids = array_filter(array_map('intval', explode(',', $atts['product_ids'])));
    $durations = array_filter(array_map('intval', explode(',', $atts['durations'])));
    
    // If no product IDs specified, show to all users with course access
    if (!empty($required_product_ids)) {
        $user_id = get_current_user_id();
        $purchased_products = array();
        $product_durations = array_combine($required_product_ids, $durations);
        
        // Check which required products the user has purchased
        foreach ($required_product_ids as $index => $product_id) {
            if (wc_customer_bought_product('', $user_id, $product_id)) {
                $duration = isset($durations[$index]) ? $durations[$index] : end($durations);
                $purchased_products[$product_id] = $duration;
            }
        }
        
        if (empty($purchased_products)) {
            return $debug_mode ? '<div class="lilac-debug">' . __('No matching purchased products found.', 'lilac') . '</div>' : '';
        }
        
        // Get the product with the longest duration that the user has purchased
        $selected_product_id = array_search(max($purchased_products), $purchased_products);
        $access_duration_days = $purchased_products[$selected_product_id];
    } else {
        // Default to 30 days if no products specified
        $selected_product_id = 0;
        $access_duration_days = 30;
    }
    
    // Convert days to seconds for calculations
    $access_duration = $access_duration_days * DAY_IN_SECONDS;
    
    // Check if access is already active in our subscriptions table
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_user_subscriptions';
    $user_id = get_current_user_id();
    
    // Get active subscription for this course and user
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE user_id = %d AND course_id = %d 
        AND status = 'active' 
        AND access_expires > %s 
        ORDER BY access_expires DESC 
        LIMIT 1",
        $user_id,
        $course_id,
        current_time('mysql', true)
    ));
    
    $is_active = !empty($subscription);
    $access_expiry = $is_active ? strtotime($subscription->access_expires) : 0;
    $current_time = current_time('timestamp');
    
    if ($debug_mode) {
        echo '<div class="lilac-debug" style="background: #f5f5f5; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd;">';
        echo '<h4>Debug Info</h4>';
        echo '<pre>User ID: ' . $user_id . '</pre>';
        echo '<pre>Course ID: ' . $course_id . '</pre>';
        echo '<pre>Selected Product ID: ' . $selected_product_id . '</pre>';
        echo '<pre>Access Duration: ' . $access_duration_days . ' days</pre>';
        echo '<pre>Is Active: ' . ($is_active ? 'Yes' : 'No') . '</pre>';
        if ($is_active) {
            echo '<pre>Expires: ' . date('Y-m-d H:i:s', $access_expiry) . '</pre>';
            echo '<pre>Subscription ID: ' . $subscription->id . '</pre>';
        }
        echo '</div>';
    }
    
    // Start output buffering
    ob_start();
    ?>
    <div class="lilac-manual-access-container" style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
        <h3><?php _e('Course Access', 'lilac'); ?></h3>
        
        <?php if ($is_active) : ?>
            <div class="access-active" style="color: #0a7c31; margin-bottom: 10px;">
                <?php 
                printf(
                    __('Your access is active until %s', 'lilac'), 
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $access_expiry)
                );
                ?>
            </div>
        <?php else : ?>
            <div class="access-inactive" style="margin-bottom: 15px;">
                <p><?php _e('Click the button below to activate your course access.', 'lilac'); ?></p>
                <button type="button" id="lilac-activate-access" class="button" 
                        data-course="<?php echo esc_attr($course_id); ?>"
                        data-product="<?php echo esc_attr($selected_product_id); ?>"
                        data-duration="<?php echo esc_attr($access_duration_days); ?>">
                    <?php 
                    printf(
                        __('Activate %d-Day Access', 'lilac'),
                        $access_duration_days
                    );
                    ?>
                </button>
                <div id="lilac-access-message" style="margin-top: 10px; display: none;"></div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#lilac-activate-access').on('click', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this);
                    var $message = $('#lilac-access-message');
                    var courseId = $button.data('course');
                    var productId = $button.data('product');
                    var durationDays = $button.data('duration');
                    
                    if (!courseId) {
                        return;
                    }
                    
                    $button.prop('disabled', true).text('Activating...');
                    $message.hide().removeClass('success error').empty();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lilac_activate_course_access',
                            course_id: courseId,
                            product_id: productId,
                            duration_days: durationDays,
                            nonce: lilac_vars.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.html(response.data.message).addClass('success').show();
                                $button.closest('.access-inactive').html(
                                    '<div class="access-active" style="color: #0a7c31; margin: 10px 0;">' +
                                    '<p>Access active until ' + response.data.expiry_date + '</p>' +
                                    '<p>Duration: ' + response.data.duration_days + ' days</p>' +
                                    '</div>'
                                );
                            } else {
                                $message.html(response.data).addClass('error').show();
                                $button.prop('disabled', false).text($button.data('original-text') || 'Activate Access');
                                $message.html(response.data.message).addClass('error').fadeIn();
                                $button.prop('disabled', false).text('<?php echo esc_js(__('Try Again', 'lilac')); ?>');
                            }
                        },
                        error: function() {
                            $message.html('<?php echo esc_js(__('An error occurred. Please try again.', 'lilac')); ?>')
                                   .addClass('error')
                                   .fadeIn();
                            $button.prop('disabled', false).text('<?php echo esc_js(__('Activate Course Access', 'lilac')); ?>');
                        }
                    });
                });
            });
            </script>
            
            <style>
            .lilac-manual-access-container .button {
                background: #2271b1;
                color: #fff;
                border-color: #2271b1;
                padding: 8px 16px;
                height: auto;
                line-height: 1.5;
                font-size: 16px;
                font-weight: 600;
            }
            .lilac-manual-access-container .button:hover {
                background: #135e96;
                border-color: #135e96;
                color: #fff;
            }
            .access-active {
                color: #0a7c31;
                font-weight: 600;
                padding: 10px;
                background: #edfaef;
                border-radius: 3px;
                border-left: 4px solid #0a7c31;
            }
            #lilac-access-message {
                padding: 10px;
                margin-top: 10px;
                border-radius: 3px;
            }
            #lilac-access-message.success {
                background: #edfaef;
                border-left: 4px solid #0a7c31;
                color: #0a7c31;
            }
            #lilac-access-message.error {
                background: #fcf0f1;
                border-left: 4px solid #d63638;
                color: #d63638;
            }
            </style>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('llm_manual_timed_access', 'llm_manual_timed_access_shortcode');

/**
 * AJAX handler for activating course access
 */
function lilac_activate_course_access() {
    check_ajax_referer('lilac_activate_access', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to activate course access.', 'lilac'));
    }
    
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if (!$course_id) {
        wp_send_json_error(__('Invalid course ID.', 'lilac'));
    }
    
    $user_id = get_current_user_id();
    // Get duration from POST or default to 30 days
    $duration_days = isset($_POST['duration_days']) ? intval($_POST['duration_days']) : 30;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $access_duration = $duration_days * DAY_IN_SECONDS;
    $expiry_time = current_time('timestamp') + $access_duration;
    $expiry_date = date('Y-m-d H:i:s', $expiry_time);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_user_subscriptions';
    
    // Check for existing active subscription
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name 
        WHERE user_id = %d AND course_id = %d AND status = 'active' AND access_expires > %s",
        $user_id,
        $course_id,
        current_time('mysql', true)
    ));
    
    // If already has active access, don't create a new one
    if ($existing) {
        wp_send_json_error(__('You already have an active subscription for this course.', 'lilac'));
    }
    
    // Insert new subscription record
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'course_id' => $course_id,
            'access_started' => current_time('mysql'),
            'access_expires' => $expiry_date,
            'duration_days' => $duration_days,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error(__('Failed to activate access. Please try again.', 'lilac'));
    }
    
    // Grant access to the course
    if (function_exists('ld_update_course_access')) {
        ld_update_course_access($user_id, $course_id);
    }
    
    // Log the activation
    $log_message = sprintf(
        'User %d activated access to course %d until %s',
        $user_id,
        $course_id,
        date('Y-m-d H:i:s', $expiry_time)
    );
    
    if (function_exists('write_log')) {
        write_log($log_message);
    }
    
    wp_send_json_success(array(
        'message' => sprintf(
            __('Access activated! You now have access to this course until %s.', 'lilac'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expiry_time)
        ),
        'expiry' => $expiry_time,
        'duration_days' => $duration_days,
        'expiry_date' => date_i18n(get_option('date_format'), $expiry_time)
    ));
}
add_action('wp_ajax_lilac_activate_course_access', 'lilac_activate_course_access');

/**
 * Check course access when loading a course
 */
function lilac_check_course_access() {
    if (!is_singular(array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic'))) {
        return;
    }
    
    $course_id = 0;
    if (function_exists('learndash_get_course_id')) {
        $course_id = learndash_get_course_id(get_the_ID());
    }
    
    if (!$course_id || !is_user_logged_in()) {
        return;
    }
    
    // Check if manual activation is required for this course
    $requires_activation = get_post_meta($course_id, '_lilac_requires_activation', true);
    if ($requires_activation !== 'yes') {
        return;
    }
    
    // Check if user has active access
    $access_expiry = get_user_meta(get_current_user_id(), '_lilac_course_access_' . $course_id, true);
    $has_access = $access_expiry && $access_expiry > current_time('timestamp');
    
    // If access has expired, remove course access
    if ($access_expiry && $access_expiry <= current_time('timestamp')) {
        if (function_exists('ld_update_course_access')) {
            ld_update_course_access(get_current_user_id(), $course_id, true);
        }
        delete_user_meta(get_current_user_id(), '_lilac_course_access_' . $course_id);
    }
}
add_action('template_redirect', 'lilac_check_course_access', 5);

/**
 * Add the shortcode to the loader
 */
function lilac_add_manual_access_shortcode() {
    require_once __DIR__ . '/manual-timed-access-shortcode.php';
}
add_action('init', 'lilac_add_manual_access_shortcode', 20);
