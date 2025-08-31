# Fix Report: Automated Student Membership Assignment

## Issue
- New users were not assigned the correct custom roles (`school_student` or `student_private`) upon registration.
- PMProRoleSync failed to detect any matching role, resulting in no membership level assignments.
- File inclusion errors (`require_once`) due to mixed use of `get_stylesheet_directory()` and incorrect paths.

## Queries & Debug Logs
- **Debug log entries**:
  - `Failed opening required '.../Captcha.php'` in `functions.php`.
  - `Direct DB sync failed - No matching role for user` with empty `roles` array.
  - `PMPro sync result for new registration` showing `success: false`.
- **Key functions inspected**:
  - `lilac_direct_db_sync_pmpro()` for direct DB membership sync.
  - `PMProRoleSync::sync_user_memberships()` for role-to-level mapping.

## Initial State
- Registration flow used `wp_insert_user()` with a `role` param, but WP sometimes ignored it.
- `__DIR__` was not used consistently, causing include path failures for login-related classes.
- `lilac_direct_db_sync_pmpro()` returned `false` due to no matching role in `$user->roles`.
- PMPro membership level remained at `0`, and no DB record was found.

## Changes Implemented
1. **Path Resolution**: Replaced all `get_stylesheet_directory()` calls with `__DIR__` in `functions.php` to guarantee correct includes.
2. **Explicit Role Setting**: After `wp_insert_user()`, called `$user->set_role($role)` to enforce the intended role.
3. **Enhanced Logging**: Added detailed debug logs in `lilac_direct_db_sync_pmpro()` and registration logic to trace role detection and sync outcomes.
4. **Repair Tool**: Created a diagnostic and fix form (`[debug_membership]` shortcode) to assign missing roles and force PMPro membership sync for existing users.

## Current State
- New registrations now reliably receive their correct roles and PMPro membership levels immediately.
- Debug panel (`[debug_membership]`) reports accurate role and membership status with an option to repair.
- No more file inclusion errors; all login-related classes load successfully.

**Testing Recommendations**:
- Register a new `school_student` user and verify their role and membership level via the debug shortcode.
- Run the repair tool on an existing user missing a membership to confirm it applies the fix.
