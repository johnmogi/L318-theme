# Teacher Login Redirection Documentation

## Overview

This document explains how teacher login redirection is implemented in the Lilac system. Teachers using the login forms on the website are redirected to the admin dashboard instead of the frontend teacher dashboard.

## Implementation Files

The teacher redirection is implemented across multiple files:

1. **LoginManager.php** - Core login processing logic
2. **minimal-login-shortcode.php** - Simplified login form 
3. **login-page-shortcode.php** - Main login page

## Redirection Flow

### 1. User Authentication

When a user submits credentials through any login form:

- The form includes a hidden field: `<input type="hidden" name="lilac_login_action" value="1" />`
- This triggers `process_login()` in the LoginManager class
- The system attempts to authenticate using username/email/phone and password
- Custom authentication logic in `maybe_use_phone_number()` allows phone-based login

### 2. Role-Based Redirection

After successful authentication:

1. The system determines the user's role(s)
2. In `get_redirect_url()`, different redirect logic is applied based on role:
   - **school_student**: Redirected to student dashboard or courses page
   - **student_private**: Redirected to private student dashboard
   - **school_teacher**: Redirected to admin dashboard (`/wp-admin/admin.php?page=teacher-dashboard`)

### 3. Admin Dashboard Access

For teachers:

- The admin dashboard contains a clean interface with limited menu items
- Teachers can only access specific sections like Registration Codes and LearnDash
- The dashboard page provides teacher-specific functionality

## For Developers

### Adding New Login Forms

When implementing new login forms, ensure:

1. The form includes `<input type="hidden" name="lilac_login_action" value="1" />`
2. Include a nonce field: `<?php wp_nonce_field('lilac_login_action', 'lilac_login_nonce'); ?>`
3. Add the teacher redirect field: `<input type="hidden" name="teacher_redirect" value="admin" />`

### Modifying Redirection Logic

To modify redirection behavior:

1. Update the `get_redirect_url()` method in LoginManager.php
2. Use the `lilac_login_redirect` filter for custom redirection logic

## Testing Recommendations

To test teacher redirection:

1. Create a test teacher account with the "school_teacher" role
2. Test login from various forms (minimal login, main login page)
3. Verify redirection to `/wp-admin/admin.php?page=teacher-dashboard`
4. Confirm appropriate admin menu items are displayed/hidden
5. Test with different URLs in the browser address bar to ensure consistency

## Related WordPress Hooks

The following WordPress hooks are used in the implementation:

- `authenticate` - Filter used to allow phone-based authentication
- `login_redirect` - Filter that controls where users are redirected after login
- `init` - Action hook where login processing is attached
- `admin_init` - Used to restrict admin menu items for teachers

## Teacher Dashboard Access Control

Teachers have restricted access in the admin area:

```php
$allowed_menus = [
    'index.php',
    'admin.php?page=teacher-dashboard',
    'admin.php?page=registration-codes',
    'learndash-lms'
];
```

Other admin menu items are removed to provide a clean, focused interface.
