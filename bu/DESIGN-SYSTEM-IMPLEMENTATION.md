# LILAC Learning Platform - Design System Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [File Structure](#file-structure)
3. [Design System Components](#design-system-components)
4. [LearnDash Integration](#learndash-integration)
5. [Implementation Steps](#implementation-steps)
6. [Testing Checklist](#testing-checklist)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance](#maintenance)

## Overview
This document provides comprehensive instructions for implementing and maintaining the design system across the LILAC Learning Platform, with special considerations for LearnDash integration.

## File Structure
```
theme/
├── css/
│   ├── _design-tokens.css    # Design tokens (colors, spacing, typography)
│   ├── _components.css      # Reusable UI components
│   ├── base.css            # Base styles and resets
│   └── STYLE-GUIDE.md      # Component documentation
├── js/
│   └── quiz-media-handler.js # Media handling for quizzes
└── functions.php           # Theme functions and style enqueuing
```

## Design System Components

### Core Concepts
1. **Design Tokens** - Single source of truth for all design decisions
2. **Components** - Reusable UI elements with consistent styling
3. **Utilities** - Helper classes for common styling needs

### Key Files
1. `_design-tokens.css` - Variables for colors, spacing, typography
2. `_components.css` - Pre-built UI components
3. `base.css` - Global styles and resets

## LearnDash Integration

### Course and Lesson Styling

#### Course Grid
```html
<div class="ld-course-list-items row">
    <div class="col-md-4">
        <div class="card course-card">
            <div class="card-body">
                <h3 class="card-title">[ld_course_title]</h3>
                <div class="card-text">[ld_course_description]</div>
                <div class="progress">
                    <div class="progress-bar" style="width: [percentage_complete]%"></div>
                </div>
                <a href="[course_permalink]" class="button button-primary">Continue</a>
            </div>
        </div>
    </div>
</div>
```

#### Quiz Styling
```html
<div class="quiz-container">
    <div class="quiz-main-content">
        <!-- Quiz content will be loaded here -->
        [ld_quiz]
    </div>
    <div class="quiz-media-sidebar">
        <!-- Media content will be loaded here -->
    </div>
</div>
```

### Custom Quiz Templates
1. **File Location**: `theme/learndash/quiz/`
2. **Template Files**:
   - `quiz.php` - Main quiz template
   - `question.php` - Individual question template
   - `hints.php` - Quiz hints template

## Implementation Steps

### 1. Theme Setup
1. Ensure all CSS files are properly enqueued in `functions.php`
2. Verify the loading order:
   ```php
   function lilac_enqueue_all_styles() {
       // 1. Design Tokens
       wp_enqueue_style('lilac-design-tokens', ...);
       // 2. Components
       wp_enqueue_style('lilac-components', ...);
       // 3. Base styles
       wp_enqueue_style('lilac-base', ...);
   }
   ```

### 2. LearnDash Customization

#### Course Grid
1. Override the default course grid template
2. Use the provided card component for consistent styling
3. Add progress indicators and status badges

#### Quiz Customization
1. Implement the quiz container structure
2. Style quiz questions and answers
3. Add custom media handling

### 3. Component Implementation
1. Use the provided utility classes for common styles
2. Extend components using BEM methodology
3. Document any new patterns

## Testing Checklist

### Design System
- [ ] Verify color contrast meets WCAG 2.1 AA standards
- [ ] Test typography scaling and readability
- [ ] Check component responsiveness
- [ ] Verify consistent spacing

### LearnDash Integration
- [ ] Test course grid layout
- [ ] Verify quiz functionality
- [ ] Check media display in quizzes
- [ ] Test progress tracking
- [ ] Verify completion certificates

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (macOS and iOS)
- [ ] Edge (latest)
- [ ] Mobile browsers

## Troubleshooting

### Common Issues
1. **Styles Not Loading**
   - Check browser console for 404 errors
   - Verify file permissions
   - Clear WordPress cache

2. **LearnDash Overrides Not Working**
   - Clear LearnDash transients
   - Check template hierarchy
   - Verify file permissions

3. **Media Not Displaying**
   - Check console for JavaScript errors
   - Verify media file permissions
   - Check file paths

## Maintenance

### Version Control
1. Always create a branch for design system changes
2. Document changes in CHANGELOG.md
3. Tag releases with semantic versioning

### Updates
1. Review and test all components after WordPress updates
2. Check for LearnDash compatibility with new versions
3. Update documentation for any changes

## LearnDash-Specific Implementation

### Custom Quiz Templates
1. Create a `learndash` directory in your theme
2. Add quiz template overrides:
   ```
   theme/
   └── learndash/
       └── quiz/
           ├── quiz.php
           ├── question.php
           └── hints.php
   ```

### Customizing the Quiz Timer
```javascript
// In your quiz-media-handler.js
jQuery(document).on('learndash-quiz-timer-update', function(e, timer) {
    // Custom timer display logic
    const minutes = Math.floor(timer.timeLeft / 60);
    const seconds = timer.timeLeft % 60;
    jQuery('.quiz-timer').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
});
```

### Sticky Quiz Navigation
```css
.quiz-navigation {
    position: sticky;
    top: 20px;
    background: var(--color-white);
    padding: var(--spacing-3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}
```

## Advanced Customization

### Custom Question Types
1. Create a new file for each question type
2. Register the question type:
   ```php
   add_filter('learndash_question_types', function($types) {
       $types['custom_type'] = __('Custom Type', 'your-text-domain');
       return $types;
   });
   ```

### AJAX Quiz Submission
```javascript
jQuery(document).on('click', '.submit-quiz', function() {
    const quizData = {
        action: 'submit_quiz',
        quiz_id: learndash_get_quiz_id(),
        // Add other quiz data
    };

    jQuery.post(ajaxurl, quizData, function(response) {
        // Handle response
    });
});
```

## Performance Optimization

### Critical CSS
1. Inline critical CSS in the `<head>`
2. Load non-critical CSS asynchronously

### Asset Loading
```php
function lilac_conditionally_load_assets() {
    if (is_singular('sfwd-quiz')) {
        wp_enqueue_script('quiz-handler');
    }
    if (is_singular('sfwd-courses')) {
        wp_enqueue_style('course-styles');
    }
}
add_action('wp_enqueue_scripts', 'lilac_conditionally_load_assets');
```

## Support
For assistance with implementation, contact:
- Developer: [Your Name]
- Email: [Your Email]
- Last Updated: May 13, 2025
