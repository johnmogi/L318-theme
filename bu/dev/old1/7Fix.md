# 7Fix: Auto-start LearnDash Course

## Affected File

- `functions.php`

## What This Fix Does

Automatically marks the LearnDash course as started for logged-in students on the single course page, creating the progress record that enables the infobar, breadcrumbs, and progress bar to display.

It also logs a message to the browser console to confirm the snippet executed.

## Code Snippet

```php
<?php
// Auto-start course for students on page load
add_action('template_redirect', function() {
    if (is_singular('sfwd-courses') && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $course_id = get_the_ID();
        if (!learndash_course_status($course_id, $user_id) && function_exists('ld_update_course_access')) {
            ld_update_course_access($user_id, $course_id, false);
        }
    }
});

// Debug: log to console in footer if on course page
add_action('wp_footer', function() {
    if (is_singular('sfwd-courses') && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $course_id = get_the_ID();
        echo "<script>console.log('Auto-start snippet ran for course {$course_id}, user {$user_id}');</script>";
    }
}, 99);
``` 

## How to Test

1. Clear any caches.
2. Log in as a student with course & group access.
3. Visit the single course page.
4. Open the browser console (F12).
5. Confirm the console message appears.
6. Confirm the LearnDash infobar and progress bar are visible.

---

*After review and approval, the snippet will run automatically for students without them needing to click "Start Course".*



להראות מבחן עובד
- רמז פעיל
- זמן שנשאר


אחד מכל דבר במסמך

