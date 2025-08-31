<?php
/**
 * LearnDash Layout Fix
 * 
 * Improves the layout of LearnDash course items to be more compact
 */

if (!defined('ABSPATH')) {
    exit;
}

class LearnDash_Layout_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add custom CSS for LearnDash layout
        add_action('wp_enqueue_scripts', array($this, 'enqueue_layout_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_layout_styles'));
    }
    
    /**
     * Enqueue custom layout styles
     */
    public function enqueue_layout_styles() {
        // Only load on LearnDash pages
        if (!$this->is_learndash_page()) {
            return;
        }
        
        $css = "
        /* PROFESSIONAL LEARNDASH LAYOUT - COMPLETE REDESIGN */
        
        /* === LESSON HEADER SECTION (TOP PART) === */
        
        /* Main lesson item container */
        .ld-item-list-item {
            margin-bottom: 8px !important;
            border: 1px solid #e1e5e9 !important;
            border-radius: 8px !important;
            background: #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06) !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
        }
        
        .ld-item-list-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
            transform: translateY(-1px) !important;
        }
        
        /* Lesson header preview */
        .ld-item-list-item-preview {
            display: flex !important;
            align-items: center !important;
            padding: 16px 20px !important;
            min-height: auto !important;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
            border-bottom: 1px solid #e9ecef !important;
        }
        
        /* Tooltip container */
        .ld-item-list-item-preview .ld-tooltip {
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
        }
        
        /* Lesson link */
        .ld-item-list-item-preview .ld-item-name {
            display: flex !important;
            align-items: center !important;
            flex: 1 !important;
            text-decoration: none !important;
            color: inherit !important;
        }
        
        .ld-item-list-item-preview .ld-item-name:hover {
            color: #0073aa !important;
        }
        
        /* Status icon styling */
        .ld-item-list-item-preview .ld-status-icon {
            margin: 0 15px 0 0 !important;
            flex-shrink: 0 !important;
            width: 24px !important;
            height: 24px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Lesson title */
        .ld-item-list-item-preview .ld-item-title {
            flex: 1 !important;
            line-height: 1.4 !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
            margin: 0 !important;
        }
        
        /* Lesson components (3 נושאים) */
        .ld-item-list-item-preview .ld-item-components {
            font-size: 13px !important;
            color: #6c757d !important;
            margin-top: 4px !important;
            font-weight: 400 !important;
        }
        
        .ld-item-list-item-preview .ld-item-component {
            background: #e9ecef !important;
            padding: 2px 8px !important;
            border-radius: 12px !important;
            font-size: 11px !important;
            color: #495057 !important;
        }
        
        /* Expand button container */
        .ld-item-list-item-preview .ld-item-details {
            margin-left: auto !important;
            flex-shrink: 0 !important;
        }
        
        /* Expand/Collapse button */
        .ld-item-list-item-preview .ld-expand-button {
            padding: 8px 16px !important;
            min-width: auto !important;
            white-space: nowrap !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 6px !important;
            background: #fff !important;
            color: #495057 !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
        }
        
        .ld-item-list-item-preview .ld-expand-button:hover {
            background: #f8f9fa !important;
            border-color: #adb5bd !important;
            transform: translateY(-1px) !important;
        }
        
        .ld-item-list-item-preview .ld-expand-button .ld-icon {
            margin-left: 6px !important;
            font-size: 12px !important;
        }
        
        /* === TOPIC LIST SECTION (BOTTOM PART) === */
        
        /* Topic list header */
        .ld-table-list-header {
            padding: 12px 20px !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: #fff !important;
            border-radius: 0 !important;
        }
        
        .ld-table-list-header .ld-table-list-title {
            display: flex !important;
            align-items: center !important;
            font-size: 14px !important;
            font-weight: 600 !important;
        }
        
        .ld-table-list-header .ld-item-icon {
            margin-left: 8px !important;
            opacity: 0.9 !important;
        }
        
        .ld-table-list-header .ld-table-list-lesson-details {
            font-size: 12px !important;
            opacity: 0.9 !important;
        }
        
        /* Topic list items container */
        .ld-table-list-items {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        
        /* Individual topic items */
        .ld-table-list-item {
            margin: 0 !important;
            border-bottom: 1px solid #f1f3f4 !important;
            background: #fff !important;
            transition: background-color 0.2s ease !important;
        }
        
        .ld-table-list-item:last-child {
            border-bottom: none !important;
        }
        
        .ld-table-list-item:hover {
            background: #f8f9fa !important;
        }
        
        /* Topic item preview (link) */
        .ld-table-list-item-preview {
            display: flex !important;
            align-items: center !important;
            padding: 14px 20px !important;
            min-height: auto !important;
            text-decoration: none !important;
            color: inherit !important;
            transition: all 0.2s ease !important;
        }
        
        .ld-table-list-item-preview:hover {
            color: #0073aa !important;
            padding-right: 24px !important;
        }
        
        /* Topic status icon */
        .ld-table-list-item-preview .ld-status-icon {
            margin: 0 12px 0 0 !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            height: 20px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Topic title */
        .ld-table-list-item-preview .ld-topic-title {
            flex: 1 !important;
            line-height: 1.4 !important;
            font-size: 14px !important;
            color: #2c3e50 !important;
            margin: 0 !important;
            font-weight: 500 !important;
            text-align: right !important;
            direction: rtl !important;
        }
        
        /* === RTL SUPPORT FOR HEBREW === */
        .rtl .ld-item-list-item-preview .ld-status-icon {
            margin: 0 0 0 15px !important;
        }
        
        .rtl .ld-item-list-item-preview .ld-item-details {
            margin-left: 0 !important;
            margin-right: auto !important;
        }
        
        .rtl .ld-expand-button .ld-icon {
            margin-left: 0 !important;
            margin-right: 6px !important;
        }
        
        .rtl .ld-table-list-item-preview .ld-status-icon {
            margin: 0 0 0 12px !important;
        }
        
        /* === RESPONSIVE DESIGN === */
        @media (max-width: 768px) {
            .ld-item-list-item-preview {
                padding: 12px 16px !important;
            }
            
            .ld-item-list-item-preview .ld-item-title {
                font-size: 15px !important;
            }
            
            .ld-item-list-item-preview .ld-expand-button {
                padding: 6px 12px !important;
                font-size: 12px !important;
            }
            
            .ld-table-list-header {
                padding: 10px 16px !important;
            }
            
            .ld-table-list-item-preview {
                padding: 12px 16px !important;
            }
            
            .ld-table-list-item-preview .ld-topic-title {
                font-size: 13px !important;
            }
        }
        
        /* Ensure text doesn't overflow */
        .ld-item-list-item-preview .ld-item-title {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
        }
        
        /* Make sure expand button is always visible */
        .ld-item-list-item-preview .ld-expand-button .ld-icon {
            margin-left: 5px !important;
        }
        
        .rtl .ld-item-list-item-preview .ld-expand-button .ld-icon {
            margin-left: 0 !important;
            margin-right: 5px !important;
        }
        ";
        
        wp_add_inline_style('learndash-front', $css);
        
        // If learndash-front style isn't available, create our own
        if (!wp_style_is('learndash-front', 'registered')) {
            wp_register_style('learndash-layout-fix', false);
            wp_enqueue_style('learndash-layout-fix');
            wp_add_inline_style('learndash-layout-fix', $css);
        }
        
        error_log("LearnDash Layout Fix: Applied compact layout styles");
    }
    
    /**
     * Check if current page is a LearnDash page
     */
    private function is_learndash_page() {
        global $post;
        
        // Check if it's a LearnDash post type
        if (is_singular() && $post) {
            $learndash_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');
            if (in_array($post->post_type, $learndash_post_types)) {
                return true;
            }
        }
        
        // Check if LearnDash is active and this is a course/lesson page
        if (function_exists('learndash_is_course_builder_enabled')) {
            return true;
        }
        
        // Check for LearnDash body classes
        if (is_admin()) {
            return false;
        }
        
        return false;
    }
}

// Initialize the layout fix
LearnDash_Layout_Fix::instance();
