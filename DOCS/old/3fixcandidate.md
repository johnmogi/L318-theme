# WooCommerce Customizations Documentation

## Overview
This document details the custom WooCommerce implementation for handling different product types (physical and virtual) with separate checkout flows. The system is designed to provide a seamless checkout experience while maintaining clear separation between physical and virtual products.

## Files Modified

### 1. `includes/woocommerce/class-woocommerce-customizations.php`
Main class handling all WooCommerce customizations.

### 2. `functions.php`
Updated to load the WooCommerce customizations.

## Class: `Lilac_WooCommerce_Customizations`

### Properties
- None (static class)

### Methods

#### `init()`
Initializes all WooCommerce customizations by hooking into various WooCommerce actions and filters.

**Hooks Used:**
- `woocommerce_checkout_fields`: Customizes checkout fields
- `woocommerce_cart_needs_shipping`: Controls shipping field visibility
- `woocommerce_checkout_process`: Validates custom fields
- `woocommerce_checkout_update_order_meta`: Saves custom fields
- `woocommerce_email_order_meta_fields`: Adds fields to order emails
- `woocommerce_add_to_cart_validation`: Validates cart contents

#### `customize_checkout_fields($fields)`
Customizes the checkout fields based on cart contents.

**Parameters:**
- `$fields` (array) - Array of WooCommerce checkout fields

**Returns:**
- (array) Modified fields array

**Behavior:**
- Removes shipping fields for virtual products
- Adds custom fields (phone confirmation, ID number) for virtual products

#### `cart_contains_only_virtual()`
Checks if cart contains only virtual/downloadable products.

**Returns:**
- (bool) True if all products are virtual/downloadable

#### `cart_needs_shipping($needs_shipping)`
Determines if cart needs shipping.

**Parameters:**
- `$needs_shipping` (bool) - Default WooCommerce value

**Returns:**
- (bool) Modified shipping requirement

#### `validate_checkout_fields()`
Validates custom checkout fields.

**Validations:**
- Phone number confirmation match
- 9-digit ID number format

#### `save_checkout_fields($order_id)`
Saves custom fields to order meta.

**Parameters:**
- `$order_id` (int) - WooCommerce order ID

#### `add_fields_to_emails($fields, $sent_to_admin, $order)`
Adds custom fields to order emails.

**Parameters:**
- `$fields` (array) - Existing email fields
- `$sent_to_admin` (bool) - If email is sent to admin
- `$order` (WC_Order) - Order object

**Returns:**
- (array) Modified fields array

#### `validate_cart_mix($passed, $product_id, $quantity)`
Validates cart contents to prevent mixing product types.

**Parameters:**
- `$passed` (bool) - Default validation status
- `$product_id` (int) - Product being added
- `$quantity` (int) - Quantity being added

**Returns:**
- (bool) Validation status

## Integration Points

### 1. Checkout Flow
- **Virtual Products**:
  - No shipping fields
  - Custom registration fields
  - Direct checkout flow

- **Physical Products**:
  - Standard WooCommerce checkout
  - Shipping fields visible
  - Normal cart behavior

### 2. Cart Validation
- Prevents mixing of product types
- Validates custom fields
- Provides clear error messages

## Extending the Functionality

### Adding New Custom Fields
1. Add field definition in `customize_checkout_fields()`
2. Add validation in `validate_checkout_fields()`
3. Add saving logic in `save_checkout_fields()`
4. Update email display in `add_fields_to_emails()` if needed

### Modifying Validation Rules
- Edit the regex patterns in `validate_checkout_fields()`
- Add new validation conditions as needed

### Adding New Product Types
1. Update `cart_contains_only_virtual()` to handle new types
2. Modify `validate_cart_mix()` for any new validation rules

## Testing Checklist

### Virtual Products
- [ ] Add virtual product to cart
- [ ] Verify shipping fields are hidden
- [ ] Test custom field validation
- [ ] Verify order email contains custom fields

### Physical Products
- [ ] Add physical product to cart
- [ ] Verify shipping fields are visible
- [ ] Verify standard checkout flow works

### Mixed Cart Prevention
- [ ] Try adding both product types
- [ ] Verify error message is shown
- [ ] Verify cart updates correctly