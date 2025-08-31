<?php
/**
 * Plugin Name: Teacher Group Assignment
 * Description: Enhanced system for assigning teachers to groups and classes
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Group_Assignment {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_assign_teacher_to_group', array($this, 'ajax_assign_teacher_to_group'));
        add_action('wp_ajax_get_available_groups', array($this, 'ajax_get_available_groups'));
        
        // Add scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add teacher assignment button
        add_action('admin_head', array($this, 'add_teacher_assignment_button'));
        
        // Handle teacher assignment form submission
        add_action('admin_init', array($this, 'handle_teacher_assignment'));
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'school-manager-teachers') === false) {
            return;
        }
        
        // Enqueue Thickbox for modal functionality
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
        
        // Enqueue our custom scripts and styles
        wp_enqueue_script(
            'teacher-group-assignment',
            plugins_url('teacher-group-assignment.js', __FILE__),
            array('jquery', 'thickbox'),
            '1.0',
            true
        );
        
        wp_localize_script('teacher-group-assignment', 'teacherAssignment', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teacher-group-assignment'),
            'i18n' => array(
                'assign' => __('הצג', 'school-manager-lite'),
                'cancel' => __('בטל', 'school-manager-lite'),
                'select_group' => __('בחר קבוצה', 'school-manager-lite'),
                'no_groups' => __('אין קבוצות זמינות', 'school-manager-lite'),
                'loading' => __('טוען...', 'school-manager-lite')
            )
        ));
    }
    
    public function add_teacher_assignment_button() {
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-teachers') {
            ?>
            <style>
                /* RTL alignment for assignment button */
                .assign-teacher-group {
                    float: right !important;
                }
                
                /* Assignment modal styles */
                .teacher-group-assignment-modal {
                    direction: rtl;
                    text-align: right;
                }
                
                .teacher-group-assignment-modal h3 {
                    text-align: right;
                }
                
                .teacher-group-assignment-modal label {
                    display: block;
                    margin-bottom: 10px;
                }
                
                .teacher-group-assignment-modal select {
                    width: 100%;
                    max-width: 300px;
                }
                
                .teacher-group-assignment-modal .submit {
                    margin-top: 20px;
                }
            </style>
            <?php
        }
    }
    
    public function ajax_get_available_groups() {
        check_ajax_referer('teacher-group-assignment', 'nonce');
        
        $groups = array();
        
        // Get LearnDash groups
        $ld_groups = get_posts(array(
            'post_type' => 'groups',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if (!empty($ld_groups)) {
            $groups['learn_dash'] = array(
                'label' => __('LearnDash Groups', 'school-manager-lite'),
                'options' => array()
            );
            
            foreach ($ld_groups as $group) {
                $groups['learn_dash']['options'][] = array(
                    'value' => 'ld_' . $group->ID,
                    'text' => $group->post_title
                );
            }
        }
        
        // Get School Manager classes
        $classes = get_terms(array(
            'taxonomy' => 'school_classes',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (!empty($classes)) {
            $groups['school_manager'] = array(
                'label' => __('School Manager Classes', 'school-manager-lite'),
                'options' => array()
            );
            
            foreach ($classes as $class) {
                $groups['school_manager']['options'][] = array(
                    'value' => 'sm_' . $class->term_id,
                    'text' => $class->name
                );
            }
        }
        
        wp_send_json_success($groups);
    }
    
    public function ajax_assign_teacher_to_group() {
        check_ajax_referer('teacher-group-assignment', 'nonce');
        
        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        $group_id = isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : '';
        
        if (!$teacher_id || !$group_id) {
            wp_send_json_error(__('Missing required parameters', 'school-manager-lite'));
        }
        
        $user = get_user_by('id', $teacher_id);
        if (!$user) {
            wp_send_json_error(__('User not found', 'school-manager-lite'));
        }
        
        // Handle LearnDash group assignment
        if (strpos($group_id, 'ld_') === 0) {
            $ld_group_id = intval(substr($group_id, 3));
            
            // Add user to LearnDash group
            if (function_exists('ld_update_group_leader')) {
                $result = ld_update_group_leader($user->ID, $ld_group_id, true);
                if ($result) {
                    wp_send_json_success(array(
                        'message' => sprintf(
                            __('Successfully assigned teacher to LearnDash group: %s', 'school-manager-lite'),
                            get_the_title($ld_group_id)
                        )
                    ));
                }
            }
        }
        
        // Handle School Manager class assignment
        if (strpos($group_id, 'sm_') === 0) {
            $sm_class_id = intval(substr($group_id, 3));
            
            // Update teacher's class assignment
            update_user_meta($user->ID, 'assigned_class', $sm_class_id);
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully assigned teacher to class: %s', 'school-manager-lite'),
                    get_term($sm_class_id, 'school_classes')->name
                )
            ));
        }
        
        wp_send_json_error(__('Failed to assign teacher to group', 'school-manager-lite'));
    }
    
    public function handle_teacher_assignment() {
        if (isset($_POST['action']) && $_POST['action'] === 'assign_teacher_to_group') {
            check_admin_referer('teacher-group-assignment');
            
            $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
            $group_id = isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : '';
            
            if ($teacher_id && $group_id) {
                // Process the assignment
                $this->ajax_assign_teacher_to_group();
            }
        }
    }
}

// Initialize
Teacher_Group_Assignment::instance();
