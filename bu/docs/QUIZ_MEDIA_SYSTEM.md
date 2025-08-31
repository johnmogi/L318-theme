# Quiz Media System Documentation
cd C:/Users/anist/Desktop/CLIENTS/AVIV/LILAC/app/public/wp-content/themes/hello-theme-child-master

## Overview
This document details the quiz media system that dynamically injects and manages media content (videos and images) in the quiz sidebar based on question data from ACF fields.

## Table of Contents
1. [System Architecture](#system-architecture)
2. [File Structure](#file-structure)
3. [Core Functions](#core-functions)
4. [JavaScript Injection Process](#javascript-injection-process)
5. [Performance Optimization Tips](#performance-optimization-tips)
6. [Troubleshooting](#troubleshooting)
7. [Example Data Format](#example-data-format)

## System Architecture

The quiz media system consists of:

1. **Frontend JavaScript** - Handles dynamic media loading and display
2. **WordPress Backend** - Provides ACF field data via AJAX
3. **Sidebar Template** - The target container for media injection

## File Structure

```
themes/hello-theme-child-master/
├── js/
│   ├── quiz-question-media.js  # Core media handling logic
│   └── quiz-performance.js      # Performance monitoring & error handling
├── single-sfwd-quiz.php         # Quiz template file
└── functions.php                # WordPress hooks and enqueues
```

## Core Functions

### 1. `lilac_enqueue_quiz_scripts` (single-sfwd-quiz.php)
- Enqueues required JavaScript files
- Sets up question ID to post ID mapping
- Localizes script data for AJAX

### 2. `quizQuestionMedia` Object (quiz-question-media.js)
Main controller object with these key methods:

- `init()`: Initializes the media handler
- `fetchQuestionData()`: Fetches ACF data for a question
- `handleAjaxResponse()`: Processes AJAX response
- `updateSidebarWithMedia()`: Updates sidebar with media content
- `getVideoHtml()`: Generates HTML for video embeds
- `getMediaHtml()`: Generates HTML for images
- `getHintHtml()`: Generates HTML for hints

## JavaScript Injection Process

### 1. Initialization
- The system initializes when the quiz page loads
- Sets up MutationObserver to detect question changes
- Initializes event listeners for navigation

### 2. Question Detection
- Detects when a new question is displayed
- Extracts question ID and other metadata
- Triggers data fetching if not already cached

### 3. Data Fetching
- Makes AJAX request to WordPress backend
- Includes error handling and retry logic
- Caches responses to minimize server requests

### 4. Media Processing
1. Checks `choose_media` ACF field for media type preference
2. Validates available media URLs
3. Generates appropriate HTML based on media type
4. Handles fallbacks if preferred media is unavailable

### 5. DOM Injection
- Clears existing sidebar content
- Injects loading indicator
- Replaces with media content when ready
- Applies any necessary styling

## Performance Optimization Tips

### 1. Caching Strategies
- Enable response caching:
  ```javascript
  // In fetchQuestionData()
  const cacheKey = 'question_' + questionPostId;
  if (this.questions[cacheKey]) {
      this.handleAjaxResponse({ success: true, data: this.questions[cacheKey] }, questionElement);
      return;
  }
  ```

### 2. Lazy Loading
- Load media only when needed:
  ```javascript
  // For images
  <img data-src="image.jpg" class="lazyload">
  
  // For videos
  <div data-video-id="ABC123" class="lazy-video"></div>
  ```

### 3. Optimize AJAX Requests
- Batch requests when possible
- Use debouncing for rapid question changes
- Implement proper error handling with retries

### 4. DOM Optimization
- Use document fragments for batch DOM updates
- Minimize layout thrashing
- Cache jQuery selectors

### 5. Asset Optimization
- Compress images and videos
- Use responsive images with srcset
- Consider using WebP format with fallbacks

## Troubleshooting

### Common Issues

1. **Media Not Loading**
   - Check browser console for errors
   - Verify ACF field names match exactly
   - Ensure proper user permissions for AJAX

2. **Incorrect Media Type Displayed**
   - Verify `choose_media` field value in ACF
   - Check for JavaScript errors
   - Inspect network requests for correct data

3. **Performance Issues**
   - Check for memory leaks
   - Profile JavaScript performance
   - Review network waterfall in DevTools

## Example Data Format

### AJAX Response Example
```json
{
    "video_url": "https://www.youtube.com/watch?v=ABC123",
    "rich_media": "1499",
    "rich_media_url": "https://example.com/wp-content/uploads/image.jpg",
    "choose_media": "סרטון",
    "add_hint": "This is a helpful hint"
}
```

### Supported Video Platforms
- YouTube
- Vimeo
- Self-hosted MP4

## Debugging

### Console Commands
```javascript
// Check available question data
console.log(quizQuestionData);

// Access media handler instance
window.quizQuestionMedia

// Force debug mode
window.quizQuestionMedia.debugMode = true;
```

### Logging
- All important actions are logged to the console when `debugMode` is true
- Network requests can be inspected in the Network tab
- Breakpoints can be set in the Sources panel

## Best Practices

1. Always test with both video and image content
2. Verify mobile responsiveness
3. Check performance on slower connections
4. Test with various ACF field combinations
5. Monitor JavaScript console for errors

## Future Improvements

1. Implement Intersection Observer for lazy loading
2. Add support for more video platforms
3. Implement adaptive bitrate streaming
4. Add analytics for media interactions
5. Improve error recovery and user feedback
