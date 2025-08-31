/**
 * Strict Next Button Controller v5 - Enhanced Version for LearnDash Quiz
 * 
 * This script ensures proper quiz flow in force hint mode:
 * 1. User must view hint first
 * 2. User must select the CORRECT answer after viewing hint
 * 3. Only then will the Next button be enabled
 */

(function() {
    // Debug logging
    const DEBUG_MODE = true;
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log('Strict Next Button:', label, ...args);
        }
    }

    // State tracking
    const hintViewedMap = {};
    const correctAnswerSelectedMap = {};  // Track if user has selected the correct answer after hint

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Add initialization when LearnDash events occur
    document.addEventListener('wpProQuiz_initComplete', init);
    
    // Delayed initialization for safer DOM access
    setTimeout(init, 1000);

    function init() {
        debug('Initializing Strict Next Button Controller v5');
        
        // Get quiz settings
        const settings = getQuizSettings();
        if (!settings) {
            debug('No quiz settings found, exiting');
            return;
        }
        
        debug('Quiz settings loaded', settings);
        
        // Set up event listeners
        setupEventListeners(settings);
        
        // Add debug button if in debug mode
        if (DEBUG_MODE) {
            addDebugButton();
        }
        
        // Perform initial check of all questions
        initialCheck(settings);
        
        // Additional check after delay
        setTimeout(() => {
            fixAllNextButtons(settings);
        }, 1000);
    }
    
    function getQuizSettings() {
        debug('Getting quiz settings');
        
        // Get quiz data
        const quizData = getQuizData();
        debug('Quiz data:', quizData);
        
        if (!quizData.quizId) {
            debug('No quiz ID found');
            return getDefaultSettings();
        }
        
        // Get settings from global options
        const quizOptions = window.quizExtensionsSettings?.quiz_options || {};
        const settings = quizOptions[quizData.quizId] || {};
        debug('Raw quiz settings for ID ' + quizData.quizId + ':', settings);
        
        // Normalize settings
        return normalizeSettings(settings);
    }
    
    function getQuizData() {
        const quizData = {
            quizId: null,
            quizPostId: null,
            fromDom: false,
            fromSettings: false
        };
        
        // Method 1: From settings
        if (window.quizExtensionsSettings?.current_quiz_id) {
            quizData.quizId = window.quizExtensionsSettings.current_quiz_id;
            quizData.fromSettings = true;
            debug('Found quiz ID from settings:', quizData.quizId);
        }
        
        // Method 2: From DOM metadata
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (quizContainer && quizContainer.hasAttribute('data-quiz-meta')) {
            try {
                const quizMeta = JSON.parse(quizContainer.getAttribute('data-quiz-meta'));
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
        // Parse force hint mode
        const forceHintMode = 
            settings.force_hint_mode === 'Yes' || 
            settings.force_hint_mode === '1' || 
            settings['Force Hint Mode'] === 'ENABLED';
            
        // Parse require correct
        const requireCorrect = 
            settings.require_correct === 'Yes' || 
            settings.require_correct === '1' || 
            settings['Require Correct'] === 'Yes';
            
        // Parse show hint
        const showHint = 
            settings.show_hint === 'Yes' || 
            settings.show_hint === '1' || 
            settings['Show Hint'] === 'Yes';
            
        // Parse auto show hint
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
            raw: settings
        };
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
    
    function setupEventListeners(settings) {
        debug('Setting up event listeners with settings:', settings);
        
        // Listen for hint button clicks
        document.addEventListener('click', function(event) {
            // Check if this is a hint button
            if (event.target.classList.contains('wpProQuiz_TipButton') || 
                event.target.closest('.wpProQuiz_TipButton')) {
                
                // Get the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    handleHintButtonClick(questionItem, settings);
                }
            }
            
            // Check if this is a check button
            if (event.target.classList.contains('wpProQuiz_QuestionButton') && 
                event.target.name === 'check') {
                
                // Get the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    handleCheckButtonClick(questionItem, settings);
                }
            }
            
            // Handle reveal answers button
            if (event.target.id === 'show-quiz-answers') {
                event.preventDefault();
                revealQuizAnswers();
            }
        });
        
        // Watch for answer changes
        document.addEventListener('change', function(event) {
            if (event.target.classList.contains('wpProQuiz_questionInput')) {
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    const questionId = getQuestionId(questionItem);
                    if (questionId) {
                        debug('Answer changed for question:', questionId);
                        
                        // Check answer correctness after a short delay
                        setTimeout(() => {
                            const isCorrect = isAnswerCorrect(questionItem);
                            
                            if (isCorrect) {
                                debug('Correct answer selected for question:', questionId);
                                correctAnswerSelectedMap[questionId] = true;
                            } else {
                                debug('Incorrect answer selected for question:', questionId);
                                correctAnswerSelectedMap[questionId] = false;
                            }
                            
                            updateNextButton(questionItem, settings);
                        }, 300);
                    }
                }
            }
        });
        
        // Observe question list for changes
        const questionList = document.querySelector('.wpProQuiz_list');
        if (questionList) {
            const observer = new MutationObserver(function(mutations) {
                debug('Question list mutations detected:', mutations.length);
                
                // Check each question
                const questions = document.querySelectorAll('.wpProQuiz_listItem');
                questions.forEach(question => {
                    updateNextButton(question, settings);
                });
            });
            
            observer.observe(questionList, { 
                childList: true, 
                subtree: true, 
                attributes: true, 
                attributeFilter: ['style', 'class'] 
            });
            
            debug('Observer attached to question list');
        }
        
        // Setup hint display observer
        observeHintDisplay(settings);
    }
    
    function observeHintDisplay(settings) {
        debug('Setting up hint display observer');
        
        // Monitor for hint visibility changes
        const hintObserver = new MutationObserver(function(mutations) {
            mutations.forEach(mutation => {
                if (mutation.target.classList.contains('wpProQuiz_hint') &&
                    mutation.attributeName === 'style') {
                    
                    const isVisible = window.getComputedStyle(mutation.target).display !== 'none';
                    if (isVisible) {
                        const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                        if (questionItem) {
                            const questionId = getQuestionId(questionItem);
                            if (questionId) {
                                debug('Hint now visible for question:', questionId);
                                hintViewedMap[questionId] = true;
                                updateNextButton(questionItem, settings);
                            }
                        }
                    }
                }
            });
        });
        
        // Attach observer to all hint elements
        const hintElements = document.querySelectorAll('.wpProQuiz_hint');
        hintElements.forEach(hint => {
            hintObserver.observe(hint, { attributes: true, attributeFilter: ['style'] });
        });
        
        debug('Attached observer to', hintElements.length, 'hint elements');
    }
    
    function handleHintButtonClick(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        debug('Hint button clicked for question:', questionId);
        
        // Mark hint as viewed
        hintViewedMap[questionId] = true;
        
        // Reset correct answer flag to ensure user reselects after seeing hint
        correctAnswerSelectedMap[questionId] = false;
        
        // Update next button
        updateNextButton(questionItem, settings);
    }
    
    function handleCheckButtonClick(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        debug('Check button clicked for question:', questionId);
        
        // Check if the answer is correct
        const isCorrect = isAnswerCorrect(questionItem);
        
        // Update correct answer flag
        correctAnswerSelectedMap[questionId] = isCorrect;
        
        // Update next button
        updateNextButton(questionItem, settings);
    }
    
    function initialCheck(settings) {
        debug('Performing initial check of all questions');
        
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        questions.forEach(question => {
            const questionId = getQuestionId(question);
            const hintVisible = question.querySelector('.wpProQuiz_hint:not([style*="display: none"])');
            
            if (hintVisible) {
                hintViewedMap[questionId] = true;
                debug('Question', questionId, 'hint is already visible');
            }
            
            updateNextButton(question, settings);
        });
    }
    
    function fixAllNextButtons(settings) {
        debug('Fixing all next buttons');
        
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        questions.forEach(question => {
            updateNextButton(question, settings);
        });
    }
    
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Try from data attribute
        if (questionItem.hasAttribute('data-question-id')) {
            return questionItem.getAttribute('data-question-id');
        }
        
        // Try from ID
        if (questionItem.id) {
            const match = questionItem.id.match(/wpProQuiz_listItem_(\d+)/);
            if (match) {
                return match[1];
            }
        }
        
        // Try from index
        const allItems = Array.from(document.querySelectorAll('.wpProQuiz_listItem'));
        const index = allItems.indexOf(questionItem);
        if (index !== -1) {
            return 'q' + index;
        }
        
        return null;
    }
    
    function isAnswerCorrect(questionItem) {
        if (!questionItem) return false;
        
        // Check if there's a correct flag
        const correctFlag = questionItem.querySelector('.wpProQuiz_correct');
        if (correctFlag && correctFlag.style.display !== 'none') {
            return true;
        }
        
        // Check for results
        const resultElem = questionItem.querySelector('.wpProQuiz_response');
        if (resultElem) {
            const correctElements = resultElem.querySelectorAll('.wpProQuiz_correct, .wpProQuiz_quiz_correct');
            const incorrectElements = resultElem.querySelectorAll('.wpProQuiz_incorrect, .wpProQuiz_quiz_incorrect');
            
            const hasCorrect = Array.from(correctElements).some(el => el.style.display !== 'none');
            const hasIncorrect = Array.from(incorrectElements).some(el => el.style.display !== 'none');
            
            debug('Answer check - hasCorrect:', hasCorrect, 'hasIncorrect:', hasIncorrect);
            
            if (hasCorrect && !hasIncorrect) {
                return true;
            }
        }
        
        return false;
    }
    
    function hasSelectedAnswer(questionItem) {
        if (!questionItem) return false;
        
        const inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        return inputs.length > 0;
    }
    
    function updateNextButton(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next]');
        if (!nextButton) return;
        
        const isCorrect = isAnswerCorrect(questionItem);
        const viewedHint = hintViewedMap[questionId] || false;
        const correctAnswerSelected = correctAnswerSelectedMap[questionId] || false;
        const hasSelection = hasSelectedAnswer(questionItem);
        
        debug('Question state for next button:', {
            id: questionId,
            isCorrect,
            viewedHint,
            correctAnswerSelected,
            hasSelection,
            forceHintMode: settings.forceHintMode
        });
        
        // Force hint mode logic
        if (settings.forceHintMode) {
            if (viewedHint && correctAnswerSelected) {
                // In force hint mode, if hint is viewed AND user has selected the correct answer, enable next button
                enableButton(nextButton);
                debug('Enabling next button - hint viewed and correct answer selected in force hint mode');
            } else {
                disableButton(nextButton);
                
                if (!viewedHint) {
                    debug('Disabling next button - hint not viewed yet in force hint mode');
                } else if (!correctAnswerSelected) {
                    debug('Disabling next button - correct answer not selected after viewing hint');
                }
            }
        } 
        // Normal mode logic
        else if (settings.requireCorrect) {
            if (isCorrect) {
                enableButton(nextButton);
                debug('Enabling next button - answer is correct');
            } else {
                disableButton(nextButton);
                debug('Disabling next button - answer is not correct');
            }
        } 
        // Default behavior - always enable
        else {
            enableButton(nextButton);
            debug('Enabling next button - no restrictions');
        }
    }
    
    function enableButton(button) {
        if (!button) return;
        
        button.disabled = false;
        button.classList.remove('wpProQuiz_button_disabled');
    }
    
    function disableButton(button) {
        if (!button) return;
        
        button.disabled = true;
        button.classList.add('wpProQuiz_button_disabled');
    }
    
    function addDebugButton() {
        debug('Adding debug button');
        
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (!quizContainer) return;
        
        const debugButton = document.createElement('button');
        debugButton.id = 'show-quiz-answers';
        debugButton.className = 'wpProQuiz_button';
        debugButton.style.marginBottom = '20px';
        debugButton.textContent = 'Debug: Show Quiz Answers';
        
        quizContainer.appendChild(debugButton);
        
        debug('Debug button added');
    }
    
    function revealQuizAnswers() {
        debug('Revealing quiz answers');
        
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        const results = [];
        
        questions.forEach(question => {
            const questionId = getQuestionId(question);
            const questionText = question.querySelector('.wpProQuiz_question_text')?.textContent.trim() || 'Question ' + questionId;
            
            const result = {
                id: questionId,
                text: questionText,
                correctAnswers: [],
                userSelections: []
            };
            
            // Find correct answers
            const answerItems = question.querySelectorAll('.wpProQuiz_questionListItem');
            answerItems.forEach(item => {
                const isCorrect = item.classList.contains('wpProQuiz_answerCorrect') || 
                                 item.classList.contains('wpProQuiz_answerCorrectIncomplete');
                const answerText = item.textContent.trim();
                
                if (isCorrect) {
                    result.correctAnswers.push(answerText);
                }
                
                const isSelected = item.querySelector('.wpProQuiz_questionInput:checked');
                if (isSelected) {
                    result.userSelections.push(answerText);
                }
            });
            
            results.push(result);
        });
        
        console.table(results);
        
        // Create visual display
        const debugContainer = document.createElement('div');
        debugContainer.id = 'quiz-debug-container';
        debugContainer.style.padding = '20px';
        debugContainer.style.margin = '20px 0';
        debugContainer.style.border = '1px solid #ddd';
        debugContainer.style.backgroundColor = '#f9f9f9';
        
        const heading = document.createElement('h3');
        heading.textContent = 'Quiz Debug Information';
        debugContainer.appendChild(heading);
        
        results.forEach(result => {
            const questionDiv = document.createElement('div');
            questionDiv.style.marginBottom = '15px';
            questionDiv.style.padding = '10px';
            questionDiv.style.border = '1px solid #ccc';
            
            const questionHeading = document.createElement('h4');
            questionHeading.textContent = result.text;
            questionDiv.appendChild(questionHeading);
            
            const correctAnswersP = document.createElement('p');
            correctAnswersP.innerHTML = '<strong>Correct Answers:</strong> ' + 
                (result.correctAnswers.length ? result.correctAnswers.join(', ') : 'None found');
            questionDiv.appendChild(correctAnswersP);
            
            const userSelectionsP = document.createElement('p');
            userSelectionsP.innerHTML = '<strong>User Selections:</strong> ' + 
                (result.userSelections.length ? result.userSelections.join(', ') : 'None');
            questionDiv.appendChild(userSelectionsP);
            
            debugContainer.appendChild(questionDiv);
        });
        
        // Insert into document
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (quizContainer) {
            if (document.getElementById('quiz-debug-container')) {
                document.getElementById('quiz-debug-container').remove();
            }
            quizContainer.appendChild(debugContainer);
        }
    }
})();
