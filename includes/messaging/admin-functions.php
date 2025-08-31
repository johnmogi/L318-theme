<?php
/**
 * Messaging Admin Functions
 *
 * This file integrates with the MessagingAdmin class and loads the messaging system.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load the autoloader if it exists
$autoloader_file = get_stylesheet_directory() . '/src/autoload.php';
if (file_exists($autoloader_file)) {
    require_once $autoloader_file;
}

// Load the MessagingAdmin class
$messaging_admin_file = get_stylesheet_directory() . '/src/Messaging/Admin/MessagingAdmin.php';
if (file_exists($messaging_admin_file)) {
    require_once $messaging_admin_file;
}

// Load the MessageManager class
$message_manager_file = get_stylesheet_directory() . '/src/Messaging/Admin/MessageManager.php';
if (file_exists($message_manager_file)) {
    require_once $message_manager_file;
}

// Load the welcome message setup
$welcome_setup_file = __DIR__ . '/setup-welcome-message.php';
if (file_exists($welcome_setup_file)) {
    require_once $welcome_setup_file;
}

// Load debug tools
$debug_welcome_file = __DIR__ . '/debug-welcome.php';
if (file_exists($debug_welcome_file) && defined('WP_DEBUG') && WP_DEBUG) {
    require_once $debug_welcome_file;
}

// Load detailed debug tools
$debug_welcome_detailed_file = __DIR__ . '/debug-welcome-detailed.php';
if (file_exists($debug_welcome_detailed_file) && defined('WP_DEBUG') && WP_DEBUG) {
    require_once $debug_welcome_detailed_file;
}

// Load test welcome script
$test_welcome_file = __DIR__ . '/test-welcome-now.php';
if (file_exists($test_welcome_file) && defined('WP_DEBUG') && WP_DEBUG) {
    require_once $test_welcome_file;
}

// Load PHP debug script
$debug_php_welcome_file = __DIR__ . '/debug-php-welcome.php';
if (file_exists($debug_php_welcome_file) && defined('WP_DEBUG') && WP_DEBUG) {
    require_once $debug_php_welcome_file;
}

// Load direct welcome test script
$direct_welcome_test_file = __DIR__ . '/direct-welcome-test.php';
if (file_exists($direct_welcome_test_file)) {
    require_once $direct_welcome_test_file;
}

/**
 * Load the SiteMessage class for toast notifications
 */
$toast_file = get_stylesheet_directory() . '/src/Messaging/Toast.php';
if (file_exists($toast_file)) {
    require_once $toast_file;
}

$site_message_file = get_stylesheet_directory() . '/src/Messaging/SiteMessage.php';
if (file_exists($site_message_file)) {
    require_once $site_message_file;
}

// Load the unified notification system
$notifications_file = get_stylesheet_directory() . '/includes/messaging/notifications.php';
if (file_exists($notifications_file)) {
    require_once $notifications_file;
}

// Include admin opt-in interface - TEMPORARILY DISABLED DUE TO TRANSLATION LOADING ISSUE
// require_once get_template_directory() . '/includes/messaging/admin-opt-in.php';

// Include debug script for message display issues - DISABLED DUE TO MISSING FILE
// require_once get_template_directory() . '/includes/messaging/debug-message-display.php';

/**
 * Initialize the messaging system and admin menu
 */
function lilac_init_messaging_system() {
    // Add admin menu - removing this as it's already handled by MessagingAdmin class
    // add_action('admin_menu', 'lilac_messaging_admin_menu');
    
    // Enqueue admin scripts
    add_action('admin_enqueue_scripts', 'lilac_messaging_admin_scripts');
    
    // Initialize frontend messaging if needed
    add_action('wp_enqueue_scripts', 'lilac_enqueue_messaging_scripts');
}
add_action('init', 'lilac_init_messaging_system');

/**
 * Add messaging admin menu
 */
function lilac_messaging_admin_menu() {
    add_menu_page(
        'Messaging System',
        'Messaging',
        'manage_options',
        'lilac-messaging',
        'lilac_messaging_admin_page',
        'dashicons-email',
        30
    );
}

/**
 * Display the messaging admin page
 */
function lilac_messaging_admin_page() {
    // Check if the MessagingAdmin class exists in the original namespace
    if (class_exists('Lilac\Messaging\Admin\MessagingAdmin')) {
        // Use the singleton pattern's get_instance method
        $admin = \Lilac\Messaging\Admin\MessagingAdmin::get_instance();
        $admin->admin_page_display();
        return;
    }
    
    // Check if the class exists in global namespace (backward compatibility)
    if (class_exists('MessagingAdmin')) {
        // Use the singleton pattern's get_instance method
        $admin = \MessagingAdmin::get_instance();
        $admin->admin_page_display();
        return;
    }
    
    // Fallback if the class isn't available
    ?>
    <div class="wrap">
        <h1>Messaging System</h1>
        <p>Manage system messages and notifications.</p>
        
        <div class="card">
            <h2>Messaging Features</h2>
            <p>This page allows administrators to:</p>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li>Create and manage system notifications</li>
                <li>Send targeted messages to user groups</li>
                <li>Review message history and analytics</li>
            </ul>
        </div>
        
        <div class="card" style="margin-top: 20px; color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: 15px;">
            <h3>Messaging System Files</h3>
            <p>The main messaging system class files are located at:</p>
            <code><?php echo esc_html(get_stylesheet_directory() . '/src/Messaging/'); ?></code>
            
            <p>Please ensure these files are present and properly configured.</p>
        </div>
    </div>
    <?php
}

/**
 * Enqueue admin scripts and styles for the messaging page
 */
function lilac_messaging_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_lilac-messaging') {
        return;
    }
    
    // First try to load from original location in src directory
    $admin_css_src = get_stylesheet_directory() . '/src/Messaging/assets/css/admin.css';
    $admin_js_src = get_stylesheet_directory() . '/src/Messaging/assets/js/admin.js';
    
    $admin_css_uri = file_exists($admin_css_src) 
        ? get_stylesheet_directory_uri() . '/src/Messaging/assets/css/admin.css'
        : get_stylesheet_directory_uri() . '/includes/messaging/css/admin.css';
        
    $admin_js_uri = file_exists($admin_js_src)
        ? get_stylesheet_directory_uri() . '/src/Messaging/assets/js/admin.js'
        : get_stylesheet_directory_uri() . '/includes/messaging/js/admin.js';
        
    // Core WP color picker for admin color options
    wp_enqueue_style('wp-color-picker');
    
    // Messaging admin styles
    wp_enqueue_style(
        'lilac-messaging-admin',
        $admin_css_uri,
        [],
        filemtime(get_stylesheet_directory() . '/includes/messaging/css/admin.css')
    );
    
    // Messaging admin scripts
    wp_enqueue_script(
        'lilac-messaging-admin',
        $admin_js_uri,
        ['jquery', 'wp-color-picker'],
        filemtime(get_stylesheet_directory() . '/includes/messaging/js/admin.js'),
        true
    );
}

/**
 * Enqueue frontend messaging scripts
 */
function lilac_enqueue_messaging_scripts() {
    // Remove any previously registered toast scripts/styles to avoid conflicts
    wp_deregister_script('lilac-toast');
    wp_deregister_style('lilac-toast');
    
    // Enqueue toast notification styles
    wp_enqueue_style(
        'lilac-toast-style',
        get_stylesheet_directory_uri() . '/includes/messaging/css/toast-system.css',
        [],
        filemtime(get_stylesheet_directory() . '/includes/messaging/css/toast-system.css')
    );
    
    // Enqueue toast notification scripts
    wp_enqueue_script(
        'lilac-toast-script',
        get_stylesheet_directory_uri() . '/includes/messaging/js/toast-system.js',
        ['jquery'],
        filemtime(get_stylesheet_directory() . '/includes/messaging/js/toast-system.js'),
        true
    );
    
    // Enqueue testing mode script (for development - disables session blocking)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_enqueue_script(
            'lilac-testing-mode',
            get_stylesheet_directory_uri() . '/includes/messaging/js/testing-mode.js',
            ['jquery', 'lilac-toast-script'],
            filemtime(get_stylesheet_directory() . '/includes/messaging/js/testing-mode.js'),
            true
        );
    }
    
    // Disable the old toast.js - we'll use our new system exclusively
    add_filter('script_loader_tag', function($tag, $handle) {
        if ($handle === 'lilac-toast') {
            return '';
        }
        return $tag;
    }, 10, 2);
}
