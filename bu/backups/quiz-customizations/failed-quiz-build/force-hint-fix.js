/**
 * Force Hint Mode Fix for LearnDash Quiz
 * 
 * This script fixes issues with the force hint mode in LearnDash quizzes:
 * 1. Properly tracks hint viewing state
 * 2. Enables Next button after hint viewing and answer selection
 * 3. Provides better detection of user interactions
 * 4. Works with all question types
 * 
 * V1.0.1 - Debug mode activated to help troubleshoot remaining issues
 */

(function() {
    // Debug settings
    const DEBUG_MODE = true;
    const VERSION = "1.0.0";
    
    // State tracking maps
    const hintViewedMap = {};    // Tracks if hint was viewed for a question
    const answerSelectedMap = {}; // Tracks if answer was selected after hint viewing
    const buttonStateMap = {};    // Tracks button enabled/disabled state to prevent flickering
    
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log(`Force Hint Fix (${VERSION}):`, label, ...args);
        }
    }
    
    debug("Script loaded");
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Re-initialize when LearnDash events occur
    document.addEventListener('wpProQuiz_initComplete', init);
    
    // Delayed initialization for safer DOM access
    setTimeout(init, 800);
    
    /**
     * Initialize the script
     */
    function init() {
        debug("Initializing");
        
        // Get quiz settings
        const settings = getQuizSettings();
        if (!settings || !settings.forceHintMode) {
            debug("Force Hint Mode not enabled, exiting", settings);
            return;
        }
        
        debug("Force Hint Mode enabled, setting up", settings);
        
        // Set up event listeners
        setupEventListeners(settings);
        
        // Process all questions
        processAllQuestions(settings);
        
        // Add observer for dynamic content
        setupMutationObserver(settings);
        
        // Add debug controls if in debug mode
        if (DEBUG_MODE) {
            addDebugControls();
        }
    }
    
    /**
     * Get quiz settings from global variable or defaults
     */
    function getQuizSettings() {
        debug("Getting quiz settings");
        
        // Get quiz data
        const quizData = getQuizData();
        debug("Quiz data:", quizData);
        
        if (!quizData.quizId) {
            debug("No quiz ID found");
            return getDefaultSettings();
        }
        
        // Get settings from global options
        const quizOptions = window.quizExtensionsSettings?.quiz_options || {};
        const settings = quizOptions[quizData.quizId] || {};
        debug("Raw quiz settings for ID " + quizData.quizId + ":", settings);
        
        // Normalize settings
        return normalizeSettings(settings);
    }
    
    /**
     * Get quiz data from DOM or global variables
     */
    function getQuizData() {
        const quizData = {
            quizId: null,
            quizPostId: null,
            quizProId: null
        };
        
        // Method 1: From settings
        if (window.quizExtensionsSettings?.current_quiz_id) {
            quizData.quizId = window.quizExtensionsSettings.current_quiz_id;
            debug("Found quiz ID from settings:", quizData.quizId);
        }
        
        // Method 2: From DOM (LearnDash standard metadata)
        const quizDataElements = document.querySelectorAll('.wpProQuiz_data');
        quizDataElements.forEach(element => {
            const quizPostIdField = element.querySelector('input[name="quiz_post_id"]');
            const quizProIdField = element.querySelector('input[name="quiz_pro_id"]');
            
            if (quizPostIdField && quizPostIdField.value) {
                quizData.quizPostId = quizPostIdField.value;
                quizData.quizId = quizData.quizPostId;
                debug("Found quiz post ID:", quizData.quizPostId);
            }
            
            if (quizProIdField && quizProIdField.value) {
                quizData.quizProId = quizProIdField.value;
                debug("Found quiz pro ID:", quizData.quizProId);
            }
        });
        
        // Method 3: From quiz meta attribute
        const quizContainer = document.querySelector('[data-quiz-meta]');
        if (quizContainer) {
            try {
                const quizMeta = JSON.parse(quizContainer.getAttribute('data-quiz-meta'));
                if (quizMeta.quiz_post_id) {
                    quizData.quizPostId = quizMeta.quiz_post_id;
                    quizData.quizId = quizData.quizPostId;
                    debug("Found quiz post ID from data-quiz-meta:", quizData.quizPostId);
                }
            } catch (e) {
                debug("Error parsing quiz meta", e);
            }
        }
        
        return quizData;
    }
    
    /**
     * Normalize settings to standard format
     */
    function normalizeSettings(settings) {
        // Parse settings
        const forceHintMode = 
            settings.force_hint_mode === 'Yes' || 
            settings.force_hint_mode === '1' || 
            settings.force_hint_mode === true || 
            settings['Force Hint Mode'] === 'ENABLED';
            
        const requireCorrect = 
            settings.require_correct === 'Yes' || 
            settings.require_correct === '1' || 
            settings.require_correct === true || 
            settings['Require Correct'] === 'Yes';
            
        const showHint = 
            settings.show_hint === 'Yes' || 
            settings.show_hint === '1' || 
            settings.show_hint === true || 
            settings['Show Hint'] === 'Yes';
            
        const autoShowHint = 
            settings.auto_show_hint === 'Yes' || 
            settings.auto_show_hint === '1' || 
            settings.auto_show_hint === true || 
            settings['Auto Show Hint'] === 'Yes';
        
        const normalized = {
            forceHintMode,
            requireCorrect,
            showHint,
            autoShowHint,
            raw: settings
        };
        
        debug("Normalized settings:", normalized);
        return normalized;
    }
    
    /**
     * Get default settings
     */
    function getDefaultSettings() {
        debug("Using default settings");
        return {
            forceHintMode: false,
            requireCorrect: true,
            showHint: true,
            autoShowHint: false,
            raw: {}
        };
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners(settings) {
        debug("Setting up event listeners");
        
        // Detect hint button clicks
        document.addEventListener('click', function(event) {
            // Handle hint button clicks
            if (event.target.classList.contains('wpProQuiz_TipButton') || 
                event.target.closest('.wpProQuiz_TipButton')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    handleHintButtonClick(questionItem, settings);
                }
            }
            
            // Handle check button clicks
            if ((event.target.classList.contains('wpProQuiz_QuestionButton') && 
                 event.target.name === 'check') || 
                event.target.closest('input[name="check"]')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    handleCheckButtonClick(questionItem, settings);
                }
            }
            
            // Handle answer selection
            if (event.target.classList.contains('wpProQuiz_questionListItem') || 
                event.target.closest('.wpProQuiz_questionListItem')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        handleAnswerSelection(questionItem, settings);
                    }, 100); // Small delay to allow DOM to update
                }
            }
            
            // Handle input changes (radio, checkbox)
            if (event.target.type === 'radio' || event.target.type === 'checkbox') {
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        handleAnswerSelection(questionItem, settings);
                    }, 100); // Small delay to allow DOM to update
                }
            }
        });
        
        // Listen for hint display via DOM changes
        setupMutationObserver(settings);
    }
    
    /**
     * Set up mutation observer to detect DOM changes
     */
    function setupMutationObserver(settings) {
        debug("Setting up mutation observer");
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check if this is related to hint display
                if (mutation.type === 'attributes' && 
                    mutation.target.classList && 
                    mutation.target.classList.contains('wpProQuiz_incorrect')) {
                    
                    debug("Detected answer response change", mutation.target);
                    
                    // Find the question item
                    const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                    if (questionItem) {
                        // Update Next button after the hint becomes visible
                        setTimeout(() => {
                            processQuestion(questionItem, settings);
                        }, 100);
                    }
                }
                
                // Check for tip display
                if (mutation.type === 'attributes' && 
                    mutation.target.classList && 
                    mutation.target.classList.contains('wpProQuiz_tipp')) {
                    
                    if (mutation.attributeName === 'style' && 
                        window.getComputedStyle(mutation.target).display !== 'none') {
                        
                        debug("Detected hint display", mutation.target);
                        
                        // Find the question item
                        const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                        if (questionItem) {
                            handleHintDisplay(questionItem, settings);
                        }
                    }
                }
            });
        });
        
        // Start observing the document
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    /**
     * Handle hint button click
     */
    function handleHintButtonClick(questionItem, settings) {
        debug("Hint button clicked");
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) {
            debug("Could not find question ID for hint button click");
            return;
        }
        
        debug("Hint button clicked for question", questionId);
        
        // Mark that the hint was viewed
        hintViewedMap[questionId] = true;
        
        debug("Hint viewed for question", questionId);
        
        // Reset answer selected state when viewing hint
        answerSelectedMap[questionId] = false;
        
        // Process the question after a delay
        setTimeout(() => {
            processQuestion(questionItem, settings);
        }, 100);
        
        // Force feedback to the user that the hint was viewed
        // Add visual indicator that hint was viewed
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && !questionList.querySelector('.hint-viewed-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'hint-viewed-indicator';
            indicator.textContent = 'רמז נצפה'; // 'Hint viewed' in Hebrew
            indicator.style.cssText = 'color: #ff9800; font-weight: bold; padding: 5px; margin: 5px 0; font-size: 14px; text-align: right; background-color: rgba(255, 152, 0, 0.1); border-right: 3px solid #ff9800;';
            questionList.appendChild(indicator);
        }
    }
    
    /**
     * Handle hint display detection
     */
    function handleHintDisplay(questionItem, settings) {
        debug("Hint displayed");
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        // Mark that the hint was viewed
        hintViewedMap[questionId] = true;
        
        debug("Hint displayed for question", questionId);
        
        // Process the question
        processQuestion(questionItem, settings);
        
        // Add special indicator class for CSS
        questionItem.classList.add('hint-viewed');
        
        // Optional: Add visual indicator that hint was viewed
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && !questionList.querySelector('.hint-viewed-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'hint-viewed-indicator';
            indicator.textContent = 'רמז נצפה';
            indicator.style.cssText = 'color: #ff9800; font-weight: bold; margin: 5px 0; font-size: 14px; text-align: right;';
            questionList.appendChild(indicator);
        }
    }
    
    /**
     * Handle check button click
     */
    function handleCheckButtonClick(questionItem, settings) {
        debug("Check button clicked");
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        // Mark that an answer was checked after viewing hint
        if (hintViewedMap[questionId]) {
            answerSelectedMap[questionId] = true;
            debug("Answer checked after viewing hint for question", questionId);
        }
        
        // Process the question after a delay
        setTimeout(() => {
            processQuestion(questionItem, settings);
        }, 100);
    }
    
    /**
     * Handle answer selection
     */
    function handleAnswerSelection(questionItem, settings) {
        debug("Answer selection detected");
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        // Check if any answer is selected
        const hasSelection = hasSelectedAnswer(questionItem);
        
        if (hasSelection && hintViewedMap[questionId]) {
            answerSelectedMap[questionId] = true;
            debug("Answer selected after viewing hint for question", questionId);
        }
        
        // Process the question
        processQuestion(questionItem, settings);
    }
    
    /**
     * Get question ID from question item
     */
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Method 1: Direct data-post-id attribute (LearnDash's standard)
        if (questionItem.dataset.postId) {
            debug("Found question ID from data-post-id:", questionItem.dataset.postId);
            return questionItem.dataset.postId;
        }
        
        // Method 2: Parse from data-question-meta JSON (LearnDash's newer format)
        if (questionItem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(questionItem.dataset.questionMeta);
                if (meta.question_post_id) {
                    debug("Found question ID from data-question-meta:", meta.question_post_id);
                    return meta.question_post_id.toString();
                } else if (meta.question_pro_id) {
                    debug("Found question pro ID from data-question-meta:", meta.question_pro_id);
                    return meta.question_pro_id.toString();
                }
            } catch (e) {
                debug("Error parsing question meta:", e);
            }
        }
        
        // Method 3: Data attribute (legacy format)
        if (questionItem.dataset.question) {
            debug("Found question ID from data-question:", questionItem.dataset.question);
            return questionItem.dataset.question;
        }
        
        // Method 4: ID attribute
        if (questionItem.id && questionItem.id.includes('wpProQuiz_')) {
            const idMatch = questionItem.id.match(/wpProQuiz_\d+_(\d+)/);
            if (idMatch && idMatch[1]) {
                debug("Found question ID from element ID:", idMatch[1]);
                return idMatch[1];
            }
        }
        
        // Method 5: Find question ID in review list
        const index = Array.from(document.querySelectorAll('.wpProQuiz_listItem')).indexOf(questionItem);
        if (index !== -1) {
            const reviewItems = document.querySelectorAll('.wpProQuiz_reviewQuestion li');
            if (reviewItems[index] && reviewItems[index].dataset.question) {
                debug("Found question ID from review items:", reviewItems[index].dataset.question);
                return reviewItems[index].dataset.question;
            }
        }
        
        // Method 6: Find any ID in question content
        const idElement = questionItem.querySelector('[data-question-id]');
        if (idElement && idElement.dataset.questionId) {
            debug("Found question ID from nested data-question-id:", idElement.dataset.questionId);
            return idElement.dataset.questionId;
        }
        
        // Method 7: Try to find the ID in the DOM structure with regex
        if (questionItem.innerHTML) {
            const idRegex = /data-question="(\d+)"|data-id="(\d+)"|question_id\s*=\s*(\d+)|questionId\s*=\s*(\d+)/;
            const match = questionItem.innerHTML.match(idRegex);
            if (match) {
                const id = match[1] || match[2] || match[3] || match[4];
                if (id) {
                    debug("Found question ID using regex from innerHTML:", id);
                    return id;
                }
            }
        }
        
        debug("Could not find question ID for", questionItem);
        return null;
    }
    
    /**
     * Check if the question has a selected answer
     */
    function hasSelectedAnswer(questionItem) {
        if (!questionItem) return false;
        
        // Check for selected answer items
        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem.selectedAnswer, .wpProQuiz_questionListItem.user-selected');
        if (selectedItems && selectedItems.length > 0) {
            return true;
        }
        
        // Check for checked inputs
        const checkedInputs = questionItem.querySelectorAll('input:checked');
        if (checkedInputs && checkedInputs.length > 0) {
            return true;
        }
        
        // Check for filled text inputs
        const textInputs = questionItem.querySelectorAll('input[type="text"], textarea');
        for (let i = 0; i < textInputs.length; i++) {
            if (textInputs[i].value.trim() !== '') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Process a single question
     */
    function processQuestion(questionItem, settings) {
        debug("Processing question");
        
        if (!questionItem) return;
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        // Update next button
        updateNextButton(questionItem, questionId, settings);
        
        // Add warning if hint not viewed yet (force hint mode)
        if (settings.forceHintMode && !hintViewedMap[questionId]) {
            addHintRequiredWarning(questionItem);
        } else {
            removeHintRequiredWarning(questionItem);
        }
    }
    
    /**
     * Process all questions in the quiz
     */
    function processAllQuestions(settings) {
        debug("Processing all questions");
        
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        questionItems.forEach(questionItem => {
            processQuestion(questionItem, settings);
        });
    }
    
    /**
     * Add warning that hint is required
     */
    function addHintRequiredWarning(questionItem) {
        // If warning already exists, don't add another
        if (questionItem.querySelector('.hint-required-warning')) return;
        
        // Check if hint button exists
        const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
        if (!hintButton) return;
        
        // Create warning element
        const warning = document.createElement('div');
        warning.className = 'hint-required-warning';
        warning.textContent = 'התשובה שלך שגויה. אנא לחץ על כפתור הרמז';
        warning.style.cssText = 'color: #f44336; font-weight: bold; padding: 10px; margin: 5px 0; font-size: 14px; text-align: right; background-color: rgba(244, 67, 54, 0.1); border-right: 3px solid #f44336;';
        
        // Insert warning after the question list
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList) {
            questionList.appendChild(warning);
        }
        
        // Highlight the hint button to make it more noticeable
        if (hintButton) {
            hintButton.style.animation = 'pulse-hint-button 1.5s infinite';
            hintButton.style.boxShadow = '0 0 10px rgba(255, 152, 0, 0.7)';
            hintButton.style.border = '2px solid #ff9800';
            
            // Add the pulse animation if it doesn't exist
            if (!document.getElementById('hint-button-animation')) {
                const style = document.createElement('style');
                style.id = 'hint-button-animation';
                style.textContent = `
                    @keyframes pulse-hint-button {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.05); }
                        100% { transform: scale(1); }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }
    
    /**
     * Remove hint required warning
     */
    function removeHintRequiredWarning(questionItem) {
        const warning = questionItem.querySelector('.hint-required-warning');
        if (warning) {
            warning.remove();
        }
    }
    
    /**
     * Update the next button based on question state
     */
    function updateNextButton(questionItem, questionId, settings) {
        debug("Updating next button for question", questionId);
        
        // Find the next button
        const nextButton = findNextButton(questionItem);
        if (!nextButton) {
            debug("No next button found");
            return;
        }
        
        // Get question state
        const hasViewedHint = hintViewedMap[questionId] || false;
        const hasSelectedAnswer = answerSelectedMap[questionId] || false;
        
        debug("Question state:", {
            questionId,
            hasViewedHint,
            hasSelectedAnswer,
            forceHintMode: settings.forceHintMode
        });
        
        // Check if button state is already set
        if (buttonStateMap[questionId] === true) {
            debug("Button already enabled for question", questionId);
            enableButton(nextButton);
            return;
        }
        
        // Force Hint Mode Logic
        if (settings.forceHintMode) {
            // If hint viewed and answer selected, enable next button
            if (hasViewedHint && hasSelectedAnswer) {
                debug("Enabling next button - hint viewed and answer selected");
                enableButton(nextButton);
                buttonStateMap[questionId] = true;
                return;
            }
            
            // If hint viewed but no answer selected yet
            if (hasViewedHint && !hasSelectedAnswer) {
                debug("Disabling next button - hint viewed but no answer selected");
                disableButton(nextButton);
                return;
            }
            
            // Hint not viewed yet
            if (!hasViewedHint) {
                debug("Disabling next button - hint not viewed yet");
                disableButton(nextButton);
                return;
            }
        }
        
        // Default behavior - don't interfere if not in force hint mode
        debug("Not in force hint mode, leaving button in default state");
    }
    
    /**
     * Find the next button for a question
     */
    function findNextButton(questionItem) {
        // Try to find the button within the question item
        let nextButton = questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        
        // If not found, try to find a global next button
        if (!nextButton) {
            nextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        }
        
        return nextButton;
    }
    
    /**
     * Enable a button
     */
    function enableButton(button) {
        if (!button) return;
        
        // Enable button
        button.disabled = false;
        button.removeAttribute('disabled');
        
        // Update classes
        button.classList.remove('wpProQuiz_button_disabled');
        button.classList.add('wpProQuiz_button_enabled');
        
        // Update style
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    }
    
    /**
     * Disable a button
     */
    function disableButton(button) {
        if (!button) return;
        
        // Disable button
        button.disabled = true;
        button.setAttribute('disabled', 'disabled');
        
        // Update classes
        button.classList.add('wpProQuiz_button_disabled');
        button.classList.remove('wpProQuiz_button_enabled');
        
        // Update style
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
    }
    
    /**
     * Add debug controls to the page
     */
    function addDebugControls() {
        // If controls already exist, don't add them again
        if (document.getElementById('force-hint-debug-controls')) return;
        
        // Create controls container
        const controls = document.createElement('div');
        controls.id = 'force-hint-debug-controls';
        controls.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 10px; border: 1px solid #ddd; z-index: 9999; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);';
        
        // Add reset button
        const resetButton = document.createElement('button');
        resetButton.textContent = 'Reset State';
        resetButton.style.cssText = 'margin-right: 10px; padding: 5px 10px;';
        resetButton.addEventListener('click', function() {
            // Reset all state
            Object.keys(hintViewedMap).forEach(key => {
                hintViewedMap[key] = false;
            });
            Object.keys(answerSelectedMap).forEach(key => {
                answerSelectedMap[key] = false;
            });
            Object.keys(buttonStateMap).forEach(key => {
                buttonStateMap[key] = false;
            });
            
            // Reprocess all questions
            processAllQuestions(getQuizSettings());
            
            debug("State reset");
        });
        
        // Add status button
        const statusButton = document.createElement('button');
        statusButton.textContent = 'Show State';
        statusButton.style.cssText = 'padding: 5px 10px;';
        statusButton.addEventListener('click', function() {
            console.log("Force Hint Fix State:", {
                hintViewedMap,
                answerSelectedMap,
                buttonStateMap,
                settings: getQuizSettings()
            });
        });
        
        // Add buttons to controls
        controls.appendChild(resetButton);
        controls.appendChild(statusButton);
        
        // Add controls to page
        document.body.appendChild(controls);
    }
})();
