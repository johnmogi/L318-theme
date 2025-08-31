<?php
/**
 * Hint Meta Box for LearnDash Questions
 */
defined('ABSPATH') || exit;

// Register meta box
add_action('add_meta_boxes', 'ld_register_hint_metabox');
function ld_register_hint_metabox() {
    add_meta_box(
        'ld_hint_metabox',
        __('Quiz Hint', 'hello-theme-child'),
        'ld_render_hint_metabox',
        'sfwd-question',
        'normal',
        'high'
    );
}

// Render meta box
function ld_render_hint_metabox($post) {
    wp_nonce_field('ld_hint_metabox_nonce', 'ld_hint_metabox_nonce_field');
    $hint = get_post_meta($post->ID, '_ld_question_hint', true);
    echo '<textarea style="width:100%;" rows="4" name="ld_question_hint">' . esc_textarea($hint) . '</textarea>';
}

// Enqueue highlight CSS for the hint metabox
add_action('admin_enqueue_scripts', 'ld_enqueue_hint_admin_styles');
function ld_enqueue_hint_admin_styles($hook) {
    $screen = get_current_screen();
    if (isset($screen->post_type) && $screen->post_type === 'sfwd-question') {
        wp_enqueue_style(
            'ld_admin_hint_css',
            get_stylesheet_directory_uri() . '/includes/quiz/admin-hint.css'
        );
    }
}

// Save hint meta
add_action('save_post', 'ld_save_hint_metabox');
function ld_save_hint_metabox($post_id) {
    if (!isset($_POST['ld_hint_metabox_nonce_field']) || 
        !wp_verify_nonce($_POST['ld_hint_metabox_nonce_field'], 'ld_hint_metabox_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (get_post_type($post_id) !== 'sfwd-question') {
        return;
    }
    if (current_user_can('edit_post', $post_id)) {
        $new_hint = sanitize_textarea_field($_POST['ld_question_hint'] ?? '');
        update_post_meta($post_id, '_ld_question_hint', $new_hint);
    }
}
