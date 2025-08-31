<?php
/**
 * Database Migrations for Registration Codes v1.3.0
 * 
 * Adds new user fields for registration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Run database migrations for registration codes v1.3.0
 */
function lilac_run_registration_db_migrations_1_3_0() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'registration_codes';
    
    // Get current database version
    $current_version = get_option('registration_codes_db_version', '0');
    
    // Only run if we're upgrading to 1.3.0 or later
    if (version_compare($current_version, '1.3.0', '<')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Add new columns if they don't exist
        $columns_to_add = array(
            'first_name' => 'VARCHAR(100) DEFAULT NULL AFTER role',
            'last_name' => 'VARCHAR(100) DEFAULT NULL AFTER first_name',
            'school_name' => 'VARCHAR(255) DEFAULT NULL AFTER last_name',
            'school_city' => 'VARCHAR(100) DEFAULT NULL AFTER school_name',
            'school_code' => 'VARCHAR(50) DEFAULT NULL AFTER school_city',
            'mobile_phone' => 'VARCHAR(50) DEFAULT NULL AFTER school_code',
            'user_password' => 'VARCHAR(255) DEFAULT NULL AFTER mobile_phone'
        );
        
        $table_columns = $wpdb->get_col("DESCRIBE `$table_name`", 0);
        
        foreach ($columns_to_add as $column => $definition) {
            if (!in_array($column, $table_columns)) {
                $wpdb->query("ALTER TABLE `$table_name` ADD COLUMN `$column` $definition");
            }
        }
        
        // Add indexes for better performance
        $indexes = array('school_name', 'school_city', 'school_code', 'mobile_phone');
        $existing_indexes = $wpdb->get_col("SHOW INDEX FROM `$table_name` WHERE Key_name != 'PRIMARY'");
        
        foreach ($indexes as $index) {
            if (!in_array($index, $existing_indexes)) {
                $wpdb->query("ALTER TABLE `$table_name` ADD INDEX `idx_$index` (`$index`)");
            }
        }
        
        // Update the database version
        update_option('registration_codes_db_version', '1.3.0');
    }
}

// Run migrations on plugin load
add_action('plugins_loaded', 'lilac_run_registration_db_migrations_1_3_0', 12); // Run after the main class init
