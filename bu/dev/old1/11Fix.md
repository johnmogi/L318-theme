# 11: Build Client Registration Page Template

## Affected Files
- `page-client-registration.php`
- `functions.php`

## Execution Plan
1. Create a new page template file in the child theme (`page-client-registration.php`).
2. Add marketing content markup and integrate the `[code_registration]` shortcode for the registration form.
3. Update registration logic to use `student_private`, `student_school`, and `school_teacher` roles in `functions.php`.
4. Adjust auto-enroll hook to only target `student_private` and `student_school` roles.
5. In WP Admin, create a page and assign this template to render the custom registration.
6. Implement second registration shortcode `[code_registration2]` in `functions.php` with corresponding render and process functions.
7. Add `purchase_form` shortcode for Book Purchase registration (Option 1) with fields and validation.

## Summary of Changes
- Introduced a standalone, extensible page template for client registration.
- Registration now assigns dynamic roles based on access code group.
- `student_private`, `student_school`, and `school_teacher` roles created and cloned from Subscriber.
- Auto-enroll hook updated to only enroll non-teacher students into LearnDash.
- Added second registration form shortcode `[code_registration2]` with `ccr2_` handlers for a distinct flow.
- Added `purchase_form` shortcode to display the Book Purchase registration form with shipping and payment options.

## Solutions

### WP-CLI Commands
```bash
wp role create student_private "תלמיד עצמאי" --clone=subscriber
wp role create student_school "תלמיד חינוך תעבורתי" --clone=subscriber
wp role create school_teacher "מורה / רכז" --clone=subscriber
wp role list
```

### PHP Functions & Queries
- **ccr_render_shortcode()**: Validates POST via `
  if ("POST" === \\$_SERVER['REQUEST_METHOD'] && ! empty(\\$_POST['access_code_action']))` and checks `isset(\\$_POST['registration_code'])`. Uses `lilac_debug_log()` to trace flow.
- **ccr_show_registration_form()**: Outputs hidden fields:
  ```html
  <input type="hidden" name="registration_code" value="...">
  <input type="hidden" name="registration_group" value="...">
  ```
- **ccr_process_registration()**: Determines and assigns role:
  ```php
  $role = 'student_private';
  if (!empty($_POST['registration_group'])) {
      $role = 'student_school';
      if ($_POST['role'] === 'teacher') {
          $role = 'school_teacher';
      }
  }
  // Create user
  $userdata['role'] = $role;
  $user_id = wp_insert_user($userdata);
  $user = new WP_User($user_id);
  $user->set_role($role);
  $user->add_role('subscriber');
  update_user_meta($user_id, $wpdb->prefix.'capabilities', [
      $role       => true,
      'subscriber'=> true,
  ]);
  ```
- **lilac_auto_enroll()**: Checks:
  ```php
  $roles = (array) $user->roles;
  if (in_array('student_private',$roles,true) || in_array('student_school',$roles,true)) {
      ld_update_group_access($user_id,1294);
      ld_update_course_access($user_id,898,false);
      ld_update_course_access($user_id,1292,false);
  }
  ```
- **AJAX endpoint**: Registered via `add_action('wp_ajax_get_correct_answer', 'ld_debug_get_correct_answer')` and returns JSON with `wp_send_json_success()`.
- **Script enqueue**: `wp_enqueue_script('learndash-debug', ...)` and localized with `wp_localize_script('learndash-debug','learndashDebug',['ajaxUrl'=>admin_url('admin-ajax.php')])`.

### Second Form PHP Functions
- **ccr2_render_shortcode()**: Handles shortcode `[code_registration2]`, validates `registration2_code`, and renders form or code input.
- **ccr2_show_code_form()**: Outputs input for `registration2_code` and hidden `registration2_group`.
- **ccr2_show_registration_form()**: Renders second form fields with hidden code and group values.
- **ccr2_process_registration()**: Hooks into `init`, maps `registration2_*` POST to `registration_*` and calls `ccr_process_registration()`.

### Book Purchase Form (Option 1)
- **purchase_form shortcode**: `[purchase_form]` renders the Book Purchase registration form with:
  - Fields: `first_name`, `last_name`, `phone`, `phone_confirm`, `delivery` (pickup/shipping), shipping address (`city`, `street`, `delivery_phone`), `payment_method` (bit/credit).
  - Notice: "שימו לב! ניתן להירשם למנוי רק לאחר קבלת הספרים. קוד ההטבה נמצא בתוך הספר." displayed above the form.
- **ls_purchase_render()**: Generates the HTML form and JS toggle for shipping.
- **ls_purchase_process()**: Hooks into `init`, logs form submission via `lilac_debug_log()` for integration with payment step.

_Run the new page to verify the form and marketing content render correctly._

_Run through these commands and functions to replicate and debug the registration flow._


req form is at

C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\knowledgebase\registration-codes-admin-documentation.md

C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\knowledgebase\school-student-expiration.md


C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\knowledgebase\Happy Path - ליישות “עצמאי _ פרטי” באתר.txt

C:\Users\anist\Desktop\CLIENTS\AVIV\LILAC\knowledgebase\Happy Path - ליישות “תלמיד” באתר.txt