<?php
/**
 * Quiz Timer Notifications Integration
 * 
 * Registers the timer notifications system with WordPress.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Include the timer notifications system if file exists
$timer_notifications_file = get_stylesheet_directory() . '/includes/quiz/timer-notifications/init.php';

if (file_exists($timer_notifications_file)) {
    require_once $timer_notifications_file;
    
    // Log successful loading
    error_log('Quiz Timer Notifications system loaded');
} else {
    // Log error if file doesn't exist
    error_log('ERROR: Quiz Timer Notifications file not found at: ' . $timer_notifications_file);
}
