<?php
/**
 * Teacher Count Fix
 * 
 * Fixes the discrepancy between teacher debug counts and actual teacher list display
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Count_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into the teacher manager's get_teachers method
        add_filter('school_manager_get_teachers_args', array($this, 'modify_teacher_query_args'), 10, 1);
        
        // Override the get_teachers method if needed
        add_action('init', array($this, 'maybe_override_teacher_manager'), 999);
        
        // Add detailed debug info to admin pages
        add_action('admin_notices', array($this, 'show_detailed_teacher_debug'), 15);
    }
    
    /**
     * Modify teacher query arguments to be more inclusive
     */
    public function modify_teacher_query_args($args) {
        // Ensure we're getting all users with any instructor-related role or capability
        if (isset($args['role__in'])) {
            $instructor_roles = array(
                'wdm_instructor',
                'school_teacher', 
                'group_leader',
                'instructor',
                'Instructor',
                'stm_lms_instructor',
                'administrator' // Sometimes admins are also teachers
            );
            
            $args['role__in'] = array_unique(array_merge($args['role__in'], $instructor_roles));
        }
        
        // Remove any restrictive meta queries that might filter out teachers
        if (isset($args['meta_query'])) {
            unset($args['meta_query']);
        }
        
        error_log("Teacher Count Fix: Modified query args: " . print_r($args, true));
        
        return $args;
    }
    
    /**
     * Override teacher manager if needed
     */
    public function maybe_override_teacher_manager() {
        // Only override on teacher admin pages
        if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'school-manager-teachers') {
            return;
        }
        
        // Check if School_Manager_Lite_Teacher_Manager exists
        if (!class_exists('School_Manager_Lite_Teacher_Manager')) {
            return;
        }
        
        // Add our custom get_teachers method
        add_filter('school_manager_teacher_manager_get_teachers', array($this, 'custom_get_teachers'), 10, 2);
    }
    
    /**
     * Custom get_teachers method that's more inclusive
     */
    public function custom_get_teachers($teachers, $args) {
        error_log("Teacher Count Fix: Custom get_teachers called with args: " . print_r($args, true));
        
        $defaults = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => -1,
            'paged' => 1,
            'search' => '',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'count_total' => true,
            'fields' => 'all_with_meta',
        );

        $args = wp_parse_args($args, $defaults);
        
        // Get all instructor roles
        $instructor_roles = array(
            'wdm_instructor',
            'school_teacher',
            'group_leader', 
            'instructor',
            'Instructor',
            'stm_lms_instructor'
        );
        
        // First, get users by roles
        $role_teachers = array();
        foreach ($instructor_roles as $role) {
            $role_args = $args;
            $role_args['role'] = $role;
            unset($role_args['role__in']);
            
            $role_users = get_users($role_args);
            foreach ($role_users as $user) {
                $role_teachers[$user->ID] = $user;
            }
        }
        
        // Then get users with instructor capabilities
        $cap_args = $args;
        unset($cap_args['role']);
        unset($cap_args['role__in']);
        
        $instructor_caps = array('school_teacher', 'manage_school_students', 'access_teacher_dashboard', 'group_leader');
        
        foreach ($instructor_caps as $cap) {
            $cap_args['meta_query'] = array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => $cap,
                    'compare' => 'LIKE'
                )
            );
            
            $cap_users = get_users($cap_args);
            foreach ($cap_users as $user) {
                $role_teachers[$user->ID] = $user;
            }
        }
        
        // Convert to array and apply sorting/pagination
        $all_teachers = array_values($role_teachers);
        
        // Apply search if provided
        if (!empty($args['search'])) {
            $search_term = trim($args['search'], '*');
            $filtered_teachers = array();
            
            foreach ($all_teachers as $teacher) {
                if (stripos($teacher->display_name, $search_term) !== false ||
                    stripos($teacher->user_login, $search_term) !== false ||
                    stripos($teacher->user_email, $search_term) !== false) {
                    $filtered_teachers[] = $teacher;
                }
            }
            
            $all_teachers = $filtered_teachers;
        }
        
        // Apply sorting
        if ($args['orderby'] === 'display_name') {
            usort($all_teachers, function($a, $b) use ($args) {
                $result = strcasecmp($a->display_name, $b->display_name);
                return ($args['order'] === 'DESC') ? -$result : $result;
            });
        }
        
        // Apply pagination
        if ($args['number'] > 0) {
            $offset = isset($args['offset']) ? $args['offset'] : 0;
            $all_teachers = array_slice($all_teachers, $offset, $args['number']);
        }
        
        error_log("Teacher Count Fix: Returning " . count($all_teachers) . " teachers");
        
        return $all_teachers;
    }
    
    /**
     * Show detailed debug information
     */
    public function show_detailed_teacher_debug() {
        // Only show on teacher admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'school-manager-teachers') {
            return;
        }
        
        // Get the actual teachers being displayed
        if (class_exists('School_Manager_Lite_Teacher_Manager')) {
            $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
            $displayed_teachers = $teacher_manager->get_teachers(array('number' => -1));
            
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Teacher Count Analysis:</strong></p>';
            echo '<ul>';
            echo '<li>Teachers actually displayed in list: ' . count($displayed_teachers) . '</li>';
            echo '<li>Expected from debug info: 31 total potential teachers</li>';
            echo '<li>Discrepancy: ' . (31 - count($displayed_teachers)) . ' teachers missing</li>';
            echo '</ul>';
            
            // Show which teachers are being displayed
            if (!empty($displayed_teachers)) {
                echo '<p><strong>Currently displayed teachers:</strong></p>';
                echo '<ul style="max-height: 200px; overflow-y: auto;">';
                foreach (array_slice($displayed_teachers, 0, 10) as $teacher) {
                    echo '<li>' . $teacher->display_name . ' (' . $teacher->user_login . ') - Roles: ' . implode(', ', $teacher->roles) . '</li>';
                }
                if (count($displayed_teachers) > 10) {
                    echo '<li><em>... and ' . (count($displayed_teachers) - 10) . ' more</em></li>';
                }
                echo '</ul>';
            }
            
            echo '<p><button type="button" class="button button-primary" onclick="location.reload()">Refresh with Fix</button></p>';
            echo '</div>';
        }
    }
}

// Initialize the fix
Teacher_Count_Fix::instance();

// Also try to directly override the teacher manager's get_teachers method
if (class_exists('School_Manager_Lite_Teacher_Manager')) {
    add_action('init', function() {
        // Hook into the teacher list table preparation
        add_action('load-school-manager_page_school-manager-teachers', function() {
            // Override the get_teachers method temporarily
            add_filter('pre_get_users', function($query) {
                // Only modify on teacher admin pages
                if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'school-manager-teachers') {
                    return $query;
                }
                
                // Make sure we include all instructor roles
                $instructor_roles = array(
                    'wdm_instructor',
                    'school_teacher',
                    'group_leader',
                    'instructor', 
                    'Instructor',
                    'stm_lms_instructor'
                );
                
                if (!isset($query->query_vars['role__in'])) {
                    $query->query_vars['role__in'] = $instructor_roles;
                } else {
                    $query->query_vars['role__in'] = array_unique(array_merge(
                        (array) $query->query_vars['role__in'], 
                        $instructor_roles
                    ));
                }
                
                // Remove any restrictive meta queries
                unset($query->query_vars['meta_query']);
                
                error_log("Teacher Count Fix: Modified pre_get_users query for teachers page");
                
                return $query;
            }, 5);
        });
    }, 999);
}
