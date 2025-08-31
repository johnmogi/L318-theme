<?php
/**
 * Subscription-related shortcodes
 * 
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Wrapper for YITH Subscription shortcode with fallback
 * 
 * Usage: [lilac_user_subscriptions] or [lilac_user_subscriptions fallback_message="Your custom message"]
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function lilac_user_subscriptions_shortcode($atts = []) {
    $atts = shortcode_atts([
        'fallback_message' => __('You do not have any active subscriptions.', 'hello-theme-child'),
    ], $atts, 'lilac_user_subscriptions');

    // Check if YITH WooCommerce Subscription is active
    if (function_exists('YWSBS_Subscription_Shortcodes') && shortcode_exists('ywsbs_my_account_subscriptions')) {
        // Use YITH's shortcode
        return do_shortcode('[ywsbs_my_account_subscriptions]');
    }

    // Fallback message if YITH is not active
    return '<div class="lilac-no-subscriptions"><p>' . esc_html($atts['fallback_message']) . '</p></div>';
}
add_shortcode('lilac_user_subscriptions', 'lilac_user_subscriptions_shortcode');

/**
 * Deprecation notice for old shortcode
 */
function llm_user_subscriptions_deprecated_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-warning"><p>';
        printf(
            /* translators: %s: New shortcode name */
            esc_html__('The [llm_user_subscriptions] shortcode is deprecated. Please use [lilac_user_subscriptions] instead.', 'hello-theme-child'),
            '[lilac_user_subscriptions]'
        );
        echo '</p></div>';
    }
}

// Add deprecation notice if old shortcode is used
function lilac_handle_deprecated_shortcode($atts) {
    add_action('admin_notices', 'llm_user_subscriptions_deprecated_notice');
    return lilac_user_subscriptions_shortcode($atts);
}
add_shortcode('llm_user_subscriptions', 'lilac_handle_deprecated_shortcode');

/**
 * Check if YITH WooCommerce Subscription is active
 * 
 * @return bool Whether the plugin is active
 */
function lilac_is_yith_subscription_active() {
    return class_exists('YITH_WC_Subscription') || function_exists('YWSBS_Subscription_Shortcodes');
}
