/**
 * Force Hint Mode Debugging Tool
 * 
 * This script identifies and fixes issues with LearnDash quiz Force Hint Mode.
 * It adds a debug overlay to show real-time state and provides comprehensive
 * logging to help troubleshoot the flow.
 */

(function() {
    // Debug constants
    const VERSION = "1.0.2";
    const FORCE_DEBUG_UI = true;
    
    // State tracking
    const state = {
        quizId: null,
        settings: null,
        questions: {},  // Store question data by ID
        hintViewed: {}, // Track hint viewed status by question ID
        answerSelected: {}, // Track answer selection by question ID
        buttonState: {}  // Track button state by question ID
    };
    
    // Log with prefix
    function log(message, ...args) {
        console.log(`[HintDebug ${VERSION}] ${message}`, ...args);
    }
    
    log("Debug script loaded");
    
    // Global debug controls - expose these to window for easy testing
    window.lilacDebug = {
        forceCorrect: false,      // Set to true to make all answers register as correct
        showDetails: true,        // Show detailed answer debugging
        override: function(value) {
            this.forceCorrect = value;
            console.log(`DEBUG OVERRIDE: ${value ? 'All answers will now be marked as correct' : 'Normal answer detection enabled'}`);
            return this.forceCorrect;
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('wpProQuiz_initComplete', init);
    setTimeout(init, 1000); // Backup initialization
    
    function init() {
        log("Initializing hint debug mode...");
        
        // Identify the quiz
        detectQuiz();
        
        // Setup watchers
        watchHintButtons();
        watchAnswerSelections();
        watchNextButtons();
        
        // Add UI
        if (FORCE_DEBUG_UI) {
            createDebugUI();
        }
        
        // Force re-run after a delay
        setTimeout(function() {
            log("Performing delayed initialization");
            detectQuiz();
            scanQuestions();
        }, 2000);
    }
    
    // Detect the current quiz ID and settings
    function detectQuiz() {
        log("Detecting quiz");
        
        // Method 1: From global settings
        if (window.quizExtensionsSettings?.current_quiz_id) {
            state.quizId = window.quizExtensionsSettings.current_quiz_id;
            log(`Quiz ID found from settings: ${state.quizId}`);
            
            // Get quiz settings
            if (window.quizExtensionsSettings?.quiz_options?.[state.quizId]) {
                state.settings = window.quizExtensionsSettings.quiz_options[state.quizId];
                log("Quiz settings:", state.settings);
            }
        }
        
        // Method 2: From DOM
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (quizContainer && quizContainer.hasAttribute('data-quiz-meta')) {
            try {
                const meta = JSON.parse(quizContainer.getAttribute('data-quiz-meta'));
                if (meta.quiz_post_id) {
                    log(`Quiz post ID found from DOM: ${meta.quiz_post_id}`);
                    if (!state.quizId) state.quizId = meta.quiz_post_id;
                }
            } catch (e) {
                log("Error parsing quiz meta", e);
            }
        }
        
        // Scan all questions
        scanQuestions();
    }
    
    // Scan for questions in the DOM
    function scanQuestions() {
        log("Scanning for questions");
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        
        log(`Found ${questionItems.length} questions`);
        
        questionItems.forEach((questionItem, index) => {
            const questionData = {
                element: questionItem,
                index: index,
                id: null,
                postId: null,
                proId: null,
                hintButton: null,
                checkButton: null,
                nextButton: null,
                hasHintViewed: false,
                hasSelectedAnswer: false
            };
            
            // Extract ID from data-post-id
            if (questionItem.hasAttribute('data-post-id')) {
                questionData.postId = questionItem.getAttribute('data-post-id');
                questionData.id = questionData.postId;
                log(`Question ${index} has post ID: ${questionData.postId}`);
            }
            
            // Extract data from data-question-meta
            if (questionItem.hasAttribute('data-question-meta')) {
                try {
                    const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                    if (meta.question_post_id) {
                        questionData.postId = meta.question_post_id;
                        if (!questionData.id) questionData.id = questionData.postId;
                        log(`Question ${index} has post ID from meta: ${questionData.postId}`);
                    }
                    if (meta.question_pro_id) {
                        questionData.proId = meta.question_pro_id;
                        if (!questionData.id) questionData.id = questionData.proId;
                        log(`Question ${index} has pro ID: ${questionData.proId}`);
                    }
                } catch (e) {
                    log(`Error parsing question meta for question ${index}`, e);
                }
            }
            
            // Find hint button
            const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
            if (hintButton) {
                questionData.hintButton = hintButton;
                log(`Question ${index} has hint button`);
            }
            
            // Find check button
            const checkButton = questionItem.querySelector('input[name="check"]');
            if (checkButton) {
                questionData.checkButton = checkButton;
                log(`Question ${index} has check button`);
            }
            
            // Find next button
            const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
            if (nextButton) {
                questionData.nextButton = nextButton;
                log(`Question ${index} has next button`);
            }
            
            // If we found an ID, store the question data
            if (questionData.id) {
                state.questions[questionData.id] = questionData;
                log(`Question data for ID ${questionData.id}:`, questionData);
            } else {
                log(`WARNING: Could not determine ID for question ${index}`);
            }
        });
    }
    
    // Watch hint buttons for clicks
    function watchHintButtons() {
        log("Setting up hint button watchers");
        
        document.addEventListener('click', function(event) {
            // Check if this is a hint button
            if (event.target.classList.contains('wpProQuiz_TipButton') || 
                event.target.closest('.wpProQuiz_TipButton')) {
                
                log("Hint button clicked");
                
                // Find the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (!questionItem) return;
                
                // Find the question ID
                let questionId = null;
                
                // Method 1: From data-post-id
                if (questionItem.hasAttribute('data-post-id')) {
                    questionId = questionItem.getAttribute('data-post-id');
                    log(`Hint clicked for question with post ID: ${questionId}`);
                }
                
                // Method 2: From data-question-meta
                if (!questionId && questionItem.hasAttribute('data-question-meta')) {
                    try {
                        const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                        if (meta.question_post_id) {
                            questionId = meta.question_post_id;
                            log(`Hint clicked for question with post ID from meta: ${questionId}`);
                        } else if (meta.question_pro_id) {
                            questionId = meta.question_pro_id;
                            log(`Hint clicked for question with pro ID: ${questionId}`);
                        }
                    } catch (e) {
                        log("Error parsing question meta", e);
                    }
                }
                
                if (questionId) {
                    // Record that hint was viewed
                    state.hintViewed[questionId] = true;
                    
                    if (state.questions[questionId]) {
                        state.questions[questionId].hasHintViewed = true;
                    }
                    
                    log(`Hint viewed for question ${questionId}`);
                    updateDebugUI();
                    
                    // Force enable the next button after hint view
                    setTimeout(function() {
                        enableNextButton(questionItem, questionId);
                        
                        // Add visual indicator
                        addHintViewedIndicator(questionItem);
                    }, 500);
                } else {
                    log("WARNING: Could not determine question ID for hint button click");
                }
            }
        });
        
        // Also watch for hint display via DOM mutation
        watchHintDisplay();
    }
    
    // Watch for hint display via DOM changes
    function watchHintDisplay() {
        log("Setting up hint display watcher");
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check if this is related to hint display
                if (mutation.type === 'attributes' && 
                    mutation.target.classList && 
                    mutation.target.classList.contains('wpProQuiz_tipp')) {
                    
                    if (mutation.attributeName === 'style' && 
                        window.getComputedStyle(mutation.target).display !== 'none') {
                        
                        log("Hint displayed via DOM change");
                        
                        // Find the question item
                        const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                        if (!questionItem) return;
                        
                        // Find the question ID
                        let questionId = null;
                        
                        // Method 1: From data-post-id
                        if (questionItem.hasAttribute('data-post-id')) {
                            questionId = questionItem.getAttribute('data-post-id');
                            log(`Hint displayed for question with post ID: ${questionId}`);
                        }
                        
                        // Method 2: From data-question-meta
                        if (!questionId && questionItem.hasAttribute('data-question-meta')) {
                            try {
                                const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                                if (meta.question_post_id) {
                                    questionId = meta.question_post_id;
                                    log(`Hint displayed for question with post ID from meta: ${questionId}`);
                                } else if (meta.question_pro_id) {
                                    questionId = meta.question_pro_id;
                                    log(`Hint displayed for question with pro ID: ${questionId}`);
                                }
                            } catch (e) {
                                log("Error parsing question meta", e);
                            }
                        }
                        
                        if (questionId) {
                            // Record that hint was viewed
                            state.hintViewed[questionId] = true;
                            
                            if (state.questions[questionId]) {
                                state.questions[questionId].hasHintViewed = true;
                            }
                            
                            log(`Hint displayed for question ${questionId}`);
                            updateDebugUI();
                            
                            // Force enable the next button after hint view
                            setTimeout(function() {
                                enableNextButton(questionItem, questionId);
                                
                                // Add visual indicator
                                addHintViewedIndicator(questionItem);
                            }, 500);
                        } else {
                            log("WARNING: Could not determine question ID for hint display");
                        }
                    }
                }
            });
        });
        
        // Start observing the document with the configured parameters
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
                log("Answer item clicked");

                if (questionItem) {
                    log(`Answer selected for question with post ID: ${questionId}`);
                    updateAnswerSelection(questionItem, questionId);

                    // Add immediate visual feedback about answer correctness
                    setTimeout(() => {
                        const isCorrect = isAnswerCorrect(questionItem);
                        log(`ANSWER CORRECTNESS CHECK: Question ${questionId} - Answer is ${isCorrect ? 'CORRECT ' : 'INCORRECT '}`);

                        // Update debug UI with answer correctness
                        updateDebugUI();

                        // Add visual indicator to debug UI by background color
                        const questionStateDiv = document.querySelector(`#hint-debug-content [data-question-id="${questionId}"]`);
                        if (questionStateDiv) {
                            if (isCorrect) {
                                questionStateDiv.style.backgroundColor = 'rgba(0, 255, 0, 0.2)';
                                questionStateDiv.innerHTML += '<div style="color: #5cff5c; font-weight: bold;"> CORRECT ANSWER SELECTED!</div>';
                            } else {
                                questionStateDiv.style.backgroundColor = '';
                                // Remove any previous correctness indicators
                                const existingIndicator = questionStateDiv.querySelector('div[style*="color: #5cff5c"]');
                                if (existingIndicator) {
                                    existingIndicator.remove();
                                }
                            }
                }
                
                // Method 2: From data-question-meta
                if (!questionId && questionItem.hasAttribute('data-question-meta')) {
                    try {
                        const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                        if (meta.question_post_id) {
                            questionId = meta.question_post_id;
                            log(`Answer selected for question with post ID from meta: ${questionId}`);
                        } else if (meta.question_pro_id) {
                            questionId = meta.question_pro_id;
                            log(`Answer selected for question with pro ID: ${questionId}`);
                        }
                    } catch (e) {
                        log("Error parsing question meta", e);
                    }
                }
                
                if (questionId) {
                    // Record that answer was selected
                    state.answerSelected[questionId] = true;
                    
                    if (state.questions[questionId]) {
                        state.questions[questionId].hasSelectedAnswer = true;
                    }
                    
                    log(`Answer selected for question ${questionId}`);
                    updateDebugUI();
                    
                    // Check if the answer is correct
                    setTimeout(function() {
                        // Look for the correct answer indicators
                        const isCorrect = isAnswerCorrect(questionItem);
                        
                        // Add visual indicator for answer correctness
                        log(`ANSWER CHECK: Question ${questionId} - Answer is ${isCorrect ? 'CORRECT ✓' : 'INCORRECT ✗'}`);
                        
                        // Add enhanced visual indicator to the question element
                        // Remove any existing indicators first
                        const existingIndicators = questionItem.querySelectorAll('.lilac-answer-indicator');
                        existingIndicators.forEach(el => el.remove());
                        
                        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked');
                        for (let i = 0; i < selectedItems.length; i++) {
                            const listItem = selectedItems[i].closest('.wpProQuiz_questionListItem');
                            if (listItem) {
                                // Create enhanced feedback indicator
                                const indicator = document.createElement('div');
                                indicator.className = 'lilac-answer-indicator';
                                
                                if (isCorrect) {
                                    indicator.innerHTML = '<span style="font-size: 18px;">✓</span> <span>תשובה נכונה זוהתה</span>';
                                    indicator.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                                    indicator.style.borderLeft = '4px solid #4CAF50';
                                    indicator.style.color = '#2e7d32';
                                } else {
                                    indicator.innerHTML = '<span style="font-size: 18px;">✗</span> <span>תשובה לא נכונה</span>';
                                    indicator.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
                                    indicator.style.borderLeft = '4px solid #F44336';
                                    indicator.style.color = '#c62828';
                                }
                                
                                // Style the indicator
                                indicator.style.padding = '10px 15px';
                                indicator.style.margin = '8px 0';
                                indicator.style.borderRadius = '0 3px 3px 0';
                                indicator.style.fontWeight = 'bold';
                                indicator.style.direction = 'rtl';
                                indicator.style.textAlign = 'right';
                                indicator.style.fontFamily = 'inherit';
                                
                                listItem.appendChild(indicator);
                            }
                        }
                        
                        // Update debug UI with answer correctness
                        const questionStateDiv = document.querySelector(`#hint-debug-content .question-state-item[data-question-id="${questionId}"]`);
                        if (questionStateDiv) {
                            // Remove any previous indicators
                            const existingIndicators = questionStateDiv.querySelectorAll('.correctness-indicator');
                            existingIndicators.forEach(el => el.remove());
                            
                            // Add new indicator
                            const indicatorDiv = document.createElement('div');
                            indicatorDiv.className = 'correctness-indicator';
                            indicatorDiv.style.padding = '3px';
                            indicatorDiv.style.marginTop = '5px';
                            indicatorDiv.style.fontWeight = 'bold';
                            indicatorDiv.style.borderRadius = '3px';
                            
                            if (isCorrect) {
                                indicatorDiv.textContent = '✓ CORRECT ANSWER SELECTED!';
                                indicatorDiv.style.backgroundColor = 'rgba(0, 255, 0, 0.2)';
                                indicatorDiv.style.color = '#00bb00';
                                questionStateDiv.style.borderColor = '#00aa00';
                            } else {
                                indicatorDiv.textContent = '✗ INCORRECT ANSWER SELECTED';
                                indicatorDiv.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                                indicatorDiv.style.color = '#bb0000';
                                questionStateDiv.style.borderColor = '';
                            }
                            
                            questionStateDiv.appendChild(indicatorDiv);
                        }
                        
                        if (isCorrect) {
                            // If answer is correct, enable next button regardless of hint status
                            log(`Correct answer selected for question ${questionId}, enabling next button`);
                            enableNextButton(questionItem, questionId);
                        } else if (state.hintViewed[questionId]) {
                            // If answer is wrong but hint was viewed, enable next button
                            log(`Hint was previously viewed for question ${questionId}, enabling next button`);
                            enableNextButton(questionItem, questionId);
                        } else {
                            // If answer is wrong and hint not viewed, force display the hint warning
                            log(`Incorrect answer and hint not viewed for question ${questionId}, showing warning`);
                            addHintRequiredWarning(questionItem);
                        }
                    }, 500); // Give time for LearnDash to process the answer
                } else {
                    log("WARNING: Could not determine question ID for answer selection");
                }
            }
        });
        
        // Watch for clicks on check button
        document.addEventListener('click', function(event) {
            if (event.target.name === 'check' && event.target.type === 'button') {
                log("Check button clicked");
                
                // Find the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (!questionItem) return;
                
                // Find the question ID
                let questionId = null;
                
                // Method 1: From data-post-id
                if (questionItem.hasAttribute('data-post-id')) {
                    questionId = questionItem.getAttribute('data-post-id');
                    log(`Check button clicked for question with post ID: ${questionId}`);
                }
                
                // Method 2: From data-question-meta
                if (!questionId && questionItem.hasAttribute('data-question-meta')) {
                    try {
                        const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                        if (meta.question_post_id) {
                            questionId = meta.question_post_id;
                            log(`Check button clicked for question with post ID from meta: ${questionId}`);
                        } else if (meta.question_pro_id) {
                            questionId = meta.question_pro_id;
                            log(`Check button clicked for question with pro ID: ${questionId}`);
                        }
                    } catch (e) {
                        log("Error parsing question meta", e);
                    }
                }
                
                if (questionId) {
                    // Record that answer was checked
                    state.answerSelected[questionId] = true;
                    
                    if (state.questions[questionId]) {
                        state.questions[questionId].hasSelectedAnswer = true;
                    }
                    
                    log(`Answer checked for question ${questionId}`);
                    updateDebugUI();
                    
                    // Check for answer correctness and hint view status
                    setTimeout(function() {
                        // Check if the answer is correct
                        const isCorrect = isAnswerCorrect(questionItem);
                        
                        if (isCorrect) {
                            // If answer is correct, enable next button regardless of hint status
                            log(`Correct answer selected for question ${questionId}, enabling next button`);
                            enableNextButton(questionItem, questionId);
                        } else if (state.hintViewed[questionId]) {
                            // If answer is wrong but hint was viewed, enable next button
                            log(`Hint was previously viewed for question ${questionId}, enabling next button`);
                            enableNextButton(questionItem, questionId);
                        } else {
                            // If answer is wrong and hint not viewed, force display the hint warning
                            log(`Incorrect answer and hint not viewed for question ${questionId}, showing warning`);
                            addHintRequiredWarning(questionItem);
                        }
                    }, 500); // Give time for LearnDash to process the answer
                } else {
                    log("WARNING: Could not determine question ID for check button click");
                }
            }
        });
    }
    
    // Watch for changes to next buttons
    function watchNextButtons() {
        log("Setting up next button watchers");
        
        // Check if any other scripts are disabling the next button
        setInterval(function() {
            // For each question we know about
            Object.keys(state.questions).forEach(function(questionId) {
                const questionData = state.questions[questionId];
                
                // If hint was viewed and answer was selected, make sure next button is enabled
                if (questionData.hasHintViewed && questionData.hasSelectedAnswer) {
                    const questionItem = questionData.element;
                    const nextButton = questionData.nextButton || questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
                    
                    if (nextButton && nextButton.disabled) {
                        log(`Next button was disabled by another script for question ${questionId}, re-enabling`);
                        enableNextButton(questionItem, questionId);
                    }
                }
            });
        }, 1000);
    }
    
    // Add indicator that hint was viewed
    function addHintViewedIndicator(questionItem) {
        // If indicator already exists, don't add another
        if (questionItem.querySelector('.hint-viewed-indicator')) return;
        
        // Create indicator
        const indicator = document.createElement('div');
        indicator.className = 'hint-viewed-indicator';
        indicator.textContent = 'רמז נצפה';
        indicator.style.cssText = 'color: #ff9800; font-weight: bold; padding: 10px; margin: 10px 0; font-size: 14px; text-align: right; background-color: rgba(255, 152, 0, 0.1); border-right: 4px solid #ff9800;';
        
        // Insert indicator
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList) {
            questionList.appendChild(indicator);
        }
    }
    
    // Add warning that hint is required
    function addHintRequiredWarning(questionItem) {
        // If warning already exists, don't add another
        if (questionItem.querySelector('.hint-required-warning')) return;
        
        // Check if hint button exists
        const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
        if (!hintButton) return;
        
        // Create warning
        const warning = document.createElement('div');
        warning.className = 'hint-required-warning';
        warning.textContent = 'התשובה שלך שגויה. אנא לחץ על כפתור הרמז';
        warning.style.cssText = 'color: #f44336; font-weight: bold; padding: 10px; margin: 10px 0; font-size: 14px; text-align: right; background-color: rgba(244, 67, 54, 0.1); border-right: 4px solid #f44336;';
        
        // Insert warning
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList) {
            questionList.appendChild(warning);
        }
        
        // Highlight the hint button
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
    
    // Enable the next button for a question
    function enableNextButton(questionItem, questionId) {
        // Try to find the next button
        let nextButton = questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_QuestionButton[name=next]');
        
        // If not found in the question, look for global next button
        if (!nextButton) {
            nextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        }
        
        if (nextButton) {
            log(`Enabling next button for question ${questionId}`);
            
            // Enable button
            nextButton.disabled = false;
            nextButton.removeAttribute('disabled');
            
            // Update styles
            nextButton.classList.remove('wpProQuiz_button_disabled');
            nextButton.style.opacity = '1';
            nextButton.style.cursor = 'pointer';
            
            // Track state
            state.buttonState[questionId] = true;
            updateDebugUI();
        } else {
            log(`WARNING: Could not find next button for question ${questionId}`);
        }
    }
    
    // Check if the selected answer is correct
    function isAnswerCorrect(questionItem) {
        console.log("-------------------------");
        console.log("🔍 CHECKING ANSWER CORRECTNESS");
        console.log("-------------------------");
        log("Checking if answer is correct");
        
        // Log the question content for debugging
        const questionText = questionItem.querySelector('.wpProQuiz_question_text')?.textContent || 'Unknown question';
        console.log(`Question text: ${questionText.substring(0, 50)}...`);
        
        // Method 1: Check for selected answers
        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked');
        console.log(`Selected items count: ${selectedItems.length}`);
        
        // Early return if no answer is selected
        if (selectedItems.length === 0) {
            console.log("❌ No answer selected");
            return false;
        }
        
        // Dump all possible data to console for analysis
        console.log("ANSWER DETAILS:");
        for (let i = 0; i < selectedItems.length; i++) {
            const input = selectedItems[i];
            const listItem = input.closest('.wpProQuiz_questionListItem');
            
            if (!listItem) {
                console.log(`Selected item ${i+1}: No parent list item found`);
                continue;
            }
            
            console.log(`Selected item ${i+1}:`);
            console.log(`- Text: ${listItem.textContent.trim().substring(0, 50)}...`);
            console.log(`- Value: ${input.value}`);
            console.log(`- Input type: ${input.type}`);
            console.log(`- Input ID: ${input.id}`);
            console.log(`- Input name: ${input.name}`);
            
            // Log all classes on the list item
            console.log(`- List item classes: ${[...listItem.classList].join(', ')}`);
            
            // Log all data attributes
            console.log("- Data attributes:");
            Array.from(listItem.attributes)
                .filter(attr => attr.name.startsWith('data-'))
                .forEach(attr => {
                    console.log(`  - ${attr.name}: ${attr.value}`);
                });
            
            Array.from(input.attributes)
                .filter(attr => attr.name.startsWith('data-'))
                .forEach(attr => {
                    console.log(`  - Input ${attr.name}: ${attr.value}`);
                });
            
            // Add visible indicators of correctness check
            console.log("Checking class indicators:");
            const classIndicators = {
                'wpProQuiz_answerCorrect': listItem.classList.contains('wpProQuiz_answerCorrect'),
                'correct': listItem.classList.contains('correct'),
                'is-correct': listItem.classList.contains('is-correct'),
                'answer-correct': listItem.classList.contains('answer-correct')
            };
            
            console.log(classIndicators);
            
            // Check for class indicators
            if (classIndicators.wpProQuiz_answerCorrect || 
                classIndicators.correct || 
                classIndicators.is_correct ||
                classIndicators.answer_correct) {
                console.log("%c✅ DETECTION METHOD 1: Found correct answer class!", "background: #4CAF50; color: white; padding: 3px 6px; border-radius: 3px;");
                log("Found answer with correct answer class");
                return true;
            }
            
            // Check for data attributes
            console.log("Checking data attributes:");
            const dataIndicators = {
                'listItem data-correct': listItem.getAttribute('data-correct'),
                'input data-correct': input.getAttribute('data-correct'),
                'listItem data-is-correct': listItem.getAttribute('data-is-correct'),
                'input data-is-correct': input.getAttribute('data-is-correct')
            };
            
            console.log(dataIndicators);
            
            if (dataIndicators['listItem data-correct'] === '1' ||
                dataIndicators['input data-correct'] === '1' ||
                dataIndicators['listItem data-is-correct'] === 'true' ||
                dataIndicators['listItem data-is-correct'] === '1' ||
                dataIndicators['input data-is-correct'] === 'true' ||
                dataIndicators['input data-is-correct'] === '1') {
                console.log("%c✅ DETECTION METHOD 2: Found correct data attribute!", "background: #2196F3; color: white; padding: 3px 6px; border-radius: 3px;");
                log("Found answer with correct data attribute");
                return true;
            }
            
            // Check aria attributes
            console.log("Checking aria attributes:");
            const ariaLabel = {
                'listItem': listItem.getAttribute('aria-label'),
                'input': input.getAttribute('aria-label')
            };
            
            console.log(ariaLabel);
            
            if ((ariaLabel.listItem && ariaLabel.listItem.includes('correct')) ||
                (ariaLabel.input && ariaLabel.input.includes('correct'))) {
                console.log("%c✅ DETECTION METHOD 3: Found correct in ARIA label!", "background: #9C27B0; color: white; padding: 3px 6px; border-radius: 3px;");
                log("Found answer with correct in aria-label");
                return true;
            }
        }
        
        // Method 2: Check LearnDash's internal data
        console.log("\nChecking LearnDash internal data:");
        try {
            // Get quiz and question IDs
            const proQuizId = questionItem.closest('.wpProQuiz_content')?.id?.replace('wpProQuiz_', '');
            const questionId = questionItem.querySelector('.wpProQuiz_questionList')?.getAttribute('data-question_id');
            
            console.log(`Quiz ID: ${proQuizId}`);
            console.log(`Question ID: ${questionId}`);
            console.log(`wpProQuizInitList available: ${!!window.wpProQuizInitList}`);
            
            if (window.wpProQuizInitList && proQuizId && questionId) {
                // Dump the full quiz data structure to console
                console.log("LearnDash Quiz Data Structure:");
                console.log(window.wpProQuizInitList[proQuizId]);
                
                const quizData = window.wpProQuizInitList[proQuizId];
                if (quizData) {
                    console.log(`Quiz data found for ID ${proQuizId}`);              
                    console.log(`JSON data available: ${!!quizData.json}`);
                    console.log(`Debug mode enabled: ${quizData.json?.ld_script_debug === true}`);
                    
                    if (quizData.json && quizData.json.ld_script_debug === true) {
                        console.log("Quiz has debug mode enabled, checking internal answer data");
                        
                        // Show cat results if available
                        if (quizData.catResults) {
                            console.log("Category results data:");
                            console.log(quizData.catResults);
                        }
                        
                        // Check if user's answer matches correct answer
                        const userAnswers = quizData.catResults?.[questionId]?.results?.json;
                        if (userAnswers) {
                            console.log("User answer data found:");
                            console.log(userAnswers);
                            
                            const isAnswerRight = userAnswers.correct === true;
                            console.log(`%c✅ DETECTION METHOD 4: Internal LearnDash data shows answer is ${isAnswerRight ? 'CORRECT' : 'INCORRECT'}`, `background: #FF9800; color: white; padding: 3px 6px; border-radius: 3px;`);
                            log(`Internal data shows answer is ${isAnswerRight ? 'correct' : 'incorrect'}`);
                            return isAnswerRight;
                        } else {
                            console.log("❌ No user answer data found in quiz data");
                        }
                    }
                }
            }
        } catch (e) {
            console.log("Error checking LearnDash internal answer data:", e);
            log("Error checking LearnDash internal answer data", e);
        }
        
        // Method 3: Look for visual indicators in the response
        console.log("\nChecking response indicators:");
        const responseCorrect = questionItem.querySelector('.wpProQuiz_response .wpProQuiz_correct, .learndash-correct-response');
        console.log(`Response indicator found: ${!!responseCorrect}`);
        
        if (responseCorrect) {
            const isVisible = window.getComputedStyle(responseCorrect).display !== 'none';
            console.log(`Response indicator visible: ${isVisible}`);
            
            if (isVisible) {
                console.log("%c✅ DETECTION METHOD 5: Found visible correct response element!", "background: #673AB7; color: white; padding: 3px 6px; border-radius: 3px;");
                log("Found visible correct response element");
                return true;
            }
        }
        
        // Method 4: Check for any visual feedback indicators
        console.log("\nChecking for check button:");
        const checkButton = questionItem.querySelector('.wpProQuiz_questionList .wpProQuiz_button[name="check"]');
        console.log(`Check button found: ${!!checkButton}`);
        
        if (checkButton) {
            const isEnabled = !checkButton.disabled && checkButton.style.display !== 'none';
            console.log(`Check button enabled: ${isEnabled}`);
            
            if (isEnabled) {
                console.log("Check button is available - possibly need to check first");
                log("Check button is available - need to check answer first");
                
                // Check for auto-check settings
                const quizSettings = getQuizSettings() || {};
                console.log("Quiz settings:", quizSettings);
                
                if (quizSettings.autoShowHint === true && quizSettings.requireCorrect === true) {
                    console.log("Auto-checking answer since auto-show is enabled");
                    log("Auto-checking answer since auto-show is enabled");
                    try {
                        checkButton.click();
                        console.log("✅ Auto-check applied - please retry correctness check");
                    } catch (e) {
                        console.log("Error auto-checking answer", e);
                        log("Error auto-checking answer", e);
                    }
                }
            }
        }
        
        // Method 5: Check for selection-loop interference
        console.log("\nChecking for selection-loop script interference:");
        const selectionLockControls = document.getElementById('psloop-controls');
        console.log(`Selection lock controls found: ${!!selectionLockControls}`);
        
        if (selectionLockControls) {
            console.log("Selection lock controls may be interfering with answer detection");
            console.log("Try using the 'Toggle Force Mode' or 'Unlock Current Question' buttons");
            
            // Create a manual override option for testing
            console.log("\n⚠️ MANUAL OVERRIDE AVAILABLE: Set window.lilacDebugForceCorrect = true; in console to force answers to be correct");
        }
        
        // Manual override for testing
        if (window.lilacDebug && window.lilacDebug.forceCorrect === true) {
            console.log("%c✅ DEBUG OVERRIDE: Forcing answer to be correct", "background: #4CAF50; color: white; padding: 2px 5px; border-radius: 3px;");
            log("Debug mode: Forcing answer to be correct");
            return true;
        }
        
        // Final check for conflict with other scripts
        console.log("\nFinal check for script conflicts:");
        const scriptConflicts = [
            { name: 'prevent-selection-loop.js', found: !!window.preventSelectionLoopData },
            { name: 'strict-next-button.js', found: !!window.strictNextButtonController }
        ];
        
        console.log("Possible conflicting scripts:", scriptConflicts);
        scriptConflicts.forEach(script => {
            if (script.found) {
                console.log(`%c⚠️ Potential conflict with ${script.name}`, "background: #FFC107; color: #333; padding: 3px 6px; border-radius: 3px; font-weight: bold;");
                console.log(`This script may interfere with our answer detection. Try toggling Force Correct Mode.`);
            }
        });
        
        // If we couldn't definitively find a correct answer, default to false
        console.log("%c❌ NO CORRECT ANSWER INDICATORS FOUND", "background: #F44336; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold;");
        console.log("-------------------------");
        log("No correct answer indicators found");
        return false;
    }
    
    // Create debug UI
    function createDebugUI() {
        log("Creating debug UI");
        
        // If debug UI already exists, don't create it again
        if (document.getElementById('hint-debug-ui')) return;
        
        // Create the debug UI container
        const debugUI = document.createElement('div');
        debugUI.id = 'hint-debug-ui';
        debugUI.style.cssText = 'position: fixed; bottom: 10px; right: 10px; width: 300px; max-height: 80vh; overflow-y: auto; background: white; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: Arial, sans-serif; font-size: 12px; z-index: 99999; display: none;';
        
        // Create header
        const header = document.createElement('div');
        header.style.cssText = 'background: #f5f5f5; padding: 5px 10px; border-bottom: 1px solid #ccc; display: flex; justify-content: space-between; align-items: center;';
        
        // Title
        const title = document.createElement('div');
        title.textContent = 'Force Hint Debug';
        title.style.fontWeight = 'bold';
        header.appendChild(title);
        
        // Controls
        const controls = document.createElement('div');
        
        // Hide button
        const hideButton = document.createElement('button');
        hideButton.textContent = 'X';
        hideButton.style.cssText = 'background: none; border: none; cursor: pointer; font-weight: bold;';
        hideButton.addEventListener('click', function() {
            debugUI.style.display = 'none';
        });
        controls.appendChild(hideButton);
        
        header.appendChild(controls);
        debugUI.appendChild(header);
        
        // Create content area
        const content = document.createElement('div');
        content.id = 'hint-debug-content';
        content.style.cssText = 'padding: 10px; overflow-y: auto;';
        debugUI.appendChild(content);
        
        // Add controls panel for global actions
        const globalControls = document.createElement('div');
        globalControls.style.cssText = 'padding: 5px 10px; border-top: 1px solid #ccc; background: #f5f5f5;';
        
        // Add toggle for manual override
        const overrideToggle = document.createElement('button');
        overrideToggle.textContent = 'Toggle Force Correct Mode';
        overrideToggle.style.cssText = 'width: 100%; padding: 8px; margin: 8px 0; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px; font-weight: bold;';
        overrideToggle.addEventListener('click', function() {
            window.lilacDebug.forceCorrect = !window.lilacDebug.forceCorrect;
            this.style.backgroundColor = window.lilacDebug.forceCorrect ? '#e8f5e9' : '#f0f0f0';
            this.style.color = window.lilacDebug.forceCorrect ? '#2e7d32' : '#000';
            this.style.borderColor = window.lilacDebug.forceCorrect ? '#4CAF50' : '#ccc';
            this.textContent = window.lilacDebug.forceCorrect ? '✓ Force Correct Mode ENABLED' : 'Toggle Force Correct Mode';
            log(`Forced correct mode ${window.lilacDebug.forceCorrect ? 'enabled' : 'disabled'}`); 
            
            // Show prominent console message
            if (window.lilacDebug.forceCorrect) {
                console.log('%c⚠️ QUIZ DEBUG MODE ENABLED: All answers will be treated as correct', 'background: #4CAF50; color: white; padding: 5px; font-size: 14px; border-radius: 3px;');
                console.log('%cTo disable, click the debug panel button or run window.lilacDebug.override(false) in console', 'font-style: italic;');
            } else {
                console.log('%c✓ Quiz debug mode disabled. Normal answer detection resumed.', 'color: #4CAF50; font-weight: bold;');
            }
            
            updateDebugUI();
        });
        globalControls.appendChild(overrideToggle);
        
        debugUI.appendChild(globalControls);
        
        // Add to body
        document.body.appendChild(debugUI);
        
        // Create toggle button
        const showButton = document.createElement('button');
        showButton.id = 'hint-debug-toggle';
        showButton.textContent = 'FH Debug';
        showButton.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: #4285f4; color: white; border: none; border-radius: 3px; padding: 5px 10px; cursor: pointer; font-family: Arial, sans-serif; font-size: 12px; z-index: 999999;';
        showButton.addEventListener('click', function() {
            debugUI.style.display = 'block';
            showButton.remove();
        });
        document.body.appendChild(showButton);

        // Add additional debug controls
        const buttonsDiv = document.createElement('div');
        buttonsDiv.style.cssText = 'display: flex; gap: 5px; margin-top: 10px;';
        
        // Create hide debug button
        const hideDebugButton = document.createElement('button');
        hideDebugButton.textContent = 'Hide Debug';
        hideDebugButton.style.cssText = 'padding: 3px 8px; font-size: 12px; cursor: pointer;';
        hideDebugButton.addEventListener('click', function() {
            debugUI.style.display = 'none';
            document.body.appendChild(showButton);
        });
        buttonsDiv.appendChild(hideDebugButton);
        
        // Force Enable button
        const forceEnableButton = document.createElement('button');
        forceEnableButton.textContent = 'Force Enable';
        forceEnableButton.style.cssText = 'padding: 3px 8px; font-size: 12px; cursor: pointer;';
        forceEnableButton.addEventListener('click', function() {
            // Enable all next buttons
            const nextButtons = document.querySelectorAll('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
            nextButtons.forEach(button => {
                button.disabled = false;
                button.removeAttribute('disabled');
                button.style.opacity = '1';
                button.style.cursor = 'pointer';
            });
            
            log("All next buttons forcibly enabled");
        });
        buttonsDiv.appendChild(forceEnableButton);
        
        debugUI.appendChild(buttonsDiv);
        
        // Add to page
        document.body.appendChild(debugUI);
        
        // Initial update
        updateDebugUI();
    }
    
    // Update debug UI
    function updateDebugUI() {
        const content = document.getElementById('hint-debug-content');
        if (!content) return;
        
        // Clear content
        content.innerHTML = '';
        
        // Add quiz info
        const quizInfo = document.createElement('div');
        quizInfo.innerHTML = `
            <div style="margin-bottom: 5px;"><b>Quiz ID:</b> ${state.quizId || 'Unknown'}</div>
            <div style="margin-bottom: 5px;"><b>Force Hint:</b> ${state.settings?.force_hint_mode ? 'Yes' : 'No'}</div>
            <div style="margin-bottom: 5px;"><b>Questions:</b> ${Object.keys(state.questions).length}</div>
        `;
        content.appendChild(quizInfo);
        
        // Add question state
        const questionState = document.createElement('div');
        questionState.innerHTML = '<div style="margin: 10px 0 5px; font-weight: bold;">Question State:</div>';
        
        // For each question
        Object.keys(state.questions).forEach(id => {
            const q = state.questions[id];
            const hintViewed = state.hintViewed[id] ? '✅' : '❌';
            const answerSelected = state.answerSelected[id] ? '✅' : '❌';
            const buttonEnabled = state.buttonState[id] ? '✅' : '❌';
            
            const qItem = document.createElement('div');
            qItem.style.cssText = 'margin-bottom: 8px; padding: 5px; background: rgba(255,255,255,0.1); border-radius: 3px;';
            qItem.innerHTML = `
                <div><b>Q${q.index} (ID: ${id}):</b></div>
                <div>Hint Viewed: ${hintViewed}</div>
                <div>Answer Selected: ${answerSelected}</div>
                <div>Next Button: ${buttonEnabled}</div>
            `;
            
            // Add control buttons
            const qControls = document.createElement('div');
            qControls.style.cssText = 'display: flex; gap: 5px; margin-top: 5px;';
            
            // Mark hint viewed button
            const markHintBtn = document.createElement('button');
            markHintBtn.textContent = 'Set Hint';
            markHintBtn.style.cssText = 'padding: 2px 5px; font-size: 10px; cursor: pointer;';
            markHintBtn.addEventListener('click', function() {
                state.hintViewed[id] = true;
                if (state.questions[id]) {
                    state.questions[id].hasHintViewed = true;
                }
                log(`Manually marked hint as viewed for question ${id}`);
                updateDebugUI();
            });
            qControls.appendChild(markHintBtn);
            
            // Mark answer selected button
            const markAnswerBtn = document.createElement('button');
            markAnswerBtn.textContent = 'Set Answer';
            markAnswerBtn.style.cssText = 'padding: 2px 5px; font-size: 10px; cursor: pointer;';
            markAnswerBtn.addEventListener('click', function() {
                state.answerSelected[id] = true;
                state.questions[id].hasSelectedAnswer = true;
                log(`Manually marked answer as selected for question ${id}`);
                updateDebugUI();
            });
            qControls.appendChild(markAnswerBtn);
            
            // Enable button
            const enableBtn = document.createElement('button');
            enableBtn.textContent = 'Enable Next';
            enableBtn.style.cssText = 'padding: 2px 5px; font-size: 10px; cursor: pointer;';
            enableBtn.addEventListener('click', function() {
                enableNextButton(q.element, id);
                log(`Manually enabled next button for question ${id}`);
            });
            qControls.appendChild(enableBtn);
            
            qItem.appendChild(qControls);
            questionState.appendChild(qItem);
        });
        
        content.appendChild(questionState);
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
})();
