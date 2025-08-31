<?php
/**
 * Welcome Message Setup
 * 
 * Sets up default welcome message settings if they don't exist
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize default welcome message settings
 */
function lilac_setup_default_welcome_message() {
    $defaults = array(
        'welcome_message_enabled' => '1',
        'welcome_message_title' => 'ברוכים הבאים לאתר שלנו!',
        'welcome_message_content' => 'תודה שביקרתם באתר האינטרנט שלנו. אנו מקווים שתהנו מהשהות שלכם!',
        'welcome_message_type' => 'info',
        'welcome_display_homepage' => '1',
        'welcome_display_once_per_session' => '1',
        'welcome_message_auto_close' => '5000', // 5 seconds
        'welcome_specific_urls' => ''
    );
    
    // Get existing settings or initialize empty array
    $current_settings = get_option('lilac_messaging_settings', array());
    
    // Merge with defaults, preserving any existing settings
    $updated_settings = wp_parse_args($current_settings, $defaults);
    
    // Update the option
    update_option('lilac_messaging_settings', $updated_settings);
    
    return $updated_settings;
}

// Run the setup when the theme is activated
add_action('after_switch_theme', 'lilac_setup_default_welcome_message');

// Also run on admin init in case the theme was already active
add_action('admin_init', function() {
    if (!get_option('lilac_messaging_settings')) {
        lilac_setup_default_welcome_message();
    }
});

// Add a one-time admin notice to inform about the welcome message
add_action('admin_notices', function() {
    $settings = get_option('lilac_messaging_settings');
    if (empty($settings) || empty($settings['welcome_message_enabled'])) {
        ?>
        <div class="notice notice-info">
            <p>The welcome message is currently disabled. <a href="<?php echo admin_url('admin.php?page=lilac-messaging'); ?>">Enable it in the Messaging settings</a>.</p>
        </div>
        <?php
    }
});
