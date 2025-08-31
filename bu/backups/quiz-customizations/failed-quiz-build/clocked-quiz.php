<?php
// Clocked Quiz Timer Module

// Register assets in header
add_action('init', function() {
    wp_register_script(
        'ld-quiz-timer',
        get_stylesheet_directory_uri() . '/includes/quiz/timer.js',
        [],
        filemtime(get_stylesheet_directory() . '/includes/quiz/timer.js'),
        false
    );
    wp_register_style(
        'ld-quiz-timer-styles',
        get_stylesheet_directory_uri() . '/includes/quiz/timer-styles.css',
        [],
        filemtime(get_stylesheet_directory() . '/includes/quiz/timer-styles.css')
    );
});

// Auto-enqueue on LearnDash quiz pages or when shortcode/div present
add_action('wp_enqueue_scripts', function() {
    global $post;
    $needs = is_singular('sfwd-quiz')
        || ($post && (has_shortcode($post->post_content, 'ld_timer')
            || strpos($post->post_content, 'data-ld-timer') !== false));
    if ($needs) {
        wp_enqueue_script('ld-quiz-timer');
        wp_enqueue_style('ld-quiz-timer-styles');
    }
});

// Shortcode for manual inclusion
add_shortcode('ld_timer', function($atts) {
    $atts = shortcode_atts([
        'duration' => 300,
        'id' => 'ld-timer-'.wp_rand()
    ], $atts);
    return sprintf('<div id="%s" data-ld-timer data-duration="%d"></div>',
        esc_attr($atts['id']), (int)$atts['duration']
    );
});
