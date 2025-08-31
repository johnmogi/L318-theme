<?php
/**
 * Quiz System Loader
 * 
 * This file is the main entry point for the new quiz system.
 * It loads all the necessary files and makes sure the old scripts
 * don't interfere with the new ones.
 */

defined('ABSPATH') || exit;

// Load the integration functions
require_once get_stylesheet_directory() . '/includes/quiz-new/functions-integration.php';

// Load the template loader
require_once get_stylesheet_directory() . '/includes/quiz-new/quiz-template-loader.php';

// Load the debug panel for admins
if (current_user_can('edit_posts') || isset($_GET['debug'])) {
    require_once get_stylesheet_directory() . '/includes/quiz-new/quiz-debug-panel.php';
}

// Load script conflict resolver - this is critical for proper operation
require_once get_stylesheet_directory() . '/includes/quiz-new/script-conflict-resolver.php';

// Load custom quiz system disabler - temporarily disables failed-quiz-build system in favor of shortcodes
require_once get_stylesheet_directory() . '/includes/quiz-new/disable-custom-quiz.php';

// Helper function to check if we should use the new quiz system
function lilac_is_new_quiz_page() {
    if (is_singular('sfwd-quiz')) {
        return true;
    }
    
    // Check if the page contains a quiz shortcode
    global $post;
    if ($post && (
        has_shortcode($post->post_content, 'ld_quiz') || 
        has_shortcode($post->post_content, 'learndash_quiz') ||
        strpos($post->post_content, 'wpProQuiz_content') !== false
    )) {
        return true;
    }
    
    return false;
}

// Disable old quiz scripts on pages that use our new system
function lilac_maybe_disable_old_quiz_scripts() {
    if (!lilac_is_new_quiz_page()) {
        return;
    }
    
    // List of scripts to disable - be comprehensive
    $scripts_to_disable = array(
        'force-hint-debug',
        'strict-next-button',
        'prevent-selection-loop',
        'quiz-performance',
        'quiz-error-handler',
        'question-id-debug',
        'quiz-sidebar',
        'next-button-manager',
        'correct-answer-manager',
        'ultra-speed-quiz',
        'lilac-quiz-hint-integration',
        'acf-quiz-hints',
        'acf-hint-test',
        'quiz-extensions',
        'quiz-debug-tools',
        'quiz-admin-tools',
        'quiz-sidebar-manager',
        'rich-media-quiz',
        'strict-next',
        'hint-manager'
    );
    
    // Remove the scripts with higher priority
    foreach ($scripts_to_disable as $script) {
        wp_dequeue_script($script);
        wp_deregister_script($script);
    }
    
    // Also remove any script with 'quiz' in the handle
    global $wp_scripts;
    if (!empty($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($handle, 'quiz') !== false && !in_array($handle, array('lilac-quiz-new', 'lilac-quiz-controller'))) {
                wp_dequeue_script($handle);
            }
        }
    }
    
    // Log that we're using the new system
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Lilac Quiz: Using new quiz system on ' . get_the_title());
    }
    
    // Load the answer debugging script for admins and editors
    if (current_user_can('edit_posts') || isset($_GET['debug'])) {
        wp_enqueue_script(
            'lilac-answer-debug',
            get_stylesheet_directory_uri() . '/includes/quiz-new/answer-debug.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/includes/quiz-new/answer-debug.js'),
            true
        );
    }
}
// Run this earlier to make sure we remove scripts before they're printed
add_action('wp_enqueue_scripts', 'lilac_maybe_disable_old_quiz_scripts', 20);

// Add admin notice to inform about the new quiz system
function lilac_quiz_admin_notice() {
    $screen = get_current_screen();
    
    // Only show on quiz edit screen and quiz settings
    if ($screen && ($screen->id === 'sfwd-quiz' || $screen->id === 'settings_page_lilac-quiz-settings')) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><strong>New Quiz System Active:</strong> This quiz is now using the new Lilac Quiz System that provides better answer detection and sidebar support.</p>
            <p>The new system includes built-in support for:</p>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li>Force hint mode with proper answer detection</li>
                <li>Visual feedback for correct/incorrect answers</li>
                <li>Optional sidebar layout</li>
                <li>Better debugging capabilities</li>
            </ul>
            <p>You can configure these options in <a href="<?php echo admin_url('options-general.php?page=lilac-quiz-settings'); ?>">Quiz Settings</a>.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'lilac_quiz_admin_notice');
