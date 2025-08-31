<?php
/**
 * Quiz Functionality Loader
 * 
 * Bootstraps the refactored quiz functionality by loading required
 * classes and initializing components needed for quiz behavior.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the QuizFlowManager class
require_once dirname(__FILE__) . '/QuizFlowManager.php';

/**
 * Initialize the Quiz Flow Manager
 */
function lilac_init_quiz_flow() {
    // Create instance of the Quiz Flow Manager
    $quiz_manager = new Lilac_QuizFlowManager();
    
    // Initialize it
    return $quiz_manager;
}

// Initialize the quiz flow
$lilac_quiz_manager = lilac_init_quiz_flow();
