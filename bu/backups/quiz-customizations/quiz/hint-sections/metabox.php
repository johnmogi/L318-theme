<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
error_log('[LD_HINT_SECTIONS] metabox.php included');

/**
 * Register rich-text Hint Sections metabox on LearnDash questions
 */
function ld_sections_register_metabox() {
    error_log('[LD_HINT_SECTIONS] ld_sections_register_metabox called');
    add_meta_box(
        'ld_hint_sections',
        __( 'Hint Sections', 'hello-theme-child' ),
        'ld_sections_render_metabox',
        'sfwd-question',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'ld_sections_register_metabox' );

/**
 * Render the Hint Sections metabox with WP Editor
 */
function ld_sections_render_metabox( $post ) {
    error_log('[LD_HINT_SECTIONS] ld_sections_render_metabox called for post ' . $post->ID);
    echo '<div style="border:1px solid red; padding:8px; margin-bottom:10px; background:#ffe;">Debug: Hint Sections metabox loaded.</div>';
    wp_nonce_field( 'ld_sections_metabox_nonce', 'ld_sections_metabox_nonce_field' );

    $content = get_post_meta( $post->ID, '_ld_hint_sections', true );
    if ( ! is_string( $content ) ) {
        $content = '';
    }

    wp_editor(
        $content,
        'ld_hint_sections_editor',
        array(
            'textarea_name' => 'ld_hint_sections',
            'media_buttons' => false,
            'textarea_rows' => 6,
            'tinymce'       => array(
                'toolbar1' => 'bold italic underline | bullist numlist | link',
            ),
            'quicktags'     => true,
            'editor_class'  => 'ld-hint-sections-editor'
        )
    );
}
