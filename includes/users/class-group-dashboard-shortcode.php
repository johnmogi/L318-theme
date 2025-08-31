<?php
/**
 * Group Dashboard Shortcode
 * 
 * Displays statistics and information for a specific group/class
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Group_Dashboard_Shortcode {
    /**
     * Initialize the shortcode
     */
    public function __construct() {
        add_shortcode('group_dashboard', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if (is_page() && has_shortcode(get_post()->post_content, 'group_dashboard')) {
            wp_enqueue_style(
                'group-dashboard-style',
                get_stylesheet_directory_uri() . '/assets/css/group-dashboard.css',
                array(),
                filemtime(get_stylesheet_directory() . '/assets/css/group-dashboard.css')
            );
        }
    }
    
    /**
     * Render the dashboard
     */
    public function render_dashboard($atts) {
        // Only show to logged in users with appropriate capabilities
        if (!is_user_logged_in() || !current_user_can('edit_users')) {
            return '<div class="group-dashboard-notice">' . 
                   __('You do not have permission to view this page.', 'hello-theme-child') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'group_id' => '',
        ), $atts, 'group_dashboard');
        
        // If no group ID is provided, try to get it from the current user
        $group_id = $atts['group_id'];
        if (empty($group_id)) {
            $group_id = get_user_meta(get_current_user_id(), Group_Field_Manager::GROUP_FIELD_KEY, true);
        }
        
        if (empty($group_id)) {
            return '<div class="group-dashboard-notice">' . 
                   __('No group/class specified and user is not assigned to any group.', 'hello-theme-child') . 
                   '</div>';
        }
        
        ob_start();
        
        // Include the group statistics
        if (class_exists('Group_Statistics')) {
            echo Group_Statistics::render_group_dashboard($group_id);
        } else {
            echo '<div class="group-dashboard-notice">' . 
                 __('Group statistics functionality is not available.', 'hello-theme-child') . 
                 '</div>';
        }
        
        return ob_get_clean();
    }
}

// Initialize the shortcode
function init_group_dashboard_shortcode() {
    new Group_Dashboard_Shortcode();
}
add_action('init', 'init_group_dashboard_shortcode');
