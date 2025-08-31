<?php
/**
 * Plugin Status Checker
 * 
 * This script checks the status of YITH WooCommerce Subscription and other related plugins.
 * Access this file directly in your browser to see the results.
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        die('Could not find wp-load.php');
    }
}

// Only allow administrators to view this page
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Function to check if a plugin is active
function is_plugin_active_check($plugin_path) {
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    return is_plugin_active($plugin_path);
}

// Get all plugins
$all_plugins = get_plugins();
$active_plugins = (array) get_option('active_plugins', []);
$network_active_plugins = [];

if (is_multisite()) {
    $network_active_plugins = array_keys((array) get_site_option('active_sitewide_plugins', []));
}

// Check YITH WooCommerce Subscription status
$yith_plugins = [];
$yith_subscription_found = false;

foreach ($all_plugins as $plugin_path => $plugin_data) {
    $is_active = in_array($plugin_path, $active_plugins) || in_array($plugin_path, $network_active_plugins);
    $is_yith = strpos(strtolower($plugin_path), 'yith') !== false || 
               strpos(strtolower($plugin_data['Name']), 'yith') !== false;
    
    if ($is_yith) {
        $status = $is_active ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>';
        $yith_plugins[] = [
            'name' => $plugin_data['Name'],
            'path' => $plugin_path,
            'status' => $status,
            'version' => $plugin_data['Version']
        ];
        
        if (strpos($plugin_path, 'yith-woocommerce-subscription') !== false) {
            $yith_subscription_found = true;
        }
    }
}

// Check for YITH constants and classes
$yith_constants = [
    'YITH_YWSBS_VERSION' => defined('YITH_YWSBS_VERSION') ? YITH_YWSBS_VERSION : 'Not defined',
    'YITH_YWSBS_INIT' => defined('YITH_YWSBS_INIT') ? YITH_YWSBS_INIT : 'Not defined',
    'YITH_YWSBS_PREMIUM' => defined('YITH_YWSBS_PREMIUM') ? YITH_YWSBS_PREMIUM : 'Not defined',
    'YITH_YWSBS_FILE' => defined('YITH_YWSBS_FILE') ? YITH_YWSBS_FILE : 'Not defined',
];

$yith_classes = [
    'YITH_WC_Subscription' => class_exists('YITH_WC_Subscription') ? 'Exists' : 'Not found',
    'YITH_YWSBS_Subscription' => class_exists('YITH_YWSBS_Subscription') ? 'Exists' : 'Not found',
    'YWSBS_Subscription' => class_exists('YWSBS_Subscription') ? 'Exists' : 'Not found',
];

// Check if the plugin's main file is loaded
$plugin_loaded = false;
$plugin_file = '';

if (defined('YITH_YWSBS_FILE') && file_exists(YITH_YWSBS_FILE)) {
    $plugin_loaded = true;
    $plugin_file = YITH_YWSBS_FILE;
}

// Output the results
?>
<!DOCTYPE html>
<html>
<head>
    <title>YITH WooCommerce Subscription Status</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .status-box { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 4px; 
            background-color: #f9f9f9;
        }
        .status-good { border-left: 4px solid #46b450; }
        .status-bad { border-left: 4px solid #dc3232; }
        .status-warning { border-left: 4px solid #ffb900; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .warning { color: #ffb900; }
    </style>
</head>
<body>
    <h1>YITH WooCommerce Subscription Status</h1>
    
    <div class="status-box <?php echo $yith_subscription_found ? 'status-good' : 'status-bad'; ?>">
        <h2>Plugin Status</h2>
        <?php if ($yith_subscription_found): ?>
            <p class="success">✓ YITH WooCommerce Subscription plugin is installed.</p>
        <?php else: ?>
            <p class="error">✗ YITH WooCommerce Subscription plugin is NOT installed or could not be found.</p>
        <?php endif; ?>
        
        <?php if ($plugin_loaded): ?>
            <p class="success">✓ Plugin main file is loaded: <?php echo esc_html($plugin_file); ?></p>
        <?php else: ?>
            <p class="error">✗ Plugin main file is NOT loaded. The plugin may be installed but not activated.</p>
        <?php endif; ?>
    </div>
    
    <div class="status-box">
        <h2>YITH Constants</h2>
        <table>
            <tr>
                <th>Constant</th>
                <th>Value</th>
            </tr>
            <?php foreach ($yith_constants as $constant => $value): ?>
                <tr>
                    <td><code><?php echo $constant; ?></code></td>
                    <td><code><?php echo esc_html($value); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="status-box">
        <h2>YITH Classes</h2>
        <table>
            <tr>
                <th>Class</th>
                <th>Status</th>
            </tr>
            <?php foreach ($yith_classes as $class => $status): ?>
                <tr>
                    <td><code><?php echo $class; ?></code></td>
                    <td class="<?php echo $status === 'Exists' ? 'success' : 'error'; ?>">
                        <?php echo $status; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <?php if (!empty($yith_plugins)): ?>
        <div class="status-box">
            <h2>YITH Plugins</h2>
            <table>
                <tr>
                    <th>Plugin</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Path</th>
                </tr>
                <?php foreach ($yith_plugins as $plugin): ?>
                    <tr>
                        <td><?php echo esc_html($plugin['name']); ?></td>
                        <td><?php echo esc_html($plugin['version']); ?></td>
                        <td><?php echo $plugin['status']; ?></td>
                        <td><code><?php echo esc_html($plugin['path']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="status-box">
        <h2>Active Plugins</h2>
        <ul>
            <?php foreach ($active_plugins as $plugin): ?>
                <li><code><?php echo esc_html($plugin); ?></code></li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (!empty($network_active_plugins)): ?>
            <h3>Network Active Plugins</h3>
            <ul>
                <?php foreach ($network_active_plugins as $plugin): ?>
                    <li><code><?php echo esc_html($plugin); ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="status-box">
        <h2>PHP Information</h2>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
        <p>WooCommerce Active: <?php echo class_exists('WooCommerce') ? 'Yes' : 'No'; ?></p>
    </div>
</body>
</html>
