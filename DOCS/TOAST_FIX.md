# Toast Notification System Fix & Documentation

## Changes Made

### 1. Toast Positioning
- Changed toast container to be centered on the screen
- Added proper z-index (999999) to ensure toasts appear above other elements
- Improved toast styling for better visibility

### 2. RTL & Hebrew Support
- Added RTL (Right-to-Left) text direction support
- Set default text alignment to right for Hebrew
- Added proper font family support
- Added `dir="rtl"` to the toast container

### 3. Toast Styling
- Increased padding for better readability
- Improved button styling
- Added smooth transitions and animations
- Made toasts more visible with better contrast

### 4. Bug Fixes
- Fixed toast not showing due to z-index issues
- Fixed toast positioning
- Improved error handling for toast creation
- Fixed alert integration with the toast system

## How to Use

### Basic Usage
```javascript
// Show a success toast
LilacToast.success('הפעולה בוצעה בהצלחה', 'הצלחה');

// Show an error toast
LilacToast.error('אירעה שגיאה בעיבוד הבקשה', 'שגיאה');

// Show a warning toast
LilacToast.warning('אנא בדוק את הפרטים שהזנת', 'אזהרה');

// Show an info toast
LilacToast.info('יש לבצע עדכון למערכת', 'מידע');
```

### Advanced Usage
```javascript
// Show a custom toast
LilacToast.showToast({
    title: 'כותרת ההודעה',
    message: 'תוכן ההודעה יוצג כאן',
    type: 'info', // 'success', 'error', 'warning', 'info'
    duration: 5000, // in milliseconds, 0 = don't auto-dismiss
    closable: true,
    position: 'center', // 'top-right', 'top-left', 'bottom-right', 'bottom-left', 'center'
    buttons: [
        {
            text: 'אישור',
            class: 'button button-primary',
            click: function() {
                // Button click handler
                return true; // return true to close the toast
            }
        },
        {
            text: 'ביטול',
            class: 'button button-link',
            click: function() {
                // Button click handler
                return true; // return true to close the toast
            }
        }
    ]
});
```

### Session Timeout Toast
```javascript
// Initialize session timeout
LilacToast.session.init({
    sessionTimeout: 30 * 60 * 1000, // 30 minutes
    warningBeforeTimeout: 5 * 60 * 1000, // 5 minutes
    onSessionAboutToExpire: function() {
        // Called when session is about to expire
    },
    onSessionExpired: function() {
        // Called when session has expired
        window.location.href = '/logout';
    },
    onSessionExtended: function() {
        // Called when user extends the session
    }
});
```

## Integration with Standard Alerts

The toast system automatically converts standard JavaScript alert(), confirm(), and prompt() dialogs to toast notifications. This provides a more modern and consistent user experience.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE11+ (with polyfills)

## Known Issues

- In some cases, the toast might appear behind other elements. If this happens, ensure that parent elements don't have `overflow: hidden` or `z-index` values that could interfere with the toast positioning.

## Troubleshooting

1. **Toasts not showing up?**
   - Check browser console for JavaScript errors
   - Verify that the toast container exists in the DOM
   - Ensure no other CSS is overriding the toast styles

2. **Text alignment issues?**
   - Verify that the `dir="rtl"` attribute is set on the toast container
   - Check for any conflicting CSS that might be overriding text direction

3. **Toast appears behind other elements?**
   - The toast container has z-index: 999999. If it's still behind other elements, those elements may have a higher z-index.
   - Check for any parent elements with `position: relative` and high z-index values

## Future Improvements

- Add support for custom animations
- Add queue system for multiple toasts
- Improve accessibility (ARIA attributes)
- Add support for different toast positions on different screen sizes

## Last Updated

2025-07-08
