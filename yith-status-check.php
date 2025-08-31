<?php
/**
 * YITH WooCommerce Subscription Status Checker
 * 
 * This file helps diagnose issues with the YITH WooCommerce Subscription plugin.
 * Access it directly in your browser to see the status report.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit('Direct access not allowed');
}

// Only allow administrators
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>YITH WooCommerce Subscription Status</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .status-box { 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 4px; 
            border-left: 4px solid #dc3232; 
            background: #f7f7f7; 
        }
        .status-box.good { border-left-color: #46b450; }
        .status-box.warning { border-left-color: #ffb900; }
        .section { margin: 30px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .warning { color: #ffb900; }
    </style>
</head>
<body>
    <div class="container">
        <h1>YITH WooCommerce Subscription Status Check</h1>
        <p>This tool helps diagnose issues with the YITH WooCommerce Subscription plugin.</p>
        
        <?php
        // Check if WooCommerce is active
        $woocommerce_active = class_exists('WooCommerce');
        ?>
        
        <div class="section">
            <h2>1. WooCommerce Status</h2>
            <div class="status-box <?php echo $woocommerce_active ? 'good' : 'error'; ?>">
                <p><strong>WooCommerce:</strong> 
                    <?php 
                    if ($woocommerce_active) {
                        echo '<span class="success">Active (v' . WC_VERSION . ')</span>';
                    } else {
                        echo '<span class="error">Not active or not installed</span>';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <?php
        // Check YITH Subscription plugin status
        $yith_plugin_active = class_exists('YITH_WC_Subscription');
        $yith_plugin_installed = file_exists(WP_PLUGIN_DIR . '/yith-woocommerce-subscription/init.php') || 
                                 file_exists(WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php');
        
        // Check if plugin is active but class not found (potential fatal error)
        $plugin_path = 'yith-woocommerce-subscription/init.php';
        $premium_plugin_path = 'yith-woocommerce-subscription-premium/init.php';
        $is_plugin_active = is_plugin_active($plugin_path) || is_plugin_active($premium_plugin_path);
        $class_exists = class_exists('YITH_WC_Subscription');
        $plugin_has_error = $is_plugin_active && !$class_exists;
        ?>
        
        <div class="section">
            <h2>2. YITH WooCommerce Subscription Status</h2>
            <div class="status-box <?php echo $yith_plugin_active ? 'good' : ($yith_plugin_installed ? 'warning' : 'error'); ?>">
                <p><strong>Plugin Status:</strong> 
                    <?php 
                    if ($yith_plugin_active) {
                        echo '<span class="success">Active</span>';
                        if (defined('YITH_YWSBS_VERSION')) {
                            echo ' (v' . YITH_YWSBS_VERSION . ')';
                        }
                    } elseif ($plugin_has_error) {
                        echo '<span class="error">Plugin active but classes not found! Potential fatal error during plugin load.</span>';
                    } elseif ($yith_plugin_installed) {
                        echo '<span class="warning">Installed but not active</span>';
                    } else {
                        echo '<span class="error">Not installed</span>';
                    }
                    ?>
                </p>
                
                <?php if ($plugin_has_error): ?>
                <p><strong>Possible Issues:</strong></p>
                <ul>
                    <li>The plugin might be encountering a fatal error during initialization</li>
                    <li>Check your PHP error log for any errors related to YITH WooCommerce Subscription</li>
                    <li>Try deactivating and reactivating the plugin</li>
                    <li>Make sure your WordPress and WooCommerce versions are compatible with the plugin</li>
                </ul>
                <?php endif; ?>
            </div>
            
            <h3>Plugin Files Check</h3>
            <table>
                <tr>
                    <th>File</th>
                    <th>Status</th>
                </tr>
                <?php
                $plugin_files = [
                    'Main Plugin File' => WP_PLUGIN_DIR . '/yith-woocommerce-subscription/init.php',
                    'Premium Version' => WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php',
                    'Main Class' => WP_PLUGIN_DIR . '/yith-woocommerce-subscription/includes/class-yith-wc-subscription.php',
                    'Premium Class' => WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/includes/class-yith-wc-subscription.php',
                ];
                
                foreach ($plugin_files as $name => $file) {
                    $exists = file_exists($file);
                    echo '<tr>';
                    echo '<td>' . esc_html($name) . '</td>';
                    echo '<td class="' . ($exists ? 'success' : 'error') . '">' . 
                         ($exists ? 'Found' : 'Not found') . 
                         ($exists ? ' (' . date('Y-m-d H:i:s', filemtime($file)) . ')' : '') . 
                         '</td>';
                    echo '</tr>';
                }
                ?>
            </table>
        </div>
        
        <?php
        // Check if we can create a subscription product
        $can_create_product = false;
        if ($yith_plugin_active) {
            $can_create_product = true;
            
            // Check if product type exists
            $product_types = wc_get_product_types();
            $subscription_product_type_exists = array_key_exists('ywsbs_subscription', $product_types);
            
            // Check if we can create a test product
            $test_product = null;
            $test_product_id = 0;
            $test_product_created = false;
            
            if ($subscription_product_type_exists && $yith_plugin_active) {
                // Try to create a test product
                try {
                    $test_product = new WC_Product_Subscription();
                    $test_product->set_name('Test Subscription Product');
                    $test_product->set_status('draft');
                    $test_product->set_catalog_visibility('hidden');
                    $test_product->set_regular_price(10);
                    $test_product_id = $test_product->save();
                    $test_product_created = !is_wp_error($test_product_id) && $test_product_id > 0;
                    
                    // Clean up
                    if ($test_product_created) {
                        wp_delete_post($test_product_id, true);
                    }
                } catch (Exception $e) {
                    $test_product_error = $e->getMessage();
                }
            }
        }
        ?>
        
        <div class="section">
            <h2>3. Subscription Product Test</h2>
            <div class="status-box <?php echo $can_create_product ? 'good' : 'error'; ?>">
                <p><strong>Subscription Product Support:</strong> 
                    <?php 
                    if ($can_create_product) {
                        echo '<span class="success">Available</span>';
                    } else {
                        echo '<span class="error">Not available - YITH WooCommerce Subscription plugin required</span>';
                    }
                    ?>
                </p>
                
                <?php if ($can_create_product): ?>
                <p><strong>Subscription Product Type:</strong> 
                    <?php 
                    if ($subscription_product_type_exists) {
                        echo '<span class="success">Registered (ywsbs_subscription)</span>';
                    } else {
                        echo '<span class="error">Not registered in WooCommerce</span>';
                    }
                    ?>
                </p>
                
                <p><strong>Test Product Creation:</strong> 
                    <?php 
                    if ($test_product_created) {
                        echo '<span class="success">Successfully created and deleted a test subscription product</span>';
                    } else {
                        echo '<span class="error">Failed to create test product';
                        if (isset($test_product_error)) {
                            echo ': ' . esc_html($test_product_error);
                        }
                        echo '</span>';
                    }
                    ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($plugin_has_error): ?>
        <div class="section">
            <h2>4. Error Resolution</h2>
            <div class="status-box warning">
                <h3>Recommended Steps to Fix Plugin Loading Issues:</h3>
                <ol>
                    <li>Check your PHP error log for any fatal errors related to the YITH WooCommerce Subscription plugin.</li>
                    <li>Deactivate and reactivate the YITH WooCommerce Subscription plugin.</li>
                    <li>If the issue persists, try reinstalling the plugin:</li>
                    <ol type="a">
                        <li>Deactivate the plugin</li>
                        <li>Delete the plugin (don't worry, your subscription data is safe in the database)</li>
                        <li>Reinstall the plugin from the WordPress repository or upload the plugin files again</li>
                        <li>Reactivate the plugin</li>
                    </ol>
                    <li>Make sure your WordPress, WooCommerce, and YITH WooCommerce Subscription versions are all compatible.</li>
                    <li>Check for conflicts with other plugins by temporarily deactivating other plugins and testing.</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>5. System Information</h2>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>WordPress Version</td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>WooCommerce Version</td>
                    <td><?php echo $woocommerce_active ? WC_VERSION : 'Not active'; ?></td>
                </tr>
                <tr>
                    <td>YITH WooCommerce Subscription Version</td>
                    <td><?php echo defined('YITH_YWSBS_VERSION') ? YITH_YWSBS_VERSION : 'Not detected'; ?></td>
                </tr>
                <tr>
                    <td>Active Theme</td>
                    <td>
                        <?php 
                        $theme = wp_get_theme();
                        echo $theme->get('Name') . ' v' . $theme->get('Version');
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>Next Steps</h2>
            <div class="status-box good">
                <?php if ($yith_plugin_active && $subscription_product_type_exists && $test_product_created): ?>
                    <p>Your YITH WooCommerce Subscription plugin appears to be working correctly. You can now proceed with creating trial subscription products.</p>
                <?php else: ?>
                    <p>Please resolve the issues shown above before proceeding with trial subscription setup.</p>
                <?php endif; ?>
                
                <?php if ($plugin_has_error): ?>
                    <p><strong>Important:</strong> The plugin appears to be active but not functioning correctly. Please resolve this issue first.</p>
                <?php endif; ?>
                
                <p><a href="<?php echo admin_url('admin.php?page=yith_woocommerce_subscription'); ?>">Go to YITH WooCommerce Subscription Settings</a></p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Output the buffer and clear it
ob_end_flush();
