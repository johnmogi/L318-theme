# ACF Hints Implementation for LearnDash Quizzes

## Overview

This documentation provides a comprehensive guide to the ACF (Advanced Custom Fields) hint implementation for LearnDash quizzes. The system dynamically loads hint content from ACF fields based on question IDs, improving the user experience by providing contextually relevant hints.

## Key Files

- **functions.php**: Contains the AJAX handlers and enqueuing functions
- **js/quiz-hint-acf.js**: Client-side JavaScript for handling hint interactions
- **js/hint-initializer.js**: Initializes hint functionality on quiz pages
- **js/acf-hint-styles.css**: CSS styling for the hint display

## How It Works

### 1. Mapping System

The system maps LearnDash quiz question IDs to WordPress post IDs where ACF content is stored. This is handled by the `lilac_get_hint_mapping()` function:

```php
function lilac_get_hint_mapping() {
    return [
        '22' => '955', // Original mapping for question 22
        '49' => '948', // Question 1 - עובר דרך
        '28' => '953', // Question 2 - מרכיבי המרחב התעבורתי
        '29' => '957', // Question 3 - נהיגה כמשימה מורכבת
        '23' => '959', // Question 4 - תלמיד נהיגה במרחב התעבורתי
        '24' => '961', // Question 5 - סיכונים במרחב התעבורתי
        '26' => '963', // Question 6 - מפגש עובר דרך לסביבה
        '25' => '965', // Question 7 - מפגש בין הדרך לעובר דרך
        '27' => '967'  // Question 8 - הגדרת מרחב תעבורתי
    ];
}
```

### 2. Script Enqueuing

CSS and JavaScript files are enqueued conditionally on LearnDash quiz pages through the `lilac_enqueue_hint_scripts()` function:

```php
function lilac_enqueue_hint_scripts() {
    // Only load on LearnDash quiz pages
    if (is_singular('sfwd-quiz') || 
        (is_singular() && get_post() && has_shortcode(get_post()->post_content, 'ld_quiz'))) {
        
        // Enqueue styles
        wp_enqueue_style('acf-hint-styles', 
            get_stylesheet_directory_uri() . '/js/acf-hint-styles.css', 
            array(), 
            filemtime(get_stylesheet_directory() . '/js/acf-hint-styles.css')
        );
        
        // Enqueue the hint script
        wp_enqueue_script('quiz-hint-acf', 
            get_stylesheet_directory_uri() . '/js/quiz-hint-acf.js', 
            array('jquery'), 
            filemtime(get_stylesheet_directory() . '/js/quiz-hint-acf.js'), 
            true
        );
        
        // Enqueue the hint initializer script
        wp_enqueue_script('hint-initializer', 
            get_stylesheet_directory_uri() . '/js/hint-initializer.js', 
            array('jquery', 'quiz-hint-acf'), 
            filemtime(get_stylesheet_directory() . '/js/hint-initializer.js'), 
            true
        );
        
        // Pass AJAX URL and nonce to script
        wp_localize_script('quiz-hint-acf', 'lilacHintData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_hint_nonce')
        ));
    }
}
```

### 3. AJAX Handler

The AJAX handler `lilac_get_acf_hint_callback()` retrieves ACF hint content based on question IDs using several fallback methods:

1. Use the static mapping from question ID to post ID
2. Extract question ID from format wpProQuiz_X_Y
3. Use question meta if available
4. Search for posts with matching hint text
5. Use default fallback post

If ACF content is found, it's processed with WordPress filters and returned. If no ACF content is found, the original hint text is used as a fallback.

### 4. Styling

The CSS in `acf-hint-styles.css` handles the styling of:
- Hint containers
- RTL support for Hebrew content
- Loading indicators
- Highlight effects for hint content

## CSS Styling Details

### Container Styling
```css
.wpProQuiz_tipp {
    position: relative;
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
    margin: 10px 0;
    border-left: 4px solid #4caf50;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 100%;
}
```

### RTL Support
```css
.acf-hint-content {
    direction: rtl;
    text-align: right;
    font-size: 1.1em;
    line-height: 1.5;
    opacity: 0;
    transition: opacity 0.3s ease;
}
```

### Visibility Control
```css
.hint-visible .acf-hint-content {
    opacity: 1;
}
```

### Loading Indicator
```css
.acf-hint-loading {
    display: none;
    text-align: center;
    padding: 10px;
}

.hint-loading .acf-hint-loading {
    display: block;
}
```

## Debugging Notes

- The nonce name must be 'lilac_hint_nonce' in both JavaScript and PHP
- Known working mapping: Question ID 22 to Post ID 955
- A debugging function `lilac_acf_hint_debug()` outputs helpful information about hint retrieval
- If you encounter issues, check:
  1. Browser console for client-side errors
  2. PHP error logs for server-side errors
  3. That ACF fields are properly set up with the field name "question_hint"
  4. That the mapping in `lilac_get_hint_mapping()` is correct

## Common Issues and Solutions

### Issue: Hints Not Displaying
- Verify the post ID mapping is correct
- Check if ACF fields are properly populated
- Ensure scripts are loading (no console errors)
- Check nonce verification in both JS and PHP sides

### Issue: Styling Problems
- Check if acf-hint-styles.css is being loaded
- Inspect the CSS with browser dev tools to see which styles are being applied
- Ensure the correct classes are being added to elements

### Issue: Wrong Content Showing
- Verify the mapping between question IDs and post IDs
- Check if the ACF field "question_hint" exists and has content
- Look at the debug data in the AJAX response

## Future Improvements

1. Make the hint mapping dynamic (database-driven instead of static array)
2. Add an admin interface for managing hint content
3. Implement caching for better performance
4. Add more robust error handling and logging
