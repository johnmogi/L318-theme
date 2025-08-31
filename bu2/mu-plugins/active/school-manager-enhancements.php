<?php
/**
 * Plugin Name: School Manager Enhancements
 * Description: Consolidated enhancements for School Manager Lite
 * Version: 1.0
 * Author: School Manager Lite
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Enhancements {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Core functionality
        add_action('admin_head', array($this, 'dashboard_styles'));
        add_action('admin_menu', array($this, 'remove_unwanted_menus'), 999);
        add_action('admin_init', array($this, 'fix_teacher_roles'));
        
        // Teacher permissions
        add_filter('user_has_cap', array($this, 'grant_instructor_capabilities'), 10, 3);
        add_action('pre_get_posts', array($this, 'allow_instructors_see_all_content'), 999);
        
        error_log('School Manager Enhancements: Initialized successfully');
    }
    
    /**
     * Dashboard styles for better visibility
     */
    public function dashboard_styles() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'school-manager') === false) {
            return;
        }
        ?>
        <style type="text/css">           
          
            .welcome-panel-content,
            .welcome-panel-content h2,
            .welcome-panel-content h3,
            .welcome-panel-content p,
            .welcome-panel-content li,
            .welcome-panel-content .about-description {
                color: #000000 !important;
            }
            .welcome-panel .button.button-primary {
                background: #2271b1 !important;
                border-color: #2271b1 !important;
                color: #ffffff !important;
            }
        </style>
        <?php
    }
    
    /**
     * Remove unwanted menu items
     */
    public function remove_unwanted_menus() {
        remove_menu_page('school-classes');
        remove_menu_page('class-management');
        remove_submenu_page('school-manager', 'school-classes');
        remove_submenu_page('school-manager', 'class-management');
        
        // CSS fallback
        echo '<style>
        #adminmenu a[href*="school-classes"],
        #adminmenu a[href*="class-management"] {
            display: none !important;
        }
        </style>';
    }
    
    /**
     * Fix teacher roles automatically
     */
    public function fix_teacher_roles() {
        // Only run once per day
        $last_run = get_transient('teacher_role_fix_last_run');
        if ($last_run) {
            return;
        }
        
        set_transient('teacher_role_fix_last_run', time(), DAY_IN_SECONDS);
        
        // Convert school_teacher to wdm_instructor
        $school_teachers = get_users(array('role' => 'school_teacher'));
        foreach ($school_teachers as $user) {
            $user->remove_role('school_teacher');
            $user->add_role('wdm_instructor');
            $this->add_instructor_capabilities($user);
            error_log("School Manager Enhancements: Converted user {$user->ID} to wdm_instructor");
        }
    }
    
    /**
     * Add instructor capabilities
     */
    private function add_instructor_capabilities($user) {
        $capabilities = array(
            'read' => true,
            'school_teacher' => true,
            'manage_school_students' => true,
            'access_teacher_dashboard' => true,
            'edit_sfwd-quizzes' => true,
            'edit_others_sfwd-quizzes' => true,
            'publish_sfwd-quizzes' => true,
            'read_private_sfwd-quizzes' => true,
            'edit_groups' => true,
            'edit_others_groups' => true,
            'read_groups' => true,
            'group_leader' => true
        );
        
        foreach ($capabilities as $cap => $grant) {
            $user->add_cap($cap, $grant);
        }
    }
    
    /**
     * Grant capabilities to instructors
     */
    public function grant_instructor_capabilities($caps, $cap, $user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('wdm_instructor', $user->roles)) {
            return $caps;
        }
        
        // Grant quiz and group capabilities
        $instructor_caps = array(
            'edit_others_sfwd-quizzes',
            'read_private_sfwd-quizzes',
            'edit_others_groups',
            'read_private_groups',
            'school_teacher'
        );
        
        if (in_array($cap[0], $instructor_caps)) {
            $caps[$cap[0]] = true;
        }
        
        return $caps;
    }
    
    /**
     * Allow instructors to see all content
     */
    public function allow_instructors_see_all_content($query) {
        if (!$query->is_main_query() || !is_admin()) {
            return;
        }
        
        $post_type = $query->get('post_type');
        if (!in_array($post_type, array('sfwd-quiz', 'groups'))) {
            return;
        }
        
        if (current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            $query->set('author', '');
            error_log("School Manager Enhancements: Removed author restriction for {$post_type}");
        }
    }
}

// Initialize
School_Manager_Enhancements::instance();
