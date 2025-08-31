jQuery(document).ready(function($) {
    'use strict';
    
    // Debug function
    function debugLog() {
        if (window.console && window.console.log) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[Lilac Debug]');
            console.log.apply(console, args);
        }
    }
    
    debugLog('Lilac Add to Cart script loaded');
    
    // Check if WooCommerce AJAX is available
    if (typeof wc_add_to_cart_params === 'undefined') {
        debugLog('WooCommerce AJAX parameters not found. Some features may not work correctly.');
    } else {
        debugLog('WooCommerce AJAX URL:', wc_add_to_cart_params.ajax_url);
    }
    
    // Debug function
    function debugLog() {
        if (!window.console || !window.console.log) return;
        
        var args = Array.prototype.slice.call(arguments);
        var timestamp = new Date().toISOString();
        
        // Add timestamp and prefix to all log messages
        args.unshift('[Lilac Debug ' + timestamp + ']');
        
        // Log to console
        console.log.apply(console, args);
        
        // Also log to server if debug is enabled
        if (lilac_vars.debug === 'yes') {
            $.ajax({
                url: lilac_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'lilac_debug_log',
                    message: args.join(' ')
                }
            });
        }
    }
    
    // Function to redirect to checkout
    function redirectToCheckout() {
        debugLog('Redirecting to checkout');
        var checkoutUrl = lilac_vars.checkout_url;
        
        // Add a small random parameter to prevent caching
        var timestamp = new Date().getTime();
        var separator = checkoutUrl.includes('?') ? '&' : '?';
        window.location.href = checkoutUrl + separator + 'nocache=' + timestamp;
    }
    
    // Handle AJAX add to cart events
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
        debugLog('=== ADDED TO CART VIA AJAX ===');
        debugLog('Button:', $button ? $button.attr('class') : 'No button');
        
        // Redirect to checkout after a short delay
        setTimeout(redirectToCheckout, 500);
    });
    
    // Handle add to cart button click
    $(document).on('click', '.single_add_to_cart_button', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $form = $button.closest('form.cart');
        
        // Disable button to prevent multiple clicks
        $button.prop('disabled', true).addClass('loading');
        
        // Get product ID from the form
        var product_id = $form.find('input[name="add-to-cart"]').val() || 
                        $button.val() ||
                        $form.data('product_id');
        
        // Get quantity
        var quantity = $form.find('input.qty').val() || 1;
        
        debugLog('Adding to cart - Product ID:', product_id, 'Quantity:', quantity);
        
        // Submit via AJAX
        $.ajax({
            type: 'POST',
            url: wc_add_to_cart_params.ajax_url,
            data: {
                action: 'woocommerce_add_to_cart',
                product_id: product_id,
                quantity: quantity,
                _wpnonce: $form.find('input[name="_wpnonce"]').val()
            },
            success: function(response) {
                debugLog('Add to cart success:', response);
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.href = wc_add_to_cart_params.cart_url;
                }
            },
            error: function(xhr, status, error) {
                debugLog('Add to cart error:', status, error);
                // Fallback to regular form submission
                $form.off('submit').submit();
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('loading');
            }
        });
    });
    
    // Handle direct add to cart buttons (like on shop page)
    $(document).on('click', '.add_to_cart_button:not(.product_type_variable):not(.product_type_grouped)', function(e) {
        var $button = $(this);
        
        // Only handle if not already processing
        if ($button.is('.loading, .added')) {
            return true;
        }
        
        debugLog('=== DIRECT ADD TO CART CLICKED ===');
        debugLog('Button data:', $button.data());
        
        // Let WooCommerce handle the click, our added_to_cart handler will take care of the rest
    });
});
