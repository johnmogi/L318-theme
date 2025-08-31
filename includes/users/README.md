# User Groups/Classes System

This module implements a comprehensive group/class management system for LearnDash, allowing you to:

1. Assign users to groups/classes
2. Track group statistics and performance
3. Display group dashboards
4. Manage group-based content access

## Files

### 1. Group Field Manager (`class-group-field-manager.php`)
- Adds a group/class field to user profiles
- Manages group assignments
- Provides helper methods for group-related operations

### 2. Group Statistics (`class-group-statistics.php`)
- Tracks and calculates group performance metrics
- Provides methods to retrieve and display statistics
- Handles data aggregation for groups

### 3. Group Dashboard Shortcode (`class-group-dashboard-shortcode.php`)
- Provides a `[group_dashboard]` shortcode
- Displays group statistics and activity
- Responsive and user-friendly interface

## Usage

### Assigning Users to Groups
1. Go to Users → All Users
2. Edit a user
3. Find the "Class/Group Information" section
4. Select the appropriate group from the dropdown
5. Update the user

### Displaying Group Statistics
Use the following shortcode to display a group dashboard:

```
[group_dashboard group_id="group1"]
```

If no `group_id` is provided, it will use the current user's group.

### Getting Users in a Group (PHP)

```php
$user_ids = Group_Field_Manager::get_users_by_group('group1');
```

### Getting Group Statistics (PHP)

```php
$stats = Group_Statistics::get_group_stats('group1');
```

## Styling

Custom styles are located in:
`/assets/css/group-dashboard.css`

## Extending

### Adding New Groups
Edit the `get_available_groups()` method in `class-group-field-manager.php` to add or modify available groups.

### Adding New Statistics
Extend the `Group_Statistics` class to add new statistical calculations and display methods.

## Dependencies

- WordPress 5.0+
- LearnDash LMS 3.0+
- PHP 7.4+


Warning: Undefined variable $course_url in C:\Users\USUARIO\Documents\SITES\LILAC\LA218\app\public\wp-content\themes\hello-theme-child-master\includes\subscription-activation.php on line 110
