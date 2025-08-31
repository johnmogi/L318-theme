<?php
/**
 * Script and Style Enqueuing Functions
 *
 * This file contains all functions related to enqueueing scripts and styles for the theme.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
if (!function_exists('hello_elementor_child_enqueue_scripts')) {
    function hello_elementor_child_enqueue_scripts() {
        $style_path = get_stylesheet_directory() . '/style.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : time();

        wp_enqueue_style(
            'hello-elementor-child-style',
            get_stylesheet_directory_uri() . '/style.css',
            ['hello-elementor-theme-style'], 
            $style_version 
        );
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20);

/**
 * Enqueue custom scripts and styles
 */
if (!function_exists('enqueue_custom_scripts')) {
    function enqueue_custom_scripts() {
        // Enqueue parent theme's style
        wp_enqueue_style(
            'hello-elementor-child-style',
            get_stylesheet_directory_uri() . '/style.css',
            [
                'hello-elementor-theme-style',
            ],
            wp_get_theme()->get('Version')
        );

        // Enqueue user account widget style
        wp_enqueue_style(
            'user-widget-style',
            get_stylesheet_directory_uri() . '/css/user-widget.css',
            [],
            wp_get_theme()->get('Version')
        );
        
        // Debugging - output theme directory for checking paths
        error_log('Theme Directory: ' . get_stylesheet_directory_uri());
        
        // Force script versions to prevent caching during development
        $force_version = time();
        
        // Enqueue Toast message system - with absolute path to ensure loading
        wp_enqueue_script(
            'toast-message-system',
            get_stylesheet_directory_uri() . '/includes/messaging/js/toast-system.js',
            ['jquery'],
            $force_version,
            false // Load in header to ensure it's available early
        );
        
        // Enqueue our simple test script
        wp_enqueue_script(
            'toast-test-script',
            get_stylesheet_directory_uri() . '/includes/messaging/js/toast-test.js',
            ['jquery'],
            $force_version,
            false // Load in header to make testing easier
        );
        
        // Enqueue Alert Helpers (depends on toast system)
        wp_enqueue_script(
            'alert-helpers',
            get_stylesheet_directory_uri() . '/includes/messaging/js/alert-helpers.js',
            ['jquery', 'toast-message-system'],
            $force_version,
            false // Load in header to ensure it's available early
        );
        
        // Localize toast settings
        wp_localize_script('toast-message-system', 'toastSettings', [
            'defaultDuration' => 5000,
            'position' => 'top-right',
            'enableAlertIntegration' => true,
            'debugMode' => true
        ]);
        
        // Add inline script for immediate testing
        wp_add_inline_script('toast-message-system', '
            console.log("Inline script test: Toast system is being loaded");
            jQuery(document).ready(function($) {
                console.log("DOM ready - Toast system should be available");
            });
        ');
        
        // Enqueue Bootstrap Toast CSS
        wp_enqueue_style(
            'bootstrap-toasts',
            get_stylesheet_directory_uri() . '/css/bootstrap-toasts.css',
            [],
            '5.0.2'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

/**
 * Enqueue all theme styles with proper dependencies
 */
if (!function_exists('lilac_enqueue_all_styles')) {
    function lilac_enqueue_all_styles() {
        // Define CSS files to be enqueued with their dependencies
        $css_files = [
            'lilac-variables' => [
                'path' => '/css/_variables.css',
                'deps' => [],
                'version' => null
            ],
            'lilac-login' => [
                'path' => '/css/login.css',
                'deps' => ['lilac-variables'],
                'version' => null
            ],
            'lilac-registration' => [
                'path' => '/css/registration.css',
                'deps' => ['lilac-variables', 'lilac-login'],
                'version' => null
            ],
            'lilac-dashboard' => [
                'path' => '/css/dashboard.css',
                'deps' => ['lilac-variables'],
                'version' => null
            ],
            'lilac-course-progress' => [
                'path' => '/css/course-progress.css',
                'deps' => ['lilac-variables'],
                'version' => null
            ],
            'lilac-teacher-dashboard' => [
                'path' => '/css/teacher-dashboard.css',
                'deps' => ['lilac-variables', 'lilac-dashboard'],
                'version' => null
            ]
        ];

        $theme_dir = get_stylesheet_directory();
        $theme_uri = get_stylesheet_directory_uri();

        // Loop through and enqueue each CSS file
        foreach ($css_files as $handle => $details) {
            $file_path = $theme_dir . $details['path'];
            
            // Only enqueue if the file exists
            if (file_exists($file_path)) {
                $version = $details['version'] ?: (filemtime($file_path) ?: wp_get_theme()->get('Version'));
                
                wp_enqueue_style(
                    $handle,
                    $theme_uri . $details['path'],
                    $details['deps'],
                    $version
                );
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'lilac_enqueue_all_styles', 10);

/**
 * Remove any existing style enqueues to avoid conflicts
 */
if (!function_exists('lilac_remove_conflicting_styles')) {
    function lilac_remove_conflicting_styles() {
        // List of styles to dequeue
        $styles_to_remove = [
            // Add styles to remove here if needed
        ];
        
        foreach ($styles_to_remove as $handle) {
            wp_dequeue_style($handle);
        }
    }
}
add_action('wp_enqueue_scripts', 'lilac_remove_conflicting_styles', 5);

/**
 * Remove version query strings from static resources
 * 
 * @param string $src The source URL
 * @return string Modified URL without version query
 */
if (!function_exists('remove_css_js_version_query')) {
    function remove_css_js_version_query($src) {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
}
add_filter('style_loader_src', 'remove_css_js_version_query', 10, 1);
add_filter('script_loader_src', 'remove_css_js_version_query', 10, 1);
