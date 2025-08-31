<?php
/**
 * Registration Admin Class
 * Stub class to prevent fatal errors
 */

if (!defined('ABSPATH')) {
    exit;
}

class Registration_Admin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Stub constructor
    }
}
