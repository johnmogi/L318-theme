/**
 * Next Button Manager for LearnDash Quiz
 * 
 * This script handles the "Next" button functionality for both force hint mode and non-force hint mode quizzes.
 * It specifically focuses on:
 * 1. Detecting when answers are correct
 * 2. Managing hint state tracking
 * 3. Enabling/disabling the Next button according to quiz rules
 * 
 * @since 1.0.0
 */

(function() {
    // Debug logging with prefix
    const DEBUG_MODE = true;
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log('Next Button Manager:', label, ...args);
        }
    }

    // State tracking
    const hintViewed = {};
    const questionResults = {};

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        debug('Initializing Next Button Manager');
        
        // Get quiz settings
        const settings = getQuizSettings();
        if (!settings) {
            debug('No quiz settings found, exiting');
            return;
        }
        
        debug('Quiz settings loaded', settings);
        
        // Set up event listeners
        setupEventListeners(settings);
    }
    
    /**
     * Get quiz settings from the global quizExtensionsSettings
     * This function exactly matches the approach used in prevent-selection-loop.js
     */
    function getQuizSettings() {
        debug('Getting quiz settings');
        
        if (!window.quizExtensionsSettings) {
            debug('quizExtensionsSettings not found');
            return getDefaultSettings();
        }
        
        // Extract the quiz IDs using multiple methods
        const quizData = getQuizData();
        debug('Quiz data:', quizData);
        
        if (!quizData.quizId) {
            debug('No quiz ID could be determined');
            return getDefaultSettings();
        }
        
        // Get settings from options
        const quizOptions = window.quizExtensionsSettings.quiz_options || {};
        const settings = quizOptions[quizData.quizId] || {};
        debug('Raw quiz settings for ID ' + quizData.quizId + ':', settings);
        
        // Normalize settings
        return normalizeSettings(settings);
    }
    
    /**
     * Get all quiz IDs and metadata through multiple methods
     */
    function getQuizData() {
        const quizData = {
            quizId: null, 
            quizProId: null,
            quizPostId: null,
            fromDom: false,
            fromSettings: false
        };
        
        // Method 1: From settings directly
        if (window.quizExtensionsSettings && window.quizExtensionsSettings.current_quiz_id) {
            quizData.quizId = window.quizExtensionsSettings.current_quiz_id;
            quizData.fromSettings = true;
            debug('Found quiz ID from settings:', quizData.quizId);
        }
        
        // Method 2: From DOM metadata
        const metaFields = document.querySelectorAll('.wpProQuiz_data');
        metaFields.forEach(metaField => {
            // Check quiz pro ID
            const quizProIdField = metaField.querySelector('input[name="quiz_pro_id"]');
            if (quizProIdField && quizProIdField.value) {
                quizData.quizProId = quizProIdField.value;
                debug('Found quiz pro ID from DOM:', quizData.quizProId);
            }
            
            // Check quiz post ID
            const quizPostIdField = metaField.querySelector('input[name="quiz_post_id"]');
            if (quizPostIdField && quizPostIdField.value) {
                quizData.quizPostId = quizPostIdField.value;
                quizData.quizId = quizData.quizPostId; // Use post ID as the main ID
                quizData.fromDom = true;
                debug('Found quiz post ID from DOM:', quizData.quizPostId);
            }
        });
        
        return quizData;
    }
    
    /**
     * Normalize settings to handle different formats
     */
    function normalizeSettings(settings) {
        // Check if we have Force Hint Mode in any format
        // The prevent-selection-loop.js uses '1' for these values
        const forceHintMode = 
            settings.force_hint_mode === 'Yes' || 
            settings.force_hint_mode === '1' || 
            settings['Force Hint Mode'] === 'ENABLED';
            
        // Check if we have Require Correct in any format
        const requireCorrect = 
            settings.require_correct === 'Yes' || 
            settings.require_correct === '1' || 
            settings['Require Correct'] === 'Yes';
            
        // Check if we have Show Hint in any format
        const showHint = 
            settings.show_hint === 'Yes' || 
            settings.show_hint === '1' || 
            settings['Show Hint'] === 'Yes';
            
        // Check if we have Auto Show Hint in any format
        const autoShowHint = 
            settings.auto_show_hint === 'Yes' || 
            settings.auto_show_hint === '1' || 
            settings['Auto Show Hint'] === 'Yes';
        
        debug('Normalized settings:', {
            forceHintMode,
            requireCorrect, 
            showHint,
            autoShowHint
        });
        
        return {
            forceHintMode,
            requireCorrect,
            showHint,
            autoShowHint,
            // Keep raw settings for debugging
            raw: settings
        };
    }
    
    /**
     * Default settings when no quiz settings are found
     */
    function getDefaultSettings() {
        debug('Using default settings');
        return {
            forceHintMode: false,
            requireCorrect: true,
            showHint: true,
            autoShowHint: false,
            isDefault: true
        };
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners(settings) {
        debug('Setting up event listeners');
        
        // Listen for hint button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('wpProQuiz_TipButton') || 
                e.target.closest('.wpProQuiz_TipButton')) {
                const button = e.target.classList.contains('wpProQuiz_TipButton') ? 
                               e.target : e.target.closest('.wpProQuiz_TipButton');
                handleHintButtonClick(button, settings);
            }
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if ((e.target.name === 'check' && e.target.classList.contains('wpProQuiz_QuestionButton')) ||
                e.target.closest('input[name="check"].wpProQuiz_QuestionButton')) {
                const button = e.target.name === 'check' ? e.target : e.target.closest('input[name="check"]');
                handleCheckButtonClick(button, settings);
            }
        });
        
        // Initial check to mark any answers that are already checked
        setTimeout(initialCheck, 500, settings);
    }
    
    /**
     * Handle hint button clicks
     */
    function handleHintButtonClick(button, settings) {
        debug('Hint button clicked');
        
        const questionItem = button.closest('.wpProQuiz_listItem');
        if (!questionItem) {
            debug('Could not find parent question item');
            return;
        }
        
        const questionId = questionItem.getAttribute('data-post-id');
        if (!questionId) {
            debug('No question ID found');
            return;
        }
        
        // Mark this hint as viewed
        hintViewed[questionId] = true;
        debug('Hint viewed for question', questionId);
        
        // Update the next button if it's a force hint mode quiz
        if (settings.forceHintMode) {
            updateNextButton(questionItem, settings);
        }
    }
    
    /**
     * Handle check button clicks
     */
    function handleCheckButtonClick(button, settings) {
        debug('Check button clicked');
        
        // Allow time for LearnDash to update its UI
        setTimeout(function() {
            const questionItem = button.closest('.wpProQuiz_listItem');
            if (!questionItem) {
                debug('Could not find parent question item');
                return;
            }
            
            const questionId = questionItem.getAttribute('data-post-id');
            if (!questionId) {
                debug('No question ID found');
                return;
            }
            
            // Check if answer is correct
            const isCorrect = checkAnswerCorrectness(questionItem);
            debug('Answer correctness result:', isCorrect);
            
            // Store result for this question
            questionResults[questionId] = isCorrect;
            
            // If the answer is correct, mark the selected items
            if (isCorrect) {
                const selectedItems = questionItem.querySelectorAll(
                    '.wpProQuiz_questionListItem.is-selected, ' +
                    '.wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected'
                );
                
                selectedItems.forEach(item => {
                    item.classList.add('selected-correct');
                    item.classList.remove('selected-incorrect');
                });
            }
            
            // Always update the next button
            updateNextButton(questionItem, settings);
        }, 300); // Wait a bit for LearnDash to finish its updates
    }
    
    /**
     * Initial check for any answers that are already marked
     */
    function initialCheck(settings) {
        debug('Performing initial check');
        
        // Find all question items
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        questionItems.forEach(questionItem => {
            const questionId = questionItem.getAttribute('data-post-id');
            if (!questionId) return;
            
            // Check if hint is already viewed
            const hintBox = questionItem.querySelector('.wpProQuiz_tipp');
            if (hintBox && window.getComputedStyle(hintBox).display !== 'none') {
                hintViewed[questionId] = true;
                debug('Hint already viewed for question', questionId);
            }
            
            // Check if answer is correct
            const isCorrect = checkAnswerCorrectness(questionItem);
            if (isCorrect) {
                questionResults[questionId] = true;
                debug('Question', questionId, 'already correct');
            }
            
            // Update next button
            updateNextButton(questionItem, settings);
        });
    }
    
    /**
     * Check if the answer is correct
     */
    function checkAnswerCorrectness(questionItem) {
        debug('Checking answer correctness');
        
        // Method 1: Check for our custom selected-correct class
        const customCorrectAnswer = questionItem.querySelector('.wpProQuiz_questionListItem.selected-correct');
        if (customCorrectAnswer) {
            debug('Found answer with our custom selected-correct class');
            return true;
        }
        
        // Method 2: Check by LearnDash UI feedback
        const correctFeedback = questionItem.querySelector('.wpProQuiz_correct');
        if (correctFeedback) {
            const style = window.getComputedStyle(correctFeedback);
            if (style.display !== 'none') {
                debug('LearnDash shows correct feedback');
                return true;
            }
        }
        
        // Method 3: Check if selected answer has correct class
        const selectedWithCorrectClass = questionItem.querySelector('li.wpProQuiz_questionListItem.wpProQuiz_answerCorrect');
        if (selectedWithCorrectClass) {
            debug('Found answer with correct class');
            return true;
        }
        
        // Method 4: Check if there's a green background on selected answer
        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem.is-selected, .wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected');
        for (const item of selectedItems) {
            const style = window.getComputedStyle(item);
            if (style.backgroundColor && (
                style.backgroundColor.includes('rgb(0, 128, 0)') || // green
                style.backgroundColor.includes('rgb(144, 238, 144)') || // lightgreen
                style.backgroundColor.includes('rgb(0, 255, 0)') || // lime
                style.backgroundColor.includes('rgb(0, 255, 0)') || // lime
                style.backgroundColor.includes('rgb(46, 204, 113)') || // emerald
                style.backgroundColor.includes('rgb(39, 174, 96)') // nephritis
            )) {
                debug('Found selected answer with green background');
                return true;
            }
        }
        
        // Method 5: Check if any "Check" button is now disabled (often means correct)
        const checkButton = questionItem.querySelector('input[name="check"]');
        if (checkButton && checkButton.disabled) {
            // If check button is disabled, often means we've already checked and it was correct
            debug('Check button is disabled, assuming correct');
            return true;
        }
        
        // Method 6: Check if answer is marked as correct via data attribute
        const markedCorrectItems = questionItem.querySelectorAll('[data-correct="1"], [data-answer-correct="1"]');
        if (markedCorrectItems.length > 0) {
            debug('Found answer marked as correct via data attribute');
            return true;
        }
        
        // Method 7: Check if the .wpProQuiz_questionList has a 'correct' class
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.classList.contains('correct')) {
            debug('Question list has correct class');
            return true;
        }
        
        // Method 8: Check for "correct-answer" div anywhere
        const correctAnswerDiv = questionItem.querySelector('.correct-answer, .wpProQuiz_response .correct');

/**
 * Initial check for any answers that are already marked
 */
function initialCheck(settings) {
    debug('Performing initial check');
    
    // Find all question items
    const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
    questionItems.forEach(questionItem => {
     * Update the Next button based on settings and state
     */
    function updateNextButton(questionItem, settings) {
        debug('Updating next button');
        
        const nextButton = questionItem.querySelector('.wpProQuiz_QuestionButton[name="next"]');
        if (!nextButton) {
            debug('Next button not found');
            return;
        }
        
        const questionId = questionItem.getAttribute('data-post-id');
        if (!questionId) {
            debug('Question ID not found');
            return;
        }
        
        const isCorrect = questionResults[questionId] || false;
        const hasViewedHint = hintViewed[questionId] || false;
        
        // Settings are already normalized at this point
        const forceHintMode = settings.forceHintMode;
        const requireCorrect = settings.requireCorrect;
        
        debug('Question state', {
            questionId,
            isCorrect,
            hasViewedHint,
            forceHintMode,
            requireCorrect
        });
        
        if (forceHintMode && requireCorrect) {
            // Force mode: Require both correct answer and hint viewed
            if (!isCorrect) {
                // Answer incorrect, hide Next button
                nextButton.style.display = 'none';
                nextButton.disabled = true;
                debug('Hiding next button - answer is incorrect (force hint mode)');
            } else {
                if (hasViewedHint) {
                    // Answer correct and hint viewed, show Next button
                    nextButton.style.display = '';
                    nextButton.disabled = false;
                    nextButton.classList.remove('disabled');
                    nextButton.style.opacity = '1';
                    nextButton.style.cursor = 'pointer';
                    debug('Enabling next button - answer is correct and hint viewed (force hint mode)');
                } else {
                    // Answer correct but hint not viewed, hide Next button
                    nextButton.style.display = 'none';
                    nextButton.disabled = true;
                    debug('Hiding next button - hint not yet viewed (force hint mode)');
                }
            }
        } else if (forceHintMode) {
            // Force hint mode but correct answer not required
            if (hasViewedHint) {
                // Hint viewed, show Next button regardless of correctness
                nextButton.style.display = '';
                nextButton.disabled = false;
                nextButton.classList.remove('disabled');
                nextButton.style.opacity = '1';
                nextButton.style.cursor = 'pointer';
                debug('Enabling next button - hint viewed (force hint mode)');
            } else {
                // Hint not viewed, hide Next button
                nextButton.style.display = 'none';
                nextButton.disabled = true;
                debug('Hiding next button - hint not yet viewed (force hint mode)');
            }
        } else if (requireCorrect) {
            // Non-force mode but require correct answer
            if (isCorrect) {
                nextButton.style.display = '';
                nextButton.disabled = false;
                nextButton.classList.remove('disabled');
                nextButton.style.opacity = '1';
                nextButton.style.cursor = 'pointer';
                debug('Enabling next button - answer is correct (non-force mode)');
            } else {
                nextButton.style.display = 'none';
                nextButton.disabled = true;
                debug('Hiding next button - answer is incorrect (non-force mode)');
            }
        } else {
            // No restrictions, show Next button
            nextButton.style.display = '';
            nextButton.disabled = false;
            nextButton.classList.remove('disabled');
            nextButton.style.opacity = '1';
            nextButton.style.cursor = 'pointer';
            debug('Enabling next button - no restrictions mode');
        }
    }
})();
