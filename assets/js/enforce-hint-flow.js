/**
 * Enforce Hint Flow
 * 
 * This script enforces the core requirement that when a user selects a wrong answer:
 * 1. Inputs must stay active (re-enablement)
 * 2. "Next" button must be hidden (block progression)
 * 3. "Hint" button must be shown (enforced hint)
 */
(function() {
    'use strict';
    
    // ----- Core Configuration -----
    const config = {
        debug: true,
        selectors: {
            question: '.wpProQuiz_listItem',
            questionList: '.wpProQuiz_questionList',
            input: '.wpProQuiz_questionInput',
            nextButton: '.wpProQuiz_button[name="next"]',
            checkButton: '.wpProQuiz_button[name="check"]',
            hintButton: '.wpProQuiz_TipButton, .wpProQuiz_button[name="tip"]',
            correctMessage: '.wpProQuiz_correct',
            incorrectMessage: '.wpProQuiz_incorrect'
        }
    };
    
    // ----- State Management -----
    // Store correct answer data keyed by question ID
    const correctAnswers = {};
    
    // ----- Main Initialization -----
    function init() {
        // Check if we're on a quiz page
        if (!document.querySelector(config.selectors.question)) {
            return;
        }
        
        log('Initializing Enforce Hint Flow');
        
        // Extract correct answers before quiz starts
        preloadCorrectAnswers();
        
        // Set up question specific processing
        const questions = document.querySelectorAll(config.selectors.question);
        questions.forEach(setupQuestion);
        
        // Add global event listeners
        setupGlobalListeners();
        
        // Hide all next buttons initially
        document.querySelectorAll(config.selectors.nextButton).forEach(hideElement);
        
        // Add essential CSS
        addEssentialStyles();
        
        // Watch for dynamic content
        observeQuizChanges();
        
        log('Enforce Hint Flow initialized');
    }
    
    // ----- Core Functionality -----
    
    /**
     * Preload correct answers for all questions in the quiz
     */
    function preloadCorrectAnswers() {
        log('Preloading correct answers');
        
        // First try to get from LearnDash data
        try {
            if (typeof wpProQuizInitList !== 'undefined' && wpProQuizInitList) {
                for (const quizId in wpProQuizInitList) {
                    if (wpProQuizInitList[quizId] && wpProQuizInitList[quizId].json) {
                        const quizData = JSON.parse(wpProQuizInitList[quizId].json);
                        if (quizData && quizData.question_answers) {
                            for (const questionId in quizData.question_answers) {
                                correctAnswers[questionId] = quizData.question_answers[questionId];
                                log(`Found correct answer for question ${questionId}`);
                            }
                        }
                    }
                }
            }
        } catch (e) {
            log('Error reading LearnDash data: ' + e.message);
        }
        
        // If we still don't have answers, extract from question attributes
        if (Object.keys(correctAnswers).length === 0) {
            log('Extracting correct answers from DOM');
            document.querySelectorAll(config.selectors.questionList).forEach(questionList => {
                const questionId = questionList.getAttribute('data-question_id');
                if (!questionId) return;
                
                // We'll have to infer correct answers when the quiz engine processes them
                correctAnswers[questionId] = {
                    status: 'pending',
                    correctOption: null,
                };
            });
        }
    }
    
    /**
     * Set up event handling for a single question
     */
    function setupQuestion(questionElement) {
        const questionList = questionElement.querySelector(config.selectors.questionList);
        if (!questionList) return;
        
        const questionId = questionList.getAttribute('data-question_id');
        if (!questionId) return;
        
        log(`Setting up question ${questionId}`);
        
        // Hide next button initially
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (nextButton) {
            hideElement(nextButton);
        }
        
        // Watch for changes to the question's inputs
        const inputs = questionElement.querySelectorAll(config.selectors.input);
        inputs.forEach(input => {
            // Add change listener
            input.addEventListener('change', () => {
                log(`Input changed for question ${questionId}`);
                processUserSelection(questionElement, questionId);
            });
            
            // Watch for disabled attribute
            const observer = new MutationObserver(() => {
                if (input.disabled) {
                    enableInput(input);
                }
            });
            
            observer.observe(input, { attributes: true });
        });
        
        // Watch for results appearing
        observeQuestionResults(questionElement, questionId);
    }
    
    /**
     * Set up global event listeners
     */
    function setupGlobalListeners() {
        // Listen for check button clicks
        document.addEventListener('click', e => {
            if (!e.target.matches(config.selectors.checkButton)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            const questionList = questionElement.querySelector(config.selectors.questionList);
            if (!questionList) return;
            
            const questionId = questionList.getAttribute('data-question_id');
            if (!questionId) return;
            
            log(`Check button clicked for question ${questionId}`);
            
            // Check the result after a brief delay to let LearnDash process
            setTimeout(() => {
                processQuestionResult(questionElement, questionId);
            }, 100);
        });
        
        // Intercept next button clicks
        document.addEventListener('click', e => {
            if (!e.target.matches(config.selectors.nextButton)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            const questionList = questionElement.querySelector(config.selectors.questionList);
            if (!questionList) return;
            
            const questionId = questionList.getAttribute('data-question_id');
            if (!questionId) return;
            
            // Only allow next if answer is correct
            if (!isAnswerCorrect(questionElement, questionId)) {
                log(`Blocking next button for question ${questionId}`);
                e.preventDefault();
                e.stopPropagation();
                hideElement(e.target);
                enforceHintView(questionElement);
                return false;
            }
        });
    }
    
    /**
     * Process the user's selection for a question
     */
    function processUserSelection(questionElement, questionId) {
        // Always enable inputs
        enableQuestionInputs(questionElement);
        
        // Check current state
        if (isAnswerCorrect(questionElement, questionId)) {
            log(`Correct answer for question ${questionId}`);
            showNextButton(questionElement);
        } else {
            log(`Wrong answer for question ${questionId}`);
            hideNextButton(questionElement);
            enforceHintView(questionElement);
        }
    }
    
    /**
     * Process the result after check button is clicked
     */
    function processQuestionResult(questionElement, questionId) {
        // Check if correct message is visible
        const correctMsg = questionElement.querySelector(config.selectors.correctMessage);
        if (correctMsg && getComputedStyle(correctMsg).display !== 'none') {
            log(`Question ${questionId} answered correctly`);
            
            // Learn the correct answer if we didn't know it
            if (correctAnswers[questionId]?.status === 'pending') {
                const selectedInput = questionElement.querySelector(`${config.selectors.input}:checked`);
                if (selectedInput) {
                    correctAnswers[questionId] = {
                        status: 'known',
                        correctOption: selectedInput.value,
                    };
                    log(`Learned correct answer for question ${questionId}: ${selectedInput.value}`);
                }
            }
            
            // Allow progression
            showNextButton(questionElement);
            return;
        }
        
        // Check if incorrect message is visible
        const incorrectMsg = questionElement.querySelector(config.selectors.incorrectMessage);
        if (incorrectMsg && getComputedStyle(incorrectMsg).display !== 'none') {
            log(`Question ${questionId} answered incorrectly`);
            
            // Re-enable inputs
            enableQuestionInputs(questionElement);
            
            // Hide next button
            hideNextButton(questionElement);
            
            // Show hint
            enforceHintView(questionElement);
            
            // Make sure check button is visible for resubmission
            const checkButton = questionElement.querySelector(config.selectors.checkButton);
            if (checkButton) {
                showElement(checkButton);
            }
        }
    }
    
    /**
     * Observe for question results appearing
     */
    function observeQuestionResults(questionElement, questionId) {
        // Watch for correct/incorrect messages
        const observer = new MutationObserver(mutations => {
            let needsProcessing = false;
            
            mutations.forEach(mutation => {
                if (mutation.target.matches(config.selectors.correctMessage) || 
                    mutation.target.matches(config.selectors.incorrectMessage)) {
                    if (mutation.attributeName === 'style' && 
                        getComputedStyle(mutation.target).display !== 'none') {
                        needsProcessing = true;
                    }
                }
            });
            
            if (needsProcessing) {
                processQuestionResult(questionElement, questionId);
            }
        });
        
        observer.observe(questionElement, { 
            attributes: true, 
            attributeFilter: ['style'],
            subtree: true
        });
    }
    
    /**
     * Observe for dynamic content in the quiz
     */
    function observeQuizChanges() {
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        // If this is a question element
                        if (node.nodeType === 1 && node.matches && node.matches(config.selectors.question)) {
                            setupQuestion(node);
                        }
                        
                        // Look for questions inside added nodes
                        if (node.nodeType === 1 && node.querySelectorAll) {
                            node.querySelectorAll(config.selectors.question).forEach(setupQuestion);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // ----- Helper Functions -----
    
    /**
     * Check if the answer for a question is correct
     */
    function isAnswerCorrect(questionElement, questionId) {
        // First check if the correct message is visible
        const correctMsg = questionElement.querySelector(config.selectors.correctMessage);
        if (correctMsg && getComputedStyle(correctMsg).display !== 'none') {
            return true;
        }
        
        // If we don't have correct answer data yet
        if (!correctAnswers[questionId] || correctAnswers[questionId].status === 'pending') {
            // Default to false if we don't know the correct answer yet
            return false;
        }
        
        // Check current selection against known correct answer
        const selectedInput = questionElement.querySelector(`${config.selectors.input}:checked`);
        if (!selectedInput) return false;
        
        return selectedInput.value === correctAnswers[questionId].correctOption;
    }
    
    /**
     * Enable all inputs for a question
     */
    function enableQuestionInputs(questionElement) {
        const inputs = questionElement.querySelectorAll(config.selectors.input);
        inputs.forEach(enableInput);
    }
    
    /**
     * Enable a single input
     */
    function enableInput(input) {
        input.disabled = false;
        input.removeAttribute('disabled');
        
        // Make parent label clickable
        const label = input.closest('label');
        if (label) {
            label.style.pointerEvents = 'auto';
            label.style.cursor = 'pointer';
        }
        
        log('Input enabled');
    }
    
    /**
     * Hide the next button
     */
    function hideNextButton(questionElement) {
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (nextButton) {
            hideElement(nextButton);
        }
    }
    
    /**
     * Show the next button
     */
    function showNextButton(questionElement) {
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (nextButton) {
            showElement(nextButton);
        }
    }
    
    /**
     * Force hint view after incorrect answer
     */
    function enforceHintView(questionElement) {
        const hintButton = questionElement.querySelector(config.selectors.hintButton);
        if (!hintButton) {
            log('Hint button not found');
            return;
        }
        
        // Make sure hint button is visible
        showElement(hintButton);
        
        // Apply highlight styling
        hintButton.classList.add('lilac-highlight');
    }
    
    /**
     * Hide an element
     */
    function hideElement(element) {
        element.style.display = 'none';
        element.style.visibility = 'hidden';
    }
    
    /**
     * Show an element
     */
    function showElement(element) {
        // Reset to default display type (inline-block for buttons)
        element.style.display = element.tagName === 'BUTTON' || 
                              element.classList.contains('wpProQuiz_button') ? 
                              'inline-block' : '';
        element.style.visibility = 'visible';
    }
    
    /**
     * Add essential CSS styles
     */
    function addEssentialStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Core styles for enforcing hint flow */
            .wpProQuiz_questionInput:disabled {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
            }
            
            /* Highlight for hint button */
            .lilac-highlight {
                background-color: #ffc107 !important;
                color: #333 !important;
                border: 2px solid #e0a800 !important;
                font-weight: bold !important;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.5) !important;
                animation: pulse-hint 1.5s infinite !important;
            }
            
            @keyframes pulse-hint {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Log with prefix (only if debug is enabled)
     */
    function log(message) {
        if (config.debug) {
            console.log(`[EnforceHint] ${message}`);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run after a short delay to catch dynamic content
    setTimeout(init, 500);
})();
