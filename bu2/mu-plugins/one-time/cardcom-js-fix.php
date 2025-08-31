<?php
/**
 * CardCom JavaScript Fix
 * 
 * Fixes CardCom plugin JavaScript errors that interfere with admin pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class CardCom_JS_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only fix on admin pages that aren't WooCommerce settings
        add_action('admin_enqueue_scripts', array($this, 'fix_cardcom_js'), 999);
    }
    
    /**
     * Fix CardCom JavaScript errors on non-WooCommerce admin pages
     */
    public function fix_cardcom_js($hook) {
        // Only run on school manager pages
        if (strpos($hook, 'school-manager') === false && 
            !isset($_GET['page']) || 
            strpos($_GET['page'], 'school-manager') === false) {
            return;
        }
        
        // Add inline script to prevent CardCom errors
        $script = "
        jQuery(document).ready(function($) {
            // Prevent CardCom errors by checking if elements exist
            if (typeof window.cardcomAdminInit !== 'undefined') {
                return; // CardCom already initialized properly
            }
            
            // Override problematic CardCom functions
            window.cardcomAdminInit = true;
            
            // Fix the specific error on line 22 of cardcom.js
            var originalGetElementById = document.getElementById;
            document.getElementById = function(id) {
                var element = originalGetElementById.call(document, id);
                if (!element && id === 'woocommerce_cardcom_currency') {
                    // Create a dummy element to prevent null errors
                    var dummy = document.createElement('input');
                    dummy.value = '';
                    dummy.id = id;
                    dummy.style.display = 'none';
                    document.body.appendChild(dummy);
                    return dummy;
                }
                return element;
            };
            
            console.log('CardCom JS Fix: Applied fixes for school manager pages');
        });
        ";
        
        wp_add_inline_script('jquery', $script);
        
        error_log("CardCom JS Fix: Applied fix for page: " . $hook);
    }
}

// Initialize the fix
CardCom_JS_Fix::instance();
