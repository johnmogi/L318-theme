# 20_LearnDashHintSectionsPlan

## Goal
Extend each LearnDash quiz question with rich-text hint sections (full WYSIWYG), editable alongside existing hint field.

## Affected Files
- includes/quiz/hint-sections/metabox.php
- includes/quiz/hint-sections/save.php
- includes/quiz/hint-sections/render.php
- includes/quiz/hint-sections/wysiwyg-init.js
- includes/quiz/hint-sections/admin-styles.css
- includes/quiz/hint-sections/front-styles.css
- functions.php (loader + enqueue)

## Execution Plan
1. **Folder Setup**
   - Create `includes/quiz/hint-sections/`
2. **Metabox** (`metabox.php`)
   - Hook `add_meta_boxes` for `sfwd-question`
   - Use `wp_editor()` to render rich-text fields
   - Add nonce and capability checks
3. **Save Logic** (`save.php`)
   - Hook `save_post`
   - Verify nonce and `current_user_can` before `update_post_meta()`
   - Sanitize via `wp_kses_post()`, log errors on failure
4. **Admin Assets**
   - `admin-styles.css` to style the WYSIWYG box
   - `wysiwyg-init.js` for any TinyMCE init tweaks
5. **Frontend Rendering** (`render.php`)
   - Filter quiz question output via `learndash_template`
   - Retrieve meta and output HTML before/after answer choices
   - Enqueue `front-styles.css` on quiz pages
6. **Loader & Enqueue** (`functions.php`)
   - Auto-require all files in `includes/quiz/hint-sections/`
   - Enqueue admin and front assets only on quiz pages
7. **Testing & Approval**
   - Validate metabox appears and saves formatted content
   - Confirm frontend displays WYSIWYG hints on all quiz types
   - Log any issues to `error_log()`
   - Request front-end owner approval before merge

## Next Steps
- Begin coding `metabox.php` and `save.php`
- Implement and test admin integration
- Proceed with frontend render logic
