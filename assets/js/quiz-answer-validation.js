/**
 * Quiz Answer Validation
 * 
 * Adds reliable correct answer validation to quizzes.
 * - Uses server-provided correct answers
 * - Enables re-answering incorrect answers
 * - Hides the Next button until correct answer is selected
 * - Enforces hint viewing before proceeding
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initAnswerValidation();
    });
    
    /**
     * Correct answers retrieved from the server
     * This data is injected by WordPress via wp_localize_script
     */
    var correctAnswers = {};
    
    // Log and process correct answers from server
    if (typeof lilacQuizData !== 'undefined') {
        console.log('[QUIZ DEBUG] lilacQuizData loaded:', lilacQuizData);
        
        if (lilacQuizData.correctAnswers) {
            console.log('[QUIZ DEBUG] Server provided correctAnswers:', lilacQuizData.correctAnswers);
            
            // Convert array-like objects to proper arrays if needed
            if (lilacQuizData.correctAnswers && typeof lilacQuizData.correctAnswers === 'object') {
                // Convert to a regular object to handle any array-like objects
                correctAnswers = JSON.parse(JSON.stringify(lilacQuizData.correctAnswers));
                console.log('[QUIZ DEBUG] Processed correctAnswers:', correctAnswers);
            }
        } else {
            console.warn('[QUIZ DEBUG] No correctAnswers found in lilacQuizData');
        }
    } else {
        console.warn('[QUIZ DEBUG] lilacQuizData not defined');
    }
    
    /**
     * Find additional correct answers from LearnDash's data structure 
     * This builds on our hardcoded knowledge and discovers more over time
     */
    function discoverCorrectAnswers() {
        
        // Look for all quiz questions
        $('.wpProQuiz_listItem').each(function() {
            var $question = $(this);
            var questionMeta = $question.attr('data-question-meta');
            
            if (questionMeta) {
                try {
                    var meta = JSON.parse(questionMeta);
                    var questionId = meta.question_post_id || '';
                    var questionType = meta.type || '';
                    
                    if (!questionId) return;
                    
                    // Only proceed if we don't already have this answer from the server
                    if (correctAnswers[questionId]) {
                        console.log('[QUIZ VALIDATION] Already have answer for question ' + questionId);
                        return;
                    }
                    
                    // Different retrieval methods based on question type
                    if (questionType === 'single') {
                        // For single choice questions
                        extractSingleChoiceCorrectAnswer($question, questionId);
                    } else if (questionType === 'multiple') {
                        // For multiple choice questions
                        extractMultipleChoiceCorrectAnswer($question, questionId);
                    }
                    // Add more question types as needed
                    
                } catch (e) {
                    console.error('[QUIZ VALIDATION] Error parsing question meta:', e);
                }
            }
        });
        
        // Debug logging
        console.log('[QUIZ VALIDATION] Combined correct answers:', correctAnswers);
    }
    
    // Extract correct answer for single choice questions
    function extractSingleChoiceCorrectAnswer($question, questionId) {
        // Method 1: Check for data attributes or classes that indicate correct answer
        var correctIndex = -1;
        
        // Look for hidden correct answer indicators in the markup
        $question.find('.wpProQuiz_questionListItem').each(function(index) {
            var $item = $(this);
            // LearnDash often has data about the correct answer in attributes
            if ($item.attr('data-correct') === '1' || $item.hasClass('wpProQuiz_answerCorrect')) {
                correctIndex = index;
                return false; // break loop
            }
        });
        
        // If we found a correct index, save it
        if (correctIndex >= 0) {
            var correctValue = correctIndex + 1; // Convert to 1-based for value attribute
            correctAnswers[questionId] = correctValue.toString();
            return;
        }
        
        // Method 2: Check the question HTML for answer data
        var questionHTML = $question.html();
        if (questionHTML.indexOf('data-correct="1"') !== -1) {
            // Try to extract from HTML pattern
            var match = questionHTML.match(/data-pos="(\d+)"[^>]*data-correct="1"/i);
            if (match && match[1]) {
                var position = parseInt(match[1]);
                correctAnswers[questionId] = (position + 1).toString();
                return;
            }
        }
        
        // Method 3: Try to get from Learndash quiz_list global variable if available
        if (typeof window.wpProQuizInitList !== 'undefined' && window.wpProQuizInitList) {
            for (var quizId in window.wpProQuizInitList) {
                var quizConfig = window.wpProQuizInitList[quizId].json;
                if (quizConfig && quizConfig.questions) {
                    for (var i = 0; i < quizConfig.questions.length; i++) {
                        var q = quizConfig.questions[i];
                        if (q.question_post_id == questionId && q.correct !== undefined) {
                            correctAnswers[questionId] = q.correct.toString();
                            return;
                        }
                    }
                }
            }
        }
        
        // Fallback: If we can't find correct answers, we'll need to check after user submits
        // and discover which answers are marked correct
        console.log('[QUIZ VALIDATION] Will determine correct answer for question ' + questionId + ' after submission');
    }
    
    // Extract correct answers for multiple choice questions
    function extractMultipleChoiceCorrectAnswer($question, questionId) {
        // Similar to single choice, but returns array of correct indices
        var correctIndices = [];
        
        $question.find('.wpProQuiz_questionListItem').each(function(index) {
            var $item = $(this);
            if ($item.attr('data-correct') === '1' || $item.hasClass('wpProQuiz_answerCorrect')) {
                correctIndices.push(index + 1);
            }
        });
        
        if (correctIndices.length > 0) {
            correctAnswers[questionId] = correctIndices.map(String); // Convert to string array
        }
    }
    
    // Learn from correct/incorrect feedback
    function learnFromFeedback($question) {
        if (!$question) return;
        
        var questionMeta = $question.attr('data-question-meta');
        if (!questionMeta) return;
        
        try {
            var meta = JSON.parse(questionMeta);
            var questionId = meta.question_post_id || '';
            
            if (!questionId) return;
            
            // If the question shows the correct answer, we can learn it
            if ($question.find('.wpProQuiz_correct').is(':visible')) {
                // Find which answer is selected
                var $selected = $question.find('.wpProQuiz_questionInput:checked');
                if ($selected.length) {
                    var answer = $selected.val();
                    console.log('[QUIZ VALIDATION] Learning correct answer for question ' + questionId + ':', answer);
                    correctAnswers[questionId] = answer;
                }
            }
        } catch (e) {
            console.error('[QUIZ VALIDATION] Error learning from feedback:', e);
        }
    }
    
    // Try to discover correct answers immediately
    discoverCorrectAnswers();
    
    /**
     * Initialize answer validation
     */
    function initAnswerValidation() {
        // Attach to check button clicks
        $(document).on('click', '.wpProQuiz_button[name="check"]', function() {
            const $questionItem = $(this).closest('.wpProQuiz_listItem');
            
            // Process after LearnDash handles the answer
            setTimeout(function() {
                validateAnswer($questionItem);
            }, 300);
        });
        
        // Process any existing questions
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            validateAnswer($question);
        });
    }
    
    /**
     * Validate answer against our answer map
     */
    function validateAnswer($questionItem) {
        // Get question data
        var questionMeta = $questionItem.attr('data-question-meta');
        if (!questionMeta) return;
        
        try {
            var meta = JSON.parse(questionMeta);
            var questionId = meta.question_post_id || '';
            var questionType = meta.type || '';
            
            // Find selected answer
            var $selectedInput = $questionItem.find('.wpProQuiz_questionInput:checked');
            var userAnswer = $selectedInput.length ? $selectedInput.val() : null;
            
            // Find if the answer was incorrect
            var $incorrectMsg = $questionItem.find('.wpProQuiz_incorrect');
            var isIncorrect = $incorrectMsg.is(':visible');
            
            // Find if the answer was correct
            var $correctMsg = $questionItem.find('.wpProQuiz_correct');
            var isCorrect = $correctMsg.is(':visible');
            
            // If we see a correct result and don't have this answer yet, learn it
            if (isCorrect && userAnswer) {
                console.log('[QUIZ VALIDATION] Learning correct answer for ' + questionId + ':', userAnswer);
                correctAnswers[questionId] = userAnswer;
            }
            
            // Get our known correct answer
            var correctAnswer = correctAnswers[questionId];
            
            // Log for debugging
            console.log('[QUIZ VALIDATION] Question ID:', questionId);
            console.log('[QUIZ VALIDATION] User answer:', userAnswer);
            console.log('[QUIZ VALIDATION] Correct answer:', correctAnswer);
            console.log('[QUIZ VALIDATION] Is incorrect:', isIncorrect);
            
            // If we don't have the correct answer yet, try to learn from the DOM
            if (!correctAnswer && $questionItem.find('.wpProQuiz_response').is(':visible')) {
                // Look for correct answers in the DOM after answer submission
                extractCorrectAnswerFromResult($questionItem, questionId, questionType);
                correctAnswer = correctAnswers[questionId];
            }
            
            // If answer is incorrect, enforce correct answer validation
            if (isIncorrect) {
                // Hide the Next button
                var $nextButton = $questionItem.find('.wpProQuiz_button[name="next"]');
                if ($nextButton.length) {
                    $nextButton.hide();
                }
                
                // Make sure the Check button is visible
                var $checkButton = $questionItem.find('.wpProQuiz_button[name="check"]');
                if ($checkButton.length) {
                    $checkButton.show().css('display', 'inline-block');
                    
                    // Remove existing validation handler if any
                    var newCheckBtn = $checkButton[0].cloneNode(true);
                    $checkButton.replaceWith(newCheckBtn);
                    
                    // Add validation handler
                    $(newCheckBtn).on('click', function(e) {
                        setTimeout(function() {
                            // Get current selection
                            var $selected = $questionItem.find('.wpProQuiz_questionInput:checked');
                            var currentAnswer = $selected.length ? $selected.val() : null;
                            
                            console.log('[QUIZ VALIDATION] Re-check, user selected:', currentAnswer);
                            console.log('[QUIZ VALIDATION] Re-check, correct answer:', correctAnswer);
                            
                            // Check if answer is now correct after resubmission
                            if ($questionItem.find('.wpProQuiz_correct').is(':visible')) {
                                // Check if hint was viewed (required for our flow)
                                var hintViewed = $questionItem.attr('data-hint-viewed') === 'true';
                                var $hintBtn = $questionItem.find('.wpProQuiz_TipButton, .wpProQuiz_hint').first();
                                
                                // If hint wasn't viewed but is required
                                if (!hintViewed && $hintBtn.length && $questionItem.attr('data-has-hint') === 'true') {
                                    // Keep the Next button hidden until hint is viewed
                                    $questionItem.find('.wpProQuiz_button[name="next"]').hide();
                                    
                                    // Highlight hint button strongly
                                    highlightHintButton($questionItem);
                                    
                                    console.log('[QUIZ VALIDATION] Correct answer, but hint not viewed yet');
                                    return;
                                }
                                
                                // This is the correct answer and hint requirements met, so show the Next button
                                var $next = $questionItem.find('.wpProQuiz_button[name="next"]');
                                if ($next.length) {
                                    $next.show().css('display', 'inline-block');
                                    console.log('[QUIZ VALIDATION] Showing Next button - correct answer');
                                }
                                
                                // Learn this correct answer for future reference
                                if (currentAnswer) {
                                    correctAnswers[questionId] = currentAnswer;
                                    console.log('[QUIZ VALIDATION] Updated correct answer for ' + questionId + ':', currentAnswer);
                                }
                                
                                return;
                            }
                            
                            // If we have a known correct answer, compare
                            if (correctAnswer !== undefined && currentAnswer === correctAnswer) {
                                var $next = $questionItem.find('.wpProQuiz_button[name="next"]');
                                if ($next.length) {
                                    $next.show().css('display', 'inline-block');
                                    console.log('[QUIZ VALIDATION] Showing Next button - matches known correct answer');
                                }
                            }
                        }, 300);
                    });
                }
            }
        } catch(e) {
            console.error('[QUIZ VALIDATION] Error processing question:', e);
        }
    }
    
    /**
     * Highlight the hint button to make it more visible
     */
    function highlightHintButton($question) {
        const $hintBtn = $question.find('.wpProQuiz_TipButton, .wpProQuiz_hint').first();
        
        if (!$hintBtn.length) return;
        
        // Add highlighting
        $hintBtn.addClass('highlight')
            .css({
                'animation': 'pulse-button 1.5s infinite',
                'background-color': '#ffc107',
                'color': '#333',
                'font-weight': 'bold',
                'box-shadow': '0 0 10px rgba(255, 193, 7, 0.7)',
                'visibility': 'visible',
                'display': 'inline-block',
                'opacity': '1'
            });
        
        // Remove any existing tooltips
        $('.hint-tooltip').remove();
        
        // Add tooltip
        const $tooltip = $('<div class="hint-tooltip">טעית! להמשך חובה לקחת רמז!</div>');
        $hintBtn.after($tooltip);
        
        // Style the tooltip
        $tooltip.css({
            'position': 'absolute',
            'background-color': '#ffc107',
            'color': '#333',
            'padding': '5px 10px',
            'border-radius': '4px',
            'font-size': '14px',
            'font-weight': 'bold',
            'z-index': '999',
            'margin-top': '5px',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
            'max-width': '200px',
            'text-align': 'center'
        });
    }
    
    /**
     * Extract correct answer from question results
     */
    function extractCorrectAnswerFromResult($question, questionId, questionType) {
        if (!questionId) return;
        
        // If the correct answer is displayed, check which option matches it
        var correctAnswerText = '';
        $question.find('.wpProQuiz_correct .wpProQuiz_AnswerMessage').each(function() {
            correctAnswerText += $(this).text() + ' ';
        });
        
        if (correctAnswerText) {
            // Try to match the correct answer text to question options
            $question.find('.wpProQuiz_questionListItem').each(function(index) {
                var optionText = $(this).text().trim();
                if (correctAnswerText.indexOf(optionText) !== -1) {
                    // Found matching text, this is likely the correct answer
                    correctAnswers[questionId] = (index + 1).toString();
                    console.log('[QUIZ VALIDATION] Found correct answer from result: ' + (index + 1));
                    return false; // break loop
                }
            });
        }
        
        // For single answer questions, if user got it right, use their answer
        if (questionType === 'single' && $question.find('.wpProQuiz_correct').is(':visible')) {
            var $selected = $question.find('.wpProQuiz_questionInput:checked');
            if ($selected.length) {
                correctAnswers[questionId] = $selected.val();
                console.log('[QUIZ VALIDATION] User answer was correct: ' + $selected.val());
            }
        }
    }
})(jQuery);
