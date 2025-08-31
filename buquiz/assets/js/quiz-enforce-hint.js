/**
 * Quiz Enforce Hint Feature
 * 
 * Forces users to view hints and select the correct answer before proceeding.
 * Only activates when 'Enforce Hint' setting is enabled for the quiz.
 */
(function($) {
    'use strict';

    // Track question state
    let questionStates = {};
    let enforcingHint = false;
    
    /**
     * Initialize the quiz enforce hint functionality
     */
    function initQuizEnforceHint() {
        // Only initialize if enforce hint is enabled
        if (!ldQuizSettings.enforceHint) {
            return;
        }
        
        console.log('Enforce Hint feature activated');
        setupEventListeners();
        
        // Immediately run these operations to fix things now
        setTimeout(function() {
            forceHideNextButtons();
            showAllHintButtons();
            enableAllInputs();
            setupReselectionHandlers();
        }, 500);
    }
    
    /**
     * Setup event listeners for quiz interaction
     */
    function setupEventListeners() {
        // Listen for answer submissions - important for re-selections
        $(document).on('click', '.wpProQuiz_questionInput', handleAnswerSelection);
        $(document).on('change', '.wpProQuiz_questionInput', handleAnswerSelection);
        
        // Listen for question check events
        $(document).on('learndash-quiz-answered', handleQuestionAnswered);
        
        // Watch for question navigation
        $(document).on('click', '.wpProQuiz_button[name="next"], .wpProQuiz_button[name="back"]', handleNavigation);
        
        // Watch for hint button clicks to track when hints are viewed
        $(document).on('click', '.wpProQuiz_hint, .wpProQuiz_TipButton', trackHintViewed);
        
        // Watch for check button clicks
        $(document).on('click', '.wpProQuiz_button[name="check"]', handleCheckButton);
        
        // Custom event for when LearnDash reports question correctness
        $(document).on('DOMNodeInserted', '.wpProQuiz_incorrect, .wpProQuiz_correct', handleFeedbackInserted);
        
        // Force check button to always be visible
        $(document).on('DOMSubtreeModified', '.wpProQuiz_question', function() {
            $('.wpProQuiz_button[name="check"]').show().css('display', 'inline-block');
            forceHideNextButtons();
            showAllHintButtons();
        });
        
        // Make the tip (hint) button always visible and remove input disabled states
        setInterval(function() {
            ensureHintButtonVisible();
            removeInputDisabledState();
            forceHideNextButtons();
            restoreCheckButton();
        }, 500);
        
        // Add a MutationObserver to watch for any new questions that appear
        setupMutationObserver();
        
        // Initial setup for current question
        setTimeout(setupCurrentQuestion, 1000);
    }
    
    /**
     * Set up the current visible question
     */
    function setupCurrentQuestion() {
        const $currentQuestion = $('.wpProQuiz_listItem:visible');
        
        if ($currentQuestion.length) {
            const questionId = getQuestionIdFromElement($currentQuestion);
            
            if (questionId && !questionStates[questionId]) {
                questionStates[questionId] = {
                    answered: false,
                    correct: false,
                    hintViewed: false,
                    hasHint: $currentQuestion.find('.wpProQuiz_hint').length > 0
                };
                
                // Initially hide "next" button if this is a fresh question
                if (!questionStates[questionId].correct) {
                    toggleNextButton(false);
                }
            }
        }
    }
    
    /**
     * Handle when a user selects an answer
     * @param {Event} e - Click event
     */
    function handleAnswerSelection(e) {
        const $question = $(this).closest('.wpProQuiz_listItem');
        const questionId = getQuestionIdFromElement($question);
        
        if (questionId) {
            // Mark that the user has attempted to answer
            if (!questionStates[questionId]) {
                questionStates[questionId] = {
                    answered: true,
                    correct: false,
                    hintViewed: false,
                    hasHint: $question.find('.wpProQuiz_hint').length > 0
                };
            } else {
                questionStates[questionId].answered = true;
            }
        }
    }
    
    /**
     * Handle question answered event (from LearnDash)
     * @param {Event} e - Event object
     * @param {Object} data - Question data
     */
    function handleQuestionAnswered(e, data) {
        if (data && data.correct !== undefined) {
            const $question = $('.wpProQuiz_listItem:visible');
            const questionId = getQuestionIdFromElement($question);
            
            if (questionId) {
                if (!questionStates[questionId]) {
                    questionStates[questionId] = {
                        answered: true,
                        correct: data.correct,
                        hintViewed: false,
                        hasHint: $question.find('.wpProQuiz_hint').length > 0
                    };
                } else {
                    questionStates[questionId].answered = true;
                    questionStates[questionId].correct = data.correct;
                }
                
                // Handle visibility of Next button based on answer correctness
                if (data.correct) {
                    toggleNextButton(true);
                } else {
                    handleIncorrectAnswer($question, questionId);
                }
            }
        }
    }
    
    /**
     * Setup MutationObserver to watch for changes to the DOM
     */
    function setupMutationObserver() {
        // Create an observer instance
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check if new questions have been added
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if ($(node).hasClass('wpProQuiz_listItem') || $(node).find('.wpProQuiz_listItem').length) {
                            ensureHintButtonVisible();
                            removeInputDisabledState();
                        }
                    }
                }
            });
        });

        // Observe the quiz container for changes
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        if (quizContainer) {
            observer.observe(quizContainer, { childList: true, subtree: true });
        }
    }

    /**
     * Make sure that hint buttons are always visible
     */
    function ensureHintButtonVisible() {
        $('.wpProQuiz_TipButton').each(function() {
            $(this).show().css('display', 'inline-block');
        });
    }
    
    /**
     * Force show all hint buttons (more aggressive approach)
     */
    function showAllHintButtons() {
        // Find all TipButtons and ensure they're visible
        $('.wpProQuiz_TipButton').each(function() {
            $(this).show().css('display', 'inline-block');
            $(this).attr('style', 'display: inline-block !important; float: left !important; margin-right: 10px !important;');
        });
        
        // Process all questions to ensure they have hint buttons and hint content
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const $tipp = $question.find('.wpProQuiz_tipp');
            const $buttons = $question.find('p:has(.wpProQuiz_QuestionButton)');
            
            // Create hint content if it doesn't exist
            if ($tipp.length === 0) {
                // Add default hint content
                $question.append(
                    '<div class="wpProQuiz_tipp" style="display: none; position: relative;">' +
                    '<div>' +
                    '<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">רמז</h5>' +
                    '<p>נסה לחשוב על התשובה הנכונה. אם אתה מתקשה, פנה למורה לעזרה.</p>' +
                    '</div>' +
                    '</div>'
                );
            }
            
            // Create hint button if it doesn't exist
            if ($buttons.length && !$buttons.find('.wpProQuiz_TipButton').length) {
                // Insert the hint button before other buttons
                $buttons.prepend('<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left !important; display: inline-block !important; margin-right: 10px !important;">');
                
                // Add click handler for new button
                $buttons.find('.wpProQuiz_TipButton').on('click', function() {
                    const $newTipp = $question.find('.wpProQuiz_tipp');
                    if ($newTipp.is(':visible')) {
                        $newTipp.hide();
                    } else {
                        $newTipp.show();
                    }
                });
            }
        });
    }
    
    /**
     * Force hide next buttons when incorrect answer is given
     */
    function forceHideNextButtons() {
        // Check if current answer is incorrect
        if ($('.wpProQuiz_incorrect:visible').length > 0) {
            $('.wpProQuiz_button[name="next"]').each(function() {
                $(this).hide().css('display', 'none').attr('style', 'display: none !important;');
            });
        }
    }
    
    /**
     * Make sure Check button is always available
     */
    function restoreCheckButton() {
        $('.wpProQuiz_button[name="check"]').each(function() {
            $(this).show().css('display', 'inline-block');
        });
    }

    /**
     * Remove disabled state from all inputs
     */
    function removeInputDisabledState() {
        // Initial removal
        $('.wpProQuiz_questionInput').prop('disabled', false).removeAttr('disabled');
    }
    
    /**
     * Enable all inputs and ensure they're clickable
     */
    function enableAllInputs() {
        // Remove disabled attribute
        $('.wpProQuiz_questionInput').prop('disabled', false).removeAttr('disabled');
        
        // Make inputs clickable by adding pointer events
        $('.wpProQuiz_questionInput').css('pointer-events', 'auto');
        $('.wpProQuiz_questionListItem label').css('pointer-events', 'auto');
    }
    
    /**
     * Set up handlers for when answers are re-selected
     */
    function setupReselectionHandlers() {
        $(document).off('click.enforceHint', '.wpProQuiz_questionInput').on('click.enforceHint', '.wpProQuiz_questionInput', function() {
            // When a new answer is selected after an incorrect one
            if ($('.wpProQuiz_incorrect:visible').length > 0) {
                const $question = $(this).closest('.wpProQuiz_listItem');
                const $checkButton = $question.find('.wpProQuiz_button[name="check"]');
                
                // Reset the response area
                $question.find('.wpProQuiz_incorrect, .wpProQuiz_correct').hide();
                
                // Show the check button
                $checkButton.show().css('display', 'inline-block');
                
                // Manually trigger the check if it's hidden
                if ($checkButton.css('display') === 'none') {
                    setTimeout(function() {
                        $checkButton.click();
                    }, 100);
                }
            }
        });
    }

    /**
     * Handle the check button click
     */
    function handleCheckButton() {
        const $question = $('.wpProQuiz_listItem:visible');
        const questionId = getQuestionIdFromElement($question);
        
        if (!questionId) return;
        
        // After a short delay, check if correct answer was given
        setTimeout(function() {
            const $correct = $question.find('.wpProQuiz_correct:visible');
            const $incorrect = $question.find('.wpProQuiz_incorrect:visible');
            
            // Make sure inputs aren't disabled
            $question.find('.wpProQuiz_questionInput').prop('disabled', false).removeAttr('disabled');
            
            if ($correct.length) {
                // If answer is correct, show next button
                if (questionStates[questionId]) {
                    questionStates[questionId].correct = true;
                }
                toggleNextButton(true);
            } else if ($incorrect.length) {
                // If answer is incorrect, handle it
                if (questionStates[questionId]) {
                    questionStates[questionId].correct = false;
                }
                handleIncorrectAnswer($question, questionId);
            }
        }, 300); // Give LearnDash time to update the DOM
    }

    /**
     * Handle when feedback is inserted into the DOM
     * This is needed because LearnDash doesn't always trigger events consistently
     */
    function handleFeedbackInserted(e) {
        const $feedback = $(e.target);
        const $question = $feedback.closest('.wpProQuiz_listItem');
        const questionId = getQuestionIdFromElement($question);
        
        if (!questionId) return;
        
        // Create state object if it doesn't exist
        if (!questionStates[questionId]) {
            questionStates[questionId] = {
                answered: true,
                correct: $feedback.hasClass('wpProQuiz_correct'),
                hintViewed: false,
                hasHint: $question.find('.wpProQuiz_hint').length > 0
            };
        } else {
            questionStates[questionId].answered = true;
            questionStates[questionId].correct = $feedback.hasClass('wpProQuiz_correct');
        }
        
        // Handle visibility of Next button based on feedback
        if ($feedback.hasClass('wpProQuiz_correct')) {
            toggleNextButton(true);
        } else if ($feedback.hasClass('wpProQuiz_incorrect')) {
            handleIncorrectAnswer($question, questionId);
        }
    }
    
    /**
     * Handle an incorrect answer
     * @param {jQuery} $question - Question element
        
        // Add highlight class
        $hintButton.addClass('highlight');
        
        // Create and attach tooltip
        const tooltip = $('<div class="hint-tooltip">טעית! להמשך חובה לקחת רמז!</div>');
        $hintButton.parent().append(tooltip);
        
        // Position tooltip
        const buttonPos = $hintButton.position();
        tooltip.css({
            'top': (buttonPos.top + $hintButton.outerHeight()) + 'px',
            'left': (buttonPos.left + $hintButton.outerWidth()/2) + 'px'
        });
        
        // Auto-scroll to hint button
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $hintButton.offset().top - 100
            }, 500);
        }, 300);
    } else {
        // Try to find or create a hint button
        showAllHintButtons();
        
        // Retry highlighting after a short delay
        setTimeout(function() {
            const $newHintButton = $question.find('.wpProQuiz_TipButton');
            if ($newHintButton.length) {
                handleIncorrectAnswer($question, questionId); // Re-run to apply tooltip
            }
        }, 100);
    }
    
    // Ensure the hint is highlighted when it's shown
    $question.find('.wpProQuiz_tipp').addClass('enforce-hint-highlight');
    
    // Add hint prompt if not already present
    if ($question.find('.enforce-hint-prompt').length === 0 && $hintButton.length) {
        const $buttonContainer = $hintButton.parent();
        $('<span class="enforce-hint-prompt">קח רמז כדי להמשיך</span>').insertAfter($hintButton);
    }
    
    // Re-attach click event to hint button to ensure it works
    $hintButton.off('click.enforceHint').on('click.enforceHint', function() {
            $tipButton.before('<span class="enforce-hint-prompt">טעית! להמשך קח רמז</span>');
        }
        
        // Make sure the tipp container is properly styled if it exists
        const $tipp = $question.find('.wpProQuiz_tipp');
        if ($tipp.length) {
            $tipp.css({
                'max-width': '100%',
                'width': '100%',
                'display': 'none',
                'position': 'relative'
            });
            
            // Add highlight class
            $tipp.addClass('enforce-hint-highlight');
            
            // Add message to the hint content if not already there
            const $hintContent = $tipp.find('div:first');
            if ($hintContent.length && !$hintContent.find('.enforce-hint-message').length) {
                $hintContent.prepend('<div class="enforce-hint-message">קרא את הרמז וודא לבחור תשובה נכונה כדי להמשיך</div>');
            }
        }
        
        // Add visual cue to question
        $question.addClass('enforce-hint-wrong-answer');
        
        // Add message to explain what's happening if not already added
        if (!$question.find('.enforce-hint-instruction').length) {
            $question.find('.wpProQuiz_question_text').after(
                '<div class="enforce-hint-instruction">' +
                'נא לבחור תשובה נכונה כדי להמשיך' +
                '</div>'
            );
        }
    }
    
    /**
     * Track when a hint is viewed
     */
    function trackHintViewed() {
        const $question = $(this).closest('.wpProQuiz_listItem');
        const questionId = getQuestionIdFromElement($question);
        
        if (questionId && questionStates[questionId]) {
            questionStates[questionId].hintViewed = true;
        }
    }
    
    /**
     * Handle navigation buttons
     * @param {Event} e - Click event
     */
    function handleNavigation(e) {
        const $question = $('.wpProQuiz_listItem:visible');
        const questionId = getQuestionIdFromElement($question);
        
        // If we have an incorrect answer and enforce hint is active, prevent navigation
        if (questionId && questionStates[questionId] && !questionStates[questionId].correct) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }
    
    /**
     * Toggle the visibility/functionality of the Next button
     * @param {boolean} show - Whether to show the button
     */
    function toggleNextButton(show) {
        const $nextButton = $('.wpProQuiz_button[name="next"]');
        
        if (show) {
            $nextButton.show().prop('disabled', false).css('display', 'block');
            $('.enforce-hint-instruction').fadeOut();
            $('.wpProQuiz_listItem').removeClass('enforce-hint-wrong-answer');
        } else {
            $nextButton.hide().prop('disabled', true).css('display', 'none');
        }
    }
    
    /**
     * Get question ID from a question element
     * @param {jQuery} $question - Question element
     * @return {string|null} Question ID or null
     */
    function getQuestionIdFromElement($question) {
        // Try from data attribute
        let questionId = $question.data('question-id');
        
        if (!questionId) {
            // Try from question list
            const $questionList = $question.find('.wpProQuiz_questionList');
            if ($questionList.length && $questionList.data('question-id')) {
                questionId = $questionList.data('question-id');
            }
        }
        
        if (!questionId) {
            // Try from index
            const questionIndex = $question.index();
            if (window.quizQuestionData && window.quizQuestionData[questionIndex]) {
                questionId = window.quizQuestionData[questionIndex];
            }
        }
        
        return questionId;
    }
    
    // Initialize on document ready
    $(document).ready(initQuizEnforceHint);
    
})(jQuery);
