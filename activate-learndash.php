<?php
/**
 * Activate LearnDash Plugin
 * 
 * This script activates the LearnDash plugin and runs the database update.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if current user can activate plugins
if (!current_user_can('activate_plugins')) {
    die('You do not have sufficient permissions to activate plugins.');
}

// Plugin file path
$plugin = 'sfwd-lms/sfwd_lms.php';

// Check if plugin exists
if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
    die('LearnDash plugin not found. Please install it first.');
}

// Activate the plugin
$result = activate_plugin($plugin);

if (is_wp_error($result)) {
    // Plugin activation failed
    echo 'Error activating LearnDash: ' . $result->get_error_message();
} else {
    echo 'LearnDash activated successfully!<br>';
    
    // Check if we need to run database updates
    if (function_exists('learndash_activation_trigger')) {
        learndash_activation_trigger();
        echo 'LearnDash database update triggered. Please check the WordPress admin for any update notifications.';
    } else {
        echo 'Could not trigger database update. Please visit the WordPress admin to complete setup.';
    }
    
    // Provide a link to the admin
    echo '<br><br><a href="' . admin_url('admin.php?page=learndash_lms_settings') . '">Go to LearnDash Settings</a>';
    echo ' | <a href="' . admin_url('plugins.php') . '">View Plugins</a>';
}

// Provide a link back to the site
echo '<br><br><a href="' . home_url() . '">Return to site</a>';
