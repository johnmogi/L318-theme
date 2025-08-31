The quiz markup you’re seeing—

html
CopyInsert
<input type="button" … class="wpProQuiz_TipButton" …>
<div class="wpProQuiz_tipp">…</div>
—actually comes from the WP Pro Quiz library that ships with LearnDash. WP Pro Quiz renders both the “Tip” button and the <div class="wpProQuiz_tipp"> wrapper around whatever hint text you enter in the quiz builder (the default hint field on each question).

If you want to inject your own ACF‐powered <span> inside that wrapper and then highlight it on click, you have two easy routes:

Override the plugin template
• Copy
/wp-content/plugins/sfwd-lms/public/partials/quiz-question.php
into
/wp-content/themes/hello-theme-child-master/learndash/quiz-question.php
• Inside the hint section, echo your ACF field:
php
CopyInsert
<?php if ( $html = get_field('custom_hint_span', $question_id) ): ?>
  <span class="highlight-hint"><?= esc_html( $html ) ?></span>
<?php endif; ?>
Use WP Pro Quiz’s filter
Add this to your child-​theme’s functions.php (or your hints module):
php
CopyInsert
add_filter('wp_pro_quiz_tipp_output', function($html, $questionId){
  $span = get_field('custom_hint_span', $questionId);
  if ( $span ) {
    $html .= '<span class="highlight-hint">'. esc_html($span) .'</span>';
  }
  return $html;
}, 10, 2);
Then in your existing JS click handler for #mark-hint, just target .highlight-hint and apply background: yellow.

# Quiz Hint Implementation Plan

1. ACF Configuration:
   - Create ACF field group “Quiz Hint”.
   - Add a field `custom_hint_span` (Text Area) for each question.
2. Inject custom hint span:
   - Use `add_filter('wp_pro_quiz_tipp_output', …)` to append `<span class="highlight-hint" data-question-id="…">…</span>`.
3. JS modifications:
   - Update `js/learndash-debug.js`:
     - On `#mark-hint` click, select `.highlight-hint[data-question-id="…"]` and add `.active` to apply yellow background.
     - On `#close-hint` click, remove `.active`.
     - Disable WP Pro Quiz’s correct-answer color by overriding CSS or removing `.wpProQuiz_answerCorrect` class.
4. CSS overrides:
   - Define `.highlight-hint.active { background: yellow; }`.
   - Override `.wpProQuiz_answerCorrect { background: none !important; }`.
5. Design adjustments:
   - Tweak button placement and spacing in `.btn-wrapper`.
   - Ensure hint container width and typography are consistent.
6. Testing:
   - Verify hints highlight correctly per question.
   - Confirm that correct answer green styling is disabled when marking wrong.
   - Test across multiple quizzes.

Let me know if you’d like me to generate the ACF group setup or drop in the exact filter+JS snippets.