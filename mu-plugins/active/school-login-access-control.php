<?php
/**
 * School Login Access Control
 * 
 * Ensures that students only have access to their assigned course
 * Runs on every login to enforce course access restrictions
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Login_Access_Control {
    
    /**
     * Initialize the access control
     */
    public static function init() {
        error_log('School Manager Lite: LOGIN ACCESS CONTROL PLUGIN INITIALIZED');
        
        // Hook into user login to enforce course access
        add_action('wp_login', array(__CLASS__, 'enforce_course_access_on_login'), 10, 2);
        
        // Also check on wp_loaded for logged-in users
        add_action('wp_loaded', array(__CLASS__, 'maybe_enforce_course_access'));
        
        // Hook into LearnDash course access checks - MULTIPLE LEVELS
        add_filter('learndash_course_access_from', array(__CLASS__, 'filter_course_access'), 10, 2);
        add_filter('learndash_user_course_access', array(__CLASS__, 'filter_user_course_access'), 10, 3);
        add_filter('learndash_course_access', array(__CLASS__, 'filter_general_course_access'), 10, 2);
        add_action('learndash_course_access_redirect', array(__CLASS__, 'block_unauthorized_course_access'), 10, 2);
        
        // ACTIVE BLOCKING: Block specific courses 3267 and 423 for ALL users except admins
        add_filter('learndash_user_has_access', array(__CLASS__, 'actively_block_restricted_courses'), 999, 3);
        
        // Block course content access
        add_filter('the_content', array(__CLASS__, 'filter_course_content'), 999);
        
        // Add debug hook to see what's happening
        add_action('wp_loaded', array(__CLASS__, 'debug_current_user'), 999);
    }
    
    /**
     * Enforce course access when user logs in
     */
    public static function enforce_course_access_on_login($user_login, $user) {
        // Only apply to student_private role
        if (!in_array('student_private', $user->roles)) {
            return;
        }
        
        self::enforce_single_course_access($user->ID);
    }
    
    /**
     * Maybe enforce course access for already logged-in users
     */
    public static function maybe_enforce_course_access() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user = wp_get_current_user();
        
        // Only apply to student_private role
        if (!in_array('student_private', $user->roles)) {
            return;
        }
        
        // Only run once per session to avoid performance issues
        if (get_user_meta($user->ID, 'school_access_checked_' . date('Y-m-d'), true)) {
            return;
        }
        
        self::enforce_single_course_access($user->ID);
        
        // Mark as checked for today
        update_user_meta($user->ID, 'school_access_checked_' . date('Y-m-d'), time());
    }
    
    /**
     * Enforce single course access for a user
     */
    public static function enforce_single_course_access($user_id) {
        // Get the user's assigned course from promo code data
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        
        if (!is_array($promo_access) || empty($promo_access['course_id'])) {
            // No promo access found, remove all course access
            self::remove_all_course_access($user_id);
            return;
        }
        
        $allowed_course_id = intval($promo_access['course_id']);
        
        // Check if access has expired
        if (!empty($promo_access['expires']) && strtotime($promo_access['expires']) < time()) {
            // Access expired, remove all access
            self::remove_all_course_access($user_id);
            update_user_meta($user_id, 'school_student_status', 'expired');
            return;
        }
        
        // Get all courses
        $all_courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        $access_changes = 0;
        
        foreach ($all_courses as $course_id) {
            if ($course_id == $allowed_course_id) {
                // Ensure access to allowed course
                if (function_exists('ld_update_course_access')) {
                    ld_update_course_access($user_id, $course_id, /* remove */ false);
                }
            } else {
                // Remove access to all other courses
                if (function_exists('ld_update_course_access')) {
                    ld_update_course_access($user_id, $course_id, /* remove */ true);
                    $access_changes++;
                }
            }
        }
        
        if ($access_changes > 0) {
            error_log("School Manager Lite: Enforced single course access for user {$user_id}. Removed access to {$access_changes} courses, kept access to course {$allowed_course_id}");
        }
    }
    
    /**
     * Remove all course access for a user
     */
    public static function remove_all_course_access($user_id) {
        $all_courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        foreach ($all_courses as $course_id) {
            if (function_exists('ld_update_course_access')) {
                ld_update_course_access($user_id, $course_id, /* remove */ true);
            }
        }
        
        error_log("School Manager Lite: Removed all course access for user {$user_id}");
    }
    
    /**
     * Filter LearnDash course access - ULTRA STRICT MODE
     */
    public static function filter_course_access($access_from, $course_id) {
        if (!is_user_logged_in()) {
            return $access_from;
        }
        
        $user = wp_get_current_user();
        
        // Only apply to student_private role
        if (!in_array('student_private', $user->roles)) {
            return $access_from;
        }
        
        // Get the user's assigned course
        $promo_access = get_user_meta($user->ID, 'school_promo_course_access', true);
        
        if (!is_array($promo_access) || empty($promo_access['course_id'])) {
            // No promo access found - BLOCK ALL COURSES
            error_log('School Manager Lite: BLOCKING course ' . $course_id . ' for user ' . $user->ID . ' - NO PROMO ACCESS FOUND');
            return 0;
        }
        
        $allowed_course_id = intval($promo_access['course_id']);
        
        // Check if this is the allowed course
        if ($course_id != $allowed_course_id) {
            // Not the allowed course - BLOCK ACCESS
            error_log('School Manager Lite: BLOCKING course ' . $course_id . ' for user ' . $user->ID . ' - ONLY ALLOWED: ' . $allowed_course_id);
            return 0;
        }
        
        // Check if access has expired
        if (!empty($promo_access['expires']) && strtotime($promo_access['expires']) < time()) {
            // Access expired - BLOCK ACCESS
            error_log('School Manager Lite: BLOCKING course ' . $course_id . ' for user ' . $user->ID . ' - ACCESS EXPIRED');
            return 0;
        }
        
        // Allow access to the assigned course ONLY
        error_log('School Manager Lite: ALLOWING course ' . $course_id . ' for user ' . $user->ID . ' - AUTHORIZED ACCESS');
        return $access_from;
    }
    
    /**
     * Additional filter for user course access
     */
    public static function filter_user_course_access($has_access, $user_id, $course_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user || !in_array('student_private', $user->roles)) {
            return $has_access;
        }
        
        $allowed_course = self::get_user_allowed_course($user_id);
        
        if ($allowed_course === false || $allowed_course != $course_id) {
            error_log('School Manager Lite: BLOCKING user course access - User: ' . $user_id . ', Course: ' . $course_id . ', Allowed: ' . $allowed_course);
            return false;
        }
        
        return $has_access;
    }
    
    /**
     * General course access filter
     */
    public static function filter_general_course_access($has_access, $course_id) {
        if (!is_user_logged_in()) {
            return $has_access;
        }
        
        $user = wp_get_current_user();
        
        if (!in_array('student_private', $user->roles)) {
            return $has_access;
        }
        
        $allowed_course = self::get_user_allowed_course($user->ID);
        
        if ($allowed_course === false || $allowed_course != $course_id) {
            error_log('School Manager Lite: BLOCKING general course access - User: ' . $user->ID . ', Course: ' . $course_id . ', Allowed: ' . $allowed_course);
            return false;
        }
        
        return $has_access;
    }
    
    /**
     * Block unauthorized course access attempts
     */
    public static function block_unauthorized_course_access($course_id, $user_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user || !in_array('student_private', $user->roles)) {
            return;
        }
        
        $allowed_course = self::get_user_allowed_course($user_id);
        
        if ($allowed_course === false || $allowed_course != $course_id) {
            error_log('School Manager Lite: REDIRECTING unauthorized course access - User: ' . $user_id . ', Course: ' . $course_id . ', Allowed: ' . $allowed_course);
            wp_redirect(home_url('/my-courses/'));
            exit;
        }
    }
    
    /**
     * Filter course content to block unauthorized access
     */
    public static function filter_course_content($content) {
        if (!is_user_logged_in()) {
            return $content;
        }
        
        global $post;
        
        // Only apply to LearnDash courses
        if (!$post || $post->post_type !== 'sfwd-courses') {
            return $content;
        }
        
        $user = wp_get_current_user();
        $course_id = $post->ID;
        
        // ACTIVE BLOCKING: Block courses 3267 and 423 for ALL users except admins
        $blocked_courses = array(3267, 423);
        if (in_array(intval($course_id), $blocked_courses)) {
            // Allow admins to access blocked courses
            if (in_array('administrator', $user->roles) || in_array('super_admin', $user->roles)) {
                error_log('School Manager Lite: ALLOWING admin access to blocked course ' . $course_id);
                return $content;
            }
            
            // Block everyone else from accessing these courses
            error_log('School Manager Lite: ACTIVELY BLOCKING content for course ' . $course_id . ' - COURSE IS RESTRICTED');
            return '<div class="learndash-course-access-denied"><h3>גישה נדחתה</h3><p>קורס זה אינו זמין כרגע. יש לך גישה רק לקורס 898.</p></div>';
        }
        
        // Only apply to student_private role for other courses
        if (!in_array('student_private', $user->roles)) {
            return $content;
        }
        
        $allowed_course = self::get_user_allowed_course($user->ID);
        
        if ($allowed_course === false || $allowed_course != $course_id) {
            error_log('School Manager Lite: BLOCKING course content - User: ' . $user->ID . ', Course: ' . $course_id . ', Allowed: ' . $allowed_course);
            return '<div class="learndash-course-access-denied"><h3>גישה נדחתה</h3><p>אין לך הרשאה לגשת לקורס זה. יש לך גישה רק לקורס שלך.</p></div>';
        }
        
        return $content;
    }
    
    /**
     * Debug current user to understand what's happening
     */
    public static function debug_current_user() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user = wp_get_current_user();
        
        // Only debug once per session to avoid spam
        static $debugged = false;
        if ($debugged) {
            return;
        }
        $debugged = true;
        
        error_log('=== SCHOOL ACCESS CONTROL DEBUG ===');
        error_log('Current User ID: ' . $user->ID);
        error_log('Current User Login: ' . $user->user_login);
        error_log('Current User Roles: ' . print_r($user->roles, true));
        
        // Check promo access meta
        $promo_access = get_user_meta($user->ID, 'school_promo_course_access', true);
        error_log('Promo Access Meta: ' . print_r($promo_access, true));
        
        // Check if user has student_private role
        $has_student_role = in_array('student_private', $user->roles);
        error_log('Has student_private role: ' . ($has_student_role ? 'YES' : 'NO'));
        
        // Check all user meta related to school
        $all_meta = get_user_meta($user->ID);
        $school_meta = array();
        foreach ($all_meta as $key => $value) {
            if (strpos($key, 'school_') !== false || strpos($key, 'course_') !== false || strpos($key, 'learndash_') !== false) {
                $school_meta[$key] = $value;
            }
        }
        error_log('School-related meta: ' . print_r($school_meta, true));
        
        // Check LearnDash course access
        if (function_exists('learndash_user_get_enrolled_courses')) {
            $enrolled_courses = learndash_user_get_enrolled_courses($user->ID);
            error_log('LearnDash Enrolled Courses: ' . print_r($enrolled_courses, true));
        }
        
        error_log('=== END DEBUG ===');
    }
    
    /**
     * Get user's allowed course ID
     */
    public static function get_user_allowed_course($user_id) {
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        
        error_log('Getting allowed course for user ' . $user_id . ': ' . print_r($promo_access, true));
        error_log('Promo access type: ' . gettype($promo_access));
        
        // Handle case where meta is returned as serialized string
        if (is_string($promo_access) && strpos($promo_access, 'a:') === 0) {
            $promo_access = unserialize($promo_access);
            error_log('Unserialized promo access: ' . print_r($promo_access, true));
        }
        
        if (!is_array($promo_access) || empty($promo_access['course_id'])) {
            error_log('No valid promo access found for user ' . $user_id . ' - not array or no course_id');
            return false;
        }
        
        // Check if access has expired
        if (!empty($promo_access['expires']) && strtotime($promo_access['expires']) < time()) {
            error_log('Access expired for user ' . $user_id . ' - expires: ' . $promo_access['expires']);
            return false;
        }
        
        $allowed_course = intval($promo_access['course_id']);
        error_log('User ' . $user_id . ' allowed course: ' . $allowed_course);
        return $allowed_course;
    }
    
    /**
     * Check if user has access to specific course
     */
    public static function user_has_course_access($user_id, $course_id) {
        $allowed_course = self::get_user_allowed_course($user_id);
        return $allowed_course && $allowed_course == $course_id;
    }
    
    /**
     * ACTIVELY BLOCK specific courses 3267 and 423 for ALL users except admins
     * This is the nuclear option - these courses are NEVER accessible
     */
    public static function actively_block_restricted_courses($has_access, $user_id, $post_id) {
        // Define the courses that are ACTIVELY BLOCKED
        $blocked_courses = array(3267, 423);
        
        // If this is not one of the blocked courses, let other logic handle it
        if (!in_array(intval($post_id), $blocked_courses)) {
            return $has_access;
        }
        
        // Check if user is admin - admins can access everything
        $user = get_user_by('ID', $user_id);
        if ($user && (in_array('administrator', $user->roles) || in_array('super_admin', $user->roles))) {
            error_log('School Manager Lite: ALLOWING admin user ' . $user_id . ' access to blocked course ' . $post_id);
            return $has_access;
        }
        
        // BLOCK ACCESS for everyone else
        error_log('School Manager Lite: ACTIVELY BLOCKING course ' . $post_id . ' for user ' . $user_id . ' - COURSE IS RESTRICTED');
        return false;
    }
}

// Initialize the access control
School_Login_Access_Control::init();
