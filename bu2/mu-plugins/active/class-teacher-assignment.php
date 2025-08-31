<?php
/**
 * Plugin Name: Class Teacher Assignment
 * Description: Enhanced teacher assignment system for classes page
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Class_Teacher_Assignment {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_assign_teacher_to_class', array($this, 'ajax_assign_teacher_to_class'));
        add_action('wp_ajax_get_available_teachers', array($this, 'ajax_get_available_teachers'));
        
        // Add scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add teacher assignment button
        add_action('admin_head', array($this, 'add_teacher_assignment_button'));
        
        // Handle teacher assignment form submission
        add_action('admin_init', array($this, 'handle_teacher_assignment'));
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'school-manager-classes') === false) {
            return;
        }
        
        // Enqueue Thickbox for modal functionality
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        
        // Enqueue our custom scripts and styles
        wp_enqueue_script(
            'class-teacher-assignment',
            plugins_url('class-teacher-assignment.js', __FILE__),
            array('jquery', 'thickbox'),
            '1.0',
            true
        );
        
        wp_localize_script('class-teacher-assignment', 'classTeacherAssignment', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('class-teacher-assignment'),
            'i18n' => array(
                'assign' => __('הצג מורה', 'school-manager-lite'),
                'cancel' => __('בטל', 'school-manager-lite'),
                'select_teacher' => __('בחר מורה', 'school-manager-lite'),
                'no_teachers' => __('אין מורים זמינים', 'school-manager-lite'),
                'loading' => __('טוען...', 'school-manager-lite'),
                'success' => __('המורה הוחבר בהצלחה לכתה', 'school-manager-lite'),
                'error' => __('שגיאה בהוספת המורה לכתה', 'school-manager-lite')
            )
        ));
    }
    
    public function add_teacher_assignment_button() {
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-classes') {
            ?>
            <style>
                /* RTL alignment for assignment button */
                .assign-teacher-button {
                    float: right !important;
                }
                
                /* Assignment modal styles */
                .class-teacher-assignment-modal {
                    direction: rtl;
                    text-align: right;
                }
                
                .class-teacher-assignment-modal h3 {
                    text-align: right;
                }
                
                .class-teacher-assignment-modal label {
                    display: block;
                    margin-bottom: 10px;
                }
                
                .class-teacher-assignment-modal select {
                    width: 100%;
                    max-width: 300px;
                }
                
                .class-teacher-assignment-modal .submit {
                    margin-top: 20px;
                }
            </style>
            <?php
        }
    }
    
    public function ajax_get_available_teachers() {
        check_ajax_referer('class-teacher-assignment', 'nonce');
        
        // Get teacher manager instance
        $teacher_manager = School_Manager_Lite_Teacher_Manager::instance();
        
        // Get all teachers with their roles
        $teachers = $teacher_manager->get_teachers();
        
        $teacher_options = array();
        
        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                // Get user's roles
                $roles = get_user_meta($teacher->ID, 'wp_capabilities', true);
                
                // Determine the primary role
                $primary_role = '';
                if (isset($roles['wdm_instructor']) || isset($roles['school_teacher'])) {
                    $primary_role = __('מורה', 'school-manager-lite');
                } elseif (isset($roles['group_leader'])) {
                    $primary_role = __('מנהיג קבוצה', 'school-manager-lite');
                }
                
                $teacher_options[] = array(
                    'value' => $teacher->ID,
                    'text' => $teacher->display_name . ' (' . $primary_role . ')',
                    'email' => $teacher->user_email
                );
            }
        }
        
        wp_send_json_success($teacher_options);
    }
    
    public function ajax_assign_teacher_to_class() {
        check_ajax_referer('class-teacher-assignment', 'nonce');
        
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        
        if (!$class_id || !$teacher_id) {
            wp_send_json_error(__('Missing required parameters', 'school-manager-lite'));
        }
        
        // Get teacher user object
        $user = get_user_by('id', $teacher_id);
        if (!$user) {
            wp_send_json_error(__('User not found', 'school-manager-lite'));
        }
        
        // Update class teacher assignment
        update_term_meta($class_id, 'teacher_id', $teacher_id);
        
        // Add teacher capability if needed
        $user->add_cap('school_teacher', true);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('מורה %s הוחבר בהצלחה לכתה', 'school-manager-lite'),
                $user->display_name
            )
        ));
    }
    
    public function handle_teacher_assignment() {
        if (isset($_POST['action']) && $_POST['action'] === 'assign_teacher_to_class') {
            check_admin_referer('class-teacher-assignment');
            
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
            
            if ($class_id && $teacher_id) {
                // Process the assignment
                $this->ajax_assign_teacher_to_class();
            }
        }
    }
}

// Initialize
Class_Teacher_Assignment::instance();
