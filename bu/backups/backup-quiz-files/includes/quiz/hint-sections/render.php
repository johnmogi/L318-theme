<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
error_log('[LD_HINT_SECTIONS] render.php included');

/**
 * Render Hint Sections on the frontend after each question
 */
function ld_sections_render_frontend( $question, $question_index ) {
    error_log(sprintf('[LD_HINT_SECTIONS] render_frontend called for post ID %d at index %d', $question->post->ID, $question_index));
    echo '<div style="border:2px dashed blue; padding:10px; margin:10px 0; background:#eef;">Debug Frontend: Hint render fired for question ' . esc_html($question->post->ID) . '</div>';
    if ( isset( $question->post->ID ) ) {
        $content = get_post_meta( $question->post->ID, '_ld_hint_sections', true );
        if ( $content ) {
            echo '<div class="ld-hint-sections-front">' . wp_kses_post( $content ) . '</div>';
        }
    }
}
add_action( 'learndash_question_after', 'ld_sections_render_frontend', 10, 2 );
