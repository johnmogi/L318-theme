/**
 * Lilac Quiz Controller
 * 
 * A clean rewrite of the quiz functionality for LearnDash with proper answer detection
 * and sidebar integration.
 * 
 * Features:
 * - Force hint mode with proper answer tracking
 * - Visual feedback for correct/incorrect answers
 * - Support for both sidebar and standard layouts
 * - Clean separation of concerns
 */

(function() {
    'use strict';
    
    // Main configuration
    const CONFIG = {
        debug: true,                  // Enable verbose logging
        forceHintMode: true,          // Require hints for incorrect answers
        checkAnswerDelay: 300,        // Delay before checking answers (ms)
        autoShowHint: false,          // Auto show hint on incorrect answer
        visualFeedback: true,         // Show visual feedback for answers
        enableNextOnCorrect: true,    // Enable next button only for correct answers
        sidebarIntegration: true      // Enable sidebar integration if available
    };
    
    // State tracking
    const STATE = {
        quiz: {
            id: null,
            postId: null,
            hasSidebar: false,
            questions: {}
        },
        initialized: false
    };
    
    /**
     * Initialize the quiz controller
     */
    function init() {
        if (STATE.initialized) return;
        
        log('Initializing Lilac Quiz Controller');
        
        // Detect quiz and configuration
        detectQuiz();
        
        // Initialize event listeners
        initEvents();
        
        // Process any existing selections
        scanExistingSelections();
        
        // Set the initialized flag
        STATE.initialized = true;
        
        log('Quiz controller initialized', STATE);
    }
    
    /**
     * Detect the current quiz and its configuration
     */
    function detectQuiz() {
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (!quizContainer) {
            log('No quiz found on this page');
            return;
        }
        
        // Extract quiz ID from container
        const quizId = quizContainer.id.replace('wpProQuiz_', '');
        STATE.quiz.id = quizId;
        
        // Check for quiz post ID in data attributes
        if (quizContainer.dataset.quizPostId) {
            STATE.quiz.postId = quizContainer.dataset.quizPostId;
        }
        
        // Detect sidebar
        const sidebarElement = document.querySelector('.quiz-sidebar, .learndash-sidebar');
        STATE.quiz.hasSidebar = !!sidebarElement;
        
        log('Quiz detected', {
            id: STATE.quiz.id,
            postId: STATE.quiz.postId,
            hasSidebar: STATE.quiz.hasSidebar
        });
        
        // Scan for questions
        scanQuestions();
    }
    
    /**
     * Scan the page for quiz questions and store their data
     */
    function scanQuestions() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        
        log(`Found ${questionItems.length} questions`);
        
        questionItems.forEach((item, index) => {
            // Try to get question ID from various sources
            let questionId = null;
            
            // Method 1: From data-post-id attribute
            if (item.dataset.postId) {
                questionId = item.dataset.postId;
            }
            
            // Method 2: From question list data attribute
            if (!questionId) {
                const questionList = item.querySelector('.wpProQuiz_questionList');
                if (questionList && questionList.dataset.questionId) {
                    questionId = questionList.dataset.questionId;
                }
            }
            
            // Method 3: Use index as fallback
            if (!questionId) {
                questionId = `q_${index}`;
            }
            
            // Store question data
            STATE.quiz.questions[questionId] = {
                element: item,
                index: index,
                questionId: questionId,
                isAnswered: false,
                isCorrect: false,
                hintViewed: false,
                nextButton: item.querySelector('.wpProQuiz_button[name=next]'),
                hintButton: item.querySelector('.wpProQuiz_button[name=tip]'),
                answerList: item.querySelector('.wpProQuiz_questionList')
            };
            
            log(`Registered question #${index + 1}`, STATE.quiz.questions[questionId]);
        });
    }
    
    /**
     * Initialize all event listeners for quiz interaction
     */
    function initEvents() {
        // Answer selection events
        initAnswerEvents();
        
        // Hint button clicks
        initHintEvents();
        
        // Check button clicks
        initCheckEvents();
        
        // Next button management
        initNextButtonEvents();
    }
    
    /**
     * Initialize answer selection events
     */
    function initAnswerEvents() {
        document.addEventListener('click', function(event) {
            // Check if click is on an answer input
            const input = event.target.closest('.wpProQuiz_questionListItem input');
            if (!input) return;
            
            // Get the question item
            const questionItem = input.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            // Find question ID
            const questionId = getQuestionId(questionItem);
            if (!questionId) {
                log('Could not determine question ID for answer selection');
                return;
            }
            
            log(`Answer selected for question ${questionId}`);
            
            // Update state
            STATE.quiz.questions[questionId].isAnswered = true;
            
            // Add a delay before checking correctness (let LearnDash process the answer)
            setTimeout(() => {
                // Check if the answer is correct
                const isCorrect = checkAnswerCorrect(questionItem, questionId);
                
                // Update state
                STATE.quiz.questions[questionId].isCorrect = isCorrect;
                
                // Provide visual feedback
                if (CONFIG.visualFeedback) {
                    provideVisualFeedback(questionItem, questionId, isCorrect);
                }
                
                // Handle next button
                updateNextButton(questionItem, questionId);
                
                log(`Answer check for question ${questionId}: ${isCorrect ? 'CORRECT' : 'INCORRECT'}`);
            }, CONFIG.checkAnswerDelay);
        });
    }
    
    /**
     * Initialize hint button events
     */
    function initHintEvents() {
        document.addEventListener('click', function(event) {
            // Check if click is on a hint button
            const button = event.target.closest('.wpProQuiz_button[name=tip]');
            if (!button) return;
            
            // Get the question item
            const questionItem = button.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            // Find question ID
            const questionId = getQuestionId(questionItem);
            if (!questionId) {
                log('Could not determine question ID for hint button');
                return;
            }
            
            log(`Hint viewed for question ${questionId}`);
            
            // Update state
            STATE.quiz.questions[questionId].hintViewed = true;
            
            // Apply hint viewed indicator
            applyHintViewedIndicator(questionItem, questionId);
            
            // Update next button state
            updateNextButton(questionItem, questionId);
            
            // Set up hint display observer
            observeHintDisplay(questionItem, questionId);
        });
    }
    
    /**
     * Initialize check button events
     */
    function initCheckEvents() {
        document.addEventListener('click', function(event) {
            // Check if click is on a check button
            const button = event.target.closest('.wpProQuiz_button[name=check]');
            if (!button) return;
            
            // Get the question item
            const questionItem = button.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            // Find question ID
            const questionId = getQuestionId(questionItem);
            if (!questionId) {
                log('Could not determine question ID for check button');
                return;
            }
            
            log(`Check button clicked for question ${questionId}`);
            
            // Add a delay for LearnDash to process the check
            setTimeout(() => {
                // Check if the answer is correct after checking
                const isCorrect = checkAnswerCorrect(questionItem, questionId);
                
                // Update state
                STATE.quiz.questions[questionId].isCorrect = isCorrect;
                STATE.quiz.questions[questionId].checkedAfterHint = STATE.quiz.questions[questionId].hintViewed;
                
                // Provide visual feedback
                if (CONFIG.visualFeedback) {
                    provideVisualFeedback(questionItem, questionId, isCorrect);
                }
                
                // Update next button state
                updateNextButton(questionItem, questionId);
                
                log(`Check result for question ${questionId}: ${isCorrect ? 'CORRECT' : 'INCORRECT'}`);
            }, CONFIG.checkAnswerDelay);
        });
    }
    
    /**
     * Initialize next button management
     */
    function initNextButtonEvents() {
        // Periodically check all next buttons to ensure they're in the correct state
        setInterval(() => {
            for (const questionId in STATE.quiz.questions) {
                const questionData = STATE.quiz.questions[questionId];
                updateNextButton(questionData.element, questionId);
            }
        }, 1000);
    }
    
    /**
     * Handle observation of hint display via DOM mutations
     */
    function observeHintDisplay(questionItem, questionId) {
        // Find the hint element
        const hintBox = questionItem.querySelector('.wpProQuiz_hint');
        if (!hintBox) return;
        
        // Create a mutation observer to watch for hint display changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const isVisible = window.getComputedStyle(hintBox).display !== 'none';
                    
                    if (isVisible) {
                        log(`Hint is now visible for question ${questionId}`);
                        
                        // Update state
                        STATE.quiz.questions[questionId].hintViewed = true;
                        
                        // Apply hint viewed indicator
                        applyHintViewedIndicator(questionItem, questionId);
                        
                        // Update next button state
                        updateNextButton(questionItem, questionId);
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(hintBox, { attributes: true, attributeFilter: ['style'] });
    }
    
    /**
     * Scan for any existing selections when the controller initializes
     */
    function scanExistingSelections() {
        // Process each registered question
        for (const questionId in STATE.quiz.questions) {
            const questionData = STATE.quiz.questions[questionId];
            const questionItem = questionData.element;
            
            // Check if an answer is already selected
            const selectedAnswer = questionItem.querySelector('.wpProQuiz_questionListItem input:checked');
            if (selectedAnswer) {
                log(`Found existing selection for question ${questionId}`);
                
                // Update state
                questionData.isAnswered = true;
                
                // Check if the answer is correct
                const isCorrect = checkAnswerCorrect(questionItem, questionId);
                questionData.isCorrect = isCorrect;
                
                // Provide visual feedback
                if (CONFIG.visualFeedback) {
                    provideVisualFeedback(questionItem, questionId, isCorrect);
                }
                
                // Check if hint was already viewed
                const hintBox = questionItem.querySelector('.wpProQuiz_hint');
                if (hintBox && window.getComputedStyle(hintBox).display !== 'none') {
                    log(`Hint already viewed for question ${questionId}`);
                    questionData.hintViewed = true;
                    applyHintViewedIndicator(questionItem, questionId);
                }
                
                // Update next button state
                updateNextButton(questionItem, questionId);
            }
        }
    }
    
    /**
     * Get the question ID from a question item element
     */
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Try to get from data-question-id attribute
        if (questionItem.dataset.questionId) {
            return questionItem.dataset.questionId;
        }
        
        // Try to get from ID
        if (questionItem.id && questionItem.id.includes('_')) {
            return questionItem.id;
        }
        
        // Try to get from data-post-id attribute
        if (questionItem.dataset.postId) {
            return questionItem.dataset.postId;
        }
        
        // Try to get from meta data
        const metaElem = questionItem.querySelector('[data-question-meta]');
        if (metaElem && metaElem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(metaElem.dataset.questionMeta);
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
            } catch (e) {
                // Ignore JSON parse errors
            }
        }
        
        // Final attempt: Use the index within parent
        if (questionItem.parentNode) {
            const index = Array.from(questionItem.parentNode.children).indexOf(questionItem);
            if (index !== -1) {
                return `q_${index}`;
            }
        }
        
        return null;
    }
    
    /**
     * Check if the selected answer is correct
     */
    function checkAnswerCorrect(questionItem, questionId) {
        if (!questionId || !questionItem) return false;
        
        // IMPORTANT: Verify that an answer is actually selected first
        const hasSelection = questionItem.querySelector('.wpProQuiz_questionListItem input:checked, .wpProQuiz_questionListItem.selected');
        if (!hasSelection) {
            log(`Question ${questionId} answer check: No answer selected`);
            return false;
        }
        
        // Debug information
        log(`Question ${questionId} answer check: Analyzing selected answer`, hasSelection);
        
        // Method 1: Check for correct response message - most reliable
        const correctResponseElement = questionItem.querySelector('.wpProQuiz_response .wpProQuiz_correct, .learndash-correct-response');
        if (correctResponseElement) {
            const style = window.getComputedStyle(correctResponseElement);
            if (style.display !== 'none' && style.visibility !== 'hidden') {
                log(`Question ${questionId} is correct (visible response message)`);
                return true;
            }
        }
        
        // Method 2: Check if the incorrect response is hidden - also reliable
        const incorrectResponseElement = questionItem.querySelector('.wpProQuiz_response .wpProQuiz_incorrect, .learndash-incorrect-response');
        if (incorrectResponseElement) {
            const style = window.getComputedStyle(incorrectResponseElement);
            if (style.display === 'none' && questionItem.querySelector('.wpProQuiz_response')) {
                log(`Question ${questionId} might be correct (incorrect message is hidden)`);
                return true;
            }
        }

        // Method 3: Check the SPECIFIC CLASS on the QUESTION ITEM itself after LearnDash processes
        if (questionItem.classList.contains('wpProQuiz_correct') || 
            questionItem.classList.contains('question-correct')) {
            log(`Question ${questionId} is correct (detected by class on question item)`);
            return true;
        }
        
        // Only check these if the previous methods didn't detect correctness
        
        // Method 4: Check for correct answer class on selected items
        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked, .wpProQuiz_questionListItem.selected');
        let anyCorrect = false;
        let anyIncorrect = false;
        
        for (let i = 0; i < selectedItems.length; i++) {
            const listItem = selectedItems[i].closest('.wpProQuiz_questionListItem');
            
            if (listItem) {
                // Check if item has CORRECT marking
                if (listItem.classList.contains('wpProQuiz_answerCorrect') || 
                    listItem.classList.contains('correct') || 
                    listItem.classList.contains('is-correct') ||
                    listItem.classList.contains('answer-correct')) {
                    anyCorrect = true;
                    log(`Selected item is marked as correct`);
                }
                
                // Check if item has INCORRECT marking
                if (listItem.classList.contains('wpProQuiz_answerIncorrect') || 
                    listItem.classList.contains('incorrect') || 
                    listItem.classList.contains('wrong')) {
                    anyIncorrect = true;
                    log(`Selected item is marked as incorrect`);
                }
                
                // Check for data attributes
                if (listItem.dataset.correct === '1' || 
                    listItem.dataset.isCorrect === 'true' || 
                    listItem.dataset.isCorrect === '1') {
                    anyCorrect = true;
                    log(`Selected item has correct data attribute`);
                }
                
                // Check input attributes directly
                const input = listItem.querySelector('input');
                if (input && (input.dataset.correct === 'true' || 
                              input.getAttribute('data-correct') === '1')) {
                    anyCorrect = true;
                    log(`Input element has correct attribute`);
                }
            }
        }
        
        // For single choice questions, if we found any correct markings and no incorrect ones
        if (anyCorrect && !anyIncorrect) {
            log(`Question ${questionId} is correct (detected by markings on selected items)`);
            return true;
        }
        
        // Method 5: Check LearnDash specific feedback elements
        const feedbackContainer = questionItem.querySelector('.wpProQuiz_response, .learndash-quiz-question-response');
        if (feedbackContainer) {
            // Check for specific text in feedback (e.g., "Correct!" or "That's right!")
            if (feedbackContainer.textContent) {
                const text = feedbackContainer.textContent.toLowerCase();
                if (text.includes('correct') || text.includes('right') || 
                    text.includes('true') || text.includes('נכון') || 
                    text.includes('excellent') || text.includes('good job')) {
                    log(`Question ${questionId} is correct (feedback text contains correct message)`);
                    return true;
                }
            }
            
            // Check for specific background colors or styles indicative of correct answers
            const style = window.getComputedStyle(feedbackContainer);
            const bgColor = style.backgroundColor.toLowerCase();
            if (bgColor.includes('green') || bgColor.includes('rgba(0, 128, 0') || 
                bgColor.includes('rgb(0, 128, 0') || bgColor.includes('#00ff00') ||
                bgColor.includes('#00cc00') || bgColor.includes('#008000')) {
                log(`Question ${questionId} is correct (green feedback background)`);
                return true;
            }
        }
        
        // Method 4: Check LearnDash internal data
        try {
            // Check for wpProQuizInitList structure first
            if (window.wpProQuizInitList && STATE.quiz.id) {
                const quizData = window.wpProQuizInitList[STATE.quiz.id];
                
                // Check the typical structure for answer data
                if (quizData && quizData.catResults) {
                    const answerData = quizData.catResults[questionId];
                    if (answerData && answerData.results && answerData.results.json) {
                        const isCorrect = answerData.results.json.correct === true;
                        if (isCorrect) {
                            log(`Question ${questionId} is correct (LearnDash internal data)`);
                            return true;
                        }
                    }
                }
                
                // Alternative structure that sometimes exists
                if (quizData && quizData.questionList) {
                    const index = Array.from(questionItem.parentNode.children).indexOf(questionItem);
                    if (quizData.questionList[index] && quizData.questionList[index].correct === true) {
                        log(`Question ${questionId} is correct (LearnDash question list)`);
                        return true;
                    }
                }
            }
            
            // Check global wpProQuiz_data if it exists
            if (window.wpProQuiz_data) {
                const quizzes = Object.values(window.wpProQuiz_data);
                for (const quiz of quizzes) {
                    if (quiz.json && quiz.json.correct === true) {
                        log(`Question ${questionId} is correct (global wpProQuiz_data)`);
                        return true;
                    }
                }
            }
        } catch (e) {
            // Silent fail for internal data checks
        }
        
        // Method 5: Check for checkmark icon
        const checkmarkIcon = questionItem.querySelector('.wpProQuiz_correct_icon, .ld-icon-checkmark');
        if (checkmarkIcon && window.getComputedStyle(checkmarkIcon).display !== 'none') {
            log(`Question ${questionId} is correct (checkmark icon visible)`);
            return true;
        }
        
        // Method 6: Check if check button is hidden and hint button is visible
        // This is often an indicator that the answer is correct in LearnDash
        const checkButton = questionItem.querySelector('input[name=check]');
        const hintButton = questionItem.querySelector('input[name=tip]');
        
        if (checkButton && hintButton && 
            window.getComputedStyle(checkButton).display === 'none' && 
            window.getComputedStyle(hintButton).display !== 'none') {
            log(`Question ${questionId} might be correct (check hidden, hint visible)`);
            return true;
        }
        
        return false;
    }
    
    /**
     * Provide visual feedback for answer correctness
     */
    function provideVisualFeedback(questionItem, questionId, isCorrect) {
        // Remove any existing feedback classes
        questionItem.classList.remove('lilac-answer-correct', 'lilac-answer-incorrect');
        
        // Add appropriate feedback class
        if (isCorrect) {
            questionItem.classList.add('lilac-answer-correct');
            
            // Find all selected answers and mark them
            const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked');
            selectedItems.forEach(item => {
                const listItem = item.closest('.wpProQuiz_questionListItem');
                if (listItem) {
                    listItem.classList.add('lilac-selected-correct');
                }
            });
        } else {
            questionItem.classList.add('lilac-answer-incorrect');
            
            // Find all selected answers and mark them
            const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked');
            selectedItems.forEach(item => {
                const listItem = item.closest('.wpProQuiz_questionListItem');
                if (listItem) {
                    listItem.classList.add('lilac-selected-incorrect');
                }
            });
            
            // If hint is available, highlight the hint button
            const hintButton = questionItem.querySelector('.wpProQuiz_button[name=tip]');
            if (hintButton && !STATE.quiz.questions[questionId].hintViewed) {
                hintButton.classList.add('lilac-hint-recommended');
            }
        }
    }
    
    /**
     * Apply a visual indicator that hint was viewed
     */
    function applyHintViewedIndicator(questionItem, questionId) {
        // Add class to question item
        questionItem.classList.add('lilac-hint-viewed');
        
        // Update hint button if it exists
        const hintButton = questionItem.querySelector('.wpProQuiz_button[name=tip]');
        if (hintButton) {
            hintButton.classList.add('lilac-hint-used');
            hintButton.classList.remove('lilac-hint-recommended');
        }
        
        // Add checkmark to hint button
        if (hintButton && !hintButton.querySelector('.lilac-hint-viewed-indicator')) {
            const indicator = document.createElement('span');
            indicator.className = 'lilac-hint-viewed-indicator';
            indicator.innerHTML = '✓';
            indicator.title = 'Hint has been viewed';
            hintButton.appendChild(indicator);
        }
        
        // If in force hint mode, update the next button status
        if (CONFIG.forceHintMode) {
            updateNextButton(questionItem, questionId);
        }
    }
    
    /**
     * Update the state of the next button based on quiz configuration
     */
    function updateNextButton(questionItem, questionId) {
        const questionData = STATE.quiz.questions[questionId];
        if (!questionData || !questionData.nextButton) {
            log(`No next button found for question ${questionId}`);
            return;
        }
        
        const nextButton = questionData.nextButton;
        let shouldEnable = true;
        let disableReasons = [];
        
        // Check if force hint mode is enabled
        if (CONFIG.forceHintMode) {
            // If the answer is incorrect, ensure hint was viewed
            if (!questionData.isCorrect) {
                if (!questionData.hintViewed) {
                    disableReasons.push('Force hint mode requires hint for incorrect answer');
                    shouldEnable = false;
                }
            }
        }
        
        // Check if correct answer is required
        if (CONFIG.enableNextOnCorrect && !questionData.isCorrect) {
            disableReasons.push('Correct answer required');
            shouldEnable = false;
        }
        
        // Show hint button if hint is available and not yet viewed
        if (questionData.hintButton) {
            const hintButton = questionData.hintButton;
            
            // Make hint button more visible when needed
            if (CONFIG.forceHintMode && !questionData.isCorrect && !questionData.hintViewed) {
                // Add visual indicator to guide users to use hint
                hintButton.classList.add('hint-recommended');
                
                // If auto-show hint is enabled, trigger the hint button click
                if (CONFIG.autoShowHint && !questionData.hintShownAutomatically) {
                    setTimeout(() => {
                        hintButton.click();
                        questionData.hintShownAutomatically = true;
                        log(`Auto-showing hint for question ${questionId}`);
                    }, 1000);
                }
            } else {
                hintButton.classList.remove('hint-recommended');
            }
        }
        
        // Update button state
        if (shouldEnable) {
            enableButton(nextButton);
            log(`Next button enabled for question ${questionId}`);
        } else {
            disableButton(nextButton);
            
            // Only log once for each update to reduce console spam
            if (questionData.lastDisableReasons !== JSON.stringify(disableReasons)) {
                for (const reason of disableReasons) {
                    log(`Next button disabled: ${reason} (Question ${questionId})`);
                }
                log(`Next button disabled for question ${questionId}`);
                questionData.lastDisableReasons = JSON.stringify(disableReasons);
            }
        }
    }
    
    /**
     * Enable a button
     */
    function enableButton(button) {
        if (!button) return;
        
        button.disabled = false;
        button.classList.remove('lilac-button-disabled');
        button.style.opacity = '';
        button.style.cursor = 'pointer';
    }
    
    /**
     * Disable a button
     */
    function disableButton(button) {
        if (!button) return;
        
        button.disabled = true;
        button.classList.add('lilac-button-disabled');
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
    }
    
    /**
     * Logger with prefix for debugging
     */
    function log(message, data) {
        if (!CONFIG.debug) return;
        
        if (data) {
            console.log(`[Lilac Quiz] ${message}`, data);
        } else {
            console.log(`[Lilac Quiz] ${message}`);
        }
    }
    
    // Initialize when the DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Also initialize on LearnDash's quiz init event
    document.addEventListener('learndash-quiz-ready', init);
    
    // Expose API for external access
    window.LilacQuiz = {
        init: init,
        getState: function() {
            return STATE;
        },
        setConfig: function(key, value) {
            if (key in CONFIG) {
                CONFIG[key] = value;
                log(`Configuration updated: ${key} = ${value}`);
            }
        }
    };
})();
