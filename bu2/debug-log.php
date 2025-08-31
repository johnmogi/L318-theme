<?php
/**
 * Debug logging functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Write to debug log file with timestamp
 */
function lilac_debug_log($message, $data = null) {
    $log_file = WP_CONTENT_DIR . '/lilac-debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    
    // Format the log entry
    $log_entry = $timestamp . ' ' . $message;
    
    // Add data if available
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_entry .= "\n" . print_r($data, true);
        } else {
            $log_entry .= ' ' . $data;
        }
    }
    
    // Add new line
    $log_entry .= "\n";
    
    // Make sure directory is writable
    if (!is_writable(dirname($log_file))) {
        error_log("LILAC DEBUG: Content directory is not writable for debug logs");
        return;
    }
    
    // Check if file exists but isn't writable
    if (file_exists($log_file) && !is_writable($log_file)) {
        error_log("LILAC DEBUG: Log file exists but isn't writable");
        return;
    }
    
    // Write to file
    $result = file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Log to PHP error log as fallback if debug file writing fails
    if ($result === false) {
        error_log("LILAC DEBUG LOG FAILED: " . $message);
        if ($data !== null) {
            error_log("LILAC DEBUG DATA: " . print_r($data, true));
        }
    }
    
    // Force immediate synchronization to disk
    if (function_exists('fsync')) {
        $handle = fopen($log_file, 'a');
        if ($handle) {
            fflush($handle);
            fsync($handle);
            fclose($handle);
        }
    }
}
