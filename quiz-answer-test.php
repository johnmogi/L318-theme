<?php
/**
 * Quiz Answer Test
 * 
 * This is a simple test script that demonstrates we can extract all correct answers
 * from a LearnDash quiz immediately when the page loads.
 */

// Only run this on quiz pages
add_action('wp_footer', 'lilac_quiz_answer_test');

function lilac_quiz_answer_test() {
    if (!is_singular('sfwd-quiz')) {
        return;
    }

    global $post;
    $quiz_id = $post->ID;
    $answer_map = array();
    
    // Get all questions for this quiz
    $questions = learndash_get_quiz_questions($quiz_id);
    
    if (!empty($questions)) {
        // Prepare the answer map
        foreach ($questions as $question) {
            $question_id = $question->ID;
            $question_post_id = $question_id; // This is what's used in the frontend
            $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
            $question_type = get_post_meta($question_id, 'question_type', true);
            
            // Get answer data from database
            global $wpdb;
            $answer_data = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wp_pro_quiz_answer WHERE question_id = %d ORDER BY sort ASC",
                    $question_pro_id
                )
            );
            
            if (!empty($answer_data)) {
                // For single choice questions
                if ($question_type === 'single') {
                    foreach ($answer_data as $index => $answer) {
                        if ($answer->correct) {
                            // Add 1 because LearnDash uses 1-indexed values in the frontend
                            $answer_map[$question_post_id] = strval($index + 1);
                            break;
                        }
                    }
                }
                // Add other question types as needed
            }
        }
    }
    
    // Output the answer data to JavaScript
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // This demonstrates we have access to all correct answers IMMEDIATELY when page loads
        var correctAnswers = <?php echo json_encode($answer_map); ?>;
        
        console.log('[QUIZ DEBUG] *** COMPLETE LIST OF CORRECT ANSWERS ***');
        console.log(correctAnswers);
        console.log('[QUIZ DEBUG] ***************************************');
        
        // Now verify we can access the correct answer for each question
        setTimeout(function() {
            var questions = document.querySelectorAll('.wpProQuiz_listItem');
            questions.forEach(function(question) {
                var meta = question.getAttribute('data-question-meta');
                if (meta) {
                    try {
                        var parsed = JSON.parse(meta);
                        var questionId = parsed.question_post_id;
                        if (questionId && correctAnswers[questionId]) {
                            console.log('[QUIZ DEBUG] Question ID ' + questionId + ' has correct answer: ' + correctAnswers[questionId]);
                        } else {
                            console.log('[QUIZ DEBUG] Could not find correct answer for question ID ' + questionId);
                        }
                    } catch(e) {
                        console.error('Error parsing question meta', e);
                    }
                }
            });
        }, 1000);
    });
    </script>
    <?php
}
