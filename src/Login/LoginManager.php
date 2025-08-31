<?php
/**
 * Custom Login System
 *
 * Provides role-based login forms with custom redirection logic.
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class LoginManager {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Start session if not already started and headers haven't been sent
        if (!session_id() && !headers_sent()) {
            session_start();
        }

        // Register shortcodes
        add_shortcode('lilac_login', array($this, 'login_shortcode'));
        add_shortcode('lilac_teacher_lostpassword', array($this, 'teacher_lostpassword_shortcode'));
        add_shortcode('lilac_user_icon_box', array($this, 'user_icon_box_shortcode'));
        
        // Handle form submissions
        add_action('init', array($this, 'process_login'));
        add_action('init', array($this, 'process_lostpassword'));
        
        // Add custom login redirects
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
        
        // Add custom auth filters
        add_filter('authenticate', array($this, 'maybe_use_phone_number'), 20, 3);
        
        // Add login links to menu or header
        add_action('wp_footer', array($this, 'add_login_links_to_header'), 10);
        
        // Add debugging info for login attempts (only in dev/test environment)
        add_action('wp_footer', array($this, 'debug_login_info'), 999);
    }
    
    /**
     * Process the login form submission
     */
    public function process_login() {
        if (!isset($_POST['lilac_login_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['lilac_login_nonce']) || 
            !wp_verify_nonce($_POST['lilac_login_nonce'], 'lilac_login_action')) {
            return;
        }
        
        $username = sanitize_text_field($_POST['log']);
        $password = $_POST['pwd'];
        $remember = isset($_POST['rememberme']);
        
        // Store original username for debugging
        $_SESSION['lilac_login_debug_original'] = $username;
        
        // Check if the login is with email or phone number
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // Using email - standard WP login
            $login_field = $username;
            $_SESSION['lilac_login_debug_type'] = 'email';
        } else {
            // Format phone number as username (remove spaces, dashes, etc.)
            $login_field = preg_replace('/[^0-9]/', '', $username);
            $_SESSION['lilac_login_debug_type'] = 'phone';
        }
        
        // Store processed login field for debugging
        $_SESSION['lilac_login_debug_processed'] = $login_field;
        
        // Check if this is a teacher-specific login form
        $teacher_redirect = isset($_POST['teacher_redirect']) && $_POST['teacher_redirect'] === 'admin';
        if ($teacher_redirect) {
            $_SESSION['lilac_teacher_redirect'] = true;
        }
        
        // Attempt to log in
        $user = wp_signon(array(
            'user_login' => $login_field,
            'user_password' => $password,
            'remember' => $remember
        ), is_ssl());
        
        if (is_wp_error($user)) {
            // Store the error
            $_SESSION['lilac_login_error'] = $user->get_error_message();
            $_SESSION['lilac_login_debug_error'] = $user->get_error_code();
            
            // Redirect back to the login page
            $redirect = add_query_arg('login', 'failed', wp_get_referer());
            wp_safe_redirect($redirect);
            exit;
        }
        
        // Successful login
        $redirect_url = $this->get_redirect_url($user);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Custom authentication filter to allow login with phone number
     */
    public function maybe_use_phone_number($user, $username, $password) {
        // Log phone lookup attempt for debugging
        $_SESSION['lilac_login_debug_phone_lookup'] = $username;
        
        // If another authentication method has already worked, return it
        if (is_a($user, 'WP_User')) {
            $_SESSION['lilac_login_debug_auth_already_done'] = true;
            return $user;
        }
        
        // Check if username looks like a phone number (only digits)
        if (preg_match('/^[0-9]+$/', $username)) {
            global $wpdb;
            
            // Try to find a user with this phone number in the usermeta
            $query = $wpdb->prepare(
                "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'phone' AND meta_value = %s",
                $username
            );
            $user_id = $wpdb->get_var($query);
            
            // Store query info for debugging
            $_SESSION['lilac_login_debug_query'] = $query;
            $_SESSION['lilac_login_debug_found_id'] = $user_id;
            
            if ($user_id) {
                $user_obj = get_user_by('ID', $user_id);
                $_SESSION['lilac_login_debug_found_login'] = $user_obj->user_login;
                
                // Authenticate with the found user's login name
                $real_user = wp_authenticate_username_password(null, $user_obj->user_login, $password);
                
                if (is_wp_error($real_user)) {
                    $_SESSION['lilac_login_debug_pw_error'] = $real_user->get_error_code();
                    return $real_user; // Return the error
                }
                
                return $real_user; // Return authenticated user
            } else {
                // Also try looking for username that is phone number
                $user_with_phone_login = get_user_by('login', $username);
                if ($user_with_phone_login) {
                    $_SESSION['lilac_login_debug_direct_phone_login'] = true;
                    return wp_authenticate_username_password(null, $username, $password);
                }
                
                // Do a secondary lookup in all user meta to see if phone number exists
                $all_users = get_users(array('fields' => array('ID')));
                $_SESSION['lilac_login_debug_users_count'] = count($all_users);
                
                foreach ($all_users as $potential_user) {
                    $user_phone = get_user_meta($potential_user->ID, 'phone', true);
                    if ($user_phone === $username) {
                        $_SESSION['lilac_login_debug_alt_found'] = $potential_user->ID;
                        $alt_user_obj = get_user_by('ID', $potential_user->ID);
                        return wp_authenticate_username_password(null, $alt_user_obj->user_login, $password);
                    }
                }
            }
        }
        
        // If we didn't find a user, return the original result
        return $user;
    }
    
    /**
     * Get the appropriate redirect URL based on user role
     */
    /**
     * Get redirect URL for users based on their enrolled courses
     * 
     * @param WP_User $user The logged-in user object
     * @return string The redirect URL
     */
    private function get_redirect_url($user) {
        // Default to home page
        $redirect_url = home_url();
        $roles = (array) $user->roles;
        
        // Check if LearnDash is active
        if (function_exists('learndash_user_get_enrolled_courses')) {
            // Get all enrolled courses for the user
            $course_ids = learndash_user_get_enrolled_courses($user->ID, array(), true);
            
            if (!empty($course_ids)) {
                // Sort course IDs to get the lowest one
                sort($course_ids, SORT_NUMERIC);
                $lowest_course_id = $course_ids[0];
                $course_url = get_permalink($lowest_course_id);
                
                if ($course_url) {
                    return $course_url;
                }
            }
        }
        
        // Fallback to role-based redirects if no courses found or LearnDash not active
        
        // Students
        if (in_array('school_student', $roles)) {
            $redirect_url = get_permalink(get_option('lilac_school_student_dashboard_page', 0));
            if (!$redirect_url) {
                // Fallback to course page or dashboard
                $redirect_url = get_permalink(get_option('lilac_school_course_page', 0)) ?: home_url('/my-courses/');
            }
        }
        // Private Students / Clients
        elseif (in_array('student_private', $roles)) {
            $redirect_url = get_permalink(get_option('lilac_private_student_dashboard_page', 0));
            if (!$redirect_url) {
                // Fallback to course page or dashboard
                $redirect_url = get_permalink(get_option('lilac_private_course_page', 0)) ?: home_url('/my-courses/');
            }
        }
        // Teachers
        elseif (in_array('school_teacher', $roles)) {
            // Direct teachers to the admin dashboard page instead of frontend
            $redirect_url = admin_url('admin.php?page=class-management');
        }
        
        // Allow filtering the redirect URL
        return apply_filters('lilac_login_redirect', $redirect_url, $user);
    }
    
    /**
     * Custom login redirect filter
     */
    public function custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
        // If no user or there's already a specific redirect, return it
        if (!is_a($user, 'WP_User') || !empty($requested_redirect_to)) {
            return $redirect_to;
        }
        
        // Use our custom redirect logic
        return $this->get_redirect_url($user);
    }
    
    /**
     * Unified login form shortcode
     */
    /**
     * Process lost password form submission
     */
    public function process_lostpassword() {
        if (!isset($_POST['lilac_lostpassword_action'])) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['lilac_lostpassword_nonce']) || 
            !wp_verify_nonce($_POST['lilac_lostpassword_nonce'], 'lilac_lostpassword_action')) {
            wp_die(__('Security check failed', 'hello-child'));
        }

        $login = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : '';
        
        if (empty($login)) {
            $_SESSION['lilac_lostpassword_error'] = __('Please enter an email or phone number.', 'hello-child');
            return;
        }

        // Check if login is email or phone
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user_data = get_user_by('email', $login);
        } else {
            // Search by phone number (meta field)
            $users = get_users([
                'meta_key' => 'phone_number',
                'meta_value' => $login,
                'number' => 1
            ]);
            $user_data = !empty($users) ? $users[0] : false;
        }

        if (!$user_data) {
            $_SESSION['lilac_lostpassword_error'] = __('No user found with that email or phone number.', 'hello-child');
            return;
        }

        // Generate reset key and store in user meta
        $key = get_password_reset_key($user_data);
        if (is_wp_error($key)) {
            $_SESSION['lilac_lostpassword_error'] = $key->get_error_message();
            return;
        }

        // Send email with reset link
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        
        $subject = sprintf(__('[%s] Password Reset', 'hello-child'), get_bloginfo('name'));
        $message = __('Someone has requested a password reset for the following account:', 'hello-child') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s', 'hello-child'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'hello-child') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'hello-child') . "\r\n\r\n";
        $message .= $reset_url . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        if (wp_mail($user_email, $subject, $message, $headers)) {
            $_SESSION['lilac_lostpassword_success'] = __('Check your email for the password reset link.', 'hello-child');
        } else {
            $_SESSION['lilac_lostpassword_error'] = __('Failed to send reset email. Please try again later.', 'hello-child');
        }
    }

    /**
     * Teacher lost password shortcode
     */
    public function teacher_lostpassword_shortcode($atts = []) {
        // Start output buffering
        ob_start();
        
        // Handle any messages
        $message = '';
        if (isset($_SESSION['lilac_lostpassword_error'])) {
            $message = '<div class="error">' . esc_html($_SESSION['lilac_lostpassword_error']) . '</div>';
            unset($_SESSION['lilac_lostpassword_error']);
        } elseif (isset($_SESSION['lilac_lostpassword_success'])) {
            $message = '<div class="success">' . esc_html($_SESSION['lilac_lostpassword_success']) . '</div>';
            unset($_SESSION['lilac_lostpassword_success']);
        }
        
        // Include the template
        include_once dirname(__FILE__) . '/templates/teacher-lostpassword-form.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Login shortcode
     */
    public function login_shortcode($atts = []) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'redirect' => '',
            'form_title' => 'התחברות לאתר',
            'form_description' => 'התחבר באמצעות מספר הטלפון/אימייל והסיסמה שלך',
            'login_button_text' => 'התחבר',
            'form_class' => 'lilac-login-form',
            'show_registration_links' => 'true',
        ), $atts, 'lilac_login');
        
        // Start output buffering
        ob_start();
        
        // Include the login form template
        include_once dirname(__FILE__) . '/templates/unified-login-form.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Add login/register links to header
     */
    public function add_login_links_to_header() {
        // Skip if user is logged in
        if (is_user_logged_in()) {
            return;
        }
        
        // Skip on login/register pages
        if (is_page(get_option('lilac_login_page', 0)) || is_page(get_option('lilac_register_page', 0))) {
            return;
        }
        
        $login_url = get_permalink(get_option('lilac_login_page', 0)) ?: wp_login_url();
        $school_register_url = get_permalink(get_option('lilac_school_register_page', 0)) ?: '#';
        $private_register_url = get_permalink(get_option('lilac_private_register_page', 0)) ?: '#';
        
        // Add the header links HTML
        ?>
        <div class="lilac-header-login-links">
            <div class="lilac-header-login-container">
                <div class="login-links-wrapper">
                    <div class="login-link register-school">
                        <a href="<?php echo esc_url($school_register_url); ?>">הרשמה <span class="bold">לחינוך התעבורתי</span></a>
                    </div>
                    <div class="login-link register-private">
                        <a href="<?php echo esc_url($private_register_url); ?>">הרשמה <span class="bold">ללקוחות פרטיים</span></a>
                    </div>
                    <div class="login-link login-existing">
                        <a href="<?php echo esc_url($login_url); ?>">מנוי קיים <span class="bold">התחבר</span></a>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .lilac-header-login-links {
                display: block;
                width: 100%;
                background-color: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
                direction: rtl;
            }
            
            .lilac-header-login-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 10px 20px;
            }
            
            .login-links-wrapper {
                display: flex;
                justify-content: flex-start;
                align-items: center;
            }
            
            .login-link {
                margin-left: 20px;
            }
            
            .login-link a {
                color: #2c3e50;
                text-decoration: none;
                font-size: 14px;
                transition: color 0.3s ease;
            }
            
            .login-link a:hover {
                color: #3498db;
            }
            
            .login-link a .bold {
                font-weight: 700;
            }
            
            @media (max-width: 768px) {
                .login-links-wrapper {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .login-link {
                    margin-left: 0;
                    margin-bottom: 10px;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Output debugging information for admin users
     */
    /**
     * User icon box shortcode
     * 
     * Displays a user icon with login/profile link
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function user_icon_box_shortcode($atts = []) {
        // Parse attributes
        $atts = shortcode_atts([
            'login_text' => __('התחברות', 'hello-child'),
            'profile_text' => __('פרופיל', 'hello-child'),
            'login_url' => wp_login_url(),
            'profile_url' => get_edit_profile_url(),
            'class' => 'elementor-icon-box-wrapper',
        ], $atts, 'lilac_user_icon_box');
        
        // Get current user
        $current_user = wp_get_current_user();
        $is_logged_in = is_user_logged_in();
        
        // Prepare user display name or login text
        $display_text = $is_logged_in ? 
            sprintf(__('היי %s', 'hello-child'), $current_user->display_name) : 
            $atts['login_text'];
            
        // Prepare link URL
        $link_url = $is_logged_in ? $atts['profile_url'] : $atts['login_url'];
        
        // Start output buffering
        ob_start();
        ?>
        <div class="elementor-element elementor-position-right elementor-mobile-position-right elementor-vertical-align-bottom elementor-widget-mobile__width-initial elementor-view-default elementor-widget elementor-widget-icon-box">
            <div class="elementor-widget-container">
                <div class="<?php echo esc_attr($atts['class']); ?>">
                    <div class="elementor-icon-box-icon">
                        <a href="<?php echo esc_url($link_url); ?>" class="elementor-icon" tabindex="-1" aria-label="<?php echo esc_attr($is_logged_in ? $atts['profile_text'] : $atts['login_text']); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="22" viewBox="0 0 21 22" fill="none">
                                <path d="M0.999993 20.7828C1.59374 9.11652 19.1563 9.11652 19.75 20.7828M6.625 4.53276C6.625 5.52732 7.02009 6.48115 7.72336 7.18442C8.42662 7.88768 9.38045 8.28277 10.375 8.28277C11.3696 8.28277 12.3234 7.88768 13.0267 7.18442C13.7299 6.48115 14.125 5.52732 14.125 4.53276C14.125 3.5382 13.7299 2.58437 13.0267 1.8811C12.3234 1.17784 11.3696 0.782752 10.375 0.782752C9.38045 0.782752 8.42662 1.17784 7.72336 1.8811C7.02009 2.58437 6.625 3.5382 6.625 4.53276Z" 
                                    stroke="currentColor" 
                                    stroke-width="1.5" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round">
                                </path>
                            </svg>
                        </a>
                    </div>
                    <div class="elementor-icon-box-content">
                        <h3 class="elementor-icon-box-title">
                            <a href="<?php echo esc_url($link_url); ?>">
                                <?php echo esc_html($display_text); ?>
                            </a>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
        <?php
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Output debugging information for admin users
     */
    public function debug_login_info() {
        // Only show to admins and if there's debug data
        if (!current_user_can('manage_options') && 
            !isset($_GET['debug']) && 
            empty($_SESSION['lilac_login_debug_original'])) {
            return;
        }
        
        if (isset($_GET['login']) && $_GET['login'] === 'failed' && !empty($_SESSION['lilac_login_debug_original'])) {
            echo '<div class="lilac-login-debug" style="max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; direction: ltr; text-align: left; font-family: monospace;">';
            echo '<h3>Login Debug Information</h3>';
            
            echo '<ul>';
            echo '<li><strong>Original Input:</strong> ' . esc_html($_SESSION['lilac_login_debug_original']) . '</li>';
            echo '<li><strong>Input Type:</strong> ' . esc_html($_SESSION['lilac_login_debug_type'] ?? 'unknown') . '</li>';
            echo '<li><strong>Processed Login:</strong> ' . esc_html($_SESSION['lilac_login_debug_processed'] ?? 'not processed') . '</li>';
            echo '<li><strong>Error Code:</strong> ' . esc_html($_SESSION['lilac_login_debug_error'] ?? 'no error') . '</li>';
            
            if (isset($_SESSION['lilac_login_debug_phone_lookup'])) {
                echo '<li><strong>Phone Lookup Attempt:</strong> ' . esc_html($_SESSION['lilac_login_debug_phone_lookup']) . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_auth_already_done'])) {
                echo '<li><strong>Auth Already Done:</strong> Yes</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_query'])) {
                echo '<li><strong>DB Query:</strong> ' . esc_html($_SESSION['lilac_login_debug_query']) . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_found_id'])) {
                echo '<li><strong>Found User ID:</strong> ' . esc_html($_SESSION['lilac_login_debug_found_id'] ?? 'none') . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_found_login'])) {
                echo '<li><strong>Found Username:</strong> ' . esc_html($_SESSION['lilac_login_debug_found_login']) . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_pw_error'])) {
                echo '<li><strong>Password Error:</strong> ' . esc_html($_SESSION['lilac_login_debug_pw_error']) . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_direct_phone_login'])) {
                echo '<li><strong>Direct Phone Login Attempt:</strong> Yes</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_users_count'])) {
                echo '<li><strong>Total Users Searched:</strong> ' . esc_html($_SESSION['lilac_login_debug_users_count']) . '</li>';
            }
            
            if (isset($_SESSION['lilac_login_debug_alt_found'])) {
                echo '<li><strong>Alternative User Found:</strong> ' . esc_html($_SESSION['lilac_login_debug_alt_found']) . '</li>';
            }
            
            // Add database query to check if phone meta exists at all
            global $wpdb;
            $phone_meta_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'phone'");
            echo '<li><strong>Total phone meta entries:</strong> ' . esc_html($phone_meta_count) . '</li>';
            
            // Check if the specific phone number exists in ANY meta
            $specific_phone = isset($_SESSION['lilac_login_debug_original']) ? $_SESSION['lilac_login_debug_original'] : '';
            if (!empty($specific_phone)) {
                $formatted_phone = preg_replace('/[^0-9]/', '', $specific_phone);
                $phone_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_value = %s",
                    $formatted_phone
                ));
                echo '<li><strong>This phone number in ANY meta:</strong> ' . esc_html($phone_exists) . '</li>';
                
                // Also check if it exists as a username
                $user_with_phone = get_user_by('login', $formatted_phone);
                echo '<li><strong>User with this phone as username:</strong> ' . ($user_with_phone ? 'Yes (ID: '.$user_with_phone->ID.')' : 'No') . '</li>';
            }
            
            echo '</ul>';
            
            // Add registration code info
            echo '<h4>Registration Process Information:</h4>';
            echo '<p>Check if registration properly stores phone number as user meta.</p>';
            
            echo '</div>';
            
            // Clear the debug info to avoid showing on subsequent page loads
            unset($_SESSION['lilac_login_debug_original']);
            unset($_SESSION['lilac_login_debug_type']);
            unset($_SESSION['lilac_login_debug_processed']);
            unset($_SESSION['lilac_login_debug_error']);
            unset($_SESSION['lilac_login_debug_phone_lookup']);
            unset($_SESSION['lilac_login_debug_auth_already_done']);
            unset($_SESSION['lilac_login_debug_query']);
            unset($_SESSION['lilac_login_debug_found_id']);
            unset($_SESSION['lilac_login_debug_found_login']);
            unset($_SESSION['lilac_login_debug_pw_error']);
            unset($_SESSION['lilac_login_debug_direct_phone_login']);
            unset($_SESSION['lilac_login_debug_users_count']);
        }
    }
    
    /**
     * Initialize the plugin
     * 
     * @return LoginManager|null Instance of LoginManager or null if not available
     */
    public static function init() {
        if (!is_admin() && !(defined('WP_CLI') && WP_CLI)) {
            if (class_exists('Lilac\\Login\\LoginManager')) {
                return self::get_instance();
            }
        }
        return null;
    }
}

// Only add the action if we're not in admin
if (!is_admin() && !(defined('WP_CLI') && WP_CLI)) {
    add_action('init', ['Lilac\\Login\\LoginManager', 'init']);
}
