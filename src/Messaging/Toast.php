<?php
/**
 * Toast Messaging System
 *
 * Provides a modern toast notification system for displaying messages
 * with session counting capabilities.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging
 */

namespace Lilac\Messaging;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Toast {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Session counter key
     */
    private $session_counter_key = 'lilac_session_counter';

    /**
     * Get instance of this class
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
        // Initialize the session if not already started
        add_action('init', array($this, 'init_session'), 1);
        
        // Register shortcodes
        add_shortcode('lilac_message', array($this, 'message_shortcode'));
        add_shortcode('lilac_session_counter', array($this, 'session_counter_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Initialize session if not already started
     */
    public function init_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
            
            // Initialize session counter if it doesn't exist
            if (!isset($_SESSION[$this->session_counter_key])) {
                $_SESSION[$this->session_counter_key] = 0;
            } else {
                // Increment the counter on each session initialization
                $_SESSION[$this->session_counter_key]++;
            }
        }
    }

    /**
     * Enqueue necessary CSS and JS
     * 
     * Note: Assets are now loaded from the includes/messaging directory
     * by the lilac_enqueue_messaging_scripts() function
     */
    public function enqueue_assets() {
        // Don't load assets here anymore - they're loaded from includes/messaging/admin-functions.php
        // This prevents duplicate asset loading
        
        // We still need to pass session data to JavaScript
        $nonce = wp_create_nonce('lilac_toast_nonce'); 
        wp_localize_script('lilac-toast-script', 'lilacToastData', array(
            'sessionCounter' => $this->get_session_count(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => $nonce
        ));
    }

    /**
     * Get the current session count
     */
    public function get_session_count() {
        return isset($_SESSION[$this->session_counter_key]) ? $_SESSION[$this->session_counter_key] : 0;
    }

    /**
     * Message shortcode
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string HTML output
     */
    public function message_shortcode($atts = [], $content = null) {
        // Extract and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'type' => 'info', // info, success, warning, error
            'title' => '',
            'dismissible' => 'true', // true or false
            'auto_close' => '0', // seconds (0 = no auto close)
            'message_id' => '', // unique identifier for the message
            'position' => 'top-left', // force top-left only
        ), $atts, 'lilac_message');
        
        $type = sanitize_text_field($atts['type']);
        $title = sanitize_text_field($atts['title']);
        $dismissible = filter_var($atts['dismissible'], FILTER_VALIDATE_BOOLEAN);
        $auto_close = absint($atts['auto_close']);
        $message_id = sanitize_text_field($atts['message_id']);
        
        // Force all toasts to top-left
        $position = 'top-left';
        
        // Process the content
        $content = $content ? wp_kses_post($content) : '';
        if (empty($content)) {
            return '';
        }
        
        // Process placeholders in the content and title
        $content = $this->process_placeholders($content);
        if (!empty($title)) {
            $title = $this->process_placeholders($title);
        }
        
        // Build the message HTML
        $html = '<div class="lilac-toast-container" data-position="' . esc_attr($position) . '">';
        $html .= '<div class="lilac-toast lilac-toast-' . esc_attr($type) . '" data-auto-close="' . esc_attr($auto_close) . '" data-message-id="' . esc_attr($message_id) . '">';
        
        if (!empty($title)) {
            $html .= '<div class="lilac-toast-header">' . esc_html($title) . '</div>';
        }
        
        $html .= '<div class="lilac-toast-body">' . $content . '</div>';
        
        if ($dismissible) {
            $html .= '<button class="lilac-toast-close" aria-label="' . esc_attr__('Close', 'hello-child') . '">×</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Process placeholders in the content
     * 
     * @param string $content Content with placeholders
     * @return string Content with placeholders replaced
     */
    public function process_placeholders($content) {
        // Skip if no content or no placeholders
        if (empty($content) || strpos($content, '{') === false) {
            return $content;
        }
        
        // Get current user
        $user = wp_get_current_user();
        $user_name = $user->ID ? $user->display_name : __('Guest', 'hello-child');
        
        // Get visit count
        $visit_count = isset($_SESSION[$this->session_counter_key]) ? intval($_SESSION[$this->session_counter_key]) : 0;
        
        // Calculate session time
        $session_time = '0:00';
        if (isset($_SESSION['session_start_time'])) {
            $time_diff = time() - $_SESSION['session_start_time'];
            if ($time_diff > 0) {
                $minutes = floor($time_diff / 60);
                $seconds = $time_diff % 60;
                $session_time = sprintf('%d:%02d', $minutes, $seconds);
            }
        } else {
            // Initialize session time tracker
            $_SESSION['session_start_time'] = time();
        }
        
        // Get course progress (from LearnDash if available)
        $course_progress = '0%';
        if (function_exists('learndash_get_course_id') && function_exists('learndash_course_progress')) {
            $course_id = learndash_get_course_id();
            if ($course_id && $user->ID) {
                $progress = learndash_course_progress(array(
                    'user_id' => $user->ID,
                    'course_id' => $course_id
                ));
                if (isset($progress['percentage'])) {
                    $course_progress = $progress['percentage'] . '%';
                }
            }
        }
        
        // Replace placeholders
        $replacements = array(
            '{user_name}' => $user_name,
            '{visit_count}' => $visit_count,
            '{active_time}' => $session_time,
            '{course_progress}' => $course_progress
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Session counter shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function session_counter_shortcode($atts = []) {
        // Extract and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'label' => __('Session Count:', 'hello-child'),
            'class' => '',
        ), $atts, 'lilac_session_counter');
        
        $label = sanitize_text_field($atts['label']);
        $class = sanitize_text_field($atts['class']);
        
        // Get the session count
        $count = $this->get_session_count();
        
        // Build the counter HTML
        $html = '<div class="lilac-session-counter ' . esc_attr($class) . '">';
        $html .= '<span class="lilac-session-counter-label">' . esc_html($label) . '</span> ';
        $html .= '<span class="lilac-session-counter-value">' . esc_html($count) . '</span>';
        $html .= '</div>';
        
        return $html;
    }
}

// Initialize the class
function lilac_toast() {
    return Toast::get_instance();
}
lilac_toast();
