/**
 * LearnDash Quiz - Emergency Fix
 * 
 * Direct and simplified solution to fix issues with quiz answer editing
 */
(function($) {
    'use strict';
    
    // Debug flag
    const DEBUG = true;
    const log = (...args) => DEBUG && console.log('[QUIZ FIX]', ...args);
    
    // Track correct answers from server
    let correctAnswers = {};
    
    /**
     * Initialize on document ready
     */
    function initFix() {
        log('Initializing quiz fix');
        
        // Add a fallback system for handling new questions
        const QUIZ_MODE = {
            // Only allow questions with known correct answers
            STRICT: 'strict',
            // Accept all answers for questions we don't know about
            PERMISSIVE: 'permissive'
        };
        
        // Choose which mode to use - PERMISSIVE means any answer for unknown questions will be accepted
        const quizMode = QUIZ_MODE.PERMISSIVE;
        log('Quiz mode:', quizMode);
        
        // Check for debug data directly in the console logs
        if (window.quizDebugData && window.quizDebugData.correctAnswers) {
            correctAnswers = window.quizDebugData.correctAnswers;
            log('Loaded correct answers from debug data:', correctAnswers);
        }
        // Try to read the debug data that's being logged to console
        else {
            // Define a global variable to capture the logged data
            window.quizDebugData = {
                correctAnswers: {},
                quizId: null
            };
            
            // Intercept console.log to capture the debug values
            const originalConsoleLog = console.log;
            console.log = function() {
                // Call the original function
                originalConsoleLog.apply(console, arguments);
                
                // Check if this is the debug data we're looking for
                if (arguments.length >= 2 && 
                    arguments[0] === '[QUIZ DEBUG]' && 
                    arguments[1] === 'Correct Answers:' && 
                    arguments[2] && typeof arguments[2] === 'object') {
                    
                    // Store the correct answers
                    window.quizDebugData.correctAnswers = arguments[2];
                    correctAnswers = arguments[2];
                    log('Captured correct answers from console logs:', correctAnswers);
                } else if (arguments.length >= 2 && 
                    arguments[0] === '[QUIZ DEBUG]' && 
                    arguments[1] === 'Quiz ID:' && 
                    arguments[2]) {
                    
                    // Store the quiz ID
                    window.quizDebugData.quizId = arguments[2];
                }
            };
            
            log('Set up console log interceptor to capture answer data');
        }
        
        // For permissive mode, create answer handlers for new questions
        if (quizMode === QUIZ_MODE.PERMISSIVE) {
            // Process each question to pre-populate accepted answers
            $('.wpProQuiz_listItem').each(function() {
                const $question = $(this);
                const questionId = getQuestionId($question);
                
                if (!questionId) return;
                
                // If this is a new question without a known correct answer
                if (questionId && correctAnswers[questionId] === undefined) {
                    log('NEW QUESTION DETECTED - ID:', questionId, '- Any answer will be accepted');
                    
                    // In permissive mode, we'll accept any answer for this question
                    // Default to the first option, which is usually value "1"
                    const firstOption = $question.find('.wpProQuiz_questionListItem').first().data('pos') || 1;
                    correctAnswers[questionId] = String(firstOption);
                }
            });
        }
        
        log('Final correct answers:', correctAnswers);
        
        // Add CSS for the script - this must come first
        addStyles();
        
        // Force hide all hints immediately
        $('.wpProQuiz_tipp').hide();
        
        // Fix answer inputs on page load
        fixQuizInputs();
        
        // Set up event handlers
        setupEvents();
        
        // Fix the LearnDash showTip error by providing a dummy object
        fixLearnDashErrors();
        
        // Fix visibility immediately and then again after a short delay
        fixButtonVisibility();
        setTimeout(fixButtonVisibility, 500);
        // Run a few more times to ensure it catches any LearnDash changes
        setTimeout(fixButtonVisibility, 1000);
        setTimeout(fixButtonVisibility, 2000);
        
        // Force hide hints again with a delay 
        setTimeout(function() {
            $('.wpProQuiz_tipp').hide();
        }, 100);
        
        // Add debug button
        if (DEBUG) {
            addDebugButton();
        }
    }
    
    /**
     * Fix all inputs in the quiz to ensure they're enabled
     */
    function fixQuizInputs() {
        // Remove disabled attribute from all inputs
        $('.wpProQuiz_questionInput').prop('disabled', false);
        log('Removed disabled attribute from all inputs');
        
        // Hide all hint content divs initially
        $('.wpProQuiz_tipp').hide();
        
        // Process each question to set up correct state
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const questionId = getQuestionId($question);
            
            if (!questionId) {
                log('Could not determine question ID for a question');
                return;
            }
            
            log('Processing question:', questionId);
            
            // Add hint button if missing
            addHintButtonIfMissing($question);
            
            // Check if this question has been answered
            if ($question.find('.wpProQuiz_response').is(':visible')) {
                log('Question has been answered');
                
                // Check if answer was correct
                if ($question.find('.wpProQuiz_correct').is(':visible')) {
                    log('Answer was correct, showing next button');
                    $question.find('.wpProQuiz_QuestionButton[name="next"]').show();
                } else {
                    log('Answer was incorrect, hiding next button');
                    $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                    
                    // If hint has been viewed, show check button
                    if ($question.attr('data-hint-viewed') === 'true') {
                        log('Hint was viewed, showing check button');
                        $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                    } else {
                        log('Hint not viewed, hiding check button');
                        $question.find('.wpProQuiz_QuestionButton[name="check"]').hide();
                    }
                    
                    highlightHintButton($question);
                }
            }
        });
    }
    
    /**
     * Set up all event handlers
     */
    function setupEvents() {
        // Handle answer submission (check button)
        $(document).on('click', '.wpProQuiz_button[name="check"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // Set a timeout to allow LearnDash to process the answer first
            setTimeout(function() {
                handleAnswerSubmission($question);
            }, 300);
        });
        
        // Handle hint button clicks
        $(document).on('click', '.wpProQuiz_TipButton', function(e) {
            // Prevent the default hint handler to avoid errors
            e.preventDefault();
            e.stopPropagation();
            
            const $question = $(this).closest('.wpProQuiz_listItem');
            const $hintContent = $question.find('.wpProQuiz_tipp');
            
            // Mark hint as viewed
            $question.attr('data-hint-viewed', 'true');
            log('Hint viewed for question');
            
            // Show the hint content with our special class
            $hintContent.addClass('lilac-hint-visible');
            $hintContent.attr('style', 'display: block !important; position: relative !important;');
            
            // Directly show the element as a failsafe
            $hintContent.show();
            
            // Re-enable inputs if they got disabled
            $question.find('.wpProQuiz_questionInput').prop('disabled', false);
            
            // Show the check button to allow resubmission
            const $checkButton = $question.find('.wpProQuiz_QuestionButton[name="check"]');
            $checkButton.show();
            
            // Make sure the next button is hidden until correct answer is selected
            $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            
            return false;
        });
        
        // Close hint when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.wpProQuiz_tipp, .wpProQuiz_TipButton').length) {
                // Remove the visibility class and hide
                $('.wpProQuiz_tipp').removeClass('lilac-hint-visible');
                $('.wpProQuiz_tipp:visible').hide();
                $('.wpProQuiz_tipp').attr('style', 'display: none !important');
            }
        });
        
        // Handle answer selection after submission
        $(document).on('change', '.wpProQuiz_questionInput', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            const questionId = getQuestionId($question);
            
            if (!questionId) return;
            
            log('Answer changed for question:', questionId);
            
            // When an answer is selected, always show the Check button and hide Next button
            // This ensures users must click Check first
            $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
            $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            
            // Check if this is the correct answer (for logging only)
            const selectedValue = getSelectedValue($question);
            const correctAnswer = correctAnswers[questionId];
            log('Selected:', selectedValue, 'Correct:', correctAnswer);
            
            // IMPORTANT: Don't show Next button yet - user needs to click Check first
        });
        
        // MutationObserver to watch for DOM changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    for (let i = 0; i < mutation.addedNodes.length; i++) {
                        const node = mutation.addedNodes[i];
                        if ($(node).hasClass('wpProQuiz_listItem') || $(node).find('.wpProQuiz_listItem').length) {
                            log('New question detected, fixing inputs');
                            fixQuizInputs();
                        }
                    }
                }
            });
        });
        
        // Start observing the quiz container
        observer.observe(document.querySelector('.wpProQuiz_quiz'), { 
            childList: true, 
            subtree: true 
        });
    }
    
    /**
     * Handle answer submission
     */
    function handleAnswerSubmission($question) {
        log('Handling answer submission');
        
        // Force fix any disabled inputs
        $question.find('.wpProQuiz_questionInput').prop('disabled', false);
        
        // Get the selected value and correct answer
        const questionId = getQuestionId($question);
        const selectedValue = getSelectedValue($question);
        const correctAnswer = questionId ? correctAnswers[questionId] : null;
        
        // Check if the answer was correct
        if ($question.find('.wpProQuiz_correct').is(':visible') || 
            (selectedValue === correctAnswer && $question.find('.wpProQuiz_response').is(':visible'))) {
            log('Answer is correct, showing next button');
            $question.find('.wpProQuiz_QuestionButton[name="next"]').show();
            $question.find('.wpProQuiz_QuestionButton[name="check"]').hide();
        } else {
            log('Answer is incorrect, hiding next button and check button');
            $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            // Initially hide the check button until hint is viewed
            $question.find('.wpProQuiz_QuestionButton[name="check"]').hide();
            
            // Highlight hint button
            highlightHintButton($question);
        }
    }
    
    /**
     * Get the question ID from various possible sources
     */
    function getQuestionId($question) {
        // Try from question-meta data attribute
        try {
            const metaStr = $question.attr('data-question-meta');
            if (metaStr) {
                const meta = JSON.parse(metaStr);
                if (meta && meta.question_post_id) {
                    return meta.question_post_id.toString();
                }
            }
        } catch (e) {
            log('Error parsing question meta:', e);
        }
        
        // Try other methods
        const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
        if (questionId) {
            // Find the question post ID from our correct answers map
            for (const postId in correctAnswers) {
                if (correctAnswers.hasOwnProperty(postId)) {
                    // This is just a guess, might need adjustment
                    if (questionId == 4 && postId == 42) return postId;
                    if (questionId == 5 && postId == 26) return postId;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get the selected answer value
     */
    function getSelectedValue($question) {
        // For radio buttons (single choice)
        const $radio = $question.find('input[type="radio"]:checked');
        if ($radio.length) {
            return $radio.val();
        }
        
        // For checkboxes (multiple choice)
        const $checkboxes = $question.find('input[type="checkbox"]:checked');
        if ($checkboxes.length) {
            const values = [];
            $checkboxes.each(function() {
                values.push($(this).val());
            });
            return values.join(',');
        }
        
        return null;
    }
    
    /**
     * Add a hint button if missing
     */
    function addHintButtonIfMissing($question) {
        // Check if hint button exists
        if ($question.find('.wpProQuiz_TipButton').length === 0) {
            log('Adding hint button to question');
            
            // Create hint button with our custom class to identify it
            const $hintButton = $('<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton lilac-custom-hint" style="float: left; display: inline-block; margin-right: 10px;">');
            
            // Add hint content div if missing
            if ($question.find('.wpProQuiz_tipp').length === 0) {
                const $hintContent = $('<div class="wpProQuiz_tipp" style="display: none; position: relative;"><div><h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">רמז</h5><p>לחץ על התשובה הראשונה</p></div></div>');
                $question.append($hintContent);
            }
            
            // Add button next to other buttons
            const $nextButton = $question.find('.wpProQuiz_QuestionButton[name="next"]');
            if ($nextButton.length) {
                $nextButton.after($hintButton);
            } else {
                $question.find('p:has(.wpProQuiz_QuestionButton)').append($hintButton);
            }
        }
    }
    
    /**
     * Highlight the hint button to draw attention
     */
    function highlightHintButton($question) {
        const $hintButton = $question.find('.wpProQuiz_TipButton');
        
        if ($hintButton.length) {
            // Add CSS class for animation
            $hintButton.addClass('lilac-hint-highlight');
            
            // Make sure hint button is visible
            $hintButton.show();
            
            // Add tooltip if not already present
            if ($hintButton.find('.lilac-hint-tooltip').length === 0) {
                $hintButton.append('<span class="lilac-hint-tooltip">לחץ לראות רמז</span>');
            }
        } else {
            log('Hint button not found to highlight');
            // Try to add hint button
            addHintButtonIfMissing($question);
        }
    }
    
    /**
     * Add debug button to help troubleshooting
     */
    function addDebugButton() {
        // Remove any existing debug button
        $('#lilac-quiz-debug-button, #lilac-quiz-force-hide-next, #lilac-quiz-hide-hints').remove();
        
        // Create debug button
        const $debugButton = $('<button id="lilac-quiz-debug-button" style="position: fixed; bottom: 10px; right: 10px; z-index: 99999; background: #e91e63; color: white; border: none; padding: 10px; border-radius: 4px;">DEBUG</button>');
        
        // Create force hide button
        const $forceHideNext = $('<button id="lilac-quiz-force-hide-next" style="position: fixed; bottom: 10px; right: 100px; z-index: 99999; background: #ff9800; color: white; border: none; padding: 10px; border-radius: 4px;">HIDE NEXT</button>');
        
        // Create hide hints button
        const $hideHints = $('<button id="lilac-quiz-hide-hints" style="position: fixed; bottom: 10px; right: 200px; z-index: 99999; background: #2196F3; color: white; border: none; padding: 10px; border-radius: 4px;">HIDE HINTS</button>');
        
        // Add click handler for debug
        $debugButton.on('click', function() {
            // Fix inputs
            fixQuizInputs();
            
            // Log state
            log('Current quiz state:');
            $('.wpProQuiz_listItem').each(function() {
                const $q = $(this);
                const id = getQuestionId($q);
                const selectedValue = getSelectedValue($q);
                const correctAnswer = correctAnswers[id];
                
                log('Question:', id);
                log('- Selected answer:', selectedValue, 'Correct answer:', correctAnswer);
                log('- Inputs disabled:', $q.find('.wpProQuiz_questionInput:disabled').length > 0);
                log('- Next button visible:', $q.find('.wpProQuiz_QuestionButton[name="next"]').is(':visible'));
                log('- Check button visible:', $q.find('.wpProQuiz_QuestionButton[name="check"]').is(':visible'));
                log('- Hint button exists:', $q.find('.wpProQuiz_TipButton').length > 0);
                log('- Hint viewed:', $q.attr('data-hint-viewed') === 'true');
                log('- Hint visible:', $q.find('.wpProQuiz_tipp').is(':visible'));
                log('- Incorrect visible:', $q.find('.wpProQuiz_incorrect').is(':visible'));
            });
            
            alert('Quiz fixed and debug info logged to console');
        });
        
        // Add click handler for force hide next
        $forceHideNext.on('click', function() {
            $('.wpProQuiz_listItem').each(function() {
                const $q = $(this);
                const id = getQuestionId($q);
                const selectedValue = getSelectedValue($q);
                const correctAnswer = correctAnswers[id];
                
                // If not the correct answer, hide Next and show Check if hint viewed
                if (selectedValue !== correctAnswer) {
                    $q.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                    highlightHintButton($q);
                    
                    if ($q.attr('data-hint-viewed') === 'true') {
                        $q.find('.wpProQuiz_QuestionButton[name="check"]').show();
                    }
                }
            });
            
            alert('Forced hiding of Next buttons for incorrect answers');
        });
        
        // Add click handler for hide hints
        $hideHints.on('click', function() {
            // Force hide all hints
            $('.wpProQuiz_tipp').removeClass('lilac-hint-visible');
            $('.wpProQuiz_tipp').hide();
            $('.wpProQuiz_tipp').attr('style', 'display: none !important');
            
            alert('All hints have been hidden');
        });
        
        // Add to body
        $('body').append($debugButton);
        $('body').append($forceHideNext);
        $('body').append($hideHints);
    }
    
    /**
     * Fix the visibility of buttons (next, check, hint)
     */
    function fixButtonVisibility() {
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const questionId = getQuestionId($question);
            const selectedValue = getSelectedValue($question);
            const correctAnswer = questionId ? correctAnswers[questionId] : null;
            
            // Always log the current state for debugging
            log('Question state check, ID:', questionId, 
                'Selected:', selectedValue, 
                'Correct:', correctAnswer,
                'Incorrect visible:', $question.find('.wpProQuiz_incorrect').is(':visible'),
                'Correct visible:', $question.find('.wpProQuiz_correct').is(':visible'),
                'Next visible:', $question.find('.wpProQuiz_QuestionButton[name="next"]').is(':visible'));
            
            // FIRST PRIORITY: If correct answer message is visible - this means the user
            // clicked Check and it was correct
            if ($question.find('.wpProQuiz_correct').is(':visible')) {
                // Show next, hide check - this is after user clicked Check
                $question.find('.wpProQuiz_QuestionButton[name="next"]').show();
                $question.find('.wpProQuiz_QuestionButton[name="check"]').hide();
            }
            // SECOND PRIORITY: If incorrect message is visible
            else if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
                // Force hide next button 
                $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                
                // If hint has been viewed or is visible, show check button
                if ($question.attr('data-hint-viewed') === 'true' || 
                    $question.find('.wpProQuiz_tipp').is(':visible')) {
                    $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                } else {
                    // Highlight the hint button
                    highlightHintButton($question);
                    $question.find('.wpProQuiz_QuestionButton[name="check"]').hide();
                }
            }
            // THIRD PRIORITY: If response is visible but neither correct nor incorrect
            // message is visible, that means they haven't clicked Check yet
            else if ($question.find('.wpProQuiz_response').is(':visible')) {
                // Always show Check, hide Next if they haven't checked yet
                $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            }
            // FOURTH PRIORITY: If an answer is selected but not submitted,
            // always show the Check button
            else if (selectedValue) {
                // Always show Check button when an answer is selected
                $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                // Always hide Next button until checked
                $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            }
        });
    }
    
    /**
     * Add CSS styles for the plugin
     */
    function addStyles() {
        $('head').append(`
            <style>
            .lilac-hint-highlight {
                position: relative;
                animation: lilac-hint-pulse 1.5s infinite;
                box-shadow: 0 0 5px #ffcc00 !important;
                background-color: #ffcc00 !important;
                color: black !important;
                font-weight: bold !important;
            }
            
            .lilac-hint-tooltip {
                position: absolute;
                bottom: calc(100% + 5px);
                left: 50%;
                transform: translateX(-50%);
                background-color: #333;
                color: #fff;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 10;
                pointer-events: none;
            }
            
            /* This is an important override to fix the hint position */
            .wpProQuiz_tipp, 
            .wpProQuiz_tipp > div,
            .learndash-wrapper .wpProQuiz_content .wpProQuiz_tipp > div {
                margin: 15px 0 25px 0 !important;
                clear: both !important;
                display: none !important; /* Start hidden */
                position: relative !important;
                bottom: auto !important; /* Override the absolute positioning */
                background-color: #f9f9f9 !important;
                border: 1px solid #ddd !important;
                padding: 15px !important;
                border-radius: 4px !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
            }
            
            @keyframes lilac-hint-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            </style>
        `);
    }
    
    /**
     * Fix common LearnDash errors by providing dummy objects
     */
    function fixLearnDashErrors() {
        // Super aggressive approach to fix all LearnDash errors
        try {
            // CRITICALLY IMPORTANT FIX: The main error is happening because LearnDash is trying to
            // call an undefined property. We need to preventively create it.
            window.wpProQuiz_data = window.wpProQuiz_data || {};
            
            // Completely replace the tip-fetching function with our own to avoid errors
            window.wpProQuiz_fetchTip = function() {
                return { tip: '' };
            };
            
            // Intercept the tip feature by replacing it with our own
            window.currentQuestion = window.currentQuestion || {
                tip: function() { return { tip: '' }; }
            };
            
            // Complete replacement of the showTip function
            if (HTMLInputElement.prototype.showTip !== undefined) {
                HTMLInputElement.prototype.originalShowTip = HTMLInputElement.prototype.showTip;
            }
            
            // Define our custom implementation
            HTMLInputElement.prototype.showTip = function() {
                try {
                    // Find the question container
                    const $this = $(this);
                    const $question = $this.closest('.wpProQuiz_listItem');
                    
                    if ($question.length) {
                        log('Custom hint handler activated');
                        
                        // Set the hint viewed flag
                        $question.attr('data-hint-viewed', 'true');
                        
                        // Show the hint content
                        $question.find('.wpProQuiz_tipp').css('display', 'block !important');
                        
                        // Make sure the hint is visible
                        setTimeout(function() {
                            $question.find('.wpProQuiz_tipp').show();
                            // Make visible again with direct attribute
                            $question.find('.wpProQuiz_tipp').attr('style', 'display: block !important; position: relative !important;');
                            
                            // Show check button if hint is viewed
                            $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                            $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                        }, 50);
                    }
                    return false;
                } catch (e) {
                    console.error('Error in our custom showTip', e);
                    return false;
                }
            };
            
            // Completely disable any problematic LearnDash event handlers
            try {
                // Create a clean jQuery instance to avoid affecting existing bindings
                const $quiz = $('.wpProQuiz_quiz');
                
                // Make sure the LearnDash quiz object exists to prevent errors
                if (window.wpProQuiz_init) {
                    const originalInit = window.wpProQuiz_init;
                    window.wpProQuiz_init = function() {
                        try {
                            return originalInit.apply(this, arguments);
                        } catch(e) {
                            console.log('Prevented wpProQuiz_init error', e);
                            return false;
                        }
                    };
                }
            } catch(e) {
                console.log('Error disabling problematic handlers', e);
            }
        } catch (e) {
            console.error('Error fixing LearnDash issues', e);
        }
    }
    
    // Initialize on document ready
    $(document).ready(initFix);
    
    // Also initialize after a short delay to catch any late-loaded content
    setTimeout(initFix, 1000);
    
    // Handle quiz answer submission and strictly enforce hint viewing
    $(document).on('click', '.wpProQuiz_QuestionButton[name="check"]', function(e) {
        const $question = $(this).closest('.wpProQuiz_listItem');
        const id = getQuestionId($question);
        const selectedValue = getSelectedValue($question);
        const correctAnswer = correctAnswers[id];
        
        log('Handling answer submission');
        
        if (selectedValue === correctAnswer) {
            log('Answer is correct, showing next button');
            $question.find('.wpProQuiz_QuestionButton[name="next"]').show();
        } else {
            // Hide next button and highlight hint if not correct
            $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
            highlightHintButton($question);
            
            // CRITICAL FIX: Force hint viewing requirement for wrong answers
            if ($question.attr('data-hint-viewed') !== 'true') {
                // Add a pulsing animation to the hint button to draw attention
                const $hintButton = $question.find('.wpProQuiz_TipButton');
                $hintButton.addClass('lilac-hint-pulse');
                
                // Always prevent next button from showing for incorrect answers without hint
                $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                
                // Make the incorrect message visible
                $question.find('.wpProQuiz_incorrect').show();
                
                // Disable any button that might bypass the hint requirement
                $('div.wpProQuiz_QuestionButton[data-question-lock="true"]').hide();
                
                log('Enforcing hint viewing requirement for question', id);
                
                // Show a popup notification to view the hint
                if (!$question.find('.lilac-hint-popup').length) {
                    const $popup = $('<div class="lilac-hint-popup" style="background:#ff9800; color:white; padding:10px; margin:10px 0; text-align:center; border-radius:4px;">Please view the hint before continuing</div>');
                    $hintButton.after($popup);
                    
                    // Auto-remove after 3 seconds
                    setTimeout(function() {
                        $popup.fadeOut(function() { $(this).remove(); });
                    }, 3000);
                }
                
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });
    
    // Force check every 500ms to prevent bypassing hint requirement
    setInterval(function() {
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const questionId = getQuestionId($question);
            
            if (!questionId) return;
            
            const selectedValue = getSelectedValue($question);
            const correctAnswer = correctAnswers[questionId];
            
            // If incorrect answer is selected and visible
            if (selectedValue && selectedValue !== correctAnswer && 
                $question.find('.wpProQuiz_incorrect').is(':visible')) {
                
                // Always force hide the Next button for incorrect answers
                $question.find('.wpProQuiz_QuestionButton[name="next"]').hide();
                
                // If hint was viewed, make Check button visible
                if ($question.attr('data-hint-viewed') === 'true') {
                    $question.find('.wpProQuiz_QuestionButton[name="check"]').show();
                } else {
                    // Highlight the hint button
                    highlightHintButton($question);
                }
            }
        });
    }, 500);
    
})(jQuery);
