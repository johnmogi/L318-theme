<?php
/**
 * Debug Message Display Issues
 * Quick debug script to check why messages aren't showing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add debug output to frontend to check message display issues
 */
function lilac_debug_message_display() {
    // Only show debug in development
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Get current user info
    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    
    // Get message settings
    $settings = get_option('lilac_messaging_settings', array());
    
    // Check exclusion logic
    $should_exclude = false;
    if ($is_logged_in) {
        $privileged_roles = array('administrator', 'teacher', 'instructor', 'group_leader', 'course_author', 'editor', 'author');
        $has_privileged_role = false;
        
        foreach ($privileged_roles as $role) {
            if (in_array($role, $current_user->roles)) {
                $has_privileged_role = true;
                break;
            }
        }
        
        $has_privileged_caps = current_user_can('manage_options') || 
                              current_user_can('edit_courses') || 
                              current_user_can('edit_lessons') ||
                              current_user_can('edit_others_posts');
        
        if ($has_privileged_role || $has_privileged_caps) {
            $opt_in = get_user_meta($current_user->ID, 'lilac_message_opt_in', true);
            if (!$opt_in) {
                $should_exclude = true;
            }
        }
    }
    
    // Check session status
    $session_shown = isset($_SESSION['lilac_shown_messages']['welcome']) ? $_SESSION['lilac_shown_messages']['welcome'] : false;
    
    ?>
    <script type="text/javascript">
        console.log('🔍 LILAC MESSAGE DEBUG INFO:');
        console.log('Current User ID: <?php echo $current_user->ID; ?>');
        console.log('Is Logged In: <?php echo $is_logged_in ? 'true' : 'false'; ?>');
        console.log('User Roles: <?php echo json_encode($current_user->roles); ?>');
        console.log('Should Exclude User: <?php echo $should_exclude ? 'true' : 'false'; ?>');
        console.log('Welcome Message Enabled: <?php echo isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled'] ? 'true' : 'false'; ?>');
        console.log('Welcome Message Content: <?php echo isset($settings['welcome_message_content']) ? '"' . esc_js($settings['welcome_message_content']) . '"' : 'null'; ?>');
        console.log('Session Welcome Shown: <?php echo $session_shown ? 'true' : 'false'; ?>');
        console.log('Is Front Page: <?php echo is_front_page() ? 'true' : 'false'; ?>');
        console.log('Current URL: <?php echo esc_js($_SERVER['REQUEST_URI']); ?>');
        
        // Check localStorage opt-in status
        console.log('localStorage opt-in:', localStorage.getItem('lilac_message_opt_in'));
        
        // If user should be excluded, show how to opt-in
        <?php if ($should_exclude): ?>
        console.log('🚫 You are excluded from seeing messages because you have privileged role/capabilities.');
        console.log('💡 To see messages, run: localStorage.setItem("lilac_message_opt_in", "true"); then refresh');
        <?php endif; ?>
        
        // Show all settings for debugging
        console.log('All Message Settings:', <?php echo json_encode($settings); ?>);
    </script>
    <?php
}

// Hook into wp_footer to show debug info
add_action('wp_footer', 'lilac_debug_message_display');
