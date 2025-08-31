<?php
/**
 * Load AJAX handlers for the theme
 * 
 * @package Hello_Child_Theme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load teacher students export handler
require_once get_stylesheet_directory() . '/inc/ajax/teacher-students-export.php';
