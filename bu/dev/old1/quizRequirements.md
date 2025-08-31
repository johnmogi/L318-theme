# Quiz Hint Feature - Requirements

## 0. Module Structure
- All hint feature code lives in its own subfolder within the child theme: `learndash-hints/`.
- Example structure:
  - `learndash-hints/learndash-hints.php` – main loader.
  - `learndash-hints/js/` – JavaScript assets (UI & AJAX).
  - `learndash-hints/css/` – Stylesheet for hint UI.
  - `learndash-hints/templates/` – Override or inject template parts.
- This modular folder can be removed or enhanced independently.

## 1. Overview
Provide a contextual hint for each Learndash quiz question by exposing the correct answer on-demand. This will aid learners and support debugging by revealing the correct response when requested.

## 2. Objectives
- Allow learners to request a hint for the current question without submitting the quiz.
- Display the correct answer as a hint in a non-intrusive overlay or integrated UI block.
- Ensure minimal performance impact and secure access to correct answers.

## 3. User Stories
1. **Learner requests hint**: As a learner, I want to click a "Show Hint" button to see a clue or the correct answer for the current question.
2. **Instructor debug mode**: As a developer or instructor in debug mode, I want to view correct answers in console/log for troubleshooting.

## 4. Functional Requirements
- **Fetch correct answer**:
  - Option A: AJAX endpoint (`ld_debug_get_correct_answer`) invoked on hint request.
  - Option B: Preload all correct answers via `wp_localize_script` into a JS object.
- **UI Components**:
  - "Show Hint" button rendered per question.
  - Hint container (modal or inline box) to display text/html hint.
  - "Close Hint" control to hide the container.
- **Integration**:
  - Hooks into Learndash question render (template filter) to append hint controls.
  - Graceful degradation if JS is disabled (hide hint button).

## 5. Non-Functional Requirements
- **Performance**: AJAX calls should be cached; preloaded data size must be reasonable.
- **Security**: Only users with appropriate capabilities or in debug mode may fetch hints.
- **Compatibility**: Support latest Learndash and Hello Elementor child theme.

## 6. Data Flow
1. User clicks "Show Hint"
2. JS determines question_id
3. If AJAX: POST to `admin-ajax.php?action=ld_debug_get_correct_answer`
4. Receive `{'success':true,'data':{'correct':"..."}}`
5. Populate hint container and display

## 7. Error Handling
- Display error message if AJAX fails or response invalid.
- Log errors to console in debug mode.

## 8. Acceptance Criteria
- Hint button appears for every quiz question.
- Correct answer is displayed on hint request within 1s.
- No errors in console under normal operation.
- UI is responsive and accessible.

---
_Add any further notes or decisions here._

✅ תואם:
- לחצן "קח רמז" קיים – מאפשר צפייה בתשובה הנכונה לפי בקשת המשתמש.
- התצוגה אינה פולשנית (inline/modal) – זה נכון לאפיון.
- מתוכננת תמיכה בפר טמפלט – מתאים למבנה Learndash.
- קיימת תמיכה בהתאמה לפי הרשאות – אבטחה בהתאם.

⚠️ חסר/דורש התאמה:
1. **שלב ביניים – "קחו רמז" פותח חומר לימוד**, לא ישר תשובה.
   → נדרש להציג טקסט הסבר / חלק מהתוכן הלימודי, ולא מיד את התשובה.

2. **שלב שני – "סמנו רמז"**:
   → רק אחרי לחיצה נוספת, מופיעה התשובה המדויקת עם הדגשה (צהוב).
   → כרגע זה לא קיים במימוש – אפשר להוסיף toggle נוסף או 2 מצבים לתשובה (רגילה > ממורקרת).

3. **שליטה בזרימה**:
   → לאחר טעות, אין אפשרות להתקדם עד שמפעילים את הרמז.
   → צריך לבדוק אם המימוש עוצר את ההתקדמות עד שהרמז נלקח.

4. **מעקב אחרי שימוש ברמזים**:
   → האם שומר בלוג אם המשתמש לקח רמז? (דרוש לדוחות / מעקב מורה)

5. **אין הבדל לפי סוג מבחן (וורוד/ירוק)**:
   → כדאי לעדכן שהרמזים זמינים רק במבחני נושא / תרגול, ולא בזמן אמת (סגול).

🔁 הצעה פשוטה:
להוסיף לשלב הנוכחי:
- שני מצבים לרמז: הסבר לימודי (קצר) > תשובה ממוקדת.
- תנאי: אין המשך לשאלה הבאה אם לא הופעל הרמז אחרי טעות.

✅ קו פעולה:
המשיכו עם הפיצ'ר – אבל הגדירו שלבים ברורים לרמז (כפתור > תוכן > סימון), כדי לא לשכתב אחר כך את ה-UI או הלוגיקה.
