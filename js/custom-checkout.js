/**
 * Custom WooCommerce Checkout Modifications
 * 
 * This script removes unwanted elements from the checkout page and ensures
 * the remaining fields work correctly with the theme's styling.
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Debug flag - set to false in production
    const DEBUG = true;
    
    /**
     * Log debug messages to console if debug is enabled
     * @param {string} message - The message to log
     * @param {*} [data] - Optional data to log
     */
    function debugLog(message, data) {
        if (DEBUG && window.console) {
            console.log(`[Checkout Debug] ${message}`, data || '');
        }
    }
    
    /**
     * Remove unwanted elements from the checkout page
     */
    function cleanupCheckoutPage() {
        // Remove coupon code section if it exists
        $('.woocommerce-form-coupon-toggle, .woocommerce-form-coupon').remove();
        
        // Remove "Ship to a different address?" checkbox and shipping fields
        $('#ship-to-different-address, .woocommerce-shipping-fields, .shipping_address').remove();
        
        // Remove additional information section
        $('.woocommerce-additional-fields').remove();
        
        // Remove section titles
        $('.woocommerce-billing-fields h3, .woocommerce-shipping-fields h3, .woocommerce-additional-fields h3').remove();
        
        // Remove all field labels
        $('.woocommerce-billing-fields label:not(.woocommerce-form__label-for-checkbox),
          .woocommerce-shipping-fields label:not(.woocommerce-form__label-for-checkbox),
          .woocommerce-additional-fields label').each(function() {
            $(this).remove();
        });
        
        // Remove any remaining labels from field wrappers
        $('.woocommerce-billing-fields__field-wrapper label,
          .woocommerce-shipping-fields__field-wrapper label,
          .woocommerce-additional-fields__field-wrapper label').remove();
        
        // Remove promo code field if it exists
        $('.form-row.promo_code_field').remove();
        
        // Ensure placeholders are visible
        $('.woocommerce-billing-fields input::placeholder,
          .woocommerce-billing-fields textarea::placeholder').css({
            'opacity': '1',
            'color': '#777'
        });
        
        debugLog('Checkout page cleaned up');
    }
    
    /**
     * Initialize the checkout modifications
     */
    function initCheckoutModifications() {
        debugLog('Initializing checkout modifications');
        
        // Run cleanup immediately
        cleanupCheckoutPage();
        
        // Run cleanup again after a short delay to catch any AJAX-loaded elements
        setTimeout(cleanupCheckoutPage, 1000);
        
        // Also run cleanup when the checkout updates (for AJAX updates)
        $(document.body).on('updated_checkout', function() {
            debugLog('Checkout updated, running cleanup');
            cleanupCheckoutPage();
        });
        
        // For WooCommerce Blocks (if used)
        $(document.body).on('updated_wc_div', function() {
            debugLog('WC Blocks updated, running cleanup');
            cleanupCheckoutPage();
        });
    }
    
    // Initialize when the page is ready
    initCheckoutModifications();
    
    // Also initialize when the page is fully loaded
    $(window).on('load', function() {
        debugLog('Window loaded, final cleanup');
        cleanupCheckoutPage();
    });
    
});
