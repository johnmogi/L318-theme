<?php
/**
 * Quiz Inspector - Shows details about quiz questions and correct answers
 */

// Bootstrap WordPress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Ensure only admins can access this script
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

// Function to get quiz info
function get_quiz_details($quiz_id = null) {
    global $wpdb;
    
    echo '<div style="max-width: 1200px; margin: 0 auto; font-family: sans-serif;">';
    echo '<h1>Quiz Details Inspector</h1>';
    
    // If no quiz_id, show list of quizzes
    if (!$quiz_id) {
        $quizzes = get_posts([
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1
        ]);
        
        echo '<h2>Available Quizzes</h2>';
        echo '<ul>';
        foreach ($quizzes as $quiz) {
            echo '<li><a href="?quiz_id=' . $quiz->ID . '">' . $quiz->post_title . '</a> (ID: ' . $quiz->ID . ')</li>';
        }
        echo '</ul>';
        return;
    }
    
    // Get quiz information
    $quiz = get_post($quiz_id);
    if (!$quiz) {
        echo '<div class="error">Quiz not found!</div>';
        return;
    }
    
    echo '<h2>Quiz: ' . esc_html($quiz->post_title) . ' (ID: ' . $quiz_id . ')</h2>';
    
    // Get quiz questions
    $quiz_meta = get_post_meta($quiz_id, '_sfwd-quiz', true);
    $pro_quiz_id = isset($quiz_meta['sfwd-quiz_quiz_pro']) ? $quiz_meta['sfwd-quiz_quiz_pro'] : null;
    
    if (!$pro_quiz_id) {
        echo '<div class="error">Pro Quiz ID not found for this quiz.</div>';
        return;
    }
    
    echo '<p>Pro Quiz ID: ' . $pro_quiz_id . '</p>';
    
    // Get questions for this quiz
    $quiz_questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}learndash_pro_quiz_question 
        WHERE quiz_id = %d 
        ORDER BY sort",
        $pro_quiz_id
    ));
    
    if (empty($quiz_questions)) {
        echo '<div class="error">No questions found for this quiz.</div>';
        return;
    }
    
    echo '<h3>' . count($quiz_questions) . ' Questions Found</h3>';
    
    // Loop through questions and display details
    foreach ($quiz_questions as $index => $question) {
        echo '<div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 5px;">';
        echo '<h4>' . ($index + 1) . '. ' . esc_html($question->title) . '</h4>';
        
        // Question content
        echo '<p><strong>Question:</strong> ' . nl2br(esc_html($question->question)) . '</p>';
        
        // Question type
        $question_types = [
            'single' => 'Single Choice',
            'multiple' => 'Multiple Choice',
            'free_answer' => 'Free Answer',
            'sort_answer' => 'Sorting',
            'matrix_sort_answer' => 'Matrix Sorting',
            'cloze_answer' => 'Fill in the Blank',
            'assessment_answer' => 'Assessment',
            'essay' => 'Essay'
        ];
        
        $question_type = isset($question_types[$question->answer_type]) ? $question_types[$question->answer_type] : $question->answer_type;
        echo '<p><strong>Type:</strong> ' . $question_type . '</p>';
        
        // Get answers
        $answers = maybe_unserialize($question->answer_data);
        
        if (!empty($answers) && is_array($answers)) {
            echo '<p><strong>Answer Options:</strong></p>';
            echo '<table style="width:100%; border-collapse: collapse;">';
            echo '<tr style="background-color: #f0f0f0;"><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Option</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Text</th><th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Correct?</th></tr>';
            
            foreach ($answers as $key => $answer) {
                $is_correct = isset($answer['correct']) && $answer['correct'] ? '✓ Yes' : 'No';
                $background = isset($answer['correct']) && $answer['correct'] ? '#d4edda' : '';
                
                echo '<tr style="background-color: ' . $background . ';">';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . ($key + 1) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($answer['answer']) . '</td>';
                echo '<td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">' . $is_correct . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>No answer data found for this question.</p>';
        }
        
        // Get WordPress Question ID
        $wp_question_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'question_pro_id' AND meta_value = %d 
            LIMIT 1",
            $question->id
        ));
        
        if ($wp_question_id) {
            echo '<p><strong>WordPress Question ID:</strong> ' . $wp_question_id . '</p>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
}

// Get quiz ID from URL parameter
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : null;

// Basic styling
echo '<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .error { color: red; font-weight: bold; padding: 10px; border: 1px solid red; margin: 10px 0; }
    h1, h2, h3, h4 { color: #0073aa; }
    a { color: #0073aa; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>';

// Display quiz details
get_quiz_details($quiz_id);
?>
