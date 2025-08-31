console.log('checkout.js loaded');

jQuery(document).ready(function($) {
    console.log('DOM ready in checkout.js');
    console.log('jQuery version:', $.fn.jquery);
    console.log('WC AJAX URL:', lilac_checkout_params ? lilac_checkout_params.wc_ajax_url : 'Not defined');
    
    // Debug: Log all cart items
    if (typeof wc_cart_fragments_params !== 'undefined') {
        console.log('Cart fragments params:', wc_cart_fragments_params);
    } else {
        console.error('wc_cart_fragments_params is not defined!');
    }
    // Debug: Check if our elements exist
    var $removeButtons = $('.woocommerce-checkout-review-order-table .remove_from_order_review');
    console.log('Found remove buttons:', $removeButtons.length);
    $removeButtons.each(function() {
        console.log('Remove button found for product ID:', $(this).data('product_id'));
    });
    
    // Handle remove item from order review
    $(document).on('click', '.woocommerce-checkout-review-order-table .remove_from_order_review', function(e) {
        console.log('Remove button clicked');
        console.log('Product ID:', $(this).data('product_id'));
        console.log('Cart Item Key:', $(this).data('cart_item_key'));
        e.preventDefault();
        
        var $this = $(this);
        var product_id = $this.data('product_id');
        var cart_item_key = $this.data('cart_item_key');
        
        // Show loading state
        $this.addClass('loading');
        
        // Send AJAX request to remove item from cart
        $.ajax({
            type: 'POST',
            url: lilac_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_from_cart'),
            data: {
                action: 'remove_from_cart',
                product_id: product_id,
                cart_item_key: cart_item_key,
                _wpnonce: wc_cart_fragments_params.remove_item_nonce
            },
            dataType: 'json',
            success: function(response) {
                console.log('AJAX success response:', response);
                if (response.fragments) {
                    // Update fragments (this will update the order review table)
                    $.each(response.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                    
                    // Trigger event so other scripts can update
                    $(document.body).trigger('removed_from_cart', [response.removed_cart_item_key, response]);
                    
                    // If cart is now empty, reload the page
                    if (response.cart_hash && response.cart_hash.length === 0) {
                        window.location.reload();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
                console.error('Error removing item from cart:', error);
                // Remove loading state on error
                $this.removeClass('loading');
            },
            complete: function() {
                // Trigger update_checkout to refresh the checkout form
                $(document.body).trigger('update_checkout');
            }
        });
    });
    
    // Handle update checkout after item removal
    $(document.body).on('removed_from_cart', function() {
        // Trigger a refresh of the checkout form to update totals
        $(document.body).trigger('update_checkout');
    });
    
    // Ensure the checkout form updates when the cart is updated
    $(document.body).on('updated_cart_totals updated_checkout', function() {
        // This ensures the order review section is properly updated
        $(document.body).trigger('update_checkout');
    });
});
