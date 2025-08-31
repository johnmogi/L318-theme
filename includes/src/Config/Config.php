<?php
/**
 * Theme configuration management
 *
 * @package Lilac\Config
 */

namespace Lilac\Config;

class Config {
    /**
     * @var array Store all configuration values
     */
    private static $config = [];

    /**
     * Initialize the configuration
     */
    public static function init() {
        // Default configuration
        $defaults = [
            'environment' => self::get_environment(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'assets_version' => defined('LILAC_THEME_VERSION') ? LILAC_THEME_VERSION : '1.0.0',
            'text_domain' => 'lilac',
            'theme_supports' => [
                'title-tag',
                'post-thumbnails',
                'custom-logo',
                'html5' => [
                    'search-form',
                    'comment-form',
                    'comment-list',
                    'gallery',
                    'caption',
                ],
                'customize-selective-refresh-widgets',
            ],
            'menus' => [
                'primary' => __('Primary Menu', 'lilac'),
                'footer' => __('Footer Menu', 'lilac'),
            ],
            'sidebars' => [
                'sidebar-1' => [
                    'name' => __('Main Sidebar', 'lilac'),
                    'description' => __('Add widgets here to appear in your main sidebar.', 'lilac'),
                ],
                'footer-1' => [
                    'name' => __('Footer Widgets 1', 'lilac'),
                    'description' => __('Add footer widgets here.', 'lilac'),
                ],
            ],
            'image_sizes' => [
                'lilac-featured' => [1200, 630, true],
                'lilac-thumbnail' => [350, 200, true],
            ],
        ];

        // Environment specific configuration
        $environment_config = [
            'development' => [
                'debug' => true,
                'assets' => [
                    'minify' => false,
                    'source_maps' => true,
                ],
            ],
            'staging' => [
                'debug' => false,
                'assets' => [
                    'minify' => true,
                    'source_maps' => false,
                ],
            ],
            'production' => [
                'debug' => false,
                'assets' => [
                    'minify' => true,
                    'source_maps' => false,
                ],
            ],
        ];

        // Merge configurations
        $env = self::get_environment();
        $env_config = $environment_config[$env] ?? $environment_config['production'];
        
        self::$config = array_merge_recursive($defaults, $env_config);

        // Apply filters to allow modification of config
        self::$config = apply_filters('lilac/config', self::$config);
    }

    /**
     * Get the current environment
     *
     * @return string Current environment (development, staging, production)
     */
    public static function get_environment() {
        if (defined('WP_ENV')) {
            return WP_ENV;
        }

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        
        if (strpos($host, '.local') !== false || $host === 'localhost' || $host === '127.0.0.1') {
            return 'development';
        }
        
        if (strpos($host, 'staging.') !== false || strpos($host, '.staging.') !== false) {
            return 'staging';
        }
        
        return 'production';
    }

    /**
     * Get a configuration value
     *
     * @param string $key Dot notation key (e.g., 'theme_supports.html5')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (empty(self::$config)) {
            self::init();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set a configuration value
     *
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    public static function set($key, $value) {
        if (empty(self::$config)) {
            self::init();
        }

        $keys = explode('.', $key);
        $last_key = array_pop($keys);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config[$last_key] = $value;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public static function is_debug() {
        return self::get('debug', false);
    }

    /**
     * Get the current environment
     *
     * @return string
     */
    public static function environment() {
        return self::get('environment', 'production');
    }

    /**
     * Check if the current environment matches the given environment
     *
     * @param string|array $environment Environment name or array of names
     * @return bool
     */
    public static function is_environment($environment) {
        $current = self::environment();
        
        if (is_array($environment)) {
            return in_array($current, $environment, true);
        }
        
        return $current === $environment;
    }
}

// Initialize the configuration
Config::init();
