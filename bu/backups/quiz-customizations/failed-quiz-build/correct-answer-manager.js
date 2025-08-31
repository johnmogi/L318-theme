/**
 * Correct Answer Manager for LearnDash Quizzes
 *
 * This script provides precise control over quiz answers and forces correct answers
 * to enable the Next button in non-Force Hint Mode and Force Hint Mode quizzes.
 * 
 * @since 1.0.0
 */

(function() {
    // Debug logging with prefix
    const DEBUG_MODE = true;
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log('Answer Manager:', label, ...args);
        }
    }

    // State tracking
    const hintViewed = {};
    const questionCorrect = {};
    let quizSettings = null;
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', initialize);

    /**
     * Initialize the manager
     */
    function initialize() {
        debug('Initializing Answer Manager');
        
        // Determine settings for current quiz
        quizSettings = getQuizSettings();
        debug('Quiz settings:', quizSettings);
        
        if (!quizSettings) {
            debug('No quiz settings found, applying default settings');
            quizSettings = {
                forceHintMode: false,
                requireCorrect: true,
                showHint: true,
                autoShowHint: false
            };
        }
        
        // Setup our event listeners
        setupEventListeners();
        
        // Initial setup for any visible question
        setTimeout(runInitialSetup, 300);
        
        // Attach observer to watch for quiz UI changes
        attachQuizObserver();
    }
    
    /**
     * Get quiz settings from global quizExtensionsSettings
     */
    function getQuizSettings() {
        debug('Getting quiz settings');
        
        if (!window.quizExtensionsSettings) {
            debug('quizExtensionsSettings not found');
            return null;
        }
        
        // Extract the quiz data
        const quizData = getQuizData();
        debug('Quiz data:', quizData);
        
        if (!quizData.quizId) {
            debug('No quiz ID could be determined');
            return null;
        }
        
        // Get settings from options
        const quizOptions = window.quizExtensionsSettings.quiz_options || {};
        const rawSettings = quizOptions[quizData.quizId] || {};
        debug('Raw quiz settings for ID ' + quizData.quizId + ':', rawSettings);
        
        // Normalize settings to handle different possible formats
        return {
            // Whether hints are required to be viewed before proceeding
            forceHintMode: 
                rawSettings.force_hint_mode === 'Yes' || 
                rawSettings.force_hint_mode === '1' || 
                rawSettings['Force Hint Mode'] === 'ENABLED',
                
            // Whether correct answers are required to proceed
            requireCorrect: 
                rawSettings.require_correct === 'Yes' || 
                rawSettings.require_correct === '1' || 
                rawSettings['Require Correct'] === 'Yes',
                
            // Whether hints are available
            showHint: 
                rawSettings.show_hint === 'Yes' || 
                rawSettings.show_hint === '1' || 
                rawSettings['Show Hint'] === 'Yes',
                
            // Whether hints should be automatically shown
            autoShowHint: 
                rawSettings.auto_show_hint === 'Yes' || 
                rawSettings.auto_show_hint === '1' || 
                rawSettings['Auto Show Hint'] === 'Yes',
                
            // Raw settings for debugging
            raw: rawSettings
        };
    }
    
    /**
     * Get quiz ID through multiple methods
     */
    function getQuizData() {
        const quizData = {
            quizId: null,
            quizProId: null,
            fromDOM: false,
            fromSettings: false
        };
        
        // Method 1: From global settings
        if (window.quizExtensionsSettings && window.quizExtensionsSettings.current_quiz_id) {
            quizData.quizId = window.quizExtensionsSettings.current_quiz_id;
            quizData.fromSettings = true;
            debug('Found quiz ID from settings:', quizData.quizId);
        }
        
        // Method 2: From the DOM data attributes
        const quizContent = document.querySelector('.wpProQuiz_content[data-quiz-meta]');
        if (quizContent) {
            try {
                const metaData = JSON.parse(quizContent.getAttribute('data-quiz-meta'));
                if (metaData && metaData.quiz_post_id) {
                    quizData.quizId = metaData.quiz_post_id;
                    quizData.quizProId = metaData.quiz_pro_id;
                    quizData.fromDOM = true;
                    debug('Found quiz IDs from DOM data-quiz-meta:', metaData);
                }
            } catch (e) {
                debug('Error parsing quiz meta data:', e);
            }
        }
        
        // Method 3: From hidden form fields
        if (!quizData.quizId) {
            const quizPostIdField = document.querySelector('input[name="quiz_post_id"]');
            if (quizPostIdField && quizPostIdField.value) {
                quizData.quizId = quizPostIdField.value;
                quizData.fromDOM = true;
                debug('Found quiz post ID from hidden field:', quizData.quizId);
            }
        }
        
        return quizData;
    }

    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners() {
        debug('Setting up event listeners');
        
        // Listen for hint button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('wpProQuiz_TipButton') || 
                e.target.closest('.wpProQuiz_TipButton')) {
                const button = e.target.classList.contains('wpProQuiz_TipButton') ? 
                    e.target : e.target.closest('.wpProQuiz_TipButton');
                handleHintButtonClick(button);
            }
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if ((e.target.name === 'check' && e.target.classList.contains('wpProQuiz_QuestionButton')) ||
                e.target.closest('input[name="check"].wpProQuiz_QuestionButton')) {
                const button = e.target.name === 'check' ? e.target : e.target.closest('input[name="check"]');
                handleCheckButtonClick(button);
            }
        });
        
        // Listen for next button clicks (for analytics)
        document.addEventListener('click', function(e) {
            if ((e.target.name === 'next' && e.target.classList.contains('wpProQuiz_QuestionButton')) ||
                e.target.closest('input[name="next"].wpProQuiz_QuestionButton')) {
                const button = e.target.name === 'next' ? e.target : e.target.closest('input[name="next"]');
                debug('Next button clicked');
            }
        });
        
        // Look for close hint button 
        document.addEventListener('click', function(e) {
            if (e.target.id === 'close-hint' || 
               (e.target.tagName === 'BUTTON' && e.target.textContent.includes('סגור רמז'))) {
                const button = e.target;
                const questionItem = button.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    const questionId = questionItem.getAttribute('data-post-id');
                    if (questionId) {
                        // Mark this question's hint as viewed
                        hintViewed[questionId] = true;
                        debug('Hint viewed via close button for question', questionId);
                        updateNextButton(questionItem);
                    }
                }
            }
        });
    }
    
    /**
     * Run initial setup for the quiz
     */
    function runInitialSetup() {
        debug('Running initial setup');
        
        // Find all question items
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        questionItems.forEach(function(questionItem) {
            const questionId = questionItem.getAttribute('data-post-id');
            if (!questionId) return;
            
            // Check if this question's hint is already shown (meaning it's been viewed)
            const hintBox = questionItem.querySelector('.wpProQuiz_tipp');
            if (hintBox && window.getComputedStyle(hintBox).display !== 'none') {
                hintViewed[questionId] = true;
                debug('Hint already viewed for question', questionId);
            }
            
            // Check if this question is already marked as answered correctly
            const isCorrect = isAnswerCorrect(questionItem);
            questionCorrect[questionId] = isCorrect;
            
            // Force correct answer if needed
            if (quizSettings && quizSettings.forceHintMode && !isCorrect) {
                forceCorrectAnswer(questionItem);
            }
            
            // Update the next button state
            updateNextButton(questionItem);
            
            // Prevent changing answers after checking if in force hint mode
            lockSelectedAnswers(questionItem);
        });
        
        // If in auto-show hint mode, show hints for all questions
        if (quizSettings && quizSettings.autoShowHint) {
            questionItems.forEach(function(questionItem) {
                const tipButton = questionItem.querySelector('.wpProQuiz_TipButton');
                if (tipButton) {
                    debug('Auto-showing hint for question');
                    tipButton.click();
                }
            });
        }
    }
    
    /**
     * Handle hint button clicks
     */
    function handleHintButtonClick(button) {
        const questionItem = button.closest('.wpProQuiz_listItem');
        if (!questionItem) return;
        
        const questionId = questionItem.getAttribute('data-post-id');
        if (!questionId) return;
        
        // Mark this question's hint as viewed
        hintViewed[questionId] = true;
        debug('Hint viewed for question', questionId);
        
        // Update the next button
        updateNextButton(questionItem);
    }
    
    /**
     * Handle check button clicks
     */
    function handleCheckButtonClick(button) {
        // Allow time for LearnDash to update the UI
        setTimeout(function() {
            const questionItem = button.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            const questionId = questionItem.getAttribute('data-post-id');
            if (!questionId) return;
            
            // Check if answer is correct
            const isCorrect = isAnswerCorrect(questionItem);
            questionCorrect[questionId] = isCorrect;
            debug('Answer correctness for question ' + questionId + ':', isCorrect);
            
            // Force correct answer if in force hint mode
            if (quizSettings && quizSettings.forceHintMode && !isCorrect) {
                forceCorrectAnswer(questionItem);
                questionCorrect[questionId] = true;
            }
            
            // Update the next button
            updateNextButton(questionItem);
            
            // Lock answers from being changed
            lockSelectedAnswers(questionItem);
        }, 300);
    }
    
    /**
     * Check if the answer is correct
     */
    function isAnswerCorrect(questionItem) {
        // Method 1: Check for LearnDash's correct feedback box
        const correctFeedback = questionItem.querySelector('.wpProQuiz_correct');
        if (correctFeedback && window.getComputedStyle(correctFeedback).display !== 'none') {
            debug('LearnDash shows correct feedback');
            return true;
        }
        
        // Method 2: Check for correct answer class
        const answerCorrect = questionItem.querySelector('.wpProQuiz_answerCorrect, .wpProQuiz_questionListItem.wpProQuiz_answerCorrect');
        if (answerCorrect) {
            debug('Found answer with correct class');
            return true;
        }
        
        // Method 3: Check if the question list has a correct class
        const questionList = questionItem.querySelector('.wpProQuiz_questionList.correct');
        if (questionList) {
            debug('Question list has correct class');
            return true;
        }
        
        debug('Answer is not correct');
        return false;
    }
    
    /**
     * Force the correct answer to be selected
     */
    function forceCorrectAnswer(questionItem) {
        debug('Forcing correct answer');
        
        // Find all possible answers
        const answerItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        if (!answerItems.length) return;
        
        // Look for the correct answer
        let correctAnswerFound = false;
        
        // First try to find by 'wpProQuiz_answerCorrect' class
        answerItems.forEach(function(item) {
            if (item.classList.contains('wpProQuiz_answerCorrect')) {
                // Select this answer
                const input = item.querySelector('input.wpProQuiz_questionInput');
                if (input) {
                    input.checked = true;
                    item.classList.add('is-selected');
                    correctAnswerFound = true;
                    debug('Selected correct answer via class');
                }
            }
        });
        
        // If we couldn't find a correct answer, force the first one
        if (!correctAnswerFound && answerItems.length > 0) {
            const firstItem = answerItems[0];
            const input = firstItem.querySelector('input.wpProQuiz_questionInput');
            if (input) {
                input.checked = true;
                firstItem.classList.add('is-selected');
                firstItem.classList.add('wpProQuiz_answerCorrect');
                debug('Forced first answer to be correct');
            }
        }
        
        // If there's a check button, click it
        const checkButton = questionItem.querySelector('input[name="check"]');
        if (checkButton) {
            // First, make sure the correct feedback box exists or create it
            let correctFeedback = questionItem.querySelector('.wpProQuiz_correct');
            if (!correctFeedback) {
                const responseDiv = questionItem.querySelector('.wpProQuiz_response');
                if (responseDiv) {
                    correctFeedback = document.createElement('div');
                    correctFeedback.className = 'wpProQuiz_correct';
                    correctFeedback.innerHTML = '<span>נכון</span><div class="wpProQuiz_AnswerMessage"></div>';
                    responseDiv.appendChild(correctFeedback);
                }
            }
            
            // Ensure the feedback box is visible
            if (correctFeedback) {
                correctFeedback.style.display = 'block';
            }
            
            // Simulate a check button click
            // checkButton.click();
        }
    }
    
    /**
     * Lock answers from being changed
     */
    function lockSelectedAnswers(questionItem) {
        // Only lock if in force hint mode
        if (!quizSettings || !quizSettings.forceHintMode) return;
        
        const answerItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        answerItems.forEach(function(item) {
            // Add a class to mark this answer as locked
            item.classList.add('answer-locked');
            
            // Disable the input
            const input = item.querySelector('input.wpProQuiz_questionInput');
            if (input) {
                input.disabled = true;
            }
        });
        
        // Disable the check button as well
        const checkButton = questionItem.querySelector('input[name="check"]');
        if (checkButton) {
            checkButton.disabled = true;
        }
    }
    
    /**
     * Update the next button based on settings and state
     */
    function updateNextButton(questionItem) {
        const nextButton = questionItem.querySelector('.wpProQuiz_QuestionButton[name="next"]');
        if (!nextButton) return;
        
        const questionId = questionItem.getAttribute('data-post-id');
        if (!questionId) return;
        
        const hasViewedHint = hintViewed[questionId] || false;
        const isCorrect = questionCorrect[questionId] || false;
        
        debug('Next button update for question ' + questionId, {
            hasViewedHint, 
            isCorrect, 
            forceHintMode: quizSettings?.forceHintMode,
            requireCorrect: quizSettings?.requireCorrect
        });
        
        if (quizSettings.forceHintMode && quizSettings.requireCorrect) {
            // Need both correct answer and hint viewed
            if (isCorrect && hasViewedHint) {
                // Both conditions met, show next button
                showNextButton(nextButton);
                debug('Showing next button - correct answer and hint viewed');
            } else {
                // Conditions not met, hide next button
                hideNextButton(nextButton);
                debug('Hiding next button - conditions not met');
            }
        } else if (quizSettings.forceHintMode) {
            // Only need hint viewed
            if (hasViewedHint) {
                showNextButton(nextButton);
                debug('Showing next button - hint viewed');
            } else {
                hideNextButton(nextButton);
                debug('Hiding next button - hint not viewed');
            }
        } else if (quizSettings.requireCorrect) {
            // Only need correct answer
            if (isCorrect) {
                showNextButton(nextButton);
                debug('Showing next button - correct answer');
            } else {
                hideNextButton(nextButton);
                debug('Hiding next button - incorrect answer');
            }
        } else {
            // No restrictions, always show next button
            showNextButton(nextButton);
            debug('Showing next button - no restrictions');
        }
    }
    
    /**
     * Show the next button
     */
    function showNextButton(button) {
        button.style.display = '';
        button.disabled = false;
        button.classList.remove('disabled');
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    }
    
    /**
     * Hide the next button
     */
    function hideNextButton(button) {
        button.style.display = 'none';
        button.disabled = true;
    }
    
    /**
     * Attach observer to watch for quiz UI changes
     */
    function attachQuizObserver() {
        // Create a mutation observer to detect when questions change
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'style' && 
                    mutation.target.classList.contains('wpProQuiz_listItem')) {
                    // A question's visibility changed, check if it's now visible
                    const questionItem = mutation.target;
                    if (questionItem.style.display !== 'none') {
                        debug('Question became visible, updating');
                        setTimeout(function() {
                            const questionId = questionItem.getAttribute('data-post-id');
                            if (!questionId) return;
                            
                            // Force correct answer if in force hint mode
                            if (quizSettings && quizSettings.forceHintMode && 
                                !questionCorrect[questionId]) {
                                forceCorrectAnswer(questionItem);
                                questionCorrect[questionId] = true;
                            }
                            
                            // Update the next button
                            updateNextButton(questionItem);
                        }, 300);
                    }
                }
            });
        });
        
        // Start observing the quiz container
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        if (quizContainer) {
            observer.observe(quizContainer, { 
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style']
            });
            debug('Quiz observer attached');
        }
    }
})();
