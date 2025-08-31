<?php
/**
 * YITH WooCommerce Subscription Status Check
 * 
 * A minimal script to check the status of YITH WooCommerce Subscription
 * without making any changes to the site.
 */

// Only run in admin area
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add debug logging
function yith_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log('YITH Debug: ' . $message);
        }
    }
}

// Add menu item
function yith_status_check_menu() {
    try {
        yith_debug_log('Attempting to add YITH Status menu item');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            yith_debug_log('WooCommerce is not active');
            return;
        }
        
        // Add the menu item
        $hook = add_submenu_page(
            'woocommerce',
            'YITH Status Check',
            'YITH Status',
            'manage_options',
            'yith-status-check',
            'yith_status_check_page'
        );
        
        if ($hook === false) {
            yith_debug_log('Failed to add YITH Status menu item');
        } else {
            yith_debug_log('Successfully added YITH Status menu item');
        }
    } catch (Exception $e) {
        yith_debug_log('Error in yith_status_check_menu: ' . $e->getMessage());
    }
}

// Hook into admin_menu with a later priority
add_action('admin_menu', 'yith_status_check_menu', 99);

// Add a debug admin notice if we can't create the menu
function yith_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if we're on the WooCommerce admin page
    $screen = get_current_screen();
    if ($screen && $screen->parent_base === 'woocommerce') {
        // Check if our menu exists
        global $submenu;
        $menu_exists = false;
        
        if (isset($submenu['woocommerce'])) {
            foreach ($submenu['woocommerce'] as $item) {
                if (isset($item[2]) && $item[2] === 'yith-status-check') {
                    $menu_exists = true;
                    break;
                }
            }
        }
        
        if (!$menu_exists) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>YITH Status Check:</strong> The menu item could not be added. ';
            echo '<a href="' . admin_url('admin.php?page=yith-status-check') . '">Click here to access the status page directly</a>.</p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'yith_admin_notice');

// Status check page
function yith_status_check_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if WooCommerce is active
    $wc_active = class_exists('WooCommerce');
    
    // Check if YITH WooCommerce Subscription is active
    $ywsbs_active = class_exists('YITH_WC_Subscription');
    
    // Get product types
    $product_types = $wc_active ? wc_get_product_types() : array();
    $subscription_registered = isset($product_types['yith_subscription']);
    
    // Count subscription products
    $subscription_count = 0;
    if ($wc_active) {
        $subscription_products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_ywsbs_subscription',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        ));
        $subscription_count = count($subscription_products);
    }
    
    // Check for YITH plugin files
    $plugin_path = WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php';
    $plugin_active = is_plugin_active('yith-woocommerce-subscription-premium/init.php');
    $plugin_exists = file_exists($plugin_path);
    
    // Check for plugin data
    $plugin_data = $plugin_exists ? get_plugin_data($plugin_path) : false;
    ?>
    <div class="wrap">
        <h1>YITH WooCommerce Subscription Status</h1>
        
        <div class="card">
            <h2>Plugin Status</h2>
            <table class="widefat">
                <tr>
                    <th>WooCommerce Active:</th>
                    <td><?php echo $wc_active ? '✓' : '✖'; ?></td>
                </tr>
                <tr>
                    <th>YITH WooCommerce Subscription Active:</th>
                    <td><?php echo $ywsbs_active ? '✓' : '✖'; ?></td>
                </tr>
                <tr>
                    <th>Plugin File Exists:</th>
                    <td><?php echo $plugin_exists ? '✓' : '✖'; ?></td>
                </tr>
                <tr>
                    <th>Plugin Active:</th>
                    <td><?php echo $plugin_active ? '✓' : '✖'; ?></td>
                </tr>
                <?php if ($plugin_data) : ?>
                <tr>
                    <th>Plugin Version:</th>
                    <td><?php echo esc_html($plugin_data['Version']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($wc_active) : ?>
        <div class="card">
            <h2>Product Types</h2>
            <table class="widefat">
                <tr>
                    <th>Subscription Product Type Registered:</th>
                    <td><?php echo $subscription_registered ? '✓' : '✖'; ?></td>
                </tr>
                <tr>
                    <th>Subscription Products Found:</th>
                    <td><?php echo $subscription_count; ?></td>
                </tr>
            </table>
            
            <?php if (!empty($product_types)) : ?>
                <h3>Available Product Types</h3>
                <ul>
                    <?php foreach ($product_types as $type => $label) : ?>
                        <li><code><?php echo esc_html($type); ?></code> - <?php echo esc_html($label); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Debug Information</h2>
            <p>This is a read-only diagnostic tool. No changes are made to your site.</p>
            
            <h3>Next Steps</h3>
            <ol>
                <li>Verify the plugin is properly installed and activated</li>
                <li>Check for any error messages in the WordPress debug log</li>
                <li>If the plugin is active but not working, try deactivating and reactivating it</li>
                <li>Ensure your license is valid and activated for this domain</li>
            </ol>
        </div>
    </div>
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .card h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .widefat {
            border-collapse: collapse;
            margin-top: 1em;
            width: 100%;
            clear: both;
        }
        .widefat th, .widefat td {
            padding: 8px 10px;
            vertical-align: top;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .widefat th {
            font-weight: 600;
            width: 200px;
        }
        code {
            background: #f0f0f1;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
    <?php
}
