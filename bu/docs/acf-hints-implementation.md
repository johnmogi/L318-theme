# LearnDash ACF Hints Integration

## Overview

The ACF Hints integration allows custom hint content from Advanced Custom Fields to be displayed in LearnDash quizzes, replacing the default hint content. The system applies a yellow highlight to make the hints stand out.

## Key Components

### 1. ID Mapping System
LearnDash Question IDs are mapped to WordPress Post IDs where the ACF fields are stored:

```php
// Map from LearnDash question IDs to WordPress post IDs
$id_mapping = [
    '22' => '1370', // Maps question ID 22 to post ID 1370
    // Add more mappings as needed
];
```

### 2. AJAX Handler Function
Located in `functions.php`, this handles AJAX requests for hint content:

```php
function lilac_get_acf_hint_callback() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_hint_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        die();
    }

    // Get question ID and mapped post ID
    $question_id = isset($_POST['question_id']) ? sanitize_text_field($_POST['question_id']) : '';
    $post_id = isset($id_mapping[$question_id]) ? $id_mapping[$question_id] : $question_id;
    
    // Retrieve ACF field content
    $hint_content = '';
    if ($post_id) {
        $hint_content = get_field('add_hint', $post_id);
        
        // Fallback to direct DB query if get_field fails
        if (!$hint_content) {
            global $wpdb;
            $meta_query = $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}postmeta 
                WHERE post_id = %d AND meta_key = %s",
                $post_id,
                'add_hint'
            );
            
            $direct_content = $wpdb->get_var($meta_query);
            if ($direct_content) {
                $hint_content = $direct_content;
            }
        }
    }
    
    // Return ACF content or fallback to original answer
    if ($hint_content) {
        wp_send_json_success(['hint' => $hint_content]);
    } else {
        // Get original LearnDash answer as fallback
        global $wpdb;
        $answer_query = $wpdb->prepare(
            "SELECT answer_data FROM {$wpdb->prefix}learndash_pro_quiz_question 
            WHERE id = %d",
            $question_id
        );
        
        $answer_data = $wpdb->get_var($answer_query);
        
        if ($answer_data && is_serialized($answer_data)) {
            $answer = @unserialize($answer_data);
            if ($answer && isset($answer['answer'])) {
                $hint_content = '<strong>התשובה הנכונה:</strong><br>' . $answer['answer'];
                wp_send_json_success(['hint' => $hint_content]);
                return;
            }
        }
        
        wp_send_json_error(['message' => 'No hint content found for this question']);
    }
}
```

### 3. JavaScript Handler

The front-end JavaScript in `quiz-hint-acf.js` handles:
- Detecting hint button clicks
- Sending AJAX requests for hint content
- Displaying hint content in a highlighted container
- Applying fallback styling to default hints if ACF content is missing

### 4. CSS Styling

```css
.highlight-hint {
    background-color: yellow !important;
    padding: 2px 5px !important;
    border-radius: 3px !important;
    display: inline-block !important;
    font-weight: bold !important;
}
```

## Database Search Function

For debugging, a database search function is provided to examine ACF hint content directly:

```php
function lilac_search_acf_hints_db_callback() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'acf_hint_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }
    
    global $wpdb;
    
    // Search the database for hint fields
    $query = $wpdb->prepare(
        "SELECT post_id, meta_key, meta_value 
         FROM {$wpdb->prefix}postmeta 
         WHERE meta_key = %s OR meta_key LIKE %s",
        'add_hint',
        '%field_67b1c991e4d16%'
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    wp_send_json_success([
        'results' => $results,
        'count' => count($results)
    ]);
}
```

## Testing the Implementation

1. Open a LearnDash quiz with hints enabled
2. Click the hint button for a question
3. The hint should appear with a yellow background
4. If ACF content exists, it will display that content
5. If ACF content is missing, it will display the original answer with yellow highlighting

## Troubleshooting

1. **Hint not appearing**: Check browser console for AJAX errors
2. **Wrong hint content**: Verify the question ID mapping in `functions.php`
3. **Missing highlighting**: Ensure CSS is properly loaded
4. **Nonce errors**: Verify the nonce name matches in both PHP and JavaScript

## Extending the System

To add more question hints:
1. Create ACF fields with the field name `add_hint` for your posts
2. Add mappings in the `$id_mapping` array in `functions.php`
3. Test each question to ensure the hint displays correctly
