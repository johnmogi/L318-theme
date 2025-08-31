<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Save Hint Sections content
 */
function ld_sections_save_metabox( $post_id ) {
    // Verify nonce
    if ( ! isset( $_POST['ld_sections_metabox_nonce_field'] )
        || ! wp_verify_nonce( $_POST['ld_sections_metabox_nonce_field'], 'ld_sections_metabox_nonce' ) ) {
        return;
    }
    // Prevent auto-save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Save or delete meta
    if ( isset( $_POST['ld_hint_sections'] ) ) {
        $data = wp_kses_post( $_POST['ld_hint_sections'] );
        update_post_meta( $post_id, '_ld_hint_sections', $data );
    } else {
        delete_post_meta( $post_id, '_ld_hint_sections' );
    }
}
add_action( 'save_post', 'ld_sections_save_metabox' );
