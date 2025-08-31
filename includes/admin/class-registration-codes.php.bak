<?php
/**
 * Registration Codes Management
 *
 * Handles the creation, storage, and management of registration codes
 * for the two-step registration process.
 *
 * @package Hello_Child_Theme
 * @subpackage Admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CCR_Registration_Codes {

    /**
     * Database table name
     */
    private $table_name;

    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'registration_codes';
        
        // Initialize hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_generate_registration_codes', array($this, 'ajax_generate_codes'));
        add_action('wp_ajax_export_registration_codes', array($this, 'ajax_export_codes'));
        add_action('wp_ajax_import_registration_codes', array($this, 'ajax_import_codes'));
        add_action('wp_ajax_delete_group', array($this, 'ajax_delete_group'));
    }

    /**
     * Create the database table on plugin activation
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(32) NOT NULL,
            group_name varchar(100) DEFAULT '',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            used_by bigint(20) DEFAULT NULL,
            used_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Registration Codes', 'hello-child'),
            __('Registration Codes', 'hello-child'),
            'manage_options',
            'registration-codes',
            array($this, 'render_admin_page'),
            'dashicons-tickets',
            30
        );
        
        // Add submenu for teachers with lower capabilities
        add_submenu_page(
            'registration-codes',
            __('Teacher Dashboard', 'hello-child'),
            __('Teacher Dashboard', 'hello-child'),
            'edit_posts', // Lower capability for teachers
            'teacher-dashboard',
            array($this, 'render_teacher_dashboard')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_registration-codes' !== $hook && 'registration-codes_page_teacher-dashboard' !== $hook) {
            return;
        }
        
        wp_enqueue_style('registration-codes-admin', get_stylesheet_directory_uri() . '/includes/admin/css/admin.css', array(), filemtime(get_stylesheet_directory() . '/includes/admin/css/admin.css'));
        
        wp_enqueue_script('registration-codes-admin', get_stylesheet_directory_uri() . '/includes/admin/js/admin.js', array('jquery'), filemtime(get_stylesheet_directory() . '/includes/admin/js/admin.js'), true);
        
        wp_localize_script('registration-codes-admin', 'ccrAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccr_admin_nonce'),
            'generating' => __('Generating codes...', 'hello-child'),
            'success' => __('Codes generated successfully!', 'hello-child'),
            'error' => __('Error generating codes. Please try again.', 'hello-child')
        ));
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        // Check if table exists, create if not
        $this->maybe_create_table();
        
        // Check if we're in the import logs tab
        if (isset($_GET['tab']) && $_GET['tab'] === 'import-logs') {
            $this->render_import_logs_tab();
            return;
        }
        
        // Get existing groups for dropdown
        $groups = $this->get_groups();
        
        // Get codes with pagination
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        $filter_group = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
        $filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $codes = $this->get_codes($offset, $per_page, $filter_group, $filter_status);
        $total_codes = $this->count_codes($filter_group, $filter_status);
        $total_pages = ceil($total_codes / $per_page);
        
        // Include the view
        include(get_stylesheet_directory() . '/includes/admin/views/registration-codes.php');
    }
    
    /**
     * Render the import logs tab
     */
    private function render_import_logs_tab() {
        // Get import logs with pagination
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        $logs = $this->get_import_logs($offset, $per_page);
        $total_logs = $this->count_import_logs();
        $total_pages = ceil($total_logs / $per_page);
        
        // Include the view
        include(get_stylesheet_directory() . '/includes/admin/views/import-logs.php');
    }

    /**
     * Render the teacher dashboard
     */
    public function render_teacher_dashboard() {
        // Check if table exists
        $this->maybe_create_table();
        
        // Get teacher's groups
        $current_user_id = get_current_user_id();
        $groups = $this->get_teacher_groups($current_user_id);
        
        // Get codes with pagination
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        
        $filter_group = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
        
        $codes = $this->get_teacher_codes($current_user_id, $offset, $per_page, $filter_group);
        $total_codes = $this->count_teacher_codes($current_user_id, $filter_group);
        $total_pages = ceil($total_codes / $per_page);
        
        // Include the view
        include(get_stylesheet_directory() . '/includes/admin/views/teacher-dashboard.php');
    }

    /**
     * Check if table exists and create if not
     */
    private function maybe_create_table() {
        global $wpdb;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $this->create_table();
        }
    }

    /**
     * Generate unique registration codes
     */
    public function generate_codes($count, $group_name = '') {
        global $wpdb;
        
        $codes = array();
        $current_user_id = get_current_user_id();
        $current_time = current_time('mysql');
        
        for ($i = 0; $i < $count; $i++) {
            // Generate a unique code
            do {
                $code = $this->generate_unique_code();
            } while ($this->code_exists($code));
            
            // Insert into database
            $wpdb->insert(
                $this->table_name,
                array(
                    'code' => $code,
                    'group_name' => $group_name,
                    'created_by' => $current_user_id,
                    'created_at' => $current_time,
                    'status' => 'active'
                ),
                array('%s', '%s', '%d', '%s', '%s')
            );
            
            if ($wpdb->insert_id) {
                $codes[] = $code;
            }
        }
        
        return $codes;
    }

    /**
     * Generate a unique code
     */
    private function generate_unique_code() {
        $prefix = 'LCH';
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return $prefix . $random;
    }

    /**
     * Check if code already exists
     */
    private function code_exists($code) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        return $count > 0;
    }

    /**
     * Get all unique group names
     */
    public function get_groups() {
        global $wpdb;
        
        $groups = $wpdb->get_col("
            SELECT DISTINCT group_name 
            FROM {$this->table_name} 
            WHERE group_name != '' 
            ORDER BY group_name ASC
        ");
        
        return $groups;
    }

    /**
     * Get teacher's groups
     */
    public function get_teacher_groups($teacher_id) {
        global $wpdb;
        
        $groups = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT group_name 
            FROM {$this->table_name} 
            WHERE created_by = %d AND group_name != '' 
            ORDER BY group_name ASC
        ", $teacher_id));
        
        return $groups;
    }

    /**
     * Get codes with pagination and filters
     */
    public function get_codes($offset = 0, $limit = 20, $group = '', $status = '') {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $sql_params = array();
        
        if (!empty($group)) {
            $sql .= " AND group_name = %s";
            $sql_params[] = $group;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $sql_params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d, %d";
        $sql_params[] = $offset;
        $sql_params[] = $limit;
        
        if (!empty($sql_params)) {
            $sql = $wpdb->prepare($sql, $sql_params);
        }
        
        return $wpdb->get_results($sql);
    }

    /**
     * Count total codes with filters
     */
    public function count_codes($group = '', $status = '') {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        $sql_params = array();
        
        if (!empty($group)) {
            $sql .= " AND group_name = %s";
            $sql_params[] = $group;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $sql_params[] = $status;
        }
        
        if (!empty($sql_params)) {
            $sql = $wpdb->prepare($sql, $sql_params);
        }
        
        return $wpdb->get_var($sql);
    }

    /**
     * Get teacher's codes with pagination and filters
     */
    public function get_teacher_codes($teacher_id, $offset = 0, $limit = 20, $group = '') {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE created_by = %d
        ", $teacher_id);
        
        if (!empty($group)) {
            $sql .= $wpdb->prepare(" AND group_name = %s", $group);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . intval($offset) . ", " . intval($limit);
        
        return $wpdb->get_results($sql);
    }

    /**
     * Count teacher's total codes with filters
     */
    public function count_teacher_codes($teacher_id, $group = '') {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT COUNT(*) FROM {$this->table_name} 
            WHERE created_by = %d
        ", $teacher_id);
        
        if (!empty($group)) {
            $sql .= $wpdb->prepare(" AND group_name = %s", $group);
        }
        
        return $wpdb->get_var($sql);
    }

    /**
     * AJAX handler for generating codes
     */
    public function ajax_generate_codes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccr_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get and sanitize parameters
        $count = isset($_POST['count']) ? absint($_POST['count']) : 10;
        $group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        
        // Limit maximum codes per batch
        $count = min($count, 500);
        
        // Generate codes
        $codes = $this->generate_codes($count, $group);
        
        if (empty($codes)) {
            wp_send_json_error('Failed to generate codes');
        }
        
        wp_send_json_success(array(
            'count' => count($codes),
            'codes' => $codes,
            'group' => $group
        ));
    }

    /**
     * AJAX handler for exporting codes
     */
    public function ajax_export_codes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccr_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get and sanitize parameters
        $group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Get all codes for export (no pagination)
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name} WHERE 1=1";
        $sql_params = array();
        
        if (!empty($group)) {
            $sql .= " AND group_name = %s";
            $sql_params[] = $group;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $sql_params[] = $status;
        }
        
        if (!current_user_can('manage_options')) {
            // Teachers can only export their own codes
            $sql .= " AND created_by = %d";
            $sql_params[] = get_current_user_id();
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($sql_params)) {
            $sql = $wpdb->prepare($sql, $sql_params);
        }
        
        $codes = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($codes)) {
            wp_send_json_error('No codes to export');
        }
        
        // Format for CSV
        $csv_data = array();
        $csv_data[] = array('Code', 'Group', 'Created By', 'Created At', 'Status', 'Used By', 'Used At');
        
        foreach ($codes as $code) {
            $created_by = get_userdata($code['created_by']);
            $created_by_name = $created_by ? $created_by->display_name : 'Unknown';
            
            $used_by = !empty($code['used_by']) ? get_userdata($code['used_by']) : null;
            $used_by_name = $used_by ? $used_by->display_name : '';
            
            $csv_data[] = array(
                $code['code'],
                $code['group_name'],
                $created_by_name,
                $code['created_at'],
                $code['status'],
                $used_by_name,
                $code['used_at'] ?: ''
            );
        }
        
        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => 'registration-codes-' . date('Y-m-d') . '.csv'
        ));
    }

    /**
     * AJAX handler for importing codes
     */
    public function ajax_import_codes() {
        // Prevent early PHP/Apache timeouts
        @ini_set('max_execution_time', 300);
        @set_time_limit(300);
        ignore_user_abort(true);
        
        // Buffer all output to prevent premature termination
        ob_start();
        
        // Add keep-alive headers for long requests
        if (!headers_sent()) {
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccr_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
        }
        
        $file = $_FILES['import_file']['tmp_name'];
        
        // Get total rows for progress tracking
        $total_rows = count(file($file));
        $chunk_size = 25;
        $memory_check_frequency = 10;
        $batch_count = 0;
        $imported = 0;
        $skipped = 0;
        $group_name = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        
        global $wpdb;
        $current_user_id = get_current_user_id();
        $current_time = current_time('mysql');
        
        // Create import log entry
        $import_id = $this->create_import_log($group_name);
        
        // Open the file
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('Could not open file');
        }
        
        // Read and skip header row
        $header = fgetcsv($handle);
        $row = 1; // Start counting after header
        
        // Process in optimized chunks
        while (!feof($handle)) {
            // Memory check every 10 chunks
            if ($batch_count % $memory_check_frequency === 0 && 
                memory_get_usage() > 100 * 1024 * 1024) {
                wp_send_json_error('Memory threshold exceeded');
            }
            
            // Process chunk with enhanced error handling
            $chunk = [];
            
            // Read chunk
            while (count($chunk) < $chunk_size && ($data = fgetcsv($handle)) !== false) {
                $row++;
                
                // Skip empty rows
                if (empty($data[0])) {
                    $skipped++;
                    continue;
                }
                
                $code = sanitize_text_field($data[0]);
                
                // Skip if code already exists
                if ($this->code_exists($code)) {
                    $skipped++;
                    continue;
                }
                
                // Use group from CSV if available
                $code_group = !empty($data[1]) ? sanitize_text_field($data[1]) : $group_name;
                
                $chunk[] = array(
                    'code' => $code,
                    'group_name' => $code_group,
                    'created_by' => $current_user_id,
                    'created_at' => $current_time,
                    'status' => 'active'
                );
            }
            
            // Bulk insert with transaction for performance
            $wpdb->query('START TRANSACTION');
            
            try {
                // Bulk insert the chunk
                if (!empty($chunk)) {
                    $values = [];
                    $placeholders = [];
                    $formats = [];
                    
                    foreach ($chunk as $item) {
                        $values = array_merge($values, array_values($item));
                        $placeholders[] = "(%s, %s, %d, %s, %s)";
                        $formats = array('%s', '%s', '%d', '%s', '%s');
                    }
                    
                    $query = "INSERT INTO {$this->table_name} (code, group_name, created_by, created_at, status) VALUES ".
                             implode(', ', $placeholders);
                    
                    $result = $wpdb->query($wpdb->prepare($query, $values));
                    
                    if ($result !== false) {
                        $imported += count($chunk);
                    } else {
                        $skipped += count($chunk);
                    }
                }
                
                $wpdb->query('COMMIT');
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                wp_send_json_error('Batch '.$batch_count.' failed: '.$e->getMessage());
            }
            
            // Free memory
            unset($chunk);
            
            // Update progress every chunk for large files
            $this->update_import_log($import_id, $imported, $skipped, $total_rows - 1);
            
            $batch_count++;
        }
        
        fclose($handle);
        
        // Final update
        $this->update_import_log($import_id, $imported, $skipped, $total_rows - 1);
        
        // Flush output before returning
        ob_end_flush();
        
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $total_rows - 1, // Subtract header row
            'import_id' => $import_id
        ));
    }

    /**
     * Create an import log entry
     */
    private function create_import_log($group_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_import_logs';
        
        // Create the table if it doesn't exist
        $this->maybe_create_import_log_table();
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'group_name' => $group_name,
                'user_id' => get_current_user_id(),
                'status' => 'processing',
                'started_at' => current_time('mysql'),
                'imported' => 0,
                'skipped' => 0,
                'total' => 0
            ),
            array('%s', '%d', '%s', '%s', '%d', '%d', '%d')
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Update an import log entry
     */
    private function update_import_log($import_id, $imported, $skipped, $total) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_import_logs';
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => $total
            ),
            array('id' => $import_id),
            array('%s', '%s', '%d', '%d', '%d'),
            array('%d')
        );
    }
    
    /**
     * Create the import log table if it doesn't exist
     */
    private function maybe_create_import_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_import_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                group_name varchar(100) DEFAULT '',
                user_id bigint(20) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'processing',
                started_at datetime NOT NULL,
                completed_at datetime DEFAULT NULL,
                imported int(11) DEFAULT 0,
                skipped int(11) DEFAULT 0,
                total int(11) DEFAULT 0,
                PRIMARY KEY  (id),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Get import logs with pagination
     */
    public function get_import_logs($offset = 0, $limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_import_logs';
        
        // Create the table if it doesn't exist
        $this->maybe_create_import_log_table();
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} ORDER BY started_at DESC LIMIT %d, %d",
            $offset,
            $limit
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Count total import logs
     */
    public function count_import_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_import_logs';
        
        // Create the table if it doesn't exist
        $this->maybe_create_import_log_table();
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }
    
    /**
     * Delete a group and all its codes
     */
    public function delete_group($group_name) {
        global $wpdb;
        
        if (empty($group_name)) {
            return false;
        }
        
        // Count codes in the group
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE group_name = %s",
            $group_name
        ));
        
        // Delete all codes in the group
        $result = $wpdb->delete(
            $this->table_name,
            array('group_name' => $group_name),
            array('%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $count;
    }
    
    /**
     * AJAX handler for deleting a group
     */
    public function ajax_delete_group() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccr_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get group name
        $group_name = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        
        if (empty($group_name)) {
            wp_send_json_error('No group specified');
        }
        
        // Delete the group
        $count = $this->delete_group($group_name);
        
        if ($count === false) {
            wp_send_json_error('Failed to delete group');
        }
        
        wp_send_json_success(array(
            'group' => $group_name,
            'deleted' => $count
        ));
    }
}

// Initialize the class
$ccr_registration_codes = new CCR_Registration_Codes();
