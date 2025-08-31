<?php
/**
 * Plugin Name: School Manager MU Plugins Loader
 * Description: Organized loader for School Manager enhancements
 * Version: 2.0
 * Author: School Manager Lite
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple loading
if (defined('SCHOOL_MANAGER_MU_LOADED')) {
    return;
}
define('SCHOOL_MANAGER_MU_LOADED', true);

// Load plugins in specific order to prevent conflicts
class School_Manager_MU_Loader {
    private static $loaded_plugins = array();
    
    public static function init() {
        // Load core functionality first
        self::load_directory('active', 'Core functionality');
        
        // Load LearnDash integrations
        self::load_directory('learndash', 'LearnDash integrations');
        
        // Load import/export features
        self::load_directory('import-export', 'Import/Export features');
        
        // Load debug tools only in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::load_directory('debug', 'Debug tools');
        }
        
        // Log loaded plugins
        error_log('School Manager MU Loader: Loaded ' . count(self::$loaded_plugins) . ' plugins');
    }
    
    private static function load_directory($dir, $description) {
        $plugins = glob(__DIR__ . '/' . $dir . '/*.php');
        if (empty($plugins)) {
            return;
        }
        
        foreach ($plugins as $file) {
            $basename = basename($file);
            
            // Skip if already loaded
            if (in_array($basename, self::$loaded_plugins)) {
                continue;
            }
            
            try {
                require_once $file;
                self::$loaded_plugins[] = $basename;
                error_log("School Manager MU Loader: Loaded {$basename} ({$description})");
            } catch (Exception $e) {
                error_log("School Manager MU Loader: Error loading {$basename}: " . $e->getMessage());
            }
        }
    }
}

// Initialize the loader
School_Manager_MU_Loader::init();
