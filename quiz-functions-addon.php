<?php
/**
 * Quiz Functionality Add-on
 * 
 * This file loads our refactored quiz functionality.
 * Include this at the end of functions.php without modifying existing code.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load our refactored quiz functionality
require_once get_stylesheet_directory() . '/includes/quiz/loader.php';
