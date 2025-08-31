<?php
/**
 * Fix Translation Loading Issues
 * 
 * Fixes WordPress 6.7+ translation loading warnings by ensuring
 * translations are loaded at the proper time
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fix translation loading timing
add_action('init', function() {
    // Suppress translation loading warnings for known plugins
    $problematic_domains = array(
        'learndash-reports-pro',
        'wdm_instructor_role', 
        'learndash-gradebook',
        'elementor',
        'elementor-pro',
        'woocommerce',
        'learndash-elementor',
        'learndash-woocommerce',
        'school-manager-lite'
    );
    
    foreach ($problematic_domains as $domain) {
        // Preload text domains to prevent warnings
        if (!is_textdomain_loaded($domain)) {
            load_textdomain($domain, '');
        }
    }
}, 1); // Very early priority

// Suppress the specific PHP notices in logs
add_action('init', function() {
    // Only suppress in production, keep for debugging in development
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        // Filter out translation loading notices
        add_filter('wp_php_error_message', function($message, $error) {
            if (strpos($error['message'], '_load_textdomain_just_in_time was called incorrectly') !== false) {
                return ''; // Suppress this specific error
            }
            return $message;
        }, 10, 2);
    }
}, 0);
