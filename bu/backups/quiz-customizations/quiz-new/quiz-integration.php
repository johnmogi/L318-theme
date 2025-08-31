<?php
/**
 * Lilac Quiz Integration
 * 
 * Integrates the rebuilt quiz system with WordPress and LearnDash.
 * Handles integration of sidebar and non-sidebar layouts.
 */

defined('ABSPATH') || exit;

class Lilac_Quiz_Integration {
    
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
        // Hook into WordPress/LearnDash
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('learndash_content', array($this, 'maybe_add_sidebar'), 10, 2);
        add_action('wp_footer', array($this, 'add_quiz_detector'));
        
        // Admin settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on LearnDash quiz pages
        if (!is_singular('sfwd-quiz') && !$this->is_quiz_page()) {
            return;
        }
        
        // Quiz controller script
        wp_enqueue_script(
            'lilac-quiz-controller',
            get_stylesheet_directory_uri() . '/includes/quiz-new/quiz-controller.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/includes/quiz-new/quiz-controller.js'),
            true
        );
        
        // Quiz styles
        wp_enqueue_style(
            'lilac-quiz-styles',
            get_stylesheet_directory_uri() . '/includes/quiz-new/quiz-styles.css',
            array(),
            filemtime(get_stylesheet_directory() . '/includes/quiz-new/quiz-styles.css')
        );
        
        // Pass quiz settings to JavaScript
        $this->localize_quiz_settings();
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
    
    /**
     * Pass quiz settings to JavaScript
     */
    private function localize_quiz_settings() {
        // Get saved settings
        $settings = get_option('lilac_quiz_settings', array());
        
        // Default settings
        $defaults = array(
            'force_hint_mode' => true,
            'enable_sidebar' => true,
            'show_visual_feedback' => true,
            'enable_next_on_correct' => true,
            'debug_mode' => false
        );
        
        // Merge with defaults
        $settings = wp_parse_args($settings, $defaults);
        
        // Localize script
        wp_localize_script('lilac-quiz-controller', 'lilacQuizSettings', $settings);
    }
    
    /**
     * Register settings for the quiz system
     */
    public function register_settings() {
        register_setting('lilac_quiz_settings', 'lilac_quiz_settings');
        
        add_settings_section(
            'lilac_quiz_section',
            'Lilac Quiz Settings',
            array($this, 'render_settings_section'),
            'lilac_quiz_settings'
        );
        
        add_settings_field(
            'force_hint_mode',
            'Force Hint Mode',
            array($this, 'render_checkbox_field'),
            'lilac_quiz_settings',
            'lilac_quiz_section',
            array(
                'id' => 'force_hint_mode',
                'description' => 'Require hint viewing for incorrect answers before proceeding'
            )
        );
        
        add_settings_field(
            'enable_sidebar',
            'Enable Quiz Sidebar',
            array($this, 'render_checkbox_field'),
            'lilac_quiz_settings',
            'lilac_quiz_section',
            array(
                'id' => 'enable_sidebar',
                'description' => 'Add a sidebar to quiz pages for additional content'
            )
        );
        
        add_settings_field(
            'show_visual_feedback',
            'Show Visual Feedback',
            array($this, 'render_checkbox_field'),
            'lilac_quiz_settings',
            'lilac_quiz_section',
            array(
                'id' => 'show_visual_feedback',
                'description' => 'Show visual indicators for correct/incorrect answers'
            )
        );
        
        add_settings_field(
            'enable_next_on_correct',
            'Enable Next Only on Correct',
            array($this, 'render_checkbox_field'),
            'lilac_quiz_settings',
            'lilac_quiz_section',
            array(
                'id' => 'enable_next_on_correct',
                'description' => 'Only enable the Next button when the answer is correct'
            )
        );
        
        add_settings_field(
            'debug_mode',
            'Debug Mode',
            array($this, 'render_checkbox_field'),
            'lilac_quiz_settings',
            'lilac_quiz_section',
            array(
                'id' => 'debug_mode',
                'description' => 'Enable detailed console logging for troubleshooting'
            )
        );
    }
    
    /**
     * Render settings section
     */
    public function render_settings_section() {
        echo '<p>Settings for the Lilac Quiz System. These settings control how quizzes behave on your site.</p>';
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $settings = get_option('lilac_quiz_settings', array());
        $id = $args['id'];
        $checked = isset($settings[$id]) ? $settings[$id] : false;
        
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="lilac_quiz_settings[' . esc_attr($id) . ']" value="1" ' . checked(1, $checked, false) . '>';
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    /**
     * Conditionally add sidebar to quiz content
     */
    public function maybe_add_sidebar($content, $post) {
        // Only apply to quiz pages
        if (!is_singular('sfwd-quiz') && !$this->is_quiz_page()) {
            return $content;
        }
        
        // Get settings
        $settings = get_option('lilac_quiz_settings', array());
        
        // Check if sidebar is enabled
        if (empty($settings['enable_sidebar'])) {
            return $content;
        }
        
        // Look for quiz content
        if (strpos($content, 'wpProQuiz_content') !== false) {
            // Add wrapper div for sidebar layout
            $content = preg_replace(
                '/<div[^>]*class=["\']wpProQuiz_content["\'][^>]*>/i',
                '<div class="quiz-with-sidebar"><div class="wpProQuiz_content">',
                $content
            );
            
            // Add sidebar and closing wrapper div
            $content = preg_replace(
                '/<\/div>(\s*?)(<div[^>]*class=["\']wpProQuiz_results|$)/i',
                '</div>' . $this->get_quiz_sidebar() . '</div>$1$2',
                $content,
                1
            );
        }
        
        return $content;
    }
    
    /**
     * Get the quiz sidebar content
     */
    private function get_quiz_sidebar() {
        // Try to get sidebar template
        $template_path = get_stylesheet_directory() . '/includes/quiz-new/quiz-sidebar-template.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Default sidebar content
        return '<div class="quiz-sidebar">
            <h3>תזכורות לבוחן</h3>
            <ul>
                <li>קרא בעיון את כל השאלות לפני שאתה עונה</li>
                <li>אם אינך בטוח בתשובה, השתמש ברמז</li>
                <li>אתה יכול לחזור למעבר על התשובות שלך</li>
            </ul>
            <div class="quiz-sidebar-help">
                <h4>צריך עזרה?</h4>
                <p>אם אתה נתקל בקשיים, לחץ על כפתור הרמז או פנה למדריך שלך לעזרה.</p>
            </div>
        </div>';
    }
    
    /**
     * Add quiz detection script to footer
     */
    public function add_quiz_detector() {
        // Only add on LearnDash pages
        if (!is_singular('sfwd-quiz') && !$this->is_quiz_page()) {
            return;
        }
        
        // Add inline script to initialize quiz controller when quiz is found
        ?>
        <script>
        (function() {
            // Try to detect quizzes after page load
            function detectQuizzes() {
                const quizContainer = document.querySelector('.wpProQuiz_content');
                if (quizContainer && window.LilacQuiz) {
                    console.log('[Lilac Quiz] Quiz detected, initializing controller');
                    window.LilacQuiz.forceInit();
                    return true;
                }
                return false;
            }
            
            // Check immediately
            if (!detectQuizzes()) {
                // Check again after DOM load
                document.addEventListener('DOMContentLoaded', detectQuizzes);
                
                // And after a delay to catch late DOM updates
                setTimeout(detectQuizzes, 1000);
            }
        })();
        </script>
        <?php
    }
}

// Initialize
Lilac_Quiz_Integration::get_instance();
