<?php
/**
 * Quiz Server Integration
 *
 * Integrates the QuizAnswerFetcher with WordPress and provides
 * correct answers to the frontend JavaScript.
 */
class QuizServerIntegration {
    
    /**
     * Initialize the integration
     */
    public function init() {
        // Load required files
        require_once dirname(__FILE__) . '/QuizAnswerFetcher.php';
        
        // Add hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and localize data
     */
    public function enqueue_scripts() {
        // Only load on quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        // Get the quiz ID
        $quiz_id = get_the_ID();
        
        // Get correct answers for this quiz
        $answers = $this->get_quiz_answers($quiz_id);
        
        // Add debug information to the page
        add_action('wp_footer', function() use ($quiz_id, $answers) {
            echo '<script>console.log("[QUIZ DEBUG] Quiz ID: ' . esc_js($quiz_id) . '");</script>';
            echo '<script>console.log("[QUIZ DEBUG] Correct Answers: ' . esc_js(json_encode($answers)) . '");</script>';
        });
        
        // Enqueue the validator script
        wp_enqueue_script(
            'lilac-quiz-answer-validator',
            get_stylesheet_directory_uri() . '/assets/js/quiz-answer-validator.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/quiz-answer-validator.js'),
            true
        );
        
        // Pass the answers to JavaScript
        wp_localize_script(
            'lilac-quiz-answer-validator',
            'lilacQuizData',
            array(
                'quizId' => $quiz_id,
                'correctAnswers' => $answers
            )
        );
    }
    
    /**
     * Get correct answers for a quiz
     *
     * @param int $quiz_id The quiz post ID
     * @return array Associative array with question IDs as keys and correct answers as values
     */
    private function get_quiz_answers($quiz_id) {
        $fetcher = new QuizAnswerFetcher();
        $answers = $fetcher->get_quiz_answers($quiz_id);
        
        return $answers;
    }
}
