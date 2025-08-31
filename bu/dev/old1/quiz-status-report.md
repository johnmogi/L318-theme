# Quiz Debug Status Report

**Date:** 2025-04-30

## Summary

- Implemented dynamic hint retrieval via AJAX in `ld_debug_get_correct_answer` endpoint.
- Refactored `learndash-debug.js` to parse `question_id` from input IDs and loop list items.
- Added verbose PHP logging (`error_log`) and `debug_display=1` flag to output raw SQL, variables, and all answers.
- Expanded AJAX response to include `question_id`, `quiz_id`, `answers`, `correct`, `db_error`, and `last_query`.

## Current Issues

- **question_id = 0**: JS parsing still not capturing the correct ID, resulting in null answers.
- **DB table error**: Logs report `edc_pro_quiz_answer` table doesn’t exist, indicating an incorrect `$wpdb->prefix` or custom table name.
- AJAX responses display `correct: null` due to the above problems.

## Next Steps

1. **Verify WPDB prefix**: Check `$wpdb->prefix` in the environment and confirm actual quiz tables (`pro_quiz_question`, `pro_quiz_answer`).
2. **Fix JS parser**: Inspect rendered HTML to locate the true source of `question_id` (ID attribute or data-attribute) and update split logic accordingly.
3. **Run debug_display**: Use `?debug_display=1` on `admin-ajax.php` to pull full debug snapshot and validate all values.
4. **Adjust table names**: If the prefix is custom (e.g., `edc_`), update table references or override `$wpdb->prefix`.
5. **Clean up**: Remove debugging logs and display code once values are correct, then finalize UI placement and styling of hints.

## Pending

- Full resolution of correct ID parsing and DB table mismatch.
- Retest AJAX flow in browser preview once fixes are applied.

---

_Postpone test; revisit when environment and parser logic are corrected._
