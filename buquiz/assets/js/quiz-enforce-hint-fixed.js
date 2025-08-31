/**
 * Quiz Enforce Hint Feature
 * 
 * Forces users to view hints and select the correct answer before proceeding.
 * Only activates when 'Enforce Hint' setting is enabled for the quiz.
 */
(function($) {
    'use strict';

    // Create default settings if not defined
    if (typeof ldQuizSettings === 'undefined') {
        console.log('Creating default quiz settings');
        window.ldQuizSettings = {
            enforceHint: true
        };
    }

    // Track question state
    let questionStates = {};
    let enforcingHint = false;
    
    /**
     * Initialize the quiz enforce hint functionality
     */
    function initQuizEnforceHint() {
        // Always run on quiz pages regardless of settings
        console.log('Enforce Hint feature activated (fixed version)');
        
        // Immediately set up all event listeners
        setupEventListeners();
        
        // Force immediate execution of critical functions
        forceImmediateSetup();
        
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
        // Only hide next button if we have an incorrect answer in the current question
        const $question = $('.wpProQuiz_listItem:visible');
        const questionId = getQuestionIdFromElement($question);
        
        if (questionId && questionStates[questionId] && !questionStates[questionId].correct && questionStates[questionId].answered) {
            // Multiple hiding approaches for robustness
            $('.wpProQuiz_button[name="next"]').hide().css('display', 'none')
                .attr('style', 'display: none !important; visibility: hidden !important;')
                .addClass('force-hide');
        }
    }
    
    /**
     * Make sure Check button is always available
     */
    function restoreCheckButton() {
        $('.wpProQuiz_button[name="check"]')
            .show().css('display', 'inline-block')
            .attr('style', 'display: inline-block !important; float: right; margin-right: 10px;');
    }
    
    /**
     * Remove disabled state from all inputs
     */
    function removeInputDisabledState() {
        $('.wpProQuiz_questionInput').prop('disabled', false)
            .removeAttr('disabled')
            .css('pointer-events', 'auto');
    }
    
    /**
     * Enable all inputs and ensure they're clickable
     */
    function enableAllInputs() {
        $('.wpProQuiz_questionInput').each(function() {
            $(this).prop('disabled', false)
                .removeAttr('disabled')
                .css('pointer-events', 'auto');
                
            $(this).closest('label').css('pointer-events', 'auto');
        });
    }
    
    /**
     * Set up handlers for when answers are re-selected
     */
    function setupReselectionHandlers() {
        $('.wpProQuiz_questionInput').off('click.reselection').on('click.reselection', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // Clear any previous feedback
            $question.find('.wpProQuiz_incorrect, .wpProQuiz_correct').hide();
            
            // Remove warning styling
            $question.find('.enforce-hint-wrong-answer').removeClass('enforce-hint-wrong-answer');
            $question.find('.enforce-hint-instruction').remove();
            $question.find('.hint-tooltip').remove();
            
            // Remove highlight from hint button
            $question.find('.wpProQuiz_TipButton').removeClass('highlight enforce-hint-button-highlight');
            
            // Show check button
            $question.find('.wpProQuiz_button[name="check"]').show().css('display', 'inline-block');
        });
    }
    
    /**
     * Handle the check button click
     */
    function handleCheckButton() {
        const $question = $(this).closest('.wpProQuiz_listItem');
        const questionId = getQuestionIdFromElement($question);
        
        if (!questionId) return;
        
        // Mark as answered
        if (!questionStates[questionId]) {
            questionStates[questionId] = {
                answered: true,
                correct: false, // We don't know yet
                hintViewed: false,
                hasHint: $question.find('.wpProQuiz_hint').length > 0
            };
        } else {
            questionStates[questionId].answered = true;
        }
        
        // LearnDash will handle the validation, but we need to prepare for re-selection
        // after an incorrect answer
        setTimeout(function() {
            // Ensure hint buttons are visible and inputs are enabled
            removeInputDisabledState();
            enableAllInputs();
            
            // If the answer is incorrect, make sure the next button stays hidden
            if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
                forceHideNextButtons();
                
                // Highlight hint button again
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
     * @param {string} questionId - Question ID
     */
    function handleIncorrectAnswer($question, questionId) {
        const state = questionStates[questionId];
        
        // Hide next button
        toggleNextButton(false);
        
        // Make sure inputs are still enabled
        removeInputDisabledState();
        
        // Apply special styling to highlight the error state
        $question.find('.wpProQuiz_question').addClass('enforce-hint-wrong-answer');
        
        // Remove any existing tooltips
        $('.hint-tooltip').remove();
        
        // Find hint button
        const $hintButton = $question.find('.wpProQuiz_TipButton');
        if ($hintButton.length) {
            // Add highlighting classes
            $hintButton.addClass('highlight enforce-hint-button-highlight');
            
            // Create tooltip with the message
            const $tooltip = $('<div class="hint-tooltip">טעית! להמשך חובה לקחת רמז!</div>');
            $hintButton.parent().append($tooltip);
            
            // Position the tooltip relative to the button
            const buttonPos = $hintButton.position();
            if (buttonPos) {
                $tooltip.css({
                    'position': 'absolute',
                    'top': (buttonPos.top + $hintButton.outerHeight() + 5) + 'px',
                    'left': (buttonPos.left + $hintButton.outerWidth()/2) + 'px'
                });
            }
            
            // Auto-scroll to hint button
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $hintButton.offset().top - 100
                }, 500);
            }, 300);
            
            // Re-attach click event
            $hintButton.off('click.tooltip').on('click.tooltip', function() {
                // Hide tooltip when hint is clicked
                $('.hint-tooltip').fadeOut(300, function() {
                    $(this).remove();
                });
            });
        } else {
            // Try to create hint button if it doesn't exist
            showAllHintButtons();
            
            // Retry after a short delay
            setTimeout(function() {
                const $newHintButton = $question.find('.wpProQuiz_TipButton');
                if ($newHintButton.length) {
                    handleIncorrectAnswer($question, questionId);
                }
            }, 100);
        }
        
        // Make sure the hint is properly styled
        const $tipp = $question.find('.wpProQuiz_tipp');
        if ($tipp.length) {
            $tipp.addClass('enforce-hint-highlight');
            
            // Ensure there's a message in the hint
            if ($tipp.find('.enforce-hint-message').length === 0) {
                $tipp.find('div:first').prepend(
                    $('<div class="enforce-hint-message"></div>')
                        .text('לאחר שתקרא את הרמז, נסה שוב לבחור את התשובה הנכונה')
                );
            }
        }
        
        // Show instruction if not already present
        if ($question.find('.enforce-hint-instruction').length === 0) {
            $question.find('.wpProQuiz_questionList').after(
                '<div class="enforce-hint-instruction">טעית! להמשך קח רמז</div>'
            );
        }
        
        // Setup for re-selection
        setupReselectionHandlers();
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
            $nextButton.show().css('display', 'inline-block')
                .removeClass('force-hide');
        } else {
            $nextButton.hide().css('display', 'none')
                .attr('style', 'display: none !important; visibility: hidden !important;')
                .addClass('force-hide');
        }
    }
    
    /**
     * Get question ID from a question element
     * @param {jQuery} $question - Question element
     * @return {string|null} Question ID or null
     */
    /**
     * Force immediate setup of all quiz features
     * This ensures everything runs even if there are script errors elsewhere
     */
    function forceImmediateSetup() {
        // Ensure all hint buttons are immediately visible
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const $buttons = $question.find('p:has(.wpProQuiz_QuestionButton)');
            
            // First, forcibly add a hint button if none exists
            if (!$buttons.find('.wpProQuiz_TipButton').length) {
                $buttons.prepend('<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left !important; display: inline-block !important; margin-right: 10px !important;">');                
            }
            
            // Make sure all buttons are visible
            $buttons.find('.wpProQuiz_TipButton').show().css('display', 'inline-block')
                .attr('style', 'display: inline-block !important; float: left !important; margin-right: 10px !important;');
                
            // Make all inputs clickable - multiple approaches
            $question.find('.wpProQuiz_questionInput').prop('disabled', false)
                .removeAttr('disabled')
                .css('pointer-events', 'auto')
                .closest('label').css('pointer-events', 'auto');
            
            // Hide next button until correct answer
            if (!$question.find('.wpProQuiz_correct').is(':visible')) {
                $question.find('.wpProQuiz_button[name="next"]').hide()
                    .css('display', 'none')
                    .attr('style', 'display: none !important; visibility: hidden !important;');
            }
            
            // Ensure check button is visible
            $question.find('.wpProQuiz_button[name="check"]').show()
                .css('display', 'inline-block')
                .attr('style', 'display: inline-block !important; float: right; margin-right: 10px;');
                
            // Add click handler to hint button
            $buttons.find('.wpProQuiz_TipButton').off('click.forceSetup').on('click.forceSetup', function() {
                const $tipp = $question.find('.wpProQuiz_tipp');
                if ($tipp.length) {
                    if ($tipp.is(':visible')) {
                        $tipp.hide();
                    } else {
                        $tipp.show();
                    }
                } else {
                    // Create hint content if it doesn't exist
                    $question.append(
                        '<div class="wpProQuiz_tipp" style="display: block; position: relative;">' +
                        '<div>' +
                        '<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">רמז</h5>' +
                        '<p>נסה לחשוב על התשובה הנכונה. אם אתה מתקשה, פנה למורה לעזרה.</p>' +
                        '</div>' +
                        '</div>'
                    );
                }
            });
        });
        
        // Create event handlers for quiz correctness
        $('.wpProQuiz_questionInput').off('change.forceSetup').on('change.forceSetup', function() {
            const $input = $(this);
            const $question = $input.closest('.wpProQuiz_listItem');
            
            // Clear any previous feedback
            $question.find('.wpProQuiz_incorrect, .wpProQuiz_correct').hide();
            
            // Remove any error styles
            $question.find('.enforce-hint-wrong-answer').removeClass('enforce-hint-wrong-answer');
            $question.find('.enforce-hint-instruction').remove();
            $question.find('.hint-tooltip').remove();
            
            // Make sure check button is visible
            $question.find('.wpProQuiz_button[name="check"]').show()
                .css('display', 'inline-block');
        });
        
        // Run again after a delay to catch any dynamic changes
        setTimeout(forceImmediateSetup, 1500);
    }
    
    function getQuestionIdFromElement($question) {
        // Try to get from data attribute
        if ($question.data('question-meta')) {
            const meta = $question.data('question-meta');
            if (meta.question_post_id) {
                return 'q_' + meta.question_post_id;
            } else if (meta.question_pro_id) {
                return 'qpro_' + meta.question_pro_id;
            }
        }
        
        // Try to get from question list
        const $questionList = $question.find('.wpProQuiz_questionList');
        if ($questionList.length && $questionList.data('question_id')) {
            return 'qid_' + $questionList.data('question_id');
        }
        
        // Try to get from input name
        const $input = $question.find('.wpProQuiz_questionInput');
        if ($input.length && $input.attr('name')) {
            const inputName = $input.attr('name');
            const matches = inputName.match(/question_\d+_(\d+)/);
            if (matches && matches[1]) {
                return 'qname_' + matches[1];
            }
        }
        
        return null;
    }
    
    // Initialize on document ready
    $(document).ready(initQuizEnforceHint);
    
})(jQuery);
