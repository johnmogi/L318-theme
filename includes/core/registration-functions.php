<?php
/**
 * Registration Functions
 *
 * This file contains all functions related to the code-based registration system.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate registration code in the frontend
 * 
 * @param string $code The registration code to validate
 * @return array Status and message
 */
if (!function_exists('ccr_validate_code')) {
    function ccr_validate_code($code) {
        global $wpdb;
        
        if (empty($code)) {
            return [
                'status' => 'error',
                'message' => 'Please enter an access code.'
            ];
        }
        
        // Sanitize the code
        $code = sanitize_text_field($code);
        
        // Check if code exists
        $table_name = $wpdb->prefix . 'registration_codes';
        $code_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s",
            $code
        ));
        
        if (!$code_data) {
            return [
                'status' => 'error',
                'message' => 'Invalid access code. Please check and try again.'
            ];
        }
        
        // Check if code has been used too many times
        if ($code_data->max_uses > 0 && $code_data->used_count >= $code_data->max_uses) {
            return [
                'status' => 'error',
                'message' => 'This access code has reached its usage limit.'
            ];
        }
        
        // Check if code is expired
        if (!empty($code_data->expiry_date) && strtotime($code_data->expiry_date) < time()) {
            return [
                'status' => 'error',
                'message' => 'This access code has expired.'
            ];
        }
        
        // Valid code
        return [
            'status' => 'success',
            'message' => 'Valid access code.',
            'data' => $code_data
        ];
    }
}

/**
 * Mark a registration code as used
 * 
 * @param string $code The code to mark as used
 * @param int $user_id The user ID who used the code
 * @return bool Success status
 */
if (!function_exists('ccr_mark_code_used')) {
    function ccr_mark_code_used($code, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'registration_codes';
        
        // Update the used count
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET used_count = used_count + 1 WHERE code = %s",
            $code
        ));
        
        if ($result) {
            // Record this usage
            $usage_table = $wpdb->prefix . 'registration_code_usage';
            $wpdb->insert(
                $usage_table,
                [
                    'code' => $code,
                    'user_id' => $user_id,
                    'used_date' => current_time('mysql')
                ]
            );
            
            return true;
        }
        
        return false;
    }
}

/**
 * Handles a two-step registration flow requiring an access code.
 */
if (!function_exists('ccr_render_shortcode')) {
    function ccr_render_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            [
                'courses' => '',
                'redirect' => '',
                'teacher_code' => ''
            ],
            $atts,
            'code_registration'
        );
        
        ob_start();
        
        // If user is logged in, show message and return
        if (is_user_logged_in()) {
            echo '<p>You are already registered and logged in.</p>';
            return ob_get_clean();
        }
        
        // Step 1 - Show code entry form unless a code was submitted
        if (!isset($_POST['access_code'])) {
            ccr_show_code_form();
            return ob_get_clean();
        }
        
        // Validate submitted code
        $code = sanitize_text_field($_POST['access_code']);
        $validation = ccr_validate_code($code);
        
        if ($validation['status'] === 'error') {
            echo '<div class="alert alert-danger">' . esc_html($validation['message']) . '</div>';
            ccr_show_code_form();
            return ob_get_clean();
        }
        
        // Show registration form for validated code
        ccr_show_registration_form($code, $validation['data'], $atts['courses']);
        
        return ob_get_clean();
    }
}
add_shortcode('code_registration', 'ccr_render_shortcode');

/**
 * Display the access code input form
 */
if (!function_exists('ccr_show_code_form')) {
    function ccr_show_code_form() {
        ?>
        <div class="registration-container">
            <h2>Enter Access Code</h2>
            <p>Please enter your access code to continue with registration.</p>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="access-code">Access Code</label>
                    <input type="text" name="access_code" id="access-code" class="form-control" required>
                </div>
                
                <button type="submit" class="submit-button">Continue</button>
            </form>
        </div>
        <?php
    }
}

/**
 * Process the registration form submission and create a new user
 */
if (!function_exists('ccr_process_registration')) {
    function ccr_process_registration() {
        // Only process on non-admin pages and when the form is submitted
        if (is_admin() || !isset($_POST['ccr_registration_submit'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['ccr_registration_nonce']) || 
            !wp_verify_nonce($_POST['ccr_registration_nonce'], 'ccr_registration')) {
            return;
        }
        
        // Get and validate the access code first
        $code = sanitize_text_field($_POST['access_code']);
        $validation = ccr_validate_code($code);
        
        if ($validation['status'] === 'error') {
            // Invalid code, redirect back with error
            wp_redirect(add_query_arg('registration_error', urlencode($validation['message']), wp_get_referer()));
            exit;
        }
        
        // Process form data
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        
        // Validate data
        $error = null;
        
        if (empty($username)) {
            $error = 'Username is required.';
        } elseif (empty($email)) {
            $error = 'Email is required.';
        } elseif (!is_email($email)) {
            $error = 'Invalid email address.';
        } elseif (empty($password)) {
            $error = 'Password is required.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (username_exists($username)) {
            $error = 'Username already exists.';
        } elseif (email_exists($email)) {
            $error = 'Email already exists.';
        }
        
        // If there's an error, redirect back
        if ($error) {
            wp_redirect(add_query_arg('registration_error', urlencode($error), wp_get_referer()));
            exit;
        }
        
        // Create the user
        $user_data = [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'subscriber' // Default role
        ];
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg('registration_error', urlencode($user_id->get_error_message()), wp_get_referer()));
            exit;
        }
        
        // Registration successful, mark code as used
        ccr_mark_code_used($code, $user_id);
        
        // Check if there are courses to enroll in
        if (!empty($_POST['courses'])) {
            $courses = explode(',', sanitize_text_field($_POST['courses']));
            
            foreach ($courses as $course_id) {
                if (function_exists('ld_update_course_access')) {
                    ld_update_course_access($user_id, $course_id);
                }
            }
        }
        
        // Check if this is a teacher registration
        if (isset($_POST['is_teacher']) && $_POST['is_teacher'] === '1') {
            // Add the school_teacher role
            $user = new WP_User($user_id);
            $user->add_role('school_teacher');
            
            // Save the teacher meta
            update_user_meta($user_id, '_is_school_teacher', '1');
        }
        
        // Auto-login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Redirect to the appropriate page
        $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : home_url();
        wp_redirect(add_query_arg('registration_success', '1', $redirect));
        exit;
    }
}
add_action('init', 'ccr_process_registration');

/**
 * Update the registration form to include email field but remove unnecessary fields
 * Simplified version - automatically assigns role based on registration code
 * 
 * @param string $code The registration code
 * @param object $code_data The code data
 * @param string $courses_att Comma-separated course IDs
 */
if (!function_exists('ccr_show_registration_form')) {
    function ccr_show_registration_form($code, $code_data = null, $courses_att = '') {
        // Show registration form
        ?>
        <div class="registration-container">
            <h2>Create Your Account</h2>
            
            <?php if (isset($_GET['registration_error'])): ?>
                <div class="alert alert-danger">
                    <?php echo esc_html(urldecode($_GET['registration_error'])); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="hidden" name="access_code" value="<?php echo esc_attr($code); ?>">
                <input type="hidden" name="courses" value="<?php echo esc_attr($courses_att); ?>">
                
                <?php if ($code_data && isset($code_data->role) && $code_data->role === 'teacher'): ?>
                    <input type="hidden" name="is_teacher" value="1">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <?php wp_nonce_field('ccr_registration', 'ccr_registration_nonce'); ?>
                
                <button type="submit" name="ccr_registration_submit" class="submit-button">Register</button>
            </form>
        </div>
        <?php
    }
}

/**
 * Auto-enroll a user in the first course
 * 
 * @param int $user_id The user ID to enroll
 * @return bool Success status
 */
if (!function_exists('lilac_auto_enroll')) {
    function lilac_auto_enroll($user_id) {
        // Check if LearnDash is active
        if (!function_exists('learndash_user_get_enrolled_courses')) {
            return false;
        }
        
        // Get all courses
        $courses = get_posts([
            'post_type' => 'sfwd-courses',
            'numberposts' => 1,
            'fields' => 'ids'
        ]);
        
        if (empty($courses)) {
            return false;
        }
        
        // Enroll in the first course
        $course_id = $courses[0];
        $result = ld_update_course_access($user_id, $course_id);
        
        return (bool)$result;
    }
}
