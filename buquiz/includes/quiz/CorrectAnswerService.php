<?php
/**
 * Correct Answer Service
 * 
 * Handles retrieving correct answers for LearnDash quiz questions
 * in a reliable, testable way without DOM parsing or guesswork.
 */

class Lilac_CorrectAnswerService {
    /**
     * Get all correct answers for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @return array Associative array of question IDs to correct answer values
     */
    /**
     * Get all correct answers for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @return array Associative array of question IDs to correct answer values
     */
    public function get_quiz_correct_answers($quiz_id = null) {
        global $wpdb;
        
        if (!$quiz_id) {
            // Get current quiz ID if not provided
            global $post;
            if (!$post || $post->post_type !== 'sfwd-quiz') {
                return array();
            }
            $quiz_id = $post->ID;
        }
        
        $answer_map = array();
        
        // Enable error logging for debugging
        error_log('[Lilac Quiz] Getting correct answers for quiz ID: ' . $quiz_id);
        
        try {
            // Get all questions for this quiz using LearnDash's API
            $questions = learndash_get_quiz_questions($quiz_id);
            
            if (empty($questions)) {
                error_log('[Lilac Quiz] No questions found for quiz ID: ' . $quiz_id);
                return $answer_map;
            }
            
            error_log('[Lilac Quiz] Found ' . count($questions) . ' questions for quiz ID: ' . $quiz_id);
            
            foreach ($questions as $question) {
                if (!is_object($question) || !isset($question->ID)) {
                    error_log('[Lilac Quiz] Invalid question object');
                    continue;
                }
                
                $question_id = $question->ID;
                $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                $question_type = get_post_meta($question_id, 'question_type', true);
                
                if (empty($question_pro_id)) {
                    error_log(sprintf(
                        '[Lilac Quiz] No pro_id found for question %d (type: %s)', 
                        $question_id, 
                        $question_type
                    ));
                    continue;
                }
                
                // Get answer data from the database
                $answer_data = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wp_pro_quiz_answer 
                         WHERE question_id = %d 
                         ORDER BY sort ASC",
                        $question_pro_id
                    )
                );
                
                if (empty($answer_data)) {
                    error_log('[Lilac Quiz] No answer data for question ID: ' . $question_id);
                    continue;
                }
                
                // Process answers based on question type
                $correct_answers = $this->get_correct_answers_by_type($answer_data, $question_type);
                
                if (!empty($correct_answers)) {
                    $answer_map[$question_id] = $correct_answers;
                    error_log(sprintf(
                        '[Lilac Quiz] Added %d correct answer(s) for question %d (type: %s)', 
                        is_array($correct_answers) ? count($correct_answers) : 1,
                        $question_id,
                        $question_type
                    ));
                } else {
                    error_log('[Lilac Quiz] No correct answers found for question ID: ' . $question_id);
                }
            }
            
        } catch (Exception $e) {
            error_log('[Lilac Quiz] Error getting correct answers: ' . $e->getMessage());
        }
        
        return $answer_map;
    }
    
    /**
     * Get correct answers based on question type
     *
     * @param array $answers Array of answer objects
     * @param string $question_type The question type
     * @return mixed Array of correct answers or single answer
     */
    protected function get_correct_answers_by_type($answers, $question_type) {
        $correct_answers = array();
        
        foreach ($answers as $index => $answer) {
            switch ($question_type) {
                case 'single':
                    if (!empty($answer->correct)) {
                        // Return as string for single choice (1-based index)
                        return (string)($index + 1);
                    }
                    break;
                    
                case 'multiple':
                    if (!empty($answer->correct)) {
                        // For multiple choice, collect all correct answers
                        $correct_answers[] = (string)($index + 1);
                    }
                    break;
                    
                case 'free_answer':
                    if (!empty($answer->answer)) {
                        // For free answer questions, return the first answer text
                        return $answer->answer;
                    }
                    break;
                    
                // Add more question types as needed
                
                default:
                    // For other types, try to determine correct answer
                    if (property_exists($answer, 'correct') && $answer->correct) {
                        $correct_answers[] = (string)($index + 1);
                    }
                    break;
            }
        }
        
        // Return appropriate format based on question type
        if ($question_type === 'multiple' && !empty($correct_answers)) {
            return $correct_answers;
        }
        
        return !empty($correct_answers) ? $correct_answers[0] : '';
    }
}
