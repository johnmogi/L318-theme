<?php
/**
 * Registration System Initialization
 * 
 * Bootstraps the OOP registration system and connects it to WordPress.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initialize the registration system
 */
function registration_system_init() {
    // Load required files
    require_once dirname(__FILE__) . '/class-registration-db-manager.php';
    require_once dirname(__FILE__) . '/class-registration-code.php';
    require_once dirname(__FILE__) . '/class-registration-service.php';
    require_once dirname(__FILE__) . '/class-registration-admin.php';
    
    // Initialize database manager
    $db_manager = Registration_DB_Manager::get_instance();
    
    // Create or update tables if necessary
    add_action('plugins_loaded', array($db_manager, 'maybe_create_tables'));
    
    // Initialize the registration service (which handles hooks and functionality)
    $service = Registration_Service::get_instance();
}

// Initialize the registration system
registration_system_init();
