<?php
/**
 * LearnDash Functions
 *
 * This file contains all LearnDash-related functions for the theme.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load LearnDash Access Control for Students and Teachers
 */
if (!function_exists('ccr_load_learndash_access_control')) {
    function ccr_load_learndash_access_control() {
        // Only load if LearnDash is active
        if (!class_exists('SFWD_LMS')) {
            return;
        }
        
        // Include the LearnDash access control file
        $file = get_stylesheet_directory() . '/includes/learndash/access-control.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
add_action('after_setup_theme', 'ccr_load_learndash_access_control', 10);

/**
 * Debug function to output debugging info for LearnDash
 */
if (!function_exists('ccr_debug_login')) {
    function ccr_debug_login($user_login, $user) {
        // Only do this debugging for administrator accounts
        if (!user_can($user, 'administrator') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        // Get user courses
        if (function_exists('learndash_user_get_enrolled_courses')) {
            $courses = learndash_user_get_enrolled_courses($user->ID);
            
            error_log('User ' . $user->user_login . ' (ID: ' . $user->ID . ') has ' . count($courses) . ' enrolled courses.');
            
            // Output each course
            foreach ($courses as $course_id) {
                error_log('- Course: ' . get_the_title($course_id) . ' (ID: ' . $course_id . ')');
            }
        }
        
        // Get group memberships
        if (function_exists('learndash_get_users_group_ids')) {
            $groups = learndash_get_users_group_ids($user->ID);
            
            error_log('User ' . $user->user_login . ' (ID: ' . $user->ID . ') belongs to ' . count($groups) . ' groups.');
            
            // Output each group
            foreach ($groups as $group_id) {
                error_log('- Group: ' . get_the_title($group_id) . ' (ID: ' . $group_id . ')');
            }
        }
    }
}
add_action('wp_login', 'ccr_debug_login', 10, 2);

// Add to footer for easier debugging
add_action('wp_footer', function() {
    if (current_user_can('administrator') || isset($_GET['show_membership_debug'])) {
        // Only output if LearnDash is active
        if (!function_exists('learndash_user_get_enrolled_courses')) {
            return;
        }
        
        $current_user = wp_get_current_user();
        
        // Get user courses
        $courses = learndash_user_get_enrolled_courses($current_user->ID);
        
        // Get group memberships
        $groups = [];
        if (function_exists('learndash_get_users_group_ids')) {
            $groups = learndash_get_users_group_ids($current_user->ID);
        }
        
        // Output debug info
        echo '<div style="background: #f5f5f5; padding: 15px; margin: 20px; border: 1px solid #ddd; font-family: monospace; direction: ltr; text-align: left;">';
        echo '<h3>LearnDash Debug Info</h3>';
        echo '<p>User: ' . esc_html($current_user->user_login) . ' (ID: ' . esc_html($current_user->ID) . ')</p>';
        
        echo '<h4>Enrolled Courses (' . count($courses) . ')</h4>';
        if (!empty($courses)) {
            echo '<ul>';
            foreach ($courses as $course_id) {
                echo '<li>' . get_the_title($course_id) . ' (ID: ' . $course_id . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No enrolled courses.</p>';
        }
        
        echo '<h4>Group Memberships (' . count($groups) . ')</h4>';
        if (!empty($groups)) {
            echo '<ul>';
            foreach ($groups as $group_id) {
                echo '<li>' . get_the_title($group_id) . ' (ID: ' . $group_id . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No group memberships.</p>';
        }
        
        echo '</div>';
    }
});
