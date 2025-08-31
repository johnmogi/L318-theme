# Toast Messaging System

This directory contains the implementation of the Toast notification system for the Lilac child theme.

## Overview

The `Toast` class provides a customizable, RTL-compatible toast notification system. It supports PHP shortcodes, session counters, and a JavaScript API for dynamic notifications.

## Features

- PHP shortcodes: `[lilac_message]`, `[lilac_session_counter]`
- Customizable options: type, title, message, position, autoClose, icon, custom CSS classes
- JavaScript API: `window.LilacToast.showToast()`
- Session visit counter
- Multiple display positions: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
- RTL support for Hebrew
- Site-wide messaging via admin interface
- User context-aware messages

## Components

The messaging system is comprised of several modular components:

1. **Toast.php** - Core toast notification functionality
2. **SiteMessage.php** - Site-wide message integration
3. **CourseProgress.php** - Progress tracking and notifications
4. **Admin/MessagingAdmin.php** - Admin interface for message management

## Installation

Ensure the components are loaded in your theme functions:

```php
require_once get_stylesheet_directory() . '/src/Messaging/Toast.php';
require_once get_stylesheet_directory() . '/src/Messaging/SiteMessage.php';
require_once get_stylesheet_directory() . '/src/Messaging/CourseProgress.php';

// Admin interface (admin only)
if (is_admin()) {
    require_once get_stylesheet_directory() . '/src/Messaging/Admin/MessagingAdmin.php';
}
```

## Basic Usage

### Shortcodes

**`[lilac_message]`**

```php
[lilac_message
    type='success'
    title='My Title'
    auto_close='5'
    position='top-left'
    icon='true'
    class='custom-class'
    message_id='unique-id-123'
]This is the message content.[/lilac_message]
```

Attributes:
- `type`: info, success, warning, error (default info)
- `title`: Optional title above the message
- `auto_close`: Seconds before auto-dismiss (default 0 for manual)
- `position`: top-left (currently fixed to top-left)
- `icon`: true/false (default true)
- `class`: Additional CSS classes
- `message_id`: Unique identifier for the message

**`[lilac_session_counter]`**

```php
[lilac_session_counter label='Session Count:' class='my-counter']
```

Attributes:
- `label`: Custom label before the count
- `class`: Additional CSS classes for styling

## JavaScript API

```js
window.LilacToast.showToast({
    type: 'success',
    title: 'Great Job',
    message: 'You have completed the lesson!',
    position: 'top-left',
    autoClose: 3, // seconds
    icon: true,
    className: 'my-custom-toast',
    messageId: 'unique-id-123' // For tracking dismissals
});
```

## User Context Integration

You can include user-specific information in your messages:

```php
$user = wp_get_current_user();
$visits = Lilac\Messaging\SiteMessage::get_instance()->get_post_type_views('post');
$time = Lilac\Messaging\SiteMessage::get_instance()->get_time_on_site();

$message = sprintf(
    'שלום %s, ביקרת באתר %d פעמים והיית פעיל במשך %d דקות',
    $user->display_name,
    $visits,
    round($time / 60)
);

Lilac\Messaging\SiteMessage::get_instance()->show_contextual_message([
    'type' => 'info',
    'title' => 'ברוך הבא', 
    'content' => $message,
    'auto_close' => 10
]);
```

## Admin Interface

Access the message configuration under **WordPress Admin → Messaging**:

- **Welcome Messages** - Configure site-wide welcome message
- **Contextual Messages** - Set up context-specific notifications
- **Progress Notifications** - Messages linked to course progress

## Styling

Override or extend styles in `assets/css/toast.css`:

```css
.lilac-toast.custom-class {
    background-color: #222;
}
```

## Development Roadmap

### Planned Enhancements

1. **Message Management**
   - CRUD interface for managing multiple messages
   - Message duplication
   - Advanced filters (URL, post type, user role)
   - Priority settings for message sequencing

2. **User Interface**
   - Separate teacher dashboard integration
   - Message preview functionality
   - Message scheduling (date/time)

3. **Advanced User Context**
   - LMS integration for course progress
   - Test result feedback
   - Personalized content based on user history
   - User role-specific messages

4. **Technical Improvements**
   - Message queue management
   - Prevent message overlap/collision
   - Performance optimizations
   - Export/import functionality

### Extending the System

The messaging system is designed to be extended through its modular architecture:

1. **Adding New Message Types**
   Create a new class that extends the base functionality:

   ```php
   namespace Lilac\Messaging\Extensions;
   
   class QuizResultMessage {
       // Implementation
   }
   ```

2. **Custom Message Triggers**
   Register new actions/hooks for message display:

   ```php
   add_action('learndash_quiz_completed', function($quiz_data, $user) {
       // Show quiz result message
   }, 10, 2);
   ```

3. **Message Queue Management**
   Implement a priority system for message display:

   ```php
   // Future implementation in SiteMessage.php
   public function queue_message($args, $priority = 10) {
       // Add to message queue with priority
   }
   
   public function process_message_queue() {
       // Display highest priority message first
   }
   ```

## Developer Reference

### MessageManager Class (`src/Messaging/Admin/MessageManager.php`)
- `add_message()` - Create a new message.
- `edit_message()` - Update existing message.
- `duplicate_message()` - Duplicate a message (inactive copy).
- `ajax_toggle_message_status()` - Toggle active/inactive via AJAX.
- `ajax_delete_message()` - Delete a message via AJAX.
- `prepare_message_data()` - Sanitize and structure form data.

### SiteMessage Class (`src/Messaging/SiteMessage.php`)
- `process_placeholders($content)` - Replace `{user_name}`, `{visit_count}`, `{session_time}`, `{course_progress}`.
- `process_custom_messages()` - Load and queue active custom messages.
- `check_display_conditions($message)` - Validate session, homepage, URL, post-type rules.
- `queue_message($message, $priority)` - Add messages to queue with priority.
- `process_message_queue()` - Display highest-priority toast and prevent overlaps.
- `show_contextual_message($args)` - API for queuing contextual messages programmatically.

### Message Reordering (Planned)
- UI for drag‑and‑drop reordering in Message Management screen.
- Persist order via AJAX/form and adjust `priority` accordingly.
- Prevent simultaneous display by honoring new sequence.

## Examples

```php
// Simple message
echo do_shortcode('[lilac_message type="warning" title="שים לב"]יש להשלים את הקורס עד ה-1 למאי.[/lilac_message]');

// Course completion message
window.LilacToast.showToast({
    type: 'success',
    title: 'כל הכבוד!',
    message: 'השלמת את הקורס בהצלחה!'
});
```

## Changelog

- v1.0.0 – Initial release
- v1.1.0 – Added site-wide messaging and admin interface


לאורך כל דפי האתר (חוץ מדפי הקורסים) תופיע למשתמש הודעה עם קישור המזמינה אותו לרכוש קורס במחיר מיוחד, אם רכש תרגול - לאחר רכישת תרגול כל משתמש יקבל הודעה עם קוד הנחה קבוע לרכישת הקורסים. כשהוא לוחץ על ההודעה הוא יכול לבחור בין הקורסים באתר, ולשלם. - כל מי שרוכש תרגול יקבל הטבה לקורס.
כאשר המשתמש נכנס לדף מוצר ספר יש לו אופציה להתרשמות מפרק חינמי מהספר ולאחריו תרגול חינמי באותו נושא. בנוסף, הסבר על שיטת הלימוד – טקסט + תמונות. בדף מוצר תרגול יהיו לו 2 מבחני חינם: מבחן נושא – ומבחן כמו בתיאוריה.
