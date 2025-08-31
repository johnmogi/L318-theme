<?php
/**
 * School Course Expiration Manager
 * 
 * Handles automatic expiration of course access for promo code students
 * Runs daily to check and remove expired access
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Course_Expiration_Manager {
    
    /**
     * Initialize the expiration manager
     */
    public static function init() {
        // Schedule daily cron job if not already scheduled
        if (!wp_next_scheduled('school_check_course_expiration')) {
            wp_schedule_event(time(), 'daily', 'school_check_course_expiration');
        }
        
        // Hook the cron job to our function
        add_action('school_check_course_expiration', array(__CLASS__, 'check_and_remove_expired_access'));
        
        // Also check on plugin activation/deactivation
        add_action('wp_loaded', array(__CLASS__, 'maybe_check_expiration'));
        
        // Add admin notice for expired students
        add_action('admin_notices', array(__CLASS__, 'show_expiration_notices'));
    }
    
    /**
     * Check and remove expired course access
     */
    public static function check_and_remove_expired_access() {
        global $wpdb;
        
        $current_time = current_time('mysql');
        $current_timestamp = time();
        
        // Get all users with promo course access
        $users_with_access = get_users(array(
            'meta_key' => 'school_promo_course_access',
            'meta_compare' => 'EXISTS',
            'fields' => 'ID'
        ));
        
        $expired_count = 0;
        $checked_count = 0;
        
        foreach ($users_with_access as $user_id) {
            $checked_count++;
            $access_data = get_user_meta($user_id, 'school_promo_course_access', true);
            
            if (!is_array($access_data) || empty($access_data['expires']) || empty($access_data['course_id'])) {
                continue;
            }
            
            $expiration_timestamp = strtotime($access_data['expires']);
            
            // Check if access has expired
            if ($expiration_timestamp < $current_timestamp) {
                $course_id = $access_data['course_id'];
                
                // Remove LearnDash course access
                if (function_exists('ld_update_course_access')) {
                    ld_update_course_access($user_id, $course_id, /* remove */ true);
                }
                
                // Update user meta to mark as expired
                update_user_meta($user_id, 'school_promo_course_access_expired', array(
                    'course_id' => $course_id,
                    'expired_on' => $current_time,
                    'original_expiration' => $access_data['expires']
                ));
                
                // Remove the active access meta
                delete_user_meta($user_id, 'school_promo_course_access');
                delete_user_meta($user_id, 'school_course_expiration_' . $course_id);
                
                // Set student status to inactive
                update_user_meta($user_id, 'school_student_status', 'expired');
                
                $expired_count++;
                
                // Log the expiration
                $user = get_user_by('ID', $user_id);
                error_log("School Manager Lite: Expired course access for user {$user->user_login} (ID: {$user_id}) from course {$course_id}");
            }
        }
        
        // Log summary
        error_log("School Manager Lite: Checked {$checked_count} users, expired {$expired_count} course accesses");
        
        // Store stats for admin notices
        update_option('school_last_expiration_check', array(
            'timestamp' => $current_time,
            'checked' => $checked_count,
            'expired' => $expired_count
        ));
        
        return array(
            'checked' => $checked_count,
            'expired' => $expired_count
        );
    }
    
    /**
     * Maybe check expiration on page load (for testing)
     */
    public static function maybe_check_expiration() {
        // Only run once per day automatically
        $last_check = get_option('school_last_expiration_check', array());
        
        if (empty($last_check['timestamp'])) {
            // Never checked before, run it
            self::check_and_remove_expired_access();
            return;
        }
        
        $last_check_time = strtotime($last_check['timestamp']);
        $one_day_ago = time() - (24 * 60 * 60);
        
        if ($last_check_time < $one_day_ago) {
            self::check_and_remove_expired_access();
        }
    }
    
    /**
     * Show admin notices about expiration checks
     */
    public static function show_expiration_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $last_check = get_option('school_last_expiration_check', array());
        
        if (empty($last_check)) {
            return;
        }
        
        // Only show notice if there were expirations in the last check
        if (!empty($last_check['expired']) && $last_check['expired'] > 0) {
            $message = sprintf(
                __('School Manager: Last expiration check found %d expired course accesses out of %d checked users. Check was run on %s.', 'school-manager-lite'),
                $last_check['expired'],
                $last_check['checked'],
                $last_check['timestamp']
            );
            
            echo '<div class="notice notice-info"><p>' . esc_html($message) . '</p></div>';
        }
    }
    
    /**
     * Get students with upcoming expiration (within 30 days)
     */
    public static function get_students_expiring_soon($days = 30) {
        $users_with_access = get_users(array(
            'meta_key' => 'school_promo_course_access',
            'meta_compare' => 'EXISTS',
            'fields' => 'all'
        ));
        
        $expiring_soon = array();
        $cutoff_timestamp = time() + ($days * 24 * 60 * 60);
        
        foreach ($users_with_access as $user) {
            $access_data = get_user_meta($user->ID, 'school_promo_course_access', true);
            
            if (!is_array($access_data) || empty($access_data['expires'])) {
                continue;
            }
            
            $expiration_timestamp = strtotime($access_data['expires']);
            
            if ($expiration_timestamp <= $cutoff_timestamp && $expiration_timestamp > time()) {
                $expiring_soon[] = array(
                    'user' => $user,
                    'expires' => $access_data['expires'],
                    'course_id' => $access_data['course_id'],
                    'days_left' => ceil(($expiration_timestamp - time()) / (24 * 60 * 60))
                );
            }
        }
        
        return $expiring_soon;
    }
    
    /**
     * Manual trigger for testing (admin only)
     */
    public static function manual_expiration_check() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = self::check_and_remove_expired_access();
        
        wp_die(sprintf(
            'Expiration check completed. Checked: %d users, Expired: %d course accesses.',
            $result['checked'],
            $result['expired']
        ));
    }
}

// Initialize the expiration manager
School_Course_Expiration_Manager::init();

// Add admin action for manual testing
add_action('wp_ajax_school_manual_expiration_check', array('School_Course_Expiration_Manager', 'manual_expiration_check'));
