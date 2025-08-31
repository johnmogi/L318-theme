# Teacher Role Management

## WP-CLI Commands

1. **List all users with teacher role**:
```
wp user list --role=school_teacher --fields=ID,user_login,display_name,user_email
```

2. **Check capabilities of teacher role**:
```
wp role list school_teacher
```

## Admin Access Configuration

Teachers should only access:
1. **LearnDash** components:
   - Courses
   - Lessons
   - Quizzes
   - Assignments

2. **Student management**:
   - Progress tracking
   - Grading

## Teacher Role Implementation

### Core Functionality
1. **Login Redirection**:
   - Teachers are redirected to `/wp-admin/admin.php?page=teacher-dashboard`
   - Handles both direct admin login and frontend redirects

2. **Access Control**:
   - Can access Registration Codes page
   - Limited LearnDash access (view-only for courses)
   - Clean admin menu with only essential items

### Code Implementation
```php
// Teacher role capabilities and login redirection
add_action('init', function() {
    $role = get_role('school_teacher');
    if ($role) {
        $role->add_cap('manage_registration_codes');
        $role->add_cap('view_teacher_dashboard');
    }
});

// Redirect teachers after login
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user) {
    if (isset($user->roles) && in_array('school_teacher', $user->roles)) {
        return admin_url('admin.php?page=teacher-dashboard');
    }
    return $redirect_to;
}, 10, 3);

// Fix teacher access to Registration Codes page
add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
    if (isset($user->roles) && in_array('school_teacher', $user->roles)) {
        $allcaps['manage_registration_codes'] = true;
        
        if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'registration-codes') {
            foreach ($caps as $cap) {
                $allcaps[$cap] = true;
            }
        }
    }
    return $allcaps;
}, 99, 4);

// Admin menu restrictions
add_action('admin_init', function() {
    if (current_user_can('school_teacher')) {
        $allowed_menus = [
            'index.php',
            'admin.php?page=teacher-dashboard',
            'admin.php?page=registration-codes',
            'learndash-lms'
        ];
        
        foreach ((array)$GLOBALS['menu'] as $key => $item) {
            if (!in_array($item[2], $allowed_menus)) {
                remove_menu_page($item[2]);
            }
        }
    }
});

// Suppress translation notices in debug.log
if (defined('WP_DEBUG') && WP_DEBUG) {
    set_error_handler(function($errno, $errstr) {
        if ($errno === E_NOTICE && 
            (strpos($errstr, 'woocommerce') !== false || 
             strpos($errstr, 'learndash') !== false)) {
            return true;
        }
        return false;
    }, E_NOTICE);
}
```

### CSV Import Template
```csv
phone,email,first_name,last_name,courses
"050-1234567",teacher1@school.edu,David,Cohen,"Math-101,Science-202"
"0529876543",teacher2@school.edu,Sarah,Levi,"English-103"
```

## Recommended Code Snippet

Add to `functions.php`:
```php
add_action('admin_init', function() {
    if (current_user_can('school_teacher')) {
        // Remove unnecessary menu items
        remove_menu_page('edit-comments.php');
        remove_menu_page('tools.php');
        
        // Show only LearnDash and essential items
        if (!current_user_can('manage_options')) {
            remove_menu_page('index.php');
            remove_menu_page('edit.php');
        }
    }
});
```

## Teacher Role Management Updates

### Phone Login Debugging
Added debug logging for phone-based login attempts to track:
- Failed login attempts
- Phone number matching in user meta
- User lookup results

### Dashboard Redirect Fix
Implemented automatic redirect for teachers:
- From: `/teacher-dashboard/` (frontend)
- To: `/wp-admin/admin.php?page=teacher-dashboard` (backend)

## CSV Import for Teachers

### CSV Template Format
File: `dev/teacher.csv`
```csv
phone,email,first_name,last_name,courses
"050-1234567",teacher1@school.edu,David,Cohen,"Math-101,Science-202"
"0529876543",teacher2@school.edu,Sarah,Levi,"English-103"
```

### Import Implementation
The CSV import feature is being implemented through the MessagingAdmin system:
- Access through admin menu
- Supports various phone formats (with/without dashes)
- Automatically assigns the school_teacher role
- Optional email field (generated if not provided)

## Verification Steps
1. Log in as teacher
2. Confirm only authorized sections appear
3. Test all required functionality remains accessible
4. Verify import system properly adds and configures teachers



https://test-li.ussl.co.il/wp-admin/admin.php?page=registration-codes
no teacher role and no redirection to teacher dashboard also the csv didnt asign the role
does the role exists?
