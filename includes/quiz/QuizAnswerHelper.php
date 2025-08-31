<?php
/**
 * Quiz Answer Helper
 * 
 * Provides helper functions to retrieve correct answers for quiz questions.
 * This is used in conjunction with the quiz-answer-validation.js script.
 */

class QuizAnswerHelper {
    /**
     * Get correct answers for all questions in a quiz
     *
     * @param int $quiz_id The ID of the quiz
     * @return array Array of correct answers in the format [question_id => correct_answer_value]
     */
    public static function get_correct_answers($quiz_id) {
        $correct_answers = array();
        
        // Get all questions for this quiz
        $questions = learndash_get_quiz_questions($quiz_id);
        
        if (!empty($questions)) {
            foreach ($questions as $question) {
                if (!is_object($question)) {
                    continue;
                }
                
                $question_id = $question->ID;
                $question_meta = get_post_meta($question_id);
                $question_pro_id = isset($question_meta['question_pro_id']) ? $question_meta['question_pro_id'][0] : '';
                
                if (empty($question_pro_id)) {
                    continue;
                }
                
                // Get correct answer for this question
                $correct_answer = self::get_question_correct_answer($question_pro_id);
                
                if ($correct_answer !== false) {
                    $correct_answers[$question_id] = $correct_answer;
                }
            }
        }
        
        return $correct_answers;
    }
    
    /**
     * Get the correct answer for a specific question
     *
     * @param int $question_pro_id The pro question ID
     * @return mixed The correct answer value or false if not found
     */
    public static function get_question_correct_answer($question_pro_id) {
        global $wpdb;
        
        // Possible answer tables in different LearnDash setups
        $possible_answer_tables = array(
            "{$wpdb->prefix}learndash_pro_quiz_answer",
            "{$wpdb->prefix}wp_pro_quiz_answer",
            "wp_learndash_pro_quiz_answer",
            "wp_wp_pro_quiz_answer",
            "learndash_pro_quiz_answer"
        );
        
        foreach ($possible_answer_tables as $table) {
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (empty($table_exists)) {
                continue;
            }
            
            // Get answers for this question
            $query = $wpdb->prepare("SELECT * FROM {$table} WHERE question_id = %d ORDER BY sort ASC", $question_pro_id);
            $results = $wpdb->get_results($query);
            
            if (!empty($results) && !$wpdb->last_error) {
                // Look for correct answers
                foreach ($results as $index => $answer) {
                    if (isset($answer->correct) && $answer->correct) {
                        // LearnDash uses 1-indexed values for answer options
                        return $index + 1;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Output correct answers as JavaScript
     *
     * @param int $quiz_id The quiz ID
     * @return string JavaScript with correct answers
     */
    public static function output_answers_js($quiz_id) {
        $correct_answers = self::get_correct_answers($quiz_id);
        
        // Convert to JavaScript object
        $js_object = json_encode($correct_answers);
        
        $output = "<script type='text/javascript'>\n";
        $output .= "/* <![CDATA[ */\n";
        $output .= "var quizCorrectAnswers = " . $js_object . ";\n";
        $output .= "console.log('[QUIZ ANSWERS] Loaded correct answers from server:', quizCorrectAnswers);\n";
        $output .= "/* ]]> */\n";
        $output .= "</script>\n";
        
        return $output;
    }
}
