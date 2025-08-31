<?php
/**
 * PHP Welcome Message Debug
 * 
 * This script will debug the PHP side of the welcome message system
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add a debug function that runs on wp_footer to check what's happening
add_action('wp_footer', 'lilac_debug_welcome_php_logic', 999);

function lilac_debug_welcome_php_logic() {
    // Only run on frontend, not admin
    if (is_admin()) {
        return;
    }
    
    $settings = get_option('lilac_messaging_settings', array());
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    
    // Initialize session tracking if needed
    if (!isset($_SESSION['lilac_shown_messages'])) {
        $_SESSION['lilac_shown_messages'] = array();
    }
    
    $debug_info = array(
        'welcome_enabled' => isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled'],
        'has_content' => !empty($settings['welcome_message_content']),
        'session_flag_set' => isset($_SESSION['lilac_shown_messages']['welcome']),
        'is_front_page' => is_front_page(),
        'is_home' => is_home(),
        'homepage_only_setting' => isset($settings['welcome_display_homepage']) && $settings['welcome_display_homepage'],
        'once_per_session_setting' => isset($settings['welcome_display_once_per_session']) && $settings['welcome_display_once_per_session'],
        'current_url' => $_SERVER['REQUEST_URI'],
        'session_status' => session_status(),
        'session_id' => session_id(),
        'sitemessage_class_exists' => class_exists('\\Lilac\\Messaging\\SiteMessage'),
        'page_id' => get_the_ID(),
        'is_page' => is_page(),
        'is_single' => is_single()
    );
    
    // Check if all conditions are met
    $should_show = true;
    $blocking_reasons = array();
    
    if (!$debug_info['welcome_enabled']) {
        $should_show = false;
        $blocking_reasons[] = 'Welcome message disabled in settings';
    }
    
    if (!$debug_info['has_content']) {
        $should_show = false;
        $blocking_reasons[] = 'No welcome message content';
    }
    
    if ($debug_info['once_per_session_setting'] && $debug_info['session_flag_set']) {
        $should_show = false;
        $blocking_reasons[] = 'Already shown this session';
    }
    
    if ($debug_info['homepage_only_setting'] && !$debug_info['is_front_page']) {
        $should_show = false;
        $blocking_reasons[] = 'Not on homepage (homepage-only setting enabled)';
    }
    
    if (!$debug_info['sitemessage_class_exists']) {
        $should_show = false;
        $blocking_reasons[] = 'SiteMessage class not loaded';
    }
    
    ?>
    <script>
    console.group('🔍 PHP Welcome Message Debug');
    console.log('Debug Info:', <?php echo json_encode($debug_info, JSON_PRETTY_PRINT); ?>);
    console.log('Should Show Welcome:', <?php echo json_encode($should_show); ?>);
    console.log('Blocking Reasons:', <?php echo json_encode($blocking_reasons); ?>);
    
    <?php if ($should_show): ?>
    console.log('✅ All conditions met - Welcome message should display!');
    
    // Try to manually trigger the welcome message
    setTimeout(function() {
        if (typeof window.LilacShowToast === 'function') {
            window.LilacShowToast({
                message: <?php echo json_encode($settings['welcome_message_content']); ?>,
                type: <?php echo json_encode($settings['welcome_message_type'] ?? 'info'); ?>,
                title: <?php echo json_encode($settings['welcome_message_title'] ?? ''); ?>,
                duration: <?php echo json_encode(intval($settings['welcome_message_auto_close'] ?? 5000)); ?>,
                position: 'top-right'
            });
            console.log('🎉 Manually triggered welcome message via PHP debug');
        } else {
            console.error('❌ LilacShowToast not available');
        }
    }, 1000);
    
    <?php else: ?>
    console.log('❌ Conditions not met - Welcome message blocked');
    console.log('To fix: ', <?php echo json_encode($blocking_reasons); ?>);
    <?php endif; ?>
    
    console.groupEnd();
    </script>
    
    <?php
    
    // If we should show but SiteMessage isn't working, try direct approach
    if ($should_show && !$debug_info['session_flag_set']) {
        // Mark as shown to prevent loops
        $_SESSION['lilac_shown_messages']['welcome'] = time();
        
        // Try to enqueue directly
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('🔧 Attempting direct welcome message trigger...');
            
            setTimeout(function() {
                if (typeof window.LilacShowToast === 'function') {
                    window.LilacShowToast({
                        message: <?php echo json_encode($settings['welcome_message_content']); ?>,
                        type: <?php echo json_encode($settings['welcome_message_type'] ?? 'info'); ?>,
                        title: <?php echo json_encode($settings['welcome_message_title'] ?? ''); ?>,
                        duration: <?php echo json_encode(intval($settings['welcome_message_auto_close'] ?? 5000)); ?>,
                        position: 'top-right'
                    });
                    console.log('✅ Direct welcome message triggered successfully!');
                } else {
                    console.error('❌ LilacShowToast function not available for direct trigger');
                }
            }, 2000);
        });
        </script>
        <?php
    }
}

// Add admin menu for clearing session
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Clear Welcome Session',
        'Clear Welcome Session',
        'manage_options',
        'clear-welcome-session',
        'lilac_clear_welcome_session_page'
    );
});

function lilac_clear_welcome_session_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'clear_session') {
        check_admin_referer('clear_welcome_session');
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Clear the welcome session flag
        if (isset($_SESSION['lilac_shown_messages']['welcome'])) {
            unset($_SESSION['lilac_shown_messages']['welcome']);
            echo '<div class="notice notice-success"><p>✅ Welcome session flag cleared!</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>ℹ️ No welcome session flag was set.</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1>Clear Welcome Session</h1>
        <div class="card">
            <h2>Session Status</h2>
            <p><strong>Session Active:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No'; ?></p>
            <p><strong>Welcome Flag Set:</strong> <?php echo isset($_SESSION['lilac_shown_messages']['welcome']) ? 'Yes (blocking)' : 'No (allowing)'; ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('clear_welcome_session'); ?>
                <input type="hidden" name="action" value="clear_session">
                <p class="submit">
                    <button type="submit" class="button button-primary">Clear Welcome Session Flag</button>
                </p>
            </form>
        </div>
    </div>
    <?php
}
