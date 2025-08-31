<?php
/**
 * Elementor LearnDash Video Integration
 * 
 * Adds video support to Elementor's LearnDash widgets by hooking into
 * the widget rendering process and injecting video content.
 * 
 * @package Elementor_LearnDash_Video_Integration
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elementor_LearnDash_Video_Integration {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Hook into Elementor widget rendering
        add_action('elementor/widget/render_content', array($this, 'inject_video_content'), 10, 2);
        
        // Ensure video assets are loaded on Elementor pages
        add_action('wp_enqueue_scripts', array($this, 'enqueue_video_assets'));
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_elementor_info'));
        }
    }
    
    public function init() {
        // Ensure video processing is enabled
        if (!defined('LEARNDASH_LESSON_VIDEO')) {
            define('LEARNDASH_LESSON_VIDEO', true);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Elementor LearnDash Video Integration: Initialized with layout fixes');
        }
    }
    
    /**
     * Inject video content into Elementor LearnDash widgets
     */
    public function inject_video_content($content, $widget) {
        // Only process LearnDash course content widgets
        if ($widget->get_name() !== 'ld-course-content') {
            return $content;
        }
        
        // Only on lesson and topic pages
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return $content;
        }
        
        global $post;
        
        // Get video content for this lesson/topic
        $video_content = $this->get_video_content($post);
        
        if (empty($video_content)) {
            return $content;
        }
        
        // Inject video content at the beginning of the widget
        $video_html = '<div class="elementor-learndash-video-container">' . $video_content . '</div>';
        
        // Insert video content after the learndash-wrapper opening tag
        $content = str_replace(
            '<div class="learndash-wrapper',
            $video_html . '<div class="learndash-wrapper',
            $content
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Elementor LearnDash Video Integration: Injected video content for post ' . $post->ID);
        }
        
        return $content;
    }
    
    /**
     * Get video content for a lesson/topic
     */
    private function get_video_content($post) {
        if (!class_exists('Learndash_Course_Video')) {
            return '';
        }
        
        // Check if this post has video
        $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
        if (empty($video_url)) {
            return '';
        }
        
        // Get video content using LearnDash's video processing
        $course_id = learndash_get_course_id($post->ID);
        $settings = learndash_get_setting($post->ID);
        
        $video_instance = Learndash_Course_Video::get_instance();
        $video_content = $video_instance->add_video_to_content('', $post, $settings);
        
        return $video_content;
    }
    
    /**
     * Enqueue video assets on Elementor pages
     */
    public function enqueue_video_assets() {
        // Only on LearnDash pages
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        global $post;
        
        // Always enqueue layout fixes for Elementor LearnDash pages
        $this->enqueue_layout_fixes();
        
        // Check if video exists and enqueue video assets
        $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
        if (!empty($video_url)) {
            // Enqueue LearnDash video assets
            $this->enqueue_learndash_video_assets($post);
        }
    }
    
    /**
     * Enqueue layout fixes CSS
     */
    private function enqueue_layout_fixes() {
        // Create a unique handle for our CSS
        wp_register_style('elementor-learndash-fixes', false);
        wp_enqueue_style('elementor-learndash-fixes');
        wp_add_inline_style('elementor-learndash-fixes', $this->get_elementor_video_css());
    }
    
    /**
     * Check if this is an Elementor page with LearnDash content
     */
    private function is_elementor_learndash_page() {
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return false;
        }
        
        // Check if Elementor is active and this page uses Elementor
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }
        
        global $post;
        return \Elementor\Plugin::$instance->documents->get($post->ID)->is_built_with_elementor();
    }
    
    /**
     * Enqueue LearnDash video assets
     */
    private function enqueue_learndash_video_assets($post) {
        // Video CSS
        $video_css_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash_lesson_video' . learndash_min_asset() . '.css';
        wp_enqueue_style('learndash_lesson_video_css', $video_css_url, array(), LEARNDASH_VERSION);
        
        // Video JS
        $video_js_url = LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash_video_script' . learndash_min_asset() . '.js';
        wp_enqueue_script('learndash_video_script_js', $video_js_url, array('jquery'), LEARNDASH_VERSION, true);
        
        // Video script data
        $video_script_data = array(
            'video_debug' => (defined('LEARNDASH_SCRIPT_DEBUG') && LEARNDASH_SCRIPT_DEBUG) ? '1' : '0',
            'video_auto_start' => learndash_get_setting($post->ID, 'lesson_video_auto_start') ? '1' : '0',
            'video_show_controls' => learndash_get_setting($post->ID, 'lesson_video_show_controls') ? '1' : '0',
            'video_focus_pause' => learndash_get_setting($post->ID, 'lesson_video_focus_pause') ? '1' : '0',
            'video_track_time' => learndash_get_setting($post->ID, 'lesson_video_track_time') ? '1' : '0',
            'video_resume' => '1',
        );
        
        wp_localize_script('learndash_video_script_js', 'learndash_video_data', $video_script_data);
    }
    
    /**
     * Get custom CSS for Elementor video integration and layout fixes
     */
    private function get_elementor_video_css() {
        return '
        /* Video Integration Styles */
        .elementor-learndash-video-container {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .elementor-learndash-video-container .ld-video {
            margin: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .elementor-learndash-video-container .ld-video iframe {
            width: 100%;
            height: auto;
            aspect-ratio: 16/9;
            border: none;
        }
        
        .elementor-learndash-video-container .ld-video-container {
            position: relative;
        }
        
        /* Video progression overlay */
        .elementor-learndash-video-container .ld-video-progression {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* COMPACT TOPIC LIST LAYOUT FIXES - HIGH SPECIFICITY */
        
        /* Fix video spacing - CRITICAL */
        body.learndash-cpt .ld-video {
            padding-bottom: 0 !important;
            margin-bottom: 10px !important;
        }
        
        body.learndash-cpt .ld-video iframe {
            height: 315px !important;
            width: 100% !important;
        }
        
        /* TARGET THE ACTUAL TOPIC GRID STRUCTURE */
        
        /* Compact the main topic container */
        body.learndash-cpt .llm-early-topics {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Compact each topic item - MAIN FIX */
        body.learndash-cpt .llm-topic-item {
            margin-bottom: 5px !important;
            padding: 8px 12px !important;
            border: 1px solid #eee !important;
            border-radius: 4px !important;
            background: #fff !important;
            transition: background-color 0.2s ease !important;
        }
        
        body.learndash-cpt .llm-topic-item:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Compact topic titles */
        body.learndash-cpt .llm-topic-item h4 {
            margin: 0 0 4px 0 !important;
            padding: 0 !important;
            font-size: 14px !important;
            line-height: 1.3 !important;
        }
        
        body.learndash-cpt .llm-topic-item h4 a {
            color: #333 !important;
            text-decoration: none !important;
            font-weight: 500 !important;
        }
        
        body.learndash-cpt .llm-topic-item h4 a:hover {
            color: #0073aa !important;
        }
        
        /* Compact lesson title */
        body.learndash-cpt .llm-lesson-title {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 12px !important;
            color: #666 !important;
            line-height: 1.2 !important;
        }
        
        /* Remove excessive spacing from shortcode container */
        body.learndash-cpt .elementor-shortcode {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* MAIN FIX: Standard LearnDash Topic Table List */
        
        /* Container fixes */

        .ld-video iframe, .learndash-video iframe, .wp-video {
        width: 100% !important;
        height: 450px !important;
        min-width: 100%;
        min-height: 450px;
        }


 
    body.learndash-cpt .ld-table-list-items {
            margin: 0 !important;
            padding: 0 !important;
            grid-template-columns: none !important;
        }
        
        /* Individual topic items - CRITICAL */
        body.learndash-cpt .ld-table-list-item {
            margin-bottom: 2px !important;
            border: 1px solid #eee !important;
            border-radius: 4px !important;
            background: #fff !important;
            overflow: hidden !important;
        }
        
        body.learndash-cpt .ld-table-list-item:last-child {
            margin-bottom: 0 !important;
        }
        
        /* Topic row styling - MAIN FIX */
        body.learndash-cpt .ld-table-list-item-preview {
            display: flex !important;
            align-items: center !important;
            padding: 10px 15px !important;
            min-height: auto !important;
            text-decoration: none !important;
            transition: background-color 0.2s ease !important;
            border: none !important;
        }
        
        body.learndash-cpt .ld-table-list-item-preview:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Status icon positioning */
        body.learndash-cpt .ld-table-list-item-preview .ld-status-icon {
            margin-left: 0 !important;
            margin-right: 12px !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            height: 20px !important;
        }
        
        /* Topic title styling */
        body.learndash-cpt .ld-table-list-item-preview .ld-topic-title {
            flex: 1 !important;
            line-height: 1.4 !important;
            font-size: 14px !important;
            color: #333 !important;
            margin: 0 !important;
            padding: 0 !important;
            font-weight: 500 !important;
        }
        
        body.learndash-cpt .ld-table-list-item-preview:hover .ld-topic-title {
            color: #0073aa !important;
        }
        
        /* RTL Support for Hebrew */
        body.rtl.learndash-cpt .ld-table-list-item-preview .ld-status-icon {
            margin-left: 12px !important;
            margin-right: 0 !important;
        }
        
        body.rtl.learndash-cpt .ld-table-list-item-preview .ld-topic-title {
            text-align: right !important;
            direction: rtl !important;
        }
        
        /* Remove extra spacing from table list */
        body.learndash-cpt .ld-table-list {
            border-radius: 6px !important;
            overflow: hidden !important;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
            margin-bottom: 15px !important;
        }
        
        body.learndash-cpt .ld-table-list-header {
            padding: 12px 15px !important;
            margin-bottom: 0 !important;
        }
        
        body.learndash-cpt .ld-table-list-items {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        body.learndash-cpt .ld-table-list-footer {
            padding: 0 !important;
            margin: 0 !important;
            display: none !important;
        }
        
        /* Content actions spacing */
        body.learndash-cpt .ld-content-actions {
            margin-top: 15px !important;
            padding-top: 15px !important;
            border-top: 1px solid #eee !important;
        }
        
        /* RTL Support for Hebrew */
        body.rtl.learndash-cpt .ld-table-list-item-preview .ld-status-icon {
            margin-left: 10px !important;
            margin-right: 0 !important;
        }
        
        body.rtl.learndash-cpt .ld-table-list-item-preview .ld-topic-title {
            text-align: right !important;
            direction: rtl !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            body.learndash-cpt .ld-video {
                margin-bottom: 8px !important;
            }
            
            body.learndash-cpt .llm-topic-item {
                padding: 6px 10px !important;
                margin-bottom: 3px !important;
            }
            
            body.learndash-cpt .llm-topic-item h4 {
                font-size: 13px !important;
            }
            
            body.learndash-cpt .llm-lesson-title {
                font-size: 11px !important;
            }
        }
        
        /* Hover effects */
        body.learndash-cpt .ld-table-list-item-preview:hover .ld-topic-title {
            color: #0073aa !important;
        }
        
        body.learndash-cpt .ld-table-list-item-preview:focus {
            outline: 2px solid #0073aa !important;
            outline-offset: -2px !important;
        }
        ';
    }
    
    /**
     * Debug information for Elementor integration
     */
    public function debug_elementor_info() {
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        global $post;
        
        $is_elementor_page = $this->is_elementor_learndash_page();
        $video_url = learndash_get_setting($post->ID, 'lesson_video_url');
        $has_video = !empty($video_url);
        
        ?>
        <script>
        console.group('Elementor LearnDash Video Integration Debug');
        console.log('Post ID:', <?php echo $post->ID; ?>);
        console.log('Post Type:', '<?php echo $post->post_type; ?>');
        console.log('Is Elementor Page:', <?php echo $is_elementor_page ? 'true' : 'false'; ?>);
        console.log('Has Video URL:', <?php echo $has_video ? 'true' : 'false'; ?>);
        console.log('Video URL:', '<?php echo esc_js($video_url); ?>');
        
        // Check for Elementor LearnDash widgets
        const elementorWidgets = document.querySelectorAll('[data-widget_type*="ld-"]');
        console.log('Elementor LearnDash Widgets Found:', elementorWidgets.length);
        elementorWidgets.forEach((widget, index) => {
            console.log(`Widget ${index + 1}:`, widget.getAttribute('data-widget_type'));
        });
        
        // Check for video containers
        const videoContainers = document.querySelectorAll('.elementor-learndash-video-container');
        console.log('Video Containers Found:', videoContainers.length);
        
        console.groupEnd();
        </script>
        <?php
    }
}

// Initialize the Elementor integration
new Elementor_LearnDash_Video_Integration();
