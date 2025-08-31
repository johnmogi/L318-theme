# School Manager MU Plugins Organization

## Clean Structure (Fixed)

The mu-plugins directory has been reorganized to prevent conflicts and improve maintainability:

```
mu-plugins/
├── loader.php                          # Main loader (loads everything)
├── active/
│   └── school-manager-enhancements.php # Core functionality (consolidated)
├── import-export/
│   ├── import-export-enhancement.php   # CSV generation & chunked import
│   └── chunked-student-import.php      # Legacy (loaded by enhancement)
├── learndash/
│   ├── instructor-quiz-features.php    # Quiz permissions
│   ├── learndash-groups-integration.php # Group management
│   └── learndash-layout-fix.php        # UI improvements
├── one-time/                           # Not auto-loaded
│   ├── cardcom-js-fix.php             # JavaScript fixes
│   ├── teacher-visibility-fix.php      # Visibility fixes
│   └── teacher-count-fix.php          # Count fixes
└── debug/                             # Only loads in WP_DEBUG mode
    └── teacher-import-debugger.php    # Debug tools
```

## What Was Fixed

### 1. **Removed Duplicates**
- Eliminated 15+ duplicate files that were causing conflicts
- Consolidated similar functionality into single files

### 2. **Organized by Function**
- **active/**: Core functionality that should always run
- **import-export/**: Import/export related features
- **learndash/**: LearnDash integrations
- **one-time/**: Fixes that were run once (not auto-loaded)
- **debug/**: Debug tools (only load in debug mode)

### 3. **Consolidated Core Features**
The main `school-manager-enhancements.php` now handles:
- Dashboard styling (Hebrew text visibility)
- Menu cleanup (removes unwanted items)
- Teacher role fixes (school_teacher → wdm_instructor)
- Instructor permissions (quiz and group access)
- Content visibility (instructors see all quizzes/groups)

### 4. **Smart Loading**
- Prevents duplicate loading
- Loads in proper order
- Error handling for failed loads
- Debug logging for troubleshooting

## Key Features Working

✅ **Hebrew Dashboard**: Text is now black and visible  
✅ **Teacher Roles**: Auto-converts to wdm_instructor  
✅ **Quiz Permissions**: Instructors see all quizzes  
✅ **Import/Export**: CSV generation and chunked import  
✅ **Menu Cleanup**: Unwanted items removed  
✅ **Debug Tools**: Available in debug mode  

## Usage

1. **Everything loads automatically** via `loader.php`
2. **No manual activation needed** - it's all mu-plugins
3. **Check debug.log** for loading confirmation
4. **One-time fixes** in `/one-time/` folder if needed manually

## Troubleshooting

If issues occur:
1. Check `/wp-content/debug.log` for errors
2. Temporarily disable by renaming `loader.php`
3. Enable WP_DEBUG to load debug tools
4. Check individual plugin files for specific issues

This clean organization eliminates the "complete mess" and provides a maintainable structure.
