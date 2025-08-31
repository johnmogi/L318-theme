<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Enqueue scripts and styles
 */
function hello_elementor_child_scripts_styles() {
    // Parent theme styles
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['hello-elementor-theme-style'],
        filemtime(get_stylesheet_directory() . '/style.css')
    );
    
    // Enqueue custom scripts only if they exist
    if (file_exists(get_stylesheet_directory() . '/assets/js/custom.js')) {
        wp_enqueue_script(
            'hello-elementor-child-script',
            get_stylesheet_directory_uri() . '/assets/js/custom.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/custom.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20);

// Load other theme files
require_once get_stylesheet_directory() . '/inc/shortcodes/loader.php';

// Load Ultimate Member integration if UM is active
function ccr_load_um_integration() {
    if (class_exists('UM')) {
        require_once get_stylesheet_directory() . '/includes/integrations/class-ultimate-member-integration.php';
    }
}
add_action('after_setup_theme', 'ccr_load_um_integration', 5);

// Load Messaging System
function ccr_load_messaging_system() {
    require_once get_stylesheet_directory() . '/includes/messaging/notifications.php';
    
    if (is_admin()) {
        require_once get_stylesheet_directory() . '/includes/messaging/admin-functions.php';
    }
}
add_action('after_setup_theme', 'ccr_load_messaging_system', 10);

// Load Login System
function ccr_load_login_system() {
    if (!is_admin()) {
        require_once get_stylesheet_directory() . '/src/Login/LoginManager.php';
        require_once get_stylesheet_directory() . '/src/Login/Captcha.php';
        require_once get_stylesheet_directory() . '/src/Login/UserAccountWidget.php';
    }
}
add_action('after_setup_theme', 'ccr_load_login_system', 10);

// Add body class for forced hint quizzes
add_filter('body_class', function($classes) {
    if (is_singular('sfwd-quiz') && get_post_meta(get_the_ID(), 'enforce_hint', true) === 'yes') {
        $classes[] = 'forced-hint-quiz';
    }
    return $classes;
});

// Debug helper function
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
