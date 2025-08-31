<?php
/**
 * Promo Code System - Installation
 * 
 * Creates and manages the promo code database table
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the promo code database table
 */
function lilac_create_promo_code_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'edc_school_promo_codes';
    
    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table does not exist, create it
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            course_id bigint(20) NOT NULL,
            description text NULL,
            max_uses int(11) NOT NULL DEFAULT 1,
            used_count int(11) NOT NULL DEFAULT 0,
            expiry_date datetime NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log the creation for debugging
        error_log('Created promo codes table: ' . $table_name);
    }
}

/**
 * Create the promo code usage tracking table
 */
function lilac_create_promo_code_usage_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'edc_school_promo_code_usage';
    
    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table does not exist, create it
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY code_id (code_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log the creation for debugging
        error_log('Created promo code usage table: ' . $table_name);
    }
}

/**
 * Initialize the promo code system
 */
function lilac_initialize_promo_code_system() {
    lilac_create_promo_code_table();
    lilac_create_promo_code_usage_table();
}

// Hook into WordPress activation
register_activation_hook(__FILE__, 'lilac_initialize_promo_code_system');

// Also run on init to ensure tables exist (safely checks if they exist first)
add_action('init', 'lilac_initialize_promo_code_system', 5);
