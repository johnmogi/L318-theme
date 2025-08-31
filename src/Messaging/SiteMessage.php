<?php
/**
 * Site-wide Messaging Integration
 *
 * Handles site-wide toast messages, welcome messages, and contextual notifications
 * based on admin settings.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging
 */

namespace Lilac\Messaging;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SiteMessage {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Option name for storing message settings
     */
    private $option_name = 'lilac_messaging_settings';
    
    /**
     * Session key for tracking shown messages
     */
    private $session_key = 'lilac_shown_messages';
    
    /**
     * Message queue to prevent multiple messages from appearing simultaneously
     */
    private $message_queue = [];
    
    /**
     * Flag to track if a message is currently being displayed
     */
    private $message_displayed = false;

    /**
     * Session key for tracking page views
     */
    private $session_counter_key = 'lilac_page_views';

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
        
        // Add site-wide messages
        add_action('wp_footer', array($this, 'display_site_messages'), 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_dismiss_message', array($this, 'ajax_dismiss_message'));
        add_action('wp_ajax_nopriv_dismiss_message', array($this, 'ajax_dismiss_message'));
        
        // Track page views for contextual messages
        add_action('template_redirect', array($this, 'track_page_visit'));
    }

    /**
     * Initialize session if not already started
     */
    public function init_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
            
            // Initialize session message tracking if it doesn't exist
            if (!isset($_SESSION[$this->session_key])) {
                $_SESSION[$this->session_key] = array();
            }
            
            // Initialize session page views if it doesn't exist
            if (!isset($_SESSION[$this->session_counter_key])) {
                $_SESSION[$this->session_counter_key] = 0;
            }
        }
    }

    /**
     * Check if current user should be excluded from seeing messages
     */
    private function should_exclude_current_user() {
        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user is not logged in (guests should see messages)
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Define privileged roles that should be excluded by default
        $privileged_roles = array(
            'administrator',
            'teacher', 
            'instructor',
            'group_leader',
            'course_author',
            'editor',
            'author'
        );
        
        // Check if user has any privileged role
        $has_privileged_role = false;
        foreach ($privileged_roles as $role) {
            if (in_array($role, $current_user->roles)) {
                $has_privileged_role = true;
                break;
            }
        }
        
        // Also check for specific capabilities
        $has_privileged_caps = current_user_can('manage_options') || 
                              current_user_can('edit_courses') || 
                              current_user_can('edit_lessons') ||
                              current_user_can('edit_others_posts');
        
        // If user has privileged role or capabilities
        if ($has_privileged_role || $has_privileged_caps) {
            // Check if user has opted-in to see messages
            $user_id = $current_user->ID;
            $opt_in = get_user_meta($user_id, 'lilac_message_opt_in', true);
            
            // If user has not opted-in, exclude them from seeing messages
            if (!$opt_in) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Display site messages in footer
     */
    public function display_site_messages() {
        // Check if current user should be excluded from seeing messages
        if ($this->should_exclude_current_user()) {
            return;
        }
        
        // Get settings
        $settings = get_option($this->option_name, array());
        
        // Reset message display flag at the start of each page load
        $this->message_displayed = false;
        $this->message_queue = [];
        
        // Process custom messages from the message manager
        $this->process_custom_messages();
        
        // Display welcome message if enabled
        if (isset($settings['welcome_message_enabled']) && $settings['welcome_message_enabled']) {
            $this->maybe_display_welcome_message($settings);
        }
        
        // Process the message queue (only one message will be displayed)
        $this->process_message_queue();
    }
    
    /**
     * Process custom messages from the message manager
     */
    private function process_custom_messages() {
        // Get custom messages
        $messages = get_option('lilac_custom_messages', array());
        
        if (empty($messages)) {
            return;
        }
        
        foreach ($messages as $message) {
            // Skip inactive messages
            if (!isset($message['active']) || !$message['active']) {
                continue;
            }
            
            // Check display conditions
            if (!$this->check_display_conditions($message)) {
                continue;
            }
            
            // Add to queue with priority based on type (error > warning > info > success)
            $priority = 10; // Default
            
            switch ($message['type']) {
                case 'error':
                    $priority = 40;
                    break;
                case 'warning':
                    $priority = 30;
                    break;
                case 'info':
                    $priority = 20;
                    break;
                case 'success':
                    $priority = 10;
                    break;
            }
            
            $this->queue_message($message, $priority);
        }
    }
    
    /**
     * Check if a message should be displayed based on its conditions
     * 
     * @param array $message The message to check
     * @return bool Whether the message should be displayed
     */
    private function check_display_conditions($message) {
        // Check session display
        if (isset($message['display_conditions']['once_per_session']) && 
            $message['display_conditions']['once_per_session'] && 
            isset($_SESSION[$this->session_key][$message['id']])) {
            return false;
        }
        
        // Check homepage condition
        if (isset($message['display_conditions']['homepage']) && 
            $message['display_conditions']['homepage'] && 
            !is_front_page()) {
            return false;
        }
        
        // Check specific URLs
        if (!empty($message['display_conditions']['specific_urls'])) {
            $urls = array_map('trim', explode("\n", $message['display_conditions']['specific_urls']));
            $current_url = rtrim($_SERVER['REQUEST_URI'], '/');
            
            $url_match = false;
            foreach ($urls as $url) {
                if (rtrim($url, '/') === $current_url) {
                    $url_match = true;
                    break;
                }
            }
            
            if (!$url_match) {
                return false;
            }
        }
        
        // Check post types
        if (!empty($message['display_conditions']['post_types'])) {
            $post_type = get_post_type();
            
            if (!in_array($post_type, $message['display_conditions']['post_types'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Add a message to the queue with priority
     * 
     * @param array $message The message to queue
     * @param int $priority The message priority (higher = more important)
     */
    public function queue_message($message, $priority = 10) {
        $this->message_queue[] = array(
            'message' => $message,
            'priority' => $priority
        );
    }
    
    /**
     * Process the message queue and display the highest priority message
     */
    private function process_message_queue() {
        // If a message is already displayed or queue is empty, don't process
        if ($this->message_displayed || empty($this->message_queue)) {
            return;
        }
        
        // Sort the queue by priority (highest first)
        usort($this->message_queue, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        // Get the highest priority message
        $top_message = $this->message_queue[0]['message'];
        
        // Process any placeholders in the content
        $content = isset($top_message['content']) ? $top_message['content'] : '';
        $content = $this->process_placeholders($content);
        
        // Generate a unique ID for this message
        $message_id = isset($top_message['id']) ? $top_message['id'] : 'msg-' . time();
        
        // Check if this message has already been shown this session
        if (isset($_SESSION[$this->session_key][$message_id])) {
            return;
        }
        
        // Build the shortcode
        $shortcode = sprintf(
            '[lilac_message type="%s" title="%s" auto_close="%s" message_id="%s"]%s[/lilac_message]',
            esc_attr($top_message['type']),
            esc_attr($top_message['title']),
            esc_attr($top_message['auto_close']),
            esc_attr($message_id),
            wp_kses_post($content)
        );
        
        // Output the message
        echo do_shortcode($shortcode);
        
        // Mark as displayed
        $this->message_displayed = true;
        
        // Mark as shown in this session if needed
        if (isset($top_message['display_conditions']['once_per_session']) && 
            $top_message['display_conditions']['once_per_session']) {
            $_SESSION[$this->session_key][$message_id] = true;
        }
    }

    /**
     * Check if we should display welcome message based on settings
     */
    private function maybe_display_welcome_message($settings) {
        // Skip if a message is already being displayed
        if ($this->message_displayed) {
            return;
        }
        
        // Skip if no content
        if (empty($settings['welcome_message_content'])) {
            return;
        }
        
        // Check if we should show once per session
        if (isset($settings['welcome_display_once_per_session']) && 
            $settings['welcome_display_once_per_session'] && 
            isset($_SESSION[$this->session_key]['welcome'])) {
            return;
        }
        
        // Check if homepage only
        if (isset($settings['welcome_display_homepage']) && 
            $settings['welcome_display_homepage'] && 
            !is_front_page()) {
            return;
        }
        
        // Check specific URLs
        if (!empty($settings['welcome_specific_urls'])) {
            $urls = array_map('trim', explode("\n", $settings['welcome_specific_urls']));
            $current_url = rtrim($_SERVER['REQUEST_URI'], '/');
            
            $url_match = false;
            foreach ($urls as $url) {
                if (rtrim($url, '/') === $current_url) {
                    $url_match = true;
                    break;
                }
            }
            
            if (!$url_match) {
                return;
            }
        }
        
        // Instead of displaying directly, add to queue with medium priority
        $welcome_message = array(
            'id' => 'welcome',
            'title' => isset($settings['welcome_message_title']) ? $settings['welcome_message_title'] : '',
            'content' => isset($settings['welcome_message_content']) ? $settings['welcome_message_content'] : '',
            'type' => isset($settings['welcome_message_type']) ? $settings['welcome_message_type'] : 'info',
            'auto_close' => isset($settings['welcome_message_auto_close']) ? $settings['welcome_message_auto_close'] : 0,
            'display_conditions' => array(
                'once_per_session' => isset($settings['welcome_display_once_per_session']) && $settings['welcome_display_once_per_session']
            )
        );
        
        $this->queue_message($welcome_message, 20); // Medium priority for welcome message
        
        // Session tracking happens in process_message_queue now
    }

    /**
     * Track the current page visit for contextual messages
     */
    public function track_page_visit() {
        if (!is_admin()) {
            // Get the current post type
            $post_type = get_post_type();
            
            // Store in session for later use
            if (!isset($_SESSION['lilac_page_views'])) {
                $_SESSION['lilac_page_views'] = array();
            }
            
            // Increment view count for this post type
            if (!isset($_SESSION['lilac_page_views'][$post_type])) {
                $_SESSION['lilac_page_views'][$post_type] = 0;
            }
            
            $_SESSION['lilac_page_views'][$post_type]++;
            
            // Store current URL
            $_SESSION['lilac_current_url'] = $_SERVER['REQUEST_URI'];
            
            // Store time of visit
            $_SESSION['lilac_last_visit'] = time();
        }
    }

    /**
     * AJAX handler for dismissing messages
     */
    public function ajax_dismiss_message() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lilac_toast_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get message ID
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (!empty($message_id)) {
            // Mark as dismissed in session
            $_SESSION[$this->session_key][$message_id] = true;
            
            wp_send_json_success();
        }
        
        wp_send_json_error('Missing message ID');
    }

    /**
     * Show a contextual message based on post type or other criteria
     * 
     * @param array $args Message arguments
     * @return bool Whether the message was shown
     */
    public function show_contextual_message($args = array()) {
        // If a message is already being displayed, queue this for next page load
        if ($this->message_displayed) {
            return false;
        }
        
        $defaults = array(
            'type' => 'info',
            'title' => '',
            'content' => '',
            'auto_close' => 0,
            'post_type' => '',
            'url' => '',
            'message_id' => 'ctx-' . time(),
            'priority' => 10
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Skip if no content
        if (empty($args['content'])) {
            return false;
        }
        
        // Check post type if specified
        if (!empty($args['post_type']) && get_post_type() !== $args['post_type']) {
            return false;
        }
        
        // Check URL if specified
        if (!empty($args['url']) && $_SERVER['REQUEST_URI'] !== $args['url']) {
            return false;
        }
        
        // Check if already shown this session
        if (isset($_SESSION[$this->session_key][$args['message_id']])) {
            return false;
        }
        
        // Add to message queue instead of displaying directly
        $message = array(
            'id' => $args['message_id'],
            'title' => $args['title'],
            'content' => $args['content'],
            'type' => $args['type'],
            'auto_close' => $args['auto_close'],
            'display_conditions' => array(
                'once_per_session' => true,
                'post_type' => $args['post_type'],
                'specific_urls' => $args['url']
            )
        );
        
        $this->queue_message($message, $args['priority']);
        
        // Process the queue immediately
        $this->process_message_queue();
    }

    /**
     * Get page view count for a specific post type
     * 
     * @param string $post_type Post type to check
     * @return int View count
     */
    public function get_post_type_views($post_type) {
        if (isset($_SESSION['lilac_page_views'][$post_type])) {
            return (int) $_SESSION['lilac_page_views'][$post_type];
        }
        
        return 0;
    }

    /**
     * Get time spent on site in seconds
     * 
     * @return int Time in seconds
     */
    public function get_time_on_site() {
        if (!isset($_SESSION['lilac_first_visit'])) {
            $_SESSION['lilac_first_visit'] = time();
            return 0;
        }
        
        return time() - $_SESSION['lilac_first_visit'];
    }

    /**
     * Process placeholders in message content
     * 
     * @param string $content The content to process
     * @return string The processed content
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
        
        // Get session time
        $session_time = $this->get_time_on_site();
        $formatted_time = '';
        if ($session_time > 0) {
            $minutes = floor($session_time / 60);
            $seconds = $session_time % 60;
            $formatted_time = sprintf('%d:%02d', $minutes, $seconds);
        }
        
        // Get course progress (placeholder for now)
        $course_progress = '0%';
        if (class_exists('Lilac\\Messaging\\CourseProgress')) {
            // Get the current course ID if on a course page
            $course_id = 0;
            if (function_exists('learndash_get_course_id')) {
                $course_id = learndash_get_course_id();
            }
            
            if ($course_id && function_exists('learndash_course_progress')) {
                $progress = learndash_course_progress(array('user_id' => $user->ID, 'course_id' => $course_id));
                if (isset($progress['percentage'])) {
                    $course_progress = $progress['percentage'] . '%';
                }
            }
        }
        
        // Replace placeholders
        $replacements = array(
            '{user_name}' => $user_name,
            '{visit_count}' => $visit_count,
            '{session_time}' => $formatted_time,
            '{course_progress}' => $course_progress
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}

// Initialize the class
function lilac_site_message() {
    return SiteMessage::get_instance();
}
lilac_site_message();
