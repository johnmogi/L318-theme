<?php
/**
 * Course Access Control
 * 
 * Handles course access based on user purchases and groups
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Lilac_Course_Access {
    
    private static $instance = null;
    
    // Course access duration (in days)
    private $access_duration = 365; // 1 year default
    
    // Get product to course mappings from options
    private function get_product_mappings() {
        $mappings = get_option('lilac_course_access_mappings', array());
        $result = array();
        
        foreach ($mappings as $mapping) {
            if (!empty($mapping['product_id']) && !empty($mapping['course_id']) && !empty($mapping['role'])) {
                $result[$mapping['product_id']] = array(
                    'course_id' => $mapping['course_id'],
                    'role' => $mapping['role']
                );
            }
        }
        
        return $result;
    }
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into WooCommerce order completion
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'), 10, 1);
        
        // Check course access
        add_filter('learndash_user_can_access_post', array($this, 'check_course_access'), 10, 3);
        
        // Add user to group based on purchase
        add_action('learndash_update_course_access', array($this, 'add_user_to_group'), 10, 3);
    }
    
    /**
     * Handle order completion
     */
    public function handle_order_completion($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        if (!$user_id) return;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            // Check if this product is in our mappings
            $mappings = $this->get_product_mappings();
            if (isset($mappings[$product_id])) {
                $mapping = $mappings[$product_id];
                $this->grant_course_access($user_id, $product_id, $mapping['role'], $mapping['course_id']);
            }
        }
    }
    
    /**
     * Grant course access based on product and role
     */
    private function grant_course_access($user_id, $product_id, $role, $course_id) {
        $access_time = time();
        $expire_time = strtotime("+{$this->access_duration} days", $access_time);
        
        // Get existing access data
        $access_data = get_user_meta($user_id, 'lilac_course_access', true) ?: array();
        
        // Add new course access
        $access_data[] = array(
            'product_id' => $product_id,
            'course_id' => $course_id,
            'role' => $role,
            'access_granted' => $access_time,
            'access_expires' => $expire_time
        );
        
        // Save updated access data
        update_user_meta($user_id, 'lilac_course_access', $access_data);
        
        // Grant access to the course
        ld_update_course_access($user_id, $course_id);
        
        // Update user role if needed
        $this->update_user_role($user_id, $role);
    }
    
    /**
     * Get courses associated with a specific role
     */
    private function get_courses_for_role($role) {
        // This should be replaced with actual course IDs from your system
        $role_courses = [
            'student_private' => [123],  // Replace with actual course IDs
            'student_school' => [456],  // Replace with actual course IDs
            'school_teacher' => [789]   // Replace with actual course IDs
        ];
        
        return isset($role_courses[$role]) ? $role_courses[$role] : [];
    }
    
    /**
     * Update user role based on purchase
     */
    private function update_user_role($user_id, $new_role) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        // Remove all existing roles
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }
        
        // Add new role
        $user->add_role($new_role);
    }
    
    /**
     * Check if user has access to a course
     */
    public function check_course_access($has_access, $post_id, $user_id) {
        // If already has access, return true
        if ($has_access) return true;
        
        // Get user's access data
        $access_data = get_user_meta($user_id, 'lilac_course_access', true);
        
        // If no access data, return current access
        if (empty($access_data)) return $has_access;
        
        // Check if access has expired
        if (time() > $access_data['access_expires']) {
            // Access expired, remove access
            $this->remove_expired_access($user_id);
            return false;
        }
        
        // Check if this course is in user's accessible courses
        return in_array($post_id, $access_data['courses']);
    }
    
    /**
     * Add user to appropriate group based on course access
     */
    public function add_user_to_group($user_id, $course_id, $access_list) {
        $access_data = get_user_meta($user_id, 'lilac_course_access', true);
        if (empty($access_data)) return;
        
        $group_id = $this->get_group_for_role($access_data['role']);
        if ($group_id) {
            ld_update_group_access($user_id, $group_id);
        }
    }
    
    /**
     * Get group ID for a role
     */
    private function get_group_for_role($role) {
        // Map roles to group IDs
        $role_groups = [
            'student_private' => 1,  // Replace with actual group ID
            'student_school' => 2,  // Replace with actual group ID
            'school_teacher' => 3   // Replace with actual group ID
        ];
        
        return isset($role_groups[$role]) ? $role_groups[$role] : 0;
    }
    
    /**
     * Remove expired access
     */
    private function remove_expired_access($user_id) {
        $access_data = get_user_meta($user_id, 'lilac_course_access', true);
        if (empty($access_data)) return;
        
        // Remove course access
        foreach ($access_data['courses'] as $course_id) {
            ld_update_course_access($user_id, $course_id, true);
        }
        
        // Remove access data
        delete_user_meta($user_id, 'lilac_course_access');
    }
}

// Initialize the class
function lilac_course_access_init() {
    return Lilac_Course_Access::get_instance();
}
add_action('init', 'lilac_course_access_init');
