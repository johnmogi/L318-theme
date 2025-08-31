<?php
/**
 * Teacher Import Debugger
 * 
 * Provides debugging tools and utilities for teacher import issues
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Import_Debugger {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add debug menu to Tools
        add_action('admin_menu', array($this, 'add_debug_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_fix_teacher_roles', array($this, 'ajax_fix_teacher_roles'));
        add_action('wp_ajax_create_test_group', array($this, 'ajax_create_test_group'));
        add_action('wp_ajax_clear_teacher_cache', array($this, 'ajax_clear_teacher_cache'));
    }
    
    /**
     * Add debug menu
     */
    public function add_debug_menu() {
        add_management_page(
            'Teacher Import Debugger',
            'Teacher Import Debug',
            'manage_options',
            'teacher-import-debug',
            array($this, 'debug_page')
        );
    }
    
    /**
     * Debug page content
     */
    public function debug_page() {
        ?>
        <div class="wrap">
            <h1>Teacher Import Debugger</h1>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2>System Status</h2>
                </div>
                <div class="inside">
                    <?php $this->show_system_status(); ?>
                </div>
            </div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2>Teacher Analysis</h2>
                </div>
                <div class="inside">
                    <?php $this->show_teacher_analysis(); ?>
                </div>
            </div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2>Group Analysis</h2>
                </div>
                <div class="inside">
                    <?php $this->show_group_analysis(); ?>
                </div>
            </div>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2>Quick Fixes</h2>
                </div>
                <div class="inside">
                    <?php $this->show_quick_fixes(); ?>
                </div>
            </div>
        </div>
        
        <script>
        function executeQuickFix(action) {
            jQuery.post(ajaxurl, {
                action: action,
                _wpnonce: '<?php echo wp_create_nonce('teacher_debug'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Success: ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Show system status
     */
    private function show_system_status() {
        echo '<table class="widefat">';
        echo '<tr><th>Plugin</th><th>Status</th></tr>';
        
        // Check School Manager Lite
        $sml_active = is_plugin_active('school-manager-lite/school-manager-lite.php');
        echo '<tr><td>School Manager Lite</td><td>' . ($sml_active ? '✅ Active' : '❌ Inactive') . '</td></tr>';
        
        // Check LearnDash
        $ld_active = is_plugin_active('sfwd-lms/sfwd_lms.php');
        echo '<tr><td>LearnDash</td><td>' . ($ld_active ? '✅ Active' : '❌ Inactive') . '</td></tr>';
        
        // Check mu-plugins
        $mu_plugins = array(
            'safe-teacher-import.php' => 'Safe Teacher Import',
            'teacher-visibility-fix.php' => 'Teacher Visibility Fix',
            'cardcom-js-fix.php' => 'CardCom JS Fix',
            'learndash-layout-fix.php' => 'LearnDash Layout Fix'
        );
        
        foreach ($mu_plugins as $file => $name) {
            $exists = file_exists(WPMU_PLUGIN_DIR . '/' . $file);
            echo '<tr><td>' . $name . '</td><td>' . ($exists ? '✅ Active' : '❌ Missing') . '</td></tr>';
        }
        
        echo '</table>';
        
        // Memory and execution info
        echo '<h4>Server Resources</h4>';
        echo '<table class="widefat">';
        echo '<tr><td>PHP Memory Limit</td><td>' . ini_get('memory_limit') . '</td></tr>';
        echo '<tr><td>PHP Max Execution Time</td><td>' . ini_get('max_execution_time') . 's</td></tr>';
        echo '<tr><td>WordPress Memory Limit</td><td>' . WP_MEMORY_LIMIT . '</td></tr>';
        echo '</table>';
    }
    
    /**
     * Show teacher analysis
     */
    private function show_teacher_analysis() {
        $instructor_roles = array('wdm_instructor', 'school_teacher', 'group_leader', 'instructor', 'Instructor', 'stm_lms_instructor');
        
        echo '<table class="widefat">';
        echo '<tr><th>Role</th><th>Count</th><th>Users</th></tr>';
        
        $total_teachers = 0;
        foreach ($instructor_roles as $role) {
            $users = get_users(array('role' => $role));
            $count = count($users);
            $total_teachers += $count;
            
            $user_names = array();
            foreach ($users as $user) {
                $user_names[] = $user->display_name . ' (' . $user->user_login . ')';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($role) . '</td>';
            echo '<td>' . $count . '</td>';
            echo '<td>' . implode(', ', array_slice($user_names, 0, 5));
            if (count($user_names) > 5) {
                echo ' <em>... and ' . (count($user_names) - 5) . ' more</em>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '<tr><td><strong>Total</strong></td><td><strong>' . $total_teachers . '</strong></td><td></td></tr>';
        echo '</table>';
        
        // Show users with instructor capabilities but wrong roles
        $cap_users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'school_teacher',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        if (!empty($cap_users)) {
            echo '<h4>Users with instructor capabilities:</h4>';
            echo '<ul>';
            foreach ($cap_users as $user) {
                echo '<li>' . $user->display_name . ' (' . $user->user_login . ') - Roles: ' . implode(', ', $user->roles) . '</li>';
            }
            echo '</ul>';
        }
    }
    
    /**
     * Show group analysis
     */
    private function show_group_analysis() {
        $groups = get_posts(array(
            'post_type' => 'groups',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        echo '<table class="widefat">';
        echo '<tr><th>Group</th><th>Leaders</th><th>Members</th><th>Class ID</th></tr>';
        
        foreach ($groups as $group) {
            $leaders = get_post_meta($group->ID, 'ld_group_leaders', true);
            $members = learndash_get_groups_users($group->ID);
            $class_id = get_post_meta($group->ID, 'class_id', true);
            
            $leader_names = array();
            if (is_array($leaders)) {
                foreach ($leaders as $leader_id) {
                    $leader = get_user_by('id', $leader_id);
                    if ($leader) {
                        $leader_names[] = $leader->display_name;
                    }
                }
            }
            
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($group->ID) . '">' . $group->post_title . '</a></td>';
            echo '<td>' . implode(', ', $leader_names) . '</td>';
            echo '<td>' . count($members) . '</td>';
            echo '<td>' . ($class_id ? $class_id : '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '<p><strong>Total Groups:</strong> ' . count($groups) . '</p>';
    }
    
    /**
     * Show quick fixes
     */
    private function show_quick_fixes() {
        echo '<div class="quick-fixes">';
        echo '<button type="button" class="button button-primary" onclick="executeQuickFix(\'fix_teacher_roles\')">Fix Teacher Roles</button> ';
        echo '<button type="button" class="button" onclick="executeQuickFix(\'create_test_group\')">Create Test Group</button> ';
        echo '<button type="button" class="button" onclick="executeQuickFix(\'clear_teacher_cache\')">Clear Teacher Cache</button>';
        echo '</div>';
        
        echo '<div style="margin-top: 15px;">';
        echo '<h4>Manual Actions:</h4>';
        echo '<ul>';
        echo '<li><strong>Fix Teacher Roles:</strong> Ensures all users with instructor capabilities have the wdm_instructor role</li>';
        echo '<li><strong>Create Test Group:</strong> Creates a test group to verify group creation is working</li>';
        echo '<li><strong>Clear Teacher Cache:</strong> Clears WordPress user cache to refresh teacher lists</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * AJAX: Fix teacher roles
     */
    public function ajax_fix_teacher_roles() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'teacher_debug')) {
            wp_send_json_error('Security check failed');
        }
        
        $fixed_count = 0;
        
        // Find users with instructor capabilities but missing wdm_instructor role
        $users = get_users(array('fields' => 'all'));
        
        foreach ($users as $user) {
            $needs_fix = false;
            
            // Check if user has instructor-related capabilities
            $instructor_caps = array('school_teacher', 'manage_school_students', 'access_teacher_dashboard', 'group_leader');
            foreach ($instructor_caps as $cap) {
                if ($user->has_cap($cap)) {
                    $needs_fix = true;
                    break;
                }
            }
            
            // Check if user has instructor-related roles
            $instructor_roles = array('school_teacher', 'group_leader', 'instructor', 'Instructor', 'stm_lms_instructor');
            foreach ($instructor_roles as $role) {
                if (in_array($role, $user->roles)) {
                    $needs_fix = true;
                    break;
                }
            }
            
            if ($needs_fix && !in_array('wdm_instructor', $user->roles)) {
                $user->add_role('wdm_instructor');
                $fixed_count++;
                error_log("Teacher Import Debugger: Added wdm_instructor role to user {$user->ID} ({$user->user_login})");
            }
        }
        
        wp_send_json_success(array(
            'message' => "Fixed {$fixed_count} teacher roles"
        ));
    }
    
    /**
     * AJAX: Create test group
     */
    public function ajax_create_test_group() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'teacher_debug')) {
            wp_send_json_error('Security check failed');
        }
        
        $group_data = array(
            'post_title' => 'Test Group ' . date('Y-m-d H:i:s'),
            'post_type' => 'groups',
            'post_status' => 'publish',
            'post_content' => 'This is a test group created by the Teacher Import Debugger'
        );
        
        $group_id = wp_insert_post($group_data);
        
        if (is_wp_error($group_id)) {
            wp_send_json_error('Failed to create test group: ' . $group_id->get_error_message());
        }
        
        // Add some meta
        update_post_meta($group_id, 'class_id', 999);
        update_post_meta($group_id, '_test_group', true);
        
        wp_send_json_success(array(
            'message' => "Created test group with ID {$group_id}"
        ));
    }
    
    /**
     * AJAX: Clear teacher cache
     */
    public function ajax_clear_teacher_cache() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'teacher_debug')) {
            wp_send_json_error('Security check failed');
        }
        
        // Clear various caches
        wp_cache_delete('users', 'users');
        wp_cache_flush();
        
        // Clear transients
        delete_transient('school_manager_teachers');
        delete_transient('learndash_groups');
        
        wp_send_json_success(array(
            'message' => 'Teacher cache cleared successfully'
        ));
    }
}

// Initialize the debugger
Teacher_Import_Debugger::instance();
