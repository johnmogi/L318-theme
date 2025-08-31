<?php
/**
 * School Role Permission Fix
 * 
 * Ensures that student roles don't have automatic access to all courses
 * and fixes any LearnDash settings that might grant universal access
 *
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Role_Permission_Fix {
    
    /**
     * Initialize the role permission fixes
     */
    public static function init() {
        // Run on admin init to fix role permissions
        add_action('admin_init', array(__CLASS__, 'fix_student_role_permissions'));
        
        // Hook into user registration to ensure proper role assignment
        add_action('user_register', array(__CLASS__, 'ensure_proper_student_role'));
        
        // Add admin notice if issues are found
        add_action('admin_notices', array(__CLASS__, 'show_permission_notices'));
        
        // Check LearnDash settings
        add_action('admin_init', array(__CLASS__, 'check_learndash_settings'));
    }
    
    /**
     * Fix student role permissions to prevent automatic course access
     */
    public static function fix_student_role_permissions() {
        // Get the student_private role
        $role = get_role('student_private');
        
        if (!$role) {
            return;
        }
        
        // Remove any capabilities that might grant automatic course access
        $capabilities_to_remove = array(
            'read_course',
            'read_courses',
            'read_private_courses',
            'edit_courses',
            'publish_courses',
            'delete_courses',
            'manage_learndash',
            'learndash_admin',
            'enroll_users'
        );
        
        $removed_caps = array();
        foreach ($capabilities_to_remove as $cap) {
            if ($role->has_cap($cap)) {
                $role->remove_cap($cap);
                $removed_caps[] = $cap;
            }
        }
        
        // Ensure basic read capability exists
        if (!$role->has_cap('read')) {
            $role->add_cap('read');
        }
        
        // Log if we removed any problematic capabilities
        if (!empty($removed_caps)) {
            error_log('School Manager Lite: Removed problematic capabilities from student_private role: ' . implode(', ', $removed_caps));
            update_option('school_role_fix_applied', array(
                'timestamp' => current_time('mysql'),
                'removed_caps' => $removed_caps
            ));
        }
    }
    
    /**
     * Ensure proper student role assignment
     */
    public static function ensure_proper_student_role($user_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }
        
        // Check if this is a student created via promo code
        $student_id = get_user_meta($user_id, '_school_student_id', true);
        
        if (!empty($student_id)) {
            // This is a student, ensure proper role
            $user->set_role('student_private');
            
            // Remove any other roles that might grant course access
            $roles_to_remove = array('subscriber', 'contributor', 'author', 'editor', 'administrator');
            foreach ($roles_to_remove as $role) {
                if (in_array($role, $user->roles)) {
                    $user->remove_role($role);
                }
            }
            
            error_log("School Manager Lite: Ensured proper role for student user {$user->user_login} (ID: {$user_id})");
        }
    }
    
    /**
     * Check LearnDash settings for automatic enrollment
     */
    public static function check_learndash_settings() {
        if (!function_exists('learndash_get_setting')) {
            return;
        }
        
        // Check if there are any global course access settings
        $issues_found = array();
        
        // Get all courses and check their settings
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($courses as $course) {
            $course_settings = learndash_get_setting($course->ID);
            
            // Check if course has open enrollment
            if (!empty($course_settings['course_price_type']) && $course_settings['course_price_type'] === 'open') {
                $issues_found[] = "Course '{$course->post_title}' (ID: {$course->ID}) has open enrollment";
            }
            
            // Check if course is assigned to student role
            if (!empty($course_settings['course_access_list']) && is_array($course_settings['course_access_list'])) {
                $student_users = get_users(array('role' => 'student_private', 'fields' => 'ID'));
                foreach ($student_users as $student_id) {
                    if (in_array($student_id, $course_settings['course_access_list'])) {
                        // This might be legitimate, but log it
                        error_log("School Manager Lite: Student {$student_id} found in access list for course {$course->ID}");
                    }
                }
            }
        }
        
        if (!empty($issues_found)) {
            update_option('school_learndash_issues', array(
                'timestamp' => current_time('mysql'),
                'issues' => $issues_found
            ));
        }
    }
    
    /**
     * Show admin notices about permission issues
     */
    public static function show_permission_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show notice if role fixes were applied
        $role_fix = get_option('school_role_fix_applied');
        if (!empty($role_fix) && !empty($role_fix['removed_caps'])) {
            $message = sprintf(
                __('School Manager: Fixed student role permissions. Removed capabilities: %s. Applied on: %s', 'school-manager-lite'),
                implode(', ', $role_fix['removed_caps']),
                $role_fix['timestamp']
            );
            
            echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
            
            // Clear the notice after showing it once
            delete_option('school_role_fix_applied');
        }
        
        // Show LearnDash issues if found
        $ld_issues = get_option('school_learndash_issues');
        if (!empty($ld_issues) && !empty($ld_issues['issues'])) {
            $message = sprintf(
                __('School Manager: Found potential LearnDash course access issues: %s', 'school-manager-lite'),
                implode('; ', $ld_issues['issues'])
            );
            
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        }
    }
    
    /**
     * Get all students with course access (for debugging)
     */
    public static function get_students_with_course_access() {
        $students = get_users(array('role' => 'student_private'));
        $access_data = array();
        
        foreach ($students as $student) {
            $courses = learndash_user_get_enrolled_courses($student->ID);
            if (!empty($courses)) {
                $access_data[$student->ID] = array(
                    'user_login' => $student->user_login,
                    'display_name' => $student->display_name,
                    'courses' => $courses,
                    'promo_access' => get_user_meta($student->ID, 'school_promo_course_access', true)
                );
            }
        }
        
        return $access_data;
    }
    
    /**
     * Manual cleanup of all student course access (DANGEROUS - admin only)
     */
    public static function manual_cleanup_all_student_access() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $students = get_users(array('role' => 'student_private'));
        $cleaned = 0;
        $courses_removed = 0;
        
        foreach ($students as $student) {
            // Get all courses
            $all_courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'fields' => 'ids'
            ));
            
            // Remove access to all courses
            foreach ($all_courses as $course_id) {
                if (function_exists('ld_update_course_access')) {
                    ld_update_course_access($student->ID, $course_id, /* remove */ true);
                    $courses_removed++;
                }
            }
            
            // Also clean up LearnDash course progress meta
            delete_user_meta($student->ID, '_sfwd-course_progress');
            
            // Remove any course-specific meta
            foreach ($all_courses as $course_id) {
                delete_user_meta($student->ID, 'course_' . $course_id . '_access_from');
                delete_user_meta($student->ID, 'learndash_course_expired_' . $course_id);
                delete_user_meta($student->ID, 'school_course_expiration_' . $course_id);
            }
            
            $cleaned++;
        }
        
        wp_die("Cleaned course access for {$cleaned} students. Removed {$courses_removed} course access entries. They will need to use promo codes to regain access.");
    }
    
    /**
     * Fix specific user's course access (for testing)
     */
    public static function fix_user_course_access() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_login = isset($_GET['user_login']) ? sanitize_text_field($_GET['user_login']) : '';
        $target_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : 898;
        
        if (empty($user_login)) {
            wp_die('Please provide user_login parameter');
        }
        
        $user = get_user_by('login', $user_login);
        if (!$user) {
            wp_die('User not found: ' . $user_login);
        }
        
        // Get all courses
        $all_courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        $removed_count = 0;
        
        // Remove access to all courses
        foreach ($all_courses as $course_id) {
            if (function_exists('ld_update_course_access')) {
                ld_update_course_access($user->ID, $course_id, /* remove */ true);
                $removed_count++;
            }
        }
        
        // Clean up meta
        delete_user_meta($user->ID, '_sfwd-course_progress');
        
        // Grant access only to target course
        if (function_exists('ld_update_course_access') && $target_course) {
            ld_update_course_access($user->ID, $target_course, /* remove */ false);
        }
        
        wp_die("Fixed user {$user_login} (ID: {$user->ID}). Removed access to {$removed_count} courses. Granted access to course {$target_course}.");
    }
}

// Initialize the role permission fixes
School_Role_Permission_Fix::init();

// Add admin action for manual cleanup (DANGEROUS)
add_action('wp_ajax_school_manual_cleanup_access', array('School_Role_Permission_Fix', 'manual_cleanup_all_student_access'));

// Add admin action for fixing specific user
add_action('wp_ajax_school_fix_user_access', array('School_Role_Permission_Fix', 'fix_user_course_access'));
