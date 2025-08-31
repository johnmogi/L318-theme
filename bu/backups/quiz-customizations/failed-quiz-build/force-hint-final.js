/**
 * Force Hint Mode - Final Implementation
 * 
 * This script correctly implements Force Hint Mode for LearnDash quizzes
 * without the issue of other scripts fighting for control.
 */

(function() {
    // Constants
    const VERSION = "1.0.0";
    const DEBUG = true;
    
    // State tracking
    const state = {
        // Quiz Info
        quizId: null,
        settings: null,
        
        // Question State
        questionData: {}, // By question ID
        hintViewed: {},   // By question ID
        answersSelected: {}, // By question ID
        correctAnswers: {}, // By question ID (for future enhancement)
        
        // Control flags
        initialized: false,
        debug: DEBUG,
        
        // UI Elements
        debugPanel: null
    };
    
    // Utility function for logging
    function log(message, ...args) {
        if (state.debug) {
            console.log(`[FHM ${VERSION}]`, message, ...args);
        }
    }
    
    log("Force Hint Mode: Final version loaded");
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('wpProQuiz_initComplete', initialize);
    setTimeout(initialize, 800);
    
    // Main initialization function
    function initialize() {
        if (state.initialized) return;
        
        // Detect the current quiz and settings
        const quizInfo = detectQuiz();
        if (!quizInfo || !quizInfo.settings || !quizInfo.settings.forceHintMode) {
            log("Force Hint Mode disabled or quiz not found");
            return;
        }
        
        state.quizId = quizInfo.quizId;
        state.settings = quizInfo.settings;
        
        log("Quiz detected, settings:", state.settings);
        
        // Find and analyze questions
        scanQuestions();
        
        // Set up event listeners
        setupEventListeners();
        
        // Add the debug UI if enabled
        if (state.debug) {
            createDebugUI();
        }
        
        state.initialized = true;
        log("Force Hint Mode initialized!");
        
        // Finally run a check on all questions
        processAllQuestions();
    }
    
    // Detect the quiz ID and settings
    function detectQuiz() {
        // Method 1: Get from global variable
        if (window.quizExtensionsSettings && window.quizExtensionsSettings.current_quiz_id) {
            const quizId = window.quizExtensionsSettings.current_quiz_id;
            log("Quiz ID found in settings:", quizId);
            
            // Get quiz settings
            if (window.quizExtensionsSettings.quiz_options && 
                window.quizExtensionsSettings.quiz_options[quizId]) {
                
                const rawSettings = window.quizExtensionsSettings.quiz_options[quizId];
                
                // Parse settings
                const settings = {
                    forceHintMode: rawSettings.force_hint_mode === '1' || 
                                   rawSettings.force_hint_mode === true || 
                                   rawSettings['Force Hint Mode'] === 'ENABLED',
                    showHint: rawSettings.show_hint === '1' || 
                             rawSettings.show_hint === true,
                    autoShowHint: rawSettings.auto_show_hint === '1' || 
                                 rawSettings.auto_show_hint === true,
                    requireCorrect: rawSettings.require_correct === '1' || 
                                    rawSettings.require_correct === true,
                    raw: rawSettings
                };
                
                log("Quiz settings:", settings);
                
                return {
                    quizId: quizId,
                    settings: settings
                };
            }
        }
        
        // Method 2: Get from DOM
        const quizContainer = document.querySelector('.wpProQuiz_content');
        if (quizContainer && quizContainer.hasAttribute('data-quiz-meta')) {
            try {
                const meta = JSON.parse(quizContainer.getAttribute('data-quiz-meta'));
                
                if (meta.quiz_post_id) {
                    log("Quiz post ID found in DOM:", meta.quiz_post_id);
                    
                    // Try to get settings from global variable with this ID
                    if (window.quizExtensionsSettings && 
                        window.quizExtensionsSettings.quiz_options && 
                        window.quizExtensionsSettings.quiz_options[meta.quiz_post_id]) {
                        
                        const rawSettings = window.quizExtensionsSettings.quiz_options[meta.quiz_post_id];
                        
                        // Parse settings
                        const settings = {
                            forceHintMode: rawSettings.force_hint_mode === '1' || 
                                        rawSettings.force_hint_mode === true || 
                                        rawSettings['Force Hint Mode'] === 'ENABLED',
                            showHint: rawSettings.show_hint === '1' || 
                                    rawSettings.show_hint === true,
                            autoShowHint: rawSettings.auto_show_hint === '1' || 
                                        rawSettings.auto_show_hint === true,
                            requireCorrect: rawSettings.require_correct === '1' || 
                                            rawSettings.require_correct === true,
                            raw: rawSettings
                        };
                        
                        log("Quiz settings found for DOM ID:", settings);
                        
                        return {
                            quizId: meta.quiz_post_id,
                            settings: settings
                        };
                    }
                }
            } catch (e) {
                log("Error parsing quiz meta:", e);
            }
        }
        
        return null;
    }
    
    // Scan questions in the quiz
    function scanQuestions() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        
        log(`Found ${questionItems.length} questions`);
        
        questionItems.forEach((questionItem, index) => {
            // Extract question ID using various methods
            let questionId = null;
            let postId = null;
            let proId = null;
            
            // Method 1: From data-post-id attribute
            if (questionItem.hasAttribute('data-post-id')) {
                postId = questionItem.getAttribute('data-post-id');
                questionId = postId;
                log(`Question ${index + 1} has post ID: ${postId}`);
            }
            
            // Method 2: From data-question-meta JSON
            if (questionItem.hasAttribute('data-question-meta')) {
                try {
                    const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                    
                    if (meta.question_post_id) {
                        postId = meta.question_post_id;
                        if (!questionId) questionId = postId;
                        log(`Question ${index + 1} has post ID from meta: ${postId}`);
                    }
                    
                    if (meta.question_pro_id) {
                        proId = meta.question_pro_id;
                        if (!questionId) questionId = proId;
                        log(`Question ${index + 1} has pro ID: ${proId}`);
                    }
                } catch (e) {
                    log(`Error parsing question meta for question ${index + 1}:`, e);
                }
            }
            
            // Store question data if found
            if (questionId) {
                state.questionData[questionId] = {
                    element: questionItem,
                    index: index,
                    id: questionId,
                    postId: postId,
                    proId: proId,
                    
                    // Get related elements
                    hintButton: questionItem.querySelector('.wpProQuiz_TipButton'),
                    checkButton: questionItem.querySelector('input[name="check"]'),
                    nextButton: questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton')
                };
                
                log(`Stored data for question ${questionId}:`, state.questionData[questionId]);
            } else {
                log(`WARNING: Could not determine ID for question ${index + 1}`);
            }
        });
    }
    
    // Set up event listeners for the quiz
    function setupEventListeners() {
        log("Setting up event listeners");
        
        // Listen for clicks in the document (delegation)
        document.addEventListener('click', function(event) {
            // Handle hint button clicks
            if (event.target.classList.contains('wpProQuiz_TipButton') || 
                event.target.closest('.wpProQuiz_TipButton')) {
                
                const hintButton = event.target.classList.contains('wpProQuiz_TipButton') ? 
                                 event.target : event.target.closest('.wpProQuiz_TipButton');
                const questionItem = hintButton.closest('.wpProQuiz_listItem');
                
                if (questionItem) {
                    handleHintButtonClick(questionItem);
                }
            }
            
            // Handle answer clicks
            if (event.target.classList.contains('wpProQuiz_questionListItem') || 
                event.target.closest('.wpProQuiz_questionListItem') ||
                (event.target.type === 'radio' || event.target.type === 'checkbox')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                
                if (questionItem) {
                    setTimeout(() => {
                        handleAnswerSelection(questionItem);
                    }, 50);
                }
            }
            
            // Handle check button clicks
            if ((event.target.classList.contains('wpProQuiz_QuestionButton') && 
                 event.target.name === 'check') || 
                event.target.closest('input[name="check"]')) {
                
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                
                if (questionItem) {
                    setTimeout(() => {
                        handleCheckButton(questionItem);
                    }, 50);
                }
            }
        });
        
        // Set up mutation observer to watch for hint display
        setupHintObserver();
    }
    
    // Handle hint button clicks
    function handleHintButtonClick(questionItem) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        log(`Hint button clicked for question ${questionId}`);
        
        // Mark hint as viewed
        state.hintViewed[questionId] = true;
        
        // Add visual indicator
        addHintViewedIndicator(questionItem);
        
        // Check if we can enable the next button
        setTimeout(() => {
            checkAndUpdateNextButton(questionItem, questionId);
        }, 100);
    }
    
    // Handle answer selection
    function handleAnswerSelection(questionItem) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        log(`Answer selected for question ${questionId}`);
        
        // Record answer selection
        state.answersSelected[questionId] = true;
        
        // Check if hint has been viewed
        if (state.hintViewed[questionId]) {
            log(`Hint already viewed for question ${questionId}`);
            
            // Check next button
            checkAndUpdateNextButton(questionItem, questionId);
        } else {
            // Show hint required warning
            log(`Hint not viewed for question ${questionId}, showing warning`);
            addHintRequiredWarning(questionItem);
        }
        
        updateDebugUI();
    }
    
    // Handle check button click
    function handleCheckButton(questionItem) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        log(`Check button clicked for question ${questionId}`);
        
        // Update answer selected state (just to be sure)
        state.answersSelected[questionId] = true;
        
        // Same logic as answer selection
        if (state.hintViewed[questionId]) {
            log(`Hint already viewed for question ${questionId}`);
            checkAndUpdateNextButton(questionItem, questionId);
        } else {
            log(`Hint not viewed for question ${questionId}, showing warning`);
            addHintRequiredWarning(questionItem);
        }
        
        updateDebugUI();
    }
    
    // Set up mutation observer to detect hint display
    function setupHintObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Watch for the hint display (style changes)
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'style' && 
                    mutation.target.classList.contains('wpProQuiz_tipp')) {
                    
                    // Check if it's now visible
                    if (window.getComputedStyle(mutation.target).display !== 'none') {
                        const questionItem = mutation.target.closest('.wpProQuiz_listItem');
                        if (questionItem) {
                            const questionId = getQuestionId(questionItem);
                            
                            if (questionId) {
                                log(`Hint displayed for question ${questionId}`);
                                
                                // Mark hint as viewed
                                state.hintViewed[questionId] = true;
                                
                                // Add visual indicator
                                addHintViewedIndicator(questionItem);
                                
                                // Update next button
                                setTimeout(() => {
                                    checkAndUpdateNextButton(questionItem, questionId);
                                }, 100);
                                
                                updateDebugUI();
                            }
                        }
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    // Process all questions at once
    function processAllQuestions() {
        Object.keys(state.questionData).forEach(questionId => {
            const questionItem = state.questionData[questionId].element;
            
            // Process each question
            checkAndUpdateNextButton(questionItem, questionId);
            
            // Add hint warning if needed
            if (!state.hintViewed[questionId] && state.settings.forceHintMode) {
                addHintRequiredWarning(questionItem);
            }
        });
    }
    
    // Check if next button should be enabled and update its state
    function checkAndUpdateNextButton(questionItem, questionId) {
        if (!state.settings.forceHintMode) return;
        
        log(`Checking next button state for question ${questionId}`);
        
        const hintViewed = state.hintViewed[questionId] || false;
        const answerSelected = state.answersSelected[questionId] || false;
        
        // Find next button
        let nextButton = state.questionData[questionId]?.nextButton;
        
        // If not found in stored data, look directly
        if (!nextButton) {
            nextButton = questionItem.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        }
        
        // If still not found, look for a global next button
        if (!nextButton) {
            nextButton = document.querySelector('.wpProQuiz_button[name=next], .wpProQuiz_nextButton');
        }
        
        if (!nextButton) {
            log(`Could not find next button for question ${questionId}`);
            return;
        }
        
        if (hintViewed) {
            // If hint was viewed, enable next button
            log(`Enabling next button for question ${questionId} - hint viewed`);
            
            // Enable button
            nextButton.disabled = false;
            nextButton.removeAttribute('disabled');
            
            // Update styles
            nextButton.classList.remove('wpProQuiz_button_disabled');
            nextButton.style.opacity = '1';
            nextButton.style.cursor = 'pointer';
        } else {
            // If hint not viewed, disable next button
            log(`Disabling next button for question ${questionId} - hint not viewed`);
            
            // Disable button
            nextButton.disabled = true;
            nextButton.setAttribute('disabled', 'disabled');
            
            // Update styles
            nextButton.classList.add('wpProQuiz_button_disabled');
            nextButton.style.opacity = '0.5';
            nextButton.style.cursor = 'not-allowed';
        }
        
        updateDebugUI();
    }
    
    // Add visual indicator for hint viewed
    function addHintViewedIndicator(questionItem) {
        // If indicator already exists, don't add another
        if (questionItem.querySelector('.hint-viewed-indicator')) return;
        
        // Create indicator
        const indicator = document.createElement('div');
        indicator.className = 'hint-viewed-indicator';
        indicator.textContent = 'רמז נצפה'; // "Hint viewed" in Hebrew
        indicator.style.cssText = 'color: #4caf50; font-weight: bold; padding: 8px; margin: 8px 0; font-size: 14px; text-align: right; background-color: rgba(76, 175, 80, 0.1); border-right: 3px solid #4caf50;';
        
        // Add to question item
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList) {
            questionList.appendChild(indicator);
        }
    }
    
    // Add warning that hint is required
    function addHintRequiredWarning(questionItem) {
        // If warning already exists, don't add another
        if (questionItem.querySelector('.hint-required-warning')) return;
        
        // Check for hint button
        const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
        if (!hintButton) return;
        
        // Create warning
        const warning = document.createElement('div');
        warning.className = 'hint-required-warning';
        warning.textContent = 'התשובה שלך שגויה. אנא לחץ על כפתור הרמז'; // "Your answer is incorrect. Please click the hint button" in Hebrew
        warning.style.cssText = 'color: #f44336; font-weight: bold; padding: 8px; margin: 8px 0; font-size: 14px; text-align: right; background-color: rgba(244, 67, 54, 0.1); border-right: 3px solid #f44336;';
        
        // Add to question item
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList) {
            questionList.appendChild(warning);
        }
        
        // Highlight hint button
        hintButton.style.animation = 'pulse-hint-button 1.5s infinite';
        hintButton.style.boxShadow = '0 0 10px rgba(255, 152, 0, 0.7)';
        hintButton.style.border = '2px solid #ff9800';
        
        // Add animation if not already present
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
    
    // Get question ID using multiple methods
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Method 1: From data-post-id
        if (questionItem.hasAttribute('data-post-id')) {
            return questionItem.getAttribute('data-post-id');
        }
        
        // Method 2: From data-question-meta
        if (questionItem.hasAttribute('data-question-meta')) {
            try {
                const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                if (meta.question_post_id) {
                    return meta.question_post_id.toString();
                } else if (meta.question_pro_id) {
                    return meta.question_pro_id.toString();
                }
            } catch (e) {}
        }
        
        // Method 3: Check already stored data
        const storedQuestion = Object.values(state.questionData).find(q => q.element === questionItem);
        if (storedQuestion) {
            return storedQuestion.id;
        }
        
        // Method 4: From index in DOM
        const allQuestions = Array.from(document.querySelectorAll('.wpProQuiz_listItem'));
        const index = allQuestions.indexOf(questionItem);
        if (index >= 0) {
            // Try to match by index to stored data
            const matchByIndex = Object.values(state.questionData).find(q => q.index === index);
            if (matchByIndex) {
                return matchByIndex.id;
            }
        }
        
        log("Could not find question ID", questionItem);
        return null;
    }
    
    // Create debug UI panel
    function createDebugUI() {
        if (document.getElementById('fhm-debug-panel')) return;
        
        // Create panel
        const panel = document.createElement('div');
        panel.id = 'fhm-debug-panel';
        panel.style.cssText = 'position: fixed; top: 10px; right: 10px; background: rgba(0, 0, 0, 0.8); color: white; padding: 10px; border-radius: 5px; font-family: monospace; z-index: 99999; max-width: 300px; max-height: 300px; overflow: auto;';
        
        // Create title
        const title = document.createElement('div');
        title.textContent = `Force Hint Mode Debug (${VERSION})`;
        title.style.cssText = 'font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #555; padding-bottom: 5px;';
        panel.appendChild(title);
        
        // Create content container
        const content = document.createElement('div');
        content.id = 'fhm-debug-content';
        content.style.cssText = 'font-size: 12px;';
        panel.appendChild(content);
        
        // Add buttons
        const buttons = document.createElement('div');
        buttons.style.cssText = 'margin-top: 10px; display: flex; gap: 5px;';
        
        // Reset button
        const resetBtn = document.createElement('button');
        resetBtn.textContent = 'Reset';
        resetBtn.style.cssText = 'padding: 3px 5px; font-size: 11px;';
        resetBtn.addEventListener('click', function() {
            state.hintViewed = {};
            state.answersSelected = {};
            updateDebugUI();
            processAllQuestions();
        });
        buttons.appendChild(resetBtn);
        
        // Hide button
        const hideBtn = document.createElement('button');
        hideBtn.textContent = 'Hide';
        hideBtn.style.cssText = 'padding: 3px 5px; font-size: 11px;';
        hideBtn.addEventListener('click', function() {
            panel.style.display = 'none';
            
            // Add show button
            const showBtn = document.createElement('button');
            showBtn.textContent = 'Debug';
            showBtn.style.cssText = 'position: fixed; bottom: 10px; right: 10px; padding: 5px; z-index: 99999;';
            showBtn.addEventListener('click', function() {
                panel.style.display = 'block';
                showBtn.remove();
            });
            document.body.appendChild(showBtn);
        });
        buttons.appendChild(hideBtn);
        
        panel.appendChild(buttons);
        
        // Add to document
        document.body.appendChild(panel);
        
        state.debugPanel = panel;
        
        // Initial update
        updateDebugUI();
    }
    
    // Update debug UI
    function updateDebugUI() {
        if (!state.debug || !state.debugPanel) return;
        
        const content = document.getElementById('fhm-debug-content');
        if (!content) return;
        
        // Clear current content
        content.innerHTML = '';
        
        // Add quiz info
        content.innerHTML += `
            <div style="margin-bottom: 5px;"><b>Quiz ID:</b> ${state.quizId || 'Unknown'}</div>
            <div style="margin-bottom: 5px;"><b>Force Hint:</b> ${state.settings?.forceHintMode ? 'Yes' : 'No'}</div>
            <div style="margin-bottom: 10px;"><b>Questions:</b> ${Object.keys(state.questionData).length}</div>
        `;
        
        // Add question state
        if (Object.keys(state.questionData).length > 0) {
            content.innerHTML += '<div style="font-weight: bold; margin-bottom: 5px;">Questions:</div>';
            
            Object.keys(state.questionData).forEach(id => {
                const q = state.questionData[id];
                const hintViewed = state.hintViewed[id] ? '✅' : '❌';
                const answerSelected = state.answersSelected[id] ? '✅' : '❌';
                
                content.innerHTML += `
                    <div style="margin-bottom: 5px; padding: 3px; background: rgba(255,255,255,0.1); border-radius: 3px;">
                        <div><b>Q${q.index+1} (${id}):</b></div>
                        <div>Hint: ${hintViewed} | Answer: ${answerSelected}</div>
                    </div>
                `;
            });
        }
    }
})();
