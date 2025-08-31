<?php
/**
 * Student Expiration
 *
 * Handles the expiration date for school_student users (June 30, 2025)
 * Shows warning messages and restricts access when expired
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class StudentExpiration {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * School student expiration date (Y-m-d format)
     */
    private $expiration_date = '2025-06-30';
    
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
        // Add hooks for checking expiration
        add_action('wp_login', array($this, 'check_expiration_on_login'), 10, 2);
        add_action('template_redirect', array($this, 'check_page_access_for_expired_users'));
        add_filter('the_content', array($this, 'maybe_add_expiration_notice'));
        
        // Add expiration status to debug panel
        add_filter('lilac_debug_user_membership_info', array($this, 'add_expiration_info_to_debug'), 10, 2);
    }
    
    /**
     * Check if the user's school_student account has expired
     * 
     * @param int $user_id The user ID to check
     * @return bool True if expired, false otherwise
     */
    public function is_school_student_expired($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user || !in_array('school_student', $user->roles)) {
            return false;
        }
        
        // Check if current date is past expiration date
        $current_date = current_time('Y-m-d');
        $expired = $current_date > $this->expiration_date;
        
        if ($expired && function_exists('lilac_debug_log')) {
            lilac_debug_log("School student account expired", array(
                'user_id' => $user_id,
                'expiration_date' => $this->expiration_date,
                'current_date' => $current_date
            ));
        }
        
        return $expired;
    }
    
    /**
     * Check expiration on login and show admin notice
     */
    public function check_expiration_on_login($user_login, $user) {
        if ($this->is_school_student_expired($user->ID)) {
            // Add a cookie to show notice on next page load
            setcookie('lilac_expired_account', '1', time() + 86400, '/');
        }
    }
    
    /**
     * Check page access for expired users
     */
    public function check_page_access_for_expired_users() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$this->is_school_student_expired($user_id)) {
            return;
        }
        
        // Allow access to specific pages only (dashboard, account, etc)
        $allowed_pages = array(
            get_option('lilac_school_student_dashboard_page', 0),
            get_option('lilac_account_page', 0),
            get_option('lilac_support_page', 0),
            get_option('lilac_login_page', 0)
        );
        
        // Always allow admin and AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Check if current page is in allowed pages
        $current_page_id = get_the_ID();
        if (!in_array($current_page_id, $allowed_pages)) {
            // Redirect to dashboard with expired notice
            $dashboard_url = get_permalink(get_option('lilac_school_student_dashboard_page', 0));
            if ($dashboard_url) {
                wp_redirect(add_query_arg('expired', '1', $dashboard_url));
                exit;
            }
        }
    }
    
    /**
     * Add expiration notice to content
     */
    public function maybe_add_expiration_notice($content) {
        if (!is_user_logged_in()) {
            return $content;
        }
        
        $user_id = get_current_user_id();
        if (!$this->is_school_student_expired($user_id)) {
            return $content;
        }
        
        // Add notice at the top of the content
        $notice = '<div class="lilac-expired-notice" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;">';
        $notice .= '<h3 style="margin-top: 0;">' . __('פג תוקף המנוי שלך', 'hello-theme-child') . '</h3>';
        $notice .= '<p>' . __('תקופת הגישה לחשבון החינוך התעבורתי שלך הסתיימה. אנא פנה למורה שלך לקבלת סיוע או מידע נוסף.', 'hello-theme-child') . '</p>';
        $notice .= '<p>' . __('באפשרותך לרכוש מנוי במשרד הרישוי על אותו חשבון משתמש.', 'hello-theme-child') . '</p>';
        $notice .= '</div>';
        
        return $notice . $content;
    }
    
    /**
     * Add expiration info to debug panel
     */
    public function add_expiration_info_to_debug($debug_info, $user_id) {
        $user = get_userdata($user_id);
        if (!$user || !in_array('school_student', $user->roles)) {
            return $debug_info;
        }
        
        $is_expired = $this->is_school_student_expired($user_id);
        $expiration_info = '<li><strong>School Student Expiration:</strong> ' . $this->expiration_date;
        
        if ($is_expired) {
            $expiration_info .= ' <span style="color: red;">(EXPIRED)</span>';
        } else {
            $days_left = floor((strtotime($this->expiration_date) - current_time('timestamp')) / 86400);
            $expiration_info .= ' <span style="color: green;">(' . $days_left . ' days left)</span>';
        }
        
        $expiration_info .= '</li>';
        
        return $debug_info . $expiration_info;
    }
}

/**
 * Initialize the student expiration functionality
 */
function lilac_student_expiration() {
    return StudentExpiration::get_instance();
}

// Initialize on plugins loaded
add_action('plugins_loaded', 'Lilac\\Login\\lilac_student_expiration');
