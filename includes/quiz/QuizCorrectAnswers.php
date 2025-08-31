<?php
/**
 * Quiz Correct Answers
 * 
 * A simple, direct solution to retrieve correct answers from the LearnDash database
 * without unnecessary complexity.
 */

/**
 * Get correct answers for a quiz
 * 
 * @param int $quiz_id The quiz ID
 * @return array Question ID => correct answer(s)
 */
function lilac_get_quiz_correct_answers($quiz_id) {
    global $wpdb;
    
    // Initialize empty answers array
    $answers = array();
    
    if (empty($quiz_id)) {
        return $answers;
    }
    
    // Get quiz pro ID (needed for database queries)
    $quiz_pro_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
    
    if (empty($quiz_pro_id)) {
        error_log("Missing quiz_pro_id for quiz {$quiz_id}");
        return $answers;
    }
    
    // 1. Get question IDs for this quiz
    $question_ids = array();
    
    // Try LearnDash API first
    if (function_exists('learndash_get_quiz_questions')) {
        $questions = learndash_get_quiz_questions($quiz_id);
        
        if (!empty($questions)) {
            foreach ($questions as $question) {
                if (is_object($question)) {
                    $question_ids[] = array(
                        'post_id' => $question->ID,
                        'pro_id' => get_post_meta($question->ID, 'question_pro_id', true)
                    );
                }
            }
        }
    }
    
    // If no questions found yet, try direct DB query
    if (empty($question_ids)) {
        $question_table = $wpdb->prefix . 'learndash_pro_quiz_question';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$question_table}'");
        
        if ($table_exists) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$question_table} WHERE quiz_id = %d",
                $quiz_pro_id
            );
            
            $results = $wpdb->get_results($query);
            
            if (!empty($results)) {
                foreach ($results as $result) {
                    $question_ids[] = array(
                        'post_id' => 0, // No post ID in this case
                        'pro_id' => $result->id
                    );
                }
            }
        }
    }
    
    // 2. For each question, find the correct answer
    foreach ($question_ids as $question) {
        // Skip if we don't have a pro_id
        if (empty($question['pro_id'])) {
            continue;
        }
        
        $question_pro_id = $question['pro_id'];
        $question_post_id = $question['post_id'];
        
        // Query the answer table
        $answer_table = $wpdb->prefix . 'learndash_pro_quiz_answer';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$answer_table}'");
        
        if (!$table_exists) {
            // Also try without prefix
            $answer_table = 'wp_learndash_pro_quiz_answer';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$answer_table}'");
            
            if (!$table_exists) {
                continue;
            }
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$answer_table} WHERE question_id = %d ORDER BY sort ASC",
            $question_pro_id
        );
        
        $results = $wpdb->get_results($query);
        
        if (!empty($results)) {
            // Find correct answer(s)
            $correct_indices = array();
            
            foreach ($results as $index => $answer) {
                if (!empty($answer->correct)) {
                    // Use 1-based index to match frontend
                    $correct_indices[] = $index + 1;
                }
            }
            
            if (!empty($correct_indices)) {
                // Use post_id as the key if available, otherwise use pro_id
                $key = !empty($question_post_id) ? $question_post_id : $question_pro_id;
                
                // Store as comma-separated list
                $answers[$key] = implode(',', $correct_indices);
            }
        }
    }
    
    return $answers;
}
