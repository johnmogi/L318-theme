<?php
/**
 * LearnDash Groups Integration
 * 
 * Bridges School Manager classes with LearnDash Groups and ensures instructors can see their groups
 */

if (!defined('ABSPATH')) {
    exit;
}

class LearnDash_Groups_Integration {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only initialize if LearnDash is active
        add_action('plugins_loaded', array($this, 'init'), 20);
    }
    
    public function init() {
        if (!class_exists('SFWD_LMS')) {
            error_log("LearnDash Groups Integration: LearnDash not active");
            return;
        }
        
        // Hook into class assignments to create/sync LearnDash groups
        add_action('school_manager_after_assign_teacher_to_class', array($this, 'sync_class_to_group'), 10, 2);
        
        // Hook into teacher dashboard to show LearnDash groups
        add_filter('school_manager_teacher_groups', array($this, 'get_teacher_groups'), 10, 2);
        
        // Ensure instructors can see their groups in LearnDash
        add_filter('learndash_groups_user_can_edit', array($this, 'instructor_can_edit_group'), 10, 3);
        
        // Add groups to teacher dashboard
        add_action('school_manager_teacher_dashboard_after_classes', array($this, 'display_teacher_groups'));
        
        // Sync existing classes to groups
        add_action('admin_init', array($this, 'maybe_sync_existing_classes'));
        
        // Handle manual group creation from classes
        add_action('wp_ajax_create_group_from_class', array($this, 'create_group_from_class'));
        
        error_log("LearnDash Groups Integration: Initialized successfully");
    }
    
    /**
     * Sync School Manager class to LearnDash group when teacher is assigned
     */
    public function sync_class_to_group($teacher_id, $class_id) {
        global $wpdb;
        
        // Get class information
        $class = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}school_classes WHERE id = %d",
            $class_id
        ));
        
        if (!$class) {
            error_log("LearnDash Groups Integration: Class {$class_id} not found");
            return;
        }
        
        // Check if group already exists for this class
        $existing_group = get_posts(array(
            'post_type' => 'groups',
            'meta_query' => array(
                array(
                    'key' => '_school_manager_class_id',
                    'value' => $class_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_group)) {
            $group_id = $existing_group[0]->ID;
            error_log("LearnDash Groups Integration: Found existing group {$group_id} for class {$class_id}");
        } else {
            // Create new LearnDash group
            $group_data = array(
                'post_title' => $class->name,
                'post_content' => sprintf(__('Group for class: %s', 'school-manager-lite'), $class->name),
                'post_status' => 'publish',
                'post_type' => 'groups',
                'post_author' => $teacher_id
            );
            
            $group_id = wp_insert_post($group_data);
            
            if (is_wp_error($group_id)) {
                error_log("LearnDash Groups Integration: Error creating group: " . $group_id->get_error_message());
                return;
            }
            
            // Link group to class
            update_post_meta($group_id, '_school_manager_class_id', $class_id);
            
            error_log("LearnDash Groups Integration: Created new group {$group_id} for class {$class_id}");
        }
        
        // Set group leader (instructor)
        update_post_meta($group_id, 'learndash_group_leaders', array($teacher_id));
        
        // Add students from class to group
        $students = $wpdb->get_col($wpdb->prepare(
            "SELECT wp_user_id FROM {$wpdb->prefix}school_students WHERE class_id = %d",
            $class_id
        ));
        
        if (!empty($students)) {
            update_post_meta($group_id, 'learndash_group_users', $students);
            error_log("LearnDash Groups Integration: Added " . count($students) . " students to group {$group_id}");
        }
        
        return $group_id;
    }
    
    /**
     * Get teacher's LearnDash groups
     */
    public function get_teacher_groups($groups, $teacher_id) {
        // Get groups where teacher is a leader
        $teacher_groups = get_posts(array(
            'post_type' => 'groups',
            'meta_query' => array(
                array(
                    'key' => 'learndash_group_leaders',
                    'value' => serialize(strval($teacher_id)),
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => -1
        ));
        
        // Also get groups created by teacher
        $authored_groups = get_posts(array(
            'post_type' => 'groups',
            'author' => $teacher_id,
            'posts_per_page' => -1
        ));
        
        // Merge and deduplicate
        $all_groups = array_merge($teacher_groups, $authored_groups);
        $unique_groups = array();
        
        foreach ($all_groups as $group) {
            $unique_groups[$group->ID] = $group;
        }
        
        error_log("LearnDash Groups Integration: Found " . count($unique_groups) . " groups for teacher {$teacher_id}");
        
        return array_values($unique_groups);
    }
    
    /**
     * Allow instructors to edit their groups
     */
    public function instructor_can_edit_group($can_edit, $group_id, $user_id) {
        if ($can_edit) {
            return $can_edit; // Already has permission
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user || !in_array('wdm_instructor', $user->roles)) {
            return $can_edit;
        }
        
        // Check if instructor is group leader
        $group_leaders = get_post_meta($group_id, 'learndash_group_leaders', true);
        if (is_array($group_leaders) && in_array($user_id, $group_leaders)) {
            return true;
        }
        
        // Check if instructor is author of the group
        $group = get_post($group_id);
        if ($group && $group->post_author == $user_id) {
            return true;
        }
        
        return $can_edit;
    }
    
    /**
     * Display teacher's groups in dashboard
     */
    public function display_teacher_groups() {
        $teacher_id = get_current_user_id();
        $groups = $this->get_teacher_groups(array(), $teacher_id);
        
        if (empty($groups)) {
            return;
        }
        
        ?>
        <div class="teacher-groups-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h2><?php _e('My LearnDash Groups', 'school-manager-lite'); ?></h2>
            
            <div class="groups-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <?php foreach ($groups as $group) : 
                    // Get group members count
                    $group_users = get_post_meta($group->ID, 'learndash_group_users', true);
                    $members_count = is_array($group_users) ? count($group_users) : 0;
                    
                    // Check if linked to School Manager class
                    $class_id = get_post_meta($group->ID, '_school_manager_class_id', true);
                ?>
                    <div class="group-card" style="background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px; padding: 15px;">
                        <h3 style="margin: 0 0 10px 0; color: #0073aa;">
                            <a href="<?php echo admin_url('post.php?post=' . $group->ID . '&action=edit'); ?>" style="text-decoration: none; color: inherit;">
                                <?php echo esc_html($group->post_title); ?>
                            </a>
                        </h3>
                        <div class="group-stats" style="display: flex; gap: 20px; font-size: 14px; color: #646970; margin-bottom: 10px;">
                            <div class="group-stat">
                                <span class="dashicons dashicons-groups" style="font-size: 16px; vertical-align: middle;"></span>
                                <span><?php printf(_n('%d Member', '%d Members', $members_count, 'school-manager-lite'), $members_count); ?></span>
                            </div>
                            <?php if ($class_id) : ?>
                                <div class="group-stat">
                                    <span class="dashicons dashicons-admin-links" style="font-size: 16px; vertical-align: middle;"></span>
                                    <span><?php printf(__('Class ID: %s', 'school-manager-lite'), $class_id); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="group-actions">
                            <a href="<?php echo admin_url('post.php?post=' . $group->ID . '&action=edit'); ?>" class="button button-small">
                                <?php _e('Manage Group', 'school-manager-lite'); ?>
                            </a>
                            <?php if (function_exists('learndash_get_group_leader_manage_groups_url')) : ?>
                                <a href="<?php echo learndash_get_group_leader_manage_groups_url($group->ID); ?>" class="button button-small">
                                    <?php _e('Group Reports', 'school-manager-lite'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="groups-summary" style="background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px; padding: 15px;">
                <strong><?php printf(_n('You manage %d LearnDash group', 'You manage %d LearnDash groups', count($groups), 'school-manager-lite'), count($groups)); ?></strong>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sync existing classes to groups (run once)
     */
    public function maybe_sync_existing_classes() {
        // Only run this once per day to avoid performance issues
        $last_sync = get_option('learndash_groups_integration_last_sync', 0);
        if (time() - $last_sync < DAY_IN_SECONDS) {
            return;
        }
        
        global $wpdb;
        
        // Get all classes with teachers
        $classes_with_teachers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}school_classes WHERE teacher_id IS NOT NULL AND teacher_id > 0"
        );
        
        foreach ($classes_with_teachers as $class) {
            // Check if group already exists
            $existing_group = get_posts(array(
                'post_type' => 'groups',
                'meta_query' => array(
                    array(
                        'key' => '_school_manager_class_id',
                        'value' => $class->id,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if (empty($existing_group)) {
                $this->sync_class_to_group($class->teacher_id, $class->id);
                error_log("LearnDash Groups Integration: Synced existing class {$class->id} to group");
            }
        }
        
        update_option('learndash_groups_integration_last_sync', time());
        error_log("LearnDash Groups Integration: Completed sync of existing classes");
    }
    
    /**
     * Handle AJAX request to create group from class
     */
    public function create_group_from_class() {
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'school-manager-lite'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'create_group_from_class')) {
            wp_die(__('Security check failed.', 'school-manager-lite'));
        }
        
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        $teacher_id = get_current_user_id();
        
        if (!$class_id) {
            wp_send_json_error(__('Invalid class ID.', 'school-manager-lite'));
        }
        
        $group_id = $this->sync_class_to_group($teacher_id, $class_id);
        
        if ($group_id) {
            wp_send_json_success(array(
                'message' => __('LearnDash group created successfully.', 'school-manager-lite'),
                'group_id' => $group_id,
                'edit_url' => admin_url('post.php?post=' . $group_id . '&action=edit')
            ));
        } else {
            wp_send_json_error(__('Failed to create LearnDash group.', 'school-manager-lite'));
        }
    }
}

// Initialize the integration
LearnDash_Groups_Integration::instance();

// Add hook to trigger group sync when teacher is assigned to class
add_action('school_manager_after_assign_teacher_to_class', function($teacher_id, $class_id) {
    do_action('school_manager_after_assign_teacher_to_class', $teacher_id, $class_id);
}, 10, 2);

// Ensure wdm_instructor role has group management capabilities
add_action('init', function() {
    $role = get_role('wdm_instructor');
    if ($role) {
        $role->add_cap('edit_groups');
        $role->add_cap('edit_others_groups');
        $role->add_cap('publish_groups');
        $role->add_cap('read_private_groups');
        $role->add_cap('delete_groups');
        $role->add_cap('delete_others_groups');
        $role->add_cap('group_leader');
    }
});

// Add admin notice about groups integration
add_action('admin_notices', function() {
    if (!current_user_can('wdm_instructor')) {
        return;
    }
    
    // Check if teacher has classes but no groups
    $teacher_id = get_current_user_id();
    global $wpdb;
    
    $classes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}school_classes WHERE teacher_id = %d",
        $teacher_id
    ));
    
    if ($classes_count > 0) {
        $groups_count = count(get_posts(array(
            'post_type' => 'groups',
            'author' => $teacher_id,
            'posts_per_page' => -1
        )));
        
        if ($groups_count == 0) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('LearnDash Groups Integration', 'school-manager-lite'); ?></strong><br>
                    <?php printf(
                        __('You have %d classes assigned but no LearnDash groups. Groups will be automatically created when you access your Teacher Dashboard.', 'school-manager-lite'),
                        $classes_count
                    ); ?>
                </p>
            </div>
            <?php
        }
    }
});
