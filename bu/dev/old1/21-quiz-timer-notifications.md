# Quiz Timer Notifications System

**Date**: 2025-05-05

## Overview
This module provides reliable timer notifications for LearnDash quizzes. It displays warnings at configurable time thresholds (60s, 30s, 10s) and when the timer expires. It also includes a test panel for triggering these notifications manually.

## Implementation Details

### Component Files
1. `includes/quiz/timer-notifications/init.php`: Main PHP loader
2. `includes/quiz/timer-notifications/timer-observer.js`: LearnDash timer monitoring
3. `includes/quiz/timer-notifications/timer-ui.js`: Test panel UI
4. `includes/quiz/timer-notifications/styles.css`: UI styling
5. `includes/quiz/quiz-timer-integration.php`: WordPress integration

### Key Features
- Reliable timer detection that doesn't rely solely on MutationObserver
- Test control panel (toggle with Ctrl+Shift+T)
- Multiple notification thresholds (60s, 30s, 10s)
- End-of-timer notification
- Inactivity notification
- Demo mode for testing without real timer changes

### Technical Details
- Uses interval-based polling instead of MutationObserver to avoid infinite loops
- Hooks into LearnDash timer element `.wpProQuiz_time_limit .time span`
- Uses LilacToast for notifications
- Control panel visible only to administrators
- RTL-compatible UI

## Installation
1. Create the required directories:
   ```
   mkdir -p includes/quiz/timer-notifications
   ```
2. Copy the component files to their respective locations
3. Add to functions.php:
   ```php
   // Load Quiz Timer Notifications
   require_once get_stylesheet_directory() . '/includes/quiz/quiz-timer-integration.php';
   ```

## Testing
1. Login as an administrator
2. Navigate to any quiz with a timer
3. Press Ctrl+Shift+T to show the test panel
4. Click the test buttons to trigger various notifications
5. Toggle "Demo Mode" to enable fake notifications without waiting for real timer events

## Technical Notes
- Only loads on pages with quizzes (auto-detected)
- Handles both quiz post types and shortcodes
- Uses file modification time for cache busting
- Integrates with the LearnDash quiz debug environment
