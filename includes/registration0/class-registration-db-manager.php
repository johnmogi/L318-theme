<?php
/**
 * Registration Database Manager
 * Handles all database operations for the registration system
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Registration_DB_Manager
 * 
 * Manages database operations for the registration system with proper OOP design
 * Acts as the data layer for all registration-related functionality
 */
class Registration_DB_Manager {
    /**
     * @var Registration_DB_Manager Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var string Database table name
     */
    private $table_name;
    
    /**
     * @var string Current database version
     */
    private $version = '1.0.0';

    /**
     * Get singleton instance
     * 
     * @return Registration_DB_Manager
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'edc_registration_codes';
        
        // Verify table on admin page load
        if (is_admin()) {
            add_action('admin_init', [$this, 'verify_table_exists']);
        }
    }
    
    /**
     * Get the registration codes table name
     * 
     * @return string Table name with prefix
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Check if tables need to be created and create them if necessary
     * This is a public wrapper for verify_table_exists to be used on plugin activation
     */
    public function maybe_create_tables() {
        return $this->verify_table_exists();
    }
    
    /**
     * Create or update the registration codes table
     * 
     * @return bool Success status
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
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
        
        dbDelta($sql);
        
        // Update version in the database
        update_option('registration_codes_db_version', $this->version);
        
        return true;
    }

    /**
     * Verify table exists and is up to date
     */
    public function verify_table_exists() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            $this->create_table();
        } else {
            // Table exists, check if we need to update it
            $current_version = get_option('registration_codes_db_version', '0');
            if (version_compare($current_version, $this->version, '<')) {
                $this->update_table_schema();
                update_option('registration_codes_db_version', $this->version);
            }
        }
    }
    
    /**
     * Update table schema if needed
     * 
     * @return bool Success status
     */
    public function update_table_schema() {
        global $wpdb;
        
        // Get current columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
        $column_names = wp_list_pluck($columns, 'Field');
        
        // Required columns with their definitions
        $required_columns = [
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
        ];
        
        // Add missing columns
        $altered = false;
        foreach ($required_columns as $column_name => $definition) {
            if (!in_array($column_name, $column_names)) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN {$column_name} {$definition}");
                $altered = true;
            }
        }
        
        // Required indexes
        $required_indexes = [
            'PRIMARY' => 'PRIMARY KEY (id)',
            'code' => 'UNIQUE KEY code (code)',
            'group_name' => 'KEY group_name (group_name)',
            'course_id' => 'KEY course_id (course_id)',
            'expiry_date' => 'KEY expiry_date (expiry_date)',
            'is_used' => 'KEY is_used (is_used)'
        ];
        
        // Get current indexes
        $current_indexes = $wpdb->get_results("SHOW INDEX FROM {$this->table_name}", ARRAY_A);
        $index_names = array_unique(wp_list_pluck($current_indexes, 'Key_name'));
        
        // Add missing indexes
        foreach ($required_indexes as $index_name => $definition) {
            if (!in_array($index_name, $index_names)) {
                $wpdb->query("ALTER TABLE {$this->table_name} ADD {$definition}");
                $altered = true;
            }
        }
        
        return $altered;
    }

    /**
     * Create a new registration code
     * 
     * @param array $code_data Array of code data
     * @return int|false ID of inserted code or false on failure
     */
    public function create_code($code_data) {
        global $wpdb;
        
        // Ensure required fields are present
        $required_fields = ['code', 'created_by'];
        foreach ($required_fields as $field) {
            if (!isset($code_data[$field])) {
                return false;
            }
        }
        
        // Set default values for optional fields
        $defaults = [
            'role' => 'subscriber',
            'group_name' => '',
            'course_id' => null,
            'max_uses' => 1,
            'used_count' => 0,
            'expiry_date' => null,
            'is_used' => 0,
            'used_by' => null,
            'used_at' => null,
        ];
        
        $code_data = wp_parse_args($code_data, $defaults);
        
        // Insert the code
        $result = $wpdb->insert(
            $this->table_name,
            $code_data,
            [
                '%s', // code
                '%s', // role
                '%s', // group_name
                '%d', // course_id
                '%d', // max_uses
                '%d', // used_count
                '%s', // expiry_date
                '%d', // is_used
                '%d', // used_by
                '%s', // used_at
                '%s', // created_at (automatically set by DB)
                '%d'  // created_by
            ]
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get registration code by code string
     * 
     * @param string $code The registration code to find
     * @return object|null Code object or null if not found
     */
    public function get_code_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE code = %s",
                $code
            )
        );
    }
    
    /**
     * Get registration code by ID
     * 
     * @param int $id The code ID
     * @return object|null Code object or null if not found
     */
    public function get_code_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Get all codes with optional filtering
     * 
     * @param array $args Query arguments
     * @return array Array of code objects
     */
    public function get_codes($args = []) {
        global $wpdb;
        
        $defaults = [
            'group_name' => '',
            'course_id' => 0,
            'is_used' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = [];
        $where_values = [];
        
        if (!empty($args['group_name'])) {
            $where[] = 'group_name = %s';
            $where_values[] = $args['group_name'];
        }
        
        if (!empty($args['course_id'])) {
            $where[] = 'course_id = %d';
            $where_values[] = $args['course_id'];
        }
        
        if (isset($args['is_used']) && is_numeric($args['is_used'])) {
            $where[] = 'is_used = %d';
            $where_values[] = $args['is_used'];
        }
        
        // Build the query
        $query = "SELECT * FROM {$this->table_name}";
        
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Add orderby and limit
        $valid_orderby = ['id', 'code', 'created_at', 'expiry_date'];
        $orderby = in_array($args['orderby'], $valid_orderby) ? $args['orderby'] : 'id';
        
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY {$orderby} {$order}";
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        
        // Prepare the query with all values
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, ...$where_values);
        }
        
        // Execute the query
        return $wpdb->get_results($query);
    }
    
    /**
     * Count codes with optional filtering
     * 
     * @param array $args Query arguments
     * @return int Count of matching codes
     */
    public function count_codes($args = []) {
        global $wpdb;
        
        $defaults = [
            'group_name' => '',
            'course_id' => 0,
            'is_used' => null,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = [];
        $where_values = [];
        
        if (!empty($args['group_name'])) {
            $where[] = 'group_name = %s';
            $where_values[] = $args['group_name'];
        }
        
        if (!empty($args['course_id'])) {
            $where[] = 'course_id = %d';
            $where_values[] = $args['course_id'];
        }
        
        if (isset($args['is_used']) && is_numeric($args['is_used'])) {
            $where[] = 'is_used = %d';
            $where_values[] = $args['is_used'];
        }
        
        // Build the query
        $query = "SELECT COUNT(*) FROM {$this->table_name}";
        
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Prepare the query with all values
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, ...$where_values);
        }
        
        // Execute the query
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Update a registration code
     * 
     * @param int $id Code ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_code($id, $data) {
        global $wpdb;
        
        // Remove disallowed fields
        $disallowed = ['id', 'code', 'created_at', 'created_by'];
        foreach ($disallowed as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        
        if (empty($data)) {
            return false;
        }
        
        // Define format for each field
        $formats = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'role':
                case 'group_name':
                case 'expiry_date':
                case 'used_at':
                    $formats[] = '%s';
                    break;
                case 'course_id':
                case 'max_uses':
                case 'used_count':
                case 'is_used':
                case 'used_by':
                    $formats[] = '%d';
                    break;
                default:
                    $formats[] = '%s';
            }
        }
        
        // Update the code
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            $formats,
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a registration code
     * 
     * @param int $id Code ID
     * @return bool Success status
     */
    public function delete_code($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
    }
    
    /**
     * Mark a code as used
     * 
     * @param string $code Code string
     * @param int $user_id User ID who used the code
     * @return bool Success status
     */
    public function mark_code_used($code, $user_id) {
        global $wpdb;
        
        $code_data = $this->get_code_by_code($code);
        if (!$code_data) {
            return false;
        }
        
        // If the code is multi-use, increment the counter instead of marking as fully used
        if ($code_data->max_uses > 1 && $code_data->used_count < $code_data->max_uses) {
            $used_count = $code_data->used_count + 1;
            $is_used = ($used_count >= $code_data->max_uses) ? 1 : 0;
            
            return $wpdb->update(
                $this->table_name,
                [
                    'used_count' => $used_count,
                    'is_used' => $is_used,
                    'used_by' => $user_id,
                    'used_at' => current_time('mysql')
                ],
                ['code' => $code],
                ['%d', '%d', '%d', '%s'],
                ['%s']
            ) !== false;
        } else {
            // Single-use code or all uses are exhausted
            return $wpdb->update(
                $this->table_name,
                [
                    'is_used' => 1,
                    'used_by' => $user_id,
                    'used_at' => current_time('mysql'),
                    'used_count' => 1
                ],
                ['code' => $code],
                ['%d', '%d', '%s', '%d'],
                ['%s']
            ) !== false;
        }
    }
    
    /**
     * Generate a unique registration code
     * 
     * @param int $length Code length (default: 8)
     * @return string Unique registration code
     */
    public function generate_unique_code($length = 8) {
        global $wpdb;
        
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max_attempts = 10;
        
        for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
            $code = '';
            $char_length = strlen($characters) - 1;
            
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[mt_rand(0, $char_length)];
            }
            
            // Check if code already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE code = %s",
                    $code
                )
            );
            
            if ($exists == 0) {
                return $code;
            }
        }
        
        // If we've reached here, we couldn't generate a unique code
        return false;
    }
    
    /**
     * Validate a registration code
     * 
     * @param string $code Code to validate
     * @return array|false Validation result or false if invalid
     */
    public function validate_code($code) {
        global $wpdb;
        
        $code_data = $this->get_code_by_code($code);
        
        if (!$code_data) {
            return ['valid' => false, 'message' => 'קוד הרשמה לא קיים'];
        }
        
        // Check if code is used (for single-use codes)
        if ($code_data->max_uses <= 1 && $code_data->is_used) {
            return ['valid' => false, 'message' => 'קוד הרשמה כבר בשימוש'];
        }
        
        // Check for multi-use codes
        if ($code_data->max_uses > 1 && $code_data->used_count >= $code_data->max_uses) {
            return ['valid' => false, 'message' => 'קוד הרשמה נוצל את מספר הפעמים המקסימלי'];
        }
        
        // Check expiry date
        if (!empty($code_data->expiry_date) && strtotime($code_data->expiry_date) < time()) {
            return ['valid' => false, 'message' => 'קוד הרשמה פג תוקף'];
        }
        
        return [
            'valid' => true,
            'code' => $code_data,
            'message' => 'קוד תקין'
        ];
    }
    
    /**
     * Force drop and recreate the registration codes table
     * 
     * @return bool Success status
     */
    public function force_recreate_table() {
        global $wpdb;
        
        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        
        // Recreate the table
        return $this->create_table();
    }
}
