<?php
/**
 * Fix for Teacher Class Wizard
 * 
 * This file contains fixes for the Teacher Class Wizard to implement
 * persistent storage for promo codes and class/teacher associations.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to extend Teacher_Class_Wizard with fixes
 */
class Teacher_Class_Wizard_Fix {
    /**
     * Constructor
     */
    public function __construct() {
        // Debug log constructor call
        error_log('TEACHER_WIZARD_FIX: Constructor called');
        
        // Hook to apply fixes after the main class is loaded
        add_action('init', array($this, 'apply_fixes'), 20);
        
        // Add admin notice for verification
        add_action('admin_notices', array($this, 'admin_notice'));
    }
    
    /**
     * Add admin notice to verify the fix is loaded
     */
    public function admin_notice() {
        // Use proper WordPress functions for admin notices to avoid deprecation warnings
        if (function_exists('add_settings_error')) {
            add_settings_error(
                'teacher_class_wizard_fix',
                'teacher_class_wizard_fix_loaded',
                'Teacher Class Wizard Fix loaded successfully!',
                'info'
            );
        }
    }
    
    /**
     * Apply fixes to the Teacher Class Wizard class
     */
    public function apply_fixes() {
        // Debug log
        error_log('TEACHER_WIZARD_FIX: apply_fixes called');
        
        // Only proceed if the class exists
        if (!class_exists('Teacher_Class_Wizard')) {
            error_log('TEACHER_WIZARD_FIX: Teacher_Class_Wizard class does not exist');
            return;
        }
        
        error_log('TEACHER_WIZARD_FIX: Teacher_Class_Wizard class exists');
        
        // Get the class instance
        $wizard = Teacher_Class_Wizard::get_instance();
        
        if (!is_object($wizard)) {
            error_log('TEACHER_WIZARD_FIX: Failed to get wizard instance');
            return;
        }
        
        error_log('TEACHER_WIZARD_FIX: Got wizard instance, adding hooks');
        
        // Add the save_promo_code method via closure
        add_filter('teacher_class_wizard_generate_promo_codes', array($this, 'save_promo_codes'), 10, 4);
        
        // Add teacher as group leader when selecting class
        add_action('teacher_class_wizard_after_class_selection', array($this, 'assign_teacher_to_class'), 10, 2);
        
        error_log('TEACHER_WIZARD_FIX: Hooks added successfully');
    }
    
    /**
     * Save promo codes to the database as CPT entries
     * 
     * @param array $promo_codes Generated promo codes
     * @param int $quantity Number of codes generated
     * @param string $prefix Prefix for the codes
     * @param array $form_data Form data from the wizard
     * @return array The promo codes
     */
    public function save_promo_codes($promo_codes, $quantity, $prefix, $form_data) {
        // Debug log that the filter was called
        error_log('TEACHER_WIZARD_FIX: save_promo_codes filter called with ' . count($promo_codes) . ' codes');
        error_log('TEACHER_WIZARD_FIX: Form data: ' . print_r($form_data, true));
        
        // Check if we have valid data
        if (empty($promo_codes) || !is_array($promo_codes)) {
            error_log('TEACHER_WIZARD_FIX: No valid promo codes to save');
            return $promo_codes;
        }
        
        // Get class and teacher IDs
        $class_id = !empty($form_data['class_id']) ? intval($form_data['class_id']) : 0;
        $teacher_id = !empty($form_data['teacher_id']) ? intval($form_data['teacher_id']) : 0;
        $expiry_date = !empty($form_data['expiry_date']) ? sanitize_text_field($form_data['expiry_date']) : '';
        
        error_log('TEACHER_WIZARD_FIX: Using class_id: ' . $class_id . ', teacher_id: ' . $teacher_id);
        
        // Log the process
        error_log(sprintf(
            'Saving %d promo codes for class ID: %d, teacher ID: %d',
            count($promo_codes),
            $class_id,
            $teacher_id
        ));
        
        // Store each promo code as a CPT
        foreach ($promo_codes as $index => $code) {
            $this->save_promo_code($code, $class_id, $teacher_id, 0, $expiry_date);
        }
        
        return $promo_codes;
    }
    
    /**
     * Save a single promo code as a CPT entry
     * 
     * @param string $code The promo code
     * @param int $group_id LearnDash group/class ID
     * @param int $teacher_id Teacher user ID
     * @param int $student_id Student user ID (if already assigned)
     * @param string $expiry_date Expiry date for the code (Y-m-d format)
     * @return int|WP_Error The post ID on success, WP_Error on failure
     */
    public function save_promo_code($code, $group_id, $teacher_id, $student_id = 0, $expiry_date = '') {
        // Check if code already exists
        $existing = get_page_by_title($code, OBJECT, 'ld_promo_code');
        if ($existing) {
            // Update existing code
            $post_id = $existing->ID;
            
            // Update metadata
            update_post_meta($post_id, '_ld_promo_code_group_id', $group_id);
            update_post_meta($post_id, '_ld_promo_code_teacher_id', $teacher_id);
            
            if ($student_id > 0) {
                update_post_meta($post_id, '_ld_promo_code_student_id', $student_id);
            }
            
            if (!empty($expiry_date)) {
                update_post_meta($post_id, '_ld_promo_code_expiry_date', $expiry_date);
            }
            
            return $post_id;
        }
        
        // Create new promo code post
        $post_data = array(
            'post_title'    => $code,
            'post_status'   => 'publish',
            'post_type'     => 'ld_promo_code',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            error_log('Error creating promo code: ' . $post_id->get_error_message());
            return $post_id;
        }
        
        // Add metadata
        update_post_meta($post_id, '_ld_promo_code_code', $code);
        update_post_meta($post_id, '_ld_promo_code_group_id', $group_id);
        update_post_meta($post_id, '_ld_promo_code_teacher_id', $teacher_id);
        
        if ($student_id > 0) {
            update_post_meta($post_id, '_ld_promo_code_student_id', $student_id);
        }
        
        if (!empty($expiry_date)) {
            update_post_meta($post_id, '_ld_promo_code_expiry_date', $expiry_date);
        }
        
        // Log the creation
        error_log(sprintf(
            'Created promo code post: %s (ID: %d) for class ID: %d, teacher ID: %d',
            $code,
            $post_id,
            $group_id,
            $teacher_id
        ));
        
        return $post_id;
    }
    
    /**
     * Assign teacher to class as group leader
     * 
     * @param int $class_id LearnDash group/class ID
     * @param int $teacher_id Teacher user ID
     */
    public function assign_teacher_to_class($class_id, $teacher_id) {
        if (empty($class_id) || empty($teacher_id)) {
            error_log("TEACHER_WIZARD_FIX: assign_teacher_to_class called with empty class_id or teacher_id");
            return;
        }
        
        error_log("TEACHER_WIZARD_FIX: Starting assign_teacher_to_class - Class ID: $class_id, Teacher ID: $teacher_id");
        
        // Try all available methods to ensure the teacher is set as a group leader
        $success = false;
        
        // Method 1: Use LearnDash function if available
        if (function_exists('learndash_set_groups_administrators')) {
            error_log("TEACHER_WIZARD_FIX: Using learndash_set_groups_administrators");
            $result = learndash_set_groups_administrators($class_id, array($teacher_id));
            
            // Log the result
            error_log(sprintf(
                'TEACHER_WIZARD_FIX: Assigned teacher ID: %d as group leader for class ID: %d - Result: %s',
                $teacher_id, $class_id, 
                ($result === true ? 'success' : (is_wp_error($result) ? $result->get_error_message() : 'unknown'))
            ));
            
            if ($result === true) {
                $success = true;
            }
        } else {
            error_log("TEACHER_WIZARD_FIX: learndash_set_groups_administrators not available");
        }
        
        // Method 2: Direct post meta update (always do this as a backup)
        $group_leaders = get_post_meta($class_id, '_ld_group_leaders', true);
        if (empty($group_leaders) || !is_array($group_leaders)) {
            $group_leaders = array();
        }
        
        error_log("TEACHER_WIZARD_FIX: Current group leaders: " . print_r($group_leaders, true));
        
        if (!in_array($teacher_id, $group_leaders)) {
            $group_leaders[] = $teacher_id;
            $update_result = update_post_meta($class_id, '_ld_group_leaders', $group_leaders);
            
            error_log(sprintf(
                'TEACHER_WIZARD_FIX: Direct update of group leaders meta - Teacher ID: %d, Class ID: %d, Update result: %s',
                $teacher_id, $class_id, ($update_result ? 'success' : 'failed')
            ));
            
            if ($update_result) {
                $success = true;
            }
        }
        
        // Method 3: Store custom meta (our backup method)
        update_post_meta($class_id, '_ld_promo_code_teacher_id', $teacher_id);
        
        // Method 4: Even more backup - store in user meta too
        add_user_meta($teacher_id, '_ld_teaching_group_ids', $class_id, false);
        
        // Verify after assignment
        $this->verify_teacher_class_assignment($class_id, $teacher_id);
        
        return $success;
    }
    
    /**
     * Verify teacher-class assignment worked correctly
     * 
     * @param int $class_id LearnDash group/class ID
     * @param int $teacher_id Teacher user ID
     */
    private function verify_teacher_class_assignment($class_id, $teacher_id) {
        error_log("TEACHER_WIZARD_FIX: Verifying teacher-class assignment for Class ID: $class_id, Teacher ID: $teacher_id");
        
        // Check method 1: LearnDash function
        if (function_exists('learndash_get_groups_administrator_ids')) {
            $group_leaders = learndash_get_groups_administrator_ids($class_id);
            error_log("TEACHER_WIZARD_FIX: learndash_get_groups_administrator_ids returned: " . print_r($group_leaders, true));
            
            if (is_array($group_leaders) && in_array($teacher_id, $group_leaders)) {
                error_log("TEACHER_WIZARD_FIX: Verification SUCCESS - Teacher $teacher_id is properly assigned to class $class_id via LearnDash function");
            } else {
                error_log("TEACHER_WIZARD_FIX: Verification FAILED - Teacher $teacher_id is NOT assigned to class $class_id via LearnDash function");
            }
        }
        
        // Check method 2: Direct meta
        $group_leaders_meta = get_post_meta($class_id, '_ld_group_leaders', true);
        error_log("TEACHER_WIZARD_FIX: _ld_group_leaders meta contains: " . print_r($group_leaders_meta, true));
        
        if (is_array($group_leaders_meta) && in_array($teacher_id, $group_leaders_meta)) {
            error_log("TEACHER_WIZARD_FIX: Verification SUCCESS - Teacher $teacher_id is properly assigned to class $class_id via post meta");
        } else {
            error_log("TEACHER_WIZARD_FIX: Verification FAILED - Teacher $teacher_id is NOT assigned to class $class_id via post meta");
        }
        
        // Check method 3: Our custom meta
        $custom_teacher_id = get_post_meta($class_id, '_ld_promo_code_teacher_id', true);
        error_log("TEACHER_WIZARD_FIX: _ld_promo_code_teacher_id meta contains: $custom_teacher_id");
        
        if ($custom_teacher_id == $teacher_id) {
            error_log("TEACHER_WIZARD_FIX: Verification SUCCESS - Teacher $teacher_id is properly assigned to class $class_id via custom meta");
        } else {
            error_log("TEACHER_WIZARD_FIX: Verification FAILED - Teacher $teacher_id is NOT assigned to class $class_id via custom meta");
        }
    }
}

// Initialize the fix
new Teacher_Class_Wizard_Fix();
