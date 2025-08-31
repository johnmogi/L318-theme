<?php
/**
 * Plugin Name: Admin Menu Colors
 * Description: Fix admin menu text colors for better visibility
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Menu_Colors {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_head', array($this, 'add_admin_styles'));
    }
    
    public function add_admin_styles() {
        ?>
        <style>
            /* Fix main menu text color */
            .wp-menu-name {
                color: #fff !important;
            }
           .welcome-panel {
            background-color: #fff !important;
          }
            
            /* Fix RTL alignment */
            .welcome-panel-column {
                float: right !important;
            }
            
            .welcome-panel-last {
                margin-right: 0 !important;
                margin-left: 20px !important;
            }
        </style>
        <?php
    }
}

// Initialize
Admin_Menu_Colors::instance();
