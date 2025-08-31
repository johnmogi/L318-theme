<?php
/**
 * Theme functions and definitions
 *
 * @package Lilac
 */

/**
 * Add username, password reset link, and login information to WooCommerce new account email
 */
add_filter('woocommerce_email_content_customer_new_account', 'customize_new_account_email', 10, 3);

function customize_new_account_email($email_content, $user, $email) {
    // Get user data
    $user_login = $user->user_login;
    $user_email = $user->user_email;
    $site_name = get_bloginfo('name');
    
    // Create login and password reset URLs
    $login_url = wp_login_url();
    $reset_url = add_query_arg(array('action' => 'lostpassword'), wp_login_url());
    
    // Customize the email content
    $email_content = '<p>שלום ' . esc_html($user_login) . ',</p>';
    $email_content .= '<p>ברוכים הבאים ל' . esc_html($site_name) . '! פרטי ההתחברות שלך:</p>';
    $email_content .= '<p><strong>שם משתמש:</strong> ' . esc_html($user_login) . '</p>';
    $email_content .= '<p>כדי להגדיר את הסיסמה שלך, לחץ על הקישור הבא: <a href="' . esc_url($reset_url) . '">איפוס סיסמה</a></p>';
    $email_content .= '<p>או היכנס ישירות לחשבון שלך: <a href="' . esc_url($login_url) . '">כניסה לחשבון</a></p>';
    $email_content .= '<p>אנא שמור את פרטי ההתחברות שלך במקום בטוח.</p>';
    $email_content .= '<p>בברכה,<br>צוות ' . esc_html($site_name) . '</p>';
    
    return $email_content;
}

// Allow LearnDash to use its default LD30 templates for better functionality
// Removed custom template override to use native LearnDash display

// Enable LearnDash Video Processing for LD30 theme
// This constant is REQUIRED for LearnDash to process lesson videos
if (!defined('LEARNDASH_LESSON_VIDEO')) {
    define('LEARNDASH_LESSON_VIDEO', true);
}

// Video content processing disabled to allow native LearnDash LD30 display
// The mu-plugin learndash-video handles video integration properly

// Translate LearnDash "Expand All" and "Collapse All" to Hebrew
add_filter('gettext', 'translate_learndash_expand_collapse', 1, 3);
add_filter('gettext_with_context', 'translate_learndash_expand_collapse_with_context', 1, 4);
add_filter('ngettext', 'translate_learndash_expand_collapse_plural', 1, 5);

// Add JavaScript translation for dynamically loaded content
add_action('wp_footer', 'add_expand_all_translation_js');

function translate_learndash_expand_collapse($translated_text, $text, $domain) {
    // Catch all instances regardless of domain
    switch ($text) {
        case 'Expand All':
            return 'הרחב הכל';
        case 'Collapse All':
            return 'צמצם';
    }
    return $translated_text;
}

function add_expand_all_translation_js() {
    if (is_singular('sfwd-courses')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to translate expand/collapse buttons
            function translateExpandButtons() {
                // Target all expand/collapse button text
                const expandButtons = document.querySelectorAll('.ld-expand-button .ld-text');
                expandButtons.forEach(function(button) {
                    if (button.textContent.trim() === 'Expand All') {
                        button.textContent = 'הרחב הכל';
                    }
                    if (button.textContent.trim() === 'Collapse All') {
                        button.textContent = 'צמצם';
                    }
                });
                
                // Also update data attributes
                const expandButtonElements = document.querySelectorAll('.ld-expand-button[data-ld-expand-text="Expand All"]');
                expandButtonElements.forEach(function(btn) {
                    btn.setAttribute('data-ld-expand-text', 'הרחב הכל');
                    btn.setAttribute('data-ld-collapse-text', 'צמצם');
                });
            }
            
            // Run translation immediately
            translateExpandButtons();
            
            // Run translation with limited retries to avoid performance issues
            let retryCount = 0;
            const maxRetries = 3;
            
            function retryTranslation() {
                if (retryCount < maxRetries) {
                    retryCount++;
                    setTimeout(translateExpandButtons, 1000 * retryCount);
                }
            }
            
            retryTranslation();
            
            // Limited mutation observer - only for specific LearnDash containers
            let observerTimeout;
            const observer = new MutationObserver(function(mutations) {
                clearTimeout(observerTimeout);
                observerTimeout = setTimeout(function() {
                    translateExpandButtons();
                }, 200);
            });
            
            // Only observe LearnDash specific containers to reduce performance impact
            const ldContainers = document.querySelectorAll('.learndash-wrapper, .ld-item-list');
            ldContainers.forEach(function(container) {
                observer.observe(container, {
                    childList: true,
                    subtree: false // Reduce scope
                });
            });
            
            // Disconnect observer after 10 seconds to prevent long-term performance impact
            setTimeout(function() {
                observer.disconnect();
            }, 10000);
        });
        </script>
        <?php
    }
}

function translate_learndash_expand_collapse_with_context($translated_text, $text, $context, $domain) {
    if ($text === 'Expand All' || $text === 'Collapse All') {
        error_log("Translation with context filter called: text='$text', context='$context', domain='$domain'");
    }
    
    if ($domain === 'learndash' || empty($domain)) {
        switch ($text) {
            case 'Expand All':
                return 'הרחב הכל';
            case 'Collapse All':
                return 'צמצם';
        }
    }
    return $translated_text;
}

function translate_learndash_expand_collapse_plural($translated_text, $single, $plural, $number, $domain) {
    if ($single === 'Expand All' || $single === 'Collapse All') {
        error_log("Translation plural filter called: single='$single', domain='$domain'");
    }
    
    if ($domain === 'learndash' || empty($domain)) {
        switch ($single) {
            case 'Expand All':
                return 'הרחב הכל';
            case 'Collapse All':
                return 'צמצם';
        }
    }
    return $translated_text;
}

// Fix for LearnDash WooCommerce translation loading issue
add_action('init', function() {
    // Only proceed if we're not in admin or doing AJAX
    if (!is_admin() && !defined('DOING_AJAX')) {
        // Check if the function exists and the text domain isn't already loaded
        if (function_exists('load_plugin_textdomain') && !did_action('learndash_woocommerce_loaded')) {
            // Load the translations with the correct path
            load_plugin_textdomain(
                'learndash-woocommerce',
                false,
                'learndash-woocommerce/languages/'
            );
            
            // Mark as loaded to prevent duplicate loading
            do_action('learndash_woocommerce_loaded');
        }
    }
}, 5);

// Define theme version
if (!defined('LILAC_THEME_VERSION')) {
    define('LILAC_THEME_VERSION', '1.0.0');
}

// Define theme directory path
if (!defined('LILAC_THEME_DIR')) {
    define('LILAC_THEME_DIR', get_stylesheet_directory());
}

// Define theme directory URI
if (!defined('LILAC_THEME_URI')) {
    define('LILAC_THEME_URI', get_stylesheet_directory_uri());
}

// Include the autoloader
require_once LILAC_THEME_DIR . '/includes/autoload.php';

// Initialize the theme
require_once LILAC_THEME_DIR . '/includes/src/Core/Theme.php';

/**
 * Load WooCommerce textdomain at the right time
 */
function lilac_load_woocommerce_textdomain() {
    if (class_exists('WooCommerce')) {
        load_plugin_textdomain('woocommerce', false, dirname(plugin_basename(WC_PLUGIN_FILE)) . '/i18n/languages/');
    }
}
add_action('init', 'lilac_load_woocommerce_textdomain', 5);

/**
 * Hide quiz navigation arrows and legend
 */
add_action('wp_head', function() {
    ?>
    <style>
        /* Hide the quiz review legend */
        .wpProQuiz_reviewLegend,
        div.wpProQuiz_reviewLegend,
        .learndash-quiz-review-legend,
        .wpProQuiz_reviewDiv .wpProQuiz_reviewLegend {
            display: none !important;
        }

        /* Hide all navigation buttons and arrows */
        .wpProQuiz_reviewButtons,
        .wpProQuiz_reviewDiv .wpProQuiz_reviewButtons,
        body .wpProQuiz_reviewDiv button,
        body .wpProQuiz_reviewDiv input[type="button"],
        .wpProQuiz_reviewDiv > div:not(.wpProQuiz_reviewQuestion),
        .wpProQuiz_reviewDiv #quiz_continue_link,
        .wpProQuiz_reviewDiv a.wpProQuiz_button {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            position: absolute !important;
            pointer-events: none !important;
        }
        
        /* Target the page numbers navigation specifically */
        .quiz-container .page-numbers,
        .wpProQuiz_content .page-numbers,
        .wpProQuiz_reviewDiv .wpProQuiz_reviewButtons .page-numbers,
        .wpProQuiz_reviewDiv .page-numbers {
            display: none !important;
        }
        
        /* Target arrows specifically */
        .wpProQuiz_reviewDiv svg,
        .wpProQuiz_content svg,
        .wpProQuiz_reviewDiv .dashicons,
        .wpProQuiz_reviewDiv .fa,
        .wpProQuiz_reviewDiv [class*="-arrow"],
        .wpProQuiz_reviewDiv [class*="arrow"],
        .wpProQuiz_reviewDiv [class*="Arrow"],
        .wpProQuiz_reviewDiv button svg,
        .wpProQuiz_reviewDiv button img,
        .wpProQuiz_reviewDiv [aria-label*="arrow"],
        .wpProQuiz_reviewDiv [aria-label*="next"],
        .wpProQuiz_reviewDiv [aria-label*="previous"],
        .wpProQuiz_reviewDiv a[class*='previous'],
        .wpProQuiz_reviewDiv a[class*='next'] {
            display: none !important;
        }

        /* Hide specific navigation arrows that could be added dynamically */
        .wpProQuiz_reviewDiv::before,
        .wpProQuiz_reviewDiv::after,
        .wpProQuiz_reviewQuestion::before,
        .wpProQuiz_reviewQuestion::after {
            display: none !important;
            content: none !important;
        }
        
        /* Force hide the entire sidebar container except for the question numbers */
        .wpProQuiz_reviewDiv > *:not(.wpProQuiz_reviewQuestion) {
            display: none !important;
        }
    </style>
    <?php
}, 9999);


/**
 * Force video display in LD30 theme
 */
add_filter('learndash_30_forced_theme_mods', function($mods) {
    $mods['ld30_show_video_in_lesson'] = true;
    return $mods;
});

/**
 * Ensure video container is visible and properly styled
 */
add_action('wp_head', function() {
    if (is_singular(['sfwd-lessons', 'sfwd-topic'])) {
        ?>
        <style>
            /* Video container styling */
            .ld-video {
                display: block !important;
                margin: 20px 0;
                min-height: 450px;
                opacity: 1 !important;
                visibility: visible !important;
                position: relative;
                z-index: 1;
            }
            
            /* Make YouTube iframe responsive */
            .ld-video iframe,
            .learndash-video iframe,
            .wp-video {
                width: 100% !important;
                height: 450px !important;
                max-width: 800px;
                margin: 0 auto;
                display: block;
                border: none;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            /* Fix for video container visibility */
            .learndash-wrapper .ld-video {
                display: block !important;
            }
            
            /* Ensure video is above other content */
            .ld-item-list.ld-lesson-list {
                position: relative;
                z-index: 2;
            }
        </style>
        <?php
    }
}, 999);

// Video shortcode processing disabled to allow native LearnDash LD30 display
// The mu-plugin learndash-video handles video integration properly

// Debug check for Timed Access module - DISABLED
// Now using lilac-ajax-fix.php MU-plugin for better handling

// Enhanced debug logging function
function custom_log($message, $data = null) {
    // Use wp-content/debug-lilac.log for better accessibility
    $log_file = WP_CONTENT_DIR . '/debug-lilac.log';
    $timestamp = current_time('mysql');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    // Add request URI and method
    $log_message .= "[URL] " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A') . PHP_EOL;
    $log_message .= "[METHOD] " . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A') . PHP_EOL;
    
    // Add POST data if this is a POST request
    if (!empty($_POST)) {
        $log_message .= "[POST DATA] " . print_r($_POST, true) . PHP_EOL;
    }
    
    // Add GET data if this is a GET request
    if (!empty($_GET)) {
        $log_message .= "[GET DATA] " . print_r($_GET, true) . PHP_EOL;
    }
    
    // Add any additional data
    if ($data !== null) {
        $log_message .= "[DATA] " . (is_array($data) || is_object($data) ? print_r($data, true) : $data) . PHP_EOL;
    }
    
    // Add a separator
    $log_message .= str_repeat('-', 80) . PHP_EOL;
    
    // Ensure the log directory exists and is writable
    if (!file_exists(dirname($log_file))) {
        @mkdir(dirname($log_file), 0755, true);
    }
    
    // Write to the log file
    @file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Also log to PHP error log for visibility
    error_log('LILAC DEBUG: ' . strip_tags($message));
}

// Add debug test endpoint
add_action('init', function() {
    if (isset($_GET['test_debug'])) {
        custom_log('Debug test', 'This is a test message');
        echo 'Debug test completed. Check debug-lilac.log';
        exit;
    }
});

// AJAX handler for client-side debug logging
add_action('wp_ajax_lilac_debug_log', 'lilac_handle_debug_log');
add_action('wp_ajax_nopriv_lilac_debug_log', 'lilac_handle_debug_log');
function lilac_handle_debug_log() {
    if (!empty($_POST['message'])) {
        $log_file = WP_CONTENT_DIR . '/debug-lilac.log';
        $message = '[' . current_time('mysql') . '] ' . sanitize_text_field($_POST['message']) . "\n";
        error_log($message, 3, $log_file);
    }
    wp_die();
}

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if (!defined('LILAC_QUIZ_FOLLOWUP_VERSION')) {
    define('LILAC_QUIZ_FOLLOWUP_VERSION', '1.0.0');
}

/**
 * Change Add to Cart button text for WooCommerce
 */
add_filter('woocommerce_product_single_add_to_cart_text', 'ccr_custom_add_to_cart_text');
add_filter('woocommerce_product_add_to_cart_text', 'ccr_custom_add_to_cart_text');
function ccr_custom_add_to_cart_text() {
    return 'רכשו עכשיו';
}

/**
 * Debug function to log to wp-content/debug.log
 */
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

/**
 * Log WooCommerce add to cart actions
 */
add_action('woocommerce_add_to_cart', 'log_add_to_cart_action', 10, 6);
function log_add_to_cart_action($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    write_log('=== ADD TO CART ACTION TRIGGERED ===');
    write_log('Product ID: ' . $product_id);
    write_log('Variation ID: ' . $variation_id);
    write_log('Quantity: ' . $quantity);
    write_log('Cart Item Data: ' . print_r($cart_item_data, true));
    write_log('$_REQUEST: ' . print_r($_REQUEST, true));
    write_log('$_POST: ' . print_r($_POST, true));
}

/**
 * Handle all add to cart redirects to checkout
 */
add_filter('woocommerce_add_to_cart_redirect', 'custom_add_to_cart_redirect', 99, 1);
function custom_add_to_cart_redirect($url) {
    // Log the start of the redirection process
    custom_log('=== START ADD TO CART REDIRECT ===');
    custom_log('Original URL', $url);
    
    // Log request data
    custom_log('Request Data', [
        'is_ajax' => wp_doing_ajax(),
        'is_cart' => is_cart(),
        'is_checkout' => is_checkout(),
        'is_product' => is_product(),
        'request' => $_REQUEST
    ]);
    
    // Don't redirect if this is an AJAX request - let the JS handle it
    if (wp_doing_ajax()) {
        custom_log('AJAX request detected, letting JS handle redirection');
        return $url;
    }
    
    // Check if this is an add to cart action
    $is_add_to_cart = (
        (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart'])) ||
        (isset($_REQUEST['add-to-cart-nonce']) && wp_verify_nonce($_REQUEST['add-to-cart-nonce'], 'add-to-cart')) ||
        (isset($_REQUEST['add-to-cart-variation']) && is_numeric($_REQUEST['add-to-cart-variation']))
    );
    
    if ($is_add_to_cart) {
        custom_log('Add to cart action detected');
        
        // Get the product ID
        $product_id = 0;
        $variation_id = 0;
        
        if (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart'])) {
            $product_id = absint($_REQUEST['add-to-cart']);
            custom_log('Simple product detected', ['product_id' => $product_id]);
        } 
        
        if (isset($_REQUEST['add-to-cart-variation']) && is_numeric($_REQUEST['add-to-cart-variation'])) {
            $variation_id = absint($_REQUEST['add-to-cart-variation']);
            $product_id = $variation_id; // For variations, use variation ID as product ID
            custom_log('Variable product detected', [
                'variation_id' => $variation_id,
                'variation' => isset($_REQUEST['variation_id']) ? $_REQUEST['variation_id'] : 'N/A'
            ]);
        }
        
        if ($product_id > 0) {
            // Clear any notices to prevent duplicate messages
            wc_clear_notices();
            
            // Get the checkout URL
            $checkout_url = wc_get_checkout_url();
            $redirect_url = add_query_arg('added-to-cart', $product_id, $checkout_url);
            
            custom_log('Redirecting to checkout', [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'redirect_url' => $redirect_url
            ]);
            
            return $redirect_url;
        }
    }
    
    custom_log('No redirect needed, returning original URL');
    return $url;
}

/**
 * Handle AJAX add to cart redirects and variable product forms
 */
add_action('wp_footer', 'custom_add_to_cart_script');
function custom_add_to_cart_script() {
    // Check if WooCommerce functions exist
    if (!function_exists('is_woocommerce') || !function_exists('is_cart') || 
        !function_exists('is_checkout') || !function_exists('is_product') ||
        !function_exists('wc_get_checkout_url')) {
        return;
    }
    
    // Only load on relevant pages
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_product()) return;
    
    $checkout_url = wc_get_checkout_url();
    $is_ajax = wp_doing_ajax();
    ?>
    <script type="text/javascript">
    (function($) {
        'use strict';
        
        // Enhanced debug function
        function debugLog() {
            if (!window.console || !window.console.log) return;
            
            var args = Array.prototype.slice.call(arguments);
            var timestamp = new Date().toISOString();
            
            // Add timestamp and prefix to all log messages
            args.unshift('[Lilac Debug ' + timestamp + ']');
            
            // Log to console
            console.log.apply(console, args);
            
            // Also log to a global array for debugging
            if (!window.lilacDebugLog) {
                window.lilacDebugLog = [];
            }
            window.lilacDebugLog.push({
                time: timestamp,
                message: args.join(' ')
            });
            
            // Send log to server for persistent logging
            try {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'lilac_debug_log',
                        message: args.join(' ')
                    }
                });
            } catch (e) {
                console.error('Failed to send debug log:', e);
            }
        }
        
        // Function to redirect to checkout
        function redirectToCheckout() {
            var checkoutUrl = '<?php echo esc_js($checkout_url); ?>';
            debugLog('Redirecting to checkout:', checkoutUrl);
            
            // Add a small random parameter to prevent caching
            var timestamp = new Date().getTime();
            var separator = checkoutUrl.includes('?') ? '&' : '?';
            window.location.href = checkoutUrl + separator + 'nocache=' + timestamp;
            
            // If we're still here after 1 second, force redirect
            setTimeout(function() {
                debugLog('Force redirecting to checkout after delay');
                window.location.href = checkoutUrl;
            }, 1000);
            
            return false;
        }
        
        // Handle AJAX add to cart
        function handleAddedToCart(event, fragments, hash, $button) {
            debugLog('=== ADDED TO CART EVENT ===');
            debugLog('Event:', event);
            debugLog('Fragments:', fragments);
            debugLog('Hash:', hash);
            debugLog('Button:', $button ? $button.attr('class') : 'No button');
            
            // Get the product ID from the button if available
            var productId = $button ? $button.data('product_id') || $button.closest('[data-product_id]').data('product_id') : 'unknown';
            debugLog('Product ID from button:', productId);
            
            // Check if this is a variation product
            var isVariation = $button && $button.closest('.variations_form').length > 0;
            var delay = isVariation ? 1500 : 800; // Longer delay for variations
            
            debugLog('Is variation:', isVariation, 'Using delay:', delay + 'ms');
            
            // Clear any existing timeouts
            if (window.addToCartTimeout) {
                clearTimeout(window.addToCartTimeout);
            }
            
            // Set a new timeout
            window.addToCartTimeout = setTimeout(function() {
                debugLog('Executing redirect after delay');
                redirectToCheckout();
            }, delay);
        }
        
        // Document ready
        $(function() {
            debugLog('Document ready');
            
            // Handle AJAX add to cart events
            $(document.body).on('added_to_cart', handleAddedToCart);
            
            // Handle variable product form submission
            $('form.variations_form').on('submit', function(e) {
                debugLog('Variable product form submission');
                var $form = $(this);
                var $button = $form.find('.single_add_to_cart_button');
                
                // Update button text and disable
                $button.text('מועבר לתשלום...').prop('disabled', true);
                
                // For AJAX add to cart
                if ($form.hasClass('variations_form')) {
                    debugLog('Variable product form detected');
                    return true; // Let the form submit normally
                }
                
                return true;
            });
            
            // Handle variation selection
            $('form.variations_form').on('found_variation', function(event, variation) {
                debugLog('Variation selected: ' + JSON.stringify(variation));
                $(this).find('.single_add_to_cart_button').text('רכשו עכשיו');
            });
            
            // Handle direct add to cart buttons (simple products)
            $(document).on('click', '.add_to_cart_button:not(.product_type_variable)', function(e) {
                debugLog('Add to cart button clicked');
                var $button = $(this);
                
                // Only proceed if not already processing
                if ($button.is('.processing, .disabled, :disabled, [disabled=disabled]')) {
                    return false;
                }
                
                // Mark as processing
                $button.addClass('processing').text('מועבר לתשלום...');
                
                // If this is an AJAX add to cart
                if (typeof wc_add_to_cart_params !== 'undefined' && $button.is('.ajax_add_to_cart')) {
                    return true; // Let WooCommerce handle the AJAX request
                }
                
                return true;
            });
            
            // Redirect if on cart page with items
            if ($('body').hasClass('woocommerce-cart') && $('.woocommerce-cart-form__contents').length) {
                debugLog('On cart page, redirecting to checkout');
                setTimeout(redirectToCheckout, 500);
            }
            
            // Debug AJAX requests
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('wc-ajax=add_to_cart')) {
                    debugLog('AJAX add to cart completed');
                    debugLog('URL: ' + settings.url);
                    debugLog('Status: ' + xhr.status);
                    
                    try {
                        var response = JSON.parse(xhr.responseText);
                        debugLog('Response: ' + JSON.stringify(response));
                    } catch (e) {
                        debugLog('Could not parse response as JSON');
                    }
                }
            });
            
            // Enhanced form submission handler for all add to cart forms
            $(document).on('submit', 'form.cart:not(.grouped_form)', function(e) {
                debugLog('=== FORM SUBMIT TRIGGERED ===');
                var $form = $(this);
                var $button = $form.find('.single_add_to_cart_button');
                var isAjax = $form.attr('enctype') === 'multipart/form-data' || 
                             $form.hasClass('variations_form') || 
                             $form.find('input[name="add-to-cart"]').length > 0;
                
                debugLog('Form data:', $form.serialize());
                debugLog('Is AJAX submission:', isAjax);
                
                // Always prevent default for our custom handling
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Disable the button to prevent multiple clicks
                $button.prop('disabled', true).addClass('loading');
                
                if (isAjax) {
                    debugLog('Processing as AJAX submission');
                    
                    // For variable products, we need to wait for variation data to be set
                    if ($form.hasClass('variations_form')) {
                        debugLog('Variable product form detected');
                        // Trigger variation selection if not already done
                        if (typeof $form.data('product_variations') === 'undefined') {
                            $form.find('.variations select').trigger('change');
                        }
                    }
                    
                    // Submit via AJAX
                    $.ajax({
                        url: wc_add_to_cart_params.ajax_url,
                        type: 'POST',
                        data: $form.serialize() + '&action=woocommerce_add_to_cart',
                        dataType: 'json',
                        success: function(response) {
                            debugLog('AJAX add to cart success:', response);
                            if (response.error && response.product_url) {
                                window.location = response.product_url;
                                return;
                            }
                            // Redirect to checkout after successful add to cart
                            redirectToCheckout();
                        },
                        error: function(xhr, status, error) {
                            var errorMsg = 'שגיאה בהוספת המוצר לעגלה. אנא נסה שוב.';
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.error_message) {
                                    errorMsg = response.error_message;
                                }
                                debugLog('AJAX add to cart error:', response);
                            } catch (e) {
                                debugLog('Error parsing error response:', e);
                            }
                            alert(errorMsg);
                            $button.prop('disabled', false).removeClass('loading');
                        }
                    });
                } else {
                    debugLog('Processing as standard form submission');
                    // For non-AJAX forms, submit normally
                    this.submit();
                }
            });
        });
        
    })(jQuery);
    </script>
    <?php
}

/**
 * Debug function to log to wp-content/debug.log
 */
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

// Remove any other conflicting redirects
remove_action('template_redirect', 'wc_redirect_to_checkout');
remove_action('template_redirect', 'wc_cart_redirect_after_error');

// Ensure WooCommerce session is started for all users
add_action('wp_loaded', function() {
    if (!is_admin() && !defined('DOING_CRON') && !defined('DOING_AJAX') && function_exists('WC')) {
        // Initialize session if not already started
        if (is_null(WC()->session)) {
            WC()->initialize_session();
        }
        // Ensure customer session cookie is set
        if (WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }
});

// Ensure cart is initialized for all users
add_action('wp_loaded', function() {
    if (function_exists('WC') && !is_admin() && !defined('DOING_CRON') && !defined('DOING_AJAX')) {
        if (is_null(WC()->cart)) {
            WC()->initialize_cart();
        }
    }
}, 5);

// Handle AJAX logging
add_action('wp_ajax_log_to_console', 'handle_console_log');
add_action('wp_ajax_nopriv_log_to_console', 'handle_console_log');
function handle_console_log() {
    if (isset($_POST['message'])) {
        write_log('JS: ' . sanitize_text_field($_POST['message']));
    }
    wp_die();
}

/**
 * Prevent cart empty redirect
 */
add_action('template_redirect', function() {
    // Ensure WooCommerce is loaded
    if (!function_exists('is_cart') || !class_exists('WooCommerce')) {
        return;
    }
    
    if (is_cart() && WC()->cart->is_empty() && !wp_doing_ajax()) {
        $referer = wp_get_referer();
        if ($referer) {
            wp_safe_redirect($referer);
            exit;
        }
    }
}, 20); // Higher priority to ensure WooCommerce is loaded

/**
 * Customize checkout fields - Remove unwanted elements and set Hebrew placeholders
 */
// Re-enabled since MU plugin checkout customizer is not working
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields', 99999);
function custom_override_checkout_fields($fields) {
    error_log('0AL208: Checkout fields filter triggered - ' . count($fields) . ' field groups found');
    // Remove order comments and unnecessary fields
    unset($fields['order']['order_comments']);
    
    // Remove shipping fields completely
    unset($fields['shipping']);
    
    // Remove coupon fields
    unset($fields['billing']['promo_code']);
    
    // Essential fields only with no labels (placeholders only)
    $fields['billing']['billing_first_name'] = array(
        'label'       => '',
        'placeholder' => 'שם פרטי',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 10
    );
    
    $fields['billing']['billing_last_name'] = array(
        'label'       => '',
        'placeholder' => 'שם משפחה',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 20
    );
    
    $fields['billing']['billing_phone'] = array(
        'label'       => '',
        'placeholder' => 'טלפון נייד (זיהוי משתמש)',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 30,
        'clear'       => true
    );
    
    $fields['billing']['phone_confirm'] = array(
        'type'        => 'text',
        'label'       => '',
        'placeholder' => 'אימות מספר נייד הקלידו שוב',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 40
    );
    
    $fields['billing']['id_number'] = array(
        'type'        => 'text',
        'label'       => '',
        'placeholder' => 'תעודת זהות (סיסמה)',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 50
    );
    
    $fields['billing']['id_confirm'] = array(
        'type'        => 'text',
        'label'       => '',
        'placeholder' => 'אימות תעודת זהות הקלידו שוב',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 60
    );
    
    $fields['billing']['billing_email'] = array(
        'label'       => '',
        'placeholder' => 'אימייל לאישור',
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 70,
        'clear'       => true
    );
    
    // Check if cart contains physical products requiring shipping
    $needs_shipping = false;
    if (WC()->cart && !empty(WC()->cart->get_cart())) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!$cart_item['data']->is_virtual() && !$cart_item['data']->is_downloadable()) {
                $needs_shipping = true;
                break;
            }
        }
    }
    
    // Add minimal address fields - only address and city
    $fields['billing']['billing_address_1'] = array(
        'label'       => '',
        'placeholder' => 'כתובת',
        'required'    => $needs_shipping, // Required only for physical products
        'class'       => array('form-row-first'),
        'priority'    => 75
    );
    
    $fields['billing']['billing_city'] = array(
        'label'       => '',
        'placeholder' => 'עיר',
        'required'    => $needs_shipping, // Required only for physical products
        'class'       => array('form-row-last'),
        'priority'    => 80
    );
    
    // Remove ALL other unnecessary fields
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['school_code']);
    unset($fields['billing']['school_info']);
    unset($fields['billing']['class_number']);
    
    return $fields;
}

/**
 * Set default country to Israel and hide country field
 */
add_filter('default_checkout_billing_country', function() {
    return 'IL'; // ISO code for Israel
});

/**
 * Remove address fields from checkout
 */
add_filter('woocommerce_checkout_fields', function($fields) {
    // Remove shipping fields
    unset($fields['shipping']);
    
    return $fields;
});

/**
 * Remove duplicate product error message
 */
add_filter('woocommerce_add_error', function($error) {
    if (strpos($error, 'You cannot add another') !== false) {
        // Return an empty string to prevent the error from showing
        return '';
    }
    return $error;
});

/**
 * Clear any error notices on the cart page
 */
add_action('template_redirect', function() {
    // Ensure WooCommerce is loaded
    if (!function_exists('is_cart') || !function_exists('wc_clear_notices') || !function_exists('wc_get_checkout_url') || !class_exists('WooCommerce')) {
        return;
    }
    
    if (is_cart()) {
        wc_clear_notices();
        
        // If cart is not empty, redirect to checkout
        if (!WC()->cart->is_empty()) {
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }
}, 99);

/**
 * Enqueue scripts and styles
 */
function hello_elementor_child_scripts_styles() {
    // Enqueue parent theme styles
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('hello-elementor-theme-style'),
        wp_get_theme()->get('Version')
    );

    // Enqueue WooCommerce scripts and styles if WooCommerce is active
    if (class_exists('WooCommerce')) {
        // Check if we're on any WooCommerce-related page or page with products
        $is_wc_page = is_product() || is_cart() || is_checkout() || is_shop() || is_product_category() || is_product_tag() || is_woocommerce();
        
        // Also check if current page has WooCommerce shortcodes or product content
        global $post;
        if (!$is_wc_page && isset($post->post_content)) {
            $is_wc_page = has_shortcode($post->post_content, 'products') || 
                         has_shortcode($post->post_content, 'product') ||
                         has_shortcode($post->post_content, 'add_to_cart') ||
                         strpos($post->post_content, 'woocommerce') !== false;
        }
        
        // Always enqueue core WooCommerce scripts if WooCommerce page detected
        // This ensures wc_add_to_cart_params is always available when needed
        if ($is_wc_page) {
            // Enqueue WooCommerce core scripts first
            wp_enqueue_script('wc-add-to-cart');
            wp_enqueue_script('wc-cart-fragments');
            
            // Ensure jQuery is loaded as dependency
            wp_enqueue_script('jquery');
            
            // Localize the script with the AJAX URL and other parameters
            // This MUST happen after the script is enqueued
            wp_localize_script('wc-add-to-cart', 'wc_add_to_cart_params', array(
                'ajax_url' => WC()->ajax_url(),
                'wc_ajax_url' => WC_AJAX::get_endpoint("%%endpoint%%"),
                'i18n_view_cart' => __('View cart', 'woocommerce'),
                'cart_url' => wc_get_cart_url(),
                'is_cart' => is_cart(),
                'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add')
            ));
        }
    }
    
    // Check if WooCommerce functions are available
    $is_woocommerce_page = false;
    if (function_exists('is_product') && function_exists('is_shop') && function_exists('is_product_category')) {
        $is_woocommerce_page = is_product() || is_shop() || is_product_category();
    }
    
    // Enqueue custom scripts only if they exist and we're on a WooCommerce page
    // Only load custom scripts if WooCommerce core scripts are already enqueued
    if ($is_wc_page && wp_script_is('wc-add-to-cart', 'enqueued')) {
        // Enqueue debug script
        wp_enqueue_script(
            'lilac-debug',
            get_stylesheet_directory_uri() . '/js/debug-test.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/js/debug-test.js'),
            true
        );
        
        // Enqueue add to cart script with proper dependencies
        wp_enqueue_script(
            'lilac-add-to-cart',
            get_stylesheet_directory_uri() . '/js/add-to-cart.js',
            array('jquery', 'wc-add-to-cart', 'wc-cart-fragments'),
            filemtime(get_stylesheet_directory() . '/js/add-to-cart.js'),
            true
        );
        
        // Localize script with necessary data
        $lilac_vars = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'is_product' => is_product() ? 'yes' : 'no',
            'is_shop' => is_shop() ? 'yes' : 'no',
            'is_product_category' => is_product_category() ? 'yes' : 'no',
            'home_url' => home_url('/'),
            'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
            'debug' => WP_DEBUG ? 'yes' : 'no',
            'user_logged_in' => is_user_logged_in() ? 'yes' : 'no',
            'wc_cart_url' => wc_get_cart_url(),
            'nonce' => wp_create_nonce('woocommerce-cart')
        );
        
        wp_localize_script('lilac-add-to-cart', 'lilac_vars', $lilac_vars);
        
        // Log the debug info to PHP error log
        if (WP_DEBUG) {
            error_log('Lilac Debug - Enqueued Scripts: ' . print_r($lilac_vars, true));
        }
        
        // Note: wc_add_to_cart_params is already localized above in the main WooCommerce section
        // Removed duplicate localization to prevent conflicts
    }
    if (file_exists(get_stylesheet_directory() . '/assets/js/custom.js')) {
        wp_enqueue_script(
            'hello-elementor-child-script',
            get_stylesheet_directory_uri() . '/assets/js/custom.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/custom.js'),
            true
        );
    }
    
    // Enqueue quiz answer handler on quiz pages
    if (is_singular('sfwd-quiz')) {
        wp_enqueue_script(
            'quiz-answer-handler',
            get_stylesheet_directory_uri() . '/assets/js/quiz-answer-handler.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/quiz-answer-handler.js'),
            true
        );
        
        // Ensure jQuery is loaded
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }
    }

    // Enqueue custom styles if the file exists
    $custom_styles_path = get_stylesheet_directory() . '/css/custom-styles.css';
    if (file_exists($custom_styles_path)) {
        wp_enqueue_style(
            'custom-styles',
            get_stylesheet_directory_uri() . '/css/custom-styles.css',
            array(),
            filemtime($custom_styles_path)
        );
    }
    
    // Enqueue WooCommerce checkout styles to fix invisible input fields
    if (class_exists('WooCommerce') && (is_checkout() || is_cart() || is_account_page())) {
        $checkout_styles_path = get_stylesheet_directory() . '/assets/css/woocommerce-checkout.css';
        if (file_exists($checkout_styles_path)) {
            wp_enqueue_style(
                'woocommerce-checkout-fixes',
                get_stylesheet_directory_uri() . '/assets/css/woocommerce-checkout.css',
                array('woocommerce-general'),
                filemtime($checkout_styles_path)
            );
        }
    }
    
    // Enqueue teacher dashboard styles if on dashboard page
    if (is_page('teacher-dashboard') || (is_singular() && has_shortcode(get_post()->post_content, 'teacher_dashboard'))) {
        wp_enqueue_style(
            'teacher-dashboard',
            get_stylesheet_directory_uri() . '/assets/css/teacher-dashboard.css',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/css/teacher-dashboard.css')
        );
        
        wp_enqueue_script(
            'teacher-dashboard',
            get_stylesheet_directory_uri() . '/assets/js/teacher-dashboard.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/teacher-dashboard.js'),
            true
        );
        
        $i18n = array(
            'loading' => __('Loading...', 'hello-theme-child'),
            'error' => __('An error occurred. Please try again.', 'hello-theme-child'),
            'noStudents' => __('No students found.', 'hello-theme-child'),
            'refreshing' => __('Refreshing...', 'hello-theme-child'),
            'statsUpdated' => __('Statistics updated successfully!', 'hello-theme-child'),
            'exporting' => __('Exporting...', 'hello-theme-child'),
            'exportComplete' => __('Export complete!', 'hello-theme-child'),
            'student' => __('student', 'hello-theme-child'),
            'students' => __('students', 'hello-theme-child'),
            'composeMessage' => __('Compose Message', 'hello-theme-child'),
            'subject' => __('Subject', 'hello-theme-child'),
            'message' => __('Message', 'hello-theme-child'),
            'sendMessage' => __('Send Message', 'hello-theme-child'),
            'cancel' => __('Cancel', 'hello-theme-child'),
            'subjectRequired' => __('Please enter a subject for your message.', 'hello-theme-child'),
            'messageRequired' => __('Please enter a message.', 'hello-theme-child'),
            'messageSent' => __('Message sent to student #%s', 'hello-theme-child'),
            'messageSentSuccess' => __('Message Sent Successfully!', 'hello-theme-child'),
            'messageSentTo' => __('Message sent to', 'hello-theme-child'),
            'recipient' => __('recipient', 'hello-theme-child'),
            'recipients' => __('recipients', 'hello-theme-child'),
            'close' => __('Close', 'hello-theme-child'),
            'registered' => __('Registered', 'hello-theme-child'),
            'lastLogin' => __('Last Login', 'hello-theme-child'),
            'never' => __('Never', 'hello-theme-child'),
            'courseProgress' => __('Course Progress', 'hello-theme-child'),
            'completed' => __('Completed', 'hello-theme-child'),
            'inProgress' => __('In Progress', 'hello-theme-child'),
            'notStarted' => __('Not Started', 'hello-theme-child'),
            'score' => __('Score', 'hello-theme-child'),
            'lastActivity' => __('Last Activity', 'hello-theme-child'),
            'noCourses' => __('No courses found for this student.', 'hello-theme-child'),
            'complete' => __('complete', 'hello-theme-child')
        );

        wp_localize_script('teacher-dashboard', 'teacherDashboardData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teacher_dashboard_nonce'),
            'i18n' => $i18n
        ));
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20);

// Enqueue dashboard behavior script
add_action('wp_enqueue_scripts', 'enqueue_dashboard_behavior_script', 99);
function enqueue_dashboard_behavior_script() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'dashboard-behavior',
            get_stylesheet_directory_uri() . '/assets/js/dashboard-behavior.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/dashboard-behavior.js'),
            true
        );
    }
}

/**
 * Enqueue progress bar styles
 */
function enqueue_progress_bar_styles() {
    // Only load on quiz pages
    if (is_singular('sfwd-quiz')) {
        wp_enqueue_style(
            'progress-bar-styles',
            get_stylesheet_directory_uri() . '/assets/css/progress-bar.css',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/css/progress-bar.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_progress_bar_styles', 99);

// Load other theme files
require_once get_stylesheet_directory() . '/inc/shortcodes/loader.php';
require_once get_stylesheet_directory() . '/includes/ld30-styles.php';

// Load YITH status check (admin only)
if (is_admin()) {
    require_once get_stylesheet_directory() . '/inc/admin/yith-status-check.php';
}

// Load Quiz Follow-up System
require_once get_stylesheet_directory() . '/includes/messaging/class-quiz-followup.php';

// Load Subscription Activation System
require_once get_stylesheet_directory() . '/includes/subscription-activation.php';

// Load Ultimate Member integration if UM is active
function ccr_load_um_integration() {
    if (class_exists('UM')) {
        require_once get_stylesheet_directory() . '/includes/integrations/class-ultimate-member-integration.php';
    }
}
add_action('after_setup_theme', 'ccr_load_um_integration', 5);

// Load Messaging System
function ccr_load_messaging_system() {
    // Re-enabled with memory optimization
    if (!defined('LILAC_MESSAGING_LOADED')) {
        define('LILAC_MESSAGING_LOADED', true);
        require_once get_stylesheet_directory() . '/includes/messaging/notifications.php';
    }
    
    if (is_admin() && !defined('LILAC_ADMIN_LOADED')) {
        define('LILAC_ADMIN_LOADED', true);
        require_once get_stylesheet_directory() . '/includes/messaging/admin-functions.php';
    }
    
    // Enqueue toast system and alert integration scripts
    add_action('wp_enqueue_scripts', 'lilac_enqueue_toast_system');
    add_action('wp_footer', 'lilac_add_toast_debug_code');
}
add_action('after_setup_theme', 'ccr_load_messaging_system', 10);

/**
 * Enqueue Toast Notification System scripts
 */
function lilac_enqueue_toast_system() {
    // Force script versions to prevent caching during development
    $force_version = time();
    
    // Enqueue jQuery as a dependency
    wp_enqueue_script('jquery');
    
    // Enqueue Toast message system CSS FIRST
    wp_enqueue_style(
        'toast-system-css',
        get_stylesheet_directory_uri() . '/includes/messaging/css/toast-system.css',
        [],
        $force_version
    );
    
    // Enqueue Toast message system
    wp_enqueue_script(
        'toast-message-system',
        get_stylesheet_directory_uri() . '/includes/messaging/js/toast-system.js',
        ['jquery'],
        $force_version,
        true // Load in footer for better performance
    );
    
    // Enqueue Session Toast Extension
    wp_enqueue_script(
        'toast-session',
        get_stylesheet_directory_uri() . '/includes/messaging/js/session-toast.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Test Timer Extension
    wp_enqueue_script(
        'toast-test-timer',
        get_stylesheet_directory_uri() . '/includes/messaging/js/test-timer-toast.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Alert Helpers
    wp_enqueue_script(
        'alert-helpers',
        get_stylesheet_directory_uri() . '/includes/messaging/js/alert-helpers.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Lilac Bonus Coupon System
    wp_enqueue_script(
        'lilac-bonus-coupon',
        get_stylesheet_directory_uri() . '/includes/messaging/js/lilac-bonus-coupon.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Toast Extensions CSS
    wp_enqueue_style(
        'toast-extensions-css',
        get_stylesheet_directory_uri() . '/includes/messaging/css/toast-extensions.css',
        ['toast-system-css'],
        $force_version
    );
    
    // Get Lilac Bonus settings
    $lilac_bonus_settings = get_option('lilac_messaging_settings', []);
    
    // Localize toast settings
    wp_localize_script('toast-message-system', 'toastSettings', [
        'defaultDuration' => 5000,
        'position' => 'top-right', // Make sure the position is set correctly
        'enableAlertIntegration' => true,
        'debugMode' => true
    ]);
    
    // Localize Lilac Bonus settings
    wp_localize_script('lilac-bonus-coupon', 'lilacBonusSettings', [
        'enabled' => !empty($lilac_bonus_settings['enable_lilac_bonus']),
        'generalMessage' => $lilac_bonus_settings['lilac_bonus_message_general'] ?? 'רוצה להצטרף לקורס במחיר מיוחד? לחץ כאן לפרטים נוספים!',
        'purchaserMessage' => $lilac_bonus_settings['lilac_bonus_message_purchaser'] ?? 'ברכות על רכישת התרגול! קוד ההנחה שלך לקורסים: {coupon_code}',
        'couponCode' => $lilac_bonus_settings['lilac_bonus_coupon_code'] ?? 'LILACBONUS',
        'excludeCourses' => !empty($lilac_bonus_settings['lilac_bonus_exclude_courses']),
        'sessionKey' => 'lilac_bonus_shown'
    ]);
    
    // Add a small fix to make sure the toast container uses the correct position
    wp_add_inline_script('toast-message-system', '
        jQuery(document).ready(function($) {
            // Force the container to use the correct position
            if ($("#lilac-toast-container").length) {
                $("#lilac-toast-container").attr("class", "top-right");
                console.log("Toast container position set to top-right");
            }
        });
    ');
}

/**
 * Add debug code to test toast functionality
 */
function lilac_add_toast_debug_code() {
    ?>
    <script type="text/javascript">
    /* Toast System Debug Code */
    console.log('Toast Debug Script Loaded');
    
    // Create global test function
    window.TestToast = function() {
        console.log('Testing Toast System...');
        
        if (typeof window.LilacToast !== 'undefined') {
            console.log('LilacToast API found!');
            window.LilacToast.success('Toast API is working!', 'Success');
            return 'Test successful';
        } else {
            console.log('LilacToast API not found');
            alert('This is a native alert - LilacToast not loaded');
            return 'Test failed';
        }
    };
    
    // Test alert integration
    window.TestAlert = function(message) {
        console.log('Testing Alert Integration...');
        alert(message || 'This is a test alert');
        return 'Alert test completed';
    };
    
    // Log toast system status on page load
    jQuery(document).ready(function($) {
        console.log('Toast System Status:', {
            'jQuery Loaded': typeof $ === 'function',
            'LilacToast Available': typeof window.LilacToast === 'function',
            'LilacShowToast Available': typeof window.LilacShowToast === 'function',
            'Alert Overridden': window.alert !== window.originalAlert
        });
    });
    </script>
    <?php
}

// Load Login System
function ccr_load_login_system() {
    if (!is_admin()) {
        error_log('Loading LoginManager...');
        
        $login_manager_path = get_stylesheet_directory() . '/src/Login/LoginManager.php';
        
        if (file_exists($login_manager_path)) {
            error_log('LoginManager.php found, including file...');
            require_once $login_manager_path;
            
            // Check if the class exists and can be loaded
            if (class_exists('Lilac\Login\LoginManager')) {
                error_log('LoginManager class exists, initializing...');
                // The init method will handle the initialization
                $instance = Lilac\Login\LoginManager::init();
                if ($instance) {
                    error_log('LoginManager initialized successfully');
                } else {
                    error_log('WARNING: LoginManager::init() returned null');
                }
            } else {
                error_log('ERROR: Lilac\Login\LoginManager class not found!');
            }
        } else {
            error_log('ERROR: LoginManager.php not found at: ' . $login_manager_path);
        }
        
        // Load other required files
        $captcha_path = get_stylesheet_directory() . '/src/Login/Captcha.php';
        if (file_exists($captcha_path)) {
            require_once $captcha_path;
        }
        
        $widget_path = get_stylesheet_directory() . '/src/Login/UserAccountWidget.php';
        if (file_exists($widget_path)) {
            require_once $widget_path;
        }
    } else {
        error_log('Skipping login system load in admin');
    }
}
add_action('after_setup_theme', 'ccr_load_login_system', 10);

/**
 * Debug function to log YITH subscription data
 */
function lilac_log_subscription_data($data, $title = 'YITH Subscription Debug') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('=== ' . $title . ' ===');
        if (is_array($data) || is_object($data)) {
            error_log(print_r($data, true));
        } else {
            error_log($data);
        }
        error_log('==================');
    }
}

/**
 * Get user's subscriptions by directly querying the database
 */
function lilac_get_user_subscriptions_directly($user_id) {
    global $wpdb;
    
    // Get all subscription posts for the user
    $subscription_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}posts p 
        INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id 
        WHERE p.post_type = 'ywsbs_subscription' 
        AND pm.meta_key = 'user_id' 
        AND pm.meta_value = %d",
        $user_id
    ));
    
    // Get all subscription meta for each subscription
    $subscriptions = array();
    foreach ($subscription_posts as $post) {
        $subscription = new stdClass();
        $subscription->ID = $post->ID;
        $subscription->post = $post;
        $subscription->meta = get_post_meta($post->ID);
        $subscriptions[] = $subscription;
    }
    
    return $subscriptions;
}

/**
 * Debug function to check YITH plugin status
 */
function lilac_check_yith_plugin_status() {
    global $wpdb;
    
    $status = array(
        'is_plugin_active' => class_exists('YITH_WC_Subscription'),
        'is_plugin_installed' => file_exists(WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/init.php'),
        'plugin_version' => defined('YITH_YWSBS_VERSION') ? YITH_YWSBS_VERSION : 'Not defined',
        'plugin_paths' => array(
            'main_file' => defined('YITH_YWSBS_INIT') ? YITH_YWSBS_INIT : 'Not defined',
            'templates' => defined('YITH_YWSBS_TEMPLATE_PATH') ? YITH_YWSBS_TEMPLATE_PATH : 'Not defined',
        ),
        'database_tables' => array(
            'posts' => $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}posts'"),
            'postmeta' => $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}postmeta'")
        ),
        'subscription_post_type' => array(
            'registered' => post_type_exists('ywsbs_subscription'),
            'count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ywsbs_subscription'")
        )
    );
    
    return $status;
}

/**
 * Get all subscriptions from the database with their meta data
 */
function lilac_get_all_subscriptions() {
    global $wpdb;
    
    $subscriptions = array();
    
    // Get all subscription posts
    $posts = $wpdb->get_results(
        "SELECT * FROM {$wpdb->posts} 
        WHERE post_type = 'ywsbs_subscription' 
        ORDER BY ID DESC"
    );
    
    if (empty($posts)) {
        return array();
    }
    
    // Get all meta for these posts in a single query
    $post_ids = array_map('intval', wp_list_pluck($posts, 'ID'));
    $post_ids_placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    
    $meta_results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_key, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ($post_ids_placeholders)",
        $post_ids
    ));
    
    // Organize meta by post ID
    $meta_by_post = array();
    foreach ($meta_results as $meta) {
        if (!isset($meta_by_post[$meta->post_id])) {
            $meta_by_post[$meta->post_id] = array();
        }
        $meta_by_post[$meta->post_id][$meta->meta_key] = maybe_unserialize($meta->meta_value);
    }
    
    // Combine posts with their meta
    foreach ($posts as $post) {
        $subscription = array(
            'ID' => $post->ID,
            'post_status' => $post->post_status,
            'post_date' => $post->post_date,
            'meta' => isset($meta_by_post[$post->ID]) ? $meta_by_post[$post->ID] : array()
        );
        
        $subscriptions[] = $subscription;
    }
    
    return $subscriptions;
}

/**
 * YITH WooCommerce Subscription Controls Shortcode
 * 
 * Usage: [yith_subscription_controls] or [yith_subscription_controls id="123"]
 */
add_shortcode('yith_subscription_controls', function($atts) {
    // Debug mode
    $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
    
    // Check if we're in debug mode via shortcode attribute
    $debug = isset($atts['debug']) && $atts['debug'] === 'yes';
    $debug_mode = $debug_mode || $debug;
    
    // Check if YITH WooCommerce Subscription is active
    $plugin_status = lilac_check_yith_plugin_status();
    
    if ($debug_mode) {
        error_log('YITH Plugin Status: ' . print_r($plugin_status, true));
    }
    
    // Check if plugin is active and required functions exist
    if (!function_exists('ywsbs_get_subscription') || !class_exists('YWSBS_Subscription_User')) {
        $error_msg = 'Error: Required YITH WooCommerce Subscription plugin functions not found.';
        if ($debug) error_log($error_msg);
        if (current_user_can('manage_options')) {
            return '<div class="notice notice-error"><p>' . esc_html($error_msg) . '</p></div>';
        }
        return '';
    }
    
    if (!$plugin_status['is_plugin_active']) {
        $error_msg = 'Error: YITH WooCommerce Subscription plugin is not active.';
        if ($debug) error_log($error_msg);
        if (current_user_can('manage_options')) {
            return '<div class="notice notice-error"><p>' . esc_html($error_msg) . '</p></div>';
        }
        return '';
    }

    // Only show to logged in users
    if (!is_user_logged_in()) {
        $error_msg = 'Please log in to manage your subscription.';
        if ($debug) error_log($error_msg);
        return '<div class="notice notice-info"><p>' . __($error_msg, 'hello-theme-child') . '</p></div>';
    }

    $atts = shortcode_atts([
        'id' => '',
        'status' => 'any', // any, active, paused, cancelled, expired, pending
        'debug' => $debug_mode ? 'yes' : 'no'
    ], $atts, 'yith_subscription_controls');

    $debug = $atts['debug'] === 'yes';
    $user_id = get_current_user_id();
    $output = '';

    try {
        if ($debug_mode) {
            error_log('YITH Subscription Shortcode - User ID: ' . $user_id);
            error_log('YITH Subscription Shortcode - Attributes: ' . print_r($atts, true));
            
            // Log all subscription post types in the database
            global $wpdb;
            $subscription_posts = $wpdb->get_results(
                "SELECT ID, post_title, post_status, post_type, post_author 
                FROM {$wpdb->posts} 
                WHERE post_type = 'ywsbs_subscription'"
            );
            error_log('All subscription posts in database: ' . print_r($subscription_posts, true));
            
            // Log all user's subscriptions from database
            $user_subscriptions = lilac_get_user_subscriptions_directly($user_id);
            error_log('User subscriptions from direct query: ' . print_r($user_subscriptions, true));
        }

        // Get all user's subscriptions using plugin's method
        $all_subscriptions = array();
        try {
            if (class_exists('YWSBS_Subscription_User') && method_exists('YWSBS_Subscription_User', 'get_subscriptions')) {
                $all_subscriptions = YWSBS_Subscription_User::get_subscriptions($user_id);
            } elseif (function_exists('ywsbs_get_user_subscriptions')) {
                $all_subscriptions = ywsbs_get_user_subscriptions($user_id);
            }
            
            // If no subscriptions found via plugin, try direct DB query
            if (empty($all_subscriptions)) {
                $direct_subscriptions = lilac_get_user_subscriptions_directly($user_id);
                if (!empty($direct_subscriptions)) {
                    if ($debug) error_log('Found ' . count($direct_subscriptions) . ' subscriptions via direct DB query');
                    // Convert to format expected by the template
                    foreach ($direct_subscriptions as $sub) {
                        if (is_object($sub) && isset($sub->ID)) {
                            $subscription = ywsbs_get_subscription($sub->ID);
                            if ($subscription) {
                                $all_subscriptions[] = $subscription;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($debug) error_log('Error getting subscriptions: ' . $e->getMessage());
        }
        
        if ($debug_mode) {
            error_log('Total subscriptions found via plugin: ' . count($all_subscriptions));
            if (!empty($all_subscriptions)) {
                foreach ($all_subscriptions as $sub) {
                    error_log(sprintf(
                        'Subscription ID: %d, Status: %s, Product ID: %d, User ID: %d',
                        $sub->get_id(),
                        $sub->get_status(),
                        $sub->get_product_id(),
                        $sub->get_user_id()
                    ));
                }
            } else {
                // Try alternative method to get subscriptions
                $args = array(
                    'post_type' => 'ywsbs_subscription',
                    'meta_key' => 'user_id',
                    'meta_value' => $user_id,
                    'posts_per_page' => -1,
                    'post_status' => 'any'
                );
                $subscription_posts = get_posts($args);
                error_log('Alternative query found ' . count($subscription_posts) . ' subscriptions');
                
                if (!empty($subscription_posts)) {
                    $subscription_ids = wp_list_pluck($subscription_posts, 'ID');
                    error_log('Subscription IDs found: ' . implode(', ', $subscription_ids));
                }
            }
        }

        // Filter by ID if specified
        if (!empty($atts['id'])) {
            $subscription = ywsbs_get_subscription($atts['id']);
            
            if (!$subscription || $subscription->get_user_id() != $user_id) {
                $error_msg = 'Subscription not found or access denied.';
                if ($debug) error_log($error_msg);
                return '<div class="notice notice-warning"><p>' . __($error_msg, 'hello-theme-child') . '</p></div>';
            }
            
            // Check if subscription status matches the filter
            if ($atts['status'] !== 'any' && !$subscription->has_status($atts['status'])) {
                if ($debug) error_log('Subscription status does not match filter');
                return '';
            }
            
            $subscriptions = array($subscription);
        } else {
            $subscriptions = $all_subscriptions;
            
            // Filter by status if needed
            if ($atts['status'] !== 'any') {
                $filtered_subscriptions = array();
                foreach ($subscriptions as $subscription) {
                    if ($subscription->has_status($atts['status'])) {
                        $filtered_subscriptions[] = $subscription;
                    }
                }
                $subscriptions = $filtered_subscriptions;
                if ($debug) error_log('After status filtering, subscriptions count: ' . count($subscriptions));
            }
        }
        
        if (empty($subscriptions)) {
            $error_msg = 'No subscriptions found for the current user.';
            if ($debug) error_log($error_msg);
            return '<div class="notice notice-info"><p>' . __($error_msg, 'hello-theme-child') . '</p>' . 
                   ($debug ? '<pre>' . print_r($all_subscriptions, true) . '</pre>' : '') . '</div>';
        }

        // Debug template path
        $template_path = '';
        if (defined('YITH_YWSBS_TEMPLATE_PATH')) {
            $template_path = YITH_YWSBS_TEMPLATE_PATH . '/myaccount/my-subscriptions.php';
        } else {
            // Fallback path if constant not defined
            $template_path = WP_PLUGIN_DIR . '/yith-woocommerce-subscription-premium/templates/myaccount/my-subscriptions.php';
        }
        
        if ($debug) {
            error_log('Template path: ' . $template_path);
            error_log('Template exists: ' . (file_exists($template_path) ? 'Yes' : 'No'));
            
            if (!file_exists($template_path)) {
                error_log('Template search paths:');
                error_log('- ' . $template_path);
                error_log('- ' . get_stylesheet_directory() . '/woocommerce/myaccount/my-subscriptions.php');
                error_log('- ' . get_template_directory() . '/woocommerce/myaccount/my-subscriptions.php');
            }
        }
        
        // Start output
        ob_start();
        
        // Include the template part that shows the subscriptions table
        if (file_exists($template_path)) {
            wc_get_template(
                'myaccount/my-subscriptions.php',
                array(
                    'subscriptions' => $subscriptions,
                    'current_page' => 1,
                    'pages' => 1,
                    'current_view' => $atts['status'],
                    'subscription_endpoint' => 'subscriptions',
                    'endpoint' => 'subscriptions',
                ),
                '',
                YITH_YWSBS_TEMPLATE_PATH . '/'
            );
        } else {
            // Fallback template if the main one doesn't exist
            echo '<div class="ywsbs-subscriptions-list">';
            echo '<h3>' . __('Your Subscriptions', 'yith-woocommerce-subscription') . '</h3>';
            
            if (!empty($subscriptions)) {
                echo '<ul class="ywsbs-subscriptions">';
                foreach ($subscriptions as $subscription) {
                    echo '<li class="subscription-item">';
                    echo '<h4>' . sprintf(__('Subscription #%s', 'yith-woocommerce-subscription'), $subscription->get_id()) . '</h4>';
                    echo '<p>Status: ' . $subscription->get_status() . '</p>';
                    echo '<p>Product: ' . get_the_title($subscription->get_product_id()) . '</p>';
                    echo '<p>Next Payment: ' . $subscription->get_payment_due_date() . '</p>';
                    // Add more subscription details as needed
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('No subscriptions found.', 'yith-woocommerce-subscription') . '</p>';
            }
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
    } catch (Exception $e) {
        $error_msg = 'Error: ' . $e->getMessage();
        if ($debug) error_log($error_msg);
        
        if (current_user_can('manage_options')) {
            return '<div class="notice notice-error"><p>' . 
                   $error_msg . 
                   '</p><pre>' . $e->getTraceAsString() . '</pre></div>';
        }
        return '<div class="notice notice-error"><p>' . 
               __('An error occurred while loading subscription information.', 'hello-theme-child') . 
               '</p></div>';
    }

    // Enqueue the plugin's styles
    if (function_exists('YWSBS_Subscription_Assets') && method_exists('YWSBS_Subscription_Assets', 'enqueue_styles_scripts')) {
        YWSBS_Subscription_Assets()->enqueue_styles_scripts();
    } elseif ($debug_mode) {
        error_log('Could not enqueue YITH Subscription assets');
    }
    
    // Add debug info for admins
    if ($debug_mode && current_user_can('manage_options')) {
        $all_subs = lilac_get_all_subscriptions();
        $user_subs = array_filter($all_subs, function($sub) use ($user_id) {
            return isset($sub['meta']['user_id']) && $sub['meta']['user_id'] == $user_id;
        });
        
        $output .= '<div class="yith-debug-info" style="margin: 20px 0; padding: 15px; background: #f8f8f8; border: 1px solid #ddd; max-height: 500px; overflow: auto;">';
        $output .= '<h3>YITH Subscription Debug Information</h3>';
        
        // Plugin status
        $output .= '<h4>Plugin Status:</h4>';
        $output .= '<pre>' . print_r($plugin_status, true) . '</pre>';
        
        // Current user info
        $output .= '<h4>Current User:</h4>';
        $output .= '<pre>User ID: ' . $user_id . '</pre>';
        
        // All subscriptions in database
        $output .= '<h4>All Subscriptions in Database (' . count($all_subs) . '):</h4>';
        $output .= '<pre>' . print_r(array_map(function($sub) {
            return [
                'ID' => $sub['ID'],
                'status' => $sub['post_status'],
                'date' => $sub['post_date'],
                'user_id' => $sub['meta']['user_id'] ?? 'N/A',
                'product_id' => $sub['meta']['product_id'] ?? 'N/A',
                'status_meta' => $sub['meta']['status'] ?? 'N/A'
            ];
        }, $all_subs), true) . '</pre>';
        
        // Current user's subscriptions
        $output .= '<h4>Current User\'s Subscriptions (' . count($user_subs) . '):</h4>';
        if (!empty($user_subs)) {
            $output .= '<pre>' . print_r(array_map(function($sub) {
                return [
                    'ID' => $sub['ID'],
                    'status' => $sub['post_status'],
                    'date' => $sub['post_date'],
                    'product_id' => $sub['meta']['product_id'] ?? 'N/A',
                    'status_meta' => $sub['meta']['status'] ?? 'N/A'
                ];
            }, $user_subs), true) . '</pre>';
        } else {
            $output .= '<p>No subscriptions found for this user in the database.</p>';
        }
        
        // Debug log note
        $output .= '<div style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ddd;">';
        $output .= '<h4>Debug Notes:</h4>';
        $output .= '<ul>';
        $output .= '<li>Check if the subscription post type exists in the database</li>';
        $output .= '<li>Verify the user_id meta_key is correctly set on subscriptions</li>';
        $output .= '<li>Check WooCommerce > YITH Subscriptions for active subscriptions</li>';
        $output .= '<li>Review the debug.log for more detailed information</li>';
        $output .= '</ul>';
        $output .= '</div>';
        
        $output .= '</div>';
    }

    return $output;
});

// Shortcode to display trial subscription button
add_shortcode('demo_subscription_button', function($atts) {
    if (!is_user_logged_in()) {
        return '<div class="notice notice-info">' . 
               __('Please log in to start your free trial.', 'hello-theme-child') . 
               '</div>';
    }
    
    $demo_product_id = get_option('lilac_demo_subscription_id');
    if (!$demo_product_id) {
        return '<div class="notice notice-error">' . 
               __('Trial subscription is not available at the moment.', 'hello-theme-child') . 
               '</div>';
    }
    
    $product = wc_get_product($demo_product_id);
    if (!$product) {
        return '<div class="notice notice-error">' . 
               __('Invalid trial subscription product.', 'hello-theme-child') . 
               '</div>';
    }
    
    // Enqueue necessary scripts
    wp_enqueue_script('wc-add-to-cart');
    
    ob_start();
    ?>
    <div class="demo-subscription-box">
        <h3><?php _e('Start Your 14-Day Free Trial', 'hello-theme-child'); ?></h3>
        <p><?php _e('Get full access to all premium features for 14 days. No credit card required.', 'hello-theme-child'); ?></p>
        
        <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data">
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($demo_product_id); ?>" class="single_add_to_cart_button button alt">
                <?php _e('Start Free Trial', 'hello-theme-child'); ?>
            </button>
        </form>
        
        <p class="trial-terms">
            <small><?php _e('By clicking "Start Free Trial", you agree to our Terms of Service and Privacy Policy.', 'hello-theme-child'); ?></small>
        </p>
    </div>
    
    <style>
    .demo-subscription-box {
        max-width: 500px;
        margin: 2em auto;
        padding: 2em;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-align: center;
    }
    .demo-subscription-box h3 {
        margin-top: 0;
        color: #333;
    }
    .demo-subscription-box .button {
        background-color: #4CAF50;
        color: white;
        padding: 12px 24px;
        font-size: 16px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: bold;
    }
    .demo-subscription-box .button:hover {
        background-color: #45a049;
    }
    .trial-terms {
        margin-top: 1.5em;
        color: #666;
        font-size: 0.9em;
    }
    </style>
    <?php
    return ob_get_clean();
});

// DISABLED: Admin notice for YITH WooCommerce Subscription - plugin not in use
/*
add_action('admin_notices', function() {
    if (!class_exists('YITH_WC_Subscription') && current_user_can('activate_plugins')) {
        echo '<div class="notice notice-warning"><p>' . 
             sprintf(
                 __('The YITH WooCommerce Subscription plugin is required for the subscription controls shortcode to work. %s', 'hello-theme-child'),
                 '<a href="' . admin_url('plugin-install.php?s=yith+woocommerce+subscription&tab=search&type=term') . '">' . 
                 __('Install/Activate it now', 'hello-theme-child') . '</a>'
             ) . 
             '</p></div>';
    }
});
*/

/**
 * Add custom body class for LearnDash courses
 */
function add_learndash_course_body_class($classes) {
    if (is_singular('sfwd-courses')) {
        global $post;
        if ($post && isset($post->ID)) {
            $classes[] = 'course-id-' . $post->ID;
        }
    }
    return $classes;
}
add_filter('body_class', 'add_learndash_course_body_class', 20);

// Add body class for quiz types
add_filter('body_class', function($classes) {
    if (is_singular('sfwd-quiz')) {
        global $post;
        if ($post) {
            $classes[] = 'quiz-' . $post->ID;
            
            // Safely check for enforce hint setting
            $enforce_hint = get_post_meta($post->ID, 'lilac_quiz_enforce_hint', true);
            if ($enforce_hint === '1' || $enforce_hint === 'yes') {
                $classes[] = 'forced-hint-quiz';
            }
        }
    }
    return $classes;
}, 5); // Lower priority to ensure it runs early

// Debug helper function
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}


function remove_css_js_version_query( $src ) {
    if ( strpos( $src, '?ver=' ) !== false ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'remove_css_js_version_query', 9999 );
add_filter( 'script_loader_src', 'remove_css_js_version_query', 9999 );

// Load custom user functionality
require_once get_stylesheet_directory() . '/includes/users/custom-user-redirects.php';

// Load registration code functionality (with safety check)
if (file_exists(get_stylesheet_directory() . '/includes/core/registration-functions.php')) {
    require_once get_stylesheet_directory() . '/includes/core/registration-functions.php';
}
require_once get_stylesheet_directory() . '/includes/users/class-user-dashboard-shortcode.php';

// Include LearnDash navigation enhancements (with safety checks for production)
if (file_exists(get_stylesheet_directory() . '/includes/learndash/back-to-course-button.php')) {
    require_once get_stylesheet_directory() . '/includes/learndash/back-to-course-button.php';
}
if (file_exists(get_stylesheet_directory() . '/includes/learndash/navigation-enhancements.php')) {
    require_once get_stylesheet_directory() . '/includes/learndash/navigation-enhancements.php';
}

// Load Learndash Dashboard Widget
if (file_exists(get_stylesheet_directory() . '/inc/widgets/LearndashDashboard/LearndashDashboard.php')) {
    require_once get_stylesheet_directory() . '/inc/widgets/LearndashDashboard/LearndashDashboard.php';
    new \Windstorm\Widgets\LearndashDashboard();
}

// Create Demo Subscription Product
add_action('init', 'lilac_maybe_create_demo_subscription', 99); // Higher priority to ensure YITH is loaded
function lilac_maybe_create_demo_subscription() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        error_log('[LILAC] WooCommerce is not active. Cannot create demo subscription product.');
        return;
    }
    
    // Check if YITH WooCommerce Subscription is active
    if (!function_exists('ywsbs_is_subscription_product')) {
        // DISABLED: YITH plugin not active - skip subscription product creation
        return;
    }
    
    // Use a transient to prevent multiple runs
    $transient_key = 'lilac_demo_subscription_creating';
    if (get_transient($transient_key)) {
        error_log('[LILAC] Demo subscription creation already in progress');
        return;
    }
    
    // Set transient for 5 minutes to prevent multiple runs
    set_transient($transient_key, '1', 5 * MINUTE_IN_SECONDS);
    
    // Check if the demo subscription product already exists
    $demo_product_id = get_option('lilac_demo_subscription_id');
    if ($demo_product_id && 'publish' === get_post_status($demo_product_id)) {
        $product = wc_get_product($demo_product_id);
        if ($product) {
            error_log('[LILAC] Demo subscription product already exists with ID: ' . $demo_product_id);
            delete_transient($transient_key);
            return;
        }
    }
    
    try {
        error_log('[LILAC] Starting to create demo subscription product');
        
        // Create a new simple product with subscription meta
        $product = new WC_Product_Simple();
        $product->set_name('14-Day Premium Trial');
        $product->set_slug('14-day-premium-trial');
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_price(0);
        $product->set_regular_price(0);
        $product->set_sale_price(0);
        $product->set_sold_individually(true);
        
        // Set product description
        $product->set_description('Start your 14-day free trial of our premium features. No credit card required. Cancel anytime.');
        $product->set_short_description('14 days free trial of premium features');
        
        // Set as subscription
        $product->update_meta_data('_ywsbs_subscription', 'yes');
        $product->update_meta_data('_ywsbs_price_is_per', '1'); // Billing interval (1)
        $product->update_meta_data('_ywsbs_price_time_option', 'days'); // Billing period (days)
        $product->update_meta_data('_ywsbs_max_length', '1'); // One-time payment (trial only)
        $product->update_meta_data('_ywsbs_trial_per', '14'); // 14-day trial
        $product->update_meta_data('_ywsbs_trial_time_option', 'days');
        $product->update_meta_data('_ywsbs_trial_fee', '0'); // Free trial
        
        // Set categories
        $default_cat = get_option('default_product_cat');
        if ($default_cat) {
            $product->set_category_ids(array($default_cat));
        }
        
        // Save the product
        $product_id = $product->save();
        
        if (is_wp_error($product_id)) {
            throw new Exception('Failed to save product: ' . $product_id->get_error_message());
        }
        
        // Save the product ID for future reference
        update_option('lilac_demo_subscription_id', $product_id);
        
        // Verify the product was created correctly
        $created_product = wc_get_product($product_id);
        if (!$created_product) {
            throw new Exception('Failed to verify created product');
        }
        
        // Log success with product details
        error_log('[LILAC] Successfully created demo subscription product with ID: ' . $product_id);
        error_log('[LILAC] Product details: ' . print_r([
            'name' => $created_product->get_name(),
            'status' => $created_product->get_status(),
            'price' => $created_product->get_price(),
            'is_subscription' => ywsbs_is_subscription_product($created_product) ? 'yes' : 'no',
            'subscription_meta' => [
                'price_is_per' => $created_product->get_meta('_ywsbs_price_is_per'),
                'price_time_option' => $created_product->get_meta('_ywsbs_price_time_option'),
                'max_length' => $created_product->get_meta('_ywsbs_max_length'),
                'trial_per' => $created_product->get_meta('_ywsbs_trial_per'),
                'trial_time_option' => $created_product->get_meta('_ywsbs_trial_time_option'),
                'trial_fee' => $created_product->get_meta('_ywsbs_trial_fee')
            ]
        ], true));
        
    } catch (Exception $e) {
        error_log('[LILAC] Error creating demo subscription product: ' . $e->getMessage());
        if (isset($product_id) && $product_id) {
            wp_delete_post($product_id, true); // Clean up if product was partially created
        }
    } finally {
        // Always delete the transient when done
        delete_transient($transient_key);
    }
}

// Load Registration Codes System
function load_registration_codes_system() {
    // Only load once
    static $loaded = false;
    if ($loaded) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Registration codes system already loaded, skipping');
        }
        return;
    }
    $loaded = true;
    
    $registration_codes_file = get_stylesheet_directory() . '/includes/registration/class-registration-codes.php';
    $promo_code_file = get_stylesheet_directory() . '/includes/promo-code.php';
    
    // Log whether files exist
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Registration codes file exists: ' . (file_exists($registration_codes_file) ? 'Yes' : 'No'));
        error_log('Promo code file exists: ' . (file_exists($promo_code_file) ? 'Yes' : 'No'));
    }
    
    // Load the registration codes file (main class)
    if (file_exists($registration_codes_file)) {
        require_once $registration_codes_file;
        if (class_exists('Registration_Codes')) {
            // Use the singleton instance
            $registration_codes = Registration_Codes::get_instance();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration_Codes class loaded via singleton');
            }
        }
    }
    
    // Load the promo code file if it exists
    if (file_exists($promo_code_file)) {
        require_once $promo_code_file;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Promo code file loaded');
            error_log('Shortcode promo_code is registered: ' . (shortcode_exists('promo_code') ? 'Yes' : 'No'));
        }
    } else {
        error_log('ERROR: Promo code file not found: ' . $promo_code_file);
    }
    
    // Load the registration wizard if the main class exists
    $wizard_file = get_stylesheet_directory() . '/includes/registration/class-registration-wizard.php';
    if (file_exists($wizard_file)) {
        require_once $wizard_file;
        if (class_exists('Registration_Wizard') && method_exists('Registration_Wizard', 'get_instance')) {
            // Get the singleton instance
            $registration_wizard = Registration_Wizard::get_instance();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration wizard instance retrieved');
            }
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ERROR: Registration_Wizard class or get_instance method not found');
        }
    } else {
        error_log('ERROR: Registration wizard file not found: ' . $wizard_file);
    }
    
    // Load the registration admin interface
    $admin_file = get_stylesheet_directory() . '/includes/registration/class-registration-admin.php';
    if (file_exists($admin_file)) {
        require_once $admin_file;
        if (class_exists('Registration_Admin') && method_exists('Registration_Admin', 'get_instance')) {
            // Get the singleton instance
            $registration_admin = Registration_Admin::get_instance();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Registration admin instance retrieved');
            }
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ERROR: Registration_Admin class or get_instance method not found');
        }
    } else {
        error_log('ERROR: Registration admin file not found: ' . $admin_file);
    }
}

// Initialize the registration system
add_action('after_setup_theme', 'load_registration_codes_system', 5);

// Load Quiz Assets
function enqueue_quiz_assets() {
    if (is_singular('sfwd-quiz')) {
        global $post;
        if (!$post) return;
        
        $quiz_id = $post->ID;
        $enforce_hint = get_post_meta($quiz_id, 'lilac_quiz_enforce_hint', true);
        
        // Enqueue quiz styles
        $css_path = get_stylesheet_directory() . '/includes/quiz/assets/css/quiz-styles.css';
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'lilac-quiz-styles',
                get_stylesheet_directory_uri() . '/includes/quiz/assets/css/quiz-styles.css',
                array(),
                filemtime($css_path)
            );
        }
        
        // Enqueue jQuery UI dialog if not already enqueued
        if (!wp_script_is('jquery-ui-dialog', 'enqueued')) {
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
        }
        
        // Skip theme quiz script - optimized plugin script handles this
        // $reselect_js_path = get_stylesheet_directory() . '/includes/quiz/assets/js/quiz-answer-reselection.js';
        
        // Only enqueue quiz timer script if enabled in admin settings
        $progress_settings = get_option('lilac_messaging_progress', [
            'enable_test_timer' => '0',
            'test_duration' => 60,
            'warning_time' => 10,
            'critical_time' => 2,
            'enable_session_warning' => '0',
            'session_timeout' => 30,
            'warning_before' => 5
        ]);
        
        $timer_js_path = get_stylesheet_directory() . '/assets/js/quiz-timer.js';
        if (file_exists($timer_js_path) && $progress_settings['enable_test_timer'] === '1') {
            wp_enqueue_script(
                'lilac-quiz-timer',
                get_stylesheet_directory_uri() . '/assets/js/quiz-timer.js',
                array('jquery', 'toast-message-system'), // Ensure toast system is loaded first
                filemtime($timer_js_path),
                true
            );
            
            // Debug script loading
            add_action('wp_footer', function() {
                ?>
                <script type="text/javascript">
                    console.log('Quiz Timer Script Loaded:', {
                        jQuery: typeof jQuery !== 'undefined',
                        LilacShowToast: typeof LilacShowToast,
                        LilacToast: typeof LilacToast
                    });
                </script>
                <?php
            }, 9999);
            
            // Settings already retrieved above
            
            // Localize script with quiz timer settings
            wp_localize_script('lilac-quiz-timer', 'lilacQuizTimerVars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lilac-quiz-timer-nonce'),
                'enableTestTimer' => $progress_settings['enable_test_timer'] === '1',
                'testDuration' => intval($progress_settings['test_duration']),
                'warningTime' => intval($progress_settings['warning_time']),
                'criticalTime' => intval($progress_settings['critical_time']),
                'enableSessionWarning' => $progress_settings['enable_session_warning'] === '1',
                'sessionTimeout' => intval($progress_settings['session_timeout']),
                'warningBefore' => intval($progress_settings['warning_before'])
            ));
        }
        
        // Enqueue main quiz script - Note: This is now handled by QuizFlowManager.php
        // to ensure proper script handles and dependencies
        // The script is enqueued with handle 'lilac-quiz-answer-flow'
    }
}
add_action('wp_enqueue_scripts', 'enqueue_quiz_assets', 20);

// Load Teacher Dashboard
require_once get_stylesheet_directory() . '/includes/users/class-teacher-dashboard.php';

// Load Teacher-Instructor Role Mapping
require_once get_stylesheet_directory() . '/includes/users/teacher-role-mapping.php';

// Initialize the Teacher Dashboard after theme setup
add_action('after_setup_theme', function() {
    if (class_exists('Lilac_Teacher_Dashboard')) {
        $teacher_dashboard = Lilac_Teacher_Dashboard::get_instance();
    }
});

// Register the [teacher_dashboard] shortcode
add_shortcode('teacher_dashboard', function($atts) {
    if (!is_user_logged_in()) {
        return '<div class="notice notice-warning">' . __('Please log in to view the teacher dashboard.', 'hello-theme-child') . '</div>';
    }
    
    if (!current_user_can('school_teacher') && !current_user_can('administrator')) {
        return '<div class="notice notice-error">' . __('You do not have permission to view this page.', 'hello-theme-child') . '</div>';
    }
    
    if (class_exists('Lilac_Teacher_Dashboard')) {
        return Lilac_Teacher_Dashboard::get_instance()->render_dashboard();
    }
    
    return '<div class="notice notice-error">' . __('Teacher dashboard is not available.', 'hello-theme-child') . '</div>';
});

// Load WooCommerce Customizations
if (class_exists('WooCommerce')) {
    require_once get_stylesheet_directory() . '/includes/woocommerce/class-woocommerce-customizations.php';
}

// Load Teacher/Class Wizard
function init_teacher_class_wizard() {
    // Only run in admin
    if (!is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
        return null;
    }
    
    $wizard_file = get_stylesheet_directory() . '/includes/admin/class-teacher-class-wizard.php';
    
    if (!file_exists($wizard_file)) {
        error_log('TEACHER_WIZARD: Error - Wizard file not found at: ' . $wizard_file);
        
        add_action('admin_notices', function() use ($wizard_file) {
            echo '<div class="notice notice-error"><p>Teacher/Class Wizard: File not found: ' . esc_html($wizard_file) . '</p></div>';
        });
        
        return null;
    }
    
    require_once $wizard_file;
    
    if (!class_exists('Teacher_Class_Wizard')) {
        error_log('TEACHER_WIZARD: Error - Teacher_Class_Wizard class not found after including file');
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Teacher/Class Wizard: Class not found after including file</p></div>';
        });
        
        return null;
    }
    
    // Use the singleton instance
    try {
        $wizard = Teacher_Class_Wizard::get_instance();
        
        if (!is_object($wizard)) {
            throw new Exception('Failed to get wizard instance');
        }
        
        return $wizard;
        
    } catch (Exception $e) {
        error_log('TEACHER_WIZARD: Failed to initialize wizard: ' . $e->getMessage());
        
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>Failed to initialize Teacher/Class Wizard: ' . 
                 esc_html($e->getMessage()) . '</p></div>';
        });
        
        return null;
    }
}

// Initialize the wizard
add_action('init', function() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        $wizard = init_teacher_class_wizard();
        
        // Load wizard fixes
        $wizard_fix_file = get_stylesheet_directory() . '/includes/admin/class-teacher-class-wizard-fix.php';
        if (file_exists($wizard_fix_file)) {
            require_once $wizard_fix_file;
        }
        
        // If we're on our admin page, enqueue scripts
        if (isset($_GET['page']) && $_GET['page'] === 'class-management') {
            if ($wizard && method_exists($wizard, 'enqueue_scripts')) {
                add_action('admin_enqueue_scripts', array($wizard, 'enqueue_scripts'));
            }
        }
    }
}, 1);

// Load other admin functionality
if (is_admin()) {
    require_once get_stylesheet_directory() . '/includes/admin/thank-you-page-settings.php';
    
    // Load Subscription Manager
    require_once get_stylesheet_directory() . '/includes/class-lilac-subscription.php';
    
    // Enqueue subscription styles
    add_action('wp_enqueue_scripts', function() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        $css = "
        /* Subscription Box */
        .lilac-subscription-box {
            max-width: 600px;
            margin: 40px auto;
            padding: 25px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .lilac-subscription-box h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
        }
        .lilac-subscription-box p {
            margin-bottom: 15px;
            color: #666;
        }
        /* Toggle Switch */
        #lilac-subscription-toggle {
            width: 40px;
            height: 20px;
            position: relative;
            -webkit-appearance: none;
            background: #ddd;
            border-radius: 20px;
            outline: none;
            transition: .4s;
            cursor: pointer;
            vertical-align: middle;
            margin: 0 10px 0 0;
        }
        #lilac-subscription-toggle:checked {
            background: #4CAF50;
        }
        #lilac-subscription-toggle:before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            background: white;
            transition: .4s;
        }
        #lilac-subscription-toggle:checked:before {
            transform: translateX(20px);
        }
        /* Admin column */
        .column-subscription {
            text-align: center;
            width: 80px;
        }
        .subscription-status {
            display: inline-block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
        }
        .subscription-status.yes {
            color: #4CAF50;
        }
        .subscription-status.no {
            color: #ccc;
        }";
        
        wp_add_inline_style('woocommerce-general', $css);
    }, 20);
}



// ============================================
// WooCommerce Checkout Redirect Functionality
// ============================================

// Add to Cart button text is handled by the ccr_custom_add_to_cart_text function below

// Debug user creation during checkout
add_action('woocommerce_created_customer', function($user_id, $new_customer_data, $password_generated) {
    error_log("=== NEW CUSTOMER CREATED ===");
    error_log("User ID: " . $user_id);
    error_log("Email: " . $new_customer_data['user_email']);
    error_log("=================================");
}, 10, 3);

// Debug order processing
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    error_log("=== ORDER PROCESSING ===");
    error_log("Order ID: " . $order_id);
    error_log("Current User ID: " . get_current_user_id());
    error_log("Order User ID: " . $order->get_user_id());
    error_log("Customer IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not available'));
    error_log("=================================");
}, 10, 3);

// 2. Clear cart before adding new product and handle add to cart
add_filter('woocommerce_add_to_cart_validation', 'clear_cart_before_adding', 10, 3);
function clear_cart_before_adding($passed, $product_id, $quantity) {
    // Debug log
    error_log('Adding to cart - Product ID: ' . $product_id . ', Quantity: ' . $quantity);
    
    // Clear the cart before adding new item
    if (!WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
        error_log('Cart was not empty - Cleared cart before adding new item');
    }
    
    return $passed;
}

// 3. Force redirect to checkout on any add-to-cart action
add_action('template_redirect', 'force_checkout_redirect', 20);
function force_checkout_redirect() {
    // Ensure WooCommerce is loaded
    if (!function_exists('is_cart') || !function_exists('wc_get_checkout_url') || !class_exists('WooCommerce')) {
        return;
    }
    
    // Debug log
    error_log('Force redirect check - is_cart: ' . (is_cart() ? 'yes' : 'no') . ', add-to-cart: ' . (isset($_REQUEST['add-to-cart']) ? $_REQUEST['add-to-cart'] : 'not set'));
    
    if (is_cart() || (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart']))) {
        if (!WC()->cart->is_empty()) {
            error_log('Redirecting to checkout');
            wp_redirect(wc_get_checkout_url());
            exit;
        } else {
            error_log('Cannot redirect - Cart is empty');
        }
    }
}

// 3.1 Fix for AJAX add to cart
// add_filter('woocommerce_add_to_cart_fragments', 'intercept_ajax_add_to_cart');
// function intercept_ajax_add_to_cart($fragments) {
//     // Don't run in admin or Elementor editor
//     if (is_admin() || 
//         (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->editor->is_edit_mode()) || 
//         (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->preview->is_preview_mode()) ||
//         (isset($_GET['elementor-preview']))) {
//         return $fragments;
//     }
    
//     error_log('AJAX add to cart intercepted');
//     if (!WC()->cart->is_empty()) {
//         wp_send_json(array(
//             'error' => false,
//             'redirect' => wc_get_checkout_url()
//         ));
//     }
//     $fragments['redirect_url'] = wc_get_checkout_url();
//     return $fragments;
// }

// 4. Add JavaScript to handle all add to cart actions
add_action('wp_footer', 'add_checkout_redirect_js', 999);
function add_checkout_redirect_js() {
    // Check if WooCommerce functions exist
    if (!function_exists('wc_get_checkout_url')) {
        return;
    }
    
    // Don't run in admin or Elementor editor
    if (is_admin() || 
        (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->editor->is_edit_mode()) || 
        (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->preview->is_preview_mode()) ||
        (isset($_GET['elementor-preview']))) {
        return;
    }
    
    // Get the checkout URL with a random parameter to prevent caching
    $checkout_url = add_query_arg('nocache', time(), wc_get_checkout_url());
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // 1. Intercept all add to cart forms
        $('body').on('submit', 'form.cart', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $button = $form.find('.single_add_to_cart_button');
            
            // Disable button to prevent multiple clicks
            $button.prop('disabled', true).addClass('loading');
            
            // Submit the form via AJAX
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: $form.serialize() + '&action=woocommerce_add_to_cart',
                success: function(response) {
                    // Redirect to checkout on success
                    window.location.href = '<?php echo esc_js($checkout_url); ?>';
                },
                error: function() {
                    // If AJAX fails, submit the form normally
                    $form.off('submit').submit();
                }
            });
            
            return false;
        });
        
        // 2. Handle simple add to cart links
        $('body').on('click', '.add_to_cart_button:not(.product_type_variable, .product_type_grouped, .product_type_external)', function(e) {
            e.preventDefault();
            var $button = $(this);
            
            // Skip if already processing
            if ($button.hasClass('loading')) return false;
            
            // Get the product ID and URL
            var product_id = $button.data('product_id');
            var product_url = $button.attr('href');
            
            // Disable button
            $button.addClass('loading');
            
            // Add to cart via AJAX
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: 'add-to-cart=' + product_id + '&action=woocommerce_add_to_cart',
                success: function() {
                    // Redirect to checkout
                    window.location.href = '<?php echo esc_js($checkout_url); ?>';
                },
                error: function() {
                    // If AJAX fails, redirect to the product URL
                    window.location.href = product_url;
                }
            });
            
            return false;
        });
        
        // 3. Handle AJAX add to cart events
        $(document.body).on('added_to_cart', function() {
            // Small delay to ensure cart is updated
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($checkout_url); ?>';
            }, 100);
        });
    });
    </script>
    <?php
}

/**
 * Strip the “/ 12 months …” tail that YITH Subscription
 * appends after the actual number.
 */
add_filter( 'woocommerce_get_price_html', 'ld_show_price_only_on_subs', 20, 2 );
function ld_show_price_only_on_subs( $html, $product ) {

	// only touch YITH-subscription product types
	if ( in_array( $product->get_type(), [ 'ywsbs_subscription', 'subscription' ], true ) ) {

		// keep the first <span class="woocommerce-Price-amount …">…</span>
		if ( preg_match( '/<span class="woocommerce-Price-amount.*?<\/span>/i', $html, $m ) ) {
			return $m[0];           // “₪ 49.00”
		}
	}

	return $html;                  // anything else: leave untouched
}

add_action('admin_head', function() {
    if (function_exists('lilac_quiz_sidebar_add_meta_box')) {
        remove_all_actions('add_meta_boxes_sfwd-quiz');
        add_action('add_meta_boxes_sfwd-quiz', 'lilac_quiz_sidebar_add_meta_box');
    }
}, 20);


// Add admin menu item
function lqs_add_sidebar_tools_menu() {
    add_menu_page(
        'Quiz Tools',
        'Quiz Tools',
        'manage_options',
        'quiz-tools',
        'lqs_render_sidebar_tools_page',
        'dashicons-admin-tools',
        100
    );
};
add_action('admin_menu', 'lqs_add_sidebar_tools_menu');

/**
 * Manual enrollment function for existing customers
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Enroll Existing Customers',
        'Enroll Customers',
        'manage_options',
        'enroll-existing-customers',
        'render_enroll_customers_page'
    );
});

function render_enroll_customers_page() {
    if (isset($_POST['enroll_customers']) && check_admin_referer('enroll_customers_nonce')) {
        $enrolled_count = 0;
        
        // Get all completed orders
        $orders = wc_get_orders(array(
            'status' => array('completed', 'processing'),
            'limit' => -1
        ));
        
        foreach ($orders as $order) {
            $user_id = $order->get_user_id();
            if (!$user_id) continue;
            
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $product_name = $item->get_name();
                
                // Try to find matching course
                $courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'title' => $product_name,
                    'posts_per_page' => 1,
                    'post_status' => 'publish'
                ));
                
                if (!empty($courses) && function_exists('ld_update_course_access')) {
                    $course_id = $courses[0]->ID;
                    
                    // Check if user is already enrolled
                    $user_courses = learndash_user_get_enrolled_courses($user_id);
                    if (!in_array($course_id, $user_courses)) {
                        ld_update_course_access($user_id, $course_id, false);
                        $enrolled_count++;
                        error_log("Manually enrolled user {$user_id} in course {$course_id}");
                    }
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>Successfully enrolled ' . $enrolled_count . ' users in their purchased courses.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Enroll Existing Customers</h1>
        <p>This tool will enroll existing customers who have completed purchases into their corresponding LearnDash courses.</p>
        
        <form method="post">
            <?php wp_nonce_field('enroll_customers_nonce'); ?>
            <p>
                <input type="submit" name="enroll_customers" class="button button-primary" 
                       value="Enroll All Existing Customers" 
                       onclick="return confirm('This will process all completed orders and enroll users in matching courses. Continue?');">
            </p>
        </form>
        
        <h3>How it works:</h3>
        <ul>
            <li>Finds all completed WooCommerce orders</li>
            <li>Matches product names with LearnDash course titles</li>
            <li>Enrolls users in matching courses (skips if already enrolled)</li>
            <li>Logs enrollment activity for debugging</li>
        </ul>
    </div>
    <?php
}

// Add CSS to hide unwanted checkout elements (keeping coupons enabled)
add_action('wp_head', function() {
    if (is_checkout()) {
        echo '<style>
            #ship-to-different-address,
            .woocommerce-shipping-fields,
            .shipping_address,
            .woocommerce-additional-fields,
            .woocommerce-billing-fields h3,
            .woocommerce-shipping-fields h3,
            .woocommerce-additional-fields h3,
            .woocommerce-billing-fields__field-wrapper label,
            .woocommerce-shipping-fields__field-wrapper label,
            .woocommerce-additional-fields__field-wrapper label,
            .woocommerce-billing-fields .form-row label:not(.woocommerce-form__label-for-checkbox),
            .form-row.promo_code_field {
                display: none !important;
            }
            
            .woocommerce-billing-fields input::placeholder,
            .woocommerce-billing-fields textarea::placeholder {
                opacity: 1 !important;
                color: #777 !important;
            }
        </style>';
    }
});

/**
 * Mark user as purchaser for Lilac Bonus coupon system
 * This ensures they see the coupon message instead of the general message
 */
add_action('woocommerce_order_status_completed', 'lilac_mark_user_as_purchaser');
add_action('woocommerce_order_status_processing', 'lilac_mark_user_as_purchaser');
add_action('woocommerce_payment_complete', 'lilac_mark_user_as_purchaser');

function lilac_mark_user_as_purchaser($order_id) {
    error_log("=== LILAC PURCHASE DEBUG START ===");
    error_log("Order ID: " . $order_id);
    error_log("Current user logged in: " . (is_user_logged_in() ? 'YES' : 'NO'));
    error_log("Current user ID: " . get_current_user_id());
    
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("ERROR: Could not get order");
        return;
    }
    
    $user_id = $order->get_user_id();
    error_log("Order user ID: " . $user_id);
    error_log("Order status: " . $order->get_status());
    
    if ($user_id) {
        // Mark user as purchaser in user meta
        update_user_meta($user_id, 'lilac_user_purchased', 'true');
        update_user_meta($user_id, 'lilac_purchase_date', current_time('mysql'));
        
        // Enroll user in LearnDash courses and set expiration based on purchased products
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            
            // Get access duration from product settings
            $access_duration = get_post_meta($product_id, '_wc_learndash_access_duration', true);
            $custom_expiry_date = get_post_meta($product_id, '_wc_learndash_custom_expiry_date', true);
            
            // Get associated LearnDash courses for this product
            $associated_courses = get_post_meta($product_id, '_related_course', true);
            
            if (!empty($associated_courses)) {
                // Handle both single course and multiple courses
                if (!is_array($associated_courses)) {
                    $associated_courses = array($associated_courses);
                }
                
                foreach ($associated_courses as $course_id) {
                    if (!empty($course_id) && function_exists('ld_update_course_access')) {
                        // Enroll user in the course
                        ld_update_course_access($user_id, $course_id, false);
                        
                        // Set expiration based on product settings
                        if (!empty($access_duration) || !empty($custom_expiry_date)) {
                            $expiry_timestamp = lilac_calculate_expiry_timestamp($access_duration, $custom_expiry_date);
                            if ($expiry_timestamp) {
                                update_user_meta($user_id, "course_{$course_id}_access_expires", $expiry_timestamp);
                                error_log("Set expiration for user {$user_id}, course {$course_id}: " . date('Y-m-d H:i:s', $expiry_timestamp));
                            }
                        }
                        
                        error_log("Enrolled user {$user_id} in course {$course_id} after purchase of product {$product_id}");
                    }
                }
            } else {
                // Fallback: Check if product name matches course name
                $product_name = $item->get_name();
                $courses = get_posts(array(
                    'post_type' => 'sfwd-courses',
                    'title' => $product_name,
                    'posts_per_page' => 1,
                    'post_status' => 'publish'
                ));
                
                if (!empty($courses)) {
                    $course_id = $courses[0]->ID;
                    if (function_exists('ld_update_course_access')) {
                        ld_update_course_access($user_id, $course_id, false);
                        
                        // Set expiration based on product settings
                        if (!empty($access_duration) || !empty($custom_expiry_date)) {
                            $expiry_timestamp = lilac_calculate_expiry_timestamp($access_duration, $custom_expiry_date);
                            if ($expiry_timestamp) {
                                update_user_meta($user_id, "course_{$course_id}_access_expires", $expiry_timestamp);
                                error_log("Set expiration for user {$user_id}, course {$course_id}: " . date('Y-m-d H:i:s', $expiry_timestamp));
                            }
                        }
                        
                        error_log("Enrolled user {$user_id} in course {$course_id} (matched by name) after purchase");
                    }
                }
            }
        }
        
        // Set session flags for recent purchase to prevent false access denial
        update_user_meta($user_id, 'lilac_recent_purchase_time', current_time('timestamp'));
        error_log("Set recent purchase time for user: " . $user_id);
        
        // Ensure user is logged in - fix session issues
        if (!is_user_logged_in() || get_current_user_id() != $user_id) {
            error_log("=== LOGIN DEBUG START ===");
            error_log("Current user before login: " . get_current_user_id() . " (logged in: " . (is_user_logged_in() ? 'yes' : 'no') . ")");
            error_log("Attempting to log in user ID: " . $user_id);
            
            // Check if user exists
            $user_data = get_userdata($user_id);
            if (!$user_data) {
                error_log("ERROR: User ID {$user_id} does not exist");
            } else {
                error_log("User found: " . $user_data->user_email);
            }
            
            // Set current user
            $set_user = wp_set_current_user($user_id);
            error_log("wp_set_current_user result: " . print_r($set_user, true));
            
            // Set auth cookie
            $cookie_set = wp_set_auth_cookie($user_id, true);
            error_log("wp_set_auth_cookie result: " . ($cookie_set ? 'Success' : 'Failed'));
            
            // Verify login
            $current_user_after = wp_get_current_user();
            error_log("After login attempt - User ID: " . $current_user_after->ID . " (logged in: " . (is_user_logged_in() ? 'yes' : 'no') . ")");
            
            // Check session token
            $session_tokens = get_user_meta($user_id, 'session_tokens', true);
            error_log("Session tokens: " . print_r($session_tokens, true));
            
            // Check cookies
            error_log("Cookies after login: " . print_r($_COOKIE, true));
            
            error_log("=== LOGIN DEBUG END ===");
        } else {
            error_log("User {$user_id} already logged in, no need to re-login");
            error_log("Current user: " . get_current_user_id() . " (logged in: " . (is_user_logged_in() ? 'yes' : 'no') . ")");
        }
        
        // Also add JavaScript to mark in frontend storage
        add_action('wp_footer', function() {
            echo '<script>'
                . 'localStorage.setItem("lilac_user_purchased", "true");'
                . 'sessionStorage.setItem("lilac_recent_purchase", "true");'
                . 'sessionStorage.removeItem("lilac_bonus_shown");' // Clear session so message can show again
                . '</script>';
        });
        
        error_log("=== LILAC PURCHASE DEBUG END ===");
    } else {
        error_log("ERROR: No user ID found for order");
    }
}

/**
 * Calculate expiry timestamp based on product access duration settings
 */
function lilac_calculate_expiry_timestamp($access_duration, $custom_expiry_date) {
    $current_time = current_time('timestamp');
    
    // If custom expiry date is set, use it
    if (!empty($custom_expiry_date)) {
        $expiry_timestamp = strtotime($custom_expiry_date);
        if ($expiry_timestamp && $expiry_timestamp > $current_time) {
            return $expiry_timestamp;
        }
    }
    
    // If access duration is set, calculate from current time
    if (!empty($access_duration)) {
        // Parse duration format (e.g., "2 weeks", "30 days", "1 month")
        $expiry_timestamp = strtotime("+{$access_duration}", $current_time);
        if ($expiry_timestamp && $expiry_timestamp > $current_time) {
            return $expiry_timestamp;
        }
    }
    
    return false; // No valid expiration found
}

/**
 * Add user purchase status to frontend for Lilac Bonus system
 */
add_action('wp_footer', 'lilac_add_user_purchase_status_to_frontend');
function lilac_add_user_purchase_status_to_frontend() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $has_purchased = get_user_meta($user_id, 'lilac_user_purchased', true) === 'true';
        
        // Check if user has any completed orders
        if (!$has_purchased) {
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('completed', 'processing'),
                'limit' => 1
            ));
            $has_purchased = !empty($orders);
            
            // Update user meta if they have purchases
            if ($has_purchased) {
                update_user_meta($user_id, 'lilac_user_purchased', 'true');
            }
        }
        
        echo '<script>'
            . 'window.lilacUserData = window.lilacUserData || {};'
            . 'window.lilacUserData.hasPurchased = ' . ($has_purchased ? 'true' : 'false') . ';'
            . 'if (' . ($has_purchased ? 'true' : 'false') . ') {'
                . 'localStorage.setItem("lilac_user_purchased", "true");'
            . '}'
            . '</script>';
    }
}

/**
 * Translate WooCommerce and other user-facing strings to Hebrew
 */
add_filter('gettext', 'lilac_translate_strings_to_hebrew', 20, 3);
function lilac_translate_strings_to_hebrew($translated_text, $text, $domain) {
    // WooCommerce coupon section translations
    $translations = array(
        'If you have a coupon code, please apply it below.' => 'אם יש לך קוד קופון, הזן אותו למטה.',
        'Coupon code' => 'קוד קופון',
        'Apply' => 'החל',
        'Apply coupon' => 'החל קופון',
        'No Students Found' => 'לא נמצאו תלמידים',
        'There are no students currently enrolled in your classes.' => 'אין תלמידים רשומים בכיתות שלך כרגע.',
        'Access denied. Teacher privileges required.' => 'הגישה נדחתה. נדרשות הרשאות מורה.',
        'Debug Information (Admin Only)' => 'מידע לניפוי תקלות (למנהלים בלבד)',
        'Teacher ID:' => 'מזהה מורה:',
        'Classes Found:' => 'כיתות שנמצאו:',
        'Class IDs:' => 'מזהי כיתות:',
        'Last Query:' => 'שאילתה אחרונה:',
        'Last Error:' => 'שגיאה אחרונה:'
    );
    
    // Return Hebrew translation if available
    if (isset($translations[$text])) {
        return $translations[$text];
    }
    
    return $translated_text;
}

/**
 * Handle logout redirects to bypass WordPress confirmation page
 * Redirect users directly to homepage instead of showing wp_die confirmation
 */
add_action('wp_logout', 'lilac_custom_logout_redirect');
function lilac_custom_logout_redirect() {
    // Redirect to homepage after logout
    wp_redirect(home_url('/'));
    exit;
}

/**
 * Customize logout URL to avoid confirmation page
 */
add_filter('logout_url', 'lilac_custom_logout_url', 10, 2);
function lilac_custom_logout_url($logout_url, $redirect) {
    // If no specific redirect is set, redirect to homepage
    if (empty($redirect)) {
        $redirect = home_url('/');
    }
    
    // Create logout URL that bypasses confirmation
    $logout_url = wp_nonce_url(
        add_query_arg('action', 'logout', wp_login_url()),
        'log-out'
    );
    
    // Add redirect parameter
    $logout_url = add_query_arg('redirect_to', urlencode($redirect), $logout_url);
    
    return $logout_url;
}

/**
 * Handle session timeout logout requests
 */
add_action('wp_ajax_lilac_session_logout', 'lilac_handle_session_logout');
add_action('wp_ajax_nopriv_lilac_session_logout', 'lilac_handle_session_logout');
function lilac_handle_session_logout() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'lilac_session_logout')) {
        wp_die('Security check failed');
    }
    
    // Log out the user
    wp_logout();
    
    // Return success response
    wp_send_json_success(array(
        'redirect_url' => home_url('/')
    ));
}

/**
 * Intercept logout action to prevent wp_die confirmation page
 */
add_action('init', 'lilac_intercept_logout');
function lilac_intercept_logout() {
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        // Verify nonce
        if (wp_verify_nonce($_GET['_wpnonce'], 'log-out')) {
            // Get redirect URL or default to homepage
            $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url('/');
            
            // Log out the user
            wp_logout();
            
            // Redirect to specified URL or homepage
            wp_redirect($redirect_to);
            exit;
        }
    }
}

// Render the tools page
function lqs_render_sidebar_tools_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    $message_type = 'info';

    // Process form submission for enabling sidebar
    if (isset($_POST['enable_all_sidebars']) && check_admin_referer('quiz_tools_nonce')) {
        $quizzes = get_posts([
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ]);

        $updated = 0;
        foreach ($quizzes as $quiz_id) {
            if (update_post_meta($quiz_id, '_ld_quiz_toggle_sidebar', '1')) {
                $updated++;
            }
        }

        $message = sprintf(__('Successfully enabled rich media sidebar for %d quizzes.', 'text-domain'), $updated);
        $message_type = 'success';
    }

    // Process form submission for enabling enforce hint
    if (isset($_POST['enable_all_enforce_hints']) && check_admin_referer('quiz_tools_nonce')) {
        $quizzes = get_posts([
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ]);

        $updated = 0;
        foreach ($quizzes as $quiz_id) {
            if (update_post_meta($quiz_id, '_ld_quiz_enforce_hint', '1')) {
                $updated++;
            }
        }

        $message = sprintf(__('Successfully enabled enforce hint for %d quizzes.', 'text-domain'), $updated);
        $message_type = 'success';
    }

    // Process form submission for enabling autostart
    if (isset($_POST['enable_all_autostart']) && check_admin_referer('quiz_tools_nonce')) {
        global $wpdb;
        
        // Get all quizzes
        $quizzes = get_posts([
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ]);

        if (empty($quizzes)) {
            // Try alternative method to find quizzes
            $quizzes = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sfwd-quiz' AND post_status IN ('publish', 'draft', 'pending')");
        }

        $updated = 0;
        $debug_info = [];
        
        foreach ($quizzes as $quiz_id) {
            // Prepare per-quiz debug bucket
            $debug_info[$quiz_id] = [];

            /* -------------------------------------------------
             * 1. Update the LearnDash display/content meta
             * -------------------------------------------------*/
            $current_settings = get_post_meta($quiz_id, '_ld_quiz_display_content_settings', true);
            $debug_info[$quiz_id]['before_meta'] = $current_settings;
            if ( ! is_array( $current_settings ) ) {
                $current_settings = [];
            }
            $new_settings = array_merge( $current_settings, [ 'autostart' => 'on' ] );
            $meta_result   = update_post_meta( $quiz_id, '_ld_quiz_display_content_settings', $new_settings, $current_settings );
            $debug_info[$quiz_id]['after_meta']   = $new_settings;
            $debug_info[$quiz_id]['meta_result']  = $meta_result;

            /* -------------------------------------------------
             * 2. Update the underlying wpProQuiz model record
             * -------------------------------------------------*/
            $pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
            if ( $pro_quiz_id ) {
                if ( ! class_exists( 'WpProQuiz_Model_QuizMapper' ) ) {
                    include_once WP_PLUGIN_DIR . '/sfwd-lms/includes/lib/wp-pro-quiz/lib/model/WpProQuiz_Model_QuizMapper.php';
                }

                $quiz_mapper = new WpProQuiz_Model_QuizMapper();
                $quiz_model  = $quiz_mapper->fetch( (int) $pro_quiz_id );

                if ( $quiz_model && method_exists( $quiz_model, 'setAutostart' ) ) {
                    $quiz_model->setAutostart( true );
                    // Persist to DB — save() returns affected rows
                    $save_rows                              = $quiz_mapper->save( $quiz_model );
                    $debug_info[$quiz_id]['pro_id']         = $pro_quiz_id;
                    $debug_info[$quiz_id]['save_rows']      = $save_rows;
                    $debug_info[$quiz_id]['model_updated']  = true;
                } else {
                    $debug_info[$quiz_id]['model_updated'] = false;
                }
            } else {
                $debug_info[$quiz_id]['pro_id']        = null;
                $debug_info[$quiz_id]['model_updated'] = false;
            }

            /* -------------------------------------------------
             * 3. Update counter if anything changed
             * -------------------------------------------------*/
            if ( $meta_result || ! empty( $save_rows ) ) {
                $updated++;
            }
        }

        // Save debug info to a log file
        $log_file = WP_CONTENT_DIR . '/debug_quiz_autostart.log';
        file_put_contents($log_file, print_r([
            'time' => current_time('mysql'),
            'queries' => $wpdb->last_query,
            'quizzes_found' => count($quizzes),
            'quizzes_updated' => $updated,
            'debug_info' => $debug_info
        ], true), FILE_APPEND);

        if ($updated > 0) {
            $message = sprintf(__('Successfully processed %d quizzes. Autostart is now enabled for all quizzes.', 'text-domain'), $updated);
            $message_type = 'success';
            
            // Clear any LearnDash transients that might be caching quiz settings
            if (function_exists('learndash_quiz_deactivate_quiz_content_meta_box_ajax')) {
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_learndash_quiz_settings_%'");
            }
        } else {
            $message = __('No quizzes were found or updated. Debug info saved to: ', 'text-domain') . $log_file;
            $message_type = 'error';
        }
    }

    ?>
    <div class="wrap">
        <h1>Quiz Tools</h1>
        
        <?php if ($message) : ?>
            <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
            <h2>Quiz Autostart</h2>
            <p>Enable autostart for all quizzes. This will automatically start quizzes without showing the "Start Quiz" button.</p>
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('quiz_tools_nonce'); ?>
                <input type="submit" name="enable_all_autostart" class="button button-primary" 
                       value="Enable Autostart for All Quizzes" 
                       onclick="return confirm('Are you sure you want to enable autostart for ALL quizzes?');">
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
            <h2>Rich Media Sidebar</h2>
            <p>Enable the rich media sidebar for all quizzes.</p>
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('quiz_tools_nonce'); ?>
                <input type="submit" name="enable_all_sidebars" class="button button-primary" 
                       value="Enable for All Quizzes" 
                       onclick="return confirm('Are you sure you want to enable rich media sidebar for ALL quizzes?');">
            </form>
        </div>

        <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
            <h2>Question Review Table</h2>
            <p>Enable the question overview table ("Show Review Question") for all quizzes.</p>
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('quiz_tools_nonce'); ?>
                <input type="submit" name="enable_all_show_review" class="button button-primary"
                       value="Enable Question Review Table for All Quizzes"
                       onclick="return confirm('Are you sure you want to enable the question review table for ALL quizzes?');">
            </form>
        </div>

        <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
            <h2>Enforce Hint</h2>
            <p>Enable enforce hint for all quizzes. This will require users to use hints before proceeding.</p>
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('quiz_tools_nonce'); ?>
                <input type="submit" name="enable_all_enforce_hints" class="button button-primary" 
                       value="Enable Enforce Hint for All Quizzes" 
                       onclick="return confirm('Are you sure you want to enable enforce hint for ALL quizzes?');">
            </form>
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
        }
    </style>
}

/* -------------------------------------------------
 * 3. Update counter if anything changed
 * -------------------------------------------------*/
if ( $meta_result || ! empty( $save_rows ) ) {
    $updated++;
}

// Save debug info to a log file
$log_file = WP_CONTENT_DIR . '/debug_quiz_autostart.log';
file_put_contents($log_file, print_r([
    'time' => current_time('mysql'),
    'queries' => $wpdb->last_query,
    'quizzes_found' => count($quizzes),
    'quizzes_updated' => $updated,
    'debug_info' => $debug_info
], true), FILE_APPEND);

if ($updated > 0) {
    $message = sprintf(__('Successfully processed %d quizzes. Autostart is now enabled for all quizzes.', 'text-domain'), $updated);
    $message_type = 'success';
    
    // Clear any LearnDash transients that might be caching quiz settings
    if (function_exists('learndash_quiz_deactivate_quiz_content_meta_box_ajax')) {
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_learndash_quiz_settings_%'");
    }
} else {
    $message = __('No quizzes were found or updated. Debug info saved to: ', 'text-domain') . $log_file;
    $message_type = 'error';
}

// Process form submission for enabling Question Review Table (showReviewQuestion)
if (isset($_POST['enable_all_show_review']) && check_admin_referer('quiz_tools_nonce')) {
    global $wpdb;

    $quizzes = get_posts([
        'post_type'      => 'sfwd-quiz',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]);
    if (empty($quizzes)) {
        $quizzes = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sfwd-quiz' AND post_status IN ('publish','draft','pending')");
    }

    $updated    = 0;
    $debug_info = [];
    foreach ($quizzes as $quiz_id) {
        $debug_info[$quiz_id] = [];

        // 1. Post meta update
        $current_settings                    = get_post_meta($quiz_id, '_ld_quiz_display_content_settings', true);
        $debug_info[$quiz_id]['before_meta'] = $current_settings;
        if (!is_array($current_settings)) {
            $current_settings = [];
        }
        $new_settings                         = array_merge($current_settings, ['showReviewQuestion' => 'on']);
        $meta_result                          = update_post_meta($quiz_id, '_ld_quiz_display_content_settings', $new_settings, $current_settings);
        $debug_info[$quiz_id]['after_meta']   = $new_settings;
        $debug_info[$quiz_id]['meta_result']  = $meta_result;

        // 2. wpProQuiz model update
        $pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        $save_rows   = 0;
        if ($pro_quiz_id) {
            if (!class_exists('WpProQuiz_Model_QuizMapper')) {
                include_once WP_PLUGIN_DIR . '/sfwd-lms/includes/lib/wp-pro-quiz/lib/model/WpProQuiz_Model_QuizMapper.php';
            }
            $mapper     = new WpProQuiz_Model_QuizMapper();
            $quiz_model = $mapper->fetch((int) $pro_quiz_id);
            if ($quiz_model && method_exists($quiz_model, 'setShowReviewQuestion')) {
                $quiz_model->setShowReviewQuestion(true);
                $save_rows = $mapper->save($quiz_model);
            }
        }
        $debug_info[$quiz_id]['pro_id']    = $pro_quiz_id;
        $debug_info[$quiz_id]['save_rows'] = $save_rows;

        if ($meta_result || !empty($save_rows)) {
            $updated++;
        }
    }

    $log_file = WP_CONTENT_DIR . '/debug_quiz_show_review.log';
    file_put_contents($log_file, print_r([
        'time'            => current_time('mysql'),
        'quizzes_found'   => count($quizzes),
        'quizzes_updated' => $updated,
        'debug_info'      => $debug_info,
    ], true), FILE_APPEND);

    if ($updated > 0) {
        $message      = sprintf(__('Successfully processed %d quizzes. Question Review Table is now enabled.', 'text-domain'), $updated);
        $message_type = 'success';
    } else {
        $message      = __('No quizzes were found or updated. Debug info saved to: ', 'text-domain') . $log_file;
        $message_type = 'error';
    }
}

?>
<div class="wrap">
    <h1>Quiz Tools</h1>
    
    <?php if ($message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
        <h2>Quiz Autostart</h2>
        <p>Enable autostart for all quizzes. This will automatically start quizzes without showing the "Start Quiz" button.</p>
        <form method="post" style="margin-top: 15px;">
            <?php wp_nonce_field('quiz_tools_nonce'); ?>
            <input type="submit" name="enable_all_autostart" class="button button-primary" 
                   value="Enable Autostart for All Quizzes" 
                   onclick="return confirm('Are you sure you want to enable autostart for ALL quizzes?');">
        </form>
    </div>
    
    <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
        <h2>Rich Media Sidebar</h2>
        <p>Enable the rich media sidebar for all quizzes.</p>
        <form method="post" style="margin-top: 15px;">
            <?php wp_nonce_field('quiz_tools_nonce'); ?>
            <input type="submit" name="enable_all_sidebars" class="button button-primary" 
                   value="Enable for All Quizzes" 
                   onclick="return confirm('Are you sure you want to enable rich media sidebar for ALL quizzes?');">
        </form>
    </div>

    <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
        <h2>Question Review Table</h2>
        <p>Enable the question overview table ("Show Review Question") for all quizzes.</p>
        <form method="post" style="margin-top: 15px;">
            <?php wp_nonce_field('quiz_tools_nonce'); ?>
            <input type="submit" name="enable_all_show_review" class="button button-primary"
                   value="Enable Question Review Table for All Quizzes"
                   onclick="return confirm('Are you sure you want to enable the question review table for ALL quizzes?');">
        </form>
    </div>

    <div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
        <h2>Enforce Hint</h2>
        <p>Enable enforce hint for all quizzes. This will require users to use hints before proceeding.</p>
        <form method="post" style="margin-top: 15px;">
            <?php wp_nonce_field('quiz_tools_nonce'); ?>
            <input type="submit" name="enable_all_enforce_hints" class="button button-primary" 
                   value="Enable Enforce Hint for All Quizzes" 
                   onclick="return confirm('Are you sure you want to enable enforce hint for ALL quizzes?');">
        </form>
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
    }
</style>
<?php
}


// Load Promo Codes
require_once get_stylesheet_directory() . '/includes/class-promo-codes.php';

// Load AJAX handlers
if (file_exists(get_stylesheet_directory() . '/inc/ajax/load-ajax-handlers.php')) {
    require_once get_stylesheet_directory() . '/inc/ajax/load-ajax-handlers.php';
    
    // Add test endpoint for AJAX handler verification
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            'AJAX Handler Test',
            'AJAX Handler Test',
            'manage_options',
            'ajax-handler-test',
            'render_ajax_handler_test_page'
        );
    });
    
    function render_ajax_handler_test_page() {
        // Include the AJAX handler file if it exists
        $handler_file = get_stylesheet_directory() . '/inc/ajax/teacher-students-export.php';
        if (file_exists($handler_file)) {
            include_once $handler_file;
        }
        
        // Get current user info
        $user = wp_get_current_user();
        $nonce = wp_create_nonce('export_teacher_students_' . $user->ID);
        $ajax_url = admin_url('admin-ajax.php');
        $test_url = add_query_arg([
            'action' => 'export_teacher_students',
            'nonce' => $nonce,
            'teacher_id' => $user->ID,
            'test' => '1'
        ], $ajax_url);
        
        // Check registration
        $action = 'wp_ajax_export_teacher_students';
        $registered = has_action($action, 'handle_teacher_students_export');
        $function_exists = function_exists('handle_teacher_students_export');
        $nonce_valid = wp_verify_nonce($nonce, 'export_teacher_students_' . $user->ID);
        
        // Output the test page
        ?>
        <div class="wrap">
            <h1>AJAX Handler Test</h1>
            
            <div class="card">
                <h2>AJAX Handler Status</h2>
                <p><strong>Action Registered:</strong> 
                    <?php echo $registered !== false ? 
                        '<span style="color:green">✅ Yes (Priority: ' . $registered . ')</span>' : 
                        '<span style="color:red">❌ No</span>'; ?>
                </p>
                <p><strong>Function Exists:</strong> 
                    <?php echo $function_exists ? 
                        '<span style="color:green">✅ Yes</span>' : 
                        '<span style="color:red">❌ No</span>'; ?>
                </p>
                <p><strong>Nonce Valid:</strong> 
                    <?php echo $nonce_valid ? 
                        '<span style="color:green">✅ Yes</span>' : 
                        '<span style="color:red">❌ No</span>'; ?>
                </p>
                
                <h3>Test AJAX Endpoint</h3>
                <p>You can test the AJAX endpoint with this URL:</p>
                <p><code><?php echo esc_url($test_url); ?></code></p>
                <p>
                    <a href="<?php echo esc_url($test_url); ?>" target="_blank" class="button button-primary">
                        Test AJAX Endpoint
                    </a>
                </p>
                
                <h3>Debug Information</h3>
                <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
                <p><strong>Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?> v<?php echo wp_get_theme()->get('Version'); ?></p>
                <p><strong>Current User:</strong> <?php echo $user->display_name; ?> (ID: <?php echo $user->ID; ?>)</p>
                <p><strong>User Roles:</strong> <?php echo implode(', ', $user->roles); ?></p>
                
                <h3>Registered AJAX Actions</h3>
                <?php
                global $wp_filter;
                $ajax_actions = [];
                
                foreach ($wp_filter as $hook => $callbacks) {
                    if (strpos($hook, 'wp_ajax') === 0) {
                        $ajax_actions[$hook] = [];
                        
                        if (isset($wp_filter[$hook]->callbacks)) {
                            foreach ($wp_filter[$hook]->callbacks as $priority => $functions) {
                                foreach ($functions as $function) {
                                    $function_name = '';
                                    if (is_string($function['function'])) {
                                        $function_name = $function['function'];
                                    } elseif (is_array($function['function'])) {
                                        $class = is_object($function['function'][0]) ? 
                                                get_class($function['function'][0]) : $function['function'][0];
                                        $function_name = $class . '->' . $function['function'][1];
                                    } else {
                                        $function_name = 'Closure';
                                    }
                                    
                                    $ajax_actions[$hook][] = [
                                        'priority' => $priority,
                                        'function' => $function_name,
                                        'accepted_args' => $function['accepted_args']
                                    ];
                                }
                            }
                        }
                    }
                }
                
                if (!empty($ajax_actions)) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Hook</th><th>Priority</th><th>Function</th><th>Args</th></tr></thead><tbody>';
                    
                    foreach ($ajax_actions as $hook => $callbacks) {
                        $first = true;
                        $row_count = count($callbacks);
                        
                        foreach ($callbacks as $callback) {
                            $highlight = $hook === $action ? ' style="background-color: #fff3cd;"' : '';
                            echo "<tr$highlight>";
                            
                            if ($first) {
                                echo "<td rowspan='$row_count'><code>$hook</code></td>";
                                $first = false;
                            }
                            
                            echo "<td>{$callback['priority']}</td>";
                            echo "<td><code>{$callback['function']}</code></td>";
                            echo "<td>{$callback['accepted_args']}</td>";
                            echo "</tr>";
                        }
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>No AJAX actions found.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Remove username and password fields from WooCommerce checkout
 * 
 * This change was implemented on 2025-07-08 to hide the username and password fields
 * while still allowing account creation using other fields (like phone and ID number).
 * 
 * To restore the default username/password fields, remove or comment out this code.
 */
add_filter('woocommerce_checkout_fields', function($fields) {
    // Remove the account username and password fields
    if (isset($fields['account'])) {
        unset($fields['account']['account_username']);
        unset($fields['account']['account_password']);
        unset($fields['account']['account_password-2']);
    }
    return $fields;
}, 20);

// Force account creation when checking out as guest
add_filter('woocommerce_create_account_default_checked', '__return_true');

// Auto-generate username and password in the background
add_filter('woocommerce_checkout_customer', function($customer_data) {
    if (!is_user_logged_in() && !empty($customer_data['user_login'])) {
        // Generate username based on phone number (as requested)
        $username = '';
        if (!empty($customer_data['billing_phone'])) {
            // Use phone number as base for username
            $username = sanitize_user('user_' . $customer_data['billing_phone'], true);
        } else {
            // Fallback to email-based username
            $username = sanitize_user(current(explode('@', $customer_data['user_email'])), true);
        }
        
        // Ensure username is unique
        $check_username = $username;
        $i = 1;
        while (username_exists($check_username)) {
            $check_username = $username . $i;
            $i++;
        }
        
        // Set username and password based on user requirements:
        // Phone number becomes the basis for username
        // ID number becomes the password (as requested)
        $customer_data['user_login'] = $check_username;
        
        // Use billing ID number as password if available, otherwise generate random password
        $billing_id = '';
        if (!empty($customer_data['billing_id_number'])) {
            $billing_id = $customer_data['billing_id_number'];
        } elseif (isset($_POST['billing_id_number'])) {
            $billing_id = sanitize_text_field(wp_unslash($_POST['billing_id_number']));
        }

        if (!empty($billing_id)) {
            $customer_data['user_pass'] = $billing_id;
        } else {
            $customer_data['user_pass'] = wp_generate_password();
        }
    }
    
    return $customer_data;
});

// Load School Class Helper functions
require_once get_stylesheet_directory() . '/includes/school-class-helper.php';

// Include required classes
require_once get_stylesheet_directory() . '/includes/class-promo-codes.php';
require_once get_stylesheet_directory() . '/includes/admin/class-school-class-manager.php';

add_action('init', function() {
    // This will ensure the shortcode is registered at the right time
    if (class_exists('Hello_Theme_Child_Promo_Codes')) {
        Hello_Theme_Child_Promo_Codes::instance();
    }
    
    // Initialize School Class Manager
    if (class_exists('Hello_Theme_Child_School_Class_Manager')) {
        Hello_Theme_Child_School_Class_Manager::instance();
    }
}, 5);

// Function to render admin class manager view
if (!function_exists('hello_theme_render_admin_class_manager')) {
    function hello_theme_render_admin_class_manager() {
        // Admin-specific view
        $manager = Hello_Theme_Child_School_Class_Manager::instance();
        $manager->render_admin_view();
    }
}

// Function to render teacher class manager view
if (!function_exists('hello_theme_render_teacher_class_manager')) {
    function hello_theme_render_teacher_class_manager() {
        // Teacher-specific view
        $manager = Hello_Theme_Child_School_Class_Manager::instance();
        $manager->render_teacher_view();
    }
}

add_filter('woocommerce_checkout_fields', 'remove_country_checkout_field');
function remove_country_checkout_field($fields) {
    // Force country to IL and hide with CSS instead of removing
    $fields['billing']['billing_country']['default'] = 'IL';
    $fields['billing']['billing_country']['class'] = array('form-row-wide', 'hidden-field');
    $fields['billing']['billing_country']['required'] = false;
    
    if (isset($fields['shipping']['shipping_country'])) {
        $fields['shipping']['shipping_country']['default'] = 'IL';
        $fields['shipping']['shipping_country']['class'] = array('form-row-wide', 'hidden-field');
        $fields['shipping']['shipping_country']['required'] = false;
    }

    return $fields;
}

// Force the country values on form submission (backend)
add_filter('woocommerce_checkout_posted_data', 'force_country_to_israel');
function force_country_to_israel($data) {
    $data['billing_country'] = 'IL';
    $data['shipping_country'] = 'IL';
    return $data;
}

// Align school code and class number fields in the same row
add_filter('woocommerce_checkout_fields', 'fix_checkout_fields_alignment');
function fix_checkout_fields_alignment($fields) {

    // Ensure school fields are side-by-side
    if (isset($fields['billing']['school_code'])) {
        $fields['billing']['school_code']['class'] = array('form-row-first');
        $fields['billing']['school_code']['priority'] = 31;
    }

    if (isset($fields['billing']['class_number'])) {
        $fields['billing']['class_number']['class'] = array('form-row-last');
        $fields['billing']['class_number']['priority'] = 32;
    }

    // Align phone fields side-by-side
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['class'] = array('form-row-first');
        $fields['billing']['billing_phone']['priority'] = 51;
    }

    if (isset($fields['billing']['phone_confirm'])) {
        $fields['billing']['phone_confirm']['class'] = array('form-row-last');
        $fields['billing']['phone_confirm']['priority'] = 52;
    }

    return $fields;
}


//Allow SVG upload
function cc_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
  }
  add_filter('upload_mimes', 'cc_mime_types');
  
  // Add capabilities to school teacher role
add_action('admin_init', function () {
    $role = get_role('school_teacher');
    if ($role) {
        $role->add_cap('group_leader');
        $role->add_cap('instructor');
        $role->add_cap('edit_courses');
        $role->add_cap('edit_published_courses');
        $role->add_cap('publish_courses');
        $role->add_cap('delete_published_courses');
        $role->add_cap('edit_others_courses');
        $role->add_cap('delete_others_courses');
    }
});

// Back to Course button shortcode
add_shortcode('back_to_course_button', function($atts) {
    // Default values
    $atts = shortcode_atts([
        'course_id' => 4236,  // Default course ID
        'text'      => 'בחזרה לקורס',  // Button text
        'class'     => 'back-to-course-btn',  // CSS class
        'style'     => 'display: inline-block; padding: 12px 25px; background-color: rgba(44, 51, 145, 1); color: white; text-decoration: none; border-radius: 25px; margin: 15px 0; font-weight: 600; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.2);'  // Inline styles
    ], $atts);
    
    $course_url = get_permalink($atts['course_id']);
    
    // Add hover effect via inline style
    $button_style = $atts['style'] . ' ';
    $button_style .= '
        <style>
            .back-to-course-btn:hover {
                background-color: rgba(44, 51, 145, 1) !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
            }
        </style>';
    
    return sprintf(
        '<a href="%s" class="%s" style="%s">%s</a>',
        esc_url($course_url),
        esc_attr($atts['class']),
        $button_style,
        esc_html($atts['text'])
    );
});