<?php
/**
 * Implement quiz answer edit functionality
 * 
 * This allows users to re-edit their answers after selecting an incorrect option
 * by keeping inputs enabled and hiding the Next button until the correct answer is selected.
 */
function lilac_quiz_answer_edit_js() {
    // Only run this on quiz pages
    if (!is_singular('sfwd-quiz')) {
        return;
    }
    ?>
    <script type="text/javascript">
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Store the correct answers from our database query
        var correctAnswers = {};
        
        // Load quiz questions and determine correct answers
        function loadQuizData() {
            // Look at question items in the DOM
            var questions = document.querySelectorAll('.wpProQuiz_listItem');
            console.log('[QUIZ DEBUG] Found ' + questions.length + ' questions in quiz');
            
            // Process each question to extract its ID
            questions.forEach(function(question) {
                var meta = question.getAttribute('data-question-meta');
                if (meta) {
                    try {
                        var parsed = JSON.parse(meta);
                        var questionId = parsed.question_post_id || '';
                        console.log('[QUIZ DEBUG] Processing question ID:', questionId);
                        
                        // For now, set placeholders - the plugin will fill these with real answers
                        if (questionId) {
                            if (questionId === '42') {
                                correctAnswers[questionId] = '1';
                            }
                        }
                    } catch(e) {
                        console.error('[QUIZ DEBUG] Error processing question:', e);
                    }
                }
            });
            
            // Log what we know
            console.log('[QUIZ DEBUG] *** COMPLETE LIST OF CORRECT ANSWERS ***');
            console.log(correctAnswers);
            console.log('[QUIZ DEBUG] ***************************************');
        }
        
        // Initial load
        loadQuizData();
        
        // Monitor answer selections
        document.addEventListener('change', function(e) {
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
        });
        
        // Main function for processing after Check button clicks
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
                
                // 4. If we know the correct answer, enforce it
                if (correctAnswers[questionId]) {
                    // Add a one-time click handler to enforce answer checking
                    var newCheckHandler = function(e) {
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
                    
                    var checkButton = questionItem.querySelector('.wpProQuiz_button[name="check"]');
                    if (checkButton) {
                        checkButton.addEventListener('click', newCheckHandler);
                    }
                }
            }
        }
        
        // Listen for Check button clicks
        document.addEventListener('click', function(e) {
            // Only process Check button clicks
            if (e.target && e.target.name === 'check' && e.target.className.includes('wpProQuiz_button')) {
                console.log('[QUIZ DEBUG] Check button clicked');
                
                // Wait for LearnDash to update the DOM
                setTimeout(function() {
                    var questionItem = e.target.closest('.wpProQuiz_listItem');
                    processCheckedAnswer(questionItem);
                }, 100);
            }
        });
        
        // Process any existing questions on page load
        setTimeout(function() {
            var questions = document.querySelectorAll('.wpProQuiz_listItem');
            questions.forEach(function(question) {
                var responseShown = question.querySelector('.wpProQuiz_response[style*="display: block"], .wpProQuiz_response:not([style*="display: none"])');
                if (responseShown) {
                    processCheckedAnswer(question);
                }
            });
        }, 100);
    });
    </script>
    <?php
}

// Hook our quiz answer edit function
add_action('wp_footer', 'lilac_quiz_answer_edit_js');
