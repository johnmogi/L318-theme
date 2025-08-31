/**
 * Lilac Quiz Answer Flow
 * 
 * Handles quiz answer validation and UI behavior:
 * - Keeps inputs clickable after incorrect selections
 * - Hides Next button until correct answer is given
 * - Shows hint button for incorrect answers
 */

(function() {
    'use strict';
    
    // Store the correct answers provided by the server
    var correctAnswers = (typeof lilacQuizData !== 'undefined' && lilacQuizData.correctAnswers) ? 
                        lilacQuizData.correctAnswers : {};
    
    // Log all correct answers we received immediately on page load for verification
    console.log('[QUIZ DEBUG] *** LILAC QUIZ DEBUG INFO ***');
    console.log('lilacQuizData:', lilacQuizData);
    console.log('correctAnswers:', correctAnswers);
    console.log('enforceHint:', typeof lilacQuizData !== 'undefined' ? lilacQuizData.enforceHint : 'undefined');
    console.log('quizId:', typeof lilacQuizData !== 'undefined' ? lilacQuizData.quizId : 'undefined');
    console.log('nonce:', typeof lilacQuizData !== 'undefined' ? lilacQuizData.nonce : 'undefined');
    console.log('ajaxurl:', typeof lilacQuizData !== 'undefined' ? lilacQuizData.ajaxurl : 'undefined');
    console.log('***************************************');
    
    /**
     * Initialize the quiz functionality
     */
    function init() {
        // Listen for answer selections
        document.addEventListener('change', handleAnswerSelection);
        
        // Listen for check button clicks
        document.addEventListener('click', handleCheckButton);
        
        // Process any existing questions on page load
        processExistingQuestions();
    }
    
    /**
     * Handle answer selection events
     */
    function handleAnswerSelection(e) {
        if (e.target && e.target.classList.contains('wpProQuiz_questionInput')) {
            var questionItem = e.target.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            var questionMeta = questionItem.getAttribute('data-question-meta');
            var questionId = '';
            if (questionMeta) {
                try {
                    var meta = JSON.parse(questionMeta);
                    questionId = meta.question_post_id || '';
                } catch(e) { }
            }
            
            console.log('[QUIZ DEBUG] Selected answer for QID ' + questionId + ':', e.target.value);
        }
    }
    
    /**
     * Handle check button clicks
     */
    function handleCheckButton(e) {
        // Only process Check button clicks
        if (e.target && e.target.name === 'check' && e.target.className.includes('wpProQuiz_button')) {
            console.log('[QUIZ DEBUG] Check button clicked');
            
            // Wait for LearnDash to update the DOM
            setTimeout(function() {
                var questionItem = e.target.closest('.wpProQuiz_listItem');
                processCheckedAnswer(questionItem);
            }, 100);
        }
    }
    
    /**
     * Process the result of a checked answer
     */
    function processCheckedAnswer(questionItem) {
        if (!questionItem) return;
        
        // Get question ID
        var questionMeta = questionItem.getAttribute('data-question-meta');
        var questionId = '';
        if (questionMeta) {
            try {
                var meta = JSON.parse(questionMeta);
                questionId = meta.question_post_id || '';
            } catch(e) { }
        }
        
        // Find out if answer was correct
        var correctMsg = questionItem.querySelector('.wpProQuiz_correct');
        var incorrectMsg = questionItem.querySelector('.wpProQuiz_incorrect');
        var isCorrect = correctMsg && (window.getComputedStyle(correctMsg).display !== 'none');
        var isIncorrect = incorrectMsg && (window.getComputedStyle(incorrectMsg).display !== 'none');
        
        // Find the selected answer
        var selectedInput = questionItem.querySelector('.wpProQuiz_questionInput:checked');
        var userAnswer = selectedInput ? selectedInput.value : null;
        
        // Get the Next button status
        var nextButton = questionItem.querySelector('.wpProQuiz_button[name="next"]');
        var isNextButtonVisible = nextButton && (window.getComputedStyle(nextButton).display !== 'none');
        
        // Log the current state
        console.log('[QUIZ DEBUG] Answer is correct?', isCorrect);
        console.log('[QUIZ DEBUG] Next button visible?', isNextButtonVisible);
        
        // The core logic - if incorrect, enable re-answering and hide Next button
        if (isIncorrect) {
            // 1. Enable all inputs
            var inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].disabled = false;
                inputs[i].removeAttribute('disabled');
                
                // Make label clickable
                var label = inputs[i].closest('label');
                if (label) {
                    label.style.pointerEvents = 'auto';
                    label.style.cursor = 'pointer';
                }
            }
            
            // 2. Make sure Check button is visible
            var checkButton = questionItem.querySelector('.wpProQuiz_button[name="check"]');
            if (checkButton) {
                checkButton.style.display = 'inline-block';
            }
            
            // 3. Hide Next button until correct answer is selected
            if (nextButton && window.getComputedStyle(nextButton).display !== 'none') {
                nextButton.style.display = 'none';
                console.log('[QUIZ DEBUG] Hiding Next button for incorrect answer');
            }
            
            // 4. Highlight the hint button with animation if available
            var tipButton = questionItem.querySelector('.wpProQuiz_TipButton');
            if (tipButton) {
                tipButton.classList.add('lilac-highlight-hint');
                // Add tooltip to encourage hint usage
                tipButton.setAttribute('title', 'Use this hint to help find the correct answer');
            }
            
            // 5. Enforce correct answer validation on next check
            if (correctAnswers[questionId]) {
                attachCorrectAnswerValidator(questionItem, questionId);
            }
        }
    }
    
    /**
     * Attach a validator to the Check button to ensure correct answer is selected
     */
    function attachCorrectAnswerValidator(questionItem, questionId) {
        var checkButton = questionItem.querySelector('.wpProQuiz_button[name="check"]');
        if (!checkButton) return;
        
        var validatorHandler = function(e) {
            setTimeout(function() {
                var selectedInput = questionItem.querySelector('.wpProQuiz_questionInput:checked');
                var userAnswer = selectedInput ? selectedInput.value : null;
                
                console.log('[QUIZ DEBUG] Re-check: User selected', userAnswer);
                console.log('[QUIZ DEBUG] Re-check: Correct answer is', correctAnswers[questionId]);
                
                // Only show Next button if the answer is correct
                var nextBtn = questionItem.querySelector('.wpProQuiz_button[name="next"]');
                if (userAnswer === correctAnswers[questionId]) {
                    if (nextBtn) {
                        nextBtn.style.display = 'inline-block';
                        console.log('[QUIZ DEBUG] Answer is correct, showing Next button');
                    }
                } else {
                    if (nextBtn) {
                        nextBtn.style.display = 'none';
                        console.log('[QUIZ DEBUG] Answer is still wrong, keeping Next button hidden');
                    }
                }
            }, 100);
        };
        
        // Remove any existing handlers to avoid duplicates
        checkButton.removeEventListener('click', validatorHandler);
        
        // Add the new handler
        checkButton.addEventListener('click', validatorHandler);
    }
    
    /**
     * Process any existing questions that might already be in the DOM
     */
    function processExistingQuestions() {
        setTimeout(function() {
            var questions = document.querySelectorAll('.wpProQuiz_listItem');
            questions.forEach(function(question) {
                var responseShown = question.querySelector(
                    '.wpProQuiz_response[style*="display: block"], ' + 
                    '.wpProQuiz_response:not([style*="display: none"])'
                );
                if (responseShown) {
                    processCheckedAnswer(question);
                }
            });
        }, 100);
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
})();
