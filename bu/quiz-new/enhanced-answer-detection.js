/**
 * Enhanced Answer Detection for LearnDash Quiz
 * 
 * This script improves the detection of correct answers in LearnDash quizzes,
 * particularly for force hint mode quizzes.
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        debug: true,
        forceHintMode: null,
        checkInterval: 500 // ms
    };
    
    // State tracking
    const STATE = {
        questionStates: {}, // Stores state for each question
        correctAnswers: {}, // Stores correct answers once discovered
        initialized: false
    };
    
    /**
     * Debug logging function
     */
    function debug(message, data = null, type = 'info') {
        if (!CONFIG.debug) return;
        
        const styles = {
            info: 'background: #e6f7ff; color: #0066cc; padding: 3px; border-radius: 3px;',
            success: 'background: #e6ffed; color: #006600; padding: 3px; border-radius: 3px;',
            warning: 'background: #fff7e6; color: #cc6600; padding: 3px; border-radius: 3px;',
            error: 'background: #ffe6e6; color: #cc0000; padding: 3px; border-radius: 3px;'
        };
        
        console.log(`%cAnswer Detection: ${message}`, styles[type] || styles.info);
        if (data) console.log(data);
    }
    
    /**
     * Initialize the enhanced answer detection
     */
    function init() {
        if (STATE.initialized) return;
        
        debug('Initializing Enhanced Answer Detection');
        
        // Detect quiz settings
        detectQuizSettings();
        
        // Set up event listeners
        setupEventListeners();
        
        // Initial scan of questions
        scanQuestions();
        
        // Start monitoring answers
        startMonitoring();
        
        // Add debugging helpers
        if (CONFIG.debug) {
            addDebugHelpers();
        }
        
        STATE.initialized = true;
    }
    
    /**
     * Detect the quiz settings
     */
    function detectQuizSettings() {
        debug('Detecting quiz settings');
        
        // Try to get settings from global variable
        if (window.quizExtensionsSettings) {
            const quizId = window.quizExtensionsSettings.current_quiz_id;
            if (quizId && window.quizExtensionsSettings.quiz_options && window.quizExtensionsSettings.quiz_options[quizId]) {
                const settings = window.quizExtensionsSettings.quiz_options[quizId];
                CONFIG.forceHintMode = settings.force_hint_mode === '1';
                
                debug('Quiz settings detected', {
                    quizId,
                    forceHintMode: CONFIG.forceHintMode,
                    settings
                });
                
                return;
            }
        }
        
        // Fallback: Check for force hint mode indicators in DOM
        const forceHintAttr = document.querySelector('[data-force-hint-mode]');
        if (forceHintAttr) {
            CONFIG.forceHintMode = forceHintAttr.getAttribute('data-force-hint-mode') === 'true';
            debug('Force hint mode detected from DOM', CONFIG.forceHintMode);
            return;
        }
        
        debug('Could not detect quiz settings, assuming standard mode', null, 'warning');
        CONFIG.forceHintMode = false;
    }
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        debug('Setting up event listeners');
        
        // Track hint button clicks
        document.addEventListener('click', function(event) {
            // Check if click was on hint button
            if (event.target.classList.contains('wpProQuiz_TipButton') || 
                event.target.closest('.wpProQuiz_TipButton')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (!questionItem) return;
                
                const questionId = getQuestionId(questionItem);
                if (!questionId) return;
                
                // Update state
                ensureQuestionState(questionId);
                STATE.questionStates[questionId].hintViewed = true;
                STATE.questionStates[questionId].hintViewedAt = Date.now();
                
                debug(`Hint viewed for question ${questionId}`, STATE.questionStates[questionId], 'info');
                
                // Trigger answer detection after hint view
                setTimeout(() => {
                    detectAnswerStatus(questionItem, questionId);
                }, 500);
            }
            
            // Track answer selections
            if (event.target.closest('.wpProQuiz_questionListItem')) {
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (!questionItem) return;
                
                const questionId = getQuestionId(questionItem);
                if (!questionId) return;
                
                // Update state
                ensureQuestionState(questionId);
                STATE.questionStates[questionId].lastSelection = Date.now();
                
                // Check if hint was previously viewed
                if (STATE.questionStates[questionId].hintViewed) {
                    STATE.questionStates[questionId].selectedAfterHint = true;
                }
                
                debug(`Answer selected for question ${questionId}`, {
                    selectedAfterHint: STATE.questionStates[questionId].selectedAfterHint,
                    hintViewed: STATE.questionStates[questionId].hintViewed
                });
                
                // Trigger answer detection after selection
                setTimeout(() => {
                    detectAnswerStatus(questionItem, questionId);
                }, 300);
            }
            
            // Track check button clicks
            if (event.target.classList.contains('wpProQuiz_QuestionButton') && 
                event.target.name === 'check') {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (!questionItem) return;
                
                const questionId = getQuestionId(questionItem);
                if (!questionId) return;
                
                debug(`Check button clicked for question ${questionId}`);
                
                // Trigger answer detection after check
                setTimeout(() => {
                    detectAnswerStatus(questionItem, questionId);
                }, 500);
            }
        });
        
        // Track hint visibility changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'style' && 
                    mutation.target.classList.contains('wpProQuiz_tipp')) {
                    
                    const isVisible = window.getComputedStyle(mutation.target).display !== 'none';
                    if (isVisible) {
                        const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                        if (!questionItem) return;
                        
                        const questionId = getQuestionId(questionItem);
                        if (!questionId) return;
                        
                        // Update state
                        ensureQuestionState(questionId);
                        STATE.questionStates[questionId].hintViewed = true;
                        STATE.questionStates[questionId].hintViewedAt = Date.now();
                        
                        debug(`Hint became visible for question ${questionId}`, null, 'info');
                        
                        // Trigger answer detection after hint view
                        setTimeout(() => {
                            detectAnswerStatus(questionItem, questionId);
                        }, 500);
                    }
                }
            });
        });
        
        // Observe all hint containers
        document.querySelectorAll('.wpProQuiz_tipp').forEach(function(hint) {
            observer.observe(hint, { attributes: true });
        });
    }
    
    /**
     * Scan questions for initial state
     */
    function scanQuestions() {
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        
        debug(`Scanning ${questions.length} questions`);
        
        questions.forEach(function(questionItem) {
            const questionId = getQuestionId(questionItem);
            if (!questionId) return;
            
            // Initialize question state
            ensureQuestionState(questionId);
            
            // Initial answer detection
            detectAnswerStatus(questionItem, questionId);
            
            // Check for hint container
            const hintContainer = questionItem.querySelector('.wpProQuiz_tipp');
            if (hintContainer && window.getComputedStyle(hintContainer).display !== 'none') {
                STATE.questionStates[questionId].hintViewed = true;
            }
        });
    }
    
    /**
     * Start monitoring for changes at regular intervals
     */
    function startMonitoring() {
        setInterval(function() {
            const visibleQuestion = document.querySelector('.wpProQuiz_listItem:visible');
            if (!visibleQuestion) return;
            
            const questionId = getQuestionId(visibleQuestion);
            if (!questionId) return;
            
            // Check answer status periodically
            detectAnswerStatus(visibleQuestion, questionId);
            
            // Update Next button state if needed
            const nextButton = visibleQuestion.querySelector('.wpProQuiz_button[name=next]');
            if (nextButton) {
                updateNextButton(visibleQuestion, questionId);
            }
        }, CONFIG.checkInterval);
    }
    
    /**
     * Get a question ID using various methods
     */
    function getQuestionId(questionItem) {
        // Method 1: data-question-id attribute
        if (questionItem.dataset.questionId) {
            return questionItem.dataset.questionId;
        }
        
        // Method 2: data-question-meta attribute
        try {
            if (questionItem.dataset.questionMeta) {
                const meta = JSON.parse(questionItem.dataset.questionMeta);
                if (meta && meta.question_post_id) {
                    return meta.question_post_id;
                }
            }
        } catch (e) {
            // Ignore JSON parse errors
        }
        
        // Method 3: data-question-id in internal element
        const questionIdElem = questionItem.querySelector('[data-question-id]');
        if (questionIdElem && questionIdElem.dataset.questionId) {
            return questionIdElem.dataset.questionId;
        }
        
        // Method 4: position in quiz
        const position = Array.from(document.querySelectorAll('.wpProQuiz_listItem')).indexOf(questionItem);
        if (position >= 0) {
            return 'question_' + position;
        }
        
        return null;
    }
    
    /**
     * Ensure a question state object exists
     */
    function ensureQuestionState(questionId) {
        if (!STATE.questionStates[questionId]) {
            STATE.questionStates[questionId] = {
                hintViewed: false,
                hintViewedAt: null,
                lastSelection: null,
                selectedAfterHint: false,
                isCorrect: false,
                checkedAt: null,
                correctAnswerDiscovered: false
            };
        }
    }
    
    /**
     * Detect answer status for a question
     */
    function detectAnswerStatus(questionItem, questionId) {
        if (!questionItem || !questionId) return;
        
        // Ensure question state exists
        ensureQuestionState(questionId);
        
        // The results of different detection methods
        const results = {
            responseCorrect: false,
            answerItemsCorrect: false,
            questionListCorrect: false,
            reviewItemSolved: false,
            forceHintModeValid: false
        };
        
        // Method 1: Check response container
        const responseBlock = questionItem.querySelector('.wpProQuiz_response');
        if (responseBlock) {
            const correctDiv = responseBlock.querySelector('.wpProQuiz_correct');
            results.responseCorrect = correctDiv && 
                                     window.getComputedStyle(correctDiv).display !== 'none';
        }
        
        // Method 2: Check answer items for correct class
        const correctItems = questionItem.querySelectorAll('.wpProQuiz_answerCorrect, .wpProQuiz_questionListItem.correct');
        results.answerItemsCorrect = correctItems && correctItems.length > 0;
        
        // Method 3: Check question list for correct class
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        results.questionListCorrect = questionList && 
                                     questionList.classList.contains('wpProQuiz_questionListCorrect');
        
        // Method 4: Check review list for solved status
        const reviewItem = document.querySelector(`.wpProQuiz_reviewQuestion li[data-question="${questionId}"]`);
        results.reviewItemSolved = reviewItem && 
                                  reviewItem.classList.contains('wpProQuiz_reviewQuestionSolved');
        
        // Method 5: Force hint mode validation
        // In force hint mode, if hint was viewed and then an answer was selected, consider it valid
        if (CONFIG.forceHintMode) {
            results.forceHintModeValid = STATE.questionStates[questionId].hintViewed && 
                                         STATE.questionStates[questionId].selectedAfterHint;
        }
        
        // Determine overall correctness
        // For standard mode: at least one detection method must indicate correct
        // For force hint mode: force hint validation OR at least one detection method
        const isCorrect = CONFIG.forceHintMode ? 
            (results.forceHintModeValid || results.responseCorrect || results.answerItemsCorrect || 
             results.questionListCorrect || results.reviewItemSolved) : 
            (results.responseCorrect || results.answerItemsCorrect || 
             results.questionListCorrect || results.reviewItemSolved);
        
        // Update state
        STATE.questionStates[questionId].isCorrect = isCorrect;
        STATE.questionStates[questionId].checkedAt = Date.now();
        STATE.questionStates[questionId].detectionResults = results;
        
        // Log detailed results
        if (CONFIG.debug) {
            debug(`Answer detection for question ${questionId}`, {
                isCorrect,
                methods: results,
                state: { ...STATE.questionStates[questionId] }
            }, isCorrect ? 'success' : 'info');
        }
        
        return isCorrect;
    }
    
    /**
     * Update the next button state
     */
    function updateNextButton(questionItem, questionId) {
        if (!questionItem || !questionId) return;
        
        const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next]');
        if (!nextButton) return;
        
        // Get current state
        const state = STATE.questionStates[questionId];
        if (!state) return;
        
        // Determine if button should be enabled
        let shouldEnable = false;
        
        // Force hint mode logic
        if (CONFIG.forceHintMode) {
            shouldEnable = state.hintViewed && state.selectedAfterHint;
            
            debug(`Next button update (Force Hint Mode) for question ${questionId}`, {
                shouldEnable,
                hintViewed: state.hintViewed,
                selectedAfterHint: state.selectedAfterHint
            });
        } 
        // Standard mode logic
        else {
            shouldEnable = state.isCorrect;
            
            debug(`Next button update (Standard Mode) for question ${questionId}`, {
                shouldEnable,
                isCorrect: state.isCorrect
            });
        }
        
        // Update button state
        if (shouldEnable) {
            enableNextButton(nextButton);
        } else {
            disableNextButton(nextButton);
        }
    }
    
    /**
     * Enable the next button
     */
    function enableNextButton(button) {
        if (!button) return;
        
        // Remove disabled attribute and class
        button.removeAttribute('disabled');
        button.classList.remove('wpProQuiz_button_disabled');
        
        // Ensure proper styling
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
        
        debug('Next button enabled', button);
    }
    
    /**
     * Disable the next button
     */
    function disableNextButton(button) {
        if (!button) return;
        
        // Add disabled attribute and class
        button.setAttribute('disabled', 'disabled');
        button.classList.add('wpProQuiz_button_disabled');
        
        // Ensure proper styling
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
        
        debug('Next button disabled', button);
    }
    
    /**
     * Add debug helpers to the page
     */
    function addDebugHelpers() {
        // Create debug button
        const debugButton = document.createElement('button');
        debugButton.textContent = 'Debug Answers';
        debugButton.className = 'ld-debug-button';
        debugButton.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            z-index: 99999;
            background: #4a90e2;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 14px;
        `;
        
        // Add click event to show debug info
        debugButton.addEventListener('click', function() {
            console.log('--- ANSWER DETECTION DEBUG INFO ---');
            console.log('Quiz Settings:', CONFIG);
            console.log('Question States:', STATE.questionStates);
            
            // Current visible question
            const visibleQuestion = document.querySelector('.wpProQuiz_listItem:visible');
            if (visibleQuestion) {
                const questionId = getQuestionId(visibleQuestion);
                debug(`Currently visible question: ${questionId}`, STATE.questionStates[questionId]);
                
                // Force update answer detection
                detectAnswerStatus(visibleQuestion, questionId);
            } else {
                debug('No visible question found');
            }
        });
        
        // Add to document
        document.body.appendChild(debugButton);
    }
    
    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', init);
    
    // Also initialize on LearnDash events
    document.addEventListener('wpProQuiz_initComplete', init);
    document.addEventListener('learndash-quiz-init', init);
    
    // Fallback initialization after delay
    setTimeout(init, 1000);
    
    // Expose API for other scripts
    window.enhancedAnswerDetection = {
        getState: function() {
            return {
                config: { ...CONFIG },
                state: { ...STATE }
            };
        },
        detectAnswer: function(questionId) {
            const questionItem = document.querySelector(`.wpProQuiz_listItem[data-question-id="${questionId}"]`);
            if (!questionItem) return null;
            return detectAnswerStatus(questionItem, questionId);
        },
        enableForQuestion: function(questionId) {
            const questionItem = document.querySelector(`.wpProQuiz_listItem[data-question-id="${questionId}"]`);
            if (!questionItem) return;
            
            ensureQuestionState(questionId);
            STATE.questionStates[questionId].hintViewed = true;
            STATE.questionStates[questionId].selectedAfterHint = true;
            
            updateNextButton(questionItem, questionId);
        }
    };
})();
