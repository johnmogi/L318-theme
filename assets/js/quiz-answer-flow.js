/**
 * Quiz answer flow script
 * 
 * Controls the quiz behavior:
 * - Disables Next button until correct answer is selected
 * - Shows custom hint message with רמז button when answers are incorrect
 * - Keeps inputs enabled after wrong answers to allow changing
 */
(function($) {
    'use strict';
    
    // Debug logging function
    function log(message, ...args) {
        if (window.console && console.log) {
            if (args.length > 0) {
                console.log('[Quiz Flow]:', message, ...args);
            } else {
                console.log('[Quiz Flow]:', message);
            }
        }
    }

    // Quiz initialization
    function initQuiz() {
        log('Initializing quiz flow');
        
        // Process any questions that are already in the DOM
        processExistingQuestions();
        
        // Add observer to detect when new questions are added
        observeQuizChanges();
        
        // Fix any already answered questions
        fixExistingAnsweredQuestions();
        
        // Add extra override for Next buttons
        overrideNextButtons();
    }
    
    // Fix any questions that were already answered before our script loaded
    function fixExistingAnsweredQuestions() {
        $('.wpProQuiz_questionListItem.wpProQuiz_answerIncorrect.is-selected').each(function() {
            const $answerItem = $(this);
            const $question = $answerItem.closest('.wpProQuiz_listItem');
            const $inputs = $question.find('input.wpProQuiz_questionInput');
            
            log('Found existing incorrect answer, fixing...');
            
            // Re-enable inputs
            $inputs.removeAttr('disabled');
            
            // Hide next button
            hideNextButton($question);
            
            // Show hint button if available
            showHintButton($question);
        });
    }
    
    // Override all Next buttons to ensure they follow our rules
    function overrideNextButtons() {
        $('.wpProQuiz_button.wpProQuiz_QuestionButton[name="next"]').each(function() {
            const $nextBtn = $(this);
            const $question = $nextBtn.closest('.wpProQuiz_listItem');
            
            // Check if this question has an incorrect answer selected
            if ($question.find('.wpProQuiz_questionListItem.wpProQuiz_answerIncorrect.is-selected').length) {
                hideNextButton($question);
            }
            // Alternatively, check if no answer is selected
            else if (!$question.find('.wpProQuiz_questionListItem.is-selected').length) {
                hideNextButton($question);
            }
        });
    }
    
    // Process questions that are already in the DOM
    function processExistingQuestions() {
        $('.wpProQuiz_listItem').each(function() {
            setupQuestionBehavior($(this));
        });
    }
    
    // Setup the behavior for a specific question
    function setupQuestionBehavior($question) {
        // Hide the Next button initially (only if the question is not already answered correctly)
        if (!$question.find('.wpProQuiz_questionListItem.wpProQuiz_answerCorrect').length) {
            hideNextButton($question);
        }
        
        // Attach click handlers to all answer options
        attachAnswerValidators($question);
        
        // Make sure hint button is visible
        showHintButton($question);
    }
    
    // Make hint button visible
    function showHintButton($question) {
        const $tipBtn = $question.find('input.wpProQuiz_TipButton');
        if ($tipBtn.length) {
            log('Making hint button visible');
            $tipBtn.show().css({
                'display': 'inline-block',
                'visibility': 'visible',
                'opacity': '1',
                'pointer-events': 'auto',
                'float': 'left',
                'margin': '5px',
                'background-color': 'rgb(255, 152, 0)',
                'color': 'white',
                'border': '2px solid rgb(230, 126, 34)',
                'font-weight': 'bold',
                'cursor': 'pointer'
            });
            $tipBtn.prop('disabled', false);
        }
    }
    
    // Hide the Next button
    function hideNextButton($question) {
        const $nextButton = $question.find('input[name="next"]');
        if ($nextButton.length) {
            log('Hiding Next button');
            $nextButton.css({
                'visibility': 'hidden !important',
                'opacity': '0 !important',
                'position': 'absolute !important',
                'pointer-events': 'none !important',
                'display': 'none !important',
                'z-index': '-1 !important',
                'width': '0px !important',
                'height': '0px !important',
                'overflow': 'hidden !important',
                'margin': '0px !important',
                'padding': '0px !important'
            });
            $nextButton.prop('disabled', true);
            
            // Log that we've hidden the button for debugging
            console.log('Next button CSS:', $nextButton.attr('style'));
            
            // Add extra attribute to ensure it stays hidden
            $nextButton.attr('data-hidden', 'true');
            
            // Direct attribute setting as a fallback approach
            $nextButton[0].style.setProperty('display', 'none', 'important');
            $nextButton[0].style.setProperty('visibility', 'hidden', 'important');
        }
    }
    
    // Show the Next button
    function showNextButton($question) {
        const $nextButton = $question.find('input[name="next"]');
        if ($nextButton.length) {
            log('Showing Next button');
            $nextButton.css({
                'visibility': 'visible',
                'opacity': '1',
                'position': 'static',
                'pointer-events': 'auto',
                'display': 'inline-block',
                'float': 'left',
                'margin': '0px 10px'
            });
            $nextButton.prop('disabled', false);
            
            // Log that we've shown the button for debugging
            console.log('Next button CSS:', $nextButton.attr('style'));
        }
    }
    
    // Attach handlers for answer validation
    function attachAnswerValidators($question) {
        const $answers = $question.find('.wpProQuiz_questionListItem input.wpProQuiz_questionInput');
        
        // First, remove any disabled attributes to allow changing
        $answers.removeAttr('disabled');
        
        $answers.on('click', function() {
            const $selectedAnswer = $(this);
            const $answerItem = $selectedAnswer.closest('.wpProQuiz_questionListItem');
            
            // Enable the check button to validate the answer
            const $checkBtn = $question.find('input[name="check"]');
            if ($checkBtn.length) {
                $checkBtn.click();
            }
            
            // After a short delay to allow the quiz to process the answer
            setTimeout(function() {
                // Check if the answer was correct
                if ($answerItem.hasClass('wpProQuiz_answerCorrect')) {
                    log('Correct answer selected');
                    
                    // Handle correct answer
                    handleCorrectAnswer($question);
                } 
                else if ($answerItem.hasClass('wpProQuiz_answerIncorrect')) {
                    log('Incorrect answer selected');
                    
                    // Handle incorrect answer
                    handleWrongAnswer($question);
                }
            }, 300);
        });
    }
    
    /**
     * Handle correct answer selection
     */
    function handleCorrectAnswer($question) {
        // Remove any previous messages
        $question.find('.lilac-hint-message, .lilac-correct-answer-message').remove();
        
        // Show the next button
        showNextButton($question);
        
        // Disable further answer changes
        $question.find('.wpProQuiz_questionInput').attr('disabled', 'disabled');
        
        // Add success message with Next button
        const $successMessage = $('<div class="lilac-correct-answer-message" style="background-color: rgb(232, 245, 233); border: 1px solid rgb(76, 175, 80); border-radius: 4px; padding: 10px 15px; margin: 15px 0px; text-align: right; font-size: 16px; display: flex; align-items: center; justify-content: space-between; direction: rtl;">' +
            '<span style="font-weight:bold;color:#4CAF50;">✓ תשובה נכונה!</span>' +
            '<span>לחץ על הבא להמשיך</span>' +
            '<button type="button" class="lilac-force-next" style="display: inline-block; visibility: visible; background-color: rgb(46, 89, 217); color: white; font-weight: bold; border: 2px solid rgb(24, 53, 155); border-radius: 4px; padding: 8px 24px; cursor: pointer; font-size: 16px; margin-right: 10px; box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 5px;">הבא</button>' +
            '</div>');
        
        // Insert message in the response area
        let $responseArea = $question.find('.wpProQuiz_response');
        if (!$responseArea.length) {
            $responseArea = $('<div class="wpProQuiz_response"></div>');
            $question.find('.wpProQuiz_questionList').after($responseArea);
        }
        
        $responseArea.prepend($successMessage);
        
        // Make the Next button work
        $question.find('.lilac-force-next').on('click', function() {
            const $nextButton = $question.find('.wpProQuiz_button[name="next"]');
            if ($nextButton.length) {
                $nextButton.click();
            }
        });
    }
    
    /**
     * Handle incorrect answer selection
     */
    function handleWrongAnswer($question) {
        // Remove any previous messages
        $question.find('.lilac-correct-answer-message').remove();
        
        // Keep next button hidden
        hideNextButton($question);
        
        // Keep inputs enabled for reselection
        $question.find('.wpProQuiz_questionInput').removeAttr('disabled');
        
        // Check if hint message already exists
        if ($question.find('.lilac-hint-message').length) {
            $question.find('.lilac-hint-message').show();
        } else {
            // Add our custom hint message
            const $hintMessage = $('<div class="lilac-hint-message" style="background-color: rgb(255, 243, 224); border: 1px solid rgb(255, 152, 0); border-radius: 4px; padding: 10px 15px; margin: 15px 0px; text-align: right; font-size: 16px; display: flex; align-items: center; justify-content: space-between; direction: rtl;">' +
                '<span style="font-weight:bold;color:#e74c3c;">❌ תשובה שגויה!</span>' +
                '<span>לחץ על רמז לקבלת עזרה</span>' +
                '<button type="button" class="lilac-force-hint" style="display: inline-block; visibility: visible; background-color: rgb(255, 152, 0); color: white; font-weight: bold; border: 2px solid rgb(230, 126, 34); border-radius: 4px; padding: 8px 24px; cursor: pointer; font-size: 16px; margin-right: 10px; box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 5px;">רמז</button>' +
                '</div>');
            
            // Insert message in the response area
            let $responseArea = $question.find('.wpProQuiz_response');
            if (!$responseArea.length) {
                $responseArea = $('<div class="wpProQuiz_response"></div>');
                $question.find('.wpProQuiz_questionList').after($responseArea);
            }
            
            $responseArea.prepend($hintMessage);
            
            // Make hint button work
            $question.find('.lilac-force-hint').on('click', function() {
                const $tipBtn = $question.find('.wpProQuiz_TipButton');
                if ($tipBtn.length) {
                    $tipBtn.click();
                }
            });
        }
        
        // Make sure the actual hint button is visible
        showHintButton($question);
        
        // Log Next button status for debugging
        const $nextButton = $question.find('input[name="next"]');
        log('Next button after wrong answer:', $nextButton.css('visibility'));
    }
    
    // Watch for changes in the quiz content to initialize new questions
    function observeQuizChanges() {
        // Check if MutationObserver is supported
        if (typeof MutationObserver !== 'undefined') {
            const quizContainer = document.querySelector('.wpProQuiz_content');
            if (quizContainer) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        // If we have new nodes added
                        if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                            // Check if any of the added nodes are question items
                            for (let i = 0; i < mutation.addedNodes.length; i++) {
                                const node = mutation.addedNodes[i];
                                if (node.nodeType === 1 && $(node).hasClass('wpProQuiz_listItem')) {
                                    log('New question detected');
                                    setupQuestionBehavior($(node));
                                }
                            }
                        }
                    });
                });
                
                // Start observing
                observer.observe(quizContainer, { 
                    childList: true, 
                    subtree: true 
                });
            }
        }
    }
    
    // Initialize on document ready
    $(document).ready(initQuiz);
    
    // Also initialize on window load to catch any late-loading elements
    $(window).on('load', function() {
        setTimeout(initQuiz, 500); // Extra delay to ensure everything is loaded
    });
    
})(jQuery);
