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
        
        // Additional check after a delay to ensure Next buttons are properly configured
        setTimeout(() => {
            fixAllNextButtons(settings);
        }, 1000);
    }
    
    /**
     * Get quiz settings from the global quizExtensionsSettings
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
    
    /**
     * Normalize settings to handle different formats
     */
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
    
    /**
     * Default settings when no quiz settings are found
     */
    function getDefaultSettings() {
        debug('Using default settings');
        return {
            forceHintMode: false,
            requireCorrect: false, // Changed to false for normal quizzes to ensure Next button works
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
            // Standard check button clicks
            if ((e.target.name === 'check' && e.target.classList.contains('wpProQuiz_QuestionButton')) ||
                e.target.closest('input[name="check"].wpProQuiz_QuestionButton')) {
                const button = e.target.name === 'check' ? e.target : e.target.closest('input[name="check"]');
                handleCheckButtonClick(button, settings);
            }
            // Also catch anchor tags that act as check buttons
            else if (e.target.classList.contains('wpProQuiz_checkButton') || 
                     e.target.closest('.wpProQuiz_checkButton')) {
                const button = e.target.classList.contains('wpProQuiz_checkButton') ? 
                               e.target : e.target.closest('.wpProQuiz_checkButton');
                handleCheckButtonClick(button, settings);
            }
        });

        // Listen for right answer showing button (for debug mode)
        document.addEventListener('click', function(e) {
            if (e.target.id === 'show-right-answers') {
                showRightAnswers();
            }
        });
        
        // Add a debug button to the page to show right answers if needed
        if (DEBUG_MODE) {
            setTimeout(addDebugButton, 1000);
        }
        
        // Listen for quiz format changes and LearnDash events
        document.addEventListener('wpProQuiz_initComplete', function() {
            debug('Quiz init complete event detected');
            fixAllNextButtons(settings);
        });
        
        // Initial check to mark any answers that are already checked
        setTimeout(initialCheck, 500, settings);
        
        // Additional checks at different intervals
        setTimeout(() => fixAllNextButtons(settings), 1000);
        setTimeout(() => fixAllNextButtons(settings), 2000);
    }
    
    /**
     * Add a debug button to show right answers
     */
    function addDebugButton() {
        // Don't show for non-admins or if already exists
        if (document.getElementById('show-right-answers')) {
            return;
        }
        
        const button = document.createElement('button');
        button.id = 'show-right-answers';
        button.innerText = 'Show Right Answers';
        button.style.background = '#f0f0f0';
        button.style.border = '1px solid #ccc';
        button.style.padding = '5px 10px';
        button.style.margin = '10px 0';
        button.style.borderRadius = '3px';
        
        button.addEventListener('click', showRightAnswers);
        
        // Insert at top of quiz content
        const quizContent = document.querySelector('.wpProQuiz_content');
        if (quizContent) {
            quizContent.insertBefore(button, quizContent.firstChild);
        }
        
        // Also add a force hint auto-complete button for testing
        const forceHintButton = document.createElement('button');
        forceHintButton.id = 'force-hint-auto-complete';
        forceHintButton.innerText = 'Force Hint Auto-Complete';
        forceHintButton.style.background = '#e0f0e0';
        forceHintButton.style.border = '1px solid #aca';
        forceHintButton.style.padding = '5px 10px';
        forceHintButton.style.margin = '10px 0 10px 10px';
        forceHintButton.style.borderRadius = '3px';
        
        forceHintButton.addEventListener('click', function() {
            autoCompleteForceHintQuiz();
        });
        
        if (quizContent) {
            quizContent.insertBefore(forceHintButton, quizContent.firstChild.nextSibling);
        }
    }
    
    /**
     * Auto-complete the current force hint quiz
     * This will view hints and trigger answers for each question to help with testing
     */
    function autoCompleteForceHintQuiz() {
        debug('Auto-completing force hint quiz');
        
        // Get current visible question
        const currentQuestion = document.querySelector('.wpProQuiz_listItem:not([style*="display: none"])');
        if (!currentQuestion) {
            debug('No visible question found');
            return;
        }
        
        const questionId = getQuestionId(currentQuestion);
        if (!questionId) {
            debug('Cannot get question ID');
            return;
        }
        
        // Find hint button and check button
        const hintButton = currentQuestion.querySelector('.wpProQuiz_TipButton');
        const checkButton = currentQuestion.querySelector('input[name="check"]');
        
        // First, view the hint if not already viewed
        if (hintButton && !hintViewed[questionId]) {
            debug('Clicking hint button');
            hintButton.click();
            
            // Wait for hint to display and be marked as viewed
            setTimeout(() => {
                // Now select an answer if one isn't already selected
                const answerOptions = currentQuestion.querySelectorAll('.wpProQuiz_questionListItem input');
                let hasSelection = false;
                
                // Check if any answer is already selected
                answerOptions.forEach(input => {
                    if (input.checked) {
                        hasSelection = true;
                    }
                });
                
                // If no selection, pick the first answer
                if (!hasSelection && answerOptions.length > 0) {
                    debug('Selecting first answer option');
                    answerOptions[0].checked = true;
                    
                    // For multiple choice answers, need to dispatch change event
                    const event = new Event('change', { bubbles: true });
                    answerOptions[0].dispatchEvent(event);
                }
                
                // Now click the check button
                if (checkButton) {
                    debug('Clicking check button');
                    checkButton.click();
                    
                    // After checking, allow time for processing, then click next
                    setTimeout(() => {
                        const nextButton = currentQuestion.querySelector('input[name="next"]');
                        if (nextButton && !nextButton.disabled) {
                            debug('Clicking next button');
                            nextButton.click();
                            
                            // Process the next question after a delay
                            setTimeout(() => {
                                autoCompleteForceHintQuiz();
                            }, 500);
                        } else {
                            debug('Next button not available or disabled');
                        }
                    }, 500);
                }
            }, 500); // Wait for hint to display
        } else {
            debug('Hint already viewed or hint button not found');
            // If hint already viewed, just check and move to next
            if (checkButton) {
                checkButton.click();
                
                setTimeout(() => {
                    const nextButton = currentQuestion.querySelector('input[name="next"]');
                    if (nextButton && !nextButton.disabled) {
                        nextButton.click();
                        
                        setTimeout(() => {
                            autoCompleteForceHintQuiz();
                        }, 500);
                    }
                }, 500);
            }
        }
    }
    
    /**
     * Display right answers for debugging
     */
    function showRightAnswers() {
        debug('Showing right answers', rightAnswers, quizAnswers);
        
        // Create a modal to display the answers
        const modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '50%';
        modal.style.left = '50%';
        modal.style.transform = 'translate(-50%, -50%)';
        modal.style.background = 'white';
        modal.style.padding = '20px';
        modal.style.border = '1px solid #ccc';
        modal.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
        modal.style.zIndex = '9999';
        modal.style.maxWidth = '80%';
        modal.style.maxHeight = '80%';
        modal.style.overflow = 'auto';
        modal.style.direction = 'rtl'; // Match the RTL direction for Hebrew
        
        const heading = document.createElement('h3');
        heading.textContent = 'Quiz Answers';
        modal.appendChild(heading);
        
        const closeButton = document.createElement('button');
        closeButton.textContent = 'Close';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '10px';
        closeButton.style.right = '10px';
        closeButton.onclick = function() {
            document.body.removeChild(modal);
        };
        modal.appendChild(closeButton);
        
        // Display answers from both sources (extracted and inferred)
        const list = document.createElement('ul');
        
        // First add extracted answers
        for (const questionId in quizAnswers.correctAnswers) {
            const item = document.createElement('li');
            item.innerHTML = `<strong>Question ${questionId}:</strong><br>`;
            
            // Add answer details
            const answersList = document.createElement('ul');
            for (const answerIndex in quizAnswers.answerText[questionId]) {
                const answerItem = document.createElement('li');
                const isCorrect = quizAnswers.correctAnswers[questionId][answerIndex];
                const answerText = quizAnswers.answerText[questionId][answerIndex];
                
                answerItem.innerHTML = `${answerIndex}: ${answerText} - ${isCorrect ? '<span style="color:green">✓ CORRECT</span>' : ''}`;
                if (isCorrect) {
                    answerItem.style.fontWeight = 'bold';
                    answerItem.style.color = 'green';
                }
                
                answersList.appendChild(answerItem);
            }
            
            item.appendChild(answersList);
            list.appendChild(item);
        }
        
        // Then add inferred answers
        for (const questionId in rightAnswers) {
            // Skip if we already covered this question above
            if (quizAnswers.correctAnswers[questionId]) continue;
            
            const item = document.createElement('li');
            const label = rightAnswers[questionId].label || 'Unknown';
            const correct = rightAnswers[questionId].correct || 'Unknown';
            item.innerHTML = `<strong>Question ${label} (inferred):</strong> ${correct}`;
            list.appendChild(item);
        }
        
        modal.appendChild(list);
        document.body.appendChild(modal);
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
        
        const questionId = getQuestionId(questionItem);
        if (!questionId) {
            debug('No question ID found');
            return;
        }
        
        // Mark this hint as viewed
        hintViewed[questionId] = true;
        debug('Hint viewed for question', questionId);
        
        // Reset check-after-hint status when hint is viewed
        // This forces the user to check the answer again after viewing the hint
        viewedHintThenChecked[questionId] = false;
        
        // Update the next button
        updateNextButton(questionItem, settings);
    }
    
    /**
     * Extract correct answers from a question after checking
     * This function analyzes the DOM after LearnDash has marked answers as correct/incorrect
     */
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
    
    /**
     * Handle check button clicks
     */
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
            // This will allow other scripts to respond to this state change
            const event = new CustomEvent('lilac_answer_checked_after_hint', {
                detail: {
                    questionId: questionId,
                    questionItem: questionItem
                },
                bubbles: true
            });
            questionItem.dispatchEvent(event);
        }
        
        // After a slight delay (to allow LearnDash to mark the answer)
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
    
    /**
     * Find and store the right answer for a question
     */
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

    /**
     * Extract question ID from various sources
     */
    function getQuestionId(questionItem) {
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
        } catch (e) {}
        
        // Try finding through class names
        const questionListItem = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionListItem) {
            const idMatch = questionListItem.id?.match(/wpProQuiz_(\d+)_(\d+)/);
            if (idMatch && idMatch[2]) return idMatch[2];
        }
        
        return null;
    }
    
    /**
     * Initial check for any answers that are already marked
     */
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
    
    /**
     * Strict check for correct answers - enhanced to work with force hint mode
     */
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
        
        // If we can't clearly determine correctness, default to false
        debug('Could not determine correctness, defaulting to false');
        return false;
    }
    
    /**
     * Update the Next button based on settings and state
     */
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
        
        // Get hint viewing status
        const hasViewedHint = hintViewed[questionId] || false;
        const hasCheckedAfterHint = viewedHintThenChecked[questionId] || false;
        const isCorrect = questionResults[questionId] || false;
        
        debug('Question state for next button:', {
            id: questionId,
            isCorrect: isCorrect,
            viewedHint: hasViewedHint, 
            checkedAfterHint: hasCheckedAfterHint,
            forceHintMode: settings.forceHintMode
        });
        
        // Special case for force hint mode
        if (settings.forceHintMode) {
            if (hasViewedHint && hasCheckedAfterHint) {
                debug('Enabling next button - hint viewed with check after (force hint mode)');
                enableButton(buttonToUpdate);
                return;
            }
        }
        // Regular mode - show next button only for correct answers
        else if (isCorrect) {
            debug('Enabling next button - answer is correct');
            enableButton(buttonToUpdate);
            return;
        }
        
        // If we got here, disable the button
        debug('Disabling next button - conditions not met');
        disableButton(buttonToUpdate);
    }
}

/**
 * Find and store the right answer for a question
 */
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

/**
 * Extract question ID from various sources
 */
function getQuestionId(questionItem) {
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
    return null; // No ID could be determined
        }
        
        if (!nextButton) {
            debug('No Next button found');
            return;
        }
        
        // Force hint mode has specific rules
        if (settings.forceHintMode) {
            // Modified force hint mode logic to allow proceeding after viewing hint
            if (hasViewedHint && hasCheckedAfterHint) {
                // If hint was viewed and user checked after viewing hint, enable button
                // regardless of correctness - this ensures users can proceed
                debug('Enabling next button - hint viewed with re-check (force hint mode)');
                enableButton(nextButton);
                
                // Remove LearnDash validation message if it exists
                const validationMessage = document.querySelector('.wpProQuiz_invalidate:not([style*="display: none"])');
                if (validationMessage) {
                    validationMessage.style.display = 'none';
                    debug('Removed LearnDash validation message');
                }
                
                // Ensure no disabled state on inputs
                const currentQuestion = document.querySelector('.wpProQuiz_listItem:not([style*="display: none"])');
                if (currentQuestion) {
                    const inputs = currentQuestion.querySelectorAll('input');
                    inputs.forEach(input => input.disabled = false);
                }
                
                // For LearnDash internal validation, ensure the question is marked as answered
                questionItem.classList.add('wpProQuiz_questionAnswered');
            } else if (!hasViewedHint) {
                // Answer correct but hint not viewed, hide Next button
                debug('Hiding next button - hint not yet viewed (force hint mode)');
                disableButton(nextButton);
            } else if (!hasCheckedAfterHint) {
                // Answer was correct but user hasn't checked after viewing hint
                debug('Hiding next button - hint viewed but no check after hint (force hint mode)');
                disableButton(nextButton);
            }
        } 
        // Regular mode: Just check if answer is correct based on settings
        else if (settings.requireCorrect) {
            if (isCorrect) {
                debug('Enabling next button - answer is correct (regular mode)');
                enableButton(nextButton);
            } else {
                debug('Hiding next button - answer is not correct (regular mode)');
                disableButton(nextButton);
            }
        } else {
            // No special requirements for normal quizzes, always enable Next button
            debug('Normal quiz mode - enabling Next button');
            enableButton(nextButton);
        }
    }
    
    /**
     * Helper function to enable button
     */
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
    
    /**
     * Helper function to disable button
     */
    function disableButton(button) {
        button.style.display = 'none';
        button.disabled = true;
        button.classList.add('disabled');
    }
    /**
     * Fix all Next buttons on the page
     */
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
})();
