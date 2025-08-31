<?php
/**
 * Promotion Code System
 * 
 * Handles promotion code validation and processing
 * for LearnDash course enrollment
 * 
 * Provides a shortcode [promo_code] that displays a form
 * for users to enter their promotion code and enroll in courses.
 * 
 * Usage examples:
 * [promo_code] - Default promotion code form for the default course
 * [promo_code course_id="123"] - Promotion code form for course with ID 123
 * [promo_code button_text="החל קוד"] - Custom submit button text in Hebrew
 * 
 * @version 2.0
 * @updated 2025-07-08
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the promotion code shortcode
 */
function lilac_register_promo_code_shortcode() {
    add_shortcode('promo_code', 'lilac_promo_code_display');
}
add_action('init', 'lilac_register_promo_code_shortcode');

/**
 * Display the promotion code input form in Hebrew
 */
function lilac_promo_code_display($atts = []) {
    // Initialize error logging
    $debug = defined('WP_DEBUG') && WP_DEBUG;
    
    // Default Hebrew text
    $defaults = [
        'title' => 'הזנת קוד הרשמה',
        'description' => 'אנא הזן את קוד ההטבה שקיבלת עם הרכישה',
        'button_text' => 'אישור קוד',
        'input_placeholder' => 'הזן את קוד ההטבה שלך',
        'success_message' => 'הקוד אומת בהצלחה! מפנה אותך לקורס...',
        'error_message' => 'קוד לא תקין. אנא נסה שוב.',
        'code_expired' => 'קוד ההטבה הזה פג תוקף.',
        'code_max_uses' => 'קוד ההטבה כבר לא פעיל אנא פנו לתמיכה',
        'code_wrong_course' => 'קוד ההטבה הזה אינו תקף עבור הקורס הזה.',
        'code_already_used' => 'הקוד כבר נעשה בו שימוש. נא להשתמש בקוד אחר.',
        'code_not_found' => 'הקוד שהזנת אינו קיים במערכת.',
        'code_inactive' => 'קוד ההטבה אינו פעיל.',
        'enrollment_failed' => 'הקוד אומת אך הייתה בעיה בהרשמה לקורס. אנא צור קשר עם התמיכה.',
        'login_required' => 'נא להתחבר למערכת לפני הזנת קוד הטבה.',
        'course_id' => 898, // Default course ID
        'redirect_url' => '', // Will be set to course URL if empty
        'redirect_delay' => 2000, // Delay in milliseconds before redirect (2 seconds)
        'auto_redirect' => 'true' // Whether to automatically redirect on success
    ];
    
    // Parse shortcode attributes
    $atts = shortcode_atts($defaults, $atts, 'promo_code');
    
    // Start output buffering
    ob_start();
    
    // Check if form was submitted
    $submitted_code = '';
    $response = [];
    
    if (isset($_POST['lilac_promo_code_submit']) && !empty($_POST['lilac_promo_code'])) {
        // Verify nonce for security
        if (!isset($_POST['lilac_promo_code_nonce']) || !wp_verify_nonce($_POST['lilac_promo_code_nonce'], 'lilac_promo_code_action')) {
            if ($debug) {
                error_log('Promo code security check failed: invalid nonce');
            }
            echo '<div class="lilac-alert lilac-alert-error">' . esc_html__('Security check failed. Please refresh the page and try again.', 'lilac') . '</div>';
            return ob_get_clean();
        }
        
        // Rate limiting to prevent abuse (max 5 attempts per minute per user)
        $user_id = get_current_user_id();
        $rate_limit_key = 'lilac_promo_code_attempts_' . $user_id;
        $attempts = get_transient($rate_limit_key) ?: 0;
        
        if ($attempts >= 5) {
            if ($debug) {
                error_log(sprintf('Rate limit exceeded for user %d: %d attempts', $user_id, $attempts));
            }
            echo '<div class="lilac-alert lilac-alert-error">' . esc_html__('Too many attempts. Please try again later.', 'lilac') . '</div>';
            return ob_get_clean();
        }
        
        // Increment attempt counter
        set_transient($rate_limit_key, $attempts + 1, 60); // 60 seconds expiry
        
        // Sanitize and validate input
        $submitted_code = sanitize_text_field($_POST['lilac_promo_code']);
        $course_id = absint($atts['course_id']);
        
        if (empty($submitted_code)) {
            echo '<div class="lilac-alert lilac-alert-error">' . esc_html__('Please enter a valid code.', 'lilac') . '</div>';
            return ob_get_clean();
        }
        
        // Log the attempt if debugging is enabled
        if ($debug) {
            error_log(sprintf('Promo code attempt: %s for course %d by user %d', $submitted_code, $course_id, $user_id));
        }
        
        // Validate the code
        $response = lilac_validate_promo_code($submitted_code, $course_id);
        
        // Get custom message if available, otherwise use the default message from the response
        if ($response['status']) {
            // Success case
            $message = $response['message'];
            echo '<div class="lilac-alert lilac-alert-success">' . esc_html($message) . '</div>';
            
            // Add JavaScript for redirect if auto_redirect is enabled
            if (!empty($response['redirect']) && $atts['auto_redirect'] !== 'false') {
                $redirect_url = !empty($atts['redirect_url']) ? $atts['redirect_url'] : $response['redirect'];
                $redirect_delay = absint($atts['redirect_delay']) ?: 2000;
                
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "' . esc_url($redirect_url) . '";
                    }, ' . $redirect_delay . ');
                </script>';
            }
        } else {
            // Error case - use specific error message if available
            $error_message = $response['message'];
            $error_type = isset($response['error_type']) ? $response['error_type'] : 'general';
            
            // Use specific error message from shortcode attributes if available
            if (isset($atts['code_' . $error_type]) && !empty($atts['code_' . $error_type])) {
                $error_message = $atts['code_' . $error_type];
            } elseif ($error_type !== 'general' && isset($atts['error_message'])) {
                // Fall back to general error message
                $error_message = $atts['error_message'];
            }
            
            echo '<div class="lilac-alert lilac-alert-error">' . esc_html($error_message) . '</div>';
        }
    }
    
    // Show login message if user is not logged in
    if (!is_user_logged_in()) {
        echo '<div class="lilac-alert lilac-alert-warning">' . esc_html($atts['login_required']) . '</div>';
        echo '<div class="lilac-login-prompt">';
        echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="lilac-login-button">התחברות</a>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Show the form
    ?>
    <div class="lilac-promo-code-container">
        <?php if (!empty($atts['title'])) : ?>
            <h2 class="lilac-promo-code-title" style="text-align: right; direction: rtl;"><?php echo esc_html($atts['title']); ?></h2>
        <?php endif; ?>
        
        <?php if (!empty($atts['description'])) : ?>
            <p class="lilac-promo-code-description" style="text-align: right; direction: rtl;"><?php echo esc_html($atts['description']); ?></p>
        <?php endif; ?>
        
        <form method="post" class="lilac-promo-code-form">
            <?php wp_nonce_field('lilac_promo_code_action', 'lilac_promo_code_nonce'); ?>
            <div class="lilac-form-group">
                <input type="text" 
                   name="lilac_promo_code" 
                   value="<?php echo esc_attr($submitted_code); ?>" 
                   placeholder="<?php echo esc_attr($atts['input_placeholder']); ?>" 
                   class="lilac-promo-code-input"
                   required
                   maxlength="50"
                   dir="rtl"
                   style="text-align: right;">
            </div>
            
            <div class="lilac-form-group">
                <input type="submit" 
                       name="lilac_promo_code_submit" 
                       value="<?php echo esc_attr($atts['button_text']); ?>" 
                       class="lilac-promo-code-button"
                       style="width: auto; padding: 10px 20px;">
            </div>
        </form>
    </div>
    <?php
    
    // Add some basic styles if not already enqueued
    if (!wp_style_is('lilac-promo-code-css', 'enqueued')) {
        echo '<style>
            .lilac-promo-code-container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                text-align: right;
                direction: rtl;
            }
            
            .lilac-promo-code-title {
                color: #333;
                margin-bottom: 15px;
            }
            
            .lilac-promo-code-description {
                color: #666;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            
            .lilac-promo-code-form {
                margin: 20px 0;
            }
            
            .lilac-form-group {
                margin-bottom: 15px;
            }
            
            .lilac-promo-code-input {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px;
                margin-bottom: 10px;
            }
            
            .lilac-promo-code-button {
                background-color: #0073aa;
                color: white;
                border: none;
                padding: 12px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s;
            }
            
            .lilac-promo-code-button:hover {
                background-color: #005177;
            }
            
            .lilac-alert {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
                text-align: right;
            }
            
            .lilac-alert-success {
                background-color: #edfaef;
                border: 1px solid #00a32a;
                color: #00a32a;
            }
            
            .lilac-alert-error {
                background-color: #fcf0f1;
                border: 1px solid #d63638;
                color: #d63638;
            }
            
            .lilac-alert-warning {
                background-color: #fef8e7;
                border: 1px solid #dba617;
                color: #744c00;
            }
            
            .lilac-login-prompt {
                text-align: center;
                margin: 20px 0;
            }
            
            .lilac-login-button {
                display: inline-block;
                background-color: #2271b1;
                color: white;
                padding: 10px 20px;
                border-radius: 4px;
                text-decoration: none;
                font-weight: bold;
            }
            
            .lilac-login-button:hover {
                background-color: #135e96;
                color: white;
            }
            
            @media (max-width: 480px) {
                .lilac-promo-code-container {
                    padding: 15px;
                }
                
                .lilac-promo-code-input,
                .lilac-promo-code-button {
                    width: 100%;
                }
            }
        </style>';
    }
    
    return ob_get_clean();
}

/**
 * Validate a promotion code against the new database table
 * 
 * @param string $code The code to validate
 * @param int $course_id The course ID to enroll in if code is valid
 * @return array Array with 'status' (bool), 'message' (string), and 'error_type' (string) on error
 */
function lilac_validate_promo_code($code, $course_id = 898) {
    global $wpdb;
    
    // Trim whitespace and convert to uppercase for consistency
    $code = strtoupper(trim($code));
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return [
            'status' => false,
            'message' => 'נא להתחבר למערכת לפני הזנת קוד הטבה.',
            'error_type' => 'login_required'
        ];
    }
    
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'lilac_promo_codes';
    $usage_table = $wpdb->prefix . 'lilac_promo_code_usage';
    
    // Check if the promo code table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("Promo code table $table_name does not exist");
        return [
            'status' => false,
            'message' => 'מערכת קודי ההטבה אינה מוגדרת כראוי. אנא צור קשר עם התמיכה.',
            'error_type' => 'system_error'
        ];
    }
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Check if code exists
        $code_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s",
            $code
        ));
        
        if (!$code_data) {
            $wpdb->query('ROLLBACK');
            return [
                'status' => false,
                'message' => 'הקוד שהזנת אינו קיים במערכת.',
                'error_type' => 'not_found'
            ];
        }
        
        // Check if code is active
        if ($code_data->is_active != 1) {
            $wpdb->query('ROLLBACK');
            return [
                'status' => false,
                'message' => 'קוד ההטבה אינו פעיל.',
                'error_type' => 'inactive'
            ];
        }
        
        // Check if code is for specific course
        if (!empty($code_data->course_id) && $code_data->course_id != $course_id) {
            $wpdb->query('ROLLBACK');
            return [
                'status' => false,
                'message' => 'קוד ההטבה הזה אינו תקף עבור הקורס הזה.',
                'error_type' => 'wrong_course'
            ];
        }
        
        // Check if code has expired
        if (!empty($code_data->expiry_date) && strtotime($code_data->expiry_date) < current_time('timestamp')) {
            $wpdb->query('ROLLBACK');
            return [
                'status' => false,
                'message' => 'קוד ההטבה הזה פג תוקף.',
                'error_type' => 'expired'
            ];
        }
        
        // Check if code has reached maximum uses
        if ($code_data->used_count >= $code_data->max_uses) {
            $wpdb->query('ROLLBACK');
            return [
                'status' => false,
                'message' => 'קוד ההטבה כבר לא פעיל אנא פנו לתמיכה',
                'error_type' => 'max_uses'
            ];
        }
        
        // Check if user has already used this code (for one-time use codes)
        if ($code_data->max_uses == 1) {
            $user_used_code = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $usage_table WHERE code_id = %d AND user_id = %d",
                $code_data->id,
                $user_id
            ));
            
            if ($user_used_code) {
                $wpdb->query('ROLLBACK');
                return [
                    'status' => false,
                    'message' => 'כבר השתמשת בקוד הטבה זה.',
                    'error_type' => 'already_used_by_user'
                ];
            }
        }
        
        // Update usage count
        $updated = $wpdb->update(
            $table_name,
            ['used_count' => $code_data->used_count + 1],
            ['id' => $code_data->id],
            ['%d'],
            ['%d']
        );
        
        if ($updated === false) {
            $wpdb->query('ROLLBACK');
            error_log('Failed to update promo code usage count: ' . $wpdb->last_error);
            return [
                'status' => false,
                'message' => 'שגיאת מערכת בעדכון נתוני הקוד. אנא נסה שוב מאוחר יותר.',
                'error_type' => 'update_failed'
            ];
        }
        
        // Record usage
        $inserted = $wpdb->insert(
            $usage_table,
            [
                'code_id' => $code_data->id,
                'user_id' => $user_id,
                'used_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
        
        if ($inserted === false) {
            $wpdb->query('ROLLBACK');
            error_log('Failed to record promo code usage: ' . $wpdb->last_error);
            return [
                'status' => false,
                'message' => 'שגיאת מערכת ברישום השימוש בקוד. אנא נסה שוב מאוחר יותר.',
                'error_type' => 'record_failed'
            ];
        }
        
        // If this was the last usage, mark code as inactive
        if (($code_data->used_count + 1) >= $code_data->max_uses) {
            $wpdb->update(
                $table_name,
                ['is_active' => 0],
                ['id' => $code_data->id],
                ['%d'],
                ['%d']
            );
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Enroll user in course
        $enrollment_result = lilac_enroll_user_in_course($user_id, $course_id);
        
        if (is_wp_error($enrollment_result)) {
            error_log('Course enrollment failed: ' . $enrollment_result->get_error_message());
            return [
                'status' => false,
                'message' => 'הקוד אומת אך הייתה בעיה בהרשמה לקורס. אנא צור קשר עם התמיכה.',
                'error_type' => 'enrollment_failed'
            ];
        }
        
        // Get course URL for redirect
        $redirect_url = get_permalink($course_id);
        if (empty($redirect_url)) {
            $redirect_url = home_url('/my-courses/');
        }
        
        return [
            'status' => true,
            'message' => 'ההרשמה בוצעה בהצלחה! מפנה אותך לקורס...',
            'redirect' => $redirect_url
        ];
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Promo code validation exception: ' . $e->getMessage());
        
        return [
            'status' => false,
            'message' => 'אירעה שגיאה בעיבוד הבקשה. אנא נסה שוב מאוחר יותר.',
            'error_type' => 'system_error'
        ];
    }
}

/**
 * Enroll a user in a course
 * 
 * @param int $user_id User ID
 * @param int $course_id Course ID
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function lilac_enroll_user_in_course($user_id, $course_id) {
    // Check if LearnDash is active
    if (!function_exists('ld_update_course_access')) {
        error_log('LearnDash functions not available for course enrollment');
        return new WP_Error('learnmash_inactive', 'LearnDash not active');
    }
    
    // Verify user and course exist
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'User not found');
    }
    
    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'sfwd-courses') {
        return new WP_Error('invalid_course', 'Course not found');
    }
    
    // Check if user is already enrolled
    $is_enrolled = sfwd_lms_has_access($course_id, $user_id);
    
    if ($is_enrolled) {
        return true; // User already has access
    }
    
    // Enroll user in course
    $result = ld_update_course_access($user_id, $course_id);
    
    if (!$result) {
        return new WP_Error('enrollment_failed', 'Failed to enroll user in course');
    }
    
    // Log the enrollment
    error_log(sprintf('User %d enrolled in course %d via promo code', $user_id, $course_id));
    
    return true;
}

/**
 * Handle redirect after successful code validation
 */
function lilac_promo_code_redirect() {
    if (isset($_GET['lilac_redirect']) && !empty($_GET['lilac_redirect'])) {
        $redirect_url = esc_url_raw($_GET['lilac_redirect']);
        
        // Verify the redirect URL is valid (internal)
        $home_url = home_url();
        $redirect_url_host = parse_url($redirect_url, PHP_URL_HOST);
        $home_url_host = parse_url($home_url, PHP_URL_HOST);
        
        if ($redirect_url_host === $home_url_host) {
            wp_redirect($redirect_url);
            exit;
        }
    }
}
add_action('template_redirect', 'lilac_promo_code_redirect');
