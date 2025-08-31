<?php
/**
 * Diagnostic script to check testihrt_admin user
 */

// Only run in admin
if (!is_admin()) {
    return;
}

// Add admin notice with user info
add_action('admin_notices', function() {
    $user = get_user_by('login', 'testihrt_admin');
    
    if (!$user) {
        echo '<div class="notice notice-warning"><p>User testihrt_admin not found.</p></div>';
        return;
    }
    
    $roles = $user->roles;
    $user_info = sprintf(
        'User ID: %d, Email: %s, Roles: %s',
        $user->ID,
        $user->user_email,
        implode(', ', $roles)
    );
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>testihrt_admin User Info:</strong> ' . esc_html($user_info) . '</p>';
    
    if (in_array('wdm_instructor', $roles)) {
        echo '<p>This user has the wdm_instructor role.</p>';
        echo '<form method="post" action="">';
        wp_nonce_field('remove_instructor_role', 'instructor_nonce');
        echo '<input type="hidden" name="action" value="remove_instructor_role">';
        submit_button('Remove wdm_instructor Role', 'secondary', 'submit', false);
        echo '</form>';
    } else {
        echo '<p>This user does not have the wdm_instructor role.</p>';
    }
    
    echo '</div>';
});

// Handle form submission
add_action('admin_init', function() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'remove_instructor_role') {
        return;
    }
    
    if (!current_user_can('edit_users') || !wp_verify_nonce($_POST['instructor_nonce'] ?? '', 'remove_instructor_role')) {
        wp_die('Permission denied');
    }
    
    $user = get_user_by('login', 'testihrt_admin');
    if ($user) {
        $user->remove_role('wdm_instructor');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Successfully removed wdm_instructor role from testihrt_admin.</p></div>';
        });
    }
});
