<?php
/**
 * Hint UI Template
 * Renders show hint and reveal answer controls
 */
?>
<div class="ld-hint-wrapper" data-question-id="<?php echo esc_attr( $question_id ); ?>">
    <button type="button" class="ld-show-hint-button"><?php _e( 'קח רמז', 'learndash-hints' ); ?></button>
    <div class="ld-hint-message" style="display:none;"></div>
    <button type="button" class="ld-reveal-answer-button" style="display:none;"><?php _e( 'חשוף תשובה', 'learndash-hints' ); ?></button>
    <div class="ld-answer-message" style="display:none;"></div>
</div>
