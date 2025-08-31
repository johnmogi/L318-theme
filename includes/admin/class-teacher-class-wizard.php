<?php
/**
 * Teacher/Class Management Wizard
 * 
 * Handles the multi-step wizard for managing teachers, classes, and promo codes
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Teacher_Class_Wizard {
    /**
     * Current step
     * @var int
     */
    private $current_step = 1;
    
    /**
     * Total steps
     * @var int
     */
    private $total_steps = 3;
    
    /**
     * Form data
     * @var array
     */
    private $form_data = array();
    
    /**
     * Errors
     * @var array
     */
    private $errors = array();
    
    /**
     * Constructor
     */
    private static $instance = null;
    private $menu_added = false;
    private $page_hook = '';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        error_log('TEACHER_WIZARD: Constructor called');
        
        // Add menu using admin_menu hook with a late priority
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        
        // Add other hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'process_form'));
        
        // Debug admin bar menu
        // Removed to clean up top bar
        // add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        
        // Register AJAX handlers
        add_action('wp_ajax_check_username_exists', array($this, 'ajax_check_username_exists'));
        add_action('wp_ajax_nopriv_check_username_exists', array($this, 'ajax_check_username_exists'));
        add_action('wp_ajax_generate_promo_codes', array($this, 'generate_promo_codes_ajax'));
        add_action('wp_ajax_download_promo_codes_csv', array($this, 'download_promo_codes_csv_ajax'));
        
        // Add capability mapping
        add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);
        
        // AJAX handlers are already registered above via individual add_action calls
    }
    
    /**
     * Map custom capabilities
     */
    public function map_meta_cap($caps, $cap, $user_id, $args) {
        // Map manage_school_classes to edit_posts for teachers
        if ('manage_school_classes' === $cap) {
            $user = get_userdata($user_id);
            if (in_array('school_teacher', (array) $user->roles) || 
                in_array('teacher', (array) $user->roles) || 
                in_array('מורה / רכז', (array) $user->roles)) {
                $caps = array('edit_posts');
            }
        }
        return $caps;
    }
    
    /**
     * Check if user has access to the page
     */
    public function check_page_access() {
        $current_user = wp_get_current_user();
        $allowed_roles = array('administrator', 'editor', 'school_teacher', 'teacher', 'מורה / רכז');
        $has_access = false;
        
        // Check if user has any of the allowed roles
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $current_user->roles)) {
                $has_access = true;
                break;
            }
        }
        
        // Also check for custom capabilities
        if (!$has_access && user_can($current_user, 'manage_school_classes')) {
            $has_access = true;
        }
        
        if (!$has_access) {
            wp_die(
                __('You do not have sufficient permissions to access this page.', 'hello-theme-child'),
                __('Access Denied', 'hello-theme-child'),
                array('response' => 403)
            );
        }
    }
    
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id'    => 'teacher_wizard',
            'title' => 'Class Management',
            'href'  => admin_url('admin.php?page=class-management'),
            'meta'  => array('class' => 'teacher-wizard-toolbar')
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Prevent duplicate menu registration
        if ($this->menu_added) {
            error_log('TEACHER_WIZARD: Menu already added, skipping');
            return false;
        }
        
        error_log('TEACHER_WIZARD: add_admin_menu() called');
        
        // Debug: Log current user info
        $current_user = wp_get_current_user();
        error_log('TEACHER_WIZARD: Current User ID: ' . $current_user->ID);
        error_log('TEACHER_WIZARD: Current User Roles: ' . print_r($current_user->roles, true));
        
        // Check if user has the required capability
        $allowed_roles = array('administrator', 'editor', 'school_teacher', 'teacher', 'מורה / רכז');
        $has_access = false;
        
        // Check if user has any of the allowed roles
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $current_user->roles)) {
                $has_access = true;
                break;
            }
        }
        
        // Also check for custom capabilities
        if (!$has_access && user_can($current_user, 'manage_school_classes')) {
            $has_access = true;
        }
        
        if (!$has_access) {
            error_log('TEACHER_WIZARD: User does not have required role. User roles: ' . print_r($current_user->roles, true));
            error_log('TEACHER_WIZARD: Allowed roles: ' . print_r($allowed_roles, true));
            return false;
        }
        
        // Log current screen and menu structure
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        error_log('TEACHER_WIZARD: Current screen: ' . ($screen ? print_r($screen, true) : 'N/A'));
        
        global $menu, $submenu;
        error_log('TEACHER_WIZARD: Current menu structure: ' . print_r($menu, true));
        
        // Set capability based on user role
        $capability = 'read'; // Base capability that all users have
        if (current_user_can('manage_options') || current_user_can('manage_network')) {
            $capability = 'manage_options';
        } elseif (in_array('school_teacher', (array) $current_user->roles) || 
                 in_array('teacher', (array) $current_user->roles) || 
                 in_array('מורה / רכז', (array) $current_user->roles)) {
            // Use a custom capability that we'll map for teachers
            $capability = 'manage_school_classes';
        }
        error_log('TEACHER_WIZARD: Using capability: ' . $capability);
        
        try {
            // Add the main menu item
            // Add the menu with the correct slug
            $this->page_hook = add_menu_page(
                'Class Management',
                'Class Management',
                $capability,
                'teacher-class-wizard',  // Changed from 'class-management'
                array($this, 'render_wizard'),
                'dashicons-welcome-learn-more',
                26 // Position in menu
            );
            
            // Ensure the page is accessible
            add_action('load-' . $this->page_hook, array($this, 'check_page_access'));
            
            // Add our scripts and styles
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            
            // Add AJAX handler for teacher search
            add_action('wp_ajax_search_teachers', array($this, 'ajax_search_teachers'));
            
            error_log('Admin menu added with page hook: ' . $this->page_hook);
            
        } catch (Exception $e) {
            // Log the error
            error_log('Error adding admin menu: ' . $e->getMessage());
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>Error adding Class Management menu: ' . 
                     esc_html($e->getMessage()) . '</p></div>';
            });
            return false;
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Debug
        error_log('TEACHER_WIZARD: Enqueue scripts called for hook: ' . $hook);
        error_log('TEACHER_WIZARD: Page hook: ' . $this->page_hook);
        
        // Only load on our plugin page
        if (strpos($hook, 'class-management') === false) {
            error_log('TEACHER_WIZARD: Skipping enqueue - not our page. Current hook: ' . $hook);
            return;
        }
        
        error_log('TEACHER_WIZARD: Loading scripts and styles for wizard');
        
        // Debug: Log theme directory and file existence
        $css_file = get_stylesheet_directory() . '/assets/css/admin/teacher-class-wizard.css';
        error_log('TEACHER_WIZARD: CSS file path: ' . $css_file);
        error_log('TEACHER_WIZARD: CSS file exists: ' . (file_exists($css_file) ? 'Yes' : 'No'));
        
        // Debug: Print the CSS URL in the admin head
        add_action('admin_head', function() {
            echo '<!-- TEACHER_WIZARD_DEBUG -->\n';
            echo '<!-- CSS URL: ' . esc_url(get_stylesheet_directory_uri() . '/assets/css/admin/teacher-class-wizard.css') . ' -->\n';
            echo '<!-- Current Hook: ' . current_filter() . ' -->\n';
        });
        
        // Enqueue WordPress media scripts for file uploads
        wp_enqueue_media();
        
        // Enqueue jQuery UI for tabs and datepicker
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Enqueue Select2
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
        
        // Enqueue our custom scripts and styles from admin directory
        wp_enqueue_style('teacher-class-wizard', get_stylesheet_directory_uri() . '/assets/css/admin/teacher-class-wizard.css', array(), '1.0.0');
        wp_enqueue_script('teacher-class-wizard', get_stylesheet_directory_uri() . '/assets/js/admin/teacher-class-wizard.js', array('jquery', 'select2'), '1.0.0', true);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('teacher-class-wizard', 'teacherClassWizard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teacher_class_wizard_nonce'),
            'i18n' => array(
                'searching' => __('Searching...', 'hello-theme-child'),
                'noResults' => __('No teachers found', 'hello-theme-child'),
                'search' => __('Search teachers...', 'hello-theme-child'),
                'loadingMore' => __('Loading more results...', 'hello-theme-child'),
                'copied' => __('Copied!', 'hello-theme-child'),
                'teacherRequired' => __('Please select an existing teacher or create a new one', 'hello-theme-child'),
                'classRequired' => __('Please select an existing class or create a new one', 'hello-theme-child')
            )
        ));
        
        // Debug info in console
        wp_add_inline_script('teacher-class-wizard', '
            console.log("Teacher Class Wizard Script Loaded");
            console.log("AJAX URL:", teacherClassWizard.ajaxUrl);
            console.log("Nonce:", teacherClassWizard.nonce);
        ');
    }
    
    /**
     * Process form submission
     */
    public function process_form() {
        if (!isset($_POST['teacher_class_wizard_nonce']) || 
            !wp_verify_nonce($_POST['teacher_class_wizard_nonce'], 'teacher_class_wizard')) {
            return;
        }
        
        // Get current step
        $this->current_step = isset($_POST['step']) ? absint($_POST['step']) : 1;
        
        // Process form data based on current step
        switch ($this->current_step) {
            case 1:
                $this->process_teacher_step();
                break;
            case 2:
                $this->process_class_step();
                break;
            case 3:
                $this->process_promo_step();
                break;
        }
    }
    
    /**
     * Process teacher step
     */
    private function process_teacher_step() {
        // Check if we're creating a new teacher or using an existing one
        $teacher_type = isset($_POST['teacher_type']) ? sanitize_text_field($_POST['teacher_type']) : 'existing';
        
        if ($teacher_type === 'new') {
            // Process new teacher creation
            $this->create_new_teacher();
        } else {
            // Process existing teacher selection
            $this->select_existing_teacher();
        }
        
        // If no errors, proceed to next step
        if (empty($this->errors)) {
            $this->current_step = 2;
            
            // Store teacher data in form data
            if ($teacher_type === 'new' && !empty($this->form_data['new_teacher_id'])) {
                $this->form_data['teacher_id'] = $this->form_data['new_teacher_id'];
            }
        }
    }
    
    /**
     * Create a new teacher account
     */
    private function create_new_teacher() {
        // Validate required fields
        $required_fields = array(
            'new_teacher_first_name' => __('First Name', 'hello-theme-child'),
            'new_teacher_last_name' => __('Last Name', 'hello-theme-child'),
            'new_teacher_phone' => __('Phone Number', 'hello-theme-child')
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $this->errors[] = sprintf(__('%s is required', 'hello-theme-child'), $label);
            }
        }
        
        // Validate phone number format
        if (!empty($_POST['new_teacher_phone']) && !preg_match('/^\d{9,15}$/', $_POST['new_teacher_phone'])) {
            $this->errors[] = __('Please enter a valid phone number (9-15 digits)', 'hello-theme-child');
        }
        
        // Check if username (phone) already exists
        if (!empty($_POST['new_teacher_phone']) && username_exists($_POST['new_teacher_phone'])) {
            $this->errors[] = __('A user with this phone number already exists', 'hello-theme-child');
        }
        
        // If we have errors, stop here
        if (!empty($this->errors)) {
            return;
        }
        
        // Sanitize input
        $first_name = sanitize_text_field($_POST['new_teacher_first_name']);
        $last_name = sanitize_text_field($_POST['new_teacher_last_name']);
        $phone = sanitize_text_field($_POST['new_teacher_phone']);
        $email = !empty($_POST['new_teacher_email']) ? sanitize_email($_POST['new_teacher_email']) : $phone . '@school.edu';
        
        // Generate a strong password
        $password = wp_generate_password(12, true, true);
        
        // Create user data array
        $user_data = array(
            'user_login'    => $phone,
            'user_email'    => $email,
            'user_pass'     => $password,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'display_name'  => $first_name . ' ' . $last_name,
            'role'          => 'school_teacher'
        );
        
        // Insert user
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            $this->errors[] = $user_id->get_error_message();
            return;
        }
        
        // Store additional user meta
        update_user_meta($user_id, 'phone_number', $phone);
        
        // Store the new teacher ID for later use
        $this->form_data['new_teacher_id'] = $user_id;
        $this->form_data['new_teacher_password'] = $password;
        
        // Send welcome email if requested
        if (isset($_POST['send_credentials']) && !empty($email)) {
            $this->send_teacher_credentials($user_id, $password, $email);
        }
        
        // Log the creation
        error_log(sprintf(
            'TEACHER_WIZARD: Created new teacher %s (ID: %d) with username %s',
            $first_name . ' ' . $last_name,
            $user_id,
            $phone
        ));
    }
    
    /**
     * Handle selection of existing teacher
     */
    private function select_existing_teacher() {
        if (empty($_POST['teacher_id'])) {
            $this->errors[] = __('Please select an existing teacher', 'hello-theme-child');
            return;
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        $teacher = get_user_by('id', $teacher_id);
        
        if (!$teacher || !in_array('school_teacher', (array) $teacher->roles)) {
            $this->errors[] = __('Invalid teacher selected', 'hello-theme-child');
            return;
        }
        
        $this->form_data['teacher_id'] = $teacher_id;
    }
    
    /**
     * Send welcome email with login credentials
     */
    private function send_teacher_credentials($user_id, $password, $email) {
        $user = get_userdata($user_id);
        $blog_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        $subject = sprintf(__('Your %s Teacher Account', 'hello-theme-child'), $blog_name);
        
        $message = sprintf(__('Hello %s,', 'hello-theme-child'), $user->first_name) . "\r\n\r\n";
        $message .= sprintf(__('A teacher account has been created for you on %s.', 'hello-theme-child'), $blog_name) . "\r\n\r\n";
        $message .= __('Your login details:', 'hello-theme-child') . "\r\n";
        $message .= __('Username: ', 'hello-theme-child') . $user->user_login . "\r\n";
        $message .= __('Password: ', 'hello-theme-child') . $password . "\r\n\r\n";
        $message .= __('You can log in here: ', 'hello-theme-child') . $login_url . "\r\n\r\n";
        $message .= __('For security reasons, please change your password after your first login.', 'hello-theme-child') . "\r\n\r\n";
        $message .= __('Best regards,', 'hello-theme-child') . "\r\n";
        $message .= $blog_name . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Process class step
     */
    private function process_class_step() {
        // Back button
        if (isset($_POST['back'])) {
            $this->current_step = 1;
            return;
        }
        
        // Validate and process class selection/creation
        if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
            // Existing class selected
            $class_id = absint($_POST['class_id']);
            $class = get_post($class_id);
            
            if ($class && $class->post_type === 'groups') {
                $this->form_data['class_id'] = $class_id;
                
                // If we have a teacher ID, assign them as group leader
                if (!empty($this->form_data['teacher_id'])) {
                    $teacher_id = $this->form_data['teacher_id'];
                    
                    // Method 1: Use LearnDash API function if available
                    if (function_exists('learndash_set_groups_administrators')) {
                        error_log(sprintf('TEACHER_WIZARD: Using learndash_set_groups_administrators to assign teacher %d to group %d', $teacher_id, $class_id));
                        $existing_leaders = learndash_get_groups_administrator_ids($class_id);
                        if (!in_array($teacher_id, $existing_leaders)) {
                            $existing_leaders[] = $teacher_id;
                            learndash_set_groups_administrators($class_id, $existing_leaders);
                        }
                    }
                    
                    // Method 2: Update the standard LearnDash meta key
                    $ld_group_leaders = (array) get_post_meta($class_id, '_ld_group_leaders', true);
                    if (!in_array($teacher_id, $ld_group_leaders)) {
                        $ld_group_leaders[] = $teacher_id;
                        update_post_meta($class_id, '_ld_group_leaders', $ld_group_leaders);
                        error_log(sprintf('TEACHER_WIZARD: Updated _ld_group_leaders meta for group %d with teacher %d', $class_id, $teacher_id));
                    }
                    
                    // Method 3: For backward compatibility with old code
                    $group_leaders = (array) get_post_meta($class_id, '_groups_leader', true);
                    if (!in_array($teacher_id, $group_leaders)) {
                        $group_leaders[] = $teacher_id;
                        update_post_meta($class_id, '_groups_leader', $group_leaders);
                    }
                    
                    // Method 4: Direct database update as final fallback
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'learndash_group_leaders';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT user_id FROM $table_name WHERE user_id = %d AND group_id = %d",
                            $teacher_id, $class_id
                        ));
                        
                        if (!$existing) {
                            error_log(sprintf('TEACHER_WIZARD: Direct DB insert to learndash_group_leaders for teacher %d and group %d', $teacher_id, $class_id));
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'user_id' => $teacher_id,
                                    'group_id' => $class_id
                                ),
                                array('%d', '%d')
                            );
                        }
                    }
                    
                    // Log the assignment
                    error_log(sprintf(
                        'TEACHER_WIZARD: Assigned teacher %d to group %d using multiple methods',
                        $teacher_id,
                        $class_id
                    ));
                }
                
                $this->current_step = 3;
                return;
            }
            
            $this->errors[] = __('Invalid class selected or you do not have permission to access this class.', 'hello-theme-child');
            
        } elseif (!empty($_POST['new_class_name'])) {
            // New class creation
            $class_data = array(
                'post_title'   => sanitize_text_field($_POST['new_class_name']),
                'post_type'    => 'groups',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
                'post_content' => !empty($_POST['new_class_description']) ? 
                    sanitize_textarea_field($_POST['new_class_description']) : ''
            );
            
            $class_id = wp_insert_post($class_data);
            
            if (!is_wp_error($class_id) && $class_id > 0) {
                // Set up LearnDash group settings
                update_post_meta($class_id, '_ld_price_type', 'closed');
                update_post_meta($class_id, '_ld_price', '');
                update_post_meta($class_id, '_ld_price_billing_cycle', '');
                
                // Assign teacher as group leader if available
                if (!empty($this->form_data['teacher_id'])) {
                    $teacher_id = $this->form_data['teacher_id'];
                    
                    // Method 1: Use LearnDash API function if available
                    if (function_exists('learndash_set_groups_administrators')) {
                        error_log(sprintf('TEACHER_WIZARD: Using learndash_set_groups_administrators for new group %d with teacher %d', $class_id, $teacher_id));
                        learndash_set_groups_administrators($class_id, array($teacher_id));
                    }
                    
                    // Method 2: Update the standard LearnDash meta key
                    update_post_meta($class_id, '_ld_group_leaders', array($teacher_id));
                    error_log(sprintf('TEACHER_WIZARD: Updated _ld_group_leaders meta for new group %d with teacher %d', $class_id, $teacher_id));
                    
                    // Method 3: For backward compatibility with old code
                    update_post_meta($class_id, '_groups_leader', array($teacher_id));
                    
                    // Method 4: Direct database update as final fallback
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'learndash_group_leaders';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        error_log(sprintf('TEACHER_WIZARD: Direct DB insert to learndash_group_leaders for new group %d with teacher %d', $class_id, $teacher_id));
                        $wpdb->insert(
                            $table_name,
                            array(
                                'user_id' => $teacher_id,
                                'group_id' => $class_id
                            ),
                            array('%d', '%d')
                        );
                    }
                    
                    // Log the assignment
                    error_log(sprintf(
                        'TEACHER_WIZARD: Created group %d and assigned teacher %d as leader using multiple methods',
                        $class_id,
                        $teacher_id
                    ));
                }
                
                $class_name = get_post_meta($class_id, '_post_title', true);
                
                // Run hook after class selection for teacher assignment
                if (!empty($this->form_data['teacher_id']) && !empty($class_id)) {
                    do_action('teacher_class_wizard_after_class_selection', $class_id, $this->form_data['teacher_id']);
                }
                
                // Store form data
                $this->form_data['class_id'] = $class_id;
                $this->form_data['class_name'] = $class_name;
                
                // Move to step 3
                $this->current_step = 3;
                return;
            }
            
            $error_message = is_wp_error($class_id) ? 
                $class_id->get_error_message() : 
                __('Unknown error occurred while creating class.', 'hello-theme-child');
                
            $this->errors[] = sprintf(
                __('Failed to create class: %s', 'hello-theme-child'),
                $error_message
            );
            
        } else {
            $this->errors[] = __('Please select an existing class or create a new one.', 'hello-theme-child');
        }
    }
    
    /**
     * Process promo code step
     */
    private function process_promo_step() {
        // Back button
        if (isset($_POST['back'])) {
            $this->current_step = 2;
            return;
        }
        
        // Validate and process promo code generation
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 10;
        $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
        
        // Check if create_students was actually checked AND student data was submitted
        // This is to prevent validation errors when the student form wasn't displayed
        $create_students = !empty($_POST['create_students']);
        $students = isset($_POST['students']) ? $_POST['students'] : array();
        
        error_log('WIZARD CLASS: Process promo step - create_students: ' . ($create_students ? 'true' : 'false'));
        error_log('WIZARD CLASS: Process promo step - students data submitted: ' . (!empty($students) ? 'true' : 'false'));
        
        // Validate quantity
        if ($quantity < 1 || $quantity > 1000) {
            $this->errors[] = __('Quantity must be between 1 and 1000.', 'hello-theme-child');
            return;
        }
        
        // Only validate student data if the checkbox was checked AND student data was submitted
        if ($create_students && !empty($students)) {
            error_log('WIZARD CLASS: Validating student data');
            
            // Validate each student
            foreach ($students as $index => $student) {
                $student_num = $index + 1;
                
                // Required fields
                $required_fields = array(
                    'first_name' => __('First Name', 'hello-theme-child'),
                    'last_name' => __('Last Name', 'hello-theme-child'),
                    'username' => __('Phone Number', 'hello-theme-child'),
                    'password' => __('Student ID', 'hello-theme-child')
                );
                
                foreach ($required_fields as $field => $label) {
                    if (empty(trim($student[$field]))) {
                        $this->errors[] = sprintf(__('Student #%d: %s is required', 'hello-theme-child'), $student_num, $label);
                    }
                }
                
                // Validate phone number format
                if (!empty($student['username']) && !preg_match('/^\d{9,15}$/', $student['username'])) {
                    $this->errors[] = sprintf(__('Student #%d: Please enter a valid phone number (9-15 digits)', 'hello-theme-child'), $student_num);
                }
                
                // Check if username already exists
                if (!empty($student['username']) && username_exists($student['username'])) {
                    $this->errors[] = sprintf(__('Student #%d: The username %s is already taken', 'hello-theme-child'), $student_num, $student['username']);
                }
            }
            
            // If we have errors, stop processing
            if (!empty($this->errors)) {
                return;
            }
        }
        
        // Generate promo codes
        $promo_codes = $this->generate_promo_codes($quantity, $prefix);
        $created_students = array();
        
        // If creating students, create user accounts
        if ($create_students && !empty($students)) {
            foreach ($students as $index => $student_data) {
                $student_data = array_map('sanitize_text_field', $student_data);
                $promo_code = isset($promo_codes[$index]) ? $promo_codes[$index] : '';
                
                // Create user data array
                $user_data = array(
                    'user_login'    => $student_data['username'],
                    'user_email'    => !empty($student_data['email']) ? $student_data['email'] : $student_data['username'] . '@example.com',
                    'first_name'    => $student_data['first_name'],
                    'last_name'     => $student_data['last_name'],
                    'user_pass'     => $student_data['password'],
                    'role'          => 'subscriber',
                    'display_name'  => $student_data['first_name'] . ' ' . $student_data['last_name']
                );
                
                // Insert user
                $user_id = wp_insert_user($user_data);
                
                if (is_wp_error($user_id)) {
                    $this->errors[] = sprintf(
                        __('Error creating student %s: %s', 'hello-theme-child'),
                        $student_data['first_name'] . ' ' . $student_data['last_name'],
                        $user_id->get_error_message()
                    );
                    continue;
                }
                
                // Add user meta
                update_user_meta($user_id, 'phone_number', $student_data['username']);
                update_user_meta($user_id, 'student_id', $student_data['password']);
                
                // Store promo code association with student
                if (!empty($promo_code)) {
                    update_user_meta($user_id, '_promo_code', $promo_code);
                    
                    // Also update the promo code CPT entry to link to this student
                    $existing = get_page_by_title($promo_code, OBJECT, 'ld_promo_code');
                    if ($existing) {
                        error_log('WIZARD CLASS: Linking student ' . $user_id . ' with promo code: ' . $promo_code);
                        update_post_meta($existing->ID, '_ld_promo_code_student_id', $user_id);
                    }
                }
                
                // Enroll student in the selected group if available
                if (!empty($this->form_data['class_id'])) {
                    $group_id = $this->form_data['class_id'];
                    
                    error_log('WIZARD CLASS: Enrolling student ' . $user_id . ' in group ' . $group_id . ' (class_id from form_data)');
                    
                    // Double-check the group exists before enrollment
                    $group = get_post($group_id);
                    if (!$group || $group->post_type !== 'groups') {
                        error_log('WIZARD CLASS: ERROR - Group ' . $group_id . ' does not exist or is not a valid group');
                        continue;
                    }
                    
                    // Force a direct update to the primary LearnDash user group table
                    global $wpdb;
                    $user_group_table = $wpdb->prefix . 'learndash_user_group';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$user_group_table'") == $user_group_table) {
                        // Check if entry already exists
                        $existing_entry = $wpdb->get_var($wpdb->prepare(
                            "SELECT user_id FROM $user_group_table WHERE user_id = %d AND group_id = %d",
                            $user_id, $group_id
                        ));
                        
                        if (!$existing_entry) {
                            error_log('WIZARD CLASS: Direct DB insert to ' . $user_group_table);
                            $result = $wpdb->insert(
                                $user_group_table,
                                array(
                                    'user_id' => $user_id,
                                    'group_id' => $group_id
                                ),
                                array('%d', '%d')
                            );
                            error_log('WIZARD CLASS: DB insert result: ' . ($result !== false ? 'success' : 'FAILED: ' . $wpdb->last_error));
                        } else {
                            error_log('WIZARD CLASS: User already in group table');
                        }
                    }
                    
                    // Use all available LearnDash functions to ensure enrollment
                    if (function_exists('ld_update_group_access')) {
                        error_log('WIZARD CLASS: Using ld_update_group_access');
                        ld_update_group_access($user_id, $group_id);
                    }
                    
                    // Add to group users - main LearnDash function
                    if (function_exists('learndash_set_group_users')) {
                        error_log('WIZARD CLASS: Using learndash_set_group_users');
                        // Get existing users
                        $existing_users = learndash_get_groups_user_ids($group_id);
                        if (!is_array($existing_users)) {
                            $existing_users = array();
                        }
                        // Add new user if not already in the group
                        if (!in_array($user_id, $existing_users)) {
                            $existing_users[] = $user_id;
                            learndash_set_group_users($group_id, $existing_users);
                        }
                    }
                    
                    // Direct database update for older LearnDash versions
                    global $wpdb;
                    // Check if the user_group entry exists
                    $table_name = $wpdb->prefix . 'learndash_user_group';
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        $existing = $wpdb->get_var($wpdb->prepare(
                            "SELECT user_id FROM $table_name WHERE user_id = %d AND group_id = %d",
                            $user_id, $group_id
                        ));
                        
                        if (!$existing) {
                            error_log('WIZARD CLASS: Direct DB insert to learndash_user_group');
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'user_id' => $user_id,
                                    'group_id' => $group_id
                                ),
                                array('%d', '%d')
                            );
                        }
                    }
                    
                    // Add user as group member via post meta as fallback
                    $group_member_ids = (array) get_post_meta($group_id, '_groups_members', true);
                    if (!in_array($user_id, $group_member_ids)) {
                        $group_member_ids[] = $user_id;
                        update_post_meta($group_id, '_groups_members', $group_member_ids);
                        error_log('WIZARD CLASS: Updated _groups_members meta with ' . count($group_member_ids) . ' members');
                    }
                }
                
                // Store created student data
                $created_students[] = array(
                    'id' => $user_id,
                    'name' => $student_data['first_name'] . ' ' . $student_data['last_name'],
                    'username' => $student_data['username'],
                    'password' => $student_data['password'],
                    'promo_code' => $promo_code
                );
                
                // Log the creation
                error_log(sprintf(
                    'TEACHER_WIZARD: Created student %s (ID: %d) with username %s',
                    $student_data['first_name'] . ' ' . $student_data['last_name'],
                    $user_id,
                    $student_data['username']
                ));
            }
        }
        
        // Store form data
        $this->form_data['promo_codes'] = $promo_codes;
        $this->form_data['quantity'] = $quantity;
        $this->form_data['prefix'] = $prefix;
        $this->form_data['created_students'] = $created_students;
        $this->form_data['create_students'] = $create_students;
    }
    
    /**
     * AJAX handler to search for teachers
     */
    public function ajax_search_teachers() {
        // Debug log
        error_log('AJAX search_teachers called');
        
        check_ajax_referer('teacher_class_wizard_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('manage_school')) {
            $error = 'Permission denied';
            error_log('AJAX Error: ' . $error);
            wp_send_json_error(__('Permission denied', 'hello-theme-child'));
        }
        
        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        error_log('Search term: ' . $search);
        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        // Query for teachers with search
        $args = array(
            'role'         => 'school_teacher',
            'number'       => $per_page,
            'offset'       => $offset,
            'search'       => '*' . esc_attr($search) . '*',
            'search_fields' => array(
                'user_login',
                'user_email',
                'display_name',
                'user_nicename',
            ),
            'fields' => 'all_with_meta',
            'orderby' => 'display_name',
            'order' => 'ASC',
            'count_total' => true
        );
        
        $user_query = new WP_User_Query($args);
        $teachers = $user_query->get_results();
        $total = $user_query->get_total();
        
        $results = array(
            'items' => array(),
            'total_count' => $total,
            'incomplete_results' => ($offset + count($teachers)) < $total,
            'pagination' => array(
                'more' => ($offset + count($teachers)) < $total
            )
        );
        
        foreach ($teachers as $teacher) {
            $results['items'][] = array(
                'id' => $teacher->ID,
                'text' => sprintf(
                    '%s (%s) - %s',
                    $teacher->display_name,
                    $teacher->user_email,
                    get_user_meta($teacher->ID, 'phone_number', true)
                ),
                'name' => $teacher->display_name,
                'email' => $teacher->user_email,
                'phone' => get_user_meta($teacher->ID, 'phone_number', true)
            );
        }
        
        wp_send_json($results);
    }
    
    /**
     * AJAX handler to check if a username exists
     */
    public function ajax_check_username_exists() {
        check_ajax_referer('teacher_class_wizard_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('manage_school')) {
            wp_send_json_error(__('Permission denied', 'hello-theme-child'));
        }
        
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        
        if (empty($username)) {
            wp_send_json_error(__('Username is required', 'hello-theme-child'));
        }
        
        $exists = username_exists($username);
        
        wp_send_json_success(array(
            'exists' => (bool) $exists
        ));
    }
    
    /**
     * Generate promo codes
     */
    private function generate_promo_codes($quantity, $prefix = '') {
        // Debug log
        error_log('WIZARD CLASS: generate_promo_codes called for ' . $quantity . ' codes with prefix "' . $prefix . '"');
        
        $codes = array();
        $length = 8 - strlen($prefix);
        
        for ($i = 0; $i < $quantity; $i++) {
            $code = $prefix . wp_generate_password($length, false);
            $codes[] = strtoupper($code);
        }
        
        // Debug log before saving
        error_log('WIZARD CLASS: Generated ' . count($codes) . ' codes, now saving to database');
        error_log('WIZARD CLASS: Form data: ' . print_r($this->form_data, true));
        
        // Direct implementation: Save each promo code to database
        if (!empty($codes)) {
            // Get class and teacher IDs
            $class_id = !empty($this->form_data['class_id']) ? intval($this->form_data['class_id']) : 0;
            $teacher_id = !empty($this->form_data['teacher_id']) ? intval($this->form_data['teacher_id']) : 0;
            
            error_log('WIZARD CLASS: Saving codes for class_id: ' . $class_id . ', teacher_id: ' . $teacher_id);
            
            // Save each code
            foreach ($codes as $code) {
                $this->save_promo_code_to_db($code, $class_id, $teacher_id);
            }
            
            // Make teacher a group leader for this class if not already
            if ($class_id && $teacher_id) {
                $this->assign_teacher_to_group($teacher_id, $class_id);
            }
        }
        
        return $codes;
    }
    
    /**
     * Save promo code to database
     * 
     * @param string $code Promo code
     * @param int $group_id Group/class ID
     * @param int $teacher_id Teacher ID
     * @return int|WP_Error Post ID or WP_Error
     */
    private function save_promo_code_to_db($code, $group_id, $teacher_id) {
        error_log('WIZARD CLASS: Saving promo code: ' . $code . ' for group: ' . $group_id . ' and teacher: ' . $teacher_id);
        
        // Check if code already exists
        $existing = get_page_by_title($code, OBJECT, 'ld_promo_code');
        if ($existing) {
            // Update existing code
            $post_id = $existing->ID;
            
            // Update metadata
            update_post_meta($post_id, '_ld_promo_code_group_id', $group_id);
            update_post_meta($post_id, '_ld_promo_code_teacher_id', $teacher_id);
            
            error_log('WIZARD CLASS: Updated existing promo code with ID: ' . $post_id);
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
            error_log('WIZARD CLASS: Error creating promo code: ' . $post_id->get_error_message());
            return $post_id;
        }
        
        // Add metadata
        update_post_meta($post_id, '_ld_promo_code_code', $code);
        update_post_meta($post_id, '_ld_promo_code_group_id', $group_id);
        update_post_meta($post_id, '_ld_promo_code_teacher_id', $teacher_id);
        
        error_log('WIZARD CLASS: Created new promo code with ID: ' . $post_id);
        return $post_id;
    }
    
    /**
     * Assign teacher as group leader
     * 
     * @param int $teacher_id Teacher user ID
     * @param int $group_id Group/class ID
     */
    private function assign_teacher_to_group($teacher_id, $group_id) {
        error_log('WIZARD CLASS: Assigning teacher ' . $teacher_id . ' as leader for group ' . $group_id);
        
        // Make sure the teacher is set as a group leader
        if (function_exists('learndash_set_groups_administrators')) {
            learndash_set_groups_administrators($group_id, array($teacher_id));
            error_log('WIZARD CLASS: Used learndash_set_groups_administrators');
        } else {
            // Fallback if LearnDash function is not available
            $group_leaders = get_post_meta($group_id, '_ld_group_leaders', true);
            if (empty($group_leaders) || !is_array($group_leaders)) {
                $group_leaders = array();
            }
            
            if (!in_array($teacher_id, $group_leaders)) {
                $group_leaders[] = $teacher_id;
                update_post_meta($group_id, '_ld_group_leaders', $group_leaders);
            }
            error_log('WIZARD CLASS: Used fallback method to set group leader');
        }
    }
    
    /**
     * AJAX handler for downloading promo codes as CSV
     */
    public function ajax_download_promo_codes_csv() {
        error_log('WIZARD CLASS: Starting ajax_download_promo_codes_csv');
        
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'teacher_class_wizard_nonce')) {
            error_log('WIZARD CLASS: CSV download nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Get codes from JSON
        $codes_json = isset($_POST['codes_json']) ? sanitize_text_field($_POST['codes_json']) : '';
        if (empty($codes_json)) {
            error_log('WIZARD CLASS: CSV download failed - empty codes_json');
            wp_send_json_error(array('message' => 'No promo codes provided'));
            exit;
        }
        
        // Decode the JSON
        $codes = json_decode(stripslashes($codes_json), true);
        if (!is_array($codes) || empty($codes)) {
            error_log('WIZARD CLASS: CSV download failed - invalid codes_json format: ' . $codes_json);
            wp_send_json_error(array('message' => 'Invalid promo codes format'));
            exit;
        }
        
        error_log('WIZARD CLASS: Generating CSV for ' . count($codes) . ' codes');
        
        // Create CSV string
        $csv_content = "Promo Code,URL\r\n";
        $site_url = site_url('/');
        
        foreach ($codes as $code_data) {
            $code = isset($code_data['code']) ? $code_data['code'] : '';
            if (!empty($code)) {
                // Create registration URL with the code
                $registration_url = add_query_arg('promo_code', urlencode($code), $site_url);
                
                // Add to CSV
                $csv_content .= '"' . esc_attr($code) . '","' . esc_url($registration_url) . '"\r\n';
            }
        }
        
        error_log('WIZARD CLASS: CSV generated successfully');
        
        // Return the CSV content
        wp_send_json_success(array(
            'message' => 'CSV generated successfully',
            'csv_content' => $csv_content
        ));
        exit;
    }
    
    /**
     * Render wizard
     */
    public function render_wizard() {
        // Check if user has access
        $allowed_roles = array('administrator', 'editor', 'teacher', 'מורה / רכז');
        $current_user = wp_get_current_user();
        $has_access = false;
        
        // Check if user has any of the allowed roles
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array) $current_user->roles)) {
                $has_access = true;
                break;
            }
        }
        
        if (!$has_access) {
            wp_die(
                __('You do not have sufficient permissions to access this page.', 'hello-theme-child'),
                '',
                array('response' => 403)
            );
        }
        
        // Log for debugging
        error_log('Rendering Class Management Wizard for user: ' . get_current_user_id());
        
        // Check for required files
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        
        // Get teacher and class data for dropdowns
        // Query for users with the school_teacher role
        $teachers = get_users(array(
            'role' => 'school_teacher',
            'orderby' => 'display_name',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'school_teacher',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        // No need to show an error if no teachers found, as the form allows creating new teachers
        
        // Add RTL support for Hebrew
        add_filter('admin_body_class', function($classes) {
            return $classes . ' rtl';
        });
        
        // Query for LearnDash groups
        $classes = get_posts(array(
            'post_type' => 'groups',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        // Log for debugging
        error_log('TEACHER_WIZARD: Found ' . count($classes) . ' LearnDash groups');
        
        // If no groups found, try to create a default one
        if (empty($classes) && current_user_can('manage_options')) {
            error_log('TEACHER_WIZARD: No groups found, creating default group');
            $default_group_id = wp_insert_post(array(
                'post_title' => __('Default Class', 'hello-theme-child'),
                'post_type' => 'groups',
                'post_status' => 'publish'
            ));
            
            if (!is_wp_error($default_group_id)) {
                $classes = get_posts(array(
                    'post_type' => 'groups',
                    'include' => array($default_group_id),
                    'post_status' => 'publish'
                ));
                
                // Set up default group settings
                update_post_meta($default_group_id, '_ld_price_type', 'closed');
                update_post_meta($default_group_id, '_ld_price', '');
                update_post_meta($default_group_id, '_ld_price_billing_cycle', '');
                
                error_log('TEACHER_WIZARD: Created default group with ID ' . $default_group_id);
            }
        }
        
        // Include the appropriate template based on current step
        include get_stylesheet_directory() . '/includes/admin/views/wizard/header.php';
        
        // Show errors if any
        if (!empty($this->errors)) {
            echo '<div class="error"><ul>';
            foreach ($this->errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }
        
        // Include step template
        if ($this->current_step == 2) {
            // Use wrapper file for step 2 to provide necessary variables
            $step_template = get_stylesheet_directory() . '/includes/admin/views/wizard/step-2-wrapper.php';
            if (file_exists($step_template)) {
                include $step_template;
            } else {
                echo '<div class="error"><p>' . __('Step 2 template not found.', 'hello-theme-child') . '</p></div>';
            }
        } else {
            // Normal template loading for other steps
            $step_template = get_stylesheet_directory() . '/includes/admin/views/wizard/step-' . $this->current_step . '.php';
            if (file_exists($step_template)) {
                include $step_template;
            } else {
                echo '<div class="error"><p>' . __('Invalid step.', 'hello-theme-child') . '</p></div>';
            }
        }
        
        include get_stylesheet_directory() . '/includes/admin/views/wizard/footer.php';
    }
    
    /**
     * Generate promo codes via AJAX
     */
    public function ajax_generate_promo_codes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'teacher_class_wizard_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Get parameters
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 10;
        $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
        $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : '';
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        
        // Validate quantity
        if ($quantity < 1 || $quantity > 1000) {
            wp_send_json_error(array('message' => 'Invalid quantity. Please enter a value between 1 and 1000.'));
        }
        
        // Generate codes using the unique code helper
        $generated_codes = array();
        $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($prefix)), 0, 5);
        
        for ($i = 0; $i < $quantity; $i++) {
            // Generate a unique code
            $code = $this->generate_unique_code($prefix);
            $generated_codes[] = array(
                'code' => $code
            );
        }
        
        // Form data for the filter
        $form_data = array(
            'class_id' => $class_id,
            'teacher_id' => isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : get_current_user_id(),
            'expiry_date' => $expiry_date
        );
        
        // Apply the filter to save the codes (implemented in Teacher_Class_Wizard_Fix)
        $codes = apply_filters('teacher_class_wizard_generate_promo_codes', $generated_codes, $quantity, $prefix, $form_data);
        
        if (empty($codes)) {
            wp_send_json_error(array('message' => 'Failed to generate promo codes'));
        }
        
        // Return success with codes
        wp_send_json_success(array('codes' => $codes));
    }
    
    // The generate_promo_codes functionality is now handled via the 'teacher_class_wizard_generate_promo_codes' filter
    // which is implemented in the Teacher_Class_Wizard_Fix class
    
    /**
     * Generate a unique promo code
     * 
     * @param string $prefix Optional prefix
     * @return string Unique code
     */
    private function generate_unique_code($prefix = '') {
        $characters = 'ABCDEFGHKLMNPQRSTUVWXYZ23456789';
        $code_length = 8;
        $max_attempts = 50;
        $attempt = 0;
        
        do {
            // Generate random code
            $code = $prefix;
            for ($i = 0; $i < $code_length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Add hyphens for readability (format: XXXX-XXXX)
            if (strlen($code) > 4) {
                $code = substr($code, 0, 4) . '-' . substr($code, 4);
            }
            
            // Check if code exists
            $exists = get_posts(array(
                'post_type'  => 'ld_promo_code',
                'meta_key'   => '_ld_promo_code_code',
                'meta_value' => $code,
                'numberposts' => 1,
            ));
            
            $attempt++;
        } while (!empty($exists) && $attempt < $max_attempts);
        
        return $code;
    }
}

// Class initialization is handled in theme's functions.php
