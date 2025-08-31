# Clocked Quiz Implementation

**Date**: 2025-05-05

## Requirements
- [ ] Time-limited questions
- [ ] Auto-save progress
- [ ] Immediate results display
- [ ] Robust image handling

## Technical Approach
1. **Timing Mechanism**:
   - Use JavaScript `setInterval` for countdown
   - Store remaining time in sessionStorage

2. **Auto-Save**:
   - Debounced AJAX calls on answer changes
   - Local fallback using localStorage

3. **Results Flow**:
   - Server-side score calculation
   - Client-side rendering with animations

## Files Affected
- `includes/quiz/timer.js`
- `learndash-hints/results-display.php`
- `assets/quiz-styles.css`

## Testing Plan
1. Verify timer accuracy across browsers
2. Test auto-save during network drops
3. Validate image fallback behavior

---

## 🧾 Developer Brief: Real-Time Exam System + Error Report Module

### 🎯 Goal

Implement a real-time exam system where users are tested under time constraints with strict pass/fail conditions, immediate result feedback, and a detailed error report. All user notifications and interactions must go through the existing **session message board** system.

---

## 🛠️ Developer Tasks Breakdown

### 1. **Exam Profile Configuration**

#### Requirements:

* Each exam uses a predefined profile:

  * **Profile Name** (e.g., “Standard Test”, “Traffic Safety Education”)
  * **Time Limit** (in minutes)
  * **Passing Threshold** (number of mistakes allowed)

#### Tasks:

* Create a `TestProfile` model with the fields above
* Allow test sessions to pull the correct profile based on user/program type
* Include the profile metadata in exam initialization

---

### 2. **Exam Timer Logic**

#### Requirements:

* Show a countdown timer during the test
* Alert the user as time is running out
* Auto-submit test when time expires

#### Tasks:

* Implement frontend countdown timer with warning threshold (e.g., 5 mins left)
* Trigger automatic exam submission at 0:00
* Ensure answers are saved progressively (auto-save)

---

### 3. **Pass/Fail Evaluation Logic**

#### Requirements:

* Based on number of mistakes:

  * **Standard Test**: Pass if ≤ 4 mistakes
  * **Traffic Safety**: Pass if ≤ 6 mistakes
* Display summary:

  * Result: **Passed / Failed**
  * Personalized message: “You made X mistakes”

#### Tasks:

* Implement a `ResultEvaluator` class/function
* Return both boolean status and mistake count message
* Inject result into session end flow

---

### 4. **Detailed Error Report**

#### Requirements:

* Display full table of incorrect answers:

  * **Question**
  * **User’s Answer**
  * **Correct Answer**
  * **Topic / Subject**
  * **Image** (if present; must be included in the report)
* Flag missing media where expected

#### Tasks:

* Extend result payload with incorrect answers and metadata
* For each failed question, check for linked image and include it
* If an image is missing but should exist, flag the issue in logs and UI (via system message)

---

### 5. **Inactivity Warning System**

#### Requirements:

* Detect inactivity during the test

  * Send message: “We haven't seen any activity. Are you still here?” Yes / No
* If unanswered within timeout (e.g., 30 seconds) → auto-submit test

#### Tasks:

* Implement client-side idle detection (mouse, keyboard inactivity)
* Trigger session message via existing message board
* If no user interaction within timeout, force test submission with appropriate log reason

---

### 6. **Integration with Session Message Board**

#### Requirements:

* All alerts and system messages must go through the existing messaging system, including:

  * Inactivity prompt
  * Time warnings
  * Final results
  * System errors or flags

#### Tasks:

* Create structured event emitters to post messages through the board
* Standardize message formats (JSON templates, severity levels, action buttons)

---

### 7. **Result Persistence + Analytics**

#### Requirements:

* Save full session data:

  * Answer set
  * Time started / ended
  * Mistake count
  * Final result (pass/fail)
* Tag any flagged questions (e.g., missing media, ambiguous answers)

#### Tasks:

* Extend `TestAttempt` or equivalent schema with full data set
* Ensure result is stored and queryable for backend reporting
* Add flags/tags per question if needed

---

## 🧪 QA Acceptance Criteria

* [ ] Timed test auto-submits correctly
* [ ] Inactivity system triggers message and timeout properly
* [ ] Pass/fail thresholds behave according to profile
* [ ] Error report shows complete info (question, user answer, correct, topic, image)
* [ ] Missing images raise visual/log flags
* [ ] All user notifications are routed through the session message system

---

## 📌 Notes

* No hints or external assistance during the exam
* Auto-save answers on each change or time interval
* Report and results are visible immediately after test ends
* Use consistent message styling for the message board
* Ensure image handling is robust (missing, corrupt, etc.)
