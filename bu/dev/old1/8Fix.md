# 8Fix: Debugging Auto-Start LearnDash Course

## Affected Files
- `functions.php` (auto-start snippet)
- `dev/8.md` (front-end HTML snapshot)

## Goal
Ensure the auto-start snippet executes and renders the LearnDash infobar for students.

## Execution Plan
1. **Verify snippet execution**
   - Open browser console, look for `Auto-start snippet ran...` log.
   - Check `lilac-debug.log` for any hook errors.
2. **Inspect HTML output**
   - Confirm presence of `<div class="learndash-shortcode-wrap-ld_infobar...">` in the DOM.
3. **Enhance debug logging**
   - Add `error_log()` or `lilac_debug_log()` calls in the snippet to verify hook entry.
4. **Adjust hook priority if needed**
   - If snippet not running early enough, change `add_action` priority.
5. **Retest**
   - Clear caches, log in as student, revisit course page, confirm logs and infobar.

---

*Awaiting your approval before applying debug enhancements to `functions.php`.*
