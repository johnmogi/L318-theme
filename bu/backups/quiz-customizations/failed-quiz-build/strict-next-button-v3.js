/**
 * Strict Next Button Controller v3 - Fixed Version for LearnDash Quiz
 * 
 * This script fixes force hint mode by ensuring:
 * 1. Proper hint view detection
 * 2. Correct next button enabling after hint viewing
 * 3. Better answer correctness detection
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
    const checkedAfterHintMap = {};

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Add initialization when LearnDash events occur
    document.addEventListener('wpProQuiz_initComplete', init);
    
    // Delayed initialization for safer DOM access
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
        
        // Listen for Hint div changes - important for force hint mode
        observeHintDisplay(settings);
        
        // Listen for when hint is actually shown
        document.addEventListener('hintShown', function(event) {
            debug('Hint shown event received', event.detail);
            const questionId = event.detail?.questionId;
            if (questionId) {
                hintViewedMap[questionId] = true;
                
                // Immediately update next buttons when in force hint mode
                if (settings.forceHintMode) {
                    const questions = document.querySelectorAll('.wpProQuiz_listItem');
                    questions.forEach(question => {
                        const qId = getQuestionId(question);
                        if (qId === questionId) {
                            updateNextButton(question, settings);
                        }
                    });
                }
            }
        });
        
        // Hook into prevent-selection-loop.js if available - integration
        if (window.LilacQuizHintTracker) {
            debug('Found LilacQuizHintTracker, integrating with hint tracking');
            window.LilacQuizHintTracker.onHintViewed = function(questionId) {
                hintViewedMap[questionId] = true;
                debug('Hint viewed from external tracker for question', questionId);
                
                // Update all next buttons when a hint is viewed
                if (settings.forceHintMode) {
                    const questions = document.querySelectorAll('.wpProQuiz_listItem');
                    questions.forEach(question => {
                        updateNextButton(question, settings);
                    });
                }
            };
        }
    }
    
    function observeHintDisplay(settings) {
        // Use MutationObserver to detect when hint is displayed
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'style' && 
                    mutation.target.classList.contains('wpProQuiz_tipp')) {
                    
                    // Check if the hint is visible
                    const hintDiv = mutation.target;
                    const isVisible = hintDiv.style.display !== 'none';
                    
                    if (isVisible) {
                        const questionItem = hintDiv.closest('.wpProQuiz_listItem');
                        if (questionItem) {
                            const questionId = getQuestionId(questionItem);
                            if (questionId) {
                                debug('Hint div now visible for question', questionId);
                                hintViewedMap[questionId] = true;
                                
                                // Create and dispatch custom event
                                const event = new CustomEvent('hintShown', {
                                    detail: { questionId }
                                });
                                document.dispatchEvent(event);
                                
                                // Update next button
                                updateNextButton(questionItem, settings);
                            }
                        }
                    }
                }
            });
        });
        
        // Start observing all hint divs
        const hintDivs = document.querySelectorAll('.wpProQuiz_tipp');
        hintDivs.forEach(function(hintDiv) {
            observer.observe(hintDiv, { attributes: true });
        });
    }
    
    function handleHintButtonClick(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        debug('Hint button clicked for question', questionId);
        
        // Mark hint as viewed
        hintViewedMap[questionId] = true;
        
        // In force hint mode, this should enable the next button
        if (settings.forceHintMode) {
            setTimeout(() => {
                updateNextButton(questionItem, settings);
            }, 500); // Allow time for hint to display
        }
    }
    
    function handleCheckButtonClick(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        debug('Check button clicked for question', questionId);
        
        // Mark as checked after hint if hint was viewed
        if (hintViewedMap[questionId]) {
            checkedAfterHintMap[questionId] = true;
        }
        
        // In both modes, update the next button after a check
        setTimeout(() => {
            updateNextButton(questionItem, settings);
        }, 500); // Allow time for check response
    }
    
    function initialCheck(settings) {
        // Check all question items on the page
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        debug('Found ' + questionItems.length + ' question items');
        
        questionItems.forEach(item => {
            debug('Checking answer correctness');
            const isCorrect = isAnswerCorrect(item);
            debug(isCorrect ? 'Answer is correct' : 'Could not determine correctness, defaulting to false');
            
            updateNextButton(item, settings);
        });
    }
    
    function fixAllNextButtons(settings) {
        debug('Fixing all Next buttons on the page');
        
        // Get all question items
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        questionItems.forEach(item => {
            updateNextButton(item, settings);
        });
    }
    
    function getQuestionId(questionItem) {
        // Try to get question ID from data attribute
        if (questionItem.dataset.postId) {
            return questionItem.dataset.postId;
        }
        
        // Try to get from question meta
        if (questionItem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(questionItem.dataset.questionMeta);
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
            } catch (e) {
                debug('Error parsing question meta', e);
            }
        }
        
        // Try to get from question list
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.dataset.questionId) {
            return questionList.dataset.questionId;
        }
        
        return null;
    }
    
    function isAnswerCorrect(questionItem) {
        // Method 1: Check if the response container shows correct
        const responseBlock = questionItem.querySelector('.wpProQuiz_response');
        if (responseBlock) {
            const correctDiv = responseBlock.querySelector('.wpProQuiz_correct');
            if (correctDiv && window.getComputedStyle(correctDiv).display !== 'none') {
                return true;
            }
        }
        
        // Method 2: Check if any answer list items have the correct class
        const answerItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        for (let i = 0; i < answerItems.length; i++) {
            if (answerItems[i].classList.contains('wpProQuiz_answerCorrect')) {
                return true;
            }
        }
        
        // Method 3: Check for checked or selected items (user has at least made a selection)
        const inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        if (inputs.length > 0) {
            // User has selected something, but we don't know if it's correct
            // Return true only if we have specific knowledge this is a force hint mode
            const questionId = getQuestionId(questionItem);
            if (questionId && hintViewedMap[questionId]) {
                return true; // In force hint mode, viewed hint + selection = allow next
            }
        }
        
        return false;
    }
    
    function updateNextButton(questionItem, settings) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next]');
        if (!nextButton) return;
        
        const isCorrect = isAnswerCorrect(questionItem);
        const viewedHint = hintViewedMap[questionId] || false;
        const checkedAfterHint = checkedAfterHintMap[questionId] || false;
        
        debug('Question state for next button:', {
            id: questionId,
            isCorrect,
            viewedHint,
            checkedAfterHint,
            forceHintMode: settings.forceHintMode
        });
        
        // Force hint mode logic
        if (settings.forceHintMode) {
            if (viewedHint) {
                // In force hint mode, if hint is viewed, enable next button
                enableButton(nextButton);
                debug('Enabling next button - hint viewed in force hint mode');
            } else {
                disableButton(nextButton);
                debug('Disabling next button - force hint mode conditions not met');
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
        button.classList.remove('disabled');
        button.style.display = 'block';
    }
    
    function disableButton(button) {
        if (!button) return;
        
        button.disabled = true;
        button.classList.add('disabled');
    }
    
    function revealQuizAnswers() {
        debug('Revealing quiz answers');
        
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        let answerHTML = '<div class="quiz-answers-debug"><h2>Quiz Answers</h2>';
        
        questionItems.forEach((item, index) => {
            const questionId = getQuestionId(item);
            const questionText = item.querySelector('.wpProQuiz_question_text')?.textContent || `Question ${index + 1}`;
            
            answerHTML += `<div class="question-debug">
                <h3>Q${index + 1}: ${questionText}</h3>
                <p>Question ID: ${questionId}</p>`;
            
            // Get all answer options
            const answerItems = item.querySelectorAll('.wpProQuiz_questionListItem');
            answerHTML += '<ul class="answer-options">';
            
            answerItems.forEach((answer, aIndex) => {
                const isCorrect = answer.classList.contains('wpProQuiz_answerCorrect');
                const answerText = answer.textContent.trim();
                
                answerHTML += `<li class="${isCorrect ? 'correct-answer' : ''}">
                    Option ${aIndex + 1}: ${answerText}
                    ${isCorrect ? ' ✓ (CORRECT)' : ''}
                </li>`;
            });
            
            answerHTML += '</ul></div>';
        });
        
        answerHTML += '</div>';
        
        // Create or update the debug panel
        let debugPanel = document.getElementById('quiz-answers-debug-panel');
        if (!debugPanel) {
            debugPanel = document.createElement('div');
            debugPanel.id = 'quiz-answers-debug-panel';
            debugPanel.style.position = 'fixed';
            debugPanel.style.bottom = '10px';
            debugPanel.style.right = '10px';
            debugPanel.style.width = '400px';
            debugPanel.style.maxHeight = '80vh';
            debugPanel.style.overflowY = 'auto';
            debugPanel.style.background = 'white';
            debugPanel.style.padding = '15px';
            debugPanel.style.border = '1px solid #ddd';
            debugPanel.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
            debugPanel.style.zIndex = '9999';
            document.body.appendChild(debugPanel);
        }
        
        debugPanel.innerHTML = answerHTML;
    }
    
    function addDebugButton() {
        // Create debug button
        let debugButton = document.getElementById('show-quiz-answers');
        if (!debugButton) {
            debugButton = document.createElement('button');
            debugButton.id = 'show-quiz-answers';
            debugButton.textContent = 'Show Quiz Answers';
            debugButton.style.position = 'fixed';
            debugButton.style.bottom = '10px';
            debugButton.style.right = '10px';
            debugButton.style.zIndex = '9999';
            debugButton.style.padding = '5px 10px';
            debugButton.style.background = '#007bff';
            debugButton.style.color = 'white';
            debugButton.style.border = 'none';
            debugButton.style.borderRadius = '3px';
            debugButton.style.cursor = 'pointer';
            
            document.body.appendChild(debugButton);
        }
    }
})();
