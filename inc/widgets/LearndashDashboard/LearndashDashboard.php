<?php
namespace Windstorm\Widgets;

class LearndashDashboard {
    public function __construct() {
        add_shortcode('learndash_dashboard', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_register_style('learndash-dashboard-style', get_stylesheet_directory_uri() . '/inc/widgets/LearndashDashboard/style.css', [], '1.0');
        wp_enqueue_style('learndash-dashboard-style');
    }

    public function render($atts = []) {
        // Get current user data
        $current_user = wp_get_current_user();
        $display_name = $current_user->display_name ?: 'משתמש';
        $current_date = date('d/m/Y');
        
        // Start output buffering
        ob_start();
        include __DIR__ . '/view.php';
        return ob_get_clean();
    }
}
