<?php
/**
 * Debug tool for registration codes
 */

// Add debug menu item
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Registration Codes',
        'Debug Reg Codes',
        'manage_options',
        'debug-registration-codes',
        'debug_registration_codes_page'
    );
});

// Debug page content
function debug_registration_codes_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'registration_codes';
    
    echo '<div class="wrap">';
    echo '<h1>Registration Codes Debug</h1>';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        echo '<div class="notice notice-error"><p>Error: Table ' . esc_html($table_name) . ' does not exist.</p></div>';
        return;
    }
    
    // Get table structure
    echo '<h2>Table Structure</h2>';
    $structure = $wpdb->get_results("DESCRIBE $table_name");
    echo '<pre>' . print_r($structure, true) . '</pre>';
    
    // Get sample data
    echo '<h2>Sample Data (First 10 Rows)</h2>';
    $results = $wpdb->get_results("SELECT * FROM $table_name LIMIT 10");
    
    if (empty($results)) {
        echo '<p>No records found in the table.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        // Table header
        echo '<thead><tr>';
        foreach (array_keys((array)$results[0]) as $column) {
            echo '<th>' . esc_html($column) . '</th>';
        }
        echo '</tr></thead>';
        
        // Table rows
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . esc_html($value ?: 'NULL') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    // Test validation
    if (isset($_POST['test_code'])) {
        $test_code = sanitize_text_field($_POST['test_code']);
        echo '<div class="notice notice-info">';
        echo '<h3>Test Validation for Code: ' . esc_html($test_code) . '</h3>';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s",
            $test_code
        ));
        
        if ($result) {
            echo '<p>Code found in database:</p>';
            echo '<pre>' . print_r($result, true) . '</pre>';
            
            if ($result->is_used || !empty($result->used_at)) {
                echo '<p style="color:red;">This code has already been used.</p>';
            } else {
                echo '<p style="color:green;">This code is valid and available.</p>';
            }
        } else {
            echo '<p style="color:red;">Code not found in database.</p>';
        }
        echo '</div>';
    }
    
    // Test form
    echo '<h2>Test Code Validation</h2>';
    echo '<form method="post">';
    echo '<p><label>Enter code to test: <input type="text" name="test_code" required></label></p>';
    echo '<p><input type="submit" class="button button-primary" value="Test Code"></p>';
    echo '</form>';
    
    echo '</div>'; // .wrap
}

// Add the debug page to the theme's includes
add_action('after_setup_theme', function() {
    if (is_admin() && current_user_can('manage_options') && !function_exists('debug_registration_codes_page')) {
        // Don't load the debug functions if they're already defined
        require_once __FILE__;
    }
});
