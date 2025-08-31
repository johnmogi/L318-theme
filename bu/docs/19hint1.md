# LearnDash Hint System Documentation

## Current Hint System Overview

The LearnDash platform currently has a built-in hint highlighting system that displays the correct answer with yellow highlighting. This feature is important to preserve as a secondary fallback mechanism.

### Current Implementation

The existing hint system uses HTML markup like this:

```html
<span class="highlight-hint hint-correct-answer active">המשתמש בדרך לנסיעה, להליכה, לעמידה או לכל מטרה אחרת.</span>
```

This mechanism:
1. Uses the `.highlight-hint` class to identify content that can be highlighted
2. Has an additional `.hint-correct-answer` class to mark the correct answer
3. Applies yellow highlighting when the "סמן רמז" (Mark Hint) button is clicked

## Enhanced ACF Hint Integration

We've enhanced this system to prioritize custom hints from ACF fields while maintaining the original functionality as a fallback.

### ACF Field Structure

The ACF field is configured as:
- **Field Group**: "שדה רמז לדף מבחנים" (Hint Field for Quiz Page)
- **Field Name**: "add_hint" 
- **Field Type**: WYSIWYG
- **Instructions**: יש להוסיף את ה-class : highlight-hint לטקסט של הרמז

### Integration Logic

1. When a user clicks "סמן רמז" (Mark Hint), JavaScript first attempts to retrieve the ACF content for that question
2. If ACF content exists, it's displayed with yellow highlighting
3. If no ACF content is found, the system falls back to the built-in hint highlighting

### Technical Implementation

The integration uses:
1. AJAX to retrieve ACF content based on the question ID
2. jQuery to insert the content into the appropriate container
3. CSS to apply the yellow highlighting to the hint content

### Console Logs

Console logs show the question_meta data structure:
```
TipButton question_meta parsed: {type: 'single', question_pro_id: 49, question_post_id: 948}
```

This data helps identify which WordPress post contains the ACF field data.

## Integration Flow

1. User clicks "סמן רמז" (Mark Hint)
2. System identifies the question's post ID from question_meta
3. AJAX request fetches the ACF field content (if exists)
4. Content is inserted into the hint container with yellow highlighting
5. If no ACF content is found, system highlights the built-in hint

## Future Enhancements

Potential improvements include:
- Caching ACF content to reduce AJAX calls
- Adding more styling options for different hint types
- Incorporating multimedia hint content (images, videos)
