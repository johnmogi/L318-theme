<?php
/**
 * Custom Quiz System Disabler
 * 
 * This file temporarily disables the custom quiz bypass system and enables
 * the use of shortcodes for quiz functionality instead.
 */

defined('ABSPATH') || exit;

/**
 * Disable all scripts and styles from failed-quiz-build
 */
function lilac_disable_custom_quiz_bypass() {
    // Script handles that need to be dequeued
    $scripts_to_remove = array(
        'lilac-quiz-debug',
        'lilac-quiz-controller',
        'ultra-speed-quiz',
        'ultra-speed-quiz-fixed',
        'force-hint-debug',
        'force-hint-fix',
        'force-hint-final',
        'quiz-error-handler',
        'quiz-performance',
        'quiz-performance-optimized',
        'quiz-sidebar',
        'strict-next-button',
        'strict-next-button-fixed',
        'strict-next-button-v2',
        'strict-next-button-v3',
        'strict-next-button-v4',
        'strict-next-button-v5',
        'correct-answer-manager',
        'next-button-manager',
        'prevent-selection-loop',
        'question-id-debug',
        'timer',
        'timer-hook'
    );
    
    // Dequeue all these scripts
    foreach ($scripts_to_remove as $script) {
        wp_dequeue_script($script);
        wp_deregister_script($script);
    }
    
    // Styles to remove
    $styles_to_remove = array(
        'lilac-quiz-styles',
        'quiz-sidebar-styles'
    );
    
    // Dequeue all these styles
    foreach ($styles_to_remove as $style) {
        wp_dequeue_style($style);
        wp_deregister_style($style);
    }
    
    // Add a notice for admins that we're using shortcodes instead
    if (current_user_can('administrator') && is_singular('sfwd-quiz')) {
        add_action('wp_footer', 'lilac_quiz_shortcode_notice');
    }
}
add_action('wp_enqueue_scripts', 'lilac_disable_custom_quiz_bypass', 999);

/**
 * Display a notice to admins that we're using shortcodes instead
 */
function lilac_quiz_shortcode_notice() {
    ?>
    <div class="lilac-admin-notice" style="position: fixed; bottom: 20px; right: 20px; background: #f8f9fa; border: 1px solid #ccc; border-left: 4px solid #007bff; padding: 10px 15px; z-index: 9999; max-width: 300px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <p><strong>Notice:</strong> Custom quiz bypass is disabled.</p>
        <p>Using shortcodes for quiz functionality instead.</p>
        <p><small>Use <code>[lilac_quiz_answers quiz_id="<?php echo get_the_ID(); ?>"]</code> to display answers.</small></p>
    </div>
    <?php
}

/**
 * Add a notice on the admin dashboard about the temporary change
 */
function lilac_admin_quiz_mode_notice() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p><strong>Quiz System Change:</strong> Custom quiz bypass system is temporarily disabled. Using shortcodes instead.</p>
        <p>To view quiz answers, add the shortcode <code>[lilac_quiz_answers quiz_id="QUIZ_ID"]</code> to a page or use it in a quiz page.</p>
    </div>
    <?php
}
add_action('admin_notices', 'lilac_admin_quiz_mode_notice');

/**
 * Optionally add editor button for inserting the quiz answers shortcode
 */
function lilac_add_quiz_answers_button($buttons) {
    array_push($buttons, 'separator', 'lilac_quiz_answers_button');
    return $buttons;
}
add_filter('mce_buttons', 'lilac_add_quiz_answers_button');

/**
 * Register the script for the button
 */
function lilac_add_quiz_answers_plugin($plugin_array) {
    $plugin_array['lilac_quiz_answers_button'] = get_stylesheet_directory_uri() . '/js/quiz-shortcode-button.js';
    return $plugin_array;
}
add_filter('mce_external_plugins', 'lilac_add_quiz_answers_plugin');

/**
 * Create the JS file for the editor button
 */
function lilac_create_quiz_answers_button_script() {
    $js_file = get_stylesheet_directory() . '/js/quiz-shortcode-button.js';
    
    // Only create if it doesn't exist
    if (!file_exists($js_file)) {
        $js_content = '(function() {
    tinymce.PluginManager.add("lilac_quiz_answers_button", function(editor, url) {
        editor.addButton("lilac_quiz_answers_button", {
            text: "Quiz Answers",
            icon: "dashicons-welcome-learn-more",
            onclick: function() {
                editor.windowManager.open({
                    title: "Insert Quiz Answers",
                    body: [
                        {
                            type: "textbox",
                            name: "quiz_id",
                            label: "Quiz ID",
                            value: ""
                        }
                    ],
                    onsubmit: function(e) {
                        editor.insertContent("[lilac_quiz_answers quiz_id=\"" + e.data.quiz_id + "\"]");
                    }
                });
            }
        });
    });
})();';
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($js_file))) {
            mkdir(dirname($js_file), 0755, true);
        }
        
        // Write the file
        file_put_contents($js_file, $js_content);
    }
}
add_action('init', 'lilac_create_quiz_answers_button_script');
