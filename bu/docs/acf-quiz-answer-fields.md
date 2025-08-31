# ACF Fields for LearnDash Quiz Answers

## Overview
This document details the implementation of Advanced Custom Fields (ACF) for enhancing LearnDash quiz questions with rich media and hints. The system allows for dynamic content display within quiz answers and supports various media types.

## Field Groups

### 1. Quiz Question Hints
- **Field Group**: "שדה רמז לדף מבחנים" (Quiz Hint Field)
- **Location**: `sfwd-question` post type
- **Fields**:
  - **Field Name**: `add_hint`
    - **Type**: WYSIWYG Editor
    - **Key**: `field_67b1c991e4d16`
    - **Instructions**: "יש להוסיף את ה-class : highlight-hint לטקסט של הרמז." 
      ("Add the class: highlight-hint to the hint text.")
    - **Media Upload**: Enabled
    - **Toolbar**: Full

### 2. Rich Media Content (Optional)
- **Field Group**: Rich Media for Questions
- **Fields**:
  - **Field Name**: `rich_media`
    - **Type**: Image
    - **Return Format**: Array
  - **Field Name**: `video_url`
    - **Type**: URL
    - **Instructions**: For embedding videos

## Implementation Details

### 1. Template Integration

#### `single-sfwd-quiz-new.php`
This template handles the display of quizzes with rich media sidebars:

```php
// Get ACF fields for each question
if (function_exists('get_fields')) {
    $acf_fields = get_fields($question_id);
    
    if (!empty($acf_fields) && is_array($acf_fields)) {
        $item = [
            'question_id' => $question_id,
            'title' => get_the_title($question_id),
            'hint' => $acf_fields['add_hint'] ?? '',
            'image' => null,
            'video' => null
        ];
        
        // Handle rich media
        if (!empty($acf_fields['rich_media']) && is_array($acf_fields['rich_media'])) {
            $item['image'] = $acf_fields['rich_media'];
        }
        
        // Handle video content
        if (!empty($acf_fields['video_url'])) {
            $item['video'] = $acf_fields['video_url'];
        }
    }
}
```

### 2. JavaScript Integration

The following JavaScript handles the dynamic display of hints and media:

```javascript
jQuery(document).ready(function($) {
    // Handle hint button clicks
    $('.hint-button').on('click', function() {
        const questionId = $(this).data('question-id');
        
        // AJAX call to get hint content
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_question_hint',
                question_id: questionId,
                nonce: ld_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Display hint in the sidebar
                    $(`#hint-container-${questionId}`).html(response.data.hint);
                    
                    // Mark hint as viewed
                    $(`#hint-button-${questionId}`).addClass('viewed');
                }
            }
        });
    });
});
```

### 3. AJAX Handler

```php
add_action('wp_ajax_get_question_hint', 'get_question_hint_callback');
add_action('wp_ajax_nopriv_get_question_hint', 'get_question_hint_callback');

function get_question_hint_callback() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ld_hint_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $question_id = intval($_POST['question_id']);
    $hint = get_field('add_hint', $question_id);
    
    if ($hint) {
        wp_send_json_success([
            'hint' => $hint
        ]);
    } else {
        wp_send_json_error('No hint available');
    }
}
```

## CSS Styling

```css
/* Hint button styling */
.hint-button {
    background: #f0f0f0;
    border: 1px solid #ddd;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
    margin: 5px 0;
    display: inline-block;
}

.hint-button.viewed {
    background: #e0e0e0;
    opacity: 0.7;
}

/* Hint content styling */
.hint-content {
    background: #f9f9f9;
    border-left: 3px solid #0073aa;
    padding: 15px;
    margin: 10px 0;
}

.highlight-hint {
    background-color: rgba(255, 255, 0, 0.2);
    padding: 2px 4px;
    border-radius: 2px;
}
```

## Usage in Templates

### Displaying Hints in Quiz Template

```php
// In your quiz template
if (have_rows('quiz_questions')) {
    while (have_rows('quiz_questions')) {
        the_row();
        $question_id = get_sub_field('question');
        $hint = get_field('add_hint', $question_id);
        
        if ($hint) {
            echo '<div class="hint-container">';
            echo '<button class="hint-button" data-question-id="' . $question_id . '">Show Hint</button>';
            echo '<div class="hint-content" id="hint-container-' . $question_id . '"></div>';
            echo '</div>';
        }
    }
}
```

## Best Practices

1. **Field Naming**
   - Use consistent naming conventions (e.g., `add_hint` for all hint fields)
   - Prefix custom fields to avoid conflicts (e.g., `ld_hint_text`)

2. **Performance**
   - Use `get_field()` with specific post ID rather than relying on global post
   - Cache hint content where possible
   - Lazy load media content

3. **Security**
   - Always verify nonces in AJAX handlers
   - Sanitize all output
   - Validate all input data

4. **Accessibility**
   - Add ARIA labels to interactive elements
   - Ensure proper contrast for hint text
   - Support keyboard navigation

## Troubleshooting

### Common Issues

1. **Hints Not Displaying**
   - Verify the field name matches exactly
   - Check if ACF is active
   - Ensure the question ID is correct

2. **Media Not Loading**
   - Check file permissions
   - Verify media exists in the media library
   - Check for JavaScript errors in console

3. **Styling Issues**
   - Check for CSS specificity conflicts
   - Ensure styles are enqueued correctly
   - Verify responsive behavior

## Related Documentation

- [LearnDash Developer Documentation](https://developers.learndash.com/)
- [ACF Field Types](https://www.advancedcustomfields.com/resources/)
- [WordPress AJAX in Plugins](https://codex.wordpress.org/AJAX_in_Plugins)
