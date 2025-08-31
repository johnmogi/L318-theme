<?php
/**
 * Admin Functions
 *
 * This file contains functions related to the WordPress admin area.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add teacher role field to user profile
 * 
 * @param WP_User $user The user object being edited
 */
if (!function_exists('add_teacher_role_field')) {
    function add_teacher_role_field($user) {
        // Only show this field for admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get current value
        $is_teacher = get_user_meta($user->ID, '_is_school_teacher', true);
        ?>
        <h3>Teacher Role Assignment</h3>
        <table class="form-table">
            <tr>
                <th><label for="is_school_teacher">School Teacher</label></th>
                <td>
                    <input type="checkbox" name="is_school_teacher" id="is_school_teacher" value="1" <?php checked($is_teacher, '1'); ?> />
                    <span class="description">Assign this user as a School Teacher with limited admin access.</span>
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'add_teacher_role_field');
add_action('edit_user_profile', 'add_teacher_role_field');

/**
 * Save teacher role assignment
 * 
 * @param int $user_id The user ID being edited
 */
if (!function_exists('save_teacher_role_field')) {
    function save_teacher_role_field($user_id) {
        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save the meta value
        update_user_meta($user_id, '_is_school_teacher', isset($_POST['is_school_teacher']) ? '1' : '0');
        
        // Update user role based on checkbox
        $user = new WP_User($user_id);
        if (isset($_POST['is_school_teacher'])) {
            $user->add_role('school_teacher');
        } else {
            $user->remove_role('school_teacher');
        }
    }
}
add_action('personal_options_update', 'save_teacher_role_field');
add_action('edit_user_profile_update', 'save_teacher_role_field');

/**
 * School Teacher Admin Menu Restrictions
 */
if (!function_exists('restrict_school_teacher_admin_access')) {
    function restrict_school_teacher_admin_access() {
        if (current_user_can('school_teacher')) {
            // Remove all menus except our two allowed ones
            global $menu;
            
            $allowed_menus = [
                'index.php', // Dashboard
                'lilac-teacher-dashboard', // Our custom menu
                'profile.php', // User profile
            ];
            
            foreach ($menu as $key => $item) {
                if (!in_array($item[2], $allowed_menus)) {
                    remove_menu_page($item[2]);
                }
            }
            
            // Redirect away from disallowed admin pages
            $screen = get_current_screen();
            if ($screen && !in_array($screen->parent_file, $allowed_menus) && !in_array($screen->id, ['profile'])) {
                wp_redirect(admin_url('admin.php?page=lilac-teacher-dashboard'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'restrict_school_teacher_admin_access');

/**
 * Handle fixing users with missing roles or memberships
 */
if (!function_exists('lilac_fix_user_role_membership')) {
    function lilac_fix_user_role_membership() {
        // Only run on our fix page
        if (!isset($_GET['page']) || $_GET['page'] !== 'fix-user-roles' || !current_user_can('manage_options')) {
            return;
        }
        
        // Check if we're processing an action
        if (isset($_GET['action']) && $_GET['action'] === 'fix_all_users') {
            // Verify nonce
            check_admin_referer('fix_user_roles_nonce');
            
            // Get all users with subscriber role
            $users = get_users([
                'role' => 'subscriber',
                'number' => -1,
                'fields' => ['ID', 'user_email']
            ]);
            
            $fixed_count = 0;
            
            // Process each user
            foreach ($users as $user) {
                // Check if they're already in a course
                $enrolled_courses = learndash_user_get_enrolled_courses($user->ID);
                
                if (empty($enrolled_courses)) {
                    // Auto-enroll in first course
                    $fixed = lilac_auto_enroll($user->ID);
                    if ($fixed) {
                        $fixed_count++;
                    }
                }
            }
            
            // Set cookie for success message
            setcookie('lilac_fix_success', $fixed_count, time() + 60, '/');
            
            // Redirect to avoid reprocessing on refresh
            wp_redirect(admin_url('admin.php?page=fix-user-roles&fixed=' . $fixed_count));
            exit;
        }
    }
}
add_action('admin_init', 'lilac_fix_user_role_membership');

/**
 * Show success message after role fix if cookie is set
 */
if (!function_exists('lilac_show_fix_success_message')) {
    function lilac_show_fix_success_message() {
        if (isset($_COOKIE['lilac_fix_success']) && !empty($_COOKIE['lilac_fix_success'])) {
            $count = intval($_COOKIE['lilac_fix_success']);
            echo '<div class="lilac-toast success" style="display:block;">
                <div class="toast-header">Success</div>
                <div class="toast-body">Successfully fixed ' . $count . ' user' . ($count !== 1 ? 's' : '') . '.</div>
            </div>';
            
            // Clear the cookie
            setcookie('lilac_fix_success', '', time() - 3600, '/');
        }
    }
}
add_action('wp_footer', 'lilac_show_fix_success_message');
