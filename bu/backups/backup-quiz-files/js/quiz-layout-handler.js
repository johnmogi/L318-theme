/**
 * Quiz Layout Handler
 * Handles the layout and functionality of quizzes
 * Version 1.1.0 - Improved Force Hint Mode implementation
 */

(function($) {
    'use strict';
    
    // Main initialization function
    function initialize() {
        console.log('Quiz Layout Handler initialized');
        
        // Add a very visible indicator that the script is loaded
        $('body').append('<div id="quiz-script-loaded-indicator" style="position:fixed;top:0;left:0;background:red;color:white;padding:10px;z-index:999999;font-size:14px;">Quiz Layout Handler Loaded - Click to Force Hint Mode</div>');
        $('#quiz-script-loaded-indicator').click(function() {
            // Manual override for testing - force the hint mode
            $('body').addClass('learndash-force-hint');
            $('.wpProQuiz_QuestionButton[name="next"]').hide();
            setupHintClickHandlers();
            setupAnswerObserver();
            alert('Force Hint Mode manually activated!\n\nNext buttons should now be hidden until you:\n1) View the hint\n2) Select the correct answer');
        });
        
        setTimeout(function() {
            $('#quiz-script-loaded-indicator').css('background', 'blue').text('Click to Force Hint Mode');
        }, 3000);
        
        // Apply layout adjustments
        setupQuizLayout();
        
        // Apply force hint mode if enabled and ONLY to force hint mode quizzes
        setupForceHintMode();
        
        // Handle top bar disabling if needed - this works independently
        handleTopBarDisabling();
        
        // Handle hint option disabling if needed
        handleHintDisabling();
        
        // Run initial check in 500ms to make sure all buttons are properly set up
        setTimeout(function() {
            // Only in force hint mode: ensure Next buttons are hidden initially
            if ($('body').hasClass('learndash-force-hint')) {
                console.log('⚠️ FORCE HINT MODE: Running initial check to hide all Next buttons');
                $('.wpProQuiz_listItem').each(function() {
                    const $question = $(this);
                    const $nextButton = $question.find('.wpProQuiz_QuestionButton[name="next"]');
                    // Hide Next button initially
                    if ($nextButton.length) {
                        $nextButton.hide();
                        console.log('Force hint mode: Hiding Next button for question', $question.find('.wpProQuiz_questionList').data('question_id'));
                    }
                });
            }
        }, 500);
        
        // Set up debug panel
        setupDebugPanel();
    }
    
    // Set up the quiz layout with sidebar
    function setupQuizLayout() {
        // Only proceed if we're on a quiz page
        if (!$('.wpProQuiz_content').length) return;
        
        // Check if rich sidebar is enabled via meta tag
        const richSidebarEnabled = $('meta[name="lilac-quiz-rich-sidebar"]').attr('content') === 'true';
        
        if (richSidebarEnabled) {
            console.log('Rich sidebar enabled, setting up layout');
            
            // Wrap quiz content in layout container
            const $quizContent = $('.wpProQuiz_content');
            if (!$quizContent.parent().hasClass('quiz-with-sidebar-container')) {
                $quizContent.wrap('<div class="quiz-with-sidebar-container"></div>');
                $quizContent.wrap('<div class="quiz-main-content"></div>');
                
                // Add sidebar container
                const $container = $('.quiz-with-sidebar-container');
                $container.append('<div class="quiz-sidebar-area"></div>');
                
                // Add body class
                $('body').addClass('quiz-with-sidebar');
                
                // Load sidebar content if available
                loadSidebarContent();
            }
        }
    }
    
    // Load sidebar content for the quiz
    function loadSidebarContent() {
        const quizId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
        const $sidebar = $('.quiz-sidebar-area');
        
        if (!quizId || !$sidebar.length) return;
        
        // Add loading indicator
        $sidebar.html('<div class="sidebar-loading">Loading sidebar content...</div>');
        
        // Look for existing media items in the page
        const mediaItems = [];
        $('.wpProQuiz_question').each(function() {
            const $question = $(this);
            const questionId = $question.find('.wpProQuiz_questionList').data('question_id') || '';
            
            if (questionId) {
                // Check for associated media
                const $hint = $question.find('.wpProQuiz_tipp');
                
                if ($hint.length) {
                    mediaItems.push({
                        questionId: questionId,
                        hint: $hint.html()
                    });
                }
            }
        });
        
        // Build sidebar content
        if (mediaItems.length) {
            let sidebarHtml = '<h3>Quiz Media</h3>';
            
            mediaItems.forEach(item => {
                sidebarHtml += `
                <div class="quiz-sidebar-item" data-question-id="${item.questionId}">
                    <div class="media-item-hint">${item.hint}</div>
                </div>`;
            });
            
            $sidebar.html(sidebarHtml);
        } else {
            $sidebar.html('<div class="no-media-message">No additional media available for this quiz.</div>');
        }
    }
    
    // Set up force hint mode
    function setupForceHintMode() {
        // Track force hint mode state globally for toggles
        window.lilacForceHintActive = false;
        
        // Method 1: Check meta tag
        if ($('meta[name="lilac-quiz-force-hint"]').attr('content') === 'true') {
            window.lilacForceHintActive = true;
            console.log('Force hint mode detected via meta tag');
        }
        
        // Method 2: Check data attribute
        else if ($('.wpProQuiz_quiz').attr('data-force-hint-mode') === 'true') {
            window.lilacForceHintActive = true;
            console.log('Force hint mode detected via data attribute');
        }
        
        // Method 3: Check URL parameter for testing
        else if (window.location.search.indexOf('force-hint=1') !== -1) {
            window.lilacForceHintActive = true;
            console.log('Force hint mode detected via URL parameter');
        }
        
        // Add a compact control button for admins
        $('body').append(
            `<div id="force-hint-toggle" style="position:fixed; bottom:10px; right:10px; background:rgba(52, 152, 219, 0.8); 
            color:white; padding:5px; border-radius:3px; cursor:pointer; z-index:999999; font-size:12px;">
            Force Hint: ${window.lilacForceHintActive ? 'On' : 'Off'}</div>`
        );
        
        // Allow toggling force hint mode for testing
        $('#force-hint-toggle').click(function() {
            window.lilacForceHintActive = !window.lilacForceHintActive;
            $(this).text(`Force Hint: ${window.lilacForceHintActive ? 'On' : 'Off'}`);
            
            if (window.lilacForceHintActive) {
                // Activate
                $('body').addClass('learndash-force-hint');
                $(this).css('background', 'rgba(231, 76, 60, 0.8)');
                
                // Immediately apply force hint rules
                console.log('⚠️ Force Hint Mode activated via toggle');
                $('.wpProQuiz_listItem').each(function() {
                    const $question = $(this);
                    const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
                    if (questionId) {
                        const quizId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
                        const storageKey = `lilac_viewed_hints_${quizId}`;
                        const viewedHints = new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
                        checkButtonVisibility($question, questionId, viewedHints);
                    }
                });
                
                // Setup observers
                setupHintClickHandlers();
                setupAnswerObserver();
            } else {
                // Deactivate
                $('body').removeClass('learndash-force-hint');
                $(this).css('background', 'rgba(52, 152, 219, 0.8)');
                // Make all Next buttons visible
                $('.wpProQuiz_QuestionButton[name="next"]').show();
                console.log('ℹ️ Force Hint Mode deactivated via toggle');
            }
            
            // Alert to confirm the change
            alert(`Force Hint Mode: ${window.lilacForceHintActive ? 'ACTIVATED' : 'DEACTIVATED'}\n\n${window.lilacForceHintActive ? 'Next buttons will be hidden until hint is viewed and answer is correct' : 'Normal quiz behavior restored'}`);
        });
        
        // Apply initial settings if enabled
        if (window.lilacForceHintActive) {
            console.log('Force hint mode enabled');
            
            // Add body class
            $('body').addClass('learndash-force-hint');
            
            // Set control button style
            $('#force-hint-toggle').css('background', 'rgba(231, 76, 60, 0.8)');
            
            // Initially update all Next buttons
            updateNextButtonVisibility();
            
            // Set up observers for hint buttons and answers
            setupHintClickHandlers();
            setupAnswerObserver();
        }
    }
    
    // Update visibility of Next buttons based on current state
    function updateNextButtonVisibility() {
        if (!window.lilacForceHintActive) {
            // Not in force hint mode - show all Next buttons
            $('.wpProQuiz_QuestionButton[name="next"]').show();
            return;
        }
        
        // Process each question separately
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
            const $nextButton = $question.find('.wpProQuiz_QuestionButton[name="next"]');
            
            // Check if answer is already marked correct
            if (isAnswerCorrect($question)) {
                // Check if hint was viewed
                const quizId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
                const storageKey = `lilac_viewed_hints_${quizId}`;
                const viewedHints = new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
                const hasViewedHint = viewedHints.has(questionId.toString());
                
                if (hasViewedHint) {
                    // Both correct and hint viewed - show Next
                    $nextButton.show();
                } else {
                    // Need to view hint - hide Next
                    $nextButton.hide();
                }
            } else {
                // Not correct - hide Next
                $nextButton.hide();
            }
        });
    }
    
    // Handle disabling of top bar if needed
    function handleTopBarDisabling() {
        // Check if top bar should be disabled
        const disableTopBar = $('meta[name="lilac-quiz-disable-topbar"]').attr('content') === 'true';
        
        if (disableTopBar) {
            console.log('Disabling quiz top bar navigation');
            $('body').addClass('learndash-disable-topbar');
        }
    }
    
    // Handle disabling of hint options if needed
    function handleHintDisabling() {
        // Check if hints should be disabled via meta tag
        const disableHints = $('meta[name="lilac-quiz-disable-hints"]').attr('content') === 'true';
        
        // Also check via data attribute as an alternative method
        const dataDisableHints = $('.wpProQuiz_quiz').attr('data-disable-hints') === 'true';
        
        if (disableHints || dataDisableHints) {
            console.log('Disabling quiz hint buttons');
            $('body').addClass('learndash-disable-hints');
            
            // Add CSS to completely hide hint buttons
            const hintDisabledStyles = `
                /* Hide hint buttons in all quizzes */
                body.learndash-disable-hints .wpProQuiz_QuestionButton[name="tip"] {
                    display: none !important;
                }
                
                /* Style for the hints disabled notice */
                .hints-disabled-notice {
                    background: #f39c12;
                    color: white;
                    padding: 5px 10px;
                    text-align: center;
                    margin-bottom: 15px;
                    font-size: 14px;
                    border-radius: 3px;
                    font-weight: bold;
                }
                
                /* Remove any padding/margin/borders where hint buttons were */
                body.learndash-disable-hints .wpProQuiz_hint_btn {
                    display: none !important;
                }
                
                /* Remove hint area */
                body.learndash-disable-hints .wpProQuiz_tipp {
                    display: none !important;
                }
            `;
            
            // Add styles to the head
            $('<style id="lilac-hint-disabled-styles"></style>').text(hintDisabledStyles).appendTo('head');
            
            // Add special notice to debug panel if it exists
            if ($('#lilac-debug-panel').length) {
                $('#lilac-debug-panel').append('<div style="color:#e74c3c;">HINTS DISABLED for this quiz</div>');
            }
            
            // Insert a small indicator at the top of the quiz
            $('.wpProQuiz_content').prepend('<div class="hints-disabled-notice">הרמזים מנוטרלים במבחן זה</div>');
            
            // Make sure Force Hint Mode doesn't conflict with disabled hints
            if (window.lilacForceHintActive) {
                console.warn('Force Hint Mode is active but hints are disabled - this is a conflict!');
                $('#force-hint-toggle').css('background', '#e67e22').text('WARNING: Hints Disabled');
            }
        }
    }
    
    // Set up hint click handlers
    function setupHintClickHandlers() {
        // Track hint views with localStorage
        const quizId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
        const storageKey = `lilac_viewed_hints_${quizId}`;
        const viewedHints = new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
        
        // Handle hint button clicks
        $(document).on('click', '.wpProQuiz_QuestionButton[name="tip"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
            
            if (questionId) {
                // Mark hint as viewed
                viewedHints.add(questionId.toString());
                localStorage.setItem(storageKey, JSON.stringify([...viewedHints]));
                
                // Add visual indication
                $(this).addClass('hint-viewed');
                
                // Check if we should show the Next button
                checkButtonVisibility($question, questionId, viewedHints);
                
                // Highlight corresponding sidebar item
                $('.quiz-sidebar-item').removeClass('active');
                $(`.quiz-sidebar-item[data-question-id="${questionId}"]`).addClass('active');
            }
        });
    }
    
    // Set up observer for answer changes
    function setupAnswerObserver() {
        // Check button click handler
        $(document).on('click', '.wpProQuiz_QuestionButton[name="check"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
            
            // Wait for LearnDash to update the DOM
            setTimeout(() => {
                const quizId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
                const storageKey = `lilac_viewed_hints_${quizId}`;
                const viewedHints = new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
                checkButtonVisibility($question, questionId, viewedHints);
            }, 300);
        });
    }
    
    // Check if Next button should be visible
    function checkButtonVisibility($question, questionId, viewedHints) {
        // Check Force Hint Mode global state directly
        if (!window.lilacForceHintActive) {
            console.log('Regular quiz mode - not applying Force Hint behavior');
            $question.find('.wpProQuiz_QuestionButton[name="next"]').show();
            return; // Exit early for regular quizzes
        }
        
        const hasViewedHint = viewedHints.has(questionId.toString());
        const isCorrect = isAnswerCorrect($question);
        const $nextButton = $question.find('.wpProQuiz_QuestionButton[name="next"]');
        const $hintButton = $question.find('.wpProQuiz_QuestionButton[name="tip"]');
        
        console.log(`Force Hint Mode - Question ${questionId} status: correct=${isCorrect}, hint-viewed=${hasViewedHint}`);
        
        // Check for answer correctness first
        if (isCorrect) {
            // If answer is correct:
            if (hasViewedHint) {
                // Both correct and hint viewed - Show Next button
                $nextButton.show();
                $question.addClass('hint-question-completed');
                console.log(`Next button enabled for question ${questionId} - correct and hint viewed`);
            } else {
                // Correct but no hint viewed - hide Next button
                $nextButton.hide();
                if ($hintButton.length) {
                    $hintButton.addClass('highlight-hint');
                    
                    // Add hint indicator if it doesn't exist
                    if (!$question.find('.hint-arrow-indicator').length) {
                        const $arrowIndicator = $('<div class="hint-arrow-indicator">⬅️ צפה ברמז</div>');
                        $hintButton.parent().prepend($arrowIndicator);
                    }
                }
                console.log(`Next button hidden for question ${questionId} - correct but hint not viewed`);
            }
        } else {
            // INCORRECT ANSWER: Always hide Next button, show hint prompt
            $nextButton.hide();
            $question.removeClass('hint-question-completed');
            
            if ($hintButton.length) {
                $hintButton.addClass('highlight-hint');
                
                // Add hint indicator if it doesn't exist
                if (!$question.find('.hint-arrow-indicator').length) {
                    const $arrowIndicator = $('<div class="hint-arrow-indicator">⬅️ צפה ברמז</div>');
                    $hintButton.parent().prepend($arrowIndicator);
                }
            }
            console.log(`Next button hidden for question ${questionId} - answer is incorrect`);
        }
    }
    
    // Check if the answer is correct
    function isAnswerCorrect($question) {
        // Method 1: Check if correct message is visible
        const $correctMsg = $question.find('.wpProQuiz_correct');
        if ($correctMsg.length && $correctMsg.is(':visible')) {
            return true;
        }
        
        // Method 2: Check for correct answer marker
        const $correctAnswer = $question.find('.wpProQuiz_answerCorrect');
        if ($correctAnswer.length) {
            return true;
        }
        
        // Method 3: Check if there's no incorrect answer
        const $incorrectMsg = $question.find('.wpProQuiz_incorrect');
        const $incorrectAnswer = $question.find('.wpProQuiz_answerIncorrect');
        if (($incorrectMsg.length && $incorrectMsg.is(':visible')) || $incorrectAnswer.length) {
            return false;
        }
        
        // Method 4: Check for correct attribute
        const $questionList = $question.find('.wpProQuiz_questionList');
        if ($questionList.data('correct') === 1) {
            return true;
        }
        
        return false;
    }
    
    // Set up debug panel
    function setupDebugPanel() {
        // Check if debug panel is requested
        if (window.location.search.indexOf('quiz_debug=1') === -1) return;
        
        // Create debug panel
        const $debugPanel = $('<div id="quiz-debug-panel"></div>').css({
            position: 'fixed',
            bottom: '10px',
            right: '10px',
            width: '300px',
            maxHeight: '400px',
            overflowY: 'auto',
            background: 'rgba(0,0,0,0.8)',
            color: 'white',
            padding: '10px',
            borderRadius: '5px',
            zIndex: 999999,
            fontSize: '12px'
        });
        
        $('body').append($debugPanel);
        
        // Add quiz settings info
        const forceHintMode = $('meta[name="lilac-quiz-force-hint"]').attr('content') === 'true';
        const richSidebarEnabled = $('meta[name="lilac-quiz-rich-sidebar"]').attr('content') === 'true';
        const disableTopBar = $('meta[name="lilac-quiz-disable-topbar"]').attr('content') === 'true';
        
        $debugPanel.html(`
            <h3>Quiz Debug</h3>
            <div>Force Hint Mode: ${forceHintMode ? 'Enabled' : 'Disabled'}</div>
            <div>Rich Sidebar: ${richSidebarEnabled ? 'Enabled' : 'Disabled'}</div>
            <div>Top Bar Disabled: ${disableTopBar ? 'Yes' : 'No'}</div>
            <div>Next Buttons: <span id="next-btn-count">Checking...</span></div>
            <div>Hint Buttons: <span id="hint-btn-count">Checking...</span></div>
            <hr>
            <div id="debug-log"></div>
        `);
        
        // Update button counts
        setTimeout(() => {
            const nextBtnCount = $('.wpProQuiz_QuestionButton[name="next"]').length;
            const nextBtnVisible = $('.wpProQuiz_QuestionButton[name="next"]:visible').length;
            $('#next-btn-count').text(`${nextBtnVisible} visible of ${nextBtnCount} total`);
            
            const hintBtnCount = $('.wpProQuiz_QuestionButton[name="tip"]').length;
            $('#hint-btn-count').text(`${hintBtnCount} total`);
        }, 1000);
        
        // Override console.log for debug panel
        const originalLog = console.log;
        console.log = function(message) {
            originalLog.apply(console, arguments);
            
            if (typeof message === 'string' && message.includes('Quiz')) {
                $('#debug-log').prepend(`<div>${new Date().toLocaleTimeString()}: ${message}</div>`);
            }
        };
    }
    
    // Initialize on document ready
    $(document).ready(initialize);
    
    // Initialize when wpProQuiz is fully loaded
    $(document).on('wpProQuiz_loaded', initialize);
    
})(jQuery);
