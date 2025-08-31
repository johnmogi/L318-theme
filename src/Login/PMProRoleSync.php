<?php
/**
 * PMPro Role Sync
 *
 * Syncs WordPress roles with PMPro membership levels to ensure proper access control
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PMProRoleSync {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Map roles to membership levels
     */
    private $role_level_map;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Map roles to membership levels
        $this->role_level_map = array(
            'school_student' => 3, // Adjust these IDs to match your PMPro levels
            'student_private' => 4,
            'school_teacher' => 5
        );
        
        // Add hooks for syncing - more aggressive approach
        add_action('init', array($this, 'check_user_roles_and_sync'), 5); // Higher priority (lower number)
        add_action('wp_login', array($this, 'sync_on_login'), 10, 2);
        add_action('set_user_role', array($this, 'sync_role_to_membership'), 10, 3);
        add_action('add_user_role', array($this, 'sync_added_role_to_membership'), 10, 2);
        add_action('user_register', array($this, 'sync_on_registration'), 5);
        
        // Direct database sync for cases where PMPro functions might fail
        add_action('admin_init', array($this, 'sync_all_users_memberships'));
        
        // Debug functions - only for admins
        if (current_user_can('manage_options') || (isset($_GET['debug_pmpro']) && $_GET['debug_pmpro'] == 1)) {
            add_action('wp_footer', array($this, 'debug_pmpro_access'), 999);
        }
        
        // Add bypass functionality for testing
        add_filter('pmpro_has_membership_access_filter', array($this, 'maybe_bypass_pmpro'), 999, 4);
    }
    
    /**
     * Sync membership on login
     */
    public function sync_on_login($user_login, $user) {
        $this->sync_user_memberships($user, true); // Force sync on login
    }

    /**
     * NEW: Sync membership immediately on user registration
     * This ensures users have PMPro access from the moment they register
     */
    public function sync_on_registration($user_id) {
        // Get the newly registered user
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        // Try to sync their membership based on role
        // Force direct DB sync for new registrations to ensure it works
        $result = $this->sync_user_memberships($user, true);
    }
    
    /**
     * Check user roles and sync with PMPro memberships if needed
     */
    public function check_user_roles_and_sync() {
        // Only for logged in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Check if user has roles that should have PMPro membership
        $this->sync_user_memberships($user);
    }
    
    /**
     * When a role is set, sync with PMPro membership
     */
    public function sync_role_to_membership($user_id, $new_role, $old_roles) {
        $user = get_userdata($user_id);
        $this->sync_user_memberships($user);
    }
    
    /**
     * When a role is added, sync with PMPro membership
     */
    public function sync_added_role_to_membership($user_id, $role) {
        $user = get_userdata($user_id);
        $this->sync_user_memberships($user);
    }
    
    /**
     * Force sync all users' roles to PMPro memberships (admin only)
     */
    public function sync_all_users_memberships() {
        // Only on admin request with proper permissions
        if (!current_user_can('manage_options') || !isset($_GET['force_sync_all_pmpro'])) {
            return;
        }
        
        // Get all users
        $users = get_users(array(
            'fields' => array('ID'),
        ));
        
        $synced = 0;
        foreach ($users as $user) {
            $user_obj = get_userdata($user->ID);
            if ($this->sync_user_memberships($user_obj, true)) {
                $synced++;
            }
        }
        
        // Show admin notice
        add_action('admin_notices', function() use ($synced) {
            echo '<div class="notice notice-success"><p>' . sprintf(__('Synced %d users to PMPro memberships.', 'hello-child'), $synced) . '</p></div>';
        });
    }
    
    /**
     * Check user roles and add appropriate PMPro memberships
     * 
     * @param object $user WP_User object
     * @param bool $force Force sync even if membership exists
     * @return bool Whether sync happened
     */
    private function sync_user_memberships($user, $force = false) {
        if (!$user) {
            return false;
        }
        
        $roles = (array) $user->roles;
        $current_level_id = $this->get_pmpro_level_id($user->ID);
        $synced = false;
        
        // First try using PMPro functions if available
        if (function_exists('pmpro_changeMembershipLevel')) {
            // Check each role against our map
            foreach ($this->role_level_map as $role => $level_id) {
                if (in_array($role, $roles) && ($current_level_id != $level_id || $force)) {
                    // User has role but not matching membership level
                    $result = pmpro_changeMembershipLevel($level_id, $user->ID);
                    
                    // Set an expiration date 1 year from now
                    $expiration_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                    update_user_meta($user->ID, 'membership_expiry', $expiration_date);
                    
                    $synced = true;
                    
                    // Only apply the first matching role to avoid overwriting
                    break;
                }
            }
        }
        
        // If PMPro functions didn't work or force is true, try direct database method
        // Changed this condition to be more aggressive about direct DB sync
        if (!$synced) {
            $synced = $this->direct_db_sync($user, $roles);
        }
        
        return $synced;
    }
    
    /**
     * Direct database sync for when PMPro functions fail
     * This is a fallback method that directly writes to the PMPro tables
     */
    private function direct_db_sync($user, $roles) {
        global $wpdb;
        if (!$user) {
            return false;
        }
        
        // Determine which level to assign
        $level_id = null;
        foreach ($this->role_level_map as $role => $lid) {
            if (in_array($role, $roles)) {
                $level_id = $lid;
                break;
            }
        }
        
        if (!$level_id) {
            return false;
        }
        
        // Check if membership record already exists
        $membership_table = $wpdb->prefix . 'pmpro_memberships_users';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $membership_table WHERE user_id = %d AND status = 'active'",
            $user->ID
        ));
        
        if ($exists) {
            // Update existing membership
            $result = $wpdb->update(
                $membership_table,
                array(
                    'membership_id' => $level_id,
                    'modified' => current_time('mysql'),
                ),
                array('id' => $exists),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new membership
            $result = $wpdb->insert(
                $membership_table,
                array(
                    'user_id' => $user->ID,
                    'membership_id' => $level_id,
                    'status' => 'active',
                    'startdate' => current_time('mysql'),
                    'modified' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
        
        // Set an expiration date 1 year from now
        $expiration_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        update_user_meta($user->ID, 'membership_expiry', $expiration_date);
        
        return $result ? true : false;
    }
    
    /**
     * Get current PMPro level ID for a user
     */
    private function get_pmpro_level_id($user_id) {
        if (!function_exists('pmpro_getMembershipLevelForUser')) {
            return 0;
        }
        
        $level = pmpro_getMembershipLevelForUser($user_id);
        return $level ? $level->id : 0;
    }
    
    /**
     * Debug PMPro access information in footer for admins
     */
    public function debug_pmpro_access() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $roles = (array) $user->roles;
        
        $current_level_id = $this->get_pmpro_level_id($user_id);
        
        echo '<div class="lilac-pmpro-debug" style="max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; direction: ltr; text-align: left; font-family: monospace;">';
        echo '<h3>PMPro Access Debug</h3>';
        
        echo '<ul>';
        echo '<li><strong>User ID:</strong> ' . esc_html($user_id) . '</li>';
        echo '<li><strong>Username:</strong> ' . esc_html($user->user_login) . '</li>';
        echo '<li><strong>Display Name:</strong> ' . esc_html($user->display_name) . '</li>';
        echo '<li><strong>Roles:</strong> ' . esc_html(implode(', ', $roles)) . '</li>';
        echo '<li><strong>Current PMPro Level ID:</strong> ' . esc_html($current_level_id) . '</li>';
        
        // Check if any of user's roles should have PMPro level
        $should_have_level = false;
        foreach ($this->role_level_map as $role => $level_id) {
            if (in_array($role, $roles)) {
                echo '<li><strong>Role ' . esc_html($role) . ' should map to:</strong> Level ' . esc_html($level_id) . '</li>';
                $should_have_level = true;
            }
        }
        
        if (!$should_have_level) {
            echo '<li><strong>No roles mapped to PMPro levels</strong></li>';
        }
        
        // Check if membership record exists in database
        global $wpdb;
        $membership_table = $wpdb->prefix . 'pmpro_memberships_users';
        $membership_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $membership_table WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        if ($membership_record) {
            echo '<li><strong>PMPro DB Record:</strong> Found (ID: ' . esc_html($membership_record->id) . ')</li>';
            echo '<li><strong>PMPro Level in DB:</strong> ' . esc_html($membership_record->membership_id) . '</li>';
            echo '<li><strong>PMPro Status:</strong> ' . esc_html($membership_record->status) . '</li>';
            echo '<li><strong>PMPro Start Date:</strong> ' . esc_html($membership_record->startdate) . '</li>';
        } else {
            echo '<li><strong>PMPro DB Record:</strong> Not Found!</li>';
        }
        
        // Show membership expiry date
        $expiration_date = get_user_meta($user_id, 'membership_expiry', true);
        echo '<li><strong>Membership Expiry:</strong> ' . ($expiration_date ? esc_html($expiration_date) : 'Not set') . '</li>';
        
        // Check if we can access current page with PMPro
        if (function_exists('pmpro_has_membership_access')) {
            global $post;
            if ($post) {
                $has_access = pmpro_has_membership_access($post->ID, $user_id);
                $access_status = is_array($has_access) ? $has_access[0] : $has_access;
                echo '<li><strong>Has access to current page:</strong> ' . ($access_status ? 'Yes' : 'No') . '</li>';
                
                // Get required level for this page
                $required_levels = get_post_meta($post->ID, '_pmpro_required_level', true);
                if ($required_levels) {
                    if (is_array($required_levels)) {
                        echo '<li><strong>Required levels for this page:</strong> ' . esc_html(implode(', ', $required_levels)) . '</li>';
                    } else {
                        echo '<li><strong>Required level for this page:</strong> ' . esc_html($required_levels) . '</li>';
                    }
                }
            }
        }
        
        echo '</ul>';
        
        echo '<div class="lilac-pmpro-debug-actions" style="margin-top: 10px;">';
        echo '<form method="post">';
        echo '<input type="hidden" name="lilac_force_sync_pmpro" value="1" />';
        echo '<input type="submit" value="Force Sync Role to PMPro" style="padding: 5px 10px; margin-right: 10px;" />';
        echo '</form>';
        
        echo '<p>Add <code>?debug_pmpro=1</code> to any URL to see this debug panel.</p>';
        
        // Direct link to access the page without PMPro checks (admin only)
        if (current_user_can('manage_options') && isset($post)) {
            echo '<p><a href="' . esc_url(add_query_arg('pmpro_bypass', '1', get_permalink($post->ID))) . '" style="color: #d63638;">Bypass PMPro (Admin Only)</a></p>';
        }
        
        echo '</div>';
        
        echo '</div>';
        
        // Handle force sync action
        if (isset($_POST['lilac_force_sync_pmpro'])) {
            $this->sync_user_memberships($user, true);
            echo '<script>
                alert("PMPro membership sync attempted. Please refresh page to see updated status.");
                window.location.reload();
            </script>';
        }
    }

    /**
     * Bypass PMPro access checks for admins
     */
    public function maybe_bypass_pmpro($hasaccess, $post_id, $user_id, $levels) {
        // Allow admins to bypass with query param
        if (isset($_GET['pmpro_bypass']) && current_user_can('manage_options')) {
            return true;
        }
        return $hasaccess;
    }
}

/**
 * Initialize the PMPro role sync functionality
 */
function lilac_pmpro_role_sync() {
    return PMProRoleSync::get_instance();
}

lilac_pmpro_role_sync();
