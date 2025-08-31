<?php
/**
 * Enqueue custom styles for LearnDash LD30 theme
 */
function enqueue_ld30_styles() {
    if (function_exists('learndash_get_post_type')) {
        $post_type = learndash_get_post_type();
        if ($post_type === 'sfwd-lessons' || $post_type === 'sfwd-topic' || $post_type === 'sfwd-quiz') {
            // Enqueue theme styles first
            wp_enqueue_style(
                'ld30-theme-styles',
                get_stylesheet_directory_uri() . '/assets/css/ld30-theme.css',
                array(),
                '1.0.0'
            );

            // Enqueue Elementor-specific styles
            wp_enqueue_style(
                'elementor-specific-styles',
                get_stylesheet_directory_uri() . '/assets/css/elementor-specific.css',
                array('ld30-theme-styles'),
                '1.0.0'
            );

            // Enqueue header styles
            wp_enqueue_style(
                'ld30-header-styles',
                get_stylesheet_directory_uri() . '/assets/css/ld30-header.css',
                array('elementor-specific-styles'),
                '1.0.0'
            );

            // Enqueue navigation styles
            wp_enqueue_style(
                'ld30-navigation-styles',
                get_stylesheet_directory_uri() . '/assets/css/ld30-navigation.css',
                array('ld30-header-styles'),
                '1.0.0'
            );

            // Enqueue main overrides
            wp_enqueue_style(
                'ld30-mint-overrides',
                get_stylesheet_directory_uri() . '/assets/css/ld30-overrides.css',
                array('ld30-navigation-styles'),
                '1.0.0'
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'enqueue_ld30_styles', 999); // High priority to override Elementor
