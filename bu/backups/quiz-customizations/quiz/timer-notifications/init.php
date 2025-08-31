<?php
/**
 * Quiz Timer Notifications System
 * 
 * Provides a robust timer integration with LearnDash quizzes
 * for threshold warnings and test controls.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Lilac_Quiz_Timer_Notifications {
    /**
     * Initialize the module
     */
    public static function init() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'render_control_panel']);
    }

    /**
     * Enqueue required assets
     */
    public static function enqueue_assets() {
        // Only load on quiz pages
        if (!self::is_quiz_page()) {
            return;
        }

        // Enqueue the timer observer
        wp_enqueue_script(
            'lilac-timer-observer',
            get_stylesheet_directory_uri() . '/includes/quiz/timer-notifications/timer-observer.js',
            ['jquery', 'lilac-toast'],
            filemtime(get_stylesheet_directory() . '/includes/quiz/timer-notifications/timer-observer.js'),
            true
        );

        // Enqueue UI components
        wp_enqueue_script(
            'lilac-timer-ui',
            get_stylesheet_directory_uri() . '/includes/quiz/timer-notifications/timer-ui.js',
            ['jquery', 'lilac-timer-observer'],
            filemtime(get_stylesheet_directory() . '/includes/quiz/timer-notifications/timer-ui.js'),
            true
        );

        // Enqueue styles
        wp_enqueue_style(
            'lilac-timer-styles',
            get_stylesheet_directory_uri() . '/includes/quiz/timer-notifications/styles.css',
            [],
            filemtime(get_stylesheet_directory() . '/includes/quiz/timer-notifications/styles.css')
        );

        // Add debug information to console
        wp_add_inline_script('lilac-timer-observer', 
            "console.log('Lilac Quiz Timer Notifications loaded on: " . get_the_title() . "');", 
            'before'
        );
    }

    /**
     * Render the control panel in the footer
     */
    public static function render_control_panel() {
        if (!self::is_quiz_page() || !current_user_can('administrator')) {
            return;
        }

        ?>
        <div id="lilac-timer-controls" class="lilac-timer-controls">
            <h3>Quiz Timer Test Controls</h3>
            <div class="timer-buttons">
                <button class="lilac-test-btn" data-seconds="60">Test: דקה אחת נותרה</button>
                <button class="lilac-test-btn" data-seconds="30">Test: 30 שניות נותרו</button>
                <button class="lilac-test-btn" data-seconds="10">Test: 10 שניות נותרו</button>
                <button class="lilac-test-btn" data-seconds="0">Test: זמן הסתיים</button>
                <button class="lilac-test-btn" data-message="inactive">Test: עדיין כאן?</button>
            </div>
            <div class="timer-status">
                <label>
                    <input type="checkbox" id="lilac-demo-mode"> הפעל מצב הדגמה
                </label>
                <div id="lilac-time-display"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Check if current page is a quiz page
     */
    private static function is_quiz_page() {
        // Check for quiz post type
        if (is_singular('sfwd-quiz')) {
            return true;
        }

        // Check for quiz shortcode in content
        global $post;
        if ($post && (
            has_shortcode($post->post_content, 'learndash_quiz') || 
            has_shortcode($post->post_content, 'ld_quiz') ||
            has_shortcode($post->post_content, 'ld_course_content')
        )) {
            return true;
        }

        // Also check for wpProQuiz divs which indicate a quiz
        if (is_singular() && $post && strpos($post->post_content, 'wpProQuiz_') !== false) {
            return true;
        }

        return false;
    }
}

// Initialize
Lilac_Quiz_Timer_Notifications::init();
