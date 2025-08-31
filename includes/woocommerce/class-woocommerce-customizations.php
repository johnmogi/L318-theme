<?php
/**
 * WooCommerce Customizations
 * 
 * Handles custom checkout fields, shipping logic, and product-specific behaviors.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lilac_WooCommerce_Customizations {
    
    /**
     * Initialize the class
     */
    public static function init() {
        // Remove shipping fields for virtual products
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'customize_checkout_fields']);
        
        // Hide shipping calculator on cart for virtual products
        add_filter('woocommerce_cart_needs_shipping', [__CLASS__, 'cart_needs_shipping']);
        
        // Add custom validation for checkout
        add_action('woocommerce_checkout_process', [__CLASS__, 'validate_checkout_fields']);
        
        // Save custom fields
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_checkout_fields']);
        
        // Add custom fields to order emails
        add_filter('woocommerce_email_order_meta_fields', [__CLASS__, 'add_fields_to_emails'], 10, 3);
        
        // Prevent mixing physical and virtual products in cart
        add_action('woocommerce_add_to_cart_validation', [__CLASS__, 'validate_cart_mix'], 10, 3);
    }
    
    /**
     * Customize checkout fields based on cart contents
     */
    public static function customize_checkout_fields($fields) {
        // If cart only contains virtual/downloadable products, remove shipping fields
        if (self::cart_contains_only_virtual()) {
            unset($fields['shipping']);
            
            // Add custom fields for course registration
            $fields['billing']['billing_phone_confirm'] = [
                'label'     => __('Confirm Phone', 'hello-child'),
                'required'  => true,
                'class'     => ['form-row-last'],
                'clear'     => true,
                'priority'  => 25
            ];
            
            $fields['billing']['billing_id_number'] = [
                'label'     => __('ID Number', 'hello-child'),
                'required'  => true,
                'class'     => ['form-row-first'],
                'priority'  => 22
            ];
        }
        
        return $fields;
    }
    
    /**
     * Check if cart contains only virtual/downloadable products
     */
    public static function cart_contains_only_virtual() {
        if (is_admin()) return false;
        if (!WC() || !WC()->cart) return false;
        
        $virtual_products = 0;
        $cart = WC()->cart;
        
        if (empty($cart->get_cart())) return false;
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product && ($product->is_virtual() || $product->is_downloadable())) {
                $virtual_products++;
            }
        }
        
        return $virtual_products === count($cart->get_cart());
    }
    
    /**
     * Determine if cart needs shipping
     */
    public static function cart_needs_shipping($needs_shipping) {
        if (self::cart_contains_only_virtual()) {
            return false;
        }
        return $needs_shipping;
    }
    
    /**
     * Validate custom checkout fields
     */
    public static function validate_checkout_fields() {
        if (self::cart_contains_only_virtual()) {
            // Phone confirmation mismatch temporarily disabled to loosen validation
            // if (isset($_POST['billing_phone'], $_POST['billing_phone_confirm']) && 
            //     $_POST['billing_phone'] !== $_POST['billing_phone_confirm']) {
            //     wc_add_notice(__('Phone numbers do not match.', 'hello-child'), 'error');
            // }
            
            // Validate ID number format (Israeli ID)
            if (isset($_POST['billing_id_number']) && !preg_match('/^\d{9}$/', $_POST['billing_id_number'])) {
                wc_add_notice(__('Please enter a valid 9-digit ID number.', 'hello-child'), 'error');
            }
        }
    }
    
    /**
     * Save custom fields
     */
    public static function save_checkout_fields($order_id) {
        if (!empty($_POST['billing_id_number'])) {
            update_post_meta($order_id, '_billing_id_number', sanitize_text_field($_POST['billing_id_number']));
        }
    }
    
    /**
     * Add custom fields to order emails
     */
    public static function add_fields_to_emails($fields, $sent_to_admin, $order) {
        $id_number = get_post_meta($order->get_id(), '_billing_id_number', true);
        
        if ($id_number) {
            $fields['billing_id_number'] = [
                'label' => __('ID Number', 'hello-child'),
                'value' => $id_number,
            ];
        }
        
        return $fields;
    }
    
    /**
     * Prevent mixing physical and virtual products in cart
     */
    public static function validate_cart_mix($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        $cart = WC()->cart;
        
        if (!$product || !$cart) {
            return $passed;
        }
        
        // Skip if cart is empty
        if ($cart->is_empty()) {
            return $passed;
        }
        
        // Check if adding a virtual product to cart with physical products
        if (($product->is_virtual() || $product->is_downloadable()) && $cart->needs_shipping()) {
            wc_add_notice(__('You cannot add a course to a cart that contains physical products. Please complete your purchase of physical items first.', 'hello-child'), 'error');
            return false;
        }
        
        // Check if adding a physical product to cart with virtual products
        if (!$product->is_virtual() && !$product->is_downloadable() && self::cart_contains_only_virtual()) {
            wc_add_notice(__('You cannot add physical products to a cart that contains courses. Please complete your course purchase first.', 'hello-child'), 'error');
            return false;
        }
        
        return $passed;
    }
}

// Initialize the class
add_action('woocommerce_loaded', ['Lilac_WooCommerce_Customizations', 'init']);
