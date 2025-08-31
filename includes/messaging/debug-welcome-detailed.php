<?php
/**
 * Detailed Welcome Message Debug Script
 * 
 * This script will help identify exactly why the welcome message isn't showing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu item for detailed debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Welcome Message - Detailed',
        'Debug Welcome Detailed',
        'manage_options',
        'debug-welcome-detailed',
        'lilac_debug_welcome_detailed_page'
    );
});

// Detailed debug page content
function lilac_debug_welcome_detailed_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle session clearing
    if (isset($_POST['action']) && $_POST['action'] === 'clear_session') {
        check_admin_referer('debug_welcome_detailed');
        
        // Clear the welcome session flag
        if (isset($_SESSION['lilac_shown_messages']['welcome'])) {
            unset($_SESSION['lilac_shown_messages']['welcome']);
            echo '<div class="notice notice-success"><p>Welcome message session flag cleared!</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>No welcome session flag was set.</p></div>';
        }
    }

    // Get current settings
    $settings = get_option('lilac_messaging_settings', array());
    
    // Check all conditions
    $checks = array();
    
    // 1. Check if welcome message is enabled
    $checks['enabled'] = isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled'];
    
    // 2. Check if content exists
    $checks['has_content'] = !empty($settings['welcome_message_content']);
    
    // 3. Check session flag
    $checks['session_clear'] = !isset($_SESSION['lilac_shown_messages']['welcome']);
    
    // 4. Check if we're on homepage (if homepage-only is enabled)
    $homepage_only = isset($settings['welcome_display_homepage']) && $settings['welcome_display_homepage'];
    $checks['homepage_condition'] = !$homepage_only || is_front_page();
    
    // 5. Check if SiteMessage class is loaded
    $checks['class_loaded'] = class_exists('\\Lilac\\Messaging\\SiteMessage');
    
    // 6. Check if session is started
    $checks['session_started'] = session_status() === PHP_SESSION_ACTIVE;
    
    ?>
    <div class="wrap">
        <h1>Detailed Welcome Message Debug</h1>
        
        <div class="card">
            <h2>Welcome Message Conditions Check</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Welcome Message Enabled</td>
                        <td><?php echo $checks['enabled'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php echo $checks['enabled'] ? 'Enabled' : 'Disabled in settings'; ?></td>
                    </tr>
                    <tr>
                        <td>Has Content</td>
                        <td><?php echo $checks['has_content'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php echo $checks['has_content'] ? 'Content exists' : 'No content set'; ?></td>
                    </tr>
                    <tr>
                        <td>Session Flag Clear</td>
                        <td><?php echo $checks['session_clear'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php echo $checks['session_clear'] ? 'Not shown this session' : 'Already shown this session'; ?></td>
                    </tr>
                    <tr>
                        <td>Homepage Condition</td>
                        <td><?php echo $checks['homepage_condition'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php 
                            if ($homepage_only) {
                                echo is_front_page() ? 'On homepage (required)' : 'Not on homepage (required)';
                            } else {
                                echo 'Homepage restriction disabled';
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <td>SiteMessage Class Loaded</td>
                        <td><?php echo $checks['class_loaded'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php echo $checks['class_loaded'] ? 'Class is available' : 'Class not loaded'; ?></td>
                    </tr>
                    <tr>
                        <td>Session Started</td>
                        <td><?php echo $checks['session_started'] ? '✅ PASS' : '❌ FAIL'; ?></td>
                        <td><?php echo $checks['session_started'] ? 'Session is active' : 'Session not started'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>Current Settings</h2>
            <pre><?php echo htmlspecialchars(print_r($settings, true)); ?></pre>
        </div>
        
        <div class="card">
            <h2>Session Information</h2>
            <p><strong>Session Status:</strong> <?php 
                switch(session_status()) {
                    case PHP_SESSION_DISABLED: echo 'Disabled'; break;
                    case PHP_SESSION_NONE: echo 'Not Started'; break;
                    case PHP_SESSION_ACTIVE: echo 'Active'; break;
                }
            ?></p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            <p><strong>Welcome Session Flag:</strong> <?php 
                echo isset($_SESSION['lilac_shown_messages']['welcome']) ? 'SET (blocking display)' : 'NOT SET (allowing display)';
            ?></p>
            <p><strong>All Session Data:</strong></p>
            <pre><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
        </div>
        
        <div class="card">
            <h2>Page Information</h2>
            <p><strong>Is Front Page:</strong> <?php echo is_front_page() ? 'Yes' : 'No'; ?></p>
            <p><strong>Is Home:</strong> <?php echo is_home() ? 'Yes' : 'No'; ?></p>
            <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>Is Admin:</strong> <?php echo is_admin() ? 'Yes' : 'No'; ?></p>
        </div>
        
        <div class="card">
            <h2>Actions</h2>
            <form method="post" action="">
                <?php wp_nonce_field('debug_welcome_detailed'); ?>
                <input type="hidden" name="action" value="clear_session">
                <p class="submit">
                    <button type="submit" class="button button-primary">Clear Welcome Session Flag</button>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Overall Status</h2>
            <?php 
            $all_pass = $checks['enabled'] && $checks['has_content'] && $checks['session_clear'] && 
                       $checks['homepage_condition'] && $checks['class_loaded'] && $checks['session_started'];
            
            if ($all_pass) {
                echo '<div class="notice notice-success"><p><strong>✅ ALL CONDITIONS MET - Welcome message should display!</strong></p></div>';
            } else {
                echo '<div class="notice notice-error"><p><strong>❌ SOME CONDITIONS FAILED - Welcome message will not display</strong></p></div>';
                echo '<p>Fix the failed conditions above to enable the welcome message.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}
