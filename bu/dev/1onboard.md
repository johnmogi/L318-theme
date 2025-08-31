✅ DEVELOPMENT PLAN: LearnDash Customization
1. 🔧 Custom Quiz Templates (Override LearnDash Output)
Goal: Remove correct answers + inject hint logic
Implementation:
Override LearnDash template:
Copy from
wp-content/plugins/sfwd-lms/themes/ld30/quiz/
into your theme folder:
your-theme/learndash/quiz/

Key templates to override:

question.php → render question, add Hint button

quiz_attempt.php → prevent answer reveal

Remove Correct Answer:
php
Copy
Edit
add_filter('learndash_show_quiz_correct_answers', '__return_false');
2. 💡 Add Hint Button Logic
Hint Button Frontend (JS):
Insert a <button class="hint-btn">קח רמז</button> near each question

Toggle a .hint-box area on click

Prevent re-answering until hint clicked after mistake

Backend Logic:
Use learndash_save_quiz_question filter to:

Track incorrect answers

Save if hint was used (via AJAX)

Store in usermeta or a custom DB table

3. 📊 Track Hint Usage Per Question
Storage Options:
usermeta:
user_hint_usage_quiz_{quiz_id} = array of question_id => used

OR

Custom table:
quiz_hint_tracking(user_id, quiz_id, question_id, used_hint)

You’ll also need:

AJAX endpoint to store hint usage

JS event listener on “קח רמז” click

4. 🧩 Custom Field or Post Type for Quiz Type
Purpose:
Distinguish בין:

נושאים (pink)

תרגול (green)

אמת (purple)

Implementation:
Use Advanced Custom Fields (ACF) or register_post_meta() to define a field:

php
Copy
Edit
register_post_meta('sfwd-quiz', '_quiz_type', [
  'show_in_rest' => true,
  'single' => true,
  'type' => 'string',
]);
Add choices:

nosim

practice

real

Use this to control:

Whether hints show

Timer inclusion

Grading logic

5. 🖥️ Student Dashboard Page
Display:
Tests completed per type

Hints used

Average score (real tests)

Resume previous test (if abandoned)

Tools:
Use shortcode: [student_dashboard]

WP_Query for quizzes + user quiz data (learndash_get_user_quiz_attempts())

6. 📋 Demo / Test Pages for Each Quiz Type
Pages:
/quiz-nosim/ → Query all quizzes of type nosim

/quiz-practice/

/quiz-real/

Implementation:
Use LearnDash shortcodes or custom template with quiz picker

For real quizzes: add timer, disable hints

7. 🔁 Hint UI Logic (JS + PHP Sync)
JS:
Click "קח רמז" = open popup/hint area

Store in local state + send via AJAX to backend

PHP:
Hook AJAX into saving hint usage (store per user/quiz/question)

Check saved value on page load → disable UI if needed

8. 📈 Progress Tracking Page
Display per user:
Topic quizzes: done, hints used, % completed

Practice: same

Real tests: scores + average, time taken

Mistake table with:

Question

Answer

Correct answer

Topic

Image if present

Data Sources:
learndash_get_user_quiz_attempts()

Quiz metadata

Your custom hint tracking

Build custom tables/views if needed

⚙️ OPTIONAL NEXT
If time allows today:

Create a settings page (admin) to:

Enable/disable hint usage

Toggle “show correct answers”

