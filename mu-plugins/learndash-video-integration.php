<?php
/**
 * LearnDash Video Integration for Custom LD30 Templates
 * 
 * This plugin ensures proper video functionality when using custom LD30 templates
 * by enabling video processing and ensuring assets are loaded correctly.
 * 
 * @package LearnDash_Video_Integration
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class LearnDash_Video_Integration {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_video_assets'));
        add_filter('learndash_template_filename', array($this, 'ensure_video_processing'), 10, 4);
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_video_info'));
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Ensure video processing is enabled
        if (!defined('LEARNDASH_LESSON_VIDEO')) {
            define('LEARNDASH_LESSON_VIDEO', true);
        }
        
        // Log initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('LearnDash Video Integration: Initialized with LEARNDASH_LESSON_VIDEO = ' . (LEARNDASH_LESSON_VIDEO ? 'true' : 'false'));
        }
    }
    
    /**
     * Enqueue video assets when needed
     */
    public function enqueue_video_assets() {
        // Only on LearnDash lesson and topic pages
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        global $post;
        
        // Check if this lesson/topic has video
        $video_url = '';
        if ($post->post_type === 'sfwd-lessons') {
            $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
        } elseif ($post->post_type === 'sfwd-topic') {
            $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
        }
        
        if (!empty($video_url)) {
            // Enqueue LearnDash video CSS
            $video_css_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash_lesson_video' . learndash_min_asset() . '.css';
            wp_enqueue_style('learndash_lesson_video_css', $video_css_url, array(), LEARNDASH_VERSION);
            
            // Enqueue LearnDash video JS
            $video_js_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash_video_script' . learndash_min_asset() . '.js';
            wp_enqueue_script('learndash_video_script_js', $video_js_url, array('jquery'), LEARNDASH_VERSION, true);
            
            // Add video script data
            $video_script_data = array(
                'video_debug' => (defined('LEARNDASH_SCRIPT_DEBUG') && LEARNDASH_SCRIPT_DEBUG) ? '1' : '0',
                'video_auto_start' => learndash_get_setting($post->ID, 'lesson_video_auto_start') ? '1' : '0',
                'video_show_controls' => learndash_get_setting($post->ID, 'lesson_video_show_controls') ? '1' : '0',
                'video_focus_pause' => learndash_get_setting($post->ID, 'lesson_video_focus_pause') ? '1' : '0',
                'video_track_time' => learndash_get_setting($post->ID, 'lesson_video_track_time') ? '1' : '0',
                'video_resume' => '1', // Always enable resume
            );
            
            wp_localize_script('learndash_video_script_js', 'learndash_video_data', $video_script_data);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LearnDash Video Integration: Enqueued video assets for post ' . $post->ID . ' with video URL: ' . $video_url);
            }
        }
    }
    
    /**
     * Ensure video processing happens even with custom templates
     */
    public function ensure_video_processing($template_filename, $name, $args, $echo) {
        // Only for lesson and topic templates
        if (!in_array($name, array('lesson', 'topic'))) {
            return $template_filename;
        }
        
        // If we're using a custom template, make sure video processing still happens
        if (strpos($template_filename, 'themes/') !== false) {
            // This is a theme template, ensure video processing is enabled
            if (!defined('LEARNDASH_LESSON_VIDEO')) {
                define('LEARNDASH_LESSON_VIDEO', true);
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('LearnDash Video Integration: Using custom template ' . $template_filename . ' for ' . $name);
            }
        }
        
        return $template_filename;
    }
    
    /**
     * Debug information in footer
     */
    public function debug_video_info() {
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        global $post;
        
        $video_url = '';
        $video_settings = array();
        
        if ($post->post_type === 'sfwd-lessons') {
            $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
            $video_settings = array(
                'auto_start' => learndash_get_setting($post->ID, 'lesson_video_auto_start'),
                'show_controls' => learndash_get_setting($post->ID, 'lesson_video_show_controls'),
                'focus_pause' => learndash_get_setting($post->ID, 'lesson_video_focus_pause'),
                'track_time' => learndash_get_setting($post->ID, 'lesson_video_track_time'),
            );
        } elseif ($post->post_type === 'sfwd-topic') {
            $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
            $video_settings = array(
                'auto_start' => learndash_get_setting($post->ID, 'lesson_video_auto_start'),
                'show_controls' => learndash_get_setting($post->ID, 'lesson_video_show_controls'),
                'focus_pause' => learndash_get_setting($post->ID, 'lesson_video_focus_pause'),
                'track_time' => learndash_get_setting($post->ID, 'lesson_video_track_time'),
            );
        }
        
        // Check if video class is available
        $video_class_available = class_exists('Learndash_Course_Video');
        $video_constant_defined = defined('LEARNDASH_LESSON_VIDEO') && LEARNDASH_LESSON_VIDEO;
        
        ?>
        <!-- LearnDash Video Integration Debug Info -->
        <div id="learndash-video-debug" style="display: none;">
            <h4>LearnDash Video Debug Info</h4>
            <p><strong>Post ID:</strong> <?php echo $post->ID; ?></p>
            <p><strong>Post Type:</strong> <?php echo $post->post_type; ?></p>
            <p><strong>Video URL:</strong> <?php echo esc_html($video_url ?: 'None'); ?></p>
            <p><strong>Video Class Available:</strong> <?php echo $video_class_available ? 'Yes' : 'No'; ?></p>
            <p><strong>Video Constant Defined:</strong> <?php echo $video_constant_defined ? 'Yes' : 'No'; ?></p>
            <p><strong>Video Settings:</strong></p>
            <ul>
                <?php foreach ($video_settings as $key => $value) : ?>
                    <li><?php echo esc_html($key); ?>: <?php echo $value ? 'Yes' : 'No'; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <script>
        // Log debug info to console
        console.group('LearnDash Video Integration Debug');
        console.log('Post ID:', <?php echo $post->ID; ?>);
        console.log('Post Type:', '<?php echo $post->post_type; ?>');
        console.log('Video URL:', '<?php echo esc_js($video_url); ?>');
        console.log('Video Class Available:', <?php echo $video_class_available ? 'true' : 'false'; ?>);
        console.log('Video Constant Defined:', <?php echo $video_constant_defined ? 'true' : 'false'; ?>);
        console.log('Video Settings:', <?php echo json_encode($video_settings); ?>);
        
        // Check for video elements in DOM
        const videoElements = document.querySelectorAll('.ld-video, [data-video-progression]');
        console.log('Video Elements Found:', videoElements.length);
        videoElements.forEach((el, index) => {
            console.log(`Video Element ${index + 1}:`, el);
        });
        
        console.groupEnd();
        </script>
        <?php
    }
}

// Initialize the plugin
new LearnDash_Video_Integration();

/**
 * Helper function to check if video is enabled for a post
 */
function learndash_video_integration_has_video($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    }
    
    $video_url = learndash_get_setting($post_id, 'lesson_video_url');
    return !empty($video_url);
}

/**
 * Helper function to get video HTML for a post
 */
function learndash_video_integration_get_video_html($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    }
    
    if (!class_exists('Learndash_Course_Video')) {
        return '';
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        return '';
    }
    
    $course_id = learndash_get_course_id($post_id);
    $settings = learndash_get_setting($post_id);
    
    $video_instance = Learndash_Course_Video::get_instance();
    $video_content = $video_instance->add_video_to_content('', $post_obj, $settings);
    
    return $video_content;
}
