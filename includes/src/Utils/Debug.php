<?php
/**
 * Debug and error handling utilities
 *
 * @package Lilac\Utils
 */

namespace Lilac\Utils;

class Debug {
    /**
     * Log debug information to the WordPress debug log
     *
     * @param mixed $data The data to log
     * @param string $message Optional message to include
     * @return void
     */
    public static function log($data, $message = '') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $output = '';
        
        if (!empty($message)) {
            $output .= "[${message}] ";
        }
        
        if (is_string($data) || is_numeric($data)) {
            $output .= $data;
        } else {
            $output .= print_r($data, true);
        }
        
        error_log($output);
    }

    /**
     * Dump and die for debugging
     * 
     * @param mixed $data The data to dump
     * @param bool $die Whether to die after dumping
     */
    public static function dd($data, $die = true) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }

    /**
     * Log an error to the WordPress error log
     * 
     * @param string $message The error message
     * @param mixed $data Optional additional data to log
     */
    public static function error($message, $data = null) {
        error_log('ERROR: ' . $message);
        
        if ($data !== null) {
            error_log('Error data: ' . print_r($data, true));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG && !wp_doing_ajax()) {
            if (!empty($data)) {
                $message .= ' Check the error log for details.';
            }
            
            if (!did_action('admin_notices')) {
                add_action('admin_notices', function() use ($message) {
                    echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
                });
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
            }
        }
    }

    /**
     * Log a warning to the WordPress error log
     * 
     * @param string $message The warning message
     * @param mixed $data Optional additional data to log
     */
    public static function warning($message, $data = null) {
        error_log('WARNING: ' . $message);
        
        if ($data !== null) {
            error_log('Warning data: ' . print_r($data, true));
        }
    }

    /**
     * Log a notice to the WordPress error log
     * 
     * @param string $message The notice message
     * @param mixed $data Optional additional data to log
     */
    public static function notice($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NOTICE: ' . $message);
            
            if ($data !== null) {
                error_log('Notice data: ' . print_r($data, true));
            }
        }
    }

    /**
     * Log deprecated function usage
     * 
     * @param string $function The function that was called
     * @param string $version The version when the function became deprecated
     * @param string $replacement Optional replacement function
     */
    public static function deprecated($function, $version, $replacement = null) {
        $message = sprintf(
            '%1$s is <strong>deprecated</strong> since version %2$s!',
            $function,
            $version
        );
        
        if (!empty($replacement)) {
            $message .= sprintf(' Use %1$s instead.', $replacement);
        }
        
        // Log the deprecated notice
        _deprecated_function($function, $version, $replacement);
        
        // Show admin notice if needed
        if (is_admin() && current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-warning"><p>' . wp_kses_post($message) . '</p></div>';
            });
        }
    }
}
