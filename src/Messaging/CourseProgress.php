<?php
/**
 * Course Progress System
 *
 * Provides functionality for tracking and displaying user progress through courses.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging
 */

namespace Lilac\Messaging;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CourseProgress {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Session keys
     */
    private $course_view_key = 'lilac_course_views';
    private $course_progress_key = 'lilac_course_progress';
    private $last_activity_key = 'lilac_last_activity';

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
        add_shortcode('lilac_course_progress', array($this, 'course_progress_shortcode'));
        add_shortcode('lilac_course_views', array($this, 'course_views_shortcode'));
        add_shortcode('lilac_activity_timer', array($this, 'activity_timer_shortcode'));
        
        // Track page views in courses
        add_action('template_redirect', array($this, 'track_course_visit'));
        
        // AJAX handler for updating progress
        add_action('wp_ajax_update_course_progress', array($this, 'update_course_progress'));
        add_action('wp_ajax_nopriv_update_course_progress', array($this, 'update_course_progress'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Initialize session if not already started
     */
    public function init_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
            
            // Initialize course tracking arrays if they don't exist
            if (!isset($_SESSION[$this->course_view_key])) {
                $_SESSION[$this->course_view_key] = array();
            }
            
            if (!isset($_SESSION[$this->course_progress_key])) {
                $_SESSION[$this->course_progress_key] = array();
            }
            
            // Set or update last activity time
            $_SESSION[$this->last_activity_key] = time();
        }
    }

    /**
     * Enqueue necessary assets
     */
    public function enqueue_assets() {
        $dir_url = get_stylesheet_directory_uri() . '/src/Messaging/assets';
        
        // Register and enqueue the CSS
        wp_register_style(
            'lilac-progress', 
            $dir_url . '/css/progress.css',
            array(),
            filemtime(get_stylesheet_directory() . '/src/Messaging/assets/css/progress.css')
        );
        wp_enqueue_style('lilac-progress');
        
        // Register and enqueue the JS
        wp_register_script(
            'lilac-progress',
            $dir_url . '/js/progress.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/src/Messaging/assets/js/progress.js'),
            true
        );
        wp_enqueue_script('lilac-progress');
        
        // Pass data to JavaScript
        wp_localize_script('lilac-progress', 'lilacProgressData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'courseViews' => $this->get_course_views(),
            'courseProgress' => $this->get_course_progress(),
            'lastActivity' => isset($_SESSION[$this->last_activity_key]) ? $_SESSION[$this->last_activity_key] : 0,
            'nonce' => wp_create_nonce('lilac_progress_nonce')
        ));
    }

    /**
     * Track course visits
     */
    public function track_course_visit() {
        // Check if we're on a course page (using LearnDash's function if available)
        $is_course = false;
        global $post;
        
        if (function_exists('learndash_is_course_post')) {
            $is_course = learndash_is_course_post($post);
        } else {
            // Fallback - check if we're on a page with certain post types
            if ($post && in_array($post->post_type, array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz'))) {
                $is_course = true;
            }
        }
        
        if ($is_course) {
            global $post;
            $post_id = $post->ID;
            
            // Get course ID if we're viewing a lesson/topic/quiz
            $course_id = 0;
            if (function_exists('learndash_get_course_id')) {
                $course_id = learndash_get_course_id($post_id);
            }
            
            // If no course ID found but it's a course, use the post ID
            if (empty($course_id) && $post->post_type === 'sfwd-courses') {
                $course_id = $post_id;
            }
            
            // Add view count if we have a valid course ID
            if (!empty($course_id)) {
                $this->increment_course_views($course_id, $post_id);
            }
        }
    }

    /**
     * Increment course views
     */
    public function increment_course_views($course_id, $post_id) {
        if (!isset($_SESSION[$this->course_view_key][$course_id])) {
            $_SESSION[$this->course_view_key][$course_id] = array(
                'total' => 0,
                'items' => array()
            );
        }
        
        $_SESSION[$this->course_view_key][$course_id]['total']++;
        
        if (!isset($_SESSION[$this->course_view_key][$course_id]['items'][$post_id])) {
            $_SESSION[$this->course_view_key][$course_id]['items'][$post_id] = 1;
        } else {
            $_SESSION[$this->course_view_key][$course_id]['items'][$post_id]++;
        }
        
        // Update last activity time
        $_SESSION[$this->last_activity_key] = time();
    }

    /**
     * AJAX handler for updating course progress
     */
    public function update_course_progress() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lilac_progress_nonce')) {
            wp_send_json_error('Invalid security token.');
            exit;
        }
        
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $progress = isset($_POST['progress']) ? floatval($_POST['progress']) : 0;
        
        if (empty($course_id)) {
            wp_send_json_error('No course ID provided.');
            exit;
        }
        
        // Update progress
        $_SESSION[$this->course_progress_key][$course_id] = $progress;
        
        // Update last activity time
        $_SESSION[$this->last_activity_key] = time();
        
        wp_send_json_success(array(
            'message' => 'Progress updated successfully.',
            'progress' => $progress
        ));
        exit;
    }

    /**
     * Get all course view data
     */
    public function get_course_views() {
        return isset($_SESSION[$this->course_view_key]) ? $_SESSION[$this->course_view_key] : array();
    }

    /**
     * Get views for a specific course
     */
    public function get_course_view_count($course_id) {
        if (isset($_SESSION[$this->course_view_key][$course_id])) {
            return $_SESSION[$this->course_view_key][$course_id]['total'];
        }
        return 0;
    }

    /**
     * Get all course progress data
     */
    public function get_course_progress() {
        return isset($_SESSION[$this->course_progress_key]) ? $_SESSION[$this->course_progress_key] : array();
    }

    /**
     * Get progress for a specific course
     */
    public function get_course_progress_value($course_id) {
        if (isset($_SESSION[$this->course_progress_key][$course_id])) {
            return $_SESSION[$this->course_progress_key][$course_id];
        }
        return 0;
    }

    /**
     * Get time since last activity
     */
    public function get_time_since_last_activity() {
        if (isset($_SESSION[$this->last_activity_key])) {
            return time() - $_SESSION[$this->last_activity_key];
        }
        return 0;
    }

    /**
     * Course progress shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function course_progress_shortcode($atts = []) {
        // Extract and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'course_id' => 0,
            'label' => __('Course Progress:', 'hello-child'),
            'class' => '',
            'show_percentage' => 'true',
            'show_bar' => 'true',
            'bar_color' => '#3498db',
            'hide_if_zero' => 'false',
        ), $atts, 'lilac_course_progress');
        
        $course_id = absint($atts['course_id']);
        $label = sanitize_text_field($atts['label']);
        $class = sanitize_text_field($atts['class']);
        $show_percentage = filter_var($atts['show_percentage'], FILTER_VALIDATE_BOOLEAN);
        $show_bar = filter_var($atts['show_bar'], FILTER_VALIDATE_BOOLEAN);
        $bar_color = sanitize_hex_color($atts['bar_color']);
        $hide_if_zero = filter_var($atts['hide_if_zero'], FILTER_VALIDATE_BOOLEAN);
        
        // Get current course ID if not specified
        if (empty($course_id)) {
            global $post;
            if (function_exists('learndash_get_course_id') && $post) {
                $course_id = learndash_get_course_id($post->ID);
            }
            
            if (empty($course_id) && $post && $post->post_type === 'sfwd-courses') {
                $course_id = $post->ID;
            }
        }
        
        if (empty($course_id)) {
            return '<div class="lilac-error" style="color:red;">Course ID is required for progress tracking.</div>';
        }
        
        // Get progress for the specified course
        $progress = $this->get_course_progress_value($course_id);
        
        // Hide if zero and hide_if_zero is true
        if ($hide_if_zero && $progress <= 0) {
            return '';
        }
        
        // Build the progress display HTML
        $progress_percent = intval($progress);
        
        $html = '<div class="lilac-course-progress ' . esc_attr($class) . '" data-course-id="' . esc_attr($course_id) . '">';
        
        if (!empty($label)) {
            $html .= '<div class="lilac-course-progress-label">' . esc_html($label) . '</div>';
        }
        
        if ($show_bar) {
            $html .= '<div class="lilac-course-progress-bar-container">';
            $html .= '<div class="lilac-course-progress-bar" style="width:' . esc_attr($progress_percent) . '%; background-color:' . esc_attr($bar_color) . '"></div>';
            $html .= '</div>';
        }
        
        if ($show_percentage) {
            $html .= '<div class="lilac-course-progress-percentage">' . esc_html($progress_percent) . '%</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Course views shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function course_views_shortcode($atts = []) {
        // Extract and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'course_id' => 0,
            'label' => __('Pages Viewed:', 'hello-child'),
            'class' => '',
            'icon' => 'true',
        ), $atts, 'lilac_course_views');
        
        $course_id = absint($atts['course_id']);
        $label = sanitize_text_field($atts['label']);
        $class = sanitize_text_field($atts['class']);
        $show_icon = filter_var($atts['icon'], FILTER_VALIDATE_BOOLEAN);
        
        // Get current course ID if not specified
        if (empty($course_id)) {
            global $post;
            if (function_exists('learndash_get_course_id') && $post) {
                $course_id = learndash_get_course_id($post->ID);
            }
            
            if (empty($course_id) && $post && $post->post_type === 'sfwd-courses') {
                $course_id = $post->ID;
            }
        }
        
        if (empty($course_id)) {
            return '<div class="lilac-error" style="color:red;">Course ID is required for view tracking.</div>';
        }
        
        // Get view count for the specified course
        $views = $this->get_course_view_count($course_id);
        
        // Build the views display HTML
        $html = '<div class="lilac-course-views ' . esc_attr($class) . '" data-course-id="' . esc_attr($course_id) . '">';
        
        if ($show_icon) {
            $html .= '<span class="lilac-course-views-icon">👁️</span> ';
        }
        
        if (!empty($label)) {
            $html .= '<span class="lilac-course-views-label">' . esc_html($label) . '</span> ';
        }
        
        $html .= '<span class="lilac-course-views-count">' . esc_html($views) . '</span>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Activity timer shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function activity_timer_shortcode($atts = []) {
        // Extract and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'label' => __('Time Active:', 'hello-child'),
            'class' => '',
            'format' => 'minutes', // seconds, minutes, hours, or formatted
            'live' => 'true',
        ), $atts, 'lilac_activity_timer');
        
        $label = sanitize_text_field($atts['label']);
        $class = sanitize_text_field($atts['class']);
        $format = sanitize_text_field($atts['format']);
        $live = filter_var($atts['live'], FILTER_VALIDATE_BOOLEAN);
        
        // Get time since last activity
        $seconds = $this->get_time_since_last_activity();
        
        // Format the time according to the requested format
        $formatted_time = '';
        switch ($format) {
            case 'seconds':
                $formatted_time = $seconds . ' ' . __('sec', 'hello-child');
                break;
            case 'minutes':
                $formatted_time = floor($seconds / 60) . ' ' . __('min', 'hello-child');
                break;
            case 'hours':
                $formatted_time = floor($seconds / 3600) . ' ' . __('hr', 'hello-child');
                break;
            case 'formatted':
            default:
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $secs = $seconds % 60;
                
                if ($hours > 0) {
                    $formatted_time = sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
                } else {
                    $formatted_time = sprintf('%d:%02d', $minutes, $secs);
                }
                break;
        }
        
        // Build the timer display HTML
        $html = '<div class="lilac-activity-timer ' . esc_attr($class) . '"' . ($live ? ' data-live="true" data-format="' . esc_attr($format) . '"' : '') . '>';
        
        if (!empty($label)) {
            $html .= '<span class="lilac-activity-timer-label">' . esc_html($label) . '</span> ';
        }
        
        $html .= '<span class="lilac-activity-timer-value">' . esc_html($formatted_time) . '</span>';
        $html .= '</div>';
        
        return $html;
    }
}

// Initialize the class
function lilac_course_progress() {
    return CourseProgress::get_instance();
}
lilac_course_progress();
