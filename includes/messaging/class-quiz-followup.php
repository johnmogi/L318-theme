<?php
/**
 * Quiz Follow-up Handler
 * 
 * Handles quiz completion tracking and follow-up messages
 */
class Lilac_Quiz_Followup {

    /**
     * @var string Option name for storing settings
     */
    private $option_name = 'lilac_quiz_followup_settings';

    /**
     * @var array Default settings
     */
    private $defaults = [
        'enable_exit_warning' => true,
        'exit_warning_message' => 'טרם השלמת את המבחן. האם אתה בטוח שברצונך לעזוב?',
        'incomplete_redirect' => '',
        'incomplete_message' => 'אנא השלימו את המבחן כדי להמשיך.',
        'enable_completion_redirect' => false,
        'completion_redirect' => '',
        'completion_delay' => 5,
        'completion_message' => 'המבחן הושלם בהצלחה! מעביר אותך תוך {seconds} שניות...',
    ];

    /**
     * @var Lilac_Quiz_Followup Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
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
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_lilac_quiz_completed', [$this, 'handle_quiz_completed']);
        add_action('wp_ajax_nopriv_lilac_quiz_completed', [$this, 'handle_quiz_completed']);
        add_action('wp_footer', [$this, 'output_quiz_tracking_script']);
    }

    /**
     * Enqueue required assets
     */
    public function enqueue_assets() {
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }

        // Enqueue the quiz exit intent script
        wp_enqueue_script(
            'lilac-quiz-exit-intent',
            get_stylesheet_directory_uri() . '/includes/messaging/js/quiz-exit-intent.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/includes/messaging/js/quiz-exit-intent.js'),
            true
        );

        // Localize script with settings
        wp_localize_script('lilac-quiz-exit-intent', 'lilacQuizFollowup', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_quiz_followup_nonce'),
            'settings' => $this->get_settings(),
        ]);
    }

    /**
     * Get settings with defaults
     */
    public function get_settings() {
        $saved = get_option($this->option_name, []);
        return wp_parse_args($saved, $this->defaults);
    }

    /**
     * Update settings
     */
    public function update_settings($settings) {
        // Sanitize settings
        $sanitized = [];
        $sanitized['enable_exit_warning'] = !empty($settings['enable_exit_warning']);
        $sanitized['exit_warning_message'] = sanitize_text_field($settings['exit_warning_message']);
        $sanitized['incomplete_redirect'] = esc_url_raw($settings['incomplete_redirect']);
        $sanitized['incomplete_message'] = sanitize_text_field($settings['incomplete_message']);
        $sanitized['enable_completion_redirect'] = !empty($settings['enable_completion_redirect']);
        $sanitized['completion_redirect'] = esc_url_raw($settings['completion_redirect']);
        $sanitized['completion_delay'] = absint($settings['completion_delay']);
        $sanitized['completion_message'] = sanitize_text_field($settings['completion_message']);

        return update_option($this->option_name, $sanitized);
    }

    /**
     * Handle quiz completion via AJAX
     */
    public function handle_quiz_completed() {
        check_ajax_referer('lilac_quiz_followup_nonce', 'nonce');

        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $user_id = get_current_user_id();

        if (!$quiz_id) {
            wp_send_json_error(['message' => 'Invalid quiz ID']);
        }

        // Mark quiz as completed for this user
        $this->mark_quiz_completed($quiz_id, $user_id);

        // Get redirect URL if enabled
        $settings = $this->get_settings();
        $response = [
            'success' => true,
            'redirect' => $settings['enable_completion_redirect'] ? $settings['completion_redirect'] : '',
            'message' => $settings['completion_message'],
            'delay' => $settings['completion_delay']
        ];

        wp_send_json_success($response);
    }

    /**
     * Mark a quiz as completed for a user
     */
    public function mark_quiz_completed($quiz_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $completed_quizzes = get_user_meta($user_id, '_lilac_completed_quizzes', true);
        if (!is_array($completed_quizzes)) {
            $completed_quizzes = [];
        }

        // Add quiz to completed list if not already there
        if (!in_array($quiz_id, $completed_quizzes)) {
            $completed_quizzes[] = $quiz_id;
            update_user_meta($user_id, '_lilac_completed_quizzes', $completed_quizzes);
        }

        // Set a cookie for non-logged-in users
        if (!$user_id) {
            $cookie_name = 'lilac_completed_quiz_' . $quiz_id;
            setcookie(
                $cookie_name,
                '1',
                time() + (30 * DAY_IN_SECONDS),
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }

        return true;
    }

    /**
     * Check if a user has completed a quiz
     */
    public function has_completed_quiz($quiz_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // For logged-in users
        if ($user_id) {
            $completed_quizzes = get_user_meta($user_id, '_lilac_completed_quizzes', true);
            return is_array($completed_quizzes) && in_array($quiz_id, $completed_quizzes);
        }
        
        // For non-logged-in users, check cookie
        return isset($_COOKIE['lilac_completed_quiz_' . $quiz_id]);
    }

    /**
     * Output the quiz tracking script in the footer
     */
    public function output_quiz_tracking_script() {
        // Only output on quiz pages
        if (!$this->is_quiz_page()) {
            return;
        }

        $quiz_id = $this->get_current_quiz_id();
        if (!$quiz_id) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize exit intent
            if (typeof window.LilacQuizExitIntent !== 'undefined') {
                new window.LilacQuizExitIntent(<?php echo $quiz_id; ?>, {
                    enabled: <?php echo $this->get_settings()['enable_exit_warning'] ? 'true' : 'false'; ?>,
                    message: '<?php echo esc_js($this->get_settings()['exit_warning_message']); ?>',
                    debug: <?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'true' : 'false'; ?>
                });
            }

            // Handle quiz completion
            $(document).on('lilac_quiz_completed', function(e, quizId) {
                if (typeof quizId === 'undefined') {
                    quizId = <?php echo $quiz_id; ?>;
                }

                $.ajax({
                    url: lilacQuizFollowup.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lilac_quiz_completed',
                        quiz_id: quizId,
                        nonce: lilacQuizFollowup.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.redirect) {
                            // Show completion message
                            if (window.LilacToast && response.data.message) {
                                window.LilacToast.success(
                                    response.data.message.replace('{seconds}', response.data.delay),
                                    'Quiz Completed!',
                                    { autoHide: false }
                                );
                            }
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, response.data.delay * 1000);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Check if current page is a quiz page
     */
    private function is_quiz_page() {
        global $post;
        
        if (is_singular('sfwd-quiz') || is_singular('quiz')) {
            return true;
        }
        
        if ($post && 
            (has_shortcode($post->post_content, 'ld_quiz') || 
             has_shortcode($post->post_content, 'quiz') ||
             has_block('learndash/ld-quiz', $post) ||
             has_block('quiz', $post))) {
            return true;
        }
        
        return false;
    }

    /**
     * Get current quiz ID
     */
    private function get_current_quiz_id() {
        global $post;
        
        if (is_singular('sfwd-quiz') || is_singular('quiz')) {
            return $post->ID;
        }
        
        // Try to get quiz ID from shortcode attributes
        if ($post) {
            $shortcode_regex = '/\[(?:ld_quiz|quiz)[^\]]*id=["\']?(\d+)["\']?[^\]]*\]/i';
            if (preg_match($shortcode_regex, $post->post_content, $matches)) {
                return $matches[1];
            }
        }
        
        // Fallback to URL parameter
        if (!empty($_GET['quiz_id'])) {
            return intval($_GET['quiz_id']);
        }
        
        return 0;
    }
}

// Initialize the plugin
function lilac_quiz_followup_init() {
    return Lilac_Quiz_Followup::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'lilac_quiz_followup_init');

// Helper functions for template usage
if (!function_exists('lilac_has_completed_quiz')) {
    /**
     * Check if current user has completed a quiz
     * 
     * @param int $quiz_id Quiz ID to check
     * @param int $user_id Optional. User ID. Defaults to current user.
     * @return bool Whether the user has completed the quiz
     */
    function lilac_has_completed_quiz($quiz_id, $user_id = 0) {
        $quiz_followup = Lilac_Quiz_Followup::get_instance();
        return $quiz_followup->has_completed_quiz($quiz_id, $user_id);
    }
}

if (!function_exists('lilac_require_quiz_completion')) {
    /**
     * Require quiz completion before showing content
     * 
     * @param int $quiz_id Quiz ID to check
     * @param string $message Optional. Message to show if quiz is not completed
     * @return bool Whether the quiz is completed
     */
    function lilac_require_quiz_completion($quiz_id, $message = '') {
        $quiz_followup = Lilac_Quiz_Followup::get_instance();
        
        if ($quiz_followup->has_completed_quiz($quiz_id)) {
            return true;
        }
        
        $settings = $quiz_followup->get_settings();
        
        // Redirect if set
        if (!empty($settings['incomplete_redirect'])) {
            wp_redirect($settings['incomplete_redirect']);
            exit;
        }
        
        // Show message
        $message = !empty($message) ? $message : $settings['incomplete_message'];
        if (function_exists('LilacToast') && method_exists('LilacToast', 'warning')) {
            LilacToast::warning($message, 'Quiz Required');
        } else {
            echo '<div class="lilac-quiz-warning notice notice-warning"><p>' . esc_html($message) . '</p></div>';
        }
        
        return false;
    }
}
