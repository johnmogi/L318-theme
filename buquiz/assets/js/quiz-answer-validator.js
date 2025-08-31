/**
 * Quiz Answer Validator
 * 
 * Integrates with the server-provided correct answers to validate user selections.
 * Works with the quiz-answer-reselection.js to ensure a seamless user experience.
 */
(function($) {
    'use strict';

    // Debug logging
    const DEBUG = true;
    const log = (...args) => DEBUG && console.log('[QUIZ VALIDATOR]', ...args);

    // Store the correct answers when they become available
    let correctAnswers = {};
    
    // Initialize the validator
    function initQuizValidator() {
        log('Initializing Quiz Answer Validator');
        
        // Load correct answers from server data
        if (window.lilacQuizData && window.lilacQuizData.correctAnswers) {
            correctAnswers = window.lilacQuizData.correctAnswers;
            log('Correct Answers Loaded:', correctAnswers);
        } else {
            log('No correct answers found in lilacQuizData');
        }
        
        // If we couldn't get answers from the server, try to use the quiz question data
        if (Object.keys(correctAnswers).length === 0) {
            extractAnswersFromDOM();
        }
        
        // Set up event listeners for quiz interactions
        setupEventListeners();
        
        // Add debug panel if needed
        if (DEBUG) {
            addDebugPanel();
        }
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners() {
        // Listen for form submission
        $(document).on('submit', '.wpProQuiz_quiz form', function(e) {
            // Don't prevent the default submission, just add validation
            validateCurrentAnswer();
        });
        
        // Listen for learndash question navigation
        $(document).on('learndash-quiz-question-loaded', function(e) {
            log('Question changed, preparing validator');
            // Wait a bit for the UI to settle
            setTimeout(prepareValidator, 200);
        });
        
        // Initial preparation
        prepareValidator();
    }
    
    /**
     * Prepare the validator for the current question
     */
    function prepareValidator() {
        // Find the current visible question
        const $currentQuestion = $('.wpProQuiz_listItem:visible');
        if (!$currentQuestion.length) return;
        
        // Get the question ID
        const questionId = getQuestionId($currentQuestion);
        if (!questionId) {
            log('Could not determine question ID');
            return;
        }
        
        log('Preparing validator for question ID:', questionId);
        
        // Add a data attribute to the question for later reference
        $currentQuestion.attr('data-lilac-question-id', questionId);
        
        // Setup check mark indicators next to each answer
        setupAnswerIndicators($currentQuestion);
    }
    
    /**
     * Add visual indicators next to each answer option
     */
    function setupAnswerIndicators($question) {
        $question.find('.wpProQuiz_questionListItem').each(function(index) {
            // Check if indicator already exists
            if ($(this).find('.lilac-answer-indicator').length === 0) {
                // Add indicator span
                $(this).append('<span class="lilac-answer-indicator" style="margin-left: 10px; display: none;"></span>');
            }
        });
    }
    
    /**
     * Validate the current answer against the known correct answers
     */
    function validateCurrentAnswer() {
        const $currentQuestion = $('.wpProQuiz_listItem:visible');
        if (!$currentQuestion.length) return;
        
        // Get the question ID
        const questionId = getQuestionId($currentQuestion);
        if (!questionId) {
            log('Could not determine question ID for validation');
            return;
        }
        
        // Get the correct answer for this question
        const correctAnswer = correctAnswers[questionId];
        if (!correctAnswer) {
            log('No correct answer found for question ID:', questionId);
            return;
        }
        
        log('Validating answer for question ID:', questionId, 'Correct answer:', correctAnswer);
        
        // Mark the correct answer(s) in the UI
        markCorrectAnswers($currentQuestion, correctAnswer);
    }
    
    /**
     * Mark the correct answers in the UI
     */
    function markCorrectAnswers($question, correctAnswer) {
        // Parse the correct answer (could be multiple, comma-separated)
        const correctIndices = correctAnswer.toString().split(',').map(i => parseInt(i, 10));
        
        // Mark each answer option
        $question.find('.wpProQuiz_questionListItem').each(function(index) {
            const $indicator = $(this).find('.lilac-answer-indicator');
            
            // 1-based index to match LearnDash's indexing
            const answerIndex = index + 1;
            
            // Check if this is a correct answer
            if (correctIndices.includes(answerIndex)) {
                $indicator.html('✓').css('color', 'green').show();
            } else {
                $indicator.html('✗').css('color', 'red').show();
            }
        });
    }
    
    /**
     * Get the question ID using multiple methods (similar to quiz-sidebar-media.js)
     */
    function getQuestionId($question) {
        // Try method 1: From question-meta data attribute
        const questionMeta = $question.data('question-meta');
        if (questionMeta && questionMeta.question_post_id) {
            return questionMeta.question_post_id;
        }
        
        // Try method 2: From the question list
        const $questionList = $question.find('.wpProQuiz_questionList');
        if ($questionList.length && $questionList.data('question-id')) {
            return $questionList.data('question-id');
        }
        
        // Try method 3: From a previous data attribute we might have set
        const savedId = $question.attr('data-lilac-question-id');
        if (savedId) {
            return savedId;
        }
        
        // Try method 4: From global quiz question data based on index
        if (window.quizQuestionData) {
            const index = $question.index();
            if (index >= 0 && window.quizQuestionData[index]) {
                return window.quizQuestionData[index];
            }
        }
        
        return null;
    }
    
    /**
     * Try to extract answers from the DOM if server data isn't available
     */
    function extractAnswersFromDOM() {
        log('Attempting to extract answers from DOM');
        
        // Check for global quiz data
        if (window.wpProQuizInitList) {
            for (const quizId in window.wpProQuizInitList) {
                const quizData = window.wpProQuizInitList[quizId];
                if (quizData && quizData.json && quizData.json.questions) {
                    log('Found quiz data, processing questions');
                    
                    // Process each question
                    Object.keys(quizData.json.questions).forEach(qId => {
                        const q = quizData.json.questions[qId];
                        if (q && q.correct) {
                            // Find the corresponding question DOM element
                            const $questionElement = $(`.wpProQuiz_listItem[data-question-id="${qId}"]`);
                            if ($questionElement.length) {
                                const questionPostId = getQuestionId($questionElement);
                                if (questionPostId) {
                                    // Format the correct answer
                                    const correctIndices = [];
                                    q.correct.forEach((isCorrect, idx) => {
                                        if (isCorrect) correctIndices.push(idx + 1);
                                    });
                                    
                                    if (correctIndices.length) {
                                        correctAnswers[questionPostId] = correctIndices.join(',');
                                        log('Extracted answer for question', questionPostId, correctAnswers[questionPostId]);
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    }
    
    /**
     * Add a debug panel to the quiz page
     */
    function addDebugPanel() {
        // Create a floating debug box
        const $debug = $('<div id="quiz-answer-debug-panel">')
            .css({
                position: 'fixed',
                right: '10px',
                bottom: '10px', 
                width: '300px',
                backgroundColor: 'rgba(0,0,0,0.8)',
                color: '#fff',
                padding: '10px',
                zIndex: 99999,
                fontSize: '12px',
                maxHeight: '40vh',
                overflowY: 'auto',
                border: '1px solid #333'
            })
            .append('<h3>Quiz Answer Debug</h3>')
            .append('<div><strong>Answers loaded:</strong> ' + Object.keys(correctAnswers).length + '</div>')
            .append('<div id="answer-debug-list"><strong>Answer Map:</strong></div>')
            .appendTo('body');
        
        // Add detected answers
        const $answerList = $('#answer-debug-list');
        Object.keys(correctAnswers).forEach(questionId => {
            $answerList.append(`<div style="margin-top: 5px;">Q${questionId}: <span style="color: yellow;">${correctAnswers[questionId]}</span></div>`);
        });
        
        // Hide button
        $('<button>')
            .text('Toggle Answer Debug')
            .css({
                position: 'fixed',
                bottom: '5px',
                right: '5px',
                zIndex: 100000,
                fontSize: '10px',
                padding: '3px',
                opacity: '0.7'
            })
            .on('click', function() {
                $('#quiz-answer-debug-panel').toggle();
            })
            .appendTo('body');
    }

    // Initialize on document ready
    $(document).ready(initQuizValidator);

})(jQuery);
