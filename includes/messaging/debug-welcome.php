<?php
/**
 * Debug Welcome Message
 * 
 * Helper script to debug welcome message functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu item for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Welcome Message',
        'Debug Welcome',
        'manage_options',
        'debug-welcome-message',
        'lilac_debug_welcome_message_page'
    );
});

// Debug page content
function lilac_debug_welcome_message_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['action']) && $_POST['action'] === 'set_welcome_message') {
        check_admin_referer('debug_welcome_message');
        
        $settings = array(
            'welcome_message_enabled' => '1',
            'welcome_message_title' => 'Welcome to Our Site!',
            'welcome_message_content' => 'Thank you for visiting our website. We hope you enjoy your stay!',
            'welcome_message_type' => 'info',
            'welcome_display_homepage' => '1',
            'welcome_display_once_per_session' => '1',
            'welcome_message_auto_close' => '5000',
            'welcome_specific_urls' => ''
        );
        
        update_option('lilac_messaging_settings', $settings);
        echo '<div class="notice notice-success"><p>Welcome message settings have been updated.</p></div>';
    }
    
    // Get current settings
    $settings = get_option('lilac_messaging_settings', array());
    ?>
    <div class="wrap">
        <h1>Debug Welcome Message</h1>
        
        <div class="card">
            <h2>Current Settings</h2>
            <pre><?php echo htmlspecialchars(print_r($settings, true)); ?></pre>
        </div>
        
        <div class="card">
            <h2>Set Default Welcome Message</h2>
            <form method="post" action="">
                <?php wp_nonce_field('debug_welcome_message'); ?>
                <input type="hidden" name="action" value="set_welcome_message">
                <p class="submit">
                    <button type="submit" class="button button-primary">Set Default Welcome Message</button>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Debug Information</h2>
            <p>Is Homepage: <?php echo is_front_page() ? 'Yes' : 'No'; ?></p>
            <p>Is Admin: <?php echo is_admin() ? 'Yes' : 'No'; ?></p>
            <p>Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active'; ?></p>
            <p>Session Data: <?php echo isset($_SESSION) ? 'Session is set' : 'Session is not set'; ?></p>
        </div>
    </div>
    <?php
}

// Add a direct link to the debug page from the admin bar
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (current_user_can('manage_options')) {
        $wp_admin_bar->add_node(array(
            'id'    => 'debug-welcome',
            'title' => 'Debug Welcome',
            'href'  => admin_url('tools.php?page=debug-welcome-message'),
            'parent' => 'site-name',
        ));
    }
}, 100);
