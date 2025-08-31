<?php
/**
 * One-time script to remove wdm_instructor role from testihrt_admin
 * 
 * This script will:
 * 1. Remove the wdm_instructor role from the testihrt_admin user
 * 2. Self-delete after successful execution
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only run this script once
$option_name = 'testihrt_instructor_removed';
if (get_option($option_name)) {
    // Self-delete if this script has already run
    @unlink(__FILE__);
    return;
}

// Get the user by login
$user = get_user_by('login', 'testihrt_admin');

if ($user) {
    // Remove wdm_instructor role
    $user->remove_role('wdm_instructor');
    
    // Log the action
    error_log('Removed wdm_instructor role from testihrt_admin');
    
    // Mark as complete
    update_option($option_name, current_time('mysql'));
    
    // Show admin notice
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Successfully removed wdm_instructor role from testihrt_admin.</p>
        </div>
        <?php
    });
}

// Self-delete after execution
register_shutdown_function(function() {
    @unlink(__FILE__);
});
