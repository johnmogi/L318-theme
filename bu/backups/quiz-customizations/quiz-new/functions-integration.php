<?php
/**
 * Quiz Functions Integration
 * 
 * This file should be included in your theme's functions.php.
 * It sets up all the necessary hooks and filters to integrate
 * our custom quiz system.
 */

defined('ABSPATH') || exit;

// Load template loader
require_once get_stylesheet_directory() . '/includes/quiz-new/quiz-template-loader.php';

/**
 * Add settings page for quiz customization
 */
function lilac_quiz_add_admin_menu() {
    add_options_page(
        'Quiz Settings',
        'Quiz Settings',
        'manage_options',
        'lilac-quiz-settings',
        'lilac_quiz_settings_page'
    );
}
add_action('admin_menu', 'lilac_quiz_add_admin_menu');

/**
 * Render the settings page
 */
function lilac_quiz_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('lilac_quiz_settings');
            do_settings_sections('lilac_quiz_settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings
 */
function lilac_quiz_register_settings() {
    register_setting('lilac_quiz_settings', 'lilac_quiz_settings');
    
    add_settings_section(
        'lilac_quiz_section',
        'Quiz Settings',
        'lilac_quiz_section_callback',
        'lilac_quiz_settings'
    );
    
    // Force Hint Mode
    add_settings_field(
        'force_hint_mode',
        'Force Hint Mode',
        'lilac_quiz_checkbox_field_callback',
        'lilac_quiz_settings',
        'lilac_quiz_section',
        array(
            'id' => 'force_hint_mode',
            'label_for' => 'force_hint_mode',
            'description' => 'Require hint viewing for incorrect answers'
        )
    );
    
    // Enable Quiz Sidebar
    add_settings_field(
        'enable_sidebar',
        'Enable Quiz Sidebar',
        'lilac_quiz_checkbox_field_callback',
        'lilac_quiz_settings',
        'lilac_quiz_section',
        array(
            'id' => 'enable_sidebar',
            'label_for' => 'enable_sidebar',
            'description' => 'Display sidebar alongside quiz content'
        )
    );
    
    // Show Visual Feedback
    add_settings_field(
        'show_visual_feedback',
        'Show Visual Feedback',
        'lilac_quiz_checkbox_field_callback',
        'lilac_quiz_settings',
        'lilac_quiz_section',
        array(
            'id' => 'show_visual_feedback',
            'label_for' => 'show_visual_feedback',
            'description' => 'Show visual feedback for correct/incorrect answers'
        )
    );
    
    // Enable Next Only on Correct
    add_settings_field(
        'enable_next_on_correct',
        'Enable Next Only on Correct',
        'lilac_quiz_checkbox_field_callback',
        'lilac_quiz_settings',
        'lilac_quiz_section',
        array(
            'id' => 'enable_next_on_correct',
            'label_for' => 'enable_next_on_correct',
            'description' => 'Only enable Next button if answer is correct'
        )
    );
    
    // Debug Mode
    add_settings_field(
        'debug_mode',
        'Debug Mode',
        'lilac_quiz_checkbox_field_callback',
        'lilac_quiz_settings',
        'lilac_quiz_section',
        array(
            'id' => 'debug_mode',
            'label_for' => 'debug_mode',
            'description' => 'Enable debugging information in browser console'
        )
    );
}
add_action('admin_init', 'lilac_quiz_register_settings');

/**
 * Section description callback
 */
function lilac_quiz_section_callback() {
    echo '<p>These settings control how quizzes are displayed and how they function.</p>';
}

/**
 * Checkbox field callback
 */
function lilac_quiz_checkbox_field_callback($args) {
    $options = get_option('lilac_quiz_settings', array());
    $id = $args['id'];
    $checked = isset($options[$id]) ? $options[$id] : true;
    
    echo '<input type="checkbox" id="' . esc_attr($id) . '" name="lilac_quiz_settings[' . esc_attr($id) . ']" ' . checked($checked, true, false) . ' value="1">';
    
    if (isset($args['description'])) {
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }
}

/**
 * Add a meta box to quiz edit screen for quiz options
 */
function lilac_quiz_add_meta_boxes() {
    add_meta_box(
        'lilac_quiz_options',
        'Quiz Display Options',
        'lilac_quiz_meta_box_callback',
        'sfwd-quiz',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'lilac_quiz_add_meta_boxes');

/**
 * Meta box callback
 */
function lilac_quiz_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('lilac_quiz_meta_box', 'lilac_quiz_meta_box_nonce');
    
    // Get saved values
    $quiz_meta = get_post_meta($post->ID, '_lilac_quiz_options', true);
    if (!is_array($quiz_meta)) {
        $quiz_meta = array();
    }
    
    // Default settings
    $defaults = array(
        'use_sidebar' => true,
        'force_hints' => true
    );
    
    // Merge with defaults
    $quiz_meta = wp_parse_args($quiz_meta, $defaults);
    
    ?>
    <p>
        <label for="lilac_quiz_use_sidebar">
            <input type="checkbox" id="lilac_quiz_use_sidebar" name="lilac_quiz_options[use_sidebar]" value="1" <?php checked($quiz_meta['use_sidebar'], true); ?>>
            Use sidebar layout
        </label>
    </p>
    
    <p>
        <label for="lilac_quiz_force_hints">
            <input type="checkbox" id="lilac_quiz_force_hints" name="lilac_quiz_options[force_hints]" value="1" <?php checked($quiz_meta['force_hints'], true); ?>>
            Force hint viewing on incorrect answers
        </label>
    </p>
    <?php
}

/**
 * Save quiz meta box data
 */
function lilac_quiz_save_meta_box_data($post_id) {
    // Check if we should save
    if (!isset($_POST['lilac_quiz_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['lilac_quiz_meta_box_nonce'], 'lilac_quiz_meta_box')) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save data
    if (isset($_POST['lilac_quiz_options']) && is_array($_POST['lilac_quiz_options'])) {
        $quiz_options = array(
            'use_sidebar' => isset($_POST['lilac_quiz_options']['use_sidebar']),
            'force_hints' => isset($_POST['lilac_quiz_options']['force_hints'])
        );
        
        update_post_meta($post_id, '_lilac_quiz_options', $quiz_options);
    }
}
add_action('save_post', 'lilac_quiz_save_meta_box_data');

/**
 * Add custom quiz class to body
 */
function lilac_quiz_body_classes($classes) {
    if (is_singular('sfwd-quiz')) {
        $quiz_id = get_the_ID();
        $quiz_meta = get_post_meta($quiz_id, '_lilac_quiz_options', true);
        
        if (is_array($quiz_meta) && isset($quiz_meta['use_sidebar']) && $quiz_meta['use_sidebar']) {
            $classes[] = 'quiz-with-sidebar';
        } else {
            $classes[] = 'quiz-standard-layout';
        }
    }
    
    return $classes;
}
add_filter('body_class', 'lilac_quiz_body_classes');
