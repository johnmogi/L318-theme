# Teacher-Instructor Role Mapping

This documentation explains the automatic role mapping system that connects the custom `school_teacher` role with LearnDash instructor capabilities.

## Overview

The role mapping system ensures that any user with the `school_teacher` role automatically receives:
1. The `stm_lms_instructor` role
2. LearnDash Group Leader capabilities
3. WordPress admin dashboard access permissions

## Implementation Details

### Files
- `/wp-content/themes/hello-theme-child-master/includes/users/teacher-role-mapping.php` - Main implementation file

### Key Functions

#### `map_school_teacher_to_instructor($user_id)`
- Maps a specific user with the `school_teacher` role to have instructor capabilities
- Adds the `stm_lms_instructor` role if not already present
- Grants LearnDash Group Leader capabilities
- Adds WordPress admin access capabilities

#### `map_all_existing_school_teachers()`
- One-time update function to map all existing users with the `school_teacher` role
- Updates the `teacher_instructor_mapping_last_run` option with the current timestamp

### WordPress Hooks

The role mapping is triggered automatically on several events:

1. **New User Registration**
   ```php
   add_action('user_register', function($user_id) {
       map_school_teacher_to_instructor($user_id);
   });
   ```

2. **Role Changes**
   ```php
   add_action('set_user_role', function($user_id, $role, $old_roles) {
       if ($role === 'school_teacher') {
           map_school_teacher_to_instructor($user_id);
       }
   }, 10, 3);
   ```

3. **User Login**
   ```php
   add_action('wp_login', function($user_login, $user) {
       if (in_array('school_teacher', (array)$user->roles)) {
           map_school_teacher_to_instructor($user->ID);
       }
   }, 10, 2);
   ```

4. **Theme Activation**
   ```php
   add_action('after_switch_theme', 'map_all_existing_school_teachers');
   ```

### Capabilities Added

The following capabilities are added to users with the `school_teacher` role:

#### WordPress Core Capabilities
- `read` - Required for admin dashboard access
- `edit_dashboard` - Edit dashboard
- `access_administrator_page` - Access admin pages
- `view_admin_dashboard` - View the admin dashboard
- `edit_users` - Edit users
- `list_users` - List users

#### LearnDash Group Leader Capabilities
- `group_leader` - LearnDash Group Leader base role
- `view_ld_reports` - View LearnDash reports
- `view_learndash_reports` - View LearnDash reports (alternate capability)
- `edit_groups` - Edit groups
- `edit_published_groups` - Edit published groups
- `read_groups` - Read groups
- `enroll_users` - Enroll users in courses/groups
- `create_groups` - Create groups
- `view_courses` - View courses

#### WooCommerce Capabilities
- `manage_woocommerce` - Manage WooCommerce settings
- `view_woocommerce_reports` - View WooCommerce reports

## Admin Tools

An admin page is provided under "Tools > Teacher Role Mapping" that allows administrators to:

1. Manually run the role mapping for all school teacher users
2. View the last time the mapping was run
3. See the total number of school teacher users in the system

## Database Structure

The system uses standard WordPress user role and capability storage:

- User roles are stored in the `wp_usermeta` table with the meta_key `wp_capabilities`
- The role mapping adds the `stm_lms_instructor` role to this serialized array
- Additional capabilities are added using WordPress `WP_User::add_cap()` method

## Troubleshooting

If a teacher user doesn't have the proper capabilities:

1. Log into the WordPress admin dashboard
2. Go to Tools > Teacher Role Mapping
3. Click "Map All Teachers"
4. Check the user's capabilities in the Users section

## Usage

No manual intervention is required for the role mapping to work. It happens automatically when:

- A new user with the `school_teacher` role is created
- An existing user's role is changed to `school_teacher`
- A `school_teacher` user logs in
- The theme is activated

## Verification

To verify the role mapping is working correctly:

1. Create a new user with the `school_teacher` role
2. Log in as that user
3. Confirm they can access the WordPress admin dashboard
4. Confirm they have LearnDash Group Leader capabilities (can view groups, etc.)
5. Confirm they have the instructor capabilities in STM LMS
