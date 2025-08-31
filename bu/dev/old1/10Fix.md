# 10Fix.md

## Goal

Hook into the YouTube article summary UI elements (`#yt_article_summary_container`, the SVG path trigger and close button) to enable custom JavaScript interactions (e.g. toggles, analytics).

## Affected Files

- `dev/10.md` (issue specification)
- `functions.php` (enqueue custom script)
- `assets/js/custom-summary-hook.js` (new)
- Theme template (e.g. `header.php` or relevant partial) where UI is rendered
- `style.css` (if style tweaks are required)

## Execution Plan

1. Create `assets/js/custom-summary-hook.js` in the child theme:
   - Use a `MutationObserver` to watch for insertion of `#yt_article_summary_container`.
   - Once present, attach `click` listeners to:
     - The SVG trigger (`div.yt_article_summary_open path`).
     - The close button (`#yt_article_summary_close_button`).
   - In handlers, dispatch custom DOM events or log to console for validation.
2. Update `functions.php`:
   - Enqueue `custom-summary-hook.js` using `wp_enqueue_script` (dependency on `jquery` or `wp-element`).
   - Load script in the footer for performance.
3. (Optional) Add CSS hooks in `style.css` if any style adjustments are needed.
4. Test in browser:
   - Load a page that renders the summary UI.
   - Trigger open and close actions; verify console logs or custom events.

## Notes

- Ensure compatibility with Glasp extension’s markup.
- Use feature detection to avoid errors when UI is not present.
