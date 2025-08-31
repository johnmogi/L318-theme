<?php
/**
 * Registration Flow Test Script
 * 
 * This script tests the complete registration flow using the OOP implementation,
 * including code validation, usage tracking, role assignment, and integration.
 * 
 * Usage: Load this file in the browser with WordPress loaded.
 */

// Exit if accessed directly outside WordPress
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress');
}

// Only allow admin users to run this script
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this test.');
}

// Load our registration system files if not already loaded
require_once(dirname(__FILE__) . '/class-registration-db-manager.php');
require_once(dirname(__FILE__) . '/class-registration-code.php');
require_once(dirname(__FILE__) . '/class-registration-service.php');

/**
 * Test Registration Flow
 */
class Registration_Flow_Tester {
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
     * @var string Test username prefix
     */
    private $username_prefix = 'test_user_';
    
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
     * Run all registration flow tests and return results array
     * 
     * @return array Test results
     */
    public function run_all_tests() {
        // Run individual tests
        $this->test_registration_with_valid_code();
        $this->test_role_assignment();
        $this->test_group_assignment();
        $this->test_course_enrollment();
        $this->test_registration_with_used_code();
        $this->test_registration_with_invalid_code();
        $this->test_wordpress_hooks();
        
        // Clean up test users and codes
        $this->cleanup();
        
        // Return results
        return $this->results;
    }
    
    /**
     * Run all registration flow tests with HTML output
     */
    public function run_tests() {
        echo '<div class="wrap">';
        echo '<h1>Registration Flow Tests</h1>';
        
        // Run all tests
        $this->run_all_tests();
        
        // Display summary
        $this->display_results();
        
        echo '</div>';
    }
    
    /**
     * Test registration with a valid code
     */
    private function test_registration_with_valid_code() {
        try {
            // Generate a test registration code
            $codes = $this->service->generate_codes([
                'count' => 1,
                'role' => 'subscriber',
                'group_name' => 'test_group_flow',
                'course_id' => null,
                'max_uses' => 1,
                'expiry_date' => null
            ]);
            
            if (empty($codes)) {
                $this->record_result('Registration with Valid Code', false, 'Failed to generate test code');
                return false;
            }
            
            $test_code = $codes[0];
            $code_string = $test_code->get_code();
            
            // Create a test user
            $username = $this->username_prefix . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the code
            $_POST['registration_code'] = $code_string;
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Registration with Valid Code', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Process the registration code (simulate the hook)
            $this->service->process_registration_code($user_id);
            
            // Check if code was marked as used
            $updated_code = $this->service->find_code($code_string);
            $code_used = $updated_code && $updated_code->is_used();
            
            // Check if user has the correct meta
            $stored_code = get_user_meta($user_id, 'registration_code', true);
            $meta_correct = $stored_code === $code_string;
            
            $success = $code_used && $meta_correct;
            $message = $success ? 
                'User registered successfully and code marked as used' : 
                'User registration issue: Code used status: ' . ($code_used ? 'Yes' : 'No') . ', Meta correct: ' . ($meta_correct ? 'Yes' : 'No');
            
            $this->record_result('Registration with Valid Code', $success, $message);
            
            return $success ? ['user_id' => $user_id, 'code' => $test_code] : false;
        } catch (Exception $e) {
            $this->record_result('Registration with Valid Code', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test role assignment based on code
     */
    private function test_role_assignment() {
        try {
            // Generate a test registration code with a specific role
            $test_role = 'editor';
            $codes = $this->service->generate_codes([
                'count' => 1,
                'role' => $test_role,
                'group_name' => 'test_role_group',
                'course_id' => null,
                'max_uses' => 1,
                'expiry_date' => null
            ]);
            
            if (empty($codes)) {
                $this->record_result('Role Assignment', false, 'Failed to generate test code');
                return false;
            }
            
            $test_code = $codes[0];
            $code_string = $test_code->get_code();
            
            // Create a test user
            $username = $this->username_prefix . 'role_' . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the code
            $_POST['registration_code'] = $code_string;
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Role Assignment', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Process the registration code (simulate the hook)
            $this->service->process_registration_code($user_id);
            
            // Check if user has the correct role
            $user = new WP_User($user_id);
            $has_role = in_array($test_role, $user->roles);
            
            $success = $has_role;
            $message = $success ? 
                "User assigned correct role ($test_role)" : 
                "Failed to assign role $test_role to user";
            
            $this->record_result('Role Assignment', $success, $message);
            
            return $success ? ['user_id' => $user_id, 'code' => $test_code] : false;
        } catch (Exception $e) {
            $this->record_result('Role Assignment', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test group assignment based on code
     */
    private function test_group_assignment() {
        try {
            // Generate a test registration code with a specific group
            $test_group = 'test_assignment_group_' . time();
            $codes = $this->service->generate_codes([
                'count' => 1,
                'role' => 'subscriber',
                'group_name' => $test_group,
                'course_id' => null,
                'max_uses' => 1,
                'expiry_date' => null
            ]);
            
            if (empty($codes)) {
                $this->record_result('Group Assignment', false, 'Failed to generate test code');
                return false;
            }
            
            $test_code = $codes[0];
            $code_string = $test_code->get_code();
            
            // Create a test user
            $username = $this->username_prefix . 'group_' . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the code
            $_POST['registration_code'] = $code_string;
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Group Assignment', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Process the registration code (simulate the hook)
            $this->service->process_registration_code($user_id);
            
            // Check if user is added to the group
            // This depends on how your group system is implemented
            // For demonstration, we'll check a hypothetical user meta
            $user_group = get_user_meta($user_id, 'user_group', true);
            $in_group = $user_group === $test_group;
            
            // Skip this test if group functionality is not implemented
            if (!method_exists($this->service, 'add_user_to_group')) {
                $this->record_result('Group Assignment', null, 'Group assignment functionality not implemented - test skipped');
                return null;
            }
            
            $success = $in_group;
            $message = $success ? 
                "User added to correct group ($test_group)" : 
                "Failed to add user to group $test_group";
            
            $this->record_result('Group Assignment', $success, $message);
            
            return $success ? ['user_id' => $user_id, 'code' => $test_code] : false;
        } catch (Exception $e) {
            $this->record_result('Group Assignment', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test course enrollment based on code
     */
    private function test_course_enrollment() {
        try {
            // Check if we have LMS plugins active
            $has_learndash = function_exists('ld_update_course_access');
            $has_tutor = function_exists('tutor_utils');
            
            if (!$has_learndash && !$has_tutor) {
                $this->record_result('Course Enrollment', null, 'No LMS plugin detected - test skipped');
                return null;
            }
            
            // Find a valid course ID if possible
            $course_id = $this->find_test_course_id();
            
            if (!$course_id) {
                $this->record_result('Course Enrollment', null, 'No courses found for testing - test skipped');
                return null;
            }
            
            // Generate a test registration code with the course ID
            $codes = $this->service->generate_codes([
                'count' => 1,
                'role' => 'subscriber',
                'group_name' => 'test_course_group',
                'course_id' => $course_id,
                'max_uses' => 1,
                'expiry_date' => null
            ]);
            
            if (empty($codes)) {
                $this->record_result('Course Enrollment', false, 'Failed to generate test code');
                return false;
            }
            
            $test_code = $codes[0];
            $code_string = $test_code->get_code();
            
            // Create a test user
            $username = $this->username_prefix . 'course_' . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the code
            $_POST['registration_code'] = $code_string;
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Course Enrollment', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Process the registration code (simulate the hook)
            $this->service->process_registration_code($user_id);
            
            // Check if user is enrolled in the course
            $enrolled = false;
            
            if ($has_learndash) {
                // Check LearnDash enrollment
                $enrolled = learndash_is_user_in_course($course_id, $user_id);
            } elseif ($has_tutor) {
                // Check Tutor LMS enrollment
                $enrolled = tutor_utils()->is_enrolled($course_id, $user_id);
            }
            
            $success = $enrolled;
            $message = $success ? 
                "User enrolled in course #$course_id successfully" : 
                "Failed to enroll user in course #$course_id";
            
            $this->record_result('Course Enrollment', $success, $message);
            
            return $success ? ['user_id' => $user_id, 'code' => $test_code] : false;
        } catch (Exception $e) {
            $this->record_result('Course Enrollment', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test registration with a used code
     */
    private function test_registration_with_used_code() {
        try {
            // First create a valid code and use it
            $result = $this->test_registration_with_valid_code();
            
            if (!$result) {
                $this->record_result('Registration with Used Code', false, 'Failed to set up test with a valid code first');
                return false;
            }
            
            $used_code = $result['code'];
            $code_string = $used_code->get_code();
            
            // Create a second test user
            $username = $this->username_prefix . 'used_' . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the used code
            $_POST['registration_code'] = $code_string;
            
            // First create the user without processing the code
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Registration with Used Code', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Validate the code before processing
            $validation = $this->service->validate_code($code_string);
            $code_rejected = !$validation['is_valid'];
            
            // Process the registration code anyway (simulate the hook)
            // This should not mark the code as used a second time if it's already used
            $this->service->process_registration_code($user_id);
            
            // The user should not have the registration code in meta
            $stored_code = get_user_meta($user_id, 'registration_code', true);
            $no_meta = empty($stored_code);
            
            // Success means the used code was rejected
            $success = $code_rejected && $no_meta;
            $message = $code_rejected ? 
                'Successfully rejected registration with a used code' : 
                'Failed to reject registration with a used code';
            
            $this->record_result('Registration with Used Code', $success, $message);
            
            return $success ? $user_id : false;
        } catch (Exception $e) {
            $this->record_result('Registration with Used Code', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test registration with an invalid code
     */
    private function test_registration_with_invalid_code() {
        try {
            // Create a non-existent code
            $invalid_code = 'INVALID' . time();
            
            // Create a test user
            $username = $this->username_prefix . 'invalid_' . time();
            $email = $username . '@example.com';
            $password = wp_generate_password();
            
            // Simulate registration with the invalid code
            $_POST['registration_code'] = $invalid_code;
            
            // First create the user without processing the code
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $this->record_result('Registration with Invalid Code', false, 'Failed to create test user: ' . $user_id->get_error_message());
                return false;
            }
            
            // Validate the code before processing
            $validation = $this->service->validate_code($invalid_code);
            $code_rejected = !$validation['is_valid'];
            
            // Process the registration code anyway (simulate the hook)
            $this->service->process_registration_code($user_id);
            
            // The user should not have the registration code in meta
            $stored_code = get_user_meta($user_id, 'registration_code', true);
            $no_meta = empty($stored_code);
            
            // Success means the invalid code was rejected
            $success = $code_rejected && $no_meta;
            $message = $code_rejected ? 
                'Successfully rejected registration with an invalid code' : 
                'Failed to reject registration with an invalid code';
            
            $this->record_result('Registration with Invalid Code', $success, $message);
            
            return $success ? $user_id : false;
        } catch (Exception $e) {
            $this->record_result('Registration with Invalid Code', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test WordPress hooks integration
     */
    private function test_wordpress_hooks() {
        try {
            // Check that necessary hooks are added
            $has_user_register_hook = has_action('user_register', array($this->service, 'process_registration_code'));
            $has_register_form_hook = has_action('register_form', array($this->service, 'add_registration_code_field'));
            $has_registration_errors_filter = has_filter('registration_errors', array($this->service, 'validate_registration_code_field'));
            
            $hooks_added = $has_user_register_hook && $has_register_form_hook && $has_registration_errors_filter;
            
            $success = $hooks_added;
            $message = $success ? 
                'All WordPress registration hooks are properly integrated' : 
                'Some WordPress registration hooks are missing';
            
            if (!$success) {
                $message .= '<br>user_register hook: ' . ($has_user_register_hook ? 'OK' : 'Missing');
                $message .= '<br>register_form hook: ' . ($has_register_form_hook ? 'OK' : 'Missing');
                $message .= '<br>registration_errors filter: ' . ($has_registration_errors_filter ? 'OK' : 'Missing');
            }
            
            $this->record_result('WordPress Hooks Integration', $success, $message);
            
            return $success;
        } catch (Exception $e) {
            $this->record_result('WordPress Hooks Integration', false, 'Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find a test course ID
     * 
     * @return int|false Course ID or false
     */
    private function find_test_course_id() {
        // Try to find a course ID based on the active LMS plugin
        
        // Check for LearnDash courses
        if (function_exists('learndash_get_courses_for_user')) {
            $args = array(
                'post_type' => 'sfwd-courses',
                'posts_per_page' => 1,
                'post_status' => 'publish',
            );
            $courses = get_posts($args);
            
            if (!empty($courses)) {
                return $courses[0]->ID;
            }
        }
        
        // Check for Tutor LMS courses
        if (function_exists('tutor_utils') && method_exists(tutor_utils(), 'get_courses')) {
            $args = array(
                'post_type' => tutor()->course_post_type,
                'posts_per_page' => 1,
                'post_status' => 'publish',
            );
            $courses = get_posts($args);
            
            if (!empty($courses)) {
                return $courses[0]->ID;
            }
        }
        
        return false;
    }
    
    /**
     * Clean up test data
     */
    private function cleanup() {
        global $wpdb;
        
        // Get all test users
        $test_users = get_users(array(
            'search' => $this->username_prefix . '*',
            'search_columns' => array('user_login'),
        ));
        
        // Delete test users if they exist
        foreach ($test_users as $user) {
            wp_delete_user($user->ID);
        }
        
        // Delete test codes (group_name starts with 'test_')
        $table_name = $this->db_manager->get_table_name();
        $wpdb->query("DELETE FROM {$table_name} WHERE group_name LIKE 'test_%'");
        
        echo '<p><strong>Cleanup:</strong> ' . count($test_users) . ' test users deleted and test registration codes removed.</p>';
    }
    
    /**
     * Custom error handler
     */
    public function error_handler($errno, $errstr, $errfile, $errline) {
        $this->record_result('ERROR', false, "PHP Error: [{$errstr}] in {$errfile} on line {$errline}");
        return true; // Don't execute PHP's default error handler
    }
    
    /**
     * Record test result
     * 
     * @param string $test_name Test name
     * @param bool|null $success Whether the test succeeded (null for skipped tests)
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
        $skipped = 0;
        
        foreach ($this->results as $result) {
            if ($result['success'] === true) {
                $passed++;
            } else if ($result['success'] === null) {
                $skipped++;
            }
        }
        
        $failed = $total - $passed - $skipped;
        
        echo '<h2>Test Results: ' . $passed . '/' . ($total - $skipped) . ' tests passed (' . $skipped . ' skipped)</h2>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Result</th><th>Message</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($this->results as $result) {
            $status = $result['success'] === true ? 'Pass' : ($result['success'] === null ? 'Skipped' : 'Fail');
            $class = $result['success'] === true ? 'success' : ($result['success'] === null ? 'skipped' : 'error');
            
            echo '<tr class="' . $class . '">';
            echo '<td>' . esc_html($result['name']) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td>' . wp_kses_post($result['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<style>';
        echo '.success { background-color: #dff0d8 !important; }';
        echo '.error { background-color: #f2dede !important; }';
        echo '.skipped { background-color: #fcf8e3 !important; }';
        echo '</style>';
    }
}

// Only run tests if explicitly called
if (isset($_GET['run_tests']) && $_GET['run_tests'] === 'registration_flow') {
    // Add admin styling
    echo '<link rel="stylesheet" href="' . admin_url('css/common.css') . '">';
    echo '<link rel="stylesheet" href="' . admin_url('css/forms.css') . '">';
    
    // Run the tests
    $tester = new Registration_Flow_Tester();
    $tester->run_tests();
} else {
    // Show a button to run tests
    echo '<div class="wrap">';
    echo '<h1>Registration Flow Tests</h1>';
    echo '<p>Click the button below to run comprehensive tests for the registration flow:</p>';
    echo '<a href="?page=' . esc_attr($_GET['page']) . '&run_tests=registration_flow" class="button button-primary">Run Registration Flow Tests</a>';
    echo '</div>';
}
