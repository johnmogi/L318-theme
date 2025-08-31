# 04: Enforce Hint After Wrong Answer

## 1. Problem

Currently in LearnDash quizzes, when a student selects a wrong answer and the quiz is configured to show feedback ("Correct" or "Incorrect"), they are allowed to continue to the next question without retrying. This leads to superficial engagement and prevents students from reflecting on mistakes.

We want to change this flow by enforcing students to:

1. View the hint (if present) after a wrong answer.
2. Re-attempt the same question until the correct answer is selected.
3. Only proceed once the correct answer is submitted.

This feature should be **opt-in** via the existing "Quiz Sidebar Settings" metabox using the **"Enforce Hint"** checkbox.

## 2. Acceptance Criteria

* [ ] A new behavior activates **only if** `_ld_quiz_enforce_hint` meta is set to true for the quiz.
* [ ] After a wrong answer is selected:

  * [ ] The student sees a visual cue (e.g. error message, red highlight).
  * [ ] The **hint block** is revealed if available.
  * [ ] All answer inputs remain **re-editable** (do not lock).
  * [ ] The **Next** button is hidden/disabled.
* [ ] Once the correct answer is selected:

  * [ ] The **Next** button is shown/enabled.
  * [ ] Student can continue to the next question.
* [ ] Works correctly across different LearnDash quiz types (single/multi-select, true/false).
* [ ] Compatible with the existing sidebar template: `single-sfwd-quiz-sidebar.php`.
* [ ] Fully functional on desktop and mobile.
* [ ] No jQuery dependencies added.

## 3. Suggested Approach (developer decides final implementation)

* Use server-side logic to detect when `_ld_quiz_enforce_hint` is enabled and enqueue additional logic as needed.
* Implement a front-end JavaScript enhancement that observes answer selections, controls hint visibility, and toggles the Next button based on answer correctness.
* Optionally use LearnDash question review APIs or JS events (e.g., `ldQuestionCheck`) if available.
* Ensure that students cannot proceed via keyboard or browser navigation tricks.

## 4. References

* `functions.php` metabox code (see `01fix.md`)
* Sidebar template: `templates/learndash/single-sfwd-quiz-sidebar.php`
* Quiz behavior examples: `wpProQuiz_question`, `wpProQuiz_questionInput`, `.wpProQuiz_hint`
* Related docs:

  * `01fix.md`, `1singleQuizTemplate.md` (quiz settings and meta integration)
  * `02fix.md`, `03fix.md` (sidebar functionality and cleanup)
  * `taskGuide.md` (task template reference)

---

> Dev should create `04fix.md` once implementation is complete. Be sure to document any files/scripts added or modified. Ensure changes are isolated and don't affect quizzes without "Enforce Hint" enabled.


To resolve the issue and achieve the desired UX behavior, here's a clear update instruction for the dev, integrating the expected flow based on your full description. You can integrate this into a task file or send it directly to the dev team:

---

## 🔁 Requested UX Modification – Hint Enforcement Behavior Update

### 🎯 Goal

After the student answers a question in a LearnDash quiz:

1. ✅ If the answer is **correct**:

   * Immediate feedback is shown (e.g. "נכון")
   * The **Next** button appears to continue

2. ❌ If the answer is **wrong**:

   * Show **"טעית! להמשך קח רמז"**
   * **Hide** the Next button
   * Show and enable the **"קחו רמז"** (Hint) button
   * After opening the Hint:

     * The student should be able to click **"סמנו רמז"** (Reveal Highlight)
     * See the **highlighted hint**
     * Then close the hint and **reselect another answer**
   * Only when the correct answer is finally chosen, the Next button appears

---

### ⚠️ Current Problems

* ❌ **Answer input is disabled** after first selection → Student cannot re-edit their choice
* ❌ **Hint area (`.wpProQuiz_tipp`)** is set to `display: none` and/or commented out
* ❌ **Next button (`name=\"next\"`)** appears even after wrong answer (sometimes via LearnDash default)

---

### ✅ What Needs to Change

#### 1. Unlock Answer Re-selection

Ensure that after a **wrong** answer, all radio inputs for that question **remain enabled** and can be changed. Remove `disabled="disabled"` programmatically if needed.

#### 2. Hint Flow Script

* Ensure the **"קחו רמז"** (Take a Hint) button is always visible at the bottom of the question panel
* When a wrong answer is selected:

  * Show a message “טעית! להמשך קח רמז.”
  * Force display of `.wpProQuiz_tipp`
  * Enable interaction: “סמנו רמז” + “סגרו רמז”
* After hint is revealed and closed, user can change their answer
* If the new selection is correct → Show the Next button

#### 3. Hide/Show the Next Button Dynamically

Ensure the button:

```html
<input type="button" name="next" value="סיים מבחן" class="wpProQuiz_button wpProQuiz_QuestionButton">
```

...is hidden (`display: none`) unless:

* The current answer is correct
* The hint has been seen (if user previously got it wrong)

---

### 🛠️ Developer Notes

* Update JavaScript tied to `.wpProQuiz_questionInput` change events
* Hint system must work **dynamically** per question
* Minimize DOM manipulation overhead; use `classList.toggle()` and `style.display` patterns
* Optional: attach a `data-hint-shown` attribute to track if the hint was triggered
* Respect the `_ld_quiz_enforce_hint` meta — only activate this flow when it's set to true

