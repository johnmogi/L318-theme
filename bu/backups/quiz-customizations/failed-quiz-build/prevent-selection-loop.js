/**
 * Prevent Selection Loop in LearnDash Quizzes
 * 
 * This script ONLY prevents answer selection changes in force hint mode quizzes.
 * Regular quizzes should allow free selection of answers.
 * 
 * VERBOSE DEBUG MODE ENABLED
 */

// Wait for DOM and quiz extensions to fully initialize
jQuery(document).ready(function() {
    console.log('%cPrevent Selection Loop: Script loaded', 'background: #f0f0ff; color: #000; padding: 3px; border-radius: 3px;');
    
    // Give extra time for quiz settings to load
    setTimeout(function() {
        preventAnswerSelectionLoop();
    }, 500);
});

/**
 * Main function to prevent the answer selection loop in LearnDash quizzes,
 * but ONLY for force hint mode quizzes
 */
function preventAnswerSelectionLoop() {
    // Global state tracking
    const state = {
        debug: true,
        hintViewed: {},  // Track hint state per question { questionId: true/false }
        selectionLocked: {}, // Track lock state per question { questionId: true/false }
        forceMode: null,  // Is this quiz in force hint mode?
        quizId: null      // Current quiz ID
    };
    
    // Enhanced debug logging
    function debugLog(message, data = null, type = 'info') {
        if (!state.debug) return;
        
        let style = '';
        switch (type) {
            case 'error': 
                style = 'background: #ffeeee; color: #aa0000; padding: 3px; border-radius: 3px;';
                break;
            case 'warn':
                style = 'background: #ffffdd; color: #aaaa00; padding: 3px; border-radius: 3px;';
                break;
            case 'success':
                style = 'background: #eeffee; color: #00aa00; padding: 3px; border-radius: 3px;';
                break;
            case 'info':
            default:
                style = 'background: #eeeeff; color: #0000aa; padding: 3px; border-radius: 3px;';
        }
        
        console.log(`%cPrevent Selection Loop: ${message}`, style);
        if (data) {
            console.log('Data:', data);
        }
    }
    
    // Dump all quiz settings to console for inspection
    function dumpQuizSettings() {
        debugLog('QUIZ SETTINGS DUMP', null, 'info');
        if (!window.quizExtensionsSettings) {
            debugLog('quizExtensionsSettings not found!', null, 'error');
            return;
        }
        
        debugLog('Current Quiz ID:', window.quizExtensionsSettings.current_quiz_id);
        debugLog('All Quiz Options:', window.quizExtensionsSettings.quiz_options);
        
        // Check if Quiz ID in document ID/class
        const quizElement = document.querySelector('.wpProQuiz_content');
        if (quizElement) {
            const dataId = quizElement.id?.match(/wpProQuiz_(\d+)/);
            if (dataId && dataId[1]) {
                debugLog(`Quiz ID from DOM: ${dataId[1]}`, null, 'info');
            }
        }
        
        // Find all other quiz-related attributes
        const quizMetaElement = document.querySelector('[data-quiz-meta]');
        if (quizMetaElement) {
            try {
                const quizMeta = JSON.parse(quizMetaElement.getAttribute('data-quiz-meta'));
                debugLog('Quiz Metadata from DOM:', quizMeta);
            } catch (e) {
                debugLog('Failed to parse quiz metadata', e, 'error');
            }
        }
    }
    
    // Determine current question ID
    function getCurrentQuestionId() {
        // Try to find current question
        const visibleQuestion = jQuery('.wpProQuiz_listItem:visible');
        if (!visibleQuestion.length) {
            debugLog('No visible question found', null, 'warn');
            return null;
        }
        
        // Try different ways to get question ID
        const questionId = visibleQuestion.data('question-id') || 
                          visibleQuestion.attr('data-question-meta') ? 
                          JSON.parse(visibleQuestion.attr('data-question-meta'))?.question_post_id : null;
                          
        return questionId;
    }
    
    // Function to check if the current quiz is in force hint mode
    function checkForceHintMode() {
        // Initial quiz settings check
        dumpQuizSettings();
        
        // Method 1: Settings object
        state.quizId = window.quizExtensionsSettings?.current_quiz_id;
        if (!state.quizId) {
            debugLog('No current quiz ID found - unable to determine mode', null, 'error');
            state.forceMode = false;
            return false;
        }
        
        // Get full settings for current quiz
        const quizSettings = window.quizExtensionsSettings?.quiz_options?.[state.quizId];
        if (!quizSettings) {
            debugLog(`No settings found for quiz ID ${state.quizId}`, null, 'warn');
            state.forceMode = false;
            return false;
        }
        
        // Check force hint mode
        state.forceMode = quizSettings.force_hint_mode === '1';
        
        // Log detailed info about this quiz
        debugLog(`QUIZ MODE DETAILS - ID: ${state.quizId}`, {
            'Force Hint Mode': state.forceMode ? 'ENABLED' : 'DISABLED',
            'Require Correct': quizSettings.require_correct === '1' ? 'Yes' : 'No',
            'Show Hint': quizSettings.show_hint === '1' ? 'Yes' : 'No',
            'Auto Show Hint': quizSettings.auto_show_hint === '1' ? 'Yes' : 'No',
            'Rich Sidebar': quizSettings.rich_sidebar === '1' ? 'Yes' : 'No'
        }, state.forceMode ? 'warn' : 'success');
        
        // Check for any other quiz indicators in the DOM
        try {
            const forceHintAttrib = document.querySelector('[data-force-hint-mode]');
            if (forceHintAttrib) {
                debugLog(`DOM data-force-hint-mode attribute: ${forceHintAttrib.getAttribute('data-force-hint-mode')}`);
            }
        } catch (e) {}
        
        return state.forceMode;
    }
    
    // Monitor hint button clicks to track hint state
    function setupHintTracking() {
        debugLog('Setting up hint tracking');
        
        // Track hint button clicks
        jQuery(document).on('click', '.wpProQuiz_TipButton', function() {
            const questionId = getCurrentQuestionId();
            if (!questionId) return;
            
            // Mark this question as having viewed the hint
            state.hintViewed[questionId] = true;
            debugLog(`Hint viewed for question ${questionId}`, state.hintViewed, 'info');
            
            // Always unlock selections after viewing a hint, even in force mode
            // This allows users to change their answer based on the hint
            unlockSelections();
        });
        
        // Handle hint container becoming visible (sometimes triggered programmatically)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList.contains('wpProQuiz_tipp') && 
                    window.getComputedStyle(mutation.target).display !== 'none') {
                    
                    const questionId = getCurrentQuestionId();
                    if (!questionId) return;
                    
                    // Mark hint as viewed
                    state.hintViewed[questionId] = true;
                    debugLog(`Hint container shown for question ${questionId}`, null, 'info');
                    
                    // Always re-enable selection after hint is viewed
                    // This is important even in force hint mode
                    unlockSelections();
                }
            });
        });
        
        // Observe hint containers
        document.querySelectorAll('.wpProQuiz_tipp').forEach(function(tippElem) {
            observer.observe(tippElem, { attributes: true, attributeFilter: ['style'] });
        });
    }
    
    // Helper function to unlock selections for the current question
    function unlockSelections() {
        const questionList = jQuery('.wpProQuiz_questionList:visible');
        if (!questionList.length) return;
        
        debugLog('Unlocking selections for current question', null, 'success');
        
        // Remove locked status
        questionList.removeAttr('data-selection-made');
        
        // Enable all inputs and remove locked styling
        questionList.find('.wpProQuiz_questionListItem').each(function() {
            jQuery(this).removeClass('answer-locked');
            jQuery(this).removeClass('user-selected');
            jQuery(this).find('input').prop('disabled', false);
        });
        
        // Update state tracking
        const questionId = getCurrentQuestionId();
        if (questionId) {
            state.selectionLocked[questionId] = false;
            
            // Dispatch a custom event that strict-next-button.js can listen for
            const unlockEvent = new CustomEvent('lilac_selection_unlocked', {
                detail: { questionId: questionId }
            });
            document.dispatchEvent(unlockEvent);
        }
    }
    
    // Check if we should enable the locking feature
    const isForceMode = checkForceHintMode();
    
    // Setup hint tracking regardless of force mode
    setupHintTracking();
    
    // If not in force mode, don't set up answer locking
    if (!isForceMode) {
        debugLog('This quiz is NOT in force hint mode - selection lock DISABLED', null, 'success');
        return;
    }
    
    debugLog('This quiz IS in force hint mode - selection lock ENABLED', null, 'warn');
    
    // Handle clicks on answer items ONLY FOR FORCE HINT MODE QUIZZES
    jQuery(document).on('click', '.wpProQuiz_questionListItem', function(e) {
        // Don't interfere with check button clicks
        if (e.target.classList.contains('wpProQuiz_QuestionButton')) {
            return;
        }
        
        // Always verify current state on each click
        const currentQuestionId = getCurrentQuestionId();
        const stillInForceMode = window.quizExtensionsSettings?.quiz_options?.[state.quizId]?.force_hint_mode === '1';
        
        debugLog(`Answer clicked: Question ID ${currentQuestionId}, Force Mode: ${stillInForceMode}`, null, 'info');
        
        // Exit if we're not in force mode anymore
        if (!stillInForceMode) {
            debugLog('Quiz is no longer in force mode, allowing selection', null, 'success');
            return;
        }
        
        // CRITICAL FIX: Check if hint has been viewed before allowing answer lock
        const hasViewedHint = state.hintViewed[currentQuestionId] === true;
        
        // If in force hint mode and hint not viewed, don't lock selections
        if (!hasViewedHint) {
            debugLog(`Hint not viewed for question ${currentQuestionId}, allowing selection changes`, null, 'warn');
            // Add styling to clicked answer but don't lock anything
            const clickedAnswer = jQuery(this);
            // Remove user-selected class from all answers and add it only to the clicked one
            clickedAnswer.closest('.wpProQuiz_questionList').find('.wpProQuiz_questionListItem').removeClass('user-selected');
            clickedAnswer.addClass('user-selected');
            return;
        }
        
        const clickedAnswer = jQuery(this);
        const questionList = clickedAnswer.closest('.wpProQuiz_questionList');

        if (questionList && questionList.length) {
            // Mark this question as having a selection made
            questionList.attr('data-selection-made', 'true');

            // Add specific styling to the clicked item
            clickedAnswer.addClass('user-selected');

            // After a short delay (to allow LearnDash to process the click)
            setTimeout(function() {
                // Final check before locking - ensure hint is still viewed
                if (questionList.attr('data-selection-made') === 'true' && state.hintViewed[currentQuestionId] === true) {
                    // Get all answer items in this question
                    const allAnswers = questionList.find('.wpProQuiz_questionListItem');

                    // Disable all inputs in non-selected answers
                    allAnswers.each(function() {
                        if (!jQuery(this).hasClass('user-selected')) {
                            // Find inputs in this answer and disable them
                            jQuery(this).find('input').prop('disabled', true);

                            // Add styling to show it's disabled
                            jQuery(this).addClass('answer-locked');
                        }
                    });

                    // Update state tracking
                    if (currentQuestionId) {
                        state.selectionLocked[currentQuestionId] = true;
                    }
                    
                    debugLog(`Selection locked for question ${currentQuestionId}`, state.selectionLocked, 'warn');
                }
            }, 100);
        }
    });

    // Add CSS styles to show locked answers (only applies when selections are locked)
    jQuery('<style>\
        .wpProQuiz_questionListItem.answer-locked {\
            opacity: 0.6;\
            pointer-events: none;\
            cursor: not-allowed;\
        }\
        .wpProQuiz_questionListItem.user-selected {\
            border: 2px solid #4CAF50;\
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);\
            background-color: rgba(76, 175, 80, 0.1);\
        }\
    </style>').appendTo('head');
    
    // Add test control buttons (ADMIN ONLY)
    if (document.body.classList.contains('admin-bar')) {
        const controlPanel = jQuery('<div id="psloop-controls" style="position: fixed; top: 40px; right: 20px; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 9999; width: 200px;">' +
            '<h4>Selection Lock Controls</h4>' +
            '<button id="psloop-toggle-force">Toggle Force Mode</button><br><br>' +
            '<button id="psloop-unlock">Unlock Current Question</button><br><br>' +
            '<button id="psloop-info">Quiz State Info</button>' +
            '</div>');
        
        jQuery('body').append(controlPanel);
        
        // Add handler for the unlock button
        jQuery('#psloop-unlock').on('click', function() {
            unlockSelections();
        });
        
        // Add handler for the quiz info button
        jQuery('#psloop-info').on('click', function() {
            dumpQuizSettings();
            alert(`Current Quiz ID: ${state.quizId}\nForce Mode: ${state.forceMode ? 'ON' : 'OFF'}\nCurrent Question: ${getCurrentQuestionId()}`); 
        });
        
        // Add handler for force mode toggle (temporary override)
        jQuery('#psloop-toggle-force').on('click', function() {
            state.forceMode = !state.forceMode;
            alert(`Force Mode Override: ${state.forceMode ? 'ENABLED' : 'DISABLED'}`); 
            if (!state.forceMode) {
                unlockSelections();
            }
        });
    }
    
    // Set up monitoring for question changes
    const questionObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.classList.contains('wpProQuiz_listItem') && 
                window.getComputedStyle(mutation.target).display !== 'none') {
                
                const questionId = getCurrentQuestionId();
                if (!questionId) return;
                
                debugLog(`Question changed to: ${questionId}`, null, 'info');
            }
        });
    });
    
    // Observe quiz content for question changes
    const quizContent = document.querySelector('.wpProQuiz_content');
    if (quizContent) {
        questionObserver.observe(quizContent, { 
            childList: true, 
            subtree: true,
            attributes: true,
            attributeFilter: ['style']
        });
    }
    
    debugLog('Initialization complete', null, 'success');
}
