# Teacher Selection Implementation

## Current Implementation (Simplified)
- Uses a standard HTML `<select>` dropdown
- Loads up to 100 teachers at once
- Displays: Teacher Name (email) - phone
- Sorted alphabetically by display name

## Why This Approach
1. **Simplicity**: No external dependencies
2. **Reliability**: Works without JavaScript
3. **Performance**: Good for up to 100 teachers

## Future Improvements
1. **WordPress List Table**
   - Better for large numbers of teachers
   - Built-in search and filters
   - Pagination support
   - Bulk actions

2. **AJAX Search**
   - Only load visible teachers
   - Search by name, email, or phone
   - Implement infinite scroll

## Code Location
- Template: `includes/admin/views/wizard/step-1.php`
- Main class: `includes/admin/class-teacher-class-wizard.php`
