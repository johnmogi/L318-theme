<?php
/**
 * YITH WooCommerce Subscription Debug Helper
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('admin_init', 'lilac_check_yith_initialization');

function lilac_check_yith_initialization() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if YITH WooCommerce Subscription class exists
    if (!class_exists('YITH_WC_Subscription')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>YITH WooCommerce Subscription:</strong> Main plugin class not found. The plugin may not be properly activated or there might be a conflict.</p>
            </div>
            <?php
        });
        return;
    }

    // Check if WooCommerce product types are registered
    $product_types = wc_get_product_types();
    if (!isset($product_types['yith_subscription'])) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning">
                <p><strong>YITH WooCommerce Subscription:</strong> Subscription product type is not registered. Trying to reinitialize...</p>
            </div>
            <?php
        });
        
        // Try to manually initialize the product type
        add_action('init', function() {
            if (function_exists('YWSBS_Subscription_Product')) {
                YWSBS_Subscription_Product::get_instance();
            }
        }, 20);
    }
}

// Add debug information to the YITH diagnostics page
add_filter('yith_plugin_fw_get_field_template_path', 'lilac_add_yith_debug_info', 10, 2);

function lilac_add_yith_debug_info($template, $field) {
    if ($field['id'] === 'yith-system-info-php-info') {
        // Add our debug info after the PHP info
        add_action('yith_system_info_after_php_info', 'lilac_output_yith_debug_info');
    }
    return $template;
}

function lilac_output_yith_debug_info() {
    echo '<h2>YITH WooCommerce Subscription Debug Info</h2>';
    
    // Check if YITH WooCommerce Subscription is active
    $is_active = class_exists('YITH_WC_Subscription');
    echo '<p><strong>YITH WooCommerce Subscription Active:</strong> ' . ($is_active ? '✓' : '✖') . '</p>';
    
    if ($is_active) {
        // Check version
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php');
        echo '<p><strong>Plugin Version:</strong> ' . $plugin_data['Version'] . '</p>';
        
        // Check WooCommerce version compatibility
        $wc_version = defined('WC_VERSION') ? WC_VERSION : 'Not active';
        echo '<p><strong>WooCommerce Version:</strong> ' . $wc_version . '</p>';
        
        // Check if product type is registered
        $product_types = wc_get_product_types();
        $product_type_registered = isset($product_types['yith_subscription']);
        echo '<p><strong>Subscription Product Type Registered:</strong> ' . ($product_type_registered ? '✓' : '✖') . '</p>';
        
        // Check if there are any subscription products
        $subscription_products = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_ywsbs_subscription',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        ]);
        
        echo '<p><strong>Subscription Products Found:</strong> ' . (count($subscription_products) > 0 ? '✓' : '✖') . '</p>';
    }
}

// Add a test button to the admin bar for quick access to subscription checks
add_action('admin_bar_menu', 'lilac_add_yith_debug_toolbar', 999);

function lilac_add_yith_debug_toolbar($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $admin_bar->add_menu([
        'id'    => 'yith-debug',
        'title' => 'YITH Debug',
        'href'  => '#',
        'meta'  => [
            'title' => __('YITH WooCommerce Subscription Debug Tools'),
        ],
    ]);
    
    $admin_bar->add_menu([
        'id'     => 'yith-debug-check',
        'parent' => 'yith-debug',
        'title'  => 'Check Subscription Status',
        'href'   => admin_url('admin.php?page=yith_system_info&tab=php-info'),
        'meta'   => [
            'title' => __('Check YITH Subscription Status'),
            'class' => 'yith-debug-tool',
        ],
    ]);
    
    $admin_bar->add_menu([
        'id'     => 'yith-debug-test',
        'parent' => 'yith-debug',
        'title'  => 'Test Product Creation',
        'href'   => wp_nonce_url(admin_url('admin-ajax.php?action=lilac_test_subscription_creation'), 'test_subscription_creation'),
        'meta'   => [
            'title' => __('Test Subscription Product Creation'),
            'class' => 'yith-debug-tool',
        ],
    ]);
}

// AJAX handler for testing subscription product creation
add_action('wp_ajax_lilac_test_subscription_creation', 'lilac_test_subscription_creation');

function lilac_test_subscription_creation() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    check_ajax_referer('test_subscription_creation');
    
    // Check if YITH WooCommerce Subscription is active
    if (!class_exists('YITH_WC_Subscription')) {
        wp_send_json_error('YITH WooCommerce Subscription is not active');
    }
    
    // Create a test subscription product
    $product = new WC_Product_Subscription();
    $product->set_name('Test Subscription Product');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_price(9.99);
    $product->set_regular_price(9.99);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    
    // Set subscription properties
    $product->update_meta_data('_ywsbs_subscription', 'yes');
    $product->update_meta_data('_ywsbs_price_is_per', '1');
    $product->update_meta_data('_ywsbs_price_time_option', 'months');
    $product->update_meta_data('_ywsbs_fee', '0');
    $product->update_meta_data('_ywsbs_trial_per', '1');
    $product->update_meta_data('_ywsbs_trial_time_option', 'weeks');
    $product->update_meta_data('_ywsbs_max_length', '0');
    
    try {
        $product_id = $product->save();
        
        if (is_wp_error($product_id)) {
            throw new Exception($product_id->get_error_message());
        }
        
        wp_send_json_success([
            'message' => 'Test subscription product created successfully!',
            'product_id' => $product_id,
            'edit_link' => get_edit_post_link($product_id, 'url')
        ]);
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Failed to create test subscription product',
            'error' => $e->getMessage()
        ]);
    }
}

// Add a test page under WooCommerce menu
add_action('admin_menu', 'lilac_add_yith_test_page');

function lilac_add_yith_test_page() {
    add_submenu_page(
        'woocommerce',
        'YITH Subscription Test',
        'YITH Test',
        'manage_options',
        'yith-subscription-test',
        'lilac_render_yith_test_page'
    );
}

function lilac_render_yith_test_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1>YITH WooCommerce Subscription Test</h1>
        
        <div class="card">
            <h2>Plugin Status</h2>
            <p><strong>YITH WooCommerce Subscription Active:</strong> 
                <?php echo class_exists('YITH_WC_Subscription') ? '✓' : '✖'; ?>
            </p>
            
            <?php if (class_exists('YITH_WC_Subscription')) : ?>
                <?php $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php'); ?>
                <p><strong>Plugin Version:</strong> <?php echo $plugin_data['Version']; ?></p>
                <p><strong>WooCommerce Version:</strong> <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not active'; ?></p>
                
                <?php 
                $product_types = wc_get_product_types();
                $product_type_registered = isset($product_types['yith_subscription']);
                ?>
                <p><strong>Subscription Product Type Registered:</strong> 
                    <?php echo $product_type_registered ? '✓' : '✖'; ?>
                </p>
                
                <h3>Actions</h3>
                <p>
                    <button id="test-subscription-creation" class="button button-primary">
                        Test Create Subscription Product
                    </button>
                    <span id="test-result" style="margin-left: 10px;"></span>
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#test-subscription-creation').on('click', function() {
                        var $button = $(this);
                        var $result = $('#test-result');
                        
                        $button.prop('disabled', true);
                        $result.text('Creating test product...').removeClass('error success');
                        
                        $.post(ajaxurl, {
                            action: 'lilac_test_subscription_creation',
                            _ajax_nonce: '<?php echo wp_create_nonce('test_subscription_creation'); ?>'
                        })
                        .done(function(response) {
                            if (response.success) {
                                $result.text('Success! ' + response.data.message)
                                    .addClass('success')
                                    .append(' <a href="' + response.data.edit_link + '">View Product</a>');
                            } else {
                                $result.text('Error: ' + response.data.message)
                                    .addClass('error');
                                
                                if (response.data.error) {
                                    $result.append('<br><pre style="white-space: pre-wrap;">' + response.data.error + '</pre>');
                                }
                            }
                        })
                        .fail(function(xhr, status, error) {
                            $result.text('Request failed: ' + error).addClass('error');
                        })
                        .always(function() {
                            $button.prop('disabled', false);
                        });
                    });
                });
                </script>
                
                <style>
                .success { color: #46b450; font-weight: bold; }
                .error { color: #dc3232; font-weight: bold; }
                .card { 
                    background: #fff; 
                    padding: 20px; 
                    margin-top: 20px; 
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    max-width: 800px;
                }
                </style>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Add a system status check to the WooCommerce system status page
add_action('woocommerce_system_status_report', 'lilac_add_yith_status_check');

function lilac_add_yith_status_check() {
    ?>
    <table class="wc_status_table widefat" cellspacing="0">
        <thead>
            <tr>
                <th colspan="3" data-export-label="YITH WooCommerce Subscription">
                    <h2>YITH WooCommerce Subscription</h2>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td data-export-label="Plugin Active">Plugin Active:</td>
                <td class="help"><?php echo wc_help_tip('Is YITH WooCommerce Subscription active?'); ?></td>
                <td>
                    <?php if (class_exists('YITH_WC_Subscription')) : ?>
                        <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                    <?php else : ?>
                        <mark class="error"><span class="dashicons dashicons-no"></span></mark>
                    <?php endif; ?>
                </td>
            </tr>
            
            <?php if (class_exists('YITH_WC_Subscription')) : ?>
                <?php $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php'); ?>
                <tr>
                    <td data-export-label="Plugin Version">Plugin Version:</td>
                    <td class="help"><?php echo wc_help_tip('The version of YITH WooCommerce Subscription installed.'); ?></td>
                    <td><?php echo esc_html($plugin_data['Version']); ?></td>
                </tr>
                
                <?php 
                $product_types = wc_get_product_types();
                $product_type_registered = isset($product_types['yith_subscription']);
                ?>
                <tr>
                    <td data-export-label="Subscription Product Type">Subscription Product Type:</td>
                    <td class="help"><?php echo wc_help_tip('Is the subscription product type registered?'); ?></td>
                    <td>
                        <?php if ($product_type_registered) : ?>
                            <mark class="yes"><span class="dashicons dashicons-yes"></span> Registered</mark>
                        <?php else : ?>
                            <mark class="error"><span class="dashicons dashicons-warning"></span> Not Registered</mark>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <?php 
                $subscription_products = get_posts([
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'meta_query' => [
                        [
                            'key' => '_ywsbs_subscription',
                            'value' => 'yes',
                            'compare' => '='
                        ]
                    ]
                ]);
                ?>
                <tr>
                    <td data-export-label="Subscription Products">Subscription Products:</td>
                    <td class="help"><?php echo wc_help_tip('Are there any subscription products?'); ?></td>
                    <td>
                        <?php if (count($subscription_products) > 0) : ?>
                            <mark class="yes"><span class="dashicons dashicons-yes"></span> Found <?php echo count($subscription_products); ?> product(s)</mark>
                        <?php else : ?>
                            <mark class="no"><span class="dashicons dashicons-no"></span> None found</mark>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}
