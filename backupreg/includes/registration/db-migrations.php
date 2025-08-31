<?php
/**
 * Database Migrations for Registration Codes
 * 
 * Handles database structure updates for the registration codes system
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Run database migrations for registration codes
 */
function lilac_run_registration_db_migrations() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'registration_codes';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Get current database version
    $current_version = get_option('registration_codes_db_version', '0');
    
    // Add new columns if needed
    if (version_compare($current_version, '1.2.0', '<')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Check if table exists first
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            // Table doesn't exist, create it with all columns
            $sql = "CREATE TABLE {$table_name} (
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
                KEY is_used (is_used),
                KEY course_id (course_id),
                KEY expiry_date (expiry_date)
            ) $charset_collate;";
            
            dbDelta($sql);
        } else {
            // Table exists, just add new columns
            
            // Add course_id column if it doesn't exist
            $column = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, 'course_id'
            ));
            
            if (empty($column)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN course_id bigint(20) DEFAULT NULL AFTER group_name");
                $wpdb->query("ALTER TABLE {$table_name} ADD KEY course_id (course_id)");
            }
            
            // Add max_uses column if it doesn't exist
            $column = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, 'max_uses'
            ));
            
            if (empty($column)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN max_uses int(11) DEFAULT 1 AFTER course_id");
            }
            
            // Add used_count column if it doesn't exist
            $column = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, 'used_count'
            ));
            
            if (empty($column)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN used_count int(11) DEFAULT 0 AFTER max_uses");
            }
            
            // Add expiry_date column if it doesn't exist
            $column = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, 'expiry_date'
            ));
            
            if (empty($column)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN expiry_date datetime DEFAULT NULL AFTER used_count");
                $wpdb->query("ALTER TABLE {$table_name} ADD KEY expiry_date (expiry_date)");
            }
        }
        
        // Update the database version
        update_option('registration_codes_db_version', '1.2.0');
    }
}

// Run migrations on plugin load
add_action('plugins_loaded', 'lilac_run_registration_db_migrations', 11); // Run after the main class init
