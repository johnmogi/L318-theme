<?php
/**
 * Registration Codes Handler - Minimum Viable Version
 * Manages the generation, validation, and usage of registration codes with group management
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Registration_Codes {
    private static $instance = null;
    private $table_name;
    private $version = '1.2.0';

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        $domain = 'registration-codes';
        $locale = apply_filters('plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain);
        
        // wp-content/languages/registration-codes/registration-codes-he_IL.mo
        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        
        // wp-content/plugins/registration-codes/languages/registration-codes-he_IL.mo
        load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Get instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = 'edc_registration_codes';
        
        // Debug log initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Registration_Codes constructor called - ' . get_class($this));
            error_log('Backtrace: ' . wp_debug_backtrace_summary());
        }
        
        // Load translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Create database tables on plugin activation
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Verify table exists on each admin page load
        if (is_admin()) {
            add_action('admin_init', array($this, 'verify_table_exists'));
            add_action('admin_notices', array($this, 'admin_notices'));
            
            // Handle force update action
            if (isset($_GET['force_update_registration_codes']) && current_user_can('manage_options')) {
                $this->force_update_tables();
                wp_redirect(remove_query_arg('force_update_registration_codes'));
                exit;
            }
        }
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_generate_registration_codes', array($this, 'ajax_generate_codes'));
        add_action('wp_ajax_load_registration_codes_tab', array($this, 'ajax_load_tab_content'));
        add_action('wp_ajax_export_registration_codes', array($this, 'ajax_export_codes'));
        
        // Process registration code on user registration
        add_action('user_register', array($this, 'process_registration_code'));
        
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }
    

    
    /**
     * Verify that the database table exists and is up to date
     */
    public function verify_table_exists() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration codes table does not exist. Creating...');
            }
            $this->create_tables();
        } else {
            // Table exists, check if we need to update it
            $current_version = get_option('registration_codes_db_version', '0');
            if (version_compare($current_version, $this->version, '<')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Registration codes table needs update. Current version: ' . $current_version . ', New version: ' . $this->version);
                }
                $this->create_tables();
            }
        }
    }

    /**
     * Create database tables
     */
    /**
     * Create or update the database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $current_version = get_option('registration_codes_db_version', '0');
        $installed_version = get_option('registration_codes_db_version');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Creating or updating registration codes table');
            error_log('Current version: ' . $current_version);
            error_log('Plugin version: ' . $this->version);
        }

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        // Define the table schema
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            role varchar(50) NOT NULL DEFAULT 'subscriber',
            group_name varchar(100) DEFAULT '',
            course_id bigint(20) DEFAULT NULL,
            max_uses int(11) DEFAULT 1,
            used_count int(11) DEFAULT 0,
            expiry_date datetime DEFAULT NULL,
            is_used tinyint(1) DEFAULT 0,
            used_by bigint(20) DEFAULT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY group_name (group_name),
            KEY course_id (course_id),
            KEY expiry_date (expiry_date),
            KEY is_used (is_used)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // If table doesn't exist, create it
        if (!$table_exists) {
            $result = dbDelta($sql);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Created new table: ' . $this->table_name);
                error_log('dbDelta result: ' . print_r($result, true));
            }
        } else {
            // Table exists, check for missing columns
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
            $column_names = wp_list_pluck($columns, 'Field');
            
            // Add missing columns
            $missing_columns = array();
            
            if (!in_array('course_id', $column_names)) {
                $missing_columns[] = 'ADD COLUMN course_id bigint(20) DEFAULT NULL';
                $missing_columns[] = 'ADD KEY course_id (course_id)';
            }
            
            if (!in_array('group_name', $column_names)) {
                $missing_columns[] = 'ADD COLUMN group_name varchar(100) DEFAULT \'\'';
                $missing_columns[] = 'ADD KEY group_name (group_name)';
            }
            
            if (!in_array('max_uses', $column_names)) {
                $missing_columns[] = 'ADD COLUMN max_uses int(11) DEFAULT 1';
            }
            
            if (!in_array('used_count', $column_names)) {
                $missing_columns[] = 'ADD COLUMN used_count int(11) DEFAULT 0';
            }
            
            if (!in_array('expiry_date', $column_names)) {
                $missing_columns[] = 'ADD COLUMN expiry_date datetime DEFAULT NULL';
                $missing_columns[] = 'ADD KEY expiry_date (expiry_date)';
            }
            
            if (!in_array('is_used', $column_names)) {
                $missing_columns[] = 'ADD COLUMN is_used tinyint(1) DEFAULT 0';
                $missing_columns[] = 'ADD KEY is_used (is_used)';
            }
            
            if (!in_array('used_by', $column_names)) {
                $missing_columns[] = 'ADD COLUMN used_by bigint(20) DEFAULT NULL';
            }
            
            if (!in_array('used_at', $column_names)) {
                $missing_columns[] = 'ADD COLUMN used_at datetime DEFAULT NULL';
            }
            
            if (!empty($missing_columns)) {
                $alter_sql = "ALTER TABLE {$this->table_name} " . implode(', ', $missing_columns);
                $wpdb->query($alter_sql);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Updated table schema: ' . $alter_sql);
                }
            }
        }

        // Update the version in the database
        if (version_compare($current_version, $this->version, '<')) {
            update_option('registration_codes_db_version', $this->version);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Updated database version to: ' . $this->version);
            }
        }
        
        // Verify the table structure
        $this->verify_table_structure();
        
        return true;
    }
    
    /**
     * Force update database tables
     */
    public function force_update_tables() {
        global $wpdb;
        
        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        
        // Recreate the table
        $this->create_tables();
        
        // Set a transient to show success message
        set_transient('registration_codes_force_updated', true, 60);
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Show success message after force update
        if (get_transient('registration_codes_force_updated')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Registration codes database table has been recreated successfully.</p>';
            echo '</div>';
            delete_transient('registration_codes_force_updated');
        }
        
        // Show notice if table needs update
        if (current_user_can('manage_options') && $this->needs_database_update()) {
            $update_url = add_query_arg('force_update_registration_codes', '1');
            echo '<div class="notice notice-warning">';
            echo '<p>The registration codes database table needs to be updated. ';
            echo '<a href="' . esc_url($update_url) . '" class="button button-primary">Update Now</a>';
            echo '</p></div>';
        }
    }
    
    /**
     * Check if database needs update
     */
    private function needs_database_update() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            return true;
        }
        
        // Check for required columns
        $required_columns = array('id', 'code', 'role', 'course_id', 'created_at');
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
        $column_names = wp_list_pluck($columns, 'Field');
        
        foreach ($required_columns as $column) {
            if (!in_array($column, $column_names)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify and fix table structure if needed
     */
    private function verify_table_structure() {
        global $wpdb;
        
        $columns = array(
            'id' => 'mediumint(9) NOT NULL AUTO_INCREMENT',
            'code' => 'varchar(50) NOT NULL',
            'role' => "varchar(50) NOT NULL DEFAULT 'subscriber'",
            'group_name' => "varchar(100) DEFAULT ''",
            'course_id' => 'bigint(20) DEFAULT NULL',
            'max_uses' => 'int(11) DEFAULT 1',
            'used_count' => 'int(11) DEFAULT 0',
            'expiry_date' => 'datetime DEFAULT NULL',
            'is_used' => 'tinyint(1) DEFAULT 0',
            'used_by' => 'bigint(20) DEFAULT NULL',
            'used_at' => 'datetime DEFAULT NULL',
            'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
            'created_by' => 'bigint(20) NOT NULL'
        );
        
        $indexes = array(
            'PRIMARY KEY  (id)',
            'UNIQUE KEY code (code)',
            'KEY group_name (group_name)',
            'KEY course_id (course_id)',
            'KEY expiry_date (expiry_date)',
            'KEY is_used (is_used)'
        );
        
        // Get current columns
        $current_columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
        $current_column_names = wp_list_pluck($current_columns, 'Field');
        
        // Check for missing columns and add them
        foreach ($columns as $column_name => $definition) {
            if (!in_array($column_name, $current_column_names)) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN {$column_name} {$definition}");
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Added missing column: {$column_name} to {$this->table_name}");
                }
            }
        }
        
        // Get current indexes
        $current_indexes = $wpdb->get_results("SHOW INDEX FROM {$this->table_name}", ARRAY_A);
        $index_names = array_unique(wp_list_pluck($current_indexes, 'Key_name'));
        
        // Add missing indexes
        foreach ($indexes as $index) {
            if (preg_match('/^(PRIMARY|UNIQUE)?\s*KEY\s+(?:`?([^`\s]+)`?)/i', $index, $matches)) {
                $index_name = !empty($matches[2]) ? $matches[2] : 'PRIMARY';
                if (!in_array($index_name, $index_names)) {
                    $wpdb->query("ALTER TABLE {$this->table_name} ADD {$index}");
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Added missing index: {$index_name} to {$this->table_name}");
                    }
                }
            }
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Only proceed if we're in the admin and the user has the right capabilities
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        // Remove any existing menu items to prevent duplicates
        $this->remove_duplicate_menus();
        
        $menu_slug = 'registration-codes';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Registration_Codes: Adding admin menu');
            error_log('Current user can manage_options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));
        }
        
        // Add main menu item in Hebrew
        $hook = add_menu_page(
            'ניהול קודי הרשמה', // Page title
            'קודי הרשמה',       // Menu title
            'manage_options',
            $menu_slug,
            array($this, 'render_admin_page'),
            'dashicons-tickets',
            27
        );
        
        // Add submenu items in Hebrew
        add_submenu_page(
            $menu_slug,
            'ניהול קודים',
            'ניהול קודים',
            'manage_options',
            $menu_slug,
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            $menu_slug,
            'ייבוא משתמשים',
            'ייבוא משתמשים',
            'manage_options',
            'import-users',
            array($this, 'render_import_users_page')
        );
        
        add_submenu_page(
            $menu_slug,
            'לוח בקרת מורה',
            'לוח בקרת מורה',
            'manage_options',
            'teacher-dashboard',
            array($this, 'render_teacher_dashboard')
        );
        
        if ($hook) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration Codes: Admin menu added successfully. Hook: ' . $hook);
            }
            
            // Add action to enqueue scripts only on our admin page
            add_action('load-' . $hook, array($this, 'enqueue_admin_scripts'));
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration Codes: Failed to add admin menu');
                error_log('Current user roles: ' . print_r(wp_get_current_user()->roles, true));
            }
        }
    }
    
    /**
     * Remove duplicate menu items to prevent multiple instances
     */
    private function remove_duplicate_menus() {
        global $menu, $submenu;
        $menu_slug = 'registration-codes';
        
        // Remove any existing menu items with the same slug
        if (!empty($menu)) {
            foreach ($menu as $key => $item) {
                if (isset($item[2]) && $item[2] === $menu_slug) {
                    unset($menu[$key]);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Registration Codes: Removed duplicate menu item: ' . $item[2]);
                    }
                }
            }
        }
        
        // Remove any submenu items
        if (!empty($submenu) && isset($submenu[$menu_slug])) {
            unset($submenu[$menu_slug]);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration Codes: Removed duplicate submenu items');
            }
        }
        
        // Re-index the menu array to prevent issues with array keys
        if (is_array($menu)) {
            $menu = array_values($menu);
        }
    }
    
    /**
     * Enqueue admin scripts
     * 
     * @param string $hook The current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'registration-codes') === false && $hook !== 'toplevel_page_registration-codes') {
            return;
        }
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manage';
        
        // Enqueue main admin script
        wp_enqueue_script(
            'registration-codes-admin',
            get_stylesheet_directory_uri() . '/includes/registration/js/admin.js',
            array('jquery', 'jquery-ui-tabs'),
            $this->version,
            true
        );

        // Enqueue import-export script if on the import/export tab
        if ($current_tab === 'import') {
            wp_enqueue_script(
                'registration-codes-import-export',
                get_stylesheet_directory_uri() . '/includes/registration/js/import-export.js',
                array('jquery'),
                $this->version,
                true
            );
        }

        // Localize the script with data needed in JS
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('registration_codes_nonce'),
            'ajax_nonce' => wp_create_nonce('registration_codes_ajax_nonce'),
            'is_admin' => current_user_can('manage_options') ? '1' : '0',
            'dateFormat' => 'yy-mm-dd',
            'i18n' => array(
                'error_loading_tab' => __('Error loading tab content. Please try again.', 'registration-codes'),
                'session_expired' => __('Your session has expired. Please refresh the page and log in again.', 'registration-codes'),
                'generating_codes' => __('Generating codes...', 'registration-codes'),
                'error_occurred' => __('An error occurred. Please try again.', 'registration-codes'),
            )
        );
        
        wp_localize_script('registration-codes-admin', 'registrationCodes', $localize_data);
        
        if (wp_script_is('registration-codes-import-export', 'enqueued')) {
            wp_localize_script('registration-codes-import-export', 'registrationCodes', $localize_data);
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'registration-codes-admin',
            get_stylesheet_directory_uri() . '/includes/registration/css/admin.css',
            array(),
            $this->version
        );
        
        // Add RTL stylesheet if needed
        if (is_rtl()) {
            wp_enqueue_style(
                'registration-codes-admin-rtl',
                get_stylesheet_directory_uri() . '/includes/registration/css/admin-rtl.css',
                array('registration-codes-admin'),
                $this->version
            );
            
            // Add RTL class to body
            add_filter('admin_body_class', function($classes) {
                return $classes . ' rtl';
            });
        }
        
        // Add additional admin styles
        $css = '
            .code-output code { display: block; padding: 5px; background: #f5f5f5; margin: 2px 0; }
            .generated-codes-actions { margin: 15px 0; }
            .nav-tab-wrapper { margin-bottom: 20px; }
        ';
        
        $css .= '
            .registration-codes-tab-content { display: none; }
            .registration-codes-tab-content.active { display: block; }
        ';
        wp_add_inline_style('registration-codes-admin', $css);
    }
    
    /**
     * Generate registration codes
     *
     * @param int $count Number of codes to generate
     * @param string $role User role
     * @param int $user_id User ID of the creator
     * @param int $course_id LearnDash course ID
     * @param int $max_uses Maximum number of uses
     * @param string $expiry_date Expiry date (Y-m-d H:i:s)
     * @return array Array of generated codes
     */
    public function generate_codes($count = 1, $role = 'subscriber', $user_id = 0, $course_id = null, $max_uses = 1, $expiry_date = null) {
        global $wpdb;
        
        $codes = array();
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            if ($this->add_code($code, $role, '', $course_id, $max_uses, $expiry_date, $user_id)) {
                $codes[] = $code;
            }
        }
        
        return $codes;
    }

    /**
     * Add a registration code
     *
     * @param string $code Code to add
     * @param string $role User role
     * @param string $group_name Group name
     * @param int $course_id LearnDash course ID
     * @param int $max_uses Maximum number of uses
     * @param string $expiry_date Expiry date (Y-m-d H:i:s)
     * @param int $user_id User ID of the creator
     * @return bool Success/failure
     */
    /**
     * Add a registration code
     *
     * @param string $code Code to add
     * @param string $role User role
     * @param string $group_name Group name
     * @param int $course_id LearnDash course ID
     * @param int $max_uses Maximum number of uses
     * @param string $expiry_date Expiry date (Y-m-d H:i:s)
     * @param int $user_id User ID of the creator
     * @param array $student_data Optional student data
     * @return bool Success/failure
     */
    public function add_code($code, $role = 'subscriber', $group_name = '', $course_id = null, $max_uses = 1, $expiry_date = null, $user_id = 0, $student_data = array()) {
        global $wpdb;
        
        if (empty($code)) {
            return false;
        }
        
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }
        
        // Prepare student data with defaults
        $student_data = wp_parse_args($student_data, array(
            'first_name'    => '',
            'last_name'     => '',
            'school_name'   => '',
            'school_city'   => '',
            'school_code'   => '',
            'mobile_phone'  => '',
            'user_password' => ''
        ));
        
        // Sanitize student data
        $student_data = array_map('sanitize_text_field', $student_data);
        
        // Prepare the main code data
        $data = array(
            'code' => $code,
            'role' => $role,
            'group_name' => $group_name,
            'course_id' => $course_id,
            'max_uses' => max(1, absint($max_uses)),
            'used_count' => 0,
            'expiry_date' => $expiry_date,
            'is_used' => 0,
            'created_by' => $user_id,
            'created_at' => current_time('mysql')
        );
        
        $format = array(
            '%s', // code
            '%s', // role
            '%s', // group_name
            '%d', // course_id
            '%d', // max_uses
            '%d', // used_count
            '%s', // expiry_date
            '%d', // is_used
            '%d', // created_by
            '%s'  // created_at
        );
        
        // Check if we should include student data (for individual student codes only)
        // Only add these fields if at least one of the values is non-empty
        $has_student_data = false;
        foreach ($student_data as $value) {
            if (!empty($value)) {
                $has_student_data = true;
                break;
            }
        }
        
        // If we have a table with student data fields and student data was provided
        if ($has_student_data) {
            // Get columns from the table to check if student data fields exist
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
            $column_names = array_column($columns, 'Field');
            
            // Only include student data fields if they exist in the table
            $student_data_fields = array(
                'first_name', 'last_name', 'school_name', 'school_city', 
                'school_code', 'mobile_phone', 'user_password'
            );
            
            foreach ($student_data_fields as $field) {
                if (in_array($field, $column_names)) {
                    $data[$field] = $student_data[$field];
                    $format[] = '%s'; // All student data fields use string format
                }
            }
        }
        
        // Debug log the data being inserted
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Adding registration code with data: ' . print_r($data, true));
        }
        
        $result = $wpdb->insert($this->table_name, $data, $format);
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Failed to insert registration code: ' . $wpdb->last_error);
            }
            return false;
        }
        
        return true;
    }

    /**
     * Check if a code exists
     *
     * @param string $code Code to check
     * @return bool True if exists
     */
    public function code_exists($code) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE code = %s",
            $code
        );
        
        return (int) $wpdb->get_var($query) > 0;
    }

    /**
     * Check if a code is valid
     *
     * @param string $code Code to check
     * @param int $course_id Course ID to validate against
     * @return array Code data if valid, false otherwise
     */
    public function validate_code($code, $course_id = null) {
        global $wpdb;
        
        if (empty($code)) {
            return false;
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE code = %s",
            $code
        );
        
        $code_data = $wpdb->get_row($query, ARRAY_A);
        
        if (!$code_data) {
            return false;
        }
        
        // Check if code is already used (legacy check)
        if ($code_data['is_used']) {
            return false;
        }
        
        // Check if code has reached max uses
        if (!empty($code_data['max_uses']) && $code_data['used_count'] >= $code_data['max_uses']) {
            return false;
        }
        
        // Check expiry date
        if (!empty($code_data['expiry_date'])) {
            $now = new DateTime();
            $expiry = new DateTime($code_data['expiry_date']);
            
            if ($now > $expiry) {
                return false;
            }
        }
        
        // Check course_id if specified
        if (!empty($course_id) && !empty($code_data['course_id']) && $code_data['course_id'] != $course_id) {
            return false;
        }
        
        return $code_data;
    }
    
    /**
     * Mark a code as used
     *
     * @param string $code Code to mark
     * @param int $user_id User ID that used the code
     * @return bool Success/failure
     */
    public function use_code($code, $user_id) {
        global $wpdb;
        
        if (empty($code)) {
            return false;
        }
        
        $code_data = $this->validate_code($code);
        if (!$code_data) {
            return false;
        }
        
        // Increment the used_count
        $used_count = $code_data['used_count'] + 1;
        
        // Check if we've reached max uses
        $is_used = ($used_count >= $code_data['max_uses']) ? 1 : 0;
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'is_used' => $is_used,
                'used_count' => $used_count,
                'used_by' => $user_id,
                'used_at' => current_time('mysql')
            ),
            array('code' => $code),
            array('%d', '%d', '%d', '%s'),
            array('%s')
        );
        
        return ($result !== false);
    }

    /**
     * AJAX handler for generating codes
     */
    public function ajax_generate_codes() {
        global $wpdb;
        
        // Start transaction for better error handling
        $wpdb->query('START TRANSACTION');
        
        try {
            // Verify nonce - accept either the standard nonce name or the form field name
            $nonce_verified = false;
            
            // Check for the standard nonce parameter
            if (isset($_POST['nonce'])) {
                $nonce_verified = wp_verify_nonce($_POST['nonce'], 'registration_codes_action'); // This must match the action in the form
            }
            
            // If not verified, try the form field name
            if (!$nonce_verified && isset($_POST['registration_codes_nonce'])) {
                $nonce_verified = wp_verify_nonce($_POST['registration_codes_nonce'], 'registration_codes_action'); // This must match the action in the form
            }
            
            // If still not verified, fail
            if (!$nonce_verified) {
                error_log('Registration codes nonce verification failed');
                error_log('POST data: ' . print_r($_POST, true));
                throw new Exception('Invalid nonce');
            }
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }
            
            // Determine which form was submitted - check both code_type and form_type for backward compatibility
            $form_type = 'class';
            if(isset($_POST['form_type'])) {
                $form_type = sanitize_text_field($_POST['form_type']);
            } elseif(isset($_POST['code_type'])) {
                $form_type = sanitize_text_field($_POST['code_type']);
            }
            
            // Common fields for both form types
            $role = isset($_POST['code_role']) ? sanitize_text_field($_POST['code_role']) : 'subscriber';
            $course_id = !empty($_POST['code_course']) ? intval($_POST['code_course']) : null;
            $max_uses = isset($_POST['code_max_uses']) ? max(1, intval($_POST['code_max_uses'])) : 1;
            $expiry_date = !empty($_POST['code_expiry']) ? sanitize_text_field($_POST['code_expiry']) : null;
            $user_id = get_current_user_id();
            
            // Initialize the student data array for both scenarios
            $student_data = array(
                'first_name'    => '',
                'last_name'     => '',
                'school_name'   => '',
                'school_city'   => '',
                'school_code'   => '',
                'mobile_phone'  => '',
                'user_password' => ''
            );
            
            // Process based on form type
            if ($form_type === 'individual') {
                // Individual student form processing
                $count = 1; // Always generate 1 code for individual students
                
                // Get individual student fields
                $student_data = array(
                    'first_name'    => isset($_POST['ind_first_name']) ? sanitize_text_field($_POST['ind_first_name']) : '',
                    'last_name'     => isset($_POST['ind_last_name']) ? sanitize_text_field($_POST['ind_last_name']) : '',
                    'school_name'   => isset($_POST['ind_school_name']) ? sanitize_text_field($_POST['ind_school_name']) : '',
                    'school_city'   => isset($_POST['ind_school_city']) ? sanitize_text_field($_POST['ind_school_city']) : '',
                    'school_code'   => isset($_POST['ind_school_code']) ? sanitize_text_field($_POST['ind_school_code']) : '',
                    'mobile_phone'  => isset($_POST['ind_mobile_phone']) ? sanitize_text_field($_POST['ind_mobile_phone']) : '',
                    'user_password' => isset($_POST['ind_user_password']) ? $_POST['ind_user_password'] : '',
                    'email'         => isset($_POST['ind_email']) ? sanitize_email($_POST['ind_email']) : ''
                );
                
                // Generate a random password if none provided
                if (empty($student_data['user_password'])) {
                    $student_data['user_password'] = wp_generate_password(12, true, true);
                }
                
                // Validate required fields for individual student
                if (empty($student_data['first_name']) || empty($student_data['last_name'])) {
                    throw new Exception('שם ושם משפחה הם שדות חובה עבור יצירת קוד לתלמיד בודד');
                }
                
                // Group name for individual is a combination of first and last name
                $group = 'Individual-' . $student_data['first_name'] . '-' . $student_data['last_name'];
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('=== Individual Student Code Generation ===');
                    error_log('Student: ' . $student_data['first_name'] . ' ' . $student_data['last_name']);
                    error_log('Email: ' . $student_data['email']);
                    error_log('Course ID: ' . ($course_id ?: 'null'));
                }
            } else {
                // Class/bulk code generation processing
                $count = isset($_POST['code_count']) ? intval($_POST['code_count']) : 1;
                $group = isset($_POST['code_group']) ? sanitize_text_field($_POST['code_group']) : '';
                
                // Validate count for bulk generation
                if ($count < 1 || $count > 1000) {
                    $count = 1; // Default to 1 for invalid values
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('=== Bulk Class Code Generation ===');
                    error_log('Count: ' . $count);
                    error_log('Group/Class: ' . $group);
                    error_log('Role: ' . $role);
                    error_log('Course ID: ' . ($course_id ?: 'null'));
                }
            }
            
            // Debug log the common values
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Max Uses: ' . $max_uses);
                error_log('Expiry Date: ' . ($expiry_date ?: 'null'));
            }
            
            // Generate codes - this is common for both individual and bulk
            $generated_codes = array();
            
            for ($i = 0; $i < $count; $i++) {
                // Generate a unique code
                $code = strtoupper(wp_generate_password(8, false, false));
                
                // Ensure code is unique
                while ($this->code_exists($code)) {
                    $code = strtoupper(wp_generate_password(8, false, false));
                }
                
                // Add the code to the database with student data
                $result = $this->add_code(
                    $code, 
                    $role, 
                    $group, 
                    $course_id, 
                    $max_uses, 
                    $expiry_date, 
                    $user_id,
                    $student_data
                );
                
                if ($result === false) {
                    throw new Exception('Failed to insert code into database: ' . $wpdb->last_error);
                }
                
                // Add code with all relevant data for display
                $generated_codes[] = array(
                    'code' => $code,
                    'role' => $role,
                    'course_id' => $course_id,
                    'group' => $group,
                    'first_name' => $student_data['first_name'],
                    'last_name' => $student_data['last_name'],
                    'email' => isset($student_data['email']) ? $student_data['email'] : ''
                );
            }
            
            // If we got here, commit the transaction
            $wpdb->query('COMMIT');
            
            if (empty($generated_codes)) {
                throw new Exception('No codes were generated');
            }
            
            // Return success response with enhanced code data
            wp_send_json_success(array(
                'message' => sprintf(
                    _n('%d code generated successfully', '%d codes generated successfully', count($generated_codes), 'registration-codes'),
                    count($generated_codes)
                ),
                'codes' => $generated_codes,
                'form_type' => $form_type
            ));
            
        } catch (Exception $e) {
            // Something went wrong, roll back the transaction
            $wpdb->query('ROLLBACK');
            error_log('Error in ajax_generate_codes: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Failed to generate codes: ', 'registration-codes') . $e->getMessage()
            ));
        }
    }





    /**
     * AJAX handler for loading tab content
     */
    public function ajax_load_tab_content() {
        // Set content type to JSON
        header('Content-Type: application/json');
        
        // Debug: Log the incoming request
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX Request: ' . print_r($_REQUEST, true));
        }
        
        // Check if user is logged in and has permission
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            return;
        }

        // Verify nonce matches one localized in admin.js
        if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'registration_codes_nonce' ) ) {
            $error = 'Security check failed. Invalid or missing nonce.';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Nonce verification failed. Nonce: ' . (isset($_POST['_ajax_nonce']) ? $_POST['_ajax_nonce'] : 'not set'));
                error_log('Current user ID: ' . get_current_user_id());
                error_log('User capabilities: ' . print_r(wp_get_current_user()->allcaps, true));
            }
            wp_send_json_error( array( 'message' => __( $error, 'registration-codes' ) ) );
            exit;
        }

        // Capability check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions', 'registration-codes' ) ) );
        }

        // Determine which tab to load
        $tab = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : 'manage';
        
        // Ensure we have a valid tab
        if (!in_array($tab, array('manage', 'generate', 'import'))) {
            $tab = 'manage';
        }

        ob_start();

        // Define the base template directory
        $template_dir = get_stylesheet_directory() . '/includes/registration/templates/';
        
        // Map tabs to their template files
        $template_map = array(
            'manage'   => 'manage-codes.php',
            'generate' => 'generate-codes.php',
            'import'   => 'import-export.php',
        );

        $template_path = $template_dir . $template_map[$tab];

        // Make sure $registration_codes is available in the template
        global $registration_codes;
        if (!isset($registration_codes)) {
            $registration_codes = Registration_Codes::get_instance();
        }

        if (file_exists($template_path)) {
            // Include the template file with error suppression to catch any fatal errors
            try {
                include $template_path;
            } catch (Exception $e) {
                error_log('Error loading template: ' . $e->getMessage());
                echo '<div class="notice notice-error"><p>';
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo 'Error in template: ' . esc_html($e->getMessage());
                } else {
                    _e('Error loading tab content', 'registration-codes');
                }
                echo '</p></div>';
            }
        } else {
            // If the template file doesn't exist, load default content
            echo '<div class="notice notice-error"><p>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo sprintf(
                    __('Template file not found: %s', 'registration-codes'),
                    '<code>' . esc_html($template_path) . '</code>'
                );
            } else {
                _e('Error loading tab content', 'registration-codes');
            }
            echo '</p></div>';
        }

        $content = ob_get_clean();
        
        if (empty($content)) {
            $content = '<div class="notice notice-error"><p>' . __('No content was loaded for this tab.', 'registration-codes') . '</p></div>';
        }

        wp_send_json_success(array('content' => $content));
    }

/**
 * Process registration code on user registration
 */
public function process_registration_code($user_id) {
    // Simplified implementation for MVP
    return true;
}

/**
 * Handle form submissions
 */
public function handle_form_submissions() {
    // Simplified implementation for MVP
    return true;
}

/**
 * Render the admin page
 */
public function render_admin_page() {
    // Get the current tab
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manage';
        
    // Define tabs
    $tabs = array(
        'manage' => __('Manage Codes', 'registration-codes'),
        'generate' => __('Generate Codes', 'registration-codes'),
        'import' => __('Import/Export', 'registration-codes'),
    );
        
    // Include the admin page template
    $template_path = get_stylesheet_directory() . '/includes/registration/templates/admin-page.php';
        
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="error"><p>' . 
             sprintf(
                 __('Template file not found: %s', 'registration-codes'),
                 '<code>' . esc_html($template_path) . '</code>'
             ) . 
             '</p></div>';
    }
}

/**
 * Get registration codes with filters
 * 
 * @param string $group_filter Filter by group name
 * @param string $status_filter Filter by status (active/used)
 * @param string $role_filter Filter by role/study
 * @param int $per_page Number of items per page
 * @param int $offset Offset for pagination
 * @return array Array of registration codes
 */
public function get_codes($group_filter = '', $status_filter = '', $role_filter = '', $per_page = 20, $offset = 0) {
    global $wpdb;
        
    $where = array('1=1');
    $where_args = array();
        
    if (!empty($group_filter)) {
        $where[] = 'group_name = %s';
        $where_args[] = $group_filter;
    }
        
    if (!empty($role_filter)) {
        $where[] = 'role = %s';
        $where_args[] = $role_filter;
    }
        
    if ($status_filter === 'used') {
        $where[] = 'is_used = 1';
    } elseif ($status_filter === 'active') {
        $where[] = 'is_used = 0';
    }
        
    $where_clause = implode(' AND ', $where);
    $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d";
        
    $query_args = array_merge($where_args, array($per_page, $offset));
        
    $prepared_query = count($where_args) > 0 
        ? $wpdb->prepare($query, $query_args) 
        : $wpdb->prepare($query, $per_page, $offset);
            
    return $wpdb->get_results($prepared_query, ARRAY_A);
}
    
/**
 * Count total number of codes with optional filters
 * 
 * @param string $group Optional group name filter
 * @param string $status Optional status filter
 * @param string $role Optional role/study filter
 * @return int Number of codes matching the filters
 */
public function count_codes($group = '', $status = '', $role = '') {
    global $wpdb;
        
    $where = array();
    $values = array();
        
    if (!empty($group)) {
        $where[] = 'group_name = %s';
        $values[] = $group;
    }
        
    if (!empty($role)) {
        $where[] = 'role = %s';
        $values[] = $role;
    }
        
    if (!empty($status)) {
        if ($status === 'used') {
            $where[] = 'is_used = 1';
        } elseif ($status === 'active') {
            $where[] = 'is_used = 0';
        }
    }
        
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT COUNT(*) FROM {$this->table_name} $where_clause";
        
    if (!empty($values)) {
        $query = $wpdb->prepare($query, $values);
    }
        
    return (int) $wpdb->get_var($query);
}
    
/**
 * Get all unique group names from the registration codes
 * 
 * @return array Array of group names
 */
public function get_groups() {
    global $wpdb;
        
    $query = "SELECT DISTINCT group_name 
             FROM {$this->table_name} 
             WHERE group_name IS NOT NULL AND group_name != '' 
             ORDER BY group_name ASC";
        
    $groups = $wpdb->get_col($query);
        
    return is_array($groups) ? $groups : array();
}

    /**
     * AJAX handler for exporting registration codes
     */
    public function ajax_export_codes() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'registration_codes_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'), 403);
            return;
        }
        
        // Get export parameters
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        $group = isset($_POST['export_group']) ? sanitize_text_field($_POST['export_group']) : '';
        $status = isset($_POST['export_status']) ? sanitize_text_field($_POST['export_status']) : '';
        $fields = isset($_POST['export_fields']) ? (array) $_POST['export_fields'] : array('code', 'role', 'group_name', 'is_used');
        
        // Sanitize fields
        $allowed_fields = array('code', 'role', 'group_name', 'is_used', 'used_by', 'used_at', 'created_at', 'created_by');
        $fields = array_intersect($fields, $allowed_fields);
        
        if (empty($fields)) {
            $fields = array('code', 'role', 'group_name', 'is_used');
        }
        
        // Get the codes with filters (no pagination for export)
        $codes = $this->get_codes($group, $status, '', 0, 0);
        
        if (empty($codes)) {
            wp_send_json_error(array('message' => 'No codes found matching the selected criteria'));
            return;
        }
        
        // Prepare data for export
        $export_data = array();
        $headers = array();
        
        // Map field names to display names
        $field_labels = array(
            'code' => 'Code',
            'role' => 'Role',
            'group_name' => 'Class',
            'is_used' => 'Status',
            'used_by' => 'Used By',
            'used_at' => 'Used At',
            'created_at' => 'Created At',
            'created_by' => 'Created By'
        );
        
        // Set headers based on selected fields
        foreach ($fields as $field) {
            if (isset($field_labels[$field])) {
                $headers[] = $field_labels[$field];
            }
        }
        
        // Prepare data rows
        foreach ($codes as $code) {
            $row = array();
            
            foreach ($fields as $field) {
                switch ($field) {
                    case 'is_used':
                        $row[] = !empty($code['is_used']) ? 'Used' : 'Available';
                        break;
                    case 'used_at':
                    case 'created_at':
                        $row[] = !empty($code[$field]) ? date('Y-m-d H:i:s', strtotime($code[$field])) : '';
                        break;
                    default:
                        $row[] = isset($code[$field]) ? $code[$field] : '';
                        break;
                }
            }
            
            $export_data[] = $row;
        }
        
        // Generate filename
        $filename = 'registration-codes-' . date('Y-m-d');
        
        // Output the file
        if ($format === 'json') {
            // Prepare data for JSON export
            $json_data = array();
            
            // Add headers as first row
            $json_data[] = array_combine($headers, $headers);
            
            // Add data rows
            foreach ($export_data as $row) {
                $json_data[] = array_combine($headers, $row);
            }
            
            // Set headers for JSON download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo json_encode($json_data, JSON_PRETTY_PRINT);
        } else {
            // Default to CSV
            $delimiter = $format === 'excel' ? ';' : ',';
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            if ($format === 'excel') {
                fputs($output, "\xEF\xBB\xBF");
            }
            
            // Add headers
            fputcsv($output, $headers, $delimiter);
            
            // Add data rows
            foreach ($export_data as $row) {
                fputcsv($output, $row, $delimiter);
            }
            
            fclose($output);
        }
        
        // Terminate to prevent WordPress from sending additional output
        wp_die();
    }
} // end class Registration_Codes

// Initialize the registration codes system
function registration_codes_init() {
    return Registration_Codes::get_instance();
}
add_action('plugins_loaded', 'registration_codes_init');
