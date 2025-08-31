/**
 * Lilac Quiz Answer Flow - Enhanced
 * 
 * Handles quiz answer validation and UI behavior with improved debugging and reliability
 */

(function($) {
    'use strict';
    
    // Debug logging function
    function debugLog() {
        if (window.console && window.console.log) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[Lilac Quiz Debug]');
            console.log.apply(console, args);
        }
    }
    
    // Configuration
    var config = {
        debug: true,
        checkDelay: 300, // ms to wait after check button click
        retryDelay: 100, // ms to wait between DOM checks
        maxRetries: 10,  // max number of retries for DOM elements
        ajaxUrl: lilacQuizData?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php',
        nonce: lilacQuizData?.nonce || ''
    };
    
    // State
    var state = {
        quizId: 0,
        enforceHint: false,
        correctAnswers: {},
        initialized: false,
        learnDashReady: false,
        questions: {},
        ajaxInProgress: false,
        pendingAjaxCalls: 0
    };
    
    // Initialize with data from wp_localize_script if available
    if (typeof lilacQuizData !== 'undefined') {
        state.quizId = lilacQuizData.quizId || 0;
        state.enforceHint = lilacQuizData.enforceHint === true;
        state.correctAnswers = lilacQuizData.correctAnswers || {};
        
        debugLog('Quiz initialized with data', {
            quizId: state.quizId,
            enforceHint: state.enforceHint,
            correctAnswers: state.correctAnswers
        });
        
        // Check enforce hint status via AJAX if we have a quiz ID
        if (state.quizId) {
            checkEnforceHintStatus(state.quizId);
        }
    } else {
        debugLog('Warning: lilacQuizData not found. Quiz functionality may be limited.');
    }
    
    /**
     * Check if enforce hint is enabled for the current quiz via AJAX
     */
    function checkEnforceHintStatus(quizId) {
        if (state.ajaxInProgress) {
            state.pendingAjaxCalls++;
            return;
        }
        
        state.ajaxInProgress = true;
        
        debugLog('Checking enforce hint status for quiz', quizId);
        
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'check_quiz_enforce_hint',
                quiz_id: quizId,
                nonce: config.nonce
            },
            dataType: 'json',
            success: function(response) {
                debugLog('Enforce hint status response:', response);
                
                if (response && response.success) {
                    state.enforceHint = response.enforce_hint === true;
                    debugLog('Enforce hint status updated:', state.enforceHint);
                    
                    // Update UI based on enforce hint status
                    updateUIForEnforceHint();
                } else {
                    debugLog('Failed to get enforce hint status:', response?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                debugLog('Error checking enforce hint status:', error);
            },
            complete: function() {
                state.ajaxInProgress = false;
                
                // Process any pending AJAX calls
                if (state.pendingAjaxCalls > 0) {
                    state.pendingAjaxCalls--;
                    checkEnforceHintStatus(quizId);
                }
            }
        });
    }
    
    /**
     * Update UI based on enforce hint status
     */
    function updateUIForEnforceHint() {
        debugLog('Updating UI for enforce hint:', state.enforceHint);
        
        // Add/remove enforce hint class to body
        if (state.enforceHint) {
            $('body').addClass('lilac-enforce-hint');
        } else {
            $('body').removeClass('lilac-enforce-hint');
        }
        
        // Update any existing questions
        processExistingQuestions();
    }
    
    /**
     * Check if LearnDash is loaded and ready
     */
    function isLearnDashReady() {
        return typeof wpProQuizData !== 'undefined' && 
               typeof wpProQuizData.nonce !== 'undefined';
    }
    
    /**
     * Wait for LearnDash to be ready
     */
    function whenLearnDashReady(callback, retryCount) {
        retryCount = retryCount || 0;
        
        if (isLearnDashReady()) {
            state.learnDashReady = true;
            debugLog('LearnDash is ready');
            callback();
        } else if (retryCount < 10) { // Max 10 retries (5 seconds)
            debugLog('Waiting for LearnDash to be ready...');
            setTimeout(function() {
                whenLearnDashReady(callback, retryCount + 1);
            }, 500);
        } else {
            debugLog('Error: LearnDash not loaded after multiple attempts');
        }
    }
    
    /**
     * Initialize the quiz functionality
     */
    function init() {
        if (state.initialized) {
            debugLog('Already initialized');
            return;
        }
        
        debugLog('Initializing quiz answer flow');
        
        // Wait for LearnDash to be ready
        whenLearnDashReady(function() {
            // Set up event listeners
            $(document)
                .on('change', '.wpProQuiz_questionInput', handleAnswerSelection)
                .on('click', 'input[name="check"]', handleCheckButton);
            
            // Process existing questions
            processExistingQuestions();
            
            state.initialized = true;
            debugLog('Quiz answer flow initialized');
            
            // Check for questions again after a delay
            setTimeout(processExistingQuestions, 1000);
        });
    }
    
    /**
     * Handle answer selection events
     */
    function handleAnswerSelection(e) {
        var $input = $(e.target);
        if (!$input.hasClass('wpProQuiz_questionInput')) {
            return;
        }
        
        var $questionItem = $input.closest('.wpProQuiz_listItem');
        if (!$questionItem.length) {
            return;
        }
        
        var questionId = $questionItem.data('question-id') || '';
        var questionMeta = $questionItem.data('question-meta');
        
        if (questionMeta) {
            try {
                var meta = JSON.parse(questionMeta);
                questionId = meta.question_post_id || questionId;
            } catch(e) {
                debugLog('Error parsing question meta:', e);
            }
        }
        
        debugLog('Selected answer for QID ' + questionId + ':', $input.val());
        
        // If this is a single-choice question, we can check if it's correct immediately
        if ($input.attr('type') === 'radio') {
            checkAnswerImmediately($questionItem, questionId, $input.val());
        }
    }
    
    /**
     * Check answer immediately for single-choice questions
     */
    function checkAnswerImmediately($questionItem, questionId, answerValue) {
        if (!state.enforceHint || !questionId || !state.correctAnswers[questionId]) {
            return;
        }
        
        // Check if the answer is correct
        var isCorrect = state.correctAnswers[questionId] === answerValue;
        
        if (isCorrect) {
            // If correct, ensure the Next button is visible
            var $nextButton = $questionItem.find('.wpProQuiz_button[name="next"]');
            if ($nextButton.length && $nextButton.css('display') === 'none') {
                $nextButton.show();
                debugLog('Correct answer selected, showing Next button');
            }
        }
    }
    
    /**
     * Handle check button clicks
     */
    function handleCheckButton(e) {
        var $button = $(e.target);
        if (!$button.is('input[name="check"]')) {
            return;
        }
        
        debugLog('Check button clicked');
        
        // Wait for LearnDash to process the answer
        setTimeout(function() {
            var $questionItem = $button.closest('.wpProQuiz_listItem');
            processCheckedAnswer($questionItem);
        }, config.checkDelay);
    }
    
    /**
     * Process the result of a checked answer
     */
    function processCheckedAnswer($questionItem) {
        if (!$questionItem.length) {
            return;
        }
        
        // Get question ID
        var questionId = $questionItem.data('question-id') || '';
        var questionMeta = $questionItem.data('question-meta');
        
        if (questionMeta) {
            try {
                var meta = JSON.parse(questionMeta);
                questionId = meta.question_post_id || questionId;
            } catch(e) {
                debugLog('Error parsing question meta:', e);
            }
        }
        
        // Check if answer is correct
        var isCorrect = $questionItem.find('.wpProQuiz_correct:visible').length > 0;
        var isIncorrect = $questionItem.find('.wpProQuiz_incorrect:visible').length > 0;
        
        debugLog('Processing answer for QID ' + questionId, {
            isCorrect: isCorrect,
            isIncorrect: isIncorrect
        });
        
        // If answer is incorrect and we're enforcing hints
        if (isIncorrect && state.enforceHint) {
            handleIncorrectAnswer($questionItem, questionId);
        }
    }
    
    /**
     * Handle incorrect answer
     */
    function handleIncorrectAnswer($questionItem, questionId) {
        debugLog('Handling incorrect answer for QID ' + questionId);
        
        // 1. Enable all inputs
        $questionItem.find('.wpProQuiz_questionInput')
            .prop('disabled', false)
            .closest('label')
                .css({
                    'pointer-events': 'auto',
                    'cursor': 'pointer'
                });
        
        // 2. Make sure Check button is visible
        $questionItem.find('input[name="check"]').show();
        
        // 3. Hide Next button
        $questionItem.find('input[name="next"]').hide();
        
        // 4. Highlight hint button
        $questionItem.find('.wpProQuiz_TipButton')
            .addClass('lilac-highlight-hint')
            .attr('title', 'Use this hint to help find the correct answer');
        
        // 5. Attach validator if we know the correct answer
        if (state.correctAnswers[questionId]) {
            attachAnswerValidator($questionItem, questionId);
        }
    }
    
    /**
     * Attach validator to ensure correct answer is selected
     */
    function attachAnswerValidator($questionItem, questionId) {
        var $checkButton = $questionItem.find('input[name="check"]');
        if (!$checkButton.length) {
            return;
        }
        
        // Remove any existing handlers to avoid duplicates
        $checkButton.off('click.answerValidator');
        
        // Add new handler
        $checkButton.on('click.answerValidator', function() {
            setTimeout(function() {
                var selectedValue = $questionItem.find('.wpProQuiz_questionInput:checked').val();
                var isCorrect = selectedValue === state.correctAnswers[questionId];
                
                debugLog('Answer validation', {
                    selectedValue: selectedValue,
                    correctValue: state.correctAnswers[questionId],
                    isCorrect: isCorrect
                });
                
                var $nextButton = $questionItem.find('input[name="next"]');
                if (isCorrect && $nextButton.length) {
                    $nextButton.show();
                    debugLog('Correct answer selected, showing Next button');
                } else if ($nextButton.length) {
                    $nextButton.hide();
                }
            });
        }
    });
}

/**
 * Process any existing questions in the DOM
 */
function processExistingQuestions() {
    $('.wpProQuiz_listItem').each(function() {
        var $question = $(this);
        var questionId = $question.data('question-id') || $question.attr('id') || '';
        
        // Skip if we've already processed this question
        if (state.questions[questionId]) {
            return;
        }
        
        // Check if this question has already been answered
        var hasResponse = $question.find('.wpProQuiz_response:visible').length > 0;
        
        if (hasResponse) {
            processCheckedAnswer($question);
        }
        
        // Mark as processed
        state.questions[questionId] = true;
    });
}

/**
 * Check if enforce hint is enabled for the current quiz via AJAX
 */
function checkEnforceHintStatus(quizId) {
    if (!quizId) {
        debugLog('No quiz ID provided, cannot check enforce hint status');
        return;
    }
    
    if (state.ajaxInProgress) {
        state.pendingAjaxCalls++;
        return;
    }
    
    state.ajaxInProgress = true;
    
    debugLog('Checking enforce hint status for quiz', quizId);
    
    $.ajax({
        url: config.ajaxUrl,
        type: 'POST',
        data: {
            action: 'check_quiz_enforce_hint',
            quiz_id: quizId,
            nonce: config.nonce
        },
        dataType: 'json',
        success: function(response) {
            debugLog('Enforce hint status response:', response);
            
            if (response && response.success) {
                state.enforceHint = response.enforce_hint === true;
                debugLog('Enforce hint status updated:', state.enforceHint);
                
                // Update UI based on enforce hint status
                updateUIForEnforceHint();
            } else {
                debugLog('Failed to get enforce hint status:', response?.message || 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            debugLog('Error checking enforce hint status:', error);
        },
        complete: function() {
            state.ajaxInProgress = false;
            
            // Process any pending AJAX calls
            if (state.pendingAjaxCalls > 0) {
                state.pendingAjaxCalls--;
                checkEnforceHintStatus(quizId);
            }
        }
    });
}

/**
 * Initialize quiz functionality
 */
function initQuizFunctionality() {
    debugLog('Initializing quiz functionality');
    
    // Initialize the quiz
    init();
    
    // Process any existing questions
    setTimeout(function() {
        if ($('.wpProQuiz_listItem').length > 0) {
            processExistingQuestions();
        }
    }, 1000);
}

// Initialize when DOM is ready
$(function() {
    debugLog('DOM ready, checking enforce hint status');
    
    // First check enforce hint status
    checkEnforceHintStatus();
    
    // Fallback in case AJAX request takes too long
    setTimeout(function() {
        if (!state.initialized) {
            debugLog('Enforce hint check timed out, initializing with default settings');
            initQuizFunctionality();
        }
    }, 5000); // 5 second timeout
});

})(jQuery);
