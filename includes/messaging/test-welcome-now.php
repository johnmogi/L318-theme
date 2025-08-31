<?php
/**
 * Quick Welcome Message Test
 * 
 * This script will force-test the welcome message system
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu item for quick testing
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Test Welcome Message Now',
        'Test Welcome Now',
        'manage_options',
        'test-welcome-now',
        'lilac_test_welcome_now_page'
    );
});

// Quick test page
function lilac_test_welcome_now_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle force test
    if (isset($_POST['action']) && $_POST['action'] === 'force_test') {
        check_admin_referer('test_welcome_now');
        
        // Clear session flag
        if (isset($_SESSION['lilac_shown_messages']['welcome'])) {
            unset($_SESSION['lilac_shown_messages']['welcome']);
        }
        
        // Force show a test welcome message using the toast system
        add_action('admin_footer', function() {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Test the toast system directly
                if (typeof window.LilacShowToast === 'function') {
                    window.LilacShowToast({
                        message: 'זוהי הודעת ברכה לבדיקה - אם אתם רואים את זה, המערכת עובדת!',
                        type: 'info',
                        title: 'ברוכים הבאים - בדיקה',
                        duration: 10000,
                        position: 'top-right'
                    });
                    console.log('Welcome test message triggered via LilacShowToast');
                } else {
                    // Fallback test
                    alert('Toast system not available - this is the problem!');
                    console.error('LilacShowToast function not available');
                }
            });
            </script>
            <?php
        });
        
        echo '<div class="notice notice-success"><p>Test welcome message triggered! Check if it appears on this page.</p></div>';
    }

    // Get current settings for display
    $settings = get_option('lilac_messaging_settings', array());
    
    ?>
    <div class="wrap">
        <h1>Test Welcome Message Now</h1>
        
        <div class="card">
            <h2>Quick System Check</h2>
            <ul>
                <li><strong>SiteMessage Class:</strong> <?php echo class_exists('\\Lilac\\Messaging\\SiteMessage') ? '✅ Loaded' : '❌ Not Loaded'; ?></li>
                <li><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Not Active'; ?></li>
                <li><strong>Welcome Enabled:</strong> <?php echo (isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled']) ? '✅ Yes' : '❌ No'; ?></li>
                <li><strong>Welcome Content:</strong> <?php echo !empty($settings['welcome_message_content']) ? '✅ Has Content' : '❌ No Content'; ?></li>
                <li><strong>Session Flag:</strong> <?php echo isset($_SESSION['lilac_shown_messages']['welcome']) ? '❌ Blocked' : '✅ Clear'; ?></li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Force Test Welcome Message</h2>
            <p>This will clear the session flag and attempt to show a test welcome message on this admin page.</p>
            <form method="post" action="">
                <?php wp_nonce_field('test_welcome_now'); ?>
                <input type="hidden" name="action" value="force_test">
                <p class="submit">
                    <button type="submit" class="button button-primary">Force Test Welcome Message</button>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Current Welcome Settings</h2>
            <table class="widefat">
                <tr><td><strong>Title:</strong></td><td><?php echo esc_html($settings['welcome_message_title'] ?? 'Not Set'); ?></td></tr>
                <tr><td><strong>Content:</strong></td><td><?php echo esc_html($settings['welcome_message_content'] ?? 'Not Set'); ?></td></tr>
                <tr><td><strong>Type:</strong></td><td><?php echo esc_html($settings['welcome_message_type'] ?? 'Not Set'); ?></td></tr>
                <tr><td><strong>Auto Close:</strong></td><td><?php echo esc_html($settings['welcome_message_auto_close'] ?? 'Not Set'); ?></td></tr>
                <tr><td><strong>Homepage Only:</strong></td><td><?php echo isset($settings['welcome_display_homepage']) && $settings['welcome_display_homepage'] ? 'Yes' : 'No'; ?></td></tr>
                <tr><td><strong>Once Per Session:</strong></td><td><?php echo isset($settings['welcome_display_once_per_session']) && $settings['welcome_display_once_per_session'] ? 'Yes' : 'No'; ?></td></tr>
            </table>
        </div>
    </div>
    <?php
}
