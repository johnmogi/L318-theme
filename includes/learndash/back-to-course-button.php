<?php
/**
 * Back to Course Button for LearnDash Navigation
 * 
 * This file is now a placeholder as the back-to-course button
 * has been moved to the quiz sidebar template.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue minimal styles for the back to course button
add_action('wp_enqueue_scripts', function() {
    if (is_singular('sfwd-quiz')) {
        $css = '
        /* Back to course button styles in quiz sidebar */
        .lilac-back-to-course-wrapper {
            text-align: center;
            margin: 15px 0;
        }
        
        .back-to-course-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: rgba(44, 51, 145, 1);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .back-to-course-btn:hover {
            background-color: rgba(44, 51, 145, 1) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }';
        
        wp_add_inline_style('hello-theme-style', $css);
    }
});

// For quiz pages specifically, ensure our styles are loaded
add_filter('the_content', function($content) {
    if (is_singular('sfwd-quiz')) {
        return lilac_add_back_to_course_button($content);
    }
    return $content;
}, 5);

// Remove footer addition to prevent duplicates

// Removed duplicate navigation modification to prevent multiple buttons
