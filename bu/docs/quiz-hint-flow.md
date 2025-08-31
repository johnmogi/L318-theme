# Quiz Hint Flow Documentation

## Overview
This document outlines the implementation details of the quiz hint functionality, specifically focusing on the "Force Hint" feature that controls quiz progression based on hint viewing and correct answer selection.

## Current Implementation

### Core Files
1. **force-hint-flow.js**
   - Controls the quiz flow when force hint mode is enabled
   - Manages next button visibility based on hint viewing and correct answers
   - Tracks user interactions with hints and answers

2. **disable-hints.js**
   - Handles the display of hints based on quiz settings
   - Provides admin debugging capabilities
   - Controls hint visibility throughout the quiz

3. **acf-hint-highlights.css**
   - Styles for hint highlights and visual indicators
   - Uses subtle colors for better user experience

### Key Functionality

#### Force Hint Mode
- When enabled, the next button is initially hidden
- Students must view the hint before being able to proceed
- The next button only becomes available after:
  1. The student views the hint
  2. The student selects the correct answer

#### Visual Indicators
- Hint buttons are styled to be noticeable but not distracting
- Selected answers are highlighted (currently in green, which may need adjustment)
- Admin indicators are shown when in admin mode for debugging

## Current Issues

1. **Visual Feedback**
   - The current green highlight for selected answers (`user-selected` class) is too prominent
   - Hint visibility states could be more intuitive

2. **Flow Control**
   - Need to ensure the next button state is properly managed
   - Should handle edge cases like page refresh or navigation away

3. **Admin Tools**
   - Debug information should be properly hidden in production
   - Admin controls should be more clearly separated

## Technical Implementation Details

### Data Flow
1. Quiz initialization loads hint settings
2. Force hint mode is checked
3. Event listeners are set up for:
   - Hint button clicks
   - Answer selection
   - Quiz navigation

### Key Functions

#### `updateNextButtonStatus($question)`
- Controls the visibility of the next button
- Checks if:
  - Hint has been viewed (`hintViewed`)
  - Correct answer has been selected (`answeredCorrectly`)
- Updates button state accordingly

#### `handleHintClick(questionId)`
- Tracks when a hint is viewed
- Updates the UI to indicate hint has been seen
- May trigger next button state update

## Future Enhancements

1. **Visual Improvements**
   - More subtle color scheme for selected answers
   - Better visual feedback for hint states
   - Improved admin interface

2. **Functionality**
   - Add support for different hint modes
   - Implement hint usage analytics
   - Add hint timing controls (e.g., delay before showing hint)

3. **Code Quality**
   - Add JSDoc comments
   - Improve error handling
   - Add unit tests

## Testing Requirements

1. **Test Cases**
   - [ ] Force hint mode enables correctly
   - [ ] Next button is initially hidden
   - [ ] Next button appears only after viewing hint and correct answer
   - [ ] Hint visibility toggles correctly
   - [ ] Admin debug tools work as expected
   - [ ] Styling is consistent across devices

## Dependencies
- WordPress 5.0+
- LearnDash LMS
- Advanced Custom Fields Pro (for hint content)
- jQuery (bundled with WordPress)

## Related Documentation
- [Quiz Database Structure](quizDbFix.md)
- [Quiz Rebuilding Notes](21REbuildingQuiz.md)
- [Feature Requests](21featureRequestsQuiz.md)

## Changelog

### 2025-05-13
- Initial documentation created
- Outlined current implementation and future improvements
