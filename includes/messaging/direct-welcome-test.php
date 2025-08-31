<?php
/**
 * Direct Welcome Message Test
 * 
 * This script will directly test and force the welcome message to appear
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add a simple test that runs on wp_footer with high priority
add_action('wp_footer', 'lilac_direct_welcome_test', 999);

function lilac_direct_welcome_test() {
    // Only run on frontend homepage
    if (is_admin() || !is_front_page()) {
        return;
    }
    
    // Get settings
    $settings = get_option('lilac_messaging_settings', array());
    
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    // Initialize session array if needed
    if (!isset($_SESSION['lilac_shown_messages'])) {
        $_SESSION['lilac_shown_messages'] = array();
    }
    
    // Check if already shown this session
    $already_shown = isset($_SESSION['lilac_shown_messages']['welcome']);
    
    // Basic checks
    $enabled = isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled'];
    $has_content = !empty($settings['welcome_message_content']);
    $is_homepage = is_front_page();
    
    ?>
    <script>
    console.group('🚀 DIRECT WELCOME TEST');
    console.log('Welcome Enabled:', <?php echo json_encode($enabled); ?>);
    console.log('Has Content:', <?php echo json_encode($has_content); ?>);
    console.log('Is Homepage:', <?php echo json_encode($is_homepage); ?>);
    console.log('Already Shown This Session:', <?php echo json_encode($already_shown); ?>);
    console.log('Current Settings:', <?php echo json_encode($settings); ?>);
    
    <?php if ($enabled && $has_content && $is_homepage && !$already_shown): ?>
    
    console.log('✅ ALL CONDITIONS MET - Forcing welcome message now!');
    
    // Mark as shown to prevent loops
    <?php $_SESSION['lilac_shown_messages']['welcome'] = time(); ?>
    
    // Force show the welcome message
    setTimeout(function() {
        if (typeof window.LilacShowToast === 'function') {
            window.LilacShowToast({
                message: <?php echo json_encode($settings['welcome_message_content']); ?>,
                type: <?php echo json_encode($settings['welcome_message_type'] ?? 'info'); ?>,
                title: <?php echo json_encode($settings['welcome_message_title'] ?? ''); ?>,
                duration: <?php echo json_encode(intval($settings['welcome_message_auto_close'] ?? 5000)); ?>,
                position: 'top-right'
            });
            console.log('🎉 WELCOME MESSAGE FORCED SUCCESSFULLY!');
        } else {
            console.error('❌ LilacShowToast function not available');
        }
    }, 1000);
    
    <?php else: ?>
    
    console.log('❌ CONDITIONS NOT MET:');
    <?php if (!$enabled): ?>
    console.log('  - Welcome message is disabled');
    <?php endif; ?>
    <?php if (!$has_content): ?>
    console.log('  - No welcome message content');
    <?php endif; ?>
    <?php if (!$is_homepage): ?>
    console.log('  - Not on homepage');
    <?php endif; ?>
    <?php if ($already_shown): ?>
    console.log('  - Already shown this session');
    <?php endif; ?>
    
    <?php endif; ?>
    
    console.groupEnd();
    </script>
    <?php
}

// Add admin menu to enable/disable this direct test
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Direct Welcome Test',
        'Direct Welcome Test',
        'manage_options',
        'direct-welcome-test',
        'lilac_direct_welcome_test_admin_page'
    );
});

function lilac_direct_welcome_test_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle session reset
    if (isset($_POST['action']) && $_POST['action'] === 'reset_session') {
        check_admin_referer('direct_welcome_test');
        
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Clear welcome session flag
        if (isset($_SESSION['lilac_shown_messages']['welcome'])) {
            unset($_SESSION['lilac_shown_messages']['welcome']);
        }
        
        echo '<div class="notice notice-success"><p>✅ Session reset! Go to homepage to test.</p></div>';
    }

    $settings = get_option('lilac_messaging_settings', array());
    
    ?>
    <div class="wrap">
        <h1>Direct Welcome Test</h1>
        
        <div class="card">
            <h2>Test Status</h2>
            <p>This script will force-test the welcome message on the homepage.</p>
            
            <table class="widefat">
                <tr>
                    <td><strong>Welcome Enabled:</strong></td>
                    <td><?php echo (isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled']) ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Has Content:</strong></td>
                    <td><?php echo !empty($settings['welcome_message_content']) ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Session Flag:</strong></td>
                    <td><?php echo isset($_SESSION['lilac_shown_messages']['welcome']) ? '❌ Set (blocking)' : '✅ Clear'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>Instructions</h2>
            <ol>
                <li>Click "Reset Session" below</li>
                <li>Go to your homepage: <a href="<?php echo home_url(); ?>" target="_blank"><?php echo home_url(); ?></a></li>
                <li>Open browser console (F12)</li>
                <li>Look for "🚀 DIRECT WELCOME TEST" output</li>
                <li>The welcome message should appear automatically if conditions are met</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Reset Session</h2>
            <form method="post" action="">
                <?php wp_nonce_field('direct_welcome_test'); ?>
                <input type="hidden" name="action" value="reset_session">
                <p class="submit">
                    <button type="submit" class="button button-primary">Reset Session & Test</button>
                </p>
            </form>
        </div>
    </div>
    <?php
}
