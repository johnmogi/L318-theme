<?php
/**
 * Quiz Template Loader
 * 
 * Ensures our custom quiz templates are loaded instead of the default LearnDash templates.
 * This file should be included in your theme's functions.php.
 */

defined('ABSPATH') || exit;

class Lilac_Quiz_Template_Loader {
    
    private static $instance = null;
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register our template directory with LearnDash
        add_filter('learndash_template_directory_filter', array($this, 'add_template_directory'), 10, 3);
        
        // Enqueue our scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add quiz classes to body
        add_filter('body_class', array($this, 'add_body_classes'));
    }
    
    /**
     * Register our theme as a template source
     */
    public function add_template_directory($filepath, $name, $args) {
        // Check if this is a quiz template
        if ($name === 'quiz' || strpos($name, 'quiz/') === 0) {
            // Check if our template file exists
            $theme_templates_dir = get_stylesheet_directory() . '/learndash/';
            $theme_file = $theme_templates_dir . $name . '.php';
            
            if (file_exists($theme_file)) {
                return $theme_file;
            }
        }
        
        return $filepath;
    }
    
    /**
     * Enqueue scripts and styles for the quiz pages
     */
    public function enqueue_scripts() {
        // Only load on quiz pages
        if (!is_singular('sfwd-quiz') && !$this->is_quiz_page()) {
            return;
        }
        
        // Enqueue our controller script
        wp_enqueue_script(
            'lilac-quiz-controller',
            get_stylesheet_directory_uri() . '/includes/quiz-new/quiz-controller.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/includes/quiz-new/quiz-controller.js'),
            true
        );
        
        // Enqueue our styles
        wp_enqueue_style(
            'lilac-quiz-styles',
            get_stylesheet_directory_uri() . '/includes/quiz-new/quiz-styles.css',
            array(),
            filemtime(get_stylesheet_directory() . '/includes/quiz-new/quiz-styles.css')
        );
        
        // Pass settings to script
        $settings = get_option('lilac_quiz_settings', array(
            'force_hint_mode' => true,
            'enable_sidebar' => true,
            'show_visual_feedback' => true,
            'enable_next_on_correct' => true,
            'debug_mode' => true
        ));
        
        wp_localize_script('lilac-quiz-controller', 'lilacQuizSettings', $settings);
    }
    
    /**
     * Add classes to body for quiz pages
     */
    public function add_body_classes($classes) {
        if (is_singular('sfwd-quiz') || $this->is_quiz_page()) {
            $classes[] = 'lilac-quiz-page';
            
            // Get quiz settings
            $settings = get_option('lilac_quiz_settings', array());
            $has_sidebar = isset($settings['enable_sidebar']) ? $settings['enable_sidebar'] : true;
            
            if ($has_sidebar) {
                $classes[] = 'quiz-with-sidebar';
            } else {
                $classes[] = 'quiz-standard-layout';
            }
        }
        
        return $classes;
    }
    
    /**
     * Check if current page contains a LearnDash quiz
     */
    private function is_quiz_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if the post content has a quiz shortcode
        if (has_shortcode($post->post_content, 'ld_quiz') || 
            has_shortcode($post->post_content, 'learndash_quiz')) {
            return true;
        }
        
        // Check for quiz content
        if (strpos($post->post_content, 'wpProQuiz_content') !== false) {
            return true;
        }
        
        return false;
    }
}

// Initialize the template loader
Lilac_Quiz_Template_Loader::get_instance();
