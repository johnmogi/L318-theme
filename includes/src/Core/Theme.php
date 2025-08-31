<?php
/**
 * Theme core class
 *
 * @package Lilac\Core
 */

namespace Lilac\Core;

class Theme {
    /**
     * Theme version
     */
    const VERSION = '1.0.0';

    /**
     * The single instance of the class
     */
    protected static $instance = null;

    /**
     * Main Theme Instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        $this->define('LILAC_THEME_PATH', get_stylesheet_directory());
        $this->define('LILAC_THEME_URI', get_stylesheet_directory_uri());
        $this->define('LILAC_THEME_ASSETS_URI', LILAC_THEME_URI . '/assets');
        $this->define('LILAC_THEME_INC', LILAC_THEME_PATH . '/includes');
        $this->define('LILAC_THEME_VERSION', self::VERSION);
    }

    /**
     * Define constant if not already set
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Setup theme supports
        add_action('after_setup_theme', array($this, 'setup_theme_supports'));
        
        // Load text domain
        add_action('after_setup_theme', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 20);
        
        // Add body classes
        add_filter('body_class', array($this, 'body_classes'));
    }

    /**
     * Setup theme supports
     */
    public function setup_theme_supports() {
        // Add default posts and comments RSS feed links to head
        add_theme_support('automatic-feed-links');
        
        // Let WordPress manage the document title
        add_theme_support('title-tag');
        
        // Enable support for Post Thumbnails on posts and pages
        add_theme_support('post-thumbnails');
        
        // Add HTML5 support
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ));
        
        // Add theme support for selective refresh for widgets
        add_theme_support('customize-selective-refresh-widgets');
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_theme_textdomain('lilac', LILAC_THEME_PATH . '/languages');
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Theme stylesheet
        wp_enqueue_style(
            'lilac-style',
            get_stylesheet_uri(),
            array(),
            LILAC_THEME_VERSION
        );
        
        // Main JavaScript file
        wp_enqueue_script(
            'lilac-main',
            LILAC_THEME_ASSETS_URI . '/js/main.js',
            array('jquery'),
            LILAC_THEME_VERSION,
            true
        );
        
        // Localize script with theme variables
        wp_localize_script('lilac-main', 'lilac_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('lilac-nonce'),
        ));
    }

    /**
     * Add custom classes to the array of body classes
     */
    public function body_classes($classes) {
        // Add class of hfeed to non-singular pages
        if (!is_singular()) {
            $classes[] = 'hfeed';
        }
        
        // Add class if sidebar is active
        if (is_active_sidebar('sidebar-1')) {
            $classes[] = 'has-sidebar';
        } else {
            $classes[] = 'no-sidebar';
        }
        
        return $classes;
    }
}

// Initialize the theme
function lilac_theme() {
    return Theme::instance();
}

// Start the theme
lilac_theme();
