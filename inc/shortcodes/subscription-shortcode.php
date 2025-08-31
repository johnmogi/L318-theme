<?php
/**
 * Subscription Shortcodes
 * 
 * Handles the display of user subscriptions with YITH WooCommerce Subscription integration.
 * Provides a wrapper around the YITH shortcode with fallback functionality.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if YITH WooCommerce Subscription is active
 * 
 * @return bool True if YITH WooCommerce Subscription is active, false otherwise
 */
function lilac_is_yith_subscription_active() {
    return class_exists('YITH_YWSBS_Subscription');
}

/**
 * Main subscription shortcode handler
 * 
 * Shortcode: [lilac_user_subscriptions]
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function lilac_user_subscriptions_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'title' => __('Your Subscriptions', 'hello-theme-child'),
        'status' => 'all', // Default to showing all statuses
        'limit' => 10, // Default number of subscriptions to show
        'show_empty' => 'yes', // Whether to show message when no subscriptions found
    ], $atts, 'lilac_user_subscriptions');

    // Start output buffering
    ob_start();

    // Check if user is logged in
    if (!is_user_logged_in()) {
        echo '<div class="lilac-alert lilac-alert-info">' . 
             __('Please log in to view your subscriptions.', 'hello-theme-child') . 
             '</div>';
        return ob_get_clean();
    }

    // Check if YITH WooCommerce Subscription is active
    if (lilac_is_yith_subscription_active()) {
        // Use YITH's built-in shortcode if available
        if (shortcode_exists('ywsbs_my_account_subscriptions')) {
            // Add a wrapper div with a specific class for styling
            echo '<div class="lilac-subscriptions-wrapper">';
            if (!empty($atts['title'])) {
                echo '<h2 class="lilac-subscriptions-title">' . esc_html($atts['title']) . '</h2>';
            }
            
            // Pass through relevant attributes to YITH shortcode
            $yith_atts = [];
            if ($atts['status'] !== 'all') {
                $yith_atts['status'] = $atts['status'];
            }
            if ($atts['limit'] > 0) {
                $yith_atts['limit'] = $atts['limit'];
            }
            
            // Output the YITH shortcode with our attributes
            echo do_shortcode('[ywsbs_my_account_subscriptions ' . 
                implode(' ', array_map(function($key, $value) {
                    return $key . '="' . esc_attr($value) . '"';
                }, array_keys($yith_atts), $yith_atts)) . ']');
            
            echo '</div>'; // Close wrapper div
            
            // Enqueue any necessary styles
            wp_enqueue_style('lilac-subscriptions');
            
            return ob_get_clean();
        }
    }
    
    // Fallback: Show message if no subscriptions found or YITH not active
    if ($atts['show_empty'] === 'yes') {
        echo '<div class="lilac-alert lilac-alert-info">';
        
        if (!lilac_is_yith_subscription_active()) {
            echo __('The subscription system is currently unavailable.', 'hello-theme-child');
            if (current_user_can('manage_options')) {
                echo ' <strong>' . __('Admin Notice:', 'hello-theme-child') . '</strong> ';
                echo __('YITH WooCommerce Subscription plugin is not active.', 'hello-theme-child');
            }
        } else {
            echo __('No subscriptions found.', 'hello-theme-child');
        }
        
        echo '</div>';
    }
    
    return ob_get_clean();
}
add_shortcode('lilac_user_subscriptions', 'lilac_user_subscriptions_shortcode');

/**
 * Deprecated shortcode handler for backward compatibility
 * 
 * Shortcode: [llm_user_subscriptions]
 * 
 * @deprecated Use [lilac_user_subscriptions] instead
 */
function llm_user_subscriptions_shortcode($atts = []) {
    // Show admin notice about deprecated shortcode
    if (current_user_can('manage_options')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('Deprecated Shortcode:', 'hello-theme-child') . '</strong> ';
            echo __('The [llm_user_subscriptions] shortcode is deprecated. Please use [lilac_user_subscriptions] instead.', 'hello-theme-child');
            echo '</p></div>';
        });
    }
    
    // Pass through to the new shortcode
    return lilac_user_subscriptions_shortcode($atts);
}
add_shortcode('llm_user_subscriptions', 'llm_user_subscriptions_shortcode');

/**
 * Enqueue styles for the subscriptions shortcode
 */
function lilac_enqueue_subscription_styles() {
    wp_register_style(
        'lilac-subscriptions',
        get_stylesheet_directory_uri() . '/assets/css/subscriptions.css',
        [],
        filemtime(get_stylesheet_directory() . '/assets/css/subscriptions.css')
    );
}
add_action('wp_enqueue_scripts', 'lilac_enqueue_subscription_styles');
