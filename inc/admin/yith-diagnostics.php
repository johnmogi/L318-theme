<?php
/**
 * YITH WooCommerce Subscription Diagnostics
 * 
 * Adds an admin page to check the status of YITH WooCommerce Subscription
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add admin menu item for YITH Diagnostics
 */
function lilac_add_yith_diagnostics_page() {
    add_submenu_page(
        'woocommerce',
        'YITH Subscription Diagnostics',
        'YITH Diagnostics',
        'manage_options',
        'yith-diagnostics',
        'lilac_render_yith_diagnostics_page'
    );
}
add_action('admin_menu', 'lilac_add_yith_diagnostics_page', 99);

/**
 * Render the diagnostics page
 */
function lilac_render_yith_diagnostics_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Run tests
    $tests = lilac_run_yith_diagnostics();
    
    // Output the page
    ?>
    <div class="wrap">
        <h1>YITH WooCommerce Subscription Diagnostics</h1>
        
        <div class="notice notice-info">
            <p>This page helps diagnose issues with the YITH WooCommerce Subscription plugin.</p>
        </div>
        
        <div class="card">
            <h2>Plugin Status</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <th>WordPress Version</th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th>WooCommerce Active</th>
                        <td class="<?php echo $tests['woocommerce_active'] ? 'status-active' : 'status-error'; ?>">
                            <?php echo $tests['woocommerce_active'] ? '✓ Active' : '✖ Not Active'; ?>
                            <?php if ($tests['woocommerce_active']) echo '(v' . WC_VERSION . ')'; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>YITH Subscription Active</th>
                        <td class="<?php echo $tests['yith_active'] ? 'status-active' : 'status-error'; ?>">
                            <?php echo $tests['yith_active'] ? '✓ Active' : '✖ Not Active'; ?>
                            <?php if ($tests['yith_active'] && defined('YITH_YWSBS_VERSION')) echo '(v' . YITH_YWSBS_VERSION . ')'; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Subscription Product Type</th>
                        <td class="<?php echo $tests['subscription_type_exists'] ? 'status-active' : 'status-error'; ?>">
                            <?php echo $tests['subscription_type_exists'] ? '✓ Registered' : '✖ Not Registered'; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if ($tests['woocommerce_active'] && $tests['yith_active']): ?>
        <div class="card">
            <h2>Subscription Products</h2>
            <?php if ($tests['subscription_products']): ?>
                <p>Found <?php echo count($tests['subscription_products']); ?> subscription products:</p>
                <ul>
                    <?php foreach ($tests['subscription_products'] as $product): ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($product->get_id()); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </a> 
                            (ID: <?php echo $product->get_id(); ?>, Status: <?php echo $product->get_status(); ?>)
                            <?php 
                            if (method_exists($product, 'get_ywsbs_trial_per')) {
                                $trial_days = $product->get_ywsbs_trial_per();
                                if ($trial_days > 0) {
                                    echo ' - Trial: ' . $trial_days . ' days';
                                }
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No subscription products found.</p>
            <?php endif; ?>
            
            <h3>Test Product Creation</h3>
            <?php if ($tests['test_product_created']): ?>
                <div class="notice notice-success">
                    <p>✓ Successfully created and deleted a test subscription product.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-error">
                    <p>✖ Failed to create test product. Error: <?php echo esc_html($tests['test_product_error']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($tests['has_errors']): ?>
        <div class="card">
            <h2>Issues Detected</h2>
            <div class="notice notice-error">
                <?php foreach ($tests['errors'] as $error): ?>
                    <p><?php echo wp_kses_post($error); ?></p>
                <?php endforeach; ?>
            </div>
            
            <h3>Recommended Actions</h3>
            <ol>
                <li>Check your PHP error log for any fatal errors related to the YITH WooCommerce Subscription plugin.</li>
                <li>Deactivate and reactivate the YITH WooCommerce Subscription plugin.</li>
                <li>Make sure your WordPress, WooCommerce, and YITH WooCommerce Subscription versions are all compatible.</li>
                <li>Check for conflicts with other plugins by temporarily deactivating other plugins and testing.</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin: 20px 0;
                padding: 20px;
            }
            .status-active { color: #46b450; font-weight: bold; }
            .status-error { color: #dc3232; font-weight: bold; }
            .notice { margin: 10px 0; padding: 10px; }
            .notice p { margin: 0.5em 0; }
        </style>
    </div>
    <?php
}

/**
 * Run diagnostics tests
 */
function lilac_run_yith_diagnostics() {
    $tests = array(
        'woocommerce_active' => false,
        'yith_active' => false,
        'subscription_type_exists' => false,
        'subscription_products' => array(),
        'test_product_created' => false,
        'test_product_error' => '',
        'has_errors' => false,
        'errors' => array()
    );
    
    // Check if WooCommerce is active
    $tests['woocommerce_active'] = class_exists('WooCommerce');
    
    if (!$tests['woocommerce_active']) {
        $tests['errors'][] = 'WooCommerce is not active. YITH WooCommerce Subscription requires WooCommerce to be installed and activated.';
        $tests['has_errors'] = true;
        return $tests;
    }
    
    // Check if YITH Subscription is active
    $tests['yith_active'] = class_exists('YITH_WC_Subscription');
    
    if (!$tests['yith_active']) {
        $tests['errors'][] = 'YITH WooCommerce Subscription is not active or not properly installed.';
        $tests['has_errors'] = true;
        return $tests;
    }
    
    // Check if subscription product type exists
    $product_types = wc_get_product_types();
    $tests['subscription_type_exists'] = array_key_exists('ywsbs_subscription', $product_types);
    
    if (!$tests['subscription_type_exists']) {
        $tests['errors'][] = 'Subscription product type is not registered. The plugin may not be properly initialized.';
        $tests['has_errors'] = true;
    }
    
    // Get existing subscription products
    $args = array(
        'type' => 'ywsbs_subscription',
        'limit' => 50,
        'status' => 'publish',
    );
    $tests['subscription_products'] = wc_get_products($args);
    
    // Try to create a test product (will be deleted immediately)
    if ($tests['subscription_type_exists']) {
        try {
            $product = new WC_Product_Subscription();
            $product->set_name('Test Subscription Product (Will be deleted)');
            $product->set_status('draft');
            $product->set_catalog_visibility('hidden');
            $product->set_regular_price(10);
            $product_id = $product->save();
            
            if (!is_wp_error($product_id) && $product_id > 0) {
                $tests['test_product_created'] = true;
                // Clean up
                wp_delete_post($product_id, true);
            } else {
                $tests['test_product_error'] = is_wp_error($product_id) ? $product_id->get_error_message() : 'Unknown error';
                $tests['errors'][] = 'Failed to create test subscription product: ' . $tests['test_product_error'];
                $tests['has_errors'] = true;
            }
        } catch (Exception $e) {
            $tests['test_product_error'] = $e->getMessage();
            $tests['errors'][] = 'Exception when creating test product: ' . $e->getMessage();
            $tests['has_errors'] = true;
        }
    }
    
    return $tests;
}
