<?php
/**
 * Quiz Answer Fetcher
 *
 * Provides reliable access to correct answers for LearnDash quizzes by directly
 * querying the database. Implements multiple fallback mechanisms to ensure
 * answers can be retrieved regardless of the specific LearnDash setup.
 */
class QuizAnswerFetcher {
    
    /**
     * Get correct answers for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @return array Associative array with question IDs as keys and correct answers as values
     */
    public function get_quiz_answers($quiz_id) {
        if (empty($quiz_id)) {
            return array();
        }
        
        // Get the quiz pro ID (needed for database queries)
        $quiz_pro_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        
        if (empty($quiz_pro_id)) {
            error_log("QuizAnswerFetcher: Missing quiz_pro_id for quiz {$quiz_id}");
            return array();
        }
        
        // Fetch questions using multiple methods
        $questions = $this->get_quiz_questions($quiz_id, $quiz_pro_id);
        
        if (empty($questions)) {
            error_log("QuizAnswerFetcher: No questions found for quiz {$quiz_id}");
            return array();
        }
        
        // Process each question to get answers
        $answers = array();
        foreach ($questions as $question) {
            if (empty($question['id'])) {
                continue;
            }
            
            $question_id = $question['id'];
            $question_pro_id = $question['pro_id'] ?? null;
            
            // Try to get correct answer
            $correct_answer = $this->get_correct_answer($question_id, $question_pro_id);
            
            if ($correct_answer) {
                $answers[$question_id] = $correct_answer;
            }
        }
        
        return $answers;
    }
    
    /**
     * Get all questions for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @param int $quiz_pro_id The quiz pro ID
     * @return array List of question data
     */
    private function get_quiz_questions($quiz_id, $quiz_pro_id) {
        $questions = array();
        
        // Method 1: Try LearnDash API first
        if (function_exists('learndash_get_quiz_questions')) {
            $questions_posts = learndash_get_quiz_questions($quiz_id);
            
            if (!empty($questions_posts)) {
                foreach ($questions_posts as $question_post) {
                    if (!is_object($question_post)) {
                        continue;
                    }
                    
                    $question_id = $question_post->ID;
                    $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                    
                    $questions[] = array(
                        'id' => $question_id,
                        'pro_id' => $question_pro_id,
                        'title' => $question_post->post_title
                    );
                }
                
                // If we found questions, return them
                if (!empty($questions)) {
                    return $questions;
                }
            }
        }
        
        // Method 2: Try direct database query for questions
        global $wpdb;
        
        $question_table_candidates = array(
            $wpdb->prefix . 'learndash_pro_quiz_question',
            'wp_learndash_pro_quiz_question'
        );
        
        foreach ($question_table_candidates as $table) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                $sql = $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE quiz_id = %d ORDER BY sort ASC",
                    $quiz_pro_id
                );
                
                $question_results = $wpdb->get_results($sql, ARRAY_A);
                
                if (!empty($question_results)) {
                    foreach ($question_results as $result) {
                        $questions[] = array(
                            'id' => $result['id'],
                            'pro_id' => $result['id'], // In this case, the ID is the pro ID
                            'title' => $result['title']
                        );
                    }
                    
                    // If we found questions, return them
                    if (!empty($questions)) {
                        return $questions;
                    }
                }
            }
        }
        
        // Method 3: Search for sfwd-question post type linked to this quiz
        $args = array(
            'post_type' => 'sfwd-question',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'quiz_id',
                    'value' => $quiz_id,
                    'compare' => '='
                )
            )
        );
        
        $question_query = new WP_Query($args);
        
        if ($question_query->have_posts()) {
            while ($question_query->have_posts()) {
                $question_query->the_post();
                $question_id = get_the_ID();
                $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                
                $questions[] = array(
                    'id' => $question_id,
                    'pro_id' => $question_pro_id,
                    'title' => get_the_title()
                );
            }
            
            wp_reset_postdata();
        }
        
        return $questions;
    }
    
    /**
     * Get the correct answer for a specific question
     *
     * @param int $question_id The question post ID
     * @param int|null $question_pro_id The question pro ID (optional)
     * @return string|null The correct answer or null if not found
     */
    private function get_correct_answer($question_id, $question_pro_id = null) {
        global $wpdb;
        
        // Try method 1: Get from post meta
        $correct_answer_meta = get_post_meta($question_id, '_correct_answer', true);
        if (!empty($correct_answer_meta)) {
            return $correct_answer_meta;
        }
        
        // Try method 2: Get from another common meta key
        $correct_answer_meta = get_post_meta($question_id, 'correct_answer', true);
        if (!empty($correct_answer_meta)) {
            return $correct_answer_meta;
        }
        
        // If we have the pro ID, try the database tables
        if (!empty($question_pro_id)) {
            // Try method 3: Query the answer table
            $answer_table_candidates = array(
                $wpdb->prefix . 'learndash_pro_quiz_answer',
                'wp_learndash_pro_quiz_answer'
            );
            
            foreach ($answer_table_candidates as $table) {
                // Check if table exists
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                    $sql = $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE question_id = %d AND correct = 1 ORDER BY sort ASC",
                        $question_pro_id
                    );
                    
                    $answers = $wpdb->get_results($sql);
                    
                    if (!empty($answers)) {
                        // Format the correct answer as a comma-separated list of indices (1-based)
                        $correct_indices = array();
                        
                        foreach ($answers as $index => $answer) {
                            // Add 1 to index because LearnDash uses 1-based indices in the UI
                            $correct_indices[] = $index + 1;
                        }
                        
                        if (!empty($correct_indices)) {
                            return implode(',', $correct_indices);
                        }
                    }
                }
            }
        }
        
        // Try method 4: Check for common question data structures in post content
        $question_post = get_post($question_id);
        if (!empty($question_post->post_content)) {
            // Look for JSON data in post_content that might contain answer info
            if (preg_match('/{.*"correct":\s*(true|1).*}/i', $question_post->post_content, $matches)) {
                // Extract correct answer from JSON
                if (preg_match_all('/"answerText":\s*"([^"]+)"/i', $matches[0], $answer_matches)) {
                    return implode(',', $answer_matches[1]);
                }
            }
        }
        
        // No correct answer found
        return null;
    }
}
