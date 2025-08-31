<?php
/**
 * LearnDash Hints Module
 * Provides contextual hints and reveals correct answers on demand.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue assets
function ld_hints_enqueue_assets() {
    $base_url = get_stylesheet_directory_uri() . '/learndash-hints';
    wp_enqueue_style( 'ld-hints-style', $base_url . '/css/learndash-hints.css' );
    wp_enqueue_script( 'ld-hints-script', $base_url . '/js/learndash-hints.js', array('jquery'), null, true );
    wp_localize_script( 'ld-hints-script', 'LD_HINTS_AJAX', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ld_hints_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'ld_hints_enqueue_assets' );

// Fetch educational hint
function ld_hints_get_hint() {
    check_ajax_referer( 'ld_hints_nonce', 'nonce' );
    $question_id = intval( $_POST['question_id'] );
    $hint_text   = get_post_meta( $question_id, '_wpProQuiz_tip', true );
    wp_send_json_success( array( 'hint' => $hint_text ) );
}
add_action( 'wp_ajax_ld_hints_get_hint', 'ld_hints_get_hint' );
add_action( 'wp_ajax_nopriv_ld_hints_get_hint', 'ld_hints_get_hint' );

// Reveal correct answer
function ld_hints_reveal_answer() {
    check_ajax_referer( 'ld_hints_nonce', 'nonce' );
    $question_id = intval( $_POST['question_id'] );
    global $wpdb;
    $table = $wpdb->prefix . 'pro_quiz_answer';
    $answer = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT answer FROM {$table} WHERE question_id = %d AND correct = 1",
            $question_id
        )
    );
    wp_send_json_success( array( 'answer' => $answer ) );
}
add_action( 'wp_ajax_ld_hints_reveal_answer', 'ld_hints_reveal_answer' );
add_action( 'wp_ajax_nopriv_ld_hints_reveal_answer', 'ld_hints_reveal_answer' );

// Append hint UI after each question's answers
function ld_hints_append_ui( $answer_html, $question ) {
    $question_id = $question->ID;
    $template = get_stylesheet_directory() . '/learndash-hints/templates/hint-template.php';
    if ( file_exists( $template ) ) {
        ob_start();
        include $template;
        $ui = ob_get_clean();
        return $answer_html . $ui;
    }
    return $answer_html;
}
add_filter( 'learndash_question_after_answer', 'ld_hints_append_ui', 10, 2 );
