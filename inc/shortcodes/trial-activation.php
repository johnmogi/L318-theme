<?php
/**
 * Trial Activation Shortcode
 * 
 * Shortcode: [lilac_trial_activation]
 * 
 * Displays a trial activation button if the user doesn't have an active subscription
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Create a trial subscription product if it doesn't exist
 */
function lilac_maybe_create_trial_product() {
    // Check if YITH WooCommerce Subscription is active
    if (!class_exists('YITH_WC_Subscription')) {
        error_log('YITH WooCommerce Subscription is not active');
        return false;
    }

    // Check if we already have a trial product
    $trial_product_id = get_option('lilac_trial_product_id');
    if ($trial_product_id && 'publish' === get_post_status($trial_product_id)) {
        return $trial_product_id;
    }

    // Create a new product
    $product = new WC_Product_Subscription();
    
    // Set basic product data
    $product->set_name('14-Day Free Trial');
    $product->set_slug('14-day-free-trial');
    $product->set_status('publish');
    $product->set_catalog_visibility('hidden'); // Hide from shop/category pages
    $product->set_virtual(true);
    $product->set_downloadable(false);
    $product->set_sold_individually(true);
    $product->set_price(0);
    $product->set_regular_price(0);
    
    // Set subscription-specific data
    $product->update_meta_data('_ywsbs_subscription', 'yes');
    $product->update_meta_data('_ywsbs_price_is_per', 1);
    $product->update_meta_data('_ywsbs_price_time_option', 'months');
    $product->update_meta_data('_ywsbs_max_length', 0); // 0 = unlimited
    
    // Enable trial period (14 days)
    $product->update_meta_data('_ywsbs_enable_trial', 'yes');
    $product->update_meta_data('_ywsbs_trial_per', 14);
    $product->update_meta_data('_ywsbs_trial_time_option', 'days');
    
    // Set regular price after trial (this will be charged after trial ends)
    $product->update_meta_data('_price', '19.99');
    $product->update_meta_data('_ywsbs_price_is_per_after_trial', 1);
    $product->update_meta_data('_ywsbs_price_time_option_after_trial', 'months');
    
    // Save the product
    $product_id = $product->save();
    
    if (!is_wp_error($product_id)) {
        update_option('lilac_trial_product_id', $product_id);
        return $product_id;
    }
    
    return false;
}

// Hook to create trial product when admin is viewing the site
add_action('init', function() {
    if (current_user_can('manage_options')) {
        lilac_maybe_create_trial_product();
    }
});

/**
 * Check if user has active subscription
 */
function lilac_has_active_subscription($user_id = null) {
    if (empty($user_id)) {
        $user_id = get_current_user_id();
    }
    
    if (empty($user_id)) {
        return false;
    }
    
    // First try YITH's method if available
    if (function_exists('ywsbs_has_active_subscriptions') && ywsbs_has_active_subscriptions($user_id)) {
        return true;
    }
    
    // Fallback: Check if user has any active subscriptions in the database
    $subscriptions = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => 'wc-active',
        'type'        => 'ywsbs_subscription',
        'return'      => 'ids',
        'limit'       => 1,
    ]);
    
    return !empty($subscriptions);
}

/**
 * Get the trial subscription product ID
 */
function lilac_get_trial_product_id() {
    // Try to create the trial product if it doesn't exist
    $product_id = lilac_maybe_create_trial_product();
    
    if ($product_id) {
        return $product_id;
    }
    
    // Fallback: Check if we have a stored option
    $trial_product_id = get_option('lilac_trial_product_id');
    
    if ($trial_product_id && 'publish' === get_post_status($trial_product_id)) {
        return $trial_product_id;
    }
    
    // Fallback: Try to find any subscription with trial enabled
    $products = wc_get_products([
        'type'  => 'ywsbs-subscription',
        'limit' => 1,
        'meta_query' => [
            [
                'key'     => '_ywsbs_enable_trial',
                'value'   => 'yes',
                'compare' => '=',
            ],
        ],
    ]);
    
    if (!empty($products)) {
        $trial_product = reset($products);
        update_option('lilac_trial_product_id', $trial_product->get_id());
        return $trial_product->get_id();
    }
    
    return false;
}

/**
 * Trial activation shortcode handler
 */
function lilac_trial_activation_shortcode($atts = []) {
    // Only for logged in users
    if (!is_user_logged_in()) {
        return '<div class="lilac-alert lilac-alert-info">' . 
               __('Please log in to activate your trial.', 'hello-theme-child') . 
               ' <a href="' . esc_url(wp_login_url(get_permalink())) . '">' . 
               __('Login', 'hello-theme-child') . '</a> ' . 
               __('or', 'hello-theme-child') . 
               ' <a href="' . esc_url(wp_registration_url()) . '">' . 
               __('Register', 'hello-theme-child') . '</a>' . 
               '</div>';
    }
    
    // Check if user already has an active subscription
    if (lilac_has_active_subscription()) {
        return '<div class="lilac-alert lilac-alert-success">' . 
               __('You already have an active subscription. Thank you for being a valued member!', 'hello-theme-child') . 
               '</div>';
    }
    
    // Get the trial product
    $product_id = lilac_get_trial_product_id();
    
    if (!$product_id) {
        if (current_user_can('manage_options')) {
            return '<div class="lilac-alert lilac-alert-warning">' . 
                   __('No trial subscription product found. Please create a subscription product with trial enabled.', 'hello-theme-child') . 
                   '</div>';
        }
        return ''; // Don't show anything to regular users if no trial product
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('ywsbs-subscription')) {
        return '<div class="lilac-alert lilac-alert-error">' . 
               __('Trial subscription is not available at the moment.', 'hello-theme-child') . 
               '</div>';
    }
    
    // Get trial details
    $trial_length = $product->get_meta('_ywsbs_trial_per');
    $trial_period = $product->get_meta('_ywsbs_trial_time_option');
    $trial_text = '';
    
    if ($trial_length > 0) {
        $periods = [
            'days'   => _n('day', 'days', $trial_length, 'hello-theme-child'),
            'weeks'  => _n('week', 'weeks', $trial_length, 'hello-theme-child'),
            'months' => _n('month', 'months', $trial_length, 'hello-theme-child'),
            'years'  => _n('year', 'years', $trial_length, 'hello-theme-child'),
        ];
        
        $trial_text = sprintf(
            __('%1$d %2$s free trial', 'hello-theme-child'),
            $trial_length,
            $periods[$trial_period] ?? 'days'
        );
    }
    
    // Enqueue styles
    wp_enqueue_style('lilac-subscriptions');
    
    // Start output buffering
    ob_start();
    ?>
    <div class="lilac-trial-activation">
        <div class="lilac-trial-card">
            <h3><?php esc_html_e('Start Your Free Trial', 'hello-theme-child'); ?></h3>
            
            <?php if ($trial_text) : ?>
                <div class="lilac-trial-duration">
                    <span class="lilac-trial-badge"><?php echo esc_html($trial_text); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="lilac-trial-features">
                <ul>
                    <li><?php esc_html_e('Full access to all courses', 'hello-theme-child'); ?></li>
                    <li><?php esc_html_e('Cancel anytime', 'hello-theme-child'); ?></li>
                    <li><?php esc_html_e('No credit card required', 'hello-theme-child'); ?></li>
                </ul>
            </div>
            
            <div class="lilac-trial-button">
                <a href="<?php echo esc_url(add_query_arg('add-to-cart', $product_id, wc_get_checkout_url())); ?>" 
                   class="button alt" 
                   data-product_id="<?php echo esc_attr($product_id); ?>" 
                   data-quantity="1">
                    <?php esc_html_e('Start Your Free Trial', 'hello-theme-child'); ?>
                </a>
                <p class="lilac-trial-note">
                    <?php esc_html_e('No credit card required. You can cancel anytime.', 'hello-theme-child'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <style>
    .lilac-trial-activation {
        max-width: 500px;
        margin: 0 auto 2em;
    }
    .lilac-trial-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        padding: 2em;
        text-align: center;
    }
    .lilac-trial-card h3 {
        margin-top: 0;
        color: #333;
        font-size: 1.5em;
    }
    .lilac-trial-badge {
        display: inline-block;
        background: #4CAF50;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        margin: 10px 0;
    }
    .lilac-trial-features {
        text-align: left;
        margin: 1.5em 0;
    }
    .lilac-trial-features ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .lilac-trial-features li {
        padding: 8px 0 8px 30px;
        position: relative;
    }
    .lilac-trial-features li:before {
        content: '✓';
        color: #4CAF50;
        position: absolute;
        left: 0;
        font-weight: bold;
    }
    .lilac-trial-button .button {
        background: #0073aa;
        color: white !important;
        border: none;
        padding: 12px 30px;
        font-size: 1.1em;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: bold;
        transition: all 0.3s;
    }
    .lilac-trial-button .button:hover {
        background: #005177;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .lilac-trial-note {
        font-size: 0.85em;
        color: #666;
        margin: 10px 0 0;
        font-style: italic;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('lilac_trial_activation', 'lilac_trial_activation_shortcode');
