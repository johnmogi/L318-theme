<?php
/**
 * Teacher Visibility Fix
 * 
 * Ensures imported teachers with wdm_instructor role are visible in the admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Visibility_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into teacher queries to ensure all instructor roles are included
        add_filter('pre_get_users', array($this, 'include_all_instructor_roles'), 10, 1);
        
        // Add debug info to admin pages
        add_action('admin_notices', array($this, 'show_teacher_debug_info'));
        
        // Fix role assignments on user save
        add_action('profile_update', array($this, 'fix_teacher_role_on_save'), 10, 2);
        add_action('user_register', array($this, 'fix_teacher_role_on_save'), 10, 1);
        
        // Add AJAX endpoint to refresh teacher list
        add_action('wp_ajax_refresh_teacher_list', array($this, 'ajax_refresh_teacher_list'));
    }
    
    /**
     * Include all instructor roles in user queries
     */
    public function include_all_instructor_roles($query) {
        // Only modify queries on teacher admin pages
        if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'school-manager-teachers') {
            return $query;
        }
        
        // Get all instructor-related roles
        $instructor_roles = array(
            'wdm_instructor',
            'school_teacher',
            'group_leader',
            'instructor',
            'Instructor',
            'stm_lms_instructor',
            'administrator' // Include admins as they might also be teachers
        );
        
        // If role__in is already set, merge our roles
        if (isset($query->query_vars['role__in'])) {
            $existing_roles = (array) $query->query_vars['role__in'];
            $query->query_vars['role__in'] = array_unique(array_merge($existing_roles, $instructor_roles));
        } else {
            $query->query_vars['role__in'] = $instructor_roles;
        }
        
        // Remove any restrictive meta queries that might filter out teachers
        if (isset($query->query_vars['meta_query'])) {
            unset($query->query_vars['meta_query']);
        }
        
        // Ensure we get all users, not just a limited number
        if (!isset($query->query_vars['number']) || $query->query_vars['number'] < 50) {
            $query->query_vars['number'] = -1; // Get all users
        }
        
        error_log("Teacher Visibility Fix: Modified user query to include roles: " . implode(', ', $query->query_vars['role__in']));
        
        return $query;
    }
    
    /**
     * Show debug info about teachers in admin
     */
    public function show_teacher_debug_info() {
        // Only show on teacher admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'school-manager-teachers') {
            return;
        }
        
        // Get all users with instructor roles
        $instructor_roles = array('wdm_instructor', 'school_teacher', 'group_leader', 'instructor', 'Instructor', 'stm_lms_instructor');
        
        $total_instructors = 0;
        $role_counts = array();
        
        foreach ($instructor_roles as $role) {
            $users = get_users(array('role' => $role, 'fields' => 'ID'));
            $count = count($users);
            $role_counts[$role] = $count;
            $total_instructors += $count;
        }
        
        // Also check for users with instructor capabilities
        $cap_users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'wdm_instructor',
                    'compare' => 'LIKE'
                )
            ),
            'fields' => 'ID'
        ));
        
        echo '<div class="notice notice-info">';
        echo '<p><strong>Teacher Debug Info:</strong></p>';
        echo '<ul>';
        foreach ($role_counts as $role => $count) {
            if ($count > 0) {
                echo '<li>' . esc_html($role) . ': ' . $count . ' users</li>';
            }
        }
        echo '<li>Users with wdm_instructor capability: ' . count($cap_users) . '</li>';
        echo '<li>Total potential teachers: ' . $total_instructors . '</li>';
        echo '</ul>';
        echo '<p><button type="button" class="button" onclick="refreshTeacherList()">Refresh Teacher List</button></p>';
        echo '</div>';
        
        // Add JavaScript for refresh functionality
        echo '<script>
        function refreshTeacherList() {
            jQuery.post(ajaxurl, {
                action: "refresh_teacher_list",
                _wpnonce: "' . wp_create_nonce('refresh_teacher_list') . '"
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert("Error refreshing teacher list: " + response.data);
                }
            });
        }
        </script>';
    }
    
    /**
     * Fix teacher role assignments when user is saved
     */
    public function fix_teacher_role_on_save($user_id, $old_user_data = null) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        // Check if user has any instructor-related capabilities or roles
        $instructor_indicators = array(
            'school_teacher',
            'wdm_instructor',
            'group_leader',
            'instructor',
            'manage_school_students',
            'access_teacher_dashboard'
        );
        
        $is_instructor = false;
        foreach ($instructor_indicators as $indicator) {
            if ($user->has_cap($indicator) || in_array($indicator, $user->roles)) {
                $is_instructor = true;
                break;
            }
        }
        
        if ($is_instructor) {
            // Ensure user has wdm_instructor role
            if (!in_array('wdm_instructor', $user->roles)) {
                $user->add_role('wdm_instructor');
                error_log("Teacher Visibility Fix: Added wdm_instructor role to user {$user_id}");
            }
            
            // Add essential capabilities
            $essential_caps = array(
                'read' => true,
                'school_teacher' => true,
                'manage_school_students' => true,
                'access_teacher_dashboard' => true,
                'edit_groups' => true,
                'read_groups' => true
            );
            
            foreach ($essential_caps as $cap => $grant) {
                if (!$user->has_cap($cap)) {
                    $user->add_cap($cap, $grant);
                }
            }
        }
    }
    
    /**
     * AJAX handler to refresh teacher list
     */
    public function ajax_refresh_teacher_list() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'refresh_teacher_list')) {
            wp_send_json_error('Security check failed');
        }
        
        // Clear user cache
        wp_cache_delete('users', 'users');
        wp_cache_flush();
        
        // Get fresh teacher count
        $instructor_roles = array('wdm_instructor', 'school_teacher', 'group_leader', 'instructor', 'Instructor', 'stm_lms_instructor');
        $total_teachers = 0;
        
        foreach ($instructor_roles as $role) {
            $users = get_users(array('role' => $role, 'fields' => 'ID'));
            $total_teachers += count($users);
        }
        
        wp_send_json_success(array(
            'message' => 'Teacher list refreshed',
            'total_teachers' => $total_teachers
        ));
    }
}

// Initialize the fix
Teacher_Visibility_Fix::instance();
