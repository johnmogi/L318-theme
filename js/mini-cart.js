jQuery(document).ready(function($) {
    // Handle remove item from mini-cart
    $(document).on('click', '.woocommerce-mini-cart .remove_from_cart_button', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var product_id = $this.data('product_id');
        var cart_item_key = $this.data('cart_item_key');
        var $cart_item = $this.closest('.woocommerce-mini-cart-item');
        
        // Show loading state
        $this.addClass('loading');
        
        // AJAX call to remove item from cart
        $.ajax({
            type: 'POST',
            url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'remove_from_cart'),
            data: {
                action: 'remove_from_cart',
                product_id: product_id,
                cart_item_key: cart_item_key,
                _wpnonce: wc_cart_fragments_params.remove_item_nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.fragments) {
                    // Update fragments
                    $.each(response.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                    
                    // Trigger event so other scripts can update
                    $(document.body).trigger('removed_from_cart', [response.removed_cart_item_key, response]);
                    
                    // If cart is now empty, redirect to shop or home
                    if (response.cart_hash && response.cart_hash.length === 0) {
                        window.location.href = wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments');
                    }
                }
            },
            complete: function() {
                $this.removeClass('loading');
            }
        });
    });
    
    // Update cart fragments when items are removed
    $(document.body).on('removed_from_cart', function() {
        // Trigger a refresh of the checkout form to update totals
        if ($('form.checkout').length) {
            $('body').trigger('update_checkout');
        }
    });
});
