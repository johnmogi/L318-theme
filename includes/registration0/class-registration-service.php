<?php
/**
 * Registration Service
 * Orchestrates registration functionality using database manager and code classes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Registration_Service
 * 
 * Main service class for registration functionality
 */
class Registration_Service {
    /**
     * @var Registration_Service Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var Registration_DB_Manager Database manager instance
     */
    private $db_manager;

    /**
     * Get singleton instance
     * 
     * @return Registration_Service
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->db_manager = Registration_DB_Manager::get_instance();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Process registration code on user registration
        add_action('user_register', array($this, 'process_registration_code'));
        
        // Add validation for registration codes during registration
        add_action('register_form', array($this, 'add_registration_code_field'));
        add_filter('registration_errors', array($this, 'validate_registration_code_field'), 10, 3);
        
        // AJAX handlers for admin
        add_action('wp_ajax_generate_registration_codes', array($this, 'ajax_generate_codes'));
        add_action('wp_ajax_export_registration_codes', array($this, 'ajax_export_codes'));
    }
    
    /**
     * Generate a batch of registration codes
     * 
     * @param array $params Parameters for code generation
     * @return array Generated codes
     */
    public function generate_codes($params) {
        $defaults = array(
            'count' => 1,
            'role' => 'subscriber',
            'group_name' => '',
            'course_id' => null,
            'max_uses' => 1,
            'expiry_date' => null,
        );
        
        $params = wp_parse_args($params, $defaults);
        $count = max(1, min(100, intval($params['count'])));
        $codes = array();
        
        for ($i = 0; $i < $count; $i++) {
            $code = new Registration_Code();
            $code->set_role($params['role'])
                 ->set_group_name($params['group_name'])
                 ->set_course_id($params['course_id'])
                 ->set_max_uses($params['max_uses'])
                 ->set_expiry_date($params['expiry_date'])
                 ->set_created_by(get_current_user_id());
            
            if ($code->save()) {
                $codes[] = $code;
            }
        }
        
        return $codes;
    }
    
    /**
     * Find a registration code by its code string
     * 
     * @param string $code_string The code to find
     * @return Registration_Code|false Code object or false if not found
     */
    public function find_code($code_string) {
        $code_data = $this->db_manager->get_code_by_code($code_string);
        
        if (!$code_data) {
            return false;
        }
        
        return new Registration_Code($code_data);
    }
    
    /**
     * Get registration code by ID
     * 
     * @param int $id Code ID
     * @return Registration_Code|false Code object or false if not found
     */
    public function get_code($id) {
        $code = new Registration_Code();
        
        if ($code->load($id)) {
            return $code;
        }
        
        return false;
    }
    
    /**
     * Get all registration codes with optional filtering
     * 
     * @param array $args Query arguments
     * @return array Array of Registration_Code objects
     */
    public function get_codes($args = []) {
        $codes_data = $this->db_manager->get_codes($args);
        $codes = [];
        
        foreach ($codes_data as $code_data) {
            $codes[] = new Registration_Code($code_data);
        }
        
        return $codes;
    }
    
    /**
     * Count registration codes with optional filtering
     * 
     * @param array $args Query arguments
     * @return int Count of codes
     */
    public function count_codes($args = []) {
        return $this->db_manager->count_codes($args);
    }
    
    /**
     * Mark a registration code as used
     * 
     * @param string $code_string The code string to mark as used
     * @param int $user_id The user ID who used the code
     * @return bool Success status
     */
    public function mark_code_used($code_string, $user_id) {
        return $this->db_manager->mark_code_used($code_string, $user_id);
    }
    
    /**
     * Validate a registration code
     * 
     * @param string $code_string Code to validate
     * @return array Validation result
     */
    public function validate_code($code_string) {
        $code = $this->find_code($code_string);
        
        if (!$code) {
            return [
                'is_valid' => false, 
                'message' => __('Invalid registration code', 'registration-codes')
            ];
        }
        
        return $code->validate();
    }
    
    /**
     * Process registration code during user registration
     * 
     * @param int $user_id New user ID
     */
    public function process_registration_code($user_id) {
        // Skip if no registration code was provided
        if (!isset($_POST['registration_code']) || empty($_POST['registration_code'])) {
            return;
        }
        
        $code_string = sanitize_text_field($_POST['registration_code']);
        $code = $this->find_code($code_string);
        
        if (!$code) {
            return;
        }
        
        // Validate the code
        $validation = $code->validate();
        
        if (!$validation['valid']) {
            return;
        }
        
        // Mark the code as used
        $code->mark_as_used($user_id);
        
        // Set the user's role based on the code
        $user = new WP_User($user_id);
        $user->set_role($code->get_role());
        
        // If the code is associated with a course, enroll the user
        if ($code->get_course_id()) {
            $this->enroll_user_in_course($user_id, $code->get_course_id());
        }
        
        // Add user to the group
        if ($code->get_group_name()) {
            $this->add_user_to_group($user_id, $code->get_group_name());
        }
        
        // Store the code in user meta
        update_user_meta($user_id, 'registration_code', $code_string);
        
        // Fire action for other plugins
        do_action('registration_code_used', $user_id, $code);
    }
    
    /**
     * Add registration code field to registration form
     */
    public function add_registration_code_field() {
        ?>
        <p>
            <label for="registration_code">
                <?php _e('Registration Code', 'registration-codes'); ?><br/>
                <input type="text" name="registration_code" id="registration_code" class="input" value="<?php echo isset($_POST['registration_code']) ? esc_attr($_POST['registration_code']) : ''; ?>" size="25" />
            </label>
        </p>
        <?php
    }
    
    /**
     * Validate registration code during user registration
     * 
     * @param WP_Error $errors WP_Error object
     * @param string $sanitized_user_login User login
     * @param string $user_email User email
     * @return WP_Error Updated errors object
     */
    public function validate_registration_code_field($errors, $sanitized_user_login, $user_email) {
        // Check if registration code is required
        $require_code = apply_filters('registration_code_required', true);
        
        if (!isset($_POST['registration_code']) || empty($_POST['registration_code'])) {
            if ($require_code) {
                $errors->add('empty_registration_code', __('<strong>ERROR</strong>: Please enter a registration code.', 'registration-codes'));
            }
            return $errors;
        }
        
        $code_string = sanitize_text_field($_POST['registration_code']);
        $validation = $this->validate_code($code_string);
        
        if (!$validation['valid']) {
            $errors->add('invalid_registration_code', $validation['message']);
        }
        
        return $errors;
    }
    
    /**
     * Enroll user in a course
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     */
    private function enroll_user_in_course($user_id, $course_id) {
        // Implementation depends on your LMS plugin
        // This is a placeholder for your actual enrollment code
        
        // Example for LearnDash
        if (function_exists('ld_update_course_access')) {
            ld_update_course_access($user_id, $course_id);
        }
        
        // Example for Tutor LMS
        if (function_exists('tutor_utils')) {
            tutor_utils()->do_enroll($course_id, 0, $user_id);
        }
        
        // Example for LifterLMS
        if (function_exists('llms_enroll_student')) {
            llms_enroll_student($user_id, $course_id);
        }
        
        do_action('registration_code_enroll_user', $user_id, $course_id);
    }
    
    /**
     * Add user to a group
     * 
     * @param int $user_id User ID
     * @param string $group_name Group name
     */
    private function add_user_to_group($user_id, $group_name) {
        // Implementation depends on your group management plugin
        // This is a placeholder for your actual group management code
        
        // Example for BuddyPress groups
        if (function_exists('groups_join_group')) {
            $group_id = BP_Groups_Group::get_id_from_slug($group_name);
            if ($group_id) {
                groups_join_group($group_id, $user_id);
            }
        }
        
        // Example for WordPress User Groups plugin
        if (function_exists('wpug_add_user_to_group')) {
            wpug_add_user_to_group($user_id, $group_name);
        }
        
        do_action('registration_code_add_user_to_group', $user_id, $group_name);
    }
    
    /**
     * AJAX handler for generating registration codes
     */
    public function ajax_generate_codes() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'registration_codes_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'subscriber';
        $group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
        $max_uses = isset($_POST['max_uses']) ? intval($_POST['max_uses']) : 1;
        $expiry_days = isset($_POST['expiry_days']) ? intval($_POST['expiry_days']) : 0;
        
        // Calculate expiry date if provided
        $expiry_date = null;
        if ($expiry_days > 0) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        }
        
        $params = [
            'count' => $count,
            'role' => $role,
            'group_name' => $group_name,
            'course_id' => $course_id,
            'max_uses' => $max_uses,
            'expiry_date' => $expiry_date
        ];
        
        $codes = $this->generate_codes($params);
        $response = [];
        
        foreach ($codes as $code) {
            $response[] = $code->to_array();
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX handler for exporting registration codes
     */
    public function ajax_export_codes() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'registration_codes_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $is_used = isset($_POST['is_used']) ? intval($_POST['is_used']) : null;
        
        $args = [
            'group_name' => $group_name,
            'course_id' => $course_id,
            'is_used' => $is_used,
            'limit' => 1000 // Export up to 1000 codes
        ];
        
        $codes = $this->get_codes($args);
        $export_data = [];
        
        foreach ($codes as $code) {
            $export_data[] = $code->to_array();
        }
        
        wp_send_json_success($export_data);
    }
}
