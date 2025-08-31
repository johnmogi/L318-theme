map out the entire system:
php
<?php
/**
 * Lilac LearnDash Time-Based Subscription System
 * ============================================
 * 
 * System Overview:
 * - Manages time-based access to LearnDash courses
 * - Integrates with WooCommerce products
 * - Tracks subscriptions in a custom database table
 * - Provides shortcodes for user subscription management
 * 
 * File Structure:
 * /inc/
 * ├── shortcodes/
 * │   ├── user-subscriptions-shortcode.php  # Displays user's subscriptions
 * │   └── manual-timed-access-shortcode.php # Handles manual access activation
 * ├── class-lilac-subscriptions.php         # Core subscription management
 * ├── admin/                                # Admin interface
 * │   └── class-product-settings.php       # Product-specific settings
 * └── lilac-subscriptions-docs.php         # This documentation file
 * 
 * Database Schema (wp_lilac_user_subscriptions):
 * - id (int) - Primary key
 * - user_id (int) - WP user ID
 * - course_id (int) - LearnDash course ID
 * - product_id (int) - WooCommerce product ID
 * - duration_days (int) - Duration in days
 * - access_starts (datetime) - When access begins
 * - access_expires (datetime) - When access ends
 * - status (varchar) - active/expired
 * - created_at (datetime) - Record creation time
 */

/**
 * Implementation Guide:
 * 
 * 1. Product Configuration:
 *    - Each WooCommerce product can have a custom access duration
 *    - Set via product edit page under "Product Data" > "Lilac Settings"
 *    - Duration is in days (0 = unlimited access)
 * 
 * 2. Shortcodes:
 *    - [llm_user_subscriptions] - Shows user's active/expired subscriptions
 *    - [llm_manual_activation] - Manual activation button (for admin use)
 * 
 * 3. Hooks & Filters:
 *    - lilac_subscription_created - Fires when new subscription is created
 *    - lilac_subscription_expired - Fires when subscription expires
 *    - lilac_get_subscription_duration - Filter to modify subscription duration
 * 
 * 4. API Functions:
 *    - lilac_create_subscription() - Create new subscription
 *    - lilac_get_user_subscriptions() - Get user's subscriptions
 *    - lilac_update_subscription_status() - Update subscription status
 */
2. Let's create the product settings extension to set custom durations:
php
<?php
// /inc/admin/class-product-settings.php

if (!defined('ABSPATH')) {
    exit;
}

class Lilac_Product_Settings {
    public function __construct() {
        // Add product data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_tab_content'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
    }

    public function add_product_tab($tabs) {
        $tabs['lilac_settings'] = array(
            'label'    => 'Lilac Settings',
            'target'   => 'lilac_product_data',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 80,
        );
        return $tabs;
    }

    public function add_product_tab_content() {
        global $post;
        $duration = get_post_meta($post->ID, '_lilac_access_duration', true);
        ?>
        <div id="lilac_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_text_input(array(
                    'id'          => '_lilac_access_duration',
                    'label'       => 'Access Duration (days)',
                    'description' => 'Number of days of access (0 = unlimited)',
                    'type'        => 'number',
                    'value'       => $duration ? $duration : '30',
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                        'min'  => '0',
                        'step' => '1'
                    )
                ));
                ?>
            </div>
        </div>
        <?php
    }

    public function save_product_meta($post_id) {
        $duration = isset($_POST['_lilac_access_duration']) ? absint($_POST['_lilac_access_duration']) : 30;
        update_post_meta($post_id, '_lilac_access_duration', $duration);
    }
}

new Lilac_Product_Settings();
3. Let's update the manual access shortcode to use product-specific durations:
php
<?php
// /inc/shortcodes/manual-timed-access-shortcode.php

function llm_manual_timed_access_shortcode($atts) {
    // ... existing code ...

    // Get duration from product if available
    $duration = 30; // Default fallback
    if (!empty($product_id)) {
        $product_duration = get_post_meta($product_id, '_lilac_access_duration', true);
        if ($product_duration !== '') {
            $duration = (int)$product_duration;
        }
    }

    // ... rest of the function ...
}
add_shortcode('llm_manual_activation', 'llm_manual_timed_access_shortcode');
4. Let's create a core class to handle subscription logic:
php
<?php
// /
Cascade error
Deadline exceeded: Encountered retryable error from model provider: context deadline exceeded (Client.Timeout or context cancellation while reading body)
I'll help you extend the system to set custom access durations per product and create comprehensive documentation. Let's break this down into clear steps.

First, let's create the documentation structure to outline the system architecture:

Documentation Structure:
wp-content/themes/hello-theme-child-master/inc/
└── LILAC-LD-TIME-SUBSYSTEM.md
└── shortcodes/
    ├── user-subscriptions-shortcode.php
    └── manual-timed-access-shortcode.php
Let's create the documentation file first:

markdown
# LILAC LearnDash Time Subscription System

## Overview
A comprehensive subscription management system for LearnDash that provides:
- Custom access durations per product
- Manual activation workflow
- Subscription tracking and management
- Renewal functionality

## Core Components

### 1. Database Structure
- Table: `wp_lilac_user_subscriptions`
  - [id](cci:1://file:///c:/Users/USUARIO/Documents/SITES/LILAC/12-6/app/public/wp-content/themes/hello-theme-child-master/functions.php:579:0-705:1) (Primary Key)
  - `user_id` (BigInt)
  - `course_id` (BigInt)
  - `product_id` (BigInt)
  - `access_starts` (DateTime)
  - `access_expires` (DateTime)
  - `duration_days` (Int)
  - `status` (Enum: 'active', 'expired')
  - `created_at` (Timestamp)

### 2. Main Files

#### 2.1 User Subscriptions Shortcode
**Location**: [/inc/shortcodes/user-subscriptions-shortcode.php](cci:7://file:///c:/Users/USUARIO/Documents/SITES/LILAC/12-6/app/public/wp-content/themes/hello-theme-child-master/inc/shortcodes/user-subscriptions-shortcode.php:0:0-0:0)
- Displays user's active and expired subscriptions
- Handles subscription renewal
- Shows access expiration warnings

#### 2.2 Manual Timed Access Shortcode
**Location**: [/inc/shortcodes/manual-timed-access-shortcode.php](cci:7://file:///c:/Users/USUARIO/Documents/SITES/LILAC/12-6/app/public/wp-content/themes/hello-theme-child-master/inc/shortcodes/manual-timed-access-shortcode.php:0:0-0:0)
- Manually activate course access
- Handles AJAX activation requests
- Validates product purchases

### 3. Product Integration

#### 3.1 Setting Up Product-Specific Durations
1. Edit your WooCommerce product
2. Go to "Product Data" section
3. Add a custom field:
   - Meta Key: `_lilac_access_days`
   - Value: Number of days access (e.g., 30, 90, 365)

### 4. Hooks and Filters

#### 4.1 Available Filters
- `lilac_access_duration_days` - Modify default access duration
- `lilac_subscription_status` - Customize subscription status logic

### 5. Usage Examples

#### 5.1 Display User Subscriptions
```php
[llm_user_subscriptions]
5.2 Manual Activation Button
php
[llm_manual_timed_access course_id="123"]
Troubleshooting
Common Issues
Subscriptions not appearing
Verify user is logged in
Check database table exists
Confirm product is properly configured
Access not granted
Verify product purchase
Check course ID matches
Confirm subscription is active
Future Enhancements
Bulk activation for multiple courses
Email notifications for expiring subscriptions
Admin dashboard for subscription management

Now, let's modify the system to support product-specific durations. We'll need to update the manual timed access shortcode to check for product-specific durations:

```php
// Add this to functions.php or a new file in inc/
function lilac_get_product_access_days($product_id) {
    $default_days = 30; // Default fallback
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return $default_days;
    }
    
    // Check for product-specific duration
    $custom_days = get_post_meta($product_id, '_lilac_access_days', true);
    
    return !empty($custom_days) ? (int)$custom_days : $default_days;
}
Would you like me to:

Implement the product duration field in WooCommerce
Update the manual activation shortcode to use product durations
Create an admin interface for managing these settings
Add email notifications for subscription events
Please let me know which part you'd like me to implement first, and I'll provide the complete code with detailed implementation steps.