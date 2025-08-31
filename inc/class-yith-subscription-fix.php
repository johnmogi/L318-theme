<?php
/**
 * Class YITH_Subscription_Fix
 * 
 * Handles the registration of YITH WooCommerce Subscription product type
 * when the plugin fails to register it properly.
 */
class YITH_Subscription_Fix {

    /**
     * The single instance of the class.
     *
     * @var YITH_Subscription_Fix
     */
    protected static $instance = null;

    /**
     * Main YITH_Subscription_Fix Instance.
     *
     * @return YITH_Subscription_Fix Main instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 20);
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'));
        add_action('admin_footer', array($this, 'subscription_custom_js'));
        add_filter('woocommerce_product_data_tabs', array($this, 'subscription_product_tabs'));
        add_action('woocommerce_product_data_panels', array($this, 'subscription_options_product_tab_content'));
        add_action('woocommerce_process_product_meta', array($this, 'save_subscription_option_field'));
    }

    /**
     * Initialize the class.
     */
    public function init() {
        // Only proceed if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Add subscription product type if it doesn't exist
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'));

        // Add product data tabs
        add_filter('woocommerce_product_data_tabs', array($this, 'subscription_product_tabs'));

        // Add product data panels
        add_action('woocommerce_product_data_panels', array($this, 'subscription_options_product_tab_content'));

        // Save product meta
        add_action('woocommerce_process_product_meta', array($this, 'save_subscription_option_field'));

        // Register product type
        add_action('init', array($this, 'register_subscription_product_type'));

        // Add to product type drop down
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'));

        // Add to product class
        add_filter('woocommerce_product_class', array($this, 'subscription_product_class'), 10, 2);
    }

    /**
     * Register the subscription product type.
     */
    public function register_subscription_product_type() {
        // Only register if the class doesn't exist
        if (!class_exists('WC_Product_Subscription')) {
            // Include the product class
            require_once WC()->plugin_path() . '/includes/abstracts/abstract-wc-product.php';
            
            // Create a simple subscription product class
            class WC_Product_Subscription extends WC_Product_Simple {
                /**
                 * Get internal type.
                 *
                 * @return string
                 */
                public function get_type() {
                    return 'yith_subscription';
                }

                /**
                 * Get the add to cart button text.
                 *
                 * @return string
                 */
                public function add_to_cart_text() {
                    $text = $this->is_purchasable() && $this->is_in_stock() ? 
                        __('Subscribe', 'yith-woocommerce-subscription') : 
                        __('Read More', 'woocommerce');

                    return apply_filters('woocommerce_product_add_to_cart_text', $text, $this);
                }

                /**
                 * Get the add to cart button text for the single page.
                 *
                 * @return string
                 */
                public function single_add_to_cart_text() {
                    return apply_filters('woocommerce_product_single_add_to_cart_text', 
                        __('Subscribe', 'yith-woocommerce-subscription'), $this);
                }
            }
        }
    }

    /**
     * Add the subscription product type to the product type selector.
     *
     * @param array $types Product types.
     * @return array
     */
    public function add_subscription_product_type($types) {
        $types['yith_subscription'] = __('Subscription', 'yith-woocommerce-subscription');
        return $types;
    }

    /**
     * Add subscription product data tab.
     *
     * @param array $tabs Product data tabs.
     * @return array
     */
    public function subscription_product_tabs($tabs) {
        $tabs['subscription'] = array(
            'label'    => __('Subscription', 'yith-woocommerce-subscription'),
            'target'   => 'subscription_product_data',
            'class'    => array('show_if_subscription'),
            'priority' => 80,
        );
        return $tabs;
    }

    /**
     * Add subscription product data panel.
     */
    public function subscription_options_product_tab_content() {
        global $post;
        ?>
        <div id='subscription_product_data' class='panel woocommerce_options_panel'>
            <div class='options_group'>
                <?php
                // Subscription price
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_price',
                    'label'       => __('Subscription Price (' . get_woocommerce_currency_symbol() . ')', 'yith-woocommerce-subscription'),
                    'placeholder' => '',
                    'desc_tip'    => 'true',
                    'description' => __('Enter the subscription price.', 'yith-woocommerce-subscription'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'step' => 'any',
                        'min'  => '0'
                    )
                ));

                // Billing interval
                woocommerce_wp_select(array(
                    'id'          => '_subscription_period_interval',
                    'label'       => __('Billing Interval', 'yith-woocommerce-subscription'),
                    'description' => __('Billing interval for this subscription.', 'yith-woocommerce-subscription'),
                    'options'     => array(
                        '1' => __('Every', 'yith-woocommerce-subscription'),
                        '2' => __('Every 2nd', 'yith-woocommerce-subscription'),
                        '3' => __('Every 3rd', 'yith-woocommerce-subscription'),
                        '4' => __('Every 4th', 'yith-woocommerce-subscription'),
                    ),
                    'desc_tip'    => true,
                ));

                // Billing period
                woocommerce_wp_select(array(
                    'id'          => '_subscription_period',
                    'label'       => __('Billing Period', 'yith-woocommerce-subscription'),
                    'description' => __('Billing period for this subscription.', 'yith-woocommerce-subscription'),
                    'options'     => array(
                        'day'   => __('Day', 'yith-woocommerce-subscription'),
                        'week'  => __('Week', 'yith-woocommerce-subscription'),
                        'month' => __('Month', 'yith-woocommerce-subscription'),
                        'year'  => __('Year', 'yith-woocommerce-subscription'),
                    ),
                    'desc_tip'    => true,
                ));
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save subscription product data.
     *
     * @param int $post_id Post ID.
     */
    public function save_subscription_option_field($post_id) {
        $product = wc_get_product($post_id);
        
        // Save subscription data if this is a subscription product
        if (isset($_POST['_subscription_price'])) {
            $product->update_meta_data('_subscription_price', wc_clean($_POST['_subscription_price']));
        }
        
        if (isset($_POST['_subscription_period_interval'])) {
            $product->update_meta_data('_subscription_period_interval', wc_clean($_POST['_subscription_period_interval']));
        }
        
        if (isset($_POST['_subscription_period'])) {
            $product->update_meta_data('_subscription_period', wc_clean($_POST['_subscription_period']));
        }
        
        $product->save();
    }

    /**
     * Add custom JS for the product type selector.
     */
    public function subscription_custom_js() {
        if ('product' !== get_post_type()) {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {
                // Show subscription product options
                jQuery('.options_group.pricing').addClass('show_if_subscription');
                
                // Show subscription product type options
                jQuery('#general_product_data .pricing').addClass('show_if_subscription');
                
                // For subscription product type
                jQuery(document).on('woocommerce-product-type-change', function(e, select_val) {
                    if (select_val === 'yith_subscription') {
                        jQuery('.general_options').show();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Get the product class name.
     *
     * @param string $classname Default class name.
     * @param string $product_type Product type.
     * @return string
     */
    public function subscription_product_class($classname, $product_type) {
        if ('yith_subscription' === $product_type) {
            return 'WC_Product_Subscription';
        }
        return $classname;
    }
}

// Initialize the class
function yith_subscription_fix() {
    return YITH_Subscription_Fix::instance();
}

// Start the fix
yith_subscription_fix();
