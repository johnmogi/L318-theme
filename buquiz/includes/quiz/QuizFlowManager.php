<?php
/**
 * Quiz Flow Manager
 * 
 * Manages the quiz flow, including loading correct answers
 * and controlling UI behavior based on answer validation.
 */

class Lilac_QuizFlowManager {
    /**
     * Instance of the CorrectAnswerService
     * 
     * @var Lilac_CorrectAnswerService
     */
    private $answer_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(__FILE__) . '/CorrectAnswerService.php';
        $this->answer_service = new Lilac_CorrectAnswerService();
        
        // Initialize on template_redirect to ensure we're on a quiz page
        add_action('template_redirect', array($this, 'init'));
        
        // Register scripts with appropriate priority
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
    }
    
    /**
     * Initialize the quiz flow functionality
     */
    public function init() {
        // Only apply to quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        
        // Add body class for CSS targeting
        add_filter('body_class', function($classes) {
            $classes[] = 'lilac-quiz-enabled';
            $classes[] = 'lilac-quiz-hint-enforced';
            return $classes;
        });
        
        // Enqueue scripts with proper priority
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
    }
    
    /**
     * Enqueue scripts and styles for quiz answer flow
     */
    public function enqueue_scripts() {
        // Only enqueue on quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        $enforce_hint = get_post_meta($quiz_id, 'lilac_quiz_enforce_hint', true);
        
        // Only apply to quizzes with enforce hint enabled
        if ($enforce_hint !== '1') {
            error_log('[Lilac Quiz] Enforce hint not enabled for quiz ' . $quiz_id);
            return;
        }
        
        error_log('[Lilac Quiz] Initializing quiz flow for quiz ' . $quiz_id);
        
        // Get all correct answers for this quiz
        $answers = $this->answer_service->get_quiz_correct_answers($quiz_id);
        
        // Debug log the answers we found
        error_log('[Lilac Quiz] Correct answers: ' . print_r($answers, true));
        
        // Enqueue with proper dependencies - make sure LearnDash scripts are loaded first
        wp_enqueue_script(
            'lilac-quiz-answer-flow',
            get_stylesheet_directory_uri() . '/includes/quiz/assets/js/quiz-answer-flow.js',
            array('jquery', 'ld-quiz-script', 'wpProQuiz_front_js'), // Depend on LearnDash quiz scripts
            filemtime(get_stylesheet_directory() . '/includes/quiz/assets/js/quiz-answer-flow.js'),
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('lilac-quiz-answer-flow', 'lilacQuizData', array(
            'correctAnswers' => $answers,
            'enforceHint' => true,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_quiz_nonce'),
            'quizId' => $quiz_id,
            'debug' => true
        ));
        
        // Enqueue our custom styles
        wp_enqueue_style(
            'lilac-quiz-styles',
            get_stylesheet_directory_uri() . '/includes/quiz/assets/css/quiz-styles.css',
            array(),
            filemtime(get_stylesheet_directory() . '/includes/quiz/assets/css/quiz-styles.css')
        );
        
        // Initialize the flow by providing correct answers
        $this->init();
    }
}
