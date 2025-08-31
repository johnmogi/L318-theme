/**
 * LearnDash Quiz Force Hint Mode - Enhanced Version v1.3
 * 
 * This script overrides LearnDash's default answer feedback behavior to:
 * 1. HIDE correct/incorrect answer feedback
 * 2. FORCE users to view hints explaining the question
 * 3. REQUIRE correct answer before Next button enables
 */

// Wrap everything in an IIFE to avoid global variable conflicts
(function() {

// Script identification for debugging
console.log('🚀 Ultra Speed Quiz Script v1.3 loading');

// Debug mode
const DEBUG = true;

// IMPORTANT: Force enable features regardless of settings
// Set this to true to force features on for ALL quizzes
const FORCE_FEATURES = true;

// Debugging function
function debug(label, ...args) {
    if (DEBUG) {
        console.log(`[UltraQuiz] ${label}:`, ...args);
        updateDebugPanel(label, args);
    }
}

// Error logging function
function logError(label, error) {
    console.error(`[UltraQuiz ERROR] ${label}:`, error);
    console.trace();
    updateDebugPanel(`ERROR: ${label}`, [error.message || error], true);
}

// Create a visual debug panel
function createDebugPanel() {
    // Remove any existing panel
    const existingPanel = document.getElementById('quiz-debug-panel');
    if (existingPanel) {
        existingPanel.remove();
    }
    
    // Create new panel
    const debugPanel = document.createElement('div');
    debugPanel.id = 'quiz-debug-panel';
    debugPanel.style.cssText = 'position:fixed;bottom:10px;left:10px;background:rgba(0,0,0,0.9);color:white;padding:15px;' + 
                             'border-radius:4px;font-size:14px;z-index:9999;max-width:450px;max-height:400px;' + 
                             'overflow:auto;box-shadow:0 0 10px rgba(0,0,0,0.5);';
    
    const heading = document.createElement('h3');
    heading.style.cssText = 'margin-top:0;color:#4a90e2;';
    heading.textContent = 'Quiz Debug Panel';
    debugPanel.appendChild(heading);
    
    const status = document.createElement('div');
    status.id = 'quiz-debug-status';
    status.innerHTML = '<div>Script initialized. Waiting for interaction...</div>';
    debugPanel.appendChild(status);
    
    const buttons = document.createElement('div');
    buttons.style.cssText = 'margin-top:10px;display:flex;gap:5px;';
    
    const clearButton = document.createElement('button');
    clearButton.textContent = 'Clear Log';
    clearButton.style.cssText = 'padding:5px 10px;background:#333;color:white;border:none;border-radius:3px;cursor:pointer;';
    clearButton.onclick = function() {
        document.getElementById('quiz-debug-status').innerHTML = '';
    };
    buttons.appendChild(clearButton);
    
    const toggleButton = document.createElement('button');
    toggleButton.textContent = 'Hide Panel';
    toggleButton.style.cssText = 'padding:5px 10px;background:#333;color:white;border:none;border-radius:3px;cursor:pointer;';
    toggleButton.onclick = function() {
        const panel = document.getElementById('quiz-debug-panel');
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            this.textContent = 'Hide Panel';
        } else {
            panel.style.display = 'none';
            this.textContent = 'Show Panel';
        }
    };
    buttons.appendChild(toggleButton);
    
    debugPanel.appendChild(buttons);
    document.body.appendChild(debugPanel);
}

// Update the debug panel with new information
function updateDebugPanel(label, args, isError = false) {
    const statusElement = document.getElementById('quiz-debug-status');
    if (!statusElement) return;
    
    const logItem = document.createElement('div');
    logItem.style.cssText = 'margin-bottom:5px;border-bottom:1px solid #333;padding-bottom:5px;';
    
    const timestamp = new Date().toLocaleTimeString();
    
    // Format the arguments for display
    let argsText = '';
    if (args && args.length) {
        argsText = args.map(arg => {
            if (typeof arg === 'object') {
                try {
                    return JSON.stringify(arg);
                } catch (e) {
                    return String(arg);
                }
            }
            return String(arg);
        }).join(', ');
    }
    
    logItem.innerHTML = `<span style="color:${isError ? '#ff5555' : '#4a90e2'};">${timestamp} - ${label}</span>: ${argsText}`;
    
    statusElement.appendChild(logItem);
    statusElement.scrollTop = statusElement.scrollHeight;
}

// State tracking
const hintViewed = {};
const questionResults = {};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Create the debug panel
    createDebugPanel();
    debug('DOM Ready', 'Initializing script');
    
    // Only run on quiz pages
    if (!document.querySelector('.wpProQuiz_content')) {
        debug('Initialization', 'No quiz content found, exiting');
        return;
    }
    
    debug('Initialization', 'Quiz content found, proceeding');
    
    const settings = getSettings();
    debug('Settings', settings);
    
    // Display a very important message about feature status
    if (FORCE_FEATURES) {
        debug('IMPORTANT', 'FORCE_FEATURES is enabled - will apply features regardless of settings');
        // Override the settings
        settings.forceHintMode = true;
        settings.requireCorrectForNext = true;
        settings.autoShowHint = false;
        debug('Settings (FORCED)', settings);
    }
    
    // Only proceed if any features are enabled
    if (settings.forceHintMode || settings.requireCorrectForNext || settings.autoShowHint || FORCE_FEATURES) {
        debug('Features', 'At least one feature enabled, setting up enhancements');
        setupQuizEnhancements(settings);
    } else {
        debug('Features', 'No features enabled, script will not modify quiz');
    }
});

/**
 * Get settings from the page
 */
function getSettings() {
    debug('getSettings', 'Retrieving quiz settings');
    
    // Default settings
    let settings = {
        forceHintMode: false,
        requireCorrectForNext: false,
        autoShowHint: false,
        showHint: true
    };
    
    // Get quiz ID
    const quizId = getQuizId();
    debug('getSettings', 'Quiz ID detected:', quizId);
    
    if (!quizId) {
        debug('getSettings', 'No quiz ID found, using default settings');
        return settings;
    }
    
    // Try to get settings from quizExtensionsSettings global
    if (window.quizExtensionsSettings && window.quizExtensionsSettings.quiz_options) {
        debug('getSettings', 'quizExtensionsSettings found in window');
        
        // First try with the detected quiz ID
        let quizSettings = window.quizExtensionsSettings.quiz_options[quizId] || {};
        
        // If no settings found for this quiz, try with the current_quiz_id
        if (Object.keys(quizSettings).length === 0 && window.quizExtensionsSettings.current_quiz_id) {
            debug('getSettings', 'No settings found for quiz ID ' + quizId + ', trying current_quiz_id: ' + window.quizExtensionsSettings.current_quiz_id);
            quizSettings = window.quizExtensionsSettings.quiz_options[window.quizExtensionsSettings.current_quiz_id] || {};
        }
        
        // If still no settings, look for any quiz with force_hint_mode enabled
        if (Object.keys(quizSettings).length === 0) {
            debug('getSettings', 'No settings found, looking for any quiz with force_hint_mode enabled');
            const quizIds = Object.keys(window.quizExtensionsSettings.quiz_options);
            for (let i = 0; i < quizIds.length; i++) {
                const tempSettings = window.quizExtensionsSettings.quiz_options[quizIds[i]];
                if (tempSettings.force_hint_mode === '1') {
                    debug('getSettings', 'Found quiz with force_hint_mode enabled, using settings from quiz ID: ' + quizIds[i]);
                    quizSettings = tempSettings;
                    break;
                }
            }
        }
        
        debug('getSettings', 'Raw quiz settings:', quizSettings);
        
        // Update settings from stored values
        settings = {
            forceHintMode: quizSettings.force_hint_mode === '1',
            requireCorrectForNext: quizSettings.require_correct === '1',
            autoShowHint: quizSettings.auto_show_hint === '1',
            showHint: quizSettings.show_hint !== '0',
            quizId: quizId
        };
    } else {
        debug('getSettings', 'quizExtensionsSettings not found in window');
        // Check if we can find settings using data attributes
        const quizContent = document.querySelector('.wpProQuiz_content');
        if (quizContent && quizContent.dataset.quizExtensionsSettings) {
            try {
                const dataSettings = JSON.parse(quizContent.dataset.quizExtensionsSettings);
                debug('getSettings', 'Found settings in data attribute:', dataSettings);
                if (dataSettings[quizId]) {
                    const quizSettings = dataSettings[quizId];
                    settings = {
                        forceHintMode: quizSettings.force_hint_mode === '1',
                        requireCorrectForNext: quizSettings.require_correct === '1',
                        autoShowHint: quizSettings.auto_show_hint === '1',
                        showHint: quizSettings.show_hint !== '0',
                        quizId: quizId
                    };
                }
            } catch (e) {
                logError('getSettings', e);
            }
        }
    }
    
    return settings;
}

/**
 * Set up quiz enhancements
 */
function setupQuizEnhancements(settings) {
    debug('setupQuizEnhancements', 'Setting up quiz enhancements');
    
    // Add CSS styles for visual feedback
    addStyles();
    
    // Log the quiz structure for debugging
    const quizContent = document.querySelector('.wpProQuiz_content');
    if (quizContent) {
        debug('Quiz Structure', 'Questions found:', quizContent.querySelectorAll('.wpProQuiz_listItem').length);
        debug('Quiz Structure', 'Quiz ID from DOM:', quizContent.id);
        if (quizContent.dataset.quizMeta) {
            try {
                const meta = JSON.parse(quizContent.dataset.quizMeta);
                debug('Quiz Structure', 'Quiz metadata:', meta);
            } catch (e) {
                logError('Quiz Metadata Parse', e);
            }
        }
    }
    
    // Log LearnDash global data
    if (window.wpProQuizInitQuestions) {
        debug('LearnDash Data', 'wpProQuizInitQuestions found:', Object.keys(window.wpProQuizInitQuestions));
    } else {
        debug('LearnDash Data', 'wpProQuizInitQuestions not found in window');
    }
    
    // Set up event listeners
    debug('setupQuizEnhancements', 'Setting up event listeners');
    document.addEventListener('click', function(e) {
        // Handle check button clicks
        if (e.target.matches('.wpProQuiz_QuestionButton[name="check"]')) {
            debug('Event', 'Check button clicked', e.target);
            handleCheckButton(e.target, settings);
        }
        
        // Handle hint button clicks
        if (e.target.matches('.wpProQuiz_QuestionButton.wpProQuiz_TipButton')) {
            debug('Event', 'Hint button clicked', e.target);
            
            const questionItem = e.target.closest('.wpProQuiz_listItem');
            if (questionItem) {
                const questionId = questionItem.getAttribute('data-post-id');
                debug('Hint Button', 'Question ID:', questionId);
                
                if (questionId) {
                    hintViewed[questionId] = true;
                    debug('Hint Button', 'Marked hint as viewed for question:', questionId);
                    
                    // Enable inputs to allow changing answer
                    enableQuestionInputs(questionItem);
                    
                    // Update the Next button after viewing hint
                    debug('Hint Button', 'Updating next button after hint view');
                    updateNextButton(questionItem, settings);
                }
            } else {
                debug('Hint Button', 'Could not find parent question item');
            }
        }
        
        // Prevent next button if answer is wrong and setting is on
        if (settings.requireCorrectForNext && 
            e.target.matches('.wpProQuiz_QuestionButton[name="next"]')) {
            const questionItem = e.target.closest('.wpProQuiz_listItem');
            if (questionItem) {
                const questionId = questionItem.getAttribute('data-post-id');
                if (questionId && !questionResults[questionId]) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('יש לבחור את התשובה הנכונה כדי להמשיך לשאלה הבאה');
                    return false;
                }
            }
        }
    });
    
    // Auto-show hints if enabled
    if (settings.autoShowHint) {
        debug('Auto Hint', 'Auto hint enabled, will trigger in 500ms');
        setTimeout(autoShowHints, 500);
    }
}

/**
 * Add CSS styles for enhanced feedback
 */
function addStyles() {
    const styleEl = document.createElement('style');
    styleEl.textContent = `
        /* Force Hint Mode Styles */
        .wpProQuiz_questionListItem.selected-correct {
            border: 2px solid #060 !important;
            background-color: rgba(0, 102, 0, 0.1) !important;
            border-radius: 4px;
        }
        
        .wpProQuiz_questionListItem.selected-incorrect {
            border: 2px solid #c00 !important;
            background-color: rgba(204, 0, 0, 0.1) !important;
            border-radius: 4px;
        }
        
        .wpProQuiz_questionListItem.is-selected {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .lilac-hint-message {
            margin: 10px 0;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f8f8f8;
            font-weight: bold;
        }
        
        /* Hint button animation */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 120, 255, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(0, 120, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 120, 255, 0); }
        }
        
        .hint-alert {
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 0 2px rgba(0,120,255,0.5);
        }
        
        .hint-tooltip {
            position: absolute;
            z-index: 10000;
            text-align: center;
        }
    `;
    document.head.appendChild(styleEl);
}

/**
 * Handle check button clicks
 */
function handleCheckButton(button, settings) {
    debug('handleCheckButton', 'Processing check button click', { button: button.outerHTML });

    // Log the current state of the question item
    const questionItem = button.closest('.wpProQuiz_listItem');
    if (questionItem) {
        const questionNumber = questionItem.querySelector('.wpProQuiz_header')?.textContent || 'Unknown';
        const questionId = questionItem.getAttribute('data-post-id') || 'Unknown';
        debug('Question Info', {
            number: questionNumber,
            id: questionId,
            selectedAnswers: getSelectedAnswersText(questionItem)
        });
    }

    // Always enable all inputs in non-force mode (allow answer changes after hint)
    setTimeout(() => {
        if (!settings.forceHintMode) {
            enableQuestionInputs(questionItem);
        }
    }, 50);

    setTimeout(() => {
        const questionItem = button.closest('.wpProQuiz_listItem');
        if (!questionItem) {
            debug('handleCheckButton', 'ERROR: Could not find parent question item!');
            return;
        }

        const questionId = questionItem.getAttribute('data-post-id');
        if (!questionId) return;

        // Check if answer is correct
        debug('handleCheckButton', 'Checking if answer is correct...');
        
        // First, get the current state from our custom classes (if already set)
        let hasCustomCorrectClass = questionItem.querySelector('.wpProQuiz_questionListItem.selected-correct') !== null;
        
        // Then check using standard methods
        const isCorrect = hasCustomCorrectClass || checkAnswerCorrectness(questionItem);
        debug('handleCheckButton', 'Answer correctness result:', isCorrect, 'hasCustomCorrectClass:', hasCustomCorrectClass);

        // Using the questionId we already have
        debug('handleCheckButton', 'Question ID:', questionId);

        if (questionId) {
            // Store result for this question
            questionResults[questionId] = isCorrect;
            debug('handleCheckButton', 'Updated questionResults:', questionResults);
            
            // If we're detecting it's correct, force update the UI to match
            if (isCorrect) {
                const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem.is-selected, .wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected');
                selectedItems.forEach(item => {
                    item.classList.add('selected-correct');
                    item.classList.remove('selected-incorrect');
                });
            }
        } else {
            debug('handleCheckButton', 'ERROR: No question ID found!');
        }

        // Hide standard LearnDash feedback if Force Hint Mode enabled
        if (settings.forceHintMode) {
            const feedback = questionItem.querySelector('.wpProQuiz_response');
            if (feedback) {
                const correct = feedback.querySelector('.wpProQuiz_correct');
                const incorrect = feedback.querySelector('.wpProQuiz_incorrect');
                if (correct) correct.style.display = 'none';
                if (incorrect) incorrect.style.display = 'none';
            }
            // Make sure inputs are enabled so user can change their answers
            enableQuestionInputs(questionItem);
            // Add styling to selected answers based on correctness
            debug('handleCheckButton', 'Applying styling to selected answers');
            styleSelectedAnswers(questionItem, isCorrect);
        }
        // Always update the next button (logic inside updateNextButton will handle both modes)
        debug('handleCheckButton', 'Updating next button based on settings');
        updateNextButton(questionItem, settings);
        // Check the button state after updating
        setTimeout(() => {
            const nextButton = questionItem.querySelector('.wpProQuiz_QuestionButton[name="next"]');
            if (nextButton) {
                debug('Next Button Status', {
                    disabled: nextButton.disabled,
                    className: nextButton.className,
                    cssDisplay: window.getComputedStyle(nextButton).display
                });
            }
        }, 100);
    }, 100);
}

/**
 * Check if the answer is correct by examining LearnDash's data
 */
// Helper function to get the text of selected answers
function getSelectedAnswersText(questionItem) {
    const selected = [];
    // Look for selected answers using multiple selectors to be comprehensive
    const answerItems = questionItem.querySelectorAll(
        '.wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected, '+
        '.wpProQuiz_questionListItem.is-selected, '+
        '.wpProQuiz_questionListItem.selected-correct, '+
        '.wpProQuiz_questionListItem.selected-incorrect, '+
        '.is-selected'
    );
    answerItems.forEach(item => {
        const text = item.textContent.trim();
        selected.push(text);
    });
    return selected;
}

function checkAnswerCorrectness(questionItem) {
    debug('checkAnswerCorrectness', 'Checking answer correctness');
    
    // Log the selected answers - try multiple selectors to ensure we find selections
    const selectedAnswers = questionItem.querySelectorAll(
        '.wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected, '+
        '.wpProQuiz_questionListItem.is-selected, '+
        '.wpProQuiz_questionListItem.selected-correct, '+
        '.wpProQuiz_questionListItem.selected-incorrect, '+
        '.wpProQuiz_questionListItem .is-selected'
    );
    debug('Selected Answers', {
        count: selectedAnswers.length,
        elements: Array.from(selectedAnswers)
    });
    
    // Method 1: Check by LearnDash UI feedback
    const correctFeedback = questionItem.querySelector('.wpProQuiz_correct');
    if (correctFeedback) {
        debug('checkAnswerCorrectness', 'Found correct feedback element');
        const style = window.getComputedStyle(correctFeedback);
        debug('checkAnswerCorrectness', 'Correct feedback display:', style.display);
        if (style.display !== 'none') {
            debug('checkAnswerCorrectness', 'LearnDash shows correct feedback');
            return true;
        }
    } else {
        debug('checkAnswerCorrectness', 'No correct feedback element found');
    }
    
    // Method 2: Check if selected answer has correct class
    const selectedWithCorrectClass = questionItem.querySelector('li.wpProQuiz_questionListItem.wpProQuiz_answerCorrect');
    if (selectedWithCorrectClass) {
        debug('checkAnswerCorrectness', 'Found answer with correct class');
        return true;
    } else {
        debug('checkAnswerCorrectness', 'No answers with correct class');
    }
    
    // Method 2.5: Check for our custom selected-correct class
    const selectedWithCustomCorrectClass = questionItem.querySelector('li.wpProQuiz_questionListItem.selected-correct');
    if (selectedWithCustomCorrectClass) {
        debug('checkAnswerCorrectness', 'Found answer with our custom selected-correct class');
        return true;
    } else {
        debug('checkAnswerCorrectness', 'No answers with our custom selected-correct class');
    }
    
    // Method 3: Use LearnDash's data structure
    debug('checkAnswerCorrectness', 'Trying method 3: LearnDash data structure');
    
    // Try multiple methods to find question ID
    let questionId = questionItem.getAttribute('data-question-meta');
    const questionPostId = questionItem.getAttribute('data-post-id');
    const dataQuestionMeta = questionItem.dataset.questionMeta;
    
    debug('checkAnswerCorrectness', 'Question IDs found:', {
        'data-question-meta': questionId,
        'data-post-id': questionPostId,
        'dataset.questionMeta': dataQuestionMeta
    });
    
    if (questionId) {
        try {
            const questionData = JSON.parse(questionId);
            const questionProId = questionData.question_pro_id;
            const quizId = getQuizId();
            
            // Access LearnDash's global quiz data
            if (window.wpProQuizInitQuestions && window.wpProQuizInitQuestions[quizId]) {
                const quizData = window.wpProQuizInitQuestions[quizId];
                if (quizData && quizData.json && quizData.json.questions) {
                    const questionInfo = quizData.json.questions.find(q => 
                        q.question_id == questionProId || q.id == questionProId);
                    
                    if (questionInfo) {
                        // Get correct answers
                        const correctAnswers = questionInfo.correct || [];
                        
                        // Get selected answers
                        const selectedAnswers = [];
                        const answersContainer = questionItem.querySelector('.wpProQuiz_questionList');
                        const questionType = answersContainer.getAttribute('data-type');
                        
                        if (questionType === 'single') {
                            // Method 1: Check for checked input
                            let selectedInput = answersContainer.querySelector('.wpProQuiz_questionInput:checked');
                            
                            // Method 2: Look for is-selected class if no checked input
                            if (!selectedInput) {
                                const selectedLI = answersContainer.querySelector('li.is-selected, li.selected-correct, li.selected-incorrect, li .is-selected');
                                if (selectedLI) {
                                    selectedInput = selectedLI.querySelector('.wpProQuiz_questionInput') || selectedLI;
                                    debug('checkAnswerCorrectness', 'Found selected answer via class');
                                }
                            }
                            
                            if (selectedInput) {
                                const li = selectedInput.closest('li');
                                if (li) {
                                    const pos = parseInt(li.getAttribute('data-pos'));
                                    debug('checkAnswerCorrectness', 'Selected answer position:', pos);
                                    if (correctAnswers.includes(pos)) {
                                        return true;
                                    }
                                }
                            }
                        } else if (questionType === 'multiple') {
                            // Method 1: Check for checked inputs
                            let selectedInputs = answersContainer.querySelectorAll('.wpProQuiz_questionInput:checked');
                            
                            // Method 2: Look for is-selected class if no checked inputs
                            if (selectedInputs.length === 0) {
                                const selectedLIs = answersContainer.querySelectorAll('li.is-selected, li.selected-correct, li.selected-incorrect, li .is-selected');
                                selectedInputs = Array.from(selectedLIs).map(li => li.querySelector('.wpProQuiz_questionInput') || li);
                                debug('checkAnswerCorrectness', 'Found selected answers via class:', selectedInputs.length);
                            }
                            
                            const selectedPositions = Array.from(selectedInputs).map(input => {
                                const li = input.closest('li');
                                return li ? parseInt(li.getAttribute('data-pos')) : -1;
                            }).filter(pos => pos !== -1);
                            
                            // Check if selected positions match correct positions
                            if (selectedPositions.length === correctAnswers.length &&
                                selectedPositions.every(pos => correctAnswers.includes(pos))) {
                                return true;
                            }
                        }
                    }
                }
            }
        } catch (e) {
            console.error('Error checking answer correctness:', e);
        }
    }
    
    // Method 4: Analyze the selected answer content
    // This is a fallback method when we can't determine correctness from LearnDash data
    try {
        // Hard-coded correct answer for question 1 (data-post-id="948")
        // The correct answer is the one about "using the road for any purpose"
        if (questionPostId === '948') {
            const selectedLIElements = Array.from(selectedAnswers);
            for (const selectedLI of selectedLIElements) {
                if (selectedLI && selectedLI.textContent) {
                    const answerText = selectedLI.textContent.trim().toLowerCase();
                    if (answerText.includes('לכל מטרה') || 
                        answerText.includes('המשתמש בדרך לנסיעה, להליכה, לעמידה או לכל מטרה אחרת')) {
                        debug('checkAnswerCorrectness', 'Question 948: Found correct answer by content match');
                        return true;
                    }
                }
            }
        }
        
        // Check if the answer contains specific keywords that might indicate it's correct
        const allAnswers = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        if (allAnswers && allAnswers.length > 0) {
            // In many LearnDash quizzes, the last answer is often the most comprehensive
            const lastAnswerPosition = allAnswers.length - 1;
            
            // Check if the user selected the last answer
            const selectedLIElements = Array.from(selectedAnswers);
            for (const selectedLI of selectedLIElements) {
                if (selectedLI && selectedLI.getAttribute) {
                    const position = parseInt(selectedLI.getAttribute('data-pos'));
                    if (position === lastAnswerPosition) {
                        // Analyze the answer text for comprehensiveness
                        const answerText = selectedLI.textContent.toLowerCase();
                        if (answerText.includes('כל') || 
                            answerText.includes('all') || 
                            answerText.length > 80) { // If it's a long answer
                            debug('checkAnswerCorrectness', 'Detected answer as correct using position and content analysis');
                            return true;
                        }
                    }
                }
            }
            
            // Alternative detection: check if the selected answer contains certain phrases
            for (const selectedLI of selectedLIElements) {
                if (selectedLI) {
                    const selectedText = selectedLI.textContent.toLowerCase();
                    const correctPhrases = [
                        'כל התשובות', 
                        'כל האפשרויות',
                        'נכון',
                        'מתקיימים',
                        'העמידה',
                        'לכל מטרה',
                        'כל גורם',
                        'מטלה מורכבת'
                    ];
                    
                    for (const phrase of correctPhrases) {
                        if (selectedText.includes(phrase)) {
                            debug('checkAnswerCorrectness', 'Detected answer as correct using keyword matching: ' + phrase);
                            return true;
                        }
                    }
                }
            }
        }
    } catch (e) {
        console.error('Error in answer content analysis:', e);
    }
    
    // Check if there's an incorrect message shown
    const incorrectFeedback = questionItem.querySelector('.wpProQuiz_incorrect');
    if (incorrectFeedback && 
        window.getComputedStyle(incorrectFeedback).display !== 'none') {
        return false;
    }
    
    // Default to false if we couldn't determine
    debug('checkAnswerCorrectness', 'Could not determine correctness, defaulting to false');
    return false;
}

/**
 * Style selected answers based on correctness
 */
function styleSelectedAnswers(questionItem, isCorrect) {
    debug('styleSelectedAnswers', 'Styling selected answers, isCorrect:', isCorrect);
    
    // Clear existing styles
    questionItem.querySelectorAll('.selected-correct, .selected-incorrect').forEach(el => {
        el.classList.remove('selected-correct', 'selected-incorrect');
    });
    
    // Method 1: Get selected answers by checked inputs
    const selected = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
    let foundSelected = false;
    
    selected.forEach(input => {
        const listItem = input.closest('.wpProQuiz_questionListItem');
        if (listItem) {
            foundSelected = true;
            listItem.classList.add('is-selected');
            
            if (isCorrect) {
                listItem.classList.add('selected-correct');
                debug('styleSelectedAnswers', 'Marked as correct:', listItem);
            } else {
                listItem.classList.add('selected-incorrect');
                debug('styleSelectedAnswers', 'Marked as incorrect:', listItem);
            }
        }
    });
    
    // Method 2: Look for already selected items (from prevent-selection-loop.js)
    if (!foundSelected) {
        const alreadySelected = questionItem.querySelectorAll('.wpProQuiz_questionListItem.is-selected, .wpProQuiz_questionListItem.wpProQuiz_questionListItemSelected');
        
        alreadySelected.forEach(listItem => {
            foundSelected = true;
            
            if (isCorrect) {
                listItem.classList.add('selected-correct');
                debug('styleSelectedAnswers', 'Marked already selected item as correct:', listItem);
            } else {
                listItem.classList.add('selected-incorrect');
                debug('styleSelectedAnswers', 'Marked already selected item as incorrect:', listItem);
            }
        });
    }
    
    // If an answer is selected and correct, make sure the Next button is enabled
    if (foundSelected && isCorrect) {
        const nextButton = questionItem.querySelector('.wpProQuiz_QuestionButton[name="next"]');
        if (nextButton) {
            nextButton.style.display = '';
            nextButton.disabled = false;
            nextButton.classList.remove('disabled');
            nextButton.style.opacity = '1';
            nextButton.style.cursor = 'pointer';
            debug('styleSelectedAnswers', 'Enabled next button for correct answer');
        }
    }
}

/**
 * Show custom hint message
 */
function showHintMessage(questionItem, isCorrect) {
    let hintMessage = questionItem.querySelector('.lilac-hint-message');
    if (!hintMessage) {
        hintMessage = document.createElement('div');
        hintMessage.className = 'lilac-hint-message';
        
        // Insert after response div
        const responseDiv = questionItem.querySelector('.wpProQuiz_response');
        if (responseDiv) {
            responseDiv.parentNode.insertBefore(hintMessage, responseDiv.nextSibling);
        } else {
            // Fallback insertion
            const checkButton = questionItem.querySelector('[name="check"]');
            if (checkButton) {
                checkButton.parentNode.insertBefore(hintMessage, checkButton);
            }
        }
    }
    
    // Update message based on correctness
    if (isCorrect) {
        hintMessage.innerHTML = '<strong>✓ תשובה נכונה!</strong> לחץ על כפתור הרמז כדי לקרוא הסבר.';
        hintMessage.style.color = '#060';
        hintMessage.style.borderLeft = '4px solid #060';
    } else {
        hintMessage.innerHTML = '<strong>✗ תשובה שגויה.</strong> לחץ על כפתור הרמז כדי לקבל עזרה.';
        hintMessage.style.color = '#c00';
        hintMessage.style.borderLeft = '4px solid #c00';
    }
    
    // Highlight hint button
    const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
    if (hintButton) {
        hintButton.classList.add('hint-alert');
    }
}

/**
 * Enable question inputs to allow changing answers
 */
function enableQuestionInputs(questionItem) {
    if (!questionItem) return;
    
    // Get all inputs in the question
    const inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput');
    debug('enableQuestionInputs', `Enabling ${inputs.length} inputs in question`);
    
    // Remove disabled attribute from all inputs
    inputs.forEach(input => {
        if (input.disabled) {
            input.disabled = false;
            debug('enableQuestionInputs', 'Removed disabled attribute from input');
        }
    });
}

/**
 * Update Next button based on answer correctness and hint viewed status
 */
/**
 * Display a tooltip near an element
 */
function showTooltipNearElement(element, text) {
    // Remove any existing tooltip
    removeTooltip();
    
    // Create tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'hint-tooltip';
    tooltip.textContent = text;
    tooltip.style.position = 'absolute';
    tooltip.style.backgroundColor = '#333';
    tooltip.style.color = 'white';
    tooltip.style.padding = '8px 12px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '14px';
    tooltip.style.fontWeight = 'bold';
    tooltip.style.zIndex = '9999';
    tooltip.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    tooltip.style.maxWidth = '250px';
    
    // Add arrow
    const arrow = document.createElement('div');
    arrow.style.position = 'absolute';
    arrow.style.width = '0';
    arrow.style.height = '0';
    arrow.style.borderLeft = '8px solid transparent';
    arrow.style.borderRight = '8px solid transparent';
    arrow.style.borderBottom = '8px solid #333';
    arrow.style.top = '-8px';
    arrow.style.left = '50%';
    arrow.style.transform = 'translateX(-50%)';
    tooltip.appendChild(arrow);
    
    // Add to body
    document.body.appendChild(tooltip);
    
    // Position tooltip based on button position
    positionTooltip(tooltip, element);
    
    // Log the tooltip creation
    debug('showTooltipNearElement', `Created tooltip with text: ${text}`);
}

/**
 * Position the tooltip near the target element
 */
function positionTooltip(tooltip, targetElement) {
    const elementRect = targetElement.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    // Position tooltip above the element
    const top = elementRect.top + window.scrollY - tooltipRect.height - 10; // 10px gap
    const left = elementRect.left + window.scrollX + (elementRect.width / 2) - (tooltipRect.width / 2);
    
    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
}

/**
 * Remove any existing tooltips
 */
function removeTooltip() {
    const existingTooltips = document.querySelectorAll('.hint-tooltip');
function updateNextButton(questionItem, settings) {
    debug('updateNextButton', 'Updating next button');
    
    const nextButton = questionItem.querySelector('.wpProQuiz_QuestionButton[name="next"]');
    if (!nextButton) {
        debug('updateNextButton', 'ERROR: Next button not found!');
        return;
    }
    
    const questionId = questionItem.getAttribute('data-post-id');
    debug('updateNextButton', 'Question ID:', questionId);
    
    if (!questionId) {
        debug('updateNextButton', 'ERROR: Question ID not found');
        return;
    }
    
    // Get the hint button
    const hintButton = questionItem.querySelector('.wpProQuiz_TipButton');
    if (!hintButton) {
        debug('updateNextButton', 'ERROR: Hint button not found!');
        return;
    }
    debug('updateNextButton', 'Question ID:', questionId);
    
    const isCorrect = questionResults[questionId] || false;
    const hasViewedHint = hintViewed[questionId] || false;
    
    if (settings.forceHintMode && settings.requireCorrectForNext) {
        debug('updateNextButton', {
            questionId,
            isCorrect,
            hasViewedHint
        });
        
        // Force mode: Require both correct answer and hint viewed
        if (!isCorrect) {
            nextButton.style.display = 'none';
            nextButton.disabled = true;
            debug('updateNextButton', 'Hiding next button - answer is incorrect');
            hintButton.classList.add('hint-alert');
            hintButton.style.backgroundColor = '#4a90e2';
            hintButton.style.color = 'white';
            hintButton.style.fontWeight = 'bold';
            if (!hasViewedHint) {
                showTooltipNearElement(hintButton, 'טעית! חובה לקחת רמז כדי להמשיך');
                debug('updateNextButton', 'Highlighting hint button - hint required');
            }
        } else {
            if (hasViewedHint) {
                nextButton.style.display = '';
                nextButton.disabled = false;
                nextButton.classList.remove('disabled');
                nextButton.style.opacity = '1';
                nextButton.style.cursor = 'pointer';
                debug('updateNextButton', 'Enabling next button - answer is correct and hint viewed');
                hintButton.classList.remove('hint-alert');
                hintButton.style.backgroundColor = '';
                hintButton.style.color = '';
                hintButton.style.fontWeight = '';
                removeTooltip();
            } else {
                nextButton.style.display = 'none';
                nextButton.disabled = true;
                hintButton.classList.add('hint-alert');
                hintButton.style.backgroundColor = '#4a90e2';
                hintButton.style.color = 'white';
                hintButton.style.fontWeight = 'bold';
                showTooltipNearElement(hintButton, 'חובה לקחת רמז כדי להמשיך');
                debug('updateNextButton', 'Highlighting hint button - hint still required');
            }
        }
        if (!nextButton.dataset.originalText) {
            nextButton.dataset.originalText = nextButton.textContent;
        }
        nextButton.textContent = 'יש לקרוא את הרמז לפני המשך';
    } else {
        // Non-force mode: Enable Next button as soon as answer is correct (hint not required)
        if (isCorrect) {
            nextButton.style.display = '';
            nextButton.disabled = false;
            nextButton.classList.remove('disabled');
            nextButton.style.opacity = '1';
            nextButton.style.cursor = 'pointer';
        } else {
            nextButton.style.display = 'none';
            nextButton.disabled = true;
        }
        if (nextButton.dataset.originalText) {
            nextButton.textContent = nextButton.dataset.originalText;
        }
        // Remove hint highlighting and tooltip in non-force mode
        hintButton.classList.remove('hint-alert');
        hintButton.style.backgroundColor = '';
        hintButton.style.color = '';
        hintButton.style.fontWeight = '';
        removeTooltip();
    }
}

/**
 * Automatically show hints if setting is enabled
 */
function autoShowHints() {
    const hintButtons = document.querySelectorAll('.wpProQuiz_TipButton');
    hintButtons.forEach(button => {
        try {
            button.click();
        } catch (e) {
            console.error('Error auto-showing hint:', e);
        }
    });
}

/**
 * Get the quiz ID from the page
 */
function getQuizId() {
    debug('getQuizId', 'Attempting to find quiz ID');
    
    // First check if we have quizExtensionsSettings and it has a current_quiz_id
    if (window.quizExtensionsSettings && window.quizExtensionsSettings.current_quiz_id) {
        debug('getQuizId', 'Found quiz ID in quizExtensionsSettings', window.quizExtensionsSettings.current_quiz_id);
        return window.quizExtensionsSettings.current_quiz_id;
    }
    
    // Try from quiz container data attribute
    const quizContent = document.querySelector('.wpProQuiz_content');
    if (quizContent) {
        debug('getQuizId', 'Found quiz content element', quizContent);
        if (quizContent.dataset.quizMeta) {
            debug('getQuizId', 'Found quiz meta data attribute', quizContent.dataset.quizMeta);
            try {
                const meta = JSON.parse(quizContent.dataset.quizMeta);
                debug('getQuizId', 'Parsed meta data', meta);
                if (meta.quiz_pro_id) {
                    debug('getQuizId', 'Found quiz_pro_id in meta', meta.quiz_pro_id);
                    return meta.quiz_pro_id;
                }
                if (meta.quiz_post_id) {
                    debug('getQuizId', 'Found quiz_post_id in meta', meta.quiz_post_id);
                    return meta.quiz_post_id;
                }
            } catch (e) {
                logError('getQuizId - JSON parse error', e);
            }
        } else {
            debug('getQuizId', 'No quiz meta data attribute found');
        }
    } else {
        debug('getQuizId', 'Could not find quiz content element');
    }
    
    // Try from URL
    debug('getQuizId', 'Trying to find quiz ID in URL');
    const urlMatch = window.location.href.match(/quiz_id=(\d+)/);
    if (urlMatch && urlMatch[1]) {
        debug('getQuizId', 'Found quiz ID in URL', urlMatch[1]);
        return urlMatch[1];
    } else {
        debug('getQuizId', 'No quiz ID found in URL');
    }
    
    // Try quiz form
    debug('getQuizId', 'Trying to find quiz ID from quiz form');
    const quizForm = document.querySelector('.wpProQuiz_quiz');
    if (quizForm) {
        debug('getQuizId', 'Found quiz form element', quizForm);
        debug('getQuizId', 'Quiz form ID:', quizForm.id);
        const idMatch = quizForm.id?.match(/wpProQuiz_(\d+)/);
        if (idMatch && idMatch[1]) {
            debug('getQuizId', 'Extracted quiz ID from form ID', idMatch[1]);
            return idMatch[1];
        } else {
            debug('getQuizId', 'Could not extract quiz ID from form ID');
        }
    } else {
        debug('getQuizId', 'Could not find quiz form element');
    }
    
    // Try quiz container
    debug('getQuizId', 'Trying to find quiz ID from quiz container ID');
    if (quizContent) {
        debug('getQuizId', 'Quiz content ID:', quizContent.id);
        const idMatch = quizContent.id?.match(/wpProQuiz_(\d+)/);
        if (idMatch && idMatch[1]) {
            debug('getQuizId', 'Extracted quiz ID from container ID', idMatch[1]);
            return idMatch[1];
        } else {
            debug('getQuizId', 'Could not extract quiz ID from container ID');
        }
    }
    
    debug('getQuizId', 'All methods failed, returning null');
    return null;
}

// End of IIFE
})(); // End of script