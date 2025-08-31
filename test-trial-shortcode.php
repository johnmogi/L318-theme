<?php
/**
 * Template Name: Test Trial Shortcode
 */

get_header();
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    <h1>Test Trial Shortcode</h1>
    
    <h2>Shortcode Output:</h2>
    <div style="border: 2px dashed #ccc; padding: 1rem; margin: 1rem 0;">
        <?php echo do_shortcode('[lilac_trial_activation]'); ?>
    </div>
    
    <h2>Debug Information:</h2>
    <pre style="background: #f5f5f5; padding: 1rem; overflow-x: auto;">
        <?php
        // Check if user is logged in
        echo "User logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "\n";
        
        if (is_user_logged_in()) {
            echo "User ID: " . get_current_user_id() . "\n";
            
            // Check if function exists
            echo "Function 'lilac_has_active_subscription' exists: " . (function_exists('lilac_has_active_subscription') ? 'Yes' : 'No') . "\n";
            
            // Check if user has active subscription
            if (function_exists('lilac_has_active_subscription')) {
                echo "User has active subscription: " . (lilac_has_active_subscription() ? 'Yes' : 'No') . "\n";
            }
            
            // Check if trial product exists
            if (function_exists('lilac_get_trial_product_id')) {
                $trial_product_id = lilac_get_trial_product_id();
                echo "Trial product ID: " . ($trial_product_id ? $trial_product_id : 'Not found') . "\n";
                
                if ($trial_product_id) {
                    $product = wc_get_product($trial_product_id);
                    echo "Trial product exists: " . ($product ? 'Yes' : 'No') . "\n";
                    
                    if ($product) {
                        echo "Product type: " . $product->get_type() . "\n";
                        echo "Trial enabled: " . ($product->get_meta('_ywsbs_enable_trial') === 'yes' ? 'Yes' : 'No') . "\n";
                    }
                }
            }
        }
        
        // Check if shortcode exists
        global $shortcode_tags;
        echo "Shortcode 'lilac_trial_activation' registered: " . (array_key_exists('lilac_trial_activation', $shortcode_tags) ? 'Yes' : 'No') . "\n";
        
        // Check if file exists
        $shortcode_file = get_stylesheet_directory() . '/inc/shortcodes/trial-activation.php';
        echo "Shortcode file exists: " . (file_exists($shortcode_file) ? 'Yes' : 'No') . "\n";
        
        // Check if file is readable
        echo "Shortcode file is readable: " . (is_readable($shortcode_file) ? 'Yes' : 'No') . "\n";
        
        // Check for any PHP errors in the shortcode file
        if (file_exists($shortcode_file)) {
            $shortcode_content = file_get_contents($shortcode_file);
            if (strpos($shortcode_content, '<?php') === false) {
                echo "Warning: Shortcode file doesn't start with PHP opening tag\n";
            }
        }
        
        // Check if WooCommerce is active
        echo "WooCommerce active: " . (class_exists('WooCommerce') ? 'Yes' : 'No') . "\n";
        
        // Check if YITH WooCommerce Subscription is active
        echo "YITH WooCommerce Subscription active: " . (class_exists('YITH_YWSBS_Subscription') ? 'Yes' : 'No') . "\n";
        ?>
    </pre>
</div>

<?php
get_footer();
