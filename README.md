# TESTLI1

## System Components

### Messaging System
The theme includes a comprehensive messaging system with the following components:

* **Toast**: Displays toast-style notifications to users (`Lilac\Messaging\Toast`)
* **SiteMessage**: Handles site-wide messages (`Lilac\Messaging\SiteMessage`)
* **CourseProgress**: Tracks user progress through courses (`Lilac\Messaging\CourseProgress`)
* **Admin Components**: Admin interfaces for managing messages (`Lilac\Messaging\Admin\MessagingAdmin` and `Lilac\Messaging\Admin\MessageManager`)

## Crash Report & Troubleshooting

### Common Issues

#### 1. Messaging System Not Initializing
**Problem**: The messaging system is loaded but not properly initialized.

**Symptoms**:
- Admin menu for messaging doesn't appear
- Toast notifications don't display
- Message placeholders show raw text instead of dynamic content

**Solution**:
Ensure all classes are properly initialized after being included. The correct initialization pattern is:

```php
// Include files
require_once get_stylesheet_directory() . '/src/Messaging/Toast.php';
// ... other includes

// Initialize with proper namespace
\Lilac\Messaging\Toast::get_instance();
// ... other initializations
```

#### 2. Message Placeholders Not Processing
**Problem**: Message placeholders like `{user_name}` or `{visit_count}` show as raw text instead of being replaced with actual data.

**Symptoms**:
- Messages show template placeholders instead of actual values
- Example: "Hello {user_name}!" instead of "Hello John!"

**Solution**:
Check that the `process_placeholders()` method in the relevant class is being called before the message is displayed.

## Implementation Details

### Class Structure
All messaging classes follow the singleton pattern with a `get_instance()` static method. Always use this method to retrieve the class instance:

```php
$toast = \Lilac\Messaging\Toast::get_instance();
```

### Message Placeholders
The system supports the following placeholders:
- `{user_name}` - Current user's display name
- `{visit_count}` - Number of site visits in current session
- `{active_time}` - Active time on site (formatted as hours:minutes)
- `{course_progress}` - Overall course progress percentage

Example: "שלום {user_name}! ביקרת באתר {visit_count} פעמים."
# lilactheme1
# lilacStudentFlow
# lilac4
# lilac-1906
# new-lilac-1
# L208-theme
# L0208-theme
# L0208-theme
# L02082-theme
# L218theme
# LA218-theme
# L38-theme
# L318-theme
