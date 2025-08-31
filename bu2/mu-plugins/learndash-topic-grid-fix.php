<?php
/**
 * LearnDash Topic Grid Layout Fix
 * 
 * Forces compact layout for LearnDash topic lists in Elementor
 * This is a dedicated fix with maximum CSS specificity
 */

if (!defined('ABSPATH')) {
    exit;
}

class LearnDash_Topic_Grid_Fix {
    
    public function __construct() {
        // Hook with high priority to ensure CSS loads
        add_action('wp_head', array($this, 'force_topic_grid_css'), 999);
        add_action('wp_footer', array($this, 'debug_css_loading'), 999);
    }
    
    /**
     * Force CSS to load in head with maximum specificity
     */
    public function force_topic_grid_css() {
        // Only on LearnDash pages
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        echo '<style id="learndash-topic-grid-fix" type="text/css">';
        echo $this->get_topic_grid_css();
        echo '</style>';
    }
    
    /**
     * Debug CSS loading
     */
    public function debug_css_loading() {
        if (!is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<script>console.log("LearnDash Topic Grid Fix CSS loaded");</script>';
        }
    }
    
    /**
     * Get the CSS for topic grid fixes
     */
    private function get_topic_grid_css() {
        return '
        /* LEARNDASH TOPIC GRID FIX - MAXIMUM SPECIFICITY */
        
        /* Fix video spacing first */
        html body.learndash-cpt .ld-video {
            padding-bottom: 0 !important;
            margin-bottom: 10px !important;
        }
        
        html body.learndash-cpt .ld-video iframe {
            height: 315px !important;
            width: 100% !important;
        }
        
        /* TARGET STANDARD LEARNDASH TOPIC LIST */
        html body.learndash-cpt .ld-table-list-items {
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        /* Individual topic items - CRITICAL FIX */
        html body.learndash-cpt .ld-table-list-item {
            margin: 0 0 3px 0 !important;
            padding: 0 !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            background: #fff !important;
            overflow: hidden !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        }
        
        html body.learndash-cpt .ld-table-list-item:last-child {
            margin-bottom: 0 !important;
        }
        
        /* Topic row - MAIN LAYOUT FIX */
        html body.learndash-cpt .ld-table-list-item-preview {
            display: flex !important;
            align-items: center !important;
            padding: 12px 15px !important;
            min-height: auto !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            border: none !important;
            background: transparent !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        html body.learndash-cpt .ld-table-list-item-preview:hover {
            background-color: #f8f9fa !important;
        }
        
        /* Status icon */
        html body.learndash-cpt .ld-table-list-item-preview .ld-status-icon {
            margin: 0 12px 0 0 !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            height: 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Topic title */
        html body.learndash-cpt .ld-table-list-item-preview .ld-topic-title {
            flex: 1 !important;
            line-height: 1.4 !important;
            font-size: 14px !important;
            color: #333 !important;
            margin: 0 !important;
            padding: 0 !important;
            font-weight: 500 !important;
            text-align: right !important;
            direction: rtl !important;
        }
        
        html body.learndash-cpt .ld-table-list-item-preview:hover .ld-topic-title {
            color: #0073aa !important;
        }
        
        /* RTL adjustments for Hebrew */
        html body.rtl.learndash-cpt .ld-table-list-item-preview .ld-status-icon {
            margin: 0 0 0 12px !important;
        }
        
        /* ALSO TARGET SHORTCODE GRID IF EXISTS */
        html body.learndash-cpt .llm-early-topics {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        html body.learndash-cpt .llm-topic-item {
            margin-bottom: 3px !important;
            padding: 12px 15px !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            background: #fff !important;
            transition: background-color 0.2s ease !important;
        }
        
        html body.learndash-cpt .llm-topic-item:hover {
            background-color: #f8f9fa !important;
        }
        
        html body.learndash-cpt .llm-topic-item h4 {
            margin: 0 0 4px 0 !important;
            padding: 0 !important;
            font-size: 14px !important;
            line-height: 1.3 !important;
        }
        
        html body.learndash-cpt .llm-lesson-title {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 12px !important;
            color: #666 !important;
            line-height: 1.2 !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            html body.learndash-cpt .ld-table-list-item-preview {
                padding: 10px 12px !important;
            }
            
            html body.learndash-cpt .ld-table-list-item-preview .ld-topic-title {
                font-size: 13px !important;
            }
            
            html body.learndash-cpt .llm-topic-item {
                padding: 10px 12px !important;
            }
        }
        ';
    }
}

// Initialize immediately
new LearnDash_Topic_Grid_Fix();
