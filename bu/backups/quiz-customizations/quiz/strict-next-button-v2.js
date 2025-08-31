/**
 * Strict Next Button Controller for LearnDash Quiz
 * 
 * This script provides a more accurate Next button control that
 * only shows the button when the answer is truly correct.
 * Enhanced for proper handling of force hint mode quizzes.
 * 
 * @since 1.0.0
 */

(function() {
    // Debug logging with prefix
    const DEBUG_MODE = true;
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log('Strict Next Button:', label, ...args);
        }
    }

    // State tracking
    const hintViewed = {};
    const questionResults = {};
    const viewedHintThenChecked = {}; // Track if user has checked the answer AFTER viewing the hint
    const rightAnswers = {}; // Store right answers
    
    // Enhanced state for storing correct answers and their positions
    const quizAnswers = {
        // Structure: { questionId: { answerIndex: boolean, position: number } }
        correctAnswers: {},
        // Store raw answer text for reference
        answerText: {},
        // Track if we've already discovered the answers for this question
        discovered: {}
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Add an additional initialization when possible LearnDash events occur
    document.addEventListener('wpProQuiz_initComplete', function() {
        debug('LearnDash quiz init detected, re-initializing');
        init();
    });
    
    // Delayed initialization for better DOM content loading
    setTimeout(init, 1000);

    function init() {
        debug('Initializing Strict Next Button Controller');
        
        // Get quiz settings
        const settings = getQuizSettings();
        if (!settings) {
            debug('No quiz settings found, exiting');
            return;
        }
        
        debug('Quiz settings loaded', settings);
        
        // Set up event listeners
        setupEventListeners(settings);
        
        // Add debug button
        addDebugButton();
        
        // Do an initial check of all questions/answers
        initialCheck(settings);
        
        // Additional check after a delay to ensure Next buttons are properly configured
        setTimeout(() => {
            fixAllNextButtons(settings);
        }, 1000);
    }
    
    function getDefaultSettings() {
        debug('Using default settings');
        return {
            forceHintMode: false,
            requireCorrect: true,
            showHint: false,
            autoShowHint: false,
            raw: {}
        };
    }
    
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

        // Method 3: From quiz meta attribute
        const quizMetaElement = document.querySelector('[data-quiz-meta]');
        if (quizMetaElement) {
            try {
                const quizMeta = JSON.parse(quizMetaElement.getAttribute('data-quiz-meta'));
                if (quizMeta.quiz_post_id) {
                    quizData.quizPostId = quizMeta.quiz_post_id;
                    quizData.quizId = quizData.quizPostId;
                    quizData.fromDom = true;
                    debug('Found quiz post ID from data-quiz-meta:', quizData.quizPostId);
                }
            } catch (e) {
                debug('Error parsing quiz meta', e);
            }
        }
        
        return quizData;
    }
    
    function normalizeSettings(settings) {
        // Check if we have Force Hint Mode in any format
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
    
    function setupEventListeners(settings) {
        debug('Setting up event listeners with settings:', settings);
        
        // Listen for hint button clicks
        document.addEventListener('click', function(event) {
            // Check if this is a hint button
            if (event.target.classList.contains('wpProQuiz_hint') || 
                event.target.closest('.wpProQuiz_hint')) {
                const hintButton = event.target.classList.contains('wpProQuiz_hint') ? 
                    event.target : 
                    event.target.closest('.wpProQuiz_hint');
                
                handleHintButtonClick(hintButton, settings);
            }
            
            // Check if this is a check button
            if (event.target.classList.contains('wpProQuiz_check') || 
                event.target.closest('.wpProQuiz_check')) {
                const checkButton = event.target.classList.contains('wpProQuiz_check') ? 
                    event.target : 
                    event.target.closest('.wpProQuiz_check');
                
                handleCheckButtonClick(checkButton, settings);
            }
        });
        
        // Listen for input changes (selections)
        document.addEventListener('change', function(event) {
            if (event.target.tagName === 'INPUT' && 
                (event.target.type === 'radio' || event.target.type === 'checkbox') &&
                event.target.closest('.wpProQuiz_questionList')) {
                
                // Get the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    // Mark this question list item as having a user selection
                    const listItem = event.target.closest('.wpProQuiz_questionListItem');
                    if (listItem) {
                        listItem.classList.add('user-selected');
                    }
                    
                    // Update the next button based on settings
                    updateNextButton(questionItem, settings);
                }
            }
        });
    }
    
    function handleHintButtonClick(button, settings) {
        // Get the question item containing this button
        const questionItem = button.closest('.wpProQuiz_listItem');
        if (!questionItem) {
            debug('Hint button not in a question item', button);
            return;
        }
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) {
            debug('Cannot get question ID for hint button');
            return;
        }
        
        // Mark hint as viewed for this question
        hintViewed[questionId] = true;
        debug('Hint viewed for question', questionId);
        
        // Reset check-after-hint status when hint is viewed
        // This forces the user to check the answer again after viewing the hint
        viewedHintThenChecked[questionId] = false;
        
        // Update the next button
        updateNextButton(questionItem, settings);
    }
    
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Try data-post-id first (most reliable)
        const postId = questionItem.getAttribute('data-post-id');
        if (postId) return postId;
        
        // Try question-meta with question_post_id
        try {
            const meta = questionItem.getAttribute('data-question-meta');
            if (meta) {
                const metaObj = JSON.parse(meta);
                if (metaObj.question_post_id) {
                    return metaObj.question_post_id.toString();
                }
                if (metaObj.question_pro_id) {
                    return 'pro-' + metaObj.question_pro_id.toString();
                }
            }
        } catch (e) {
            debug('Error parsing question meta', e);
        }
        
        // Try data-question attribute for review list
        const dataQuestion = questionItem.getAttribute('data-question');
        if (dataQuestion) return dataQuestion;
        
        // Try closest data-attribute for nested elements
        const parentWithId = questionItem.closest('[data-question], [data-post-id]');
        if (parentWithId) {
            const id = parentWithId.getAttribute('data-question') || parentWithId.getAttribute('data-post-id');
            if (id) return id;
        }
        
        // As a last resort, use the question number if available
        const questionNumber = questionItem.querySelector('.wpProQuiz_header')?.textContent.trim();
        if (questionNumber) {
            return 'q-' + questionNumber.replace(/[^0-9]/g, '');
        }
        
        debug('Could not determine question ID');
        return null;
    }
    
    function handleCheckButtonClick(button, settings) {
        // Get the question item containing this button
        const questionItem = button.closest('.wpProQuiz_listItem');
        if (!questionItem) {
            debug('Check button not in a question item', button);
            return;
        }
        
        // Get question ID
        const questionId = getQuestionId(questionItem);
        if (!questionId) {
            debug('Cannot get question ID for check button');
            return;
        }
        
        debug('Check button clicked for question', questionId);
        
        // Find the right answer if not already stored
        if (!rightAnswers[questionId]) {
            findRightAnswer(questionItem, questionId);
        }
        
        // Check if hint was viewed before this check - important for force hint mode
        if (hintViewed[questionId]) {
            viewedHintThenChecked[questionId] = true;
            debug('User checked answer after viewing hint for question', questionId);
            
            // Mark the question as answered for LearnDash's internal validation
            questionItem.classList.add('wpProQuiz_questionAnswered');
            
            // In force hint mode, mark user-selected answers with our own class
            const selectedOptions = questionItem.querySelectorAll('input:checked');
            selectedOptions.forEach(input => {
                const listItem = input.closest('.wpProQuiz_questionListItem');
                if (listItem) {
                    listItem.classList.add('user-selected');
                    debug('Marked user selection with class for question', questionId);
                }
            });
            
            // Broadcast a custom event that hint was viewed and then checked
            const event = new CustomEvent('lilac_answer_checked_after_hint', {
                detail: {
                    questionId: questionId,
                    questionItem: questionItem
                },
                bubbles: true
            });
            questionItem.dispatchEvent(event);
        }
        
        // After a delay (to allow LearnDash to mark the answer)
        setTimeout(function() {
            // Re-check if the answer is correct
            questionResults[questionId] = isAnswerCorrect(questionItem);
            debug('Question result after check:', questionId, questionResults[questionId]);
            
            // Try to extract the correct answers from the DOM
            extractCorrectAnswers(questionItem);
            
            // For force hint mode, ensure any validation messages are removed
            if (hintViewed[questionId] && settings.forceHintMode) {
                // Hide any validation message if present
                const validationMessage = questionItem.querySelector('.wpProQuiz_invalidate');
                if (validationMessage) {
                    validationMessage.style.display = 'none';
                    debug('Removing validation message for force hint mode');
                }
            }
            
            // Update the Next button state
            updateNextButton(questionItem, settings);
        }, 300); // Wait for LearnDash to finish its updates
    }
    
    function isAnswerCorrect(questionItem) {
        debug('Checking answer correctness');
        if (!questionItem) return false;
        
        const questionId = getQuestionId(questionItem);
        if (!questionId) return false;
        
        // In force hint mode, if the hint was viewed and then checked, consider it valid
        if (hintViewed[questionId] && viewedHintThenChecked[questionId]) {
            debug('Force hint mode - hint viewed and checked, considering valid');
            return true;
        }
        
        // Check if the response container shows correct
        const responseBlock = questionItem.querySelector('.wpProQuiz_response');
        if (responseBlock) {
            const correctDiv = responseBlock.querySelector('.wpProQuiz_correct');
            if (correctDiv && window.getComputedStyle(correctDiv).display !== 'none') {
                debug('Answer is correct - response block shows correct');
                return true;
            }
        }
        
        // Check if any of the answer items have the correct class
        const correctItems = questionItem.querySelectorAll('.wpProQuiz_answerCorrect, .wpProQuiz_questionListItem.correct');
        if (correctItems && correctItems.length > 0) {
            debug('Answer is correct - found items with correct classes');
            return true;
        }
        
        // Check if the question list has been marked as correct
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.classList.contains('wpProQuiz_questionListCorrect')) {
            debug('Answer is correct - questionList has correct class');
            return true;
        }
        
        // Check for a green checkmark in the review list (LearnDash UI)
        if (questionId) {
            const reviewItem = document.querySelector(`.wpProQuiz_reviewQuestion li[data-question="${questionId}"]`);
            if (reviewItem && reviewItem.classList.contains('wpProQuiz_reviewQuestionSolved')) {
                debug('Answer is correct - review item is marked as solved');
                return true;
            }
        }

        // Check for user selected items with 'is-selected' class
        const userSelectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem.user-selected');
        if (userSelectedItems && userSelectedItems.length > 0) {
            // For force hint mode, if any selection exists after viewing hint
            if (hintViewed[questionId]) {
                debug('Force hint mode - user selection exists after viewing hint');
                return true;
            }
        }
        
        // Check for any checked inputs in force hint mode
        if (hintViewed[questionId]) {
            const checkedInputs = questionItem.querySelectorAll('input:checked');
            if (checkedInputs && checkedInputs.length > 0) {
                debug('Force hint mode - checked inputs found after viewing hint');
                return true;
            }
        }
        
        // If we can't clearly determine correctness, default to false
        debug('Could not determine correctness, defaulting to false');
        return false;
    }
    
    function updateNextButton(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        
        if (!questionId) {
            debug('Cannot update Next button - no question ID found');
            return;
        }
        
        // Find the Next button for this question
        const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        
        // If not found in the question item, look for a global next button
        let globalNextButton = null;
        if (!nextButton) {
            globalNextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        }
        
        // Use whichever button we found
        const buttonToUpdate = nextButton || globalNextButton;
        
        if (!buttonToUpdate) {
            debug('No Next button found');
            return;
        }
        
        // Re-check the answer correctness
        const isCorrect = isAnswerCorrect(questionItem);
        questionResults[questionId] = isCorrect;
        
        // Get hint viewing status
        const hasViewedHint = hintViewed[questionId] || false;
        const hasCheckedAfterHint = viewedHintThenChecked[questionId] || false;
        
        debug('Question state for next button:', {
            id: questionId,
            isCorrect: isCorrect,
            viewedHint: hasViewedHint, 
            checkedAfterHint: hasCheckedAfterHint,
            forceHintMode: settings?.forceHintMode || false
        });
        
        // Special case for force hint mode
        if (settings && settings.forceHintMode) {
            // If hint was viewed AND user checked after viewing hint, enable button
            if (hasViewedHint && hasCheckedAfterHint) {
                debug('Enabling next button - hint viewed with check after (force hint mode)');
                enableButton(buttonToUpdate);
                return;
            }
            
            // If hint was viewed and user has made any selection
            if (hasViewedHint) {
                const hasSelection = questionItem.querySelector('input:checked') !== null;
                if (hasSelection) {
                    debug('Enabling next button - hint viewed with selection (force hint mode)');
                    enableButton(buttonToUpdate);
                    return;
                }
            }
            
            // If not meeting force hint mode conditions, disable button
            debug('Disabling next button - force hint mode conditions not met');
            disableButton(buttonToUpdate);
            return;
        }
        
        // Regular mode - show next button only for correct answers
        if (isCorrect) {
            debug('Enabling next button - answer is correct');
            enableButton(buttonToUpdate);
            return;
        }
        
        // If we got here, disable the button
        debug('Disabling next button - conditions not met');
        disableButton(buttonToUpdate);
    }
    
    function extractCorrectAnswers(questionItem) {
        const questionId = getQuestionId(questionItem);
        if (!questionId || quizAnswers.discovered[questionId]) {
            return; // Skip if already discovered or no question ID
        }
        
        debug('Extracting correct answers for question', questionId);
        
        // Initialize the correct answers object for this question
        quizAnswers.correctAnswers[questionId] = {};
        quizAnswers.answerText[questionId] = {};
        
        // Find all answer items
        const answerItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        
        // Check for answers marked as correct by LearnDash
        let foundCorrectAnswer = false;
        answerItems.forEach((item, index) => {
            // Store the answer text for reference
            const answerText = item.textContent.trim();
            quizAnswers.answerText[questionId][index] = answerText;
            
            // Check if this is marked as the correct answer
            const isCorrect = item.classList.contains('wpProQuiz_answerCorrect') || 
                              item.querySelector('.wpProQuiz_correct') !== null;
            
            // Store the result
            quizAnswers.correctAnswers[questionId][index] = isCorrect;
            
            if (isCorrect) {
                foundCorrectAnswer = true;
                debug(`Found correct answer for question ${questionId}: option ${index}`, answerText);
            }
        });
        
        // If we found at least one correct answer, mark this question as discovered
        if (foundCorrectAnswer) {
            quizAnswers.discovered[questionId] = true;
            debug('Successfully identified correct answers for question', questionId, quizAnswers.correctAnswers[questionId]);
        }
        
        return foundCorrectAnswer;
    }
    
    function findRightAnswer(questionItem, questionId) {
        const questionText = questionItem.querySelector('.wpProQuiz_question_text');
        const questionLabel = questionItem.querySelector('.wpProQuiz_header span')?.textContent || '?';
        
        // Look for the correct answer options
        const correctItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem[data-correct="1"], .wpProQuiz_questionListItem.wpProQuiz_answerCorrect');
        
        if (correctItems.length > 0) {
            const correctTexts = [];
            correctItems.forEach(item => {
                const labelText = item.textContent.trim();
                correctTexts.push(labelText);
            });
            
            rightAnswers[questionId] = {
                label: questionLabel,
                question: questionText ? questionText.textContent.trim() : 'Unknown question',
                correct: correctTexts.join(' | '),
                timestamp: new Date().toISOString()
            };
            
            debug('Stored right answer for question ' + questionId, rightAnswers[questionId]);
        }
    }
    
    function initialCheck(settings) {
        debug('Performing initial check');
        
        // Find all question items
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        
        debug('Found ' + questionItems.length + ' question items');
        
        questionItems.forEach(questionItem => {
            const questionId = getQuestionId(questionItem);
            if (!questionId) {
                debug('No question ID found for a question item');
                return;
            }
            
            // Check if hint is already viewed
            const hintBox = questionItem.querySelector('.wpProQuiz_tipp');
            if (hintBox && window.getComputedStyle(hintBox).display !== 'none') {
                hintViewed[questionId] = true;
                debug('Hint already viewed for question', questionId);
            }
            
            // Check if answer is correct
            const isCorrect = isAnswerCorrect(questionItem);
            if (isCorrect) {
                questionResults[questionId] = true;
                debug('Question ' + questionId + ' already correct');
            }
            
            // Update next button
            updateNextButton(questionItem, settings);
        });
    }
    
    function enableButton(button) {
        // First unset any inline display style
        button.style.display = '';
        button.disabled = false;
        button.classList.remove('disabled');
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
        
        // Then forcibly set to inline-block to ensure visibility
        setTimeout(() => {
            if (window.getComputedStyle(button).display === 'none') {
                button.style.display = 'inline-block';
                debug('Force-showing button that was still hidden');
            }
        }, 100);
    }
    
    function disableButton(button) {
        button.style.display = 'none';
        button.disabled = true;
        button.classList.add('disabled');
    }
    
    function fixAllNextButtons(settings) {
        debug('Fixing all Next buttons on the page');
        
        // First find all question items
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        questionItems.forEach(item => {
            updateNextButton(item, settings);
        });
        
        // If no questions found or this is a normal quiz with no force hint mode,
        // ensure all Next buttons are visible
        if (!settings.forceHintMode || questionItems.length === 0) {
            const allNextButtons = document.querySelectorAll('.wpProQuiz_nextButton, input[name="next"], a.wpProQuiz_nextButton, .wpProQuiz_button[name="next"]');
            debug('Found ' + allNextButtons.length + ' next buttons to check in normal mode');
            
            allNextButtons.forEach(button => {
                // Only restore buttons that are not specifically disabled for other reasons
                if (!button.disabled) {
                    enableButton(button);
                    debug('Enabled a next button in normal mode', button);
                }
            });
        }
    }
    
    function addDebugButton() {
        // Check if debug is disabled in settings
        if (window.quizExtensionsSettings && window.quizExtensionsSettings.disable_debug === '1') {
            return;
        }
        
        // Create the button element
        const debugButton = document.createElement('button');
        debugButton.textContent = '🐞 Show Quiz Answers';
        debugButton.className = 'lilac-debug-button';
        debugButton.style.cssText = 'position: fixed; bottom: 10px; right: 10px; z-index: 9999; background: #f44336; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 12px;';
        
        // Add auto-complete button for force hint quizzes
        const autoCompleteButton = document.createElement('button');
        autoCompleteButton.textContent = '🚀 Auto-Complete Quiz';
        autoCompleteButton.className = 'lilac-auto-complete-button';
        autoCompleteButton.style.cssText = 'position: fixed; bottom: 50px; right: 10px; z-index: 9999; background: #2196f3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 12px;';
        
        // Add click event to the debug button
        debugButton.addEventListener('click', function() {
            showRightAnswers();
        });
        
        // Add click event to the auto-complete button
        autoCompleteButton.addEventListener('click', function() {
            autoCompleteForceHintQuiz();
        });
        
        // Add the buttons to the document
        document.body.appendChild(debugButton);
        document.body.appendChild(autoCompleteButton);
    }
    
    /**
     * Display right answers for debugging
     */
    function showRightAnswers() {
        debug('Showing right answers for debugging');
        
        // Check if we have any stored right answers
        const hasStoredAnswers = Object.keys(rightAnswers).length > 0;
        const hasDiscoveredAnswers = Object.keys(quizAnswers.discovered).length > 0;
        
        if (!hasStoredAnswers && !hasDiscoveredAnswers) {
            // No answers stored yet, try to extract from current state
            debug('No answers stored, extracting from current state');
            
            // Find all question items
            const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
            questionItems.forEach(questionItem => {
                findRightAnswer(questionItem, getQuestionId(questionItem));
                extractCorrectAnswers(questionItem);
            });
        }
        
        // Create the display element
        let answersDisplay = document.getElementById('lilac-right-answers');
        if (!answersDisplay) {
            answersDisplay = document.createElement('div');
            answersDisplay.id = 'lilac-right-answers';
            answersDisplay.style.cssText = 'position: fixed; top: 20px; right: 20px; max-width: 400px; max-height: 80%; overflow-y: auto; background: rgba(0,0,0,0.8); color: white; padding: 15px; border-radius: 5px; z-index: 10000; font-size: 14px;';
            document.body.appendChild(answersDisplay);
        }
        
        // Toggle display
        if (answersDisplay.style.display === 'none') {
            answersDisplay.style.display = 'block';
        } else {
            answersDisplay.style.display = 'none';
            return;
        }
        
        // Build the content
        let content = '<h3>Quiz Answers</h3>';
        
        // Add stored right answers
        if (hasStoredAnswers) {
            content += '<h4>Stored Answers</h4>';
            for (const questionId in rightAnswers) {
                const answer = rightAnswers[questionId];
                content += `<div style="margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px;">
                    <div><strong>Q${answer.label}</strong>: ${answer.question}</div>
                    <div style="color: #4caf50;">A: ${answer.correct}</div>
                </div>`;
            }
        }
        
        // Add discovered answers from DOM
        if (hasDiscoveredAnswers) {
            content += '<h4>Discovered Answers</h4>';
            for (const questionId in quizAnswers.discovered) {
                content += `<div style="margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px;">\n                    <div><strong>Question ${questionId}</strong></div>`;
                
                // Show correct answers for this question
                for (const answerIndex in quizAnswers.correctAnswers[questionId]) {
                    if (quizAnswers.correctAnswers[questionId][answerIndex]) {
                        const answerText = quizAnswers.answerText[questionId][answerIndex] || 'Answer ' + answerIndex;
                        content += `<div style="color: #4caf50;">✓ ${answerText}</div>`;
                    }
                }
                content += '</div>';
            }
        }
        
        // If no answers found at all
        if (!hasStoredAnswers && !hasDiscoveredAnswers) {
            content += '<p>No answers found. Try checking answers first.</p>';
        }
        
        // Add a close button
        content += '<button onclick="document.getElementById(\'lilac-right-answers\').style.display=\'none\'" style="background: #f44336; color: white; border: none; padding: 5px 10px; margin-top: 10px; cursor: pointer;">Close</button>';
        
        // Set the content
        answersDisplay.innerHTML = content;
    }
    
    /**
     * Auto-complete the current force hint quiz
     * This will view hints and trigger answers for each question to help with testing
     */
    function autoCompleteForceHintQuiz() {
        debug('Auto-completing force hint quiz');
        
        // Get all questions
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        if (questionItems.length === 0) {
            debug('No questions found');
            return;
        }
        
        // Process the current visible question
        const currentQuestion = Array.from(questionItems).find(item => 
            window.getComputedStyle(item).display !== 'none'
        );
        
        if (!currentQuestion) {
            debug('No visible question found');
            return;
        }
        
        // Step 1: Click the hint button if available
        const hintButton = currentQuestion.querySelector('.wpProQuiz_hint');
        if (hintButton) {
            debug('Clicking hint button');
            hintButton.click();
            
            // Wait for hint to appear
            setTimeout(() => {
                // Step 2: Select the first answer
                const inputs = currentQuestion.querySelectorAll('input');
                if (inputs.length > 0) {
                    debug('Selecting first answer');
                    
                    // For radio buttons, just select the first one
                    if (inputs[0].type === 'radio') {
                        inputs[0].checked = true;
                        inputs[0].dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    // For checkboxes, select all of them
                    else if (inputs[0].type === 'checkbox') {
                        inputs.forEach(input => {
                            input.checked = true;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }
                    
                    // Step 3: Click the check button
                    setTimeout(() => {
                        const checkButton = currentQuestion.querySelector('.wpProQuiz_check');
                        if (checkButton) {
                            debug('Clicking check button');
                            checkButton.click();
                            
                            // Step 4: Click the next button after a delay
                            setTimeout(() => {
                                const nextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
                                if (nextButton && window.getComputedStyle(nextButton).display !== 'none') {
                                    debug('Clicking next button');
                                    nextButton.click();
                                    
                                    // Recursively continue with the next question
                                    setTimeout(autoCompleteForceHintQuiz, 500);
                                } else {
                                    debug('Next button not found or not visible');
                                }
                            }, 300);
                        }
                    }, 300);
                }
            }, 300);
        } else {
            debug('No hint button found, trying direct answer');
            // Direct answer selection if no hint button
            const inputs = currentQuestion.querySelectorAll('input');
            if (inputs.length > 0) {
                // Select first answer and continue
                inputs[0].checked = true;
                inputs[0].dispatchEvent(new Event('change', { bubbles: true }));
                
                setTimeout(() => {
                    const checkButton = currentQuestion.querySelector('.wpProQuiz_check');
                    if (checkButton) checkButton.click();
                    
                    setTimeout(() => {
                        const nextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
                        if (nextButton) nextButton.click();
                        
                        setTimeout(autoCompleteForceHintQuiz, 500);
                    }, 300);
                }, 300);
            }
        }
    }
})();
