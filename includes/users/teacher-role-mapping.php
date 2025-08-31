<?php
/**
 * Teacher-Instructor Role Mapping
 * 
 * Automatically maps school_teacher role to have LearnDash instructor capabilities
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Map school_teacher users to also have the stm_lms_instructor role and capabilities
 *
 * @param int $user_id The user ID to map
 * @return bool True if mapping was successful, false otherwise
 */
function map_school_teacher_to_instructor($user_id) {
    if (!$user_id || !is_numeric($user_id)) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    // Log the process for debugging
    custom_log('Running teacher-instructor mapping for user #' . $user_id);

    // Check if user has school_teacher role
    if (!in_array('school_teacher', (array)$user->roles)) {
        custom_log('User #' . $user_id . ' is not a school_teacher, skipping mapping');
        return false;
    }

    // Get user capabilities
    $user_caps = get_user_meta($user_id, 'wp_capabilities', true);
    if (!is_array($user_caps)) {
        $user_caps = array();
    }

    // Add stm_lms_instructor role to user if they don't already have it
    if (!isset($user_caps['stm_lms_instructor']) || $user_caps['stm_lms_instructor'] !== true) {
        $user_caps['stm_lms_instructor'] = true;
        update_user_meta($user_id, 'wp_capabilities', $user_caps);
        custom_log('Added stm_lms_instructor role to user #' . $user_id);
    }

    // Add LearnDash Group Leader capabilities
    $learndash_caps = array(
        'group_leader',            // LearnDash Group Leader base role
        'view_ld_reports',         // View LearnDash reports
        'view_learndash_reports',  // View LearnDash reports (alternate capability)
        'edit_groups',             // Edit groups
        'edit_published_groups',   // Edit published groups
        'read_groups',             // Read groups
        'enroll_users',            // Enroll users in courses/groups
        'create_groups',           // Create groups
        'view_courses',            // View courses
    );

    // Add WordPress admin dashboard access capabilities
    $admin_caps = array(
        'read',                   // Read capability (required for admin access)
        'edit_dashboard',         // Edit dashboard
        'access_administrator_page', // Access admin page
        'view_admin_dashboard',   // View admin dashboard
        'edit_users',             // Edit users
        'list_users',             // List users
    );

    // Combine all capabilities
    $all_caps = array_merge($learndash_caps, $admin_caps);

    // Add capabilities to user
    $user = new WP_User($user_id);
    foreach ($all_caps as $cap) {
        $user->add_cap($cap);
    }

    custom_log('Added all required capabilities to user #' . $user_id);
    return true;
}

/**
 * Run a one-time update to map all existing school_teacher users
 */
function map_all_existing_school_teachers() {
    $users = get_users(array(
        'role' => 'school_teacher',
        'fields' => 'ID'
    ));

    custom_log('Starting batch mapping for ' . count($users) . ' school_teacher users');

    foreach ($users as $user_id) {
        map_school_teacher_to_instructor($user_id);
    }

    update_option('teacher_instructor_mapping_last_run', time());
    custom_log('Completed batch mapping for school_teacher users');
}

/**
 * Handle new user registration - map roles if needed
 */
add_action('user_register', function($user_id) {
    map_school_teacher_to_instructor($user_id);
});

/**
 * Handle role changes - map roles if needed
 */
add_action('set_user_role', function($user_id, $role, $old_roles) {
    if ($role === 'school_teacher') {
        map_school_teacher_to_instructor($user_id);
    }
}, 10, 3);

/**
 * Handle user login - ensure mapping is correct
 */
add_action('wp_login', function($user_login, $user) {
    if (in_array('school_teacher', (array)$user->roles)) {
        map_school_teacher_to_instructor($user->ID);
    }
}, 10, 2);

/**
 * Add required capabilities to school_teacher role
 */
add_action('admin_init', function() {
    $role = get_role('school_teacher');
    if ($role) {
        $role->add_cap('read');
        $role->add_cap('edit_dashboard');
        $role->add_cap('access_administrator_page');
        $role->add_cap('view_admin_dashboard');
        $role->add_cap('group_leader');
        $role->add_cap('view_ld_reports');
        $role->add_cap('view_learndash_reports');
        $role->add_cap('edit_users');
        $role->add_cap('list_users');
        $role->add_cap('manage_woocommerce');
        $role->add_cap('view_woocommerce_reports');
    }
});

/**
 * Add filter to grant school_teacher users admin access
 */
add_filter('user_has_cap', function($allcaps, $cap, $args) {
    if (isset($args[1]) && $args[1] === 'access_administrator_page' && current_user_can('school_teacher')) {
        $allcaps['access_administrator_page'] = true;
    }
    return $allcaps;
}, 10, 3);

/**
 * Run the mapping on plugin activation or theme switch
 */
add_action('after_switch_theme', 'map_all_existing_school_teachers');

/**
 * Add admin page to run the mapping manually
 */
add_action('admin_menu', function() {
    add_management_page(
        'Teacher Role Mapping',
        'Teacher Role Mapping',
        'manage_options',
        'teacher-role-mapping',
        'teacher_role_mapping_admin_page'
    );
});

/**
 * Admin page callback function
 */
function teacher_role_mapping_admin_page() {
    // Check if we should run the mapping
    if (isset($_POST['map_all_teachers']) && current_user_can('manage_options')) {
        check_admin_referer('map_all_teachers_nonce');
        map_all_existing_school_teachers();
        echo '<div class="notice notice-success"><p>All school_teacher users have been mapped to have instructor capabilities.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Teacher-Instructor Role Mapping</h1>
        <p>This tool manages the automatic role mapping between school_teacher users and LearnDash instructor capabilities.</p>
        
        <div class="card">
            <h2>Map All School Teachers</h2>
            <p>Click the button below to map all existing school_teacher users to have instructor capabilities.</p>
            <form method="post">
                <?php wp_nonce_field('map_all_teachers_nonce'); ?>
                <input type="submit" name="map_all_teachers" class="button button-primary" value="Map All Teachers">
            </form>
        </div>
        
        <div class="card">
            <h2>Role Mapping Status</h2>
            <p>Last mapping run: <?php 
                $last_run = get_option('teacher_instructor_mapping_last_run');
                echo $last_run ? date('F j, Y, g:i a', $last_run) : 'Never';
            ?></p>
            
            <?php
            $teacher_count = count(get_users(['role' => 'school_teacher']));
            echo "<p>Total school_teacher users: $teacher_count</p>";
            ?>
        </div>
    </div>
    <?php
}
