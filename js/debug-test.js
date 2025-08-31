jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Lilac Debug Test: Script loaded successfully!');
    
    // Test if jQuery is working
    console.log('jQuery version:', $.fn.jquery);
    
    // Test if WooCommerce AJAX is available
    console.log('WC AJAX URL:', typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : 'Not defined');
    
    // Test if our custom vars are available
    console.log('Lilac Vars:', typeof lilac_vars !== 'undefined' ? lilac_vars : 'Not defined');
    
    // Test form submission
    $('form.cart').each(function() {
        console.log('Found cart form:', this);
    });
    
    // Test add to cart buttons
    $('.add_to_cart_button').each(function() {
        console.log('Found add to cart button:', this);
    });
});
