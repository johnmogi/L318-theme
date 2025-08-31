<?php
/**
 * Class CCR_Quiz_Extensions
 *
 * Modular, extensible admin page for managing quiz enhancements (e.g., hints, timers) for LearnDash/WpProQuiz quizzes.
 *
 * - Registers a new admin menu page ("Quiz Extensions")
 * - Renders a tabbed settings UI (future-proof for more sections)
 * - Lists quizzes and allows toggling enhancements per quiz (initially: "Show Hint" feature)
 * - Saves settings in the options table (namespaced)
 * - Loads a separate view template for UI
 *
 * @author Your Team
 * @since 2025-05-07
 */
class CCR_Quiz_Extensions {
    /**
     * Option key for storing settings
     */
    const OPTION_KEY = 'ccr_quiz_extensions_settings';

    /**
     * Constructor: hooks into admin_menu
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_post_ccr_save_quiz_extensions', [$this, 'handle_save']);
        
        // Direct form handling in admin.php
        add_action('admin_init', [$this, 'handle_direct_save']);
    }

    /**
     * Register the admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Learn Options', 'hello-child'), // MENU LABEL
            __('Learn Options', 'hello-child'),
            'manage_options',
            'quiz-extensions',
            [$this, 'render_admin_page'],
            'dashicons-welcome-learn-more',
            32
        );
    }

    /**
     * Enqueue scripts/styles for the admin page (future use)
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_quiz-extensions') return;
        // Example: wp_enqueue_style('ccr-quiz-extensions-admin', ...);
    }

    /**
     * Render the main admin page UI
     */
    public function render_admin_page() {
        $settings = get_option(self::OPTION_KEY, []);
        $quizzes = $this->get_all_quizzes();
        include get_stylesheet_directory() . '/includes/admin/views/quiz-extensions.php';
    }

    /**
     * Handle settings save through admin-post.php (POST)
     */
    public function handle_save() {
        if (!current_user_can('manage_options') || !check_admin_referer('ccr_quiz_extensions_save')) {
            wp_die(__('Unauthorized request', 'hello-child'));
        }
        $settings = isset($_POST['quiz_extensions']) ? (array) $_POST['quiz_extensions'] : [];
        update_option(self::OPTION_KEY, $settings);
        wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=quiz-extensions')));
        exit;
    }
    
    /**
     * Alternative handler for direct form submissions in admin.php
     * This bypasses admin-post.php which may have issues in some WordPress setups
     */
    public function handle_direct_save() {
        // Only run on quiz extensions page with a POST request
        if (!isset($_GET['page']) || $_GET['page'] !== 'quiz-extensions' || !isset($_POST['ccr_direct_save'])) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized request', 'hello-child'));
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ccr_quiz_extensions_save')) {
            wp_die(__('Security check failed', 'hello-child'));
        }
        
        // Save settings
        $settings = isset($_POST['quiz_extensions']) ? (array) $_POST['quiz_extensions'] : [];
        update_option(self::OPTION_KEY, $settings);
        
        // Redirect to same page with success message
        wp_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=quiz-extensions')));
        exit;
    }

    /**
     * Get all quizzes (LearnDash + WpProQuiz)
     *
     * @return array [ [ 'ID' => int, 'title' => string, ... ], ... ]
     */
    public function get_all_quizzes() {
        // LearnDash quizzes are 'sfwd-quiz', WpProQuiz are 'wp_pro_quiz'
        $args = [
            'post_type' => ['sfwd-quiz', 'wp_pro_quiz'],
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        $posts = get_posts($args);
        $quizzes = [];
        foreach ($posts as $post) {
            $quizzes[] = [
                'ID' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type
            ];
        }
        return $quizzes;
    }

    /**
     * Enqueue frontend script to disable quiz top bar navigation as needed
     */
    public static function maybe_enqueue_frontend_script() {
        // Get settings
        $settings = get_option(self::OPTION_KEY, []);
        $quiz_ids = [];
        foreach ($settings as $quiz_id => $opts) {
            if (!empty($opts['disable_topbar'])) {
                $quiz_ids[] = (int)$quiz_id;
            }
        }
        if (!$quiz_ids) return; // No quizzes need this

        // Only enqueue on quiz pages (body class check)
        if (!is_singular(['sfwd-quiz', 'wp_pro_quiz'])) return;

        // Enqueue Lilac Toast assets first (if not already enqueued)
        if (!wp_script_is('lilac-toast', 'enqueued')) {
            if (function_exists('Lilac\\Messaging\\Toast::get_instance')) {
                \Lilac\Messaging\Toast::get_instance()->enqueue_assets();
            } else {
                // Fallback: try to enqueue manually if class not available
                $dir_url = get_stylesheet_directory_uri() . '/src/Messaging/assets';
                wp_register_script(
                    'lilac-toast',
                    $dir_url . '/js/toast.js',
                    array('jquery'),
                    filemtime(get_stylesheet_directory() . '/src/Messaging/assets/js/toast.js'),
                    true
                );
                wp_enqueue_script('lilac-toast');
                wp_register_style(
                    'lilac-toast',
                    $dir_url . '/css/toast.css',
                    array(),
                    filemtime(get_stylesheet_directory() . '/src/Messaging/assets/css/toast.css')
                );
                wp_enqueue_style('lilac-toast');
            }
        }
        // Now enqueue quiz-extensions with lilac-toast as a dependency
        wp_enqueue_script(
            'ccr-quiz-extensions',
            get_stylesheet_directory_uri() . '/js/quiz-extensions.js',
            ['jquery', 'lilac-toast'],
            filemtime(get_stylesheet_directory() . '/js/quiz-extensions.js'),
            true
        );
        wp_localize_script('ccr-quiz-extensions', 'quizExtensionsSettings', [
            'disable_topbar_quiz_ids' => $quiz_ids
        ]);
    }
}

// Initialize the class and hook frontend logic
if (is_admin()) {
    new CCR_Quiz_Extensions();
} else {
    add_action('wp_enqueue_scripts', ['CCR_Quiz_Extensions', 'maybe_enqueue_frontend_script']);
}
