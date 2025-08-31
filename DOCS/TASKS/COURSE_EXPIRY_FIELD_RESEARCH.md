# Course Expiry Date Field - Research & Documentation

## Overview
The course expiry date field displayed in the user dashboard (e.g., "תוקף עד 09/24/2025") is a **custom field system** implemented specifically for this site to manage time-limited course access.

## Field Structure & Location

### User Meta Field Pattern
- **Meta Key**: `course_{course_id}_access_expires`
- **Meta Value**: Unix timestamp of expiration date
- **Storage**: WordPress `wp_usermeta` table
- **Example**: `course_123_access_expires` = `1727136000` (timestamp for 2024-09-24)

### Current Implementation Files

#### 1. **Setting the Field** (`functions.php` lines 2847, 2874)
```php
update_user_meta($user_id, "course_{$course_id}_access_expires", $expiry_timestamp);
```

#### 2. **Reading the Field** (`class-user-dashboard-shortcode.php` lines 330-333)
```php
if (preg_match('/^course_(\d+)_access_expires$/', $meta_key, $matches)) {
    $course_id = intval($matches[1]);
    $expires_timestamp = intval($meta_values[0]);
}
```

#### 3. **Access Control Check** (`src/LearnDash/AccessControl.php` line 76)
```php
$access_expires = get_user_meta($user->ID, 'course_' . $post->ID . '_access_expires', true);
$is_expired = !empty($access_expires) && $access_expires < current_time('timestamp');
```

## Data Flow

### 1. **Field Creation Triggers**
- **WooCommerce Order Processing**: When a course product is purchased
- **Manual Admin Assignment**: Through course expiration management tools
- **Subscription Renewal**: When existing access is extended

### 2. **Field Usage**
- **Dashboard Display**: Shows formatted expiry date to users
- **Access Control**: Checks if current time exceeds expiry timestamp
- **Admin Management**: Various admin tools for bulk management

## Current Functionality Status

### ✅ **Working Features**
- **Display**: Expiry dates show correctly in user dashboard
- **Data Storage**: Fields are properly stored and retrieved
- **Admin Tools**: Multiple management interfaces exist

### ❌ **Missing/Incomplete Features**
- **Automatic Access Revocation**: No cron job or hook to automatically remove access when expired
- **LearnDash Integration**: Not fully integrated with LearnDash's native access system
- **Notification System**: No automated expiry warnings to users

## Technical Architecture

### Database Schema
```sql
-- wp_usermeta table
user_id | meta_key                    | meta_value
123     | course_456_access_expires   | 1727136000
123     | course_789_access_expires   | 1735689600
```

### Related Systems
1. **WooCommerce Integration**: Product purchase triggers field creation
2. **LearnDash Courses**: Course IDs reference LearnDash course posts
3. **Custom Admin Tools**: Multiple MU-plugins for management
4. **User Dashboard**: Display and user-facing functionality

## Implementation Plan for Full Functionality

### Phase 1: Core Access Control (High Priority)
1. **Create Access Control Hook**
   ```php
   add_filter('learndash_user_has_access', 'check_course_expiry_access', 10, 3);
   ```

2. **Implement Expiry Check Function**
   ```php
   function check_course_expiry_access($has_access, $user_id, $course_id) {
       if (!$has_access) return false;
       
       $expires = get_user_meta($user_id, "course_{$course_id}_access_expires", true);
       if ($expires && $expires < current_time('timestamp')) {
           return false;
       }
       return $has_access;
   }
   ```

### Phase 2: Automated Management (Medium Priority)
1. **Daily Cron Job**
   - Check all expiry fields
   - Remove expired course enrollments
   - Send expiry notifications

2. **Integration with LearnDash Progress**
   - Sync with `learndash_user_get_course_progress()`
   - Handle partial completion scenarios

### Phase 3: Enhanced Features (Low Priority)
1. **Grace Period System**
2. **Renewal Reminders**
3. **Bulk Expiry Management**
4. **Reporting Dashboard**

## Required Development Work

### Immediate (1-2 hours)
- **Access Control Filter**: Implement the core expiry check
- **Testing**: Verify expired courses become inaccessible

### Short Term (4-6 hours)
- **Cron Job Setup**: Automated daily expiry processing
- **Notification System**: Email alerts for expiring courses
- **Admin Interface**: Bulk management tools

### Long Term (8-12 hours)
- **Full LearnDash Integration**: Native progress tracking sync
- **Advanced Reporting**: Expiry analytics and management
- **User Self-Service**: Renewal and extension options

## Security Considerations
- **User Meta Validation**: Ensure only authorized updates
- **Timestamp Integrity**: Prevent manipulation of expiry dates
- **Access Bypass Prevention**: Multiple validation layers

## Performance Impact
- **Database Queries**: Additional meta queries per course access check
- **Caching Strategy**: Implement user meta caching for frequently accessed data
- **Optimization**: Batch processing for bulk operations

---

**Status**: Custom field system exists but needs access control integration
**Priority**: High - Core functionality missing
**Estimated Development Time**: 6-8 hours for full implementation
