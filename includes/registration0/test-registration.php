<?php
/**
 * Registration Test Script
 * 
 * This script tests the registration code generation functionality
 * using the new OOP implementation.
 * 
 * Usage: Load this file in the browser with WordPress loaded.
 */

// Exit if accessed directly outside WordPress
if (!defined('ABSPATH')) {
    // First try the standard path
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    
    // If not found, try locating wp-load.php in different common locations
    if (!file_exists($wp_load_path)) {
        // Try app/public structure (Local by Flywheel typical setup)
        $wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';
    }
    
    // If still not found, try one more directory up
    if (!file_exists($wp_load_path)) {
        $wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/wp-load.php';
    }
    
    // Try another common path for WordPress installations
    if (!file_exists($wp_load_path)) {
        // Go to document root and back
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $wp_load_path = $document_root . '/wp-load.php';
    }
    
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. Cannot run tests. Please access this script through WordPress admin or adjust the path.');
    }
}

// Only allow admin users to run this script
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this test.');
}

// Load our registration system files
require_once(dirname(__FILE__) . '/class-registration-db-manager.php');
require_once(dirname(__FILE__) . '/class-registration-code.php');
require_once(dirname(__FILE__) . '/class-registration-service.php');

/**
 * Test Registration Code Generator
 */
class Registration_Tester {
    /**
     * @var Registration_Service
     */
    private $service;
    
    /**
     * @var Registration_DB_Manager
     */
    private $db_manager;
    
    /**
     * @var array Test results
     */
    private $results = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize service and DB manager
        $this->service = Registration_Service::get_instance();
        $this->db_manager = Registration_DB_Manager::get_instance();
        
        // Ensure tables exist
        $this->db_manager->maybe_create_tables();
        
        // Set up error handling
        set_error_handler(array($this, 'error_handler'));
    }
    
    /**
     * Run all tests
     */
    public function run_tests() {
        echo '<div class="wrap">';
        echo '<h1>Registration System Tests</h1>';
        
        // Run individual tests
        $this->test_db_connection();
        $this->test_table_exists();
        $this->test_generate_single_code();
        $this->test_generate_multiple_codes();
        $this->test_code_validation();
        $this->test_code_usage();
        
        // Display summary
        $this->display_results();
        
        echo '</div>';
    }
    
    /**
     * Test database connection
     */
    private function test_db_connection() {
        global $wpdb;
        
        try {
            // Test a simple query
            $result = $wpdb->get_var("SELECT 1");
            $this->record_result('Database Connection', $result === '1', 'Connected to database successfully');
        } catch (Exception $e) {
            $this->record_result('Database Connection', false, 'Failed to connect to database: ' . $e->getMessage());
        }
    }
    
    /**
     * Test if registration codes table exists
     */
    private function test_table_exists() {
        global $wpdb;
        
        try {
            $table_name = $this->db_manager->get_table_name();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            
            if (!$table_exists) {
                // Try to create the table
                $this->db_manager->create_tables();
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            }
            
            $this->record_result('Registration Table', $table_exists, 'Table ' . ($table_exists ? 'exists' : 'could not be created'));
        } catch (Exception $e) {
            $this->record_result('Registration Table', false, 'Error checking table: ' . $e->getMessage());
        }
    }
    
    /**
     * Test generating a single code
     */
    private function test_generate_single_code() {
        try {
            // Generate a single code
            $codes = $this->service->generate_codes([
                'count' => 1,
                'role' => 'subscriber',
                'group_name' => 'test_group',
                'course_id' => null,
                'max_uses' => 1,
                'expiry_date' => null
            ]);
            
            $success = !empty($codes) && is_array($codes) && count($codes) === 1;
            $message = $success ? 'Generated code: ' . $codes[0]->get_code() : 'Failed to generate code';
            
            $this->record_result('Generate Single Code', $success, $message);
            
            // Return the code for further testing
            return $success ? $codes[0] : null;
        } catch (Exception $e) {
            $this->record_result('Generate Single Code', false, 'Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Test generating multiple codes
     */
    private function test_generate_multiple_codes() {
        try {
            // Generate 5 codes
            $codes = $this->service->generate_codes([
                'count' => 5,
                'role' => 'subscriber',
                'group_name' => 'test_multiple',
                'course_id' => null,
                'max_uses' => 1,
                'expiry_date' => date('Y-m-d', strtotime('+30 days'))
            ]);
            
            $success = !empty($codes) && is_array($codes) && count($codes) === 5;
            $message = $success ? 'Generated 5 codes successfully' : 'Failed to generate multiple codes';
            
            if ($success) {
                $message .= '<br>Codes: ';
                foreach ($codes as $code) {
                    $message .= $code->get_code() . ', ';
                }
                $message = rtrim($message, ', ');
            }
            
            $this->record_result('Generate Multiple Codes', $success, $message);
            
            // Return the codes for further testing
            return $success ? $codes : null;
        } catch (Exception $e) {
            $this->record_result('Generate Multiple Codes', false, 'Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Test code validation
     */
    private function test_code_validation() {
        try {
            // Generate a test code first
            $test_code = $this->test_generate_single_code();
            
            if (!$test_code) {
                $this->record_result('Code Validation', false, 'Could not generate a code for validation testing');
                return;
            }
            
            // Test validating the code
            $code_value = $test_code->get_code();
            $validation = $this->service->validate_code($code_value);
            
            $success = $validation['is_valid'];
            $message = $success ? 'Code validated successfully' : 'Code validation failed: ' . $validation['message'];
            
            $this->record_result('Code Validation', $success, $message);
            
            // Test validating a non-existent code
            $fake_code = 'XXXXXXXX';
            $fake_validation = $this->service->validate_code($fake_code);
            
            $fake_success = !$fake_validation['is_valid'];
            $fake_message = $fake_success ? 'Successfully rejected invalid code' : 'Failed to reject invalid code';
            
            $this->record_result('Invalid Code Rejection', $fake_success, $fake_message);
        } catch (Exception $e) {
            $this->record_result('Code Validation', false, 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Test code usage
     */
    private function test_code_usage() {
        try {
            // Generate a test code first
            $test_code = $this->test_generate_single_code();
            
            if (!$test_code) {
                $this->record_result('Code Usage', false, 'Could not generate a code for usage testing');
                return;
            }
            
            // Test marking the code as used
            $code_value = $test_code->get_code();
            $user_id = get_current_user_id();
            
            $usage_result = $this->service->mark_code_used($code_value, $user_id);
            
            $success = $usage_result;
            $message = $success ? 'Code marked as used successfully' : 'Failed to mark code as used';
            
            $this->record_result('Code Usage', $success, $message);
            
            // Test validating the used code (should fail)
            $validation = $this->service->validate_code($code_value);
            
            $reuse_success = !$validation['is_valid'];
            $reuse_message = $reuse_success ? 'Successfully rejected used code' : 'Failed to reject used code';
            
            $this->record_result('Used Code Rejection', $reuse_success, $reuse_message);
        } catch (Exception $e) {
            $this->record_result('Code Usage', false, 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Record test result
     * 
     * @param string $test_name Test name
     * @param bool $success Whether the test succeeded
     * @param string $message Additional message
     */
    private function record_result($test_name, $success, $message = '') {
        $this->results[] = array(
            'name' => $test_name,
            'success' => $success,
            'message' => $message
        );
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        $total = count($this->results);
        $passed = 0;
        
        foreach ($this->results as $result) {
            if ($result['success']) {
                $passed++;
            }
        }
        
        echo '<h2>Test Results: ' . $passed . '/' . $total . ' tests passed</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($this->results as $result) {
            $status = $result['success'] ? 'Pass' : 'Fail';
            $status_class = $result['success'] ? 'pass' : 'fail';
            
            echo '<tr>';
            echo '<td>' . esc_html($result['name']) . '</td>';
            echo '<td><span class="status-' . $status_class . '">' . esc_html($status) . '</span></td>';
            echo '<td>' . wp_kses_post($result['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<style>
            .status-pass { color: green; font-weight: bold; }
            .status-fail { color: red; font-weight: bold; }
        </style>';
    }
    
    /**
     * Custom error handler
     */
    public function error_handler($errno, $errstr, $errfile, $errline) {
        $this->record_result('PHP Error', false, "Error ($errno): $errstr in $errfile on line $errline");
        return true;
    }
}

// Run the tests
$tester = new Registration_Tester();
$tester->run_tests();
