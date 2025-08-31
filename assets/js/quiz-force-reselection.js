/**
 * Quiz Force Reselection
 * 
 * A direct, focused solution that forces LearnDash quizzes to:
 * 1. Keep inputs enabled after a wrong answer
 * 2. Hide Next button until correct answer is selected
 * 3. Integrate with hint system
 */
(function() {
    'use strict';
    
    // Configuration
    const config = {
        selectors: {
            question: '.wpProQuiz_listItem',
            input: '.wpProQuiz_questionInput',
            label: '.wpProQuiz_questionListItem label',
            checkButton: '.wpProQuiz_button[name="check"]',
            nextButton: '.wpProQuiz_button[name="next"]',
            // Add all possible hint button selectors
            hintButton: '.wpProQuiz_TipButton, .wpProQuiz_button[name="tip"]',
            incorrectMessage: '.wpProQuiz_incorrect',
            correctMessage: '.wpProQuiz_correct',
            responseContainer: '.wpProQuiz_response',
            hintContent: '.wpProQuiz_tipp'
        },
        classes: {
            highlight: 'lilac-highlight',
            hintRequired: 'lilac-hint-required'
        },
        debug: true  // Enable to see console logs
    };
    
    // State management
    const state = {
        questions: {},  // Tracks state for each question
        observers: [],  // Holds all MutationObservers
        initialized: false // Track if we've initialized
    };
    
    /**
     * Initialize the force reselection functionality
     */
    function init() {
        log('Quiz Force Reselection: Initializing');
        
        // Watch for initial DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onDOMReady);
        } else {
            onDOMReady();
        }
        
        // Run multiple times with increasing delays to ensure we catch all dynamic content
        setTimeout(onDOMReady, 100);
        setTimeout(onDOMReady, 500);
        setTimeout(onDOMReady, 1000);
        setTimeout(onDOMReady, 2000);
    }
    
    /**
     * Handle when DOM is ready
     */
    function onDOMReady() {
        // Don't run multiple times
        if (state.initialized) {
            return;
        }
        
        log('Quiz Force Reselection: DOM Ready');
        
        // Add global CSS overrides first thing
        addGlobalStyles();
        
        // Check if we have any questions yet
        const questions = document.querySelectorAll(config.selectors.question);
        if (questions.length === 0) {
            log('No questions found yet, will retry');
            return;
        }
        
        // Process each question on the page
        questions.forEach(processQuestion);
        
        // Fix visible state immediately for existing questions
        fixCurrentQuizState();
        
        // Set up global event listeners
        setupEventListeners();
        
        // Observe DOM changes for dynamically added content
        observeDOMChanges();
        
        // Mark as initialized
        state.initialized = true;
        
        log('Quiz Force Reselection: Setup complete');
    }
    
    /**
     * Immediately fix the current state of the quiz
     */
    function fixCurrentQuizState() {
        log('Fixing current quiz state');
        
        // Find all questions with visible incorrect messages
        document.querySelectorAll(config.selectors.incorrectMessage).forEach(function(incorrectMsg) {
            if (getComputedStyle(incorrectMsg).display !== 'none') {
                const questionElement = incorrectMsg.closest(config.selectors.question);
                if (questionElement) {
                    const questionId = getQuestionId(questionElement);
                    log(`Found question with incorrect answer: ${questionId}`);
                    
                    // Force hide next button
                    hideNextButton(questionElement);
                    
                    // Force enable inputs
                    forceEnableInputs(questionElement);
                    
                    // Make sure hint button is visible
                    const hintButton = questionElement.querySelector(config.selectors.hintButton);
                    if (hintButton) {
                        log('Showing hint button');
                        hintButton.style.display = 'inline-block';
                        highlightHintButton(questionElement);
                    } else {
                        log('Warning: No hint button found!');
                    }
                    
                    // Update state
                    state.questions[questionId] = {
                        answered: true,
                        correct: false,
                        hintViewed: false
                    };
                }
            }
        });
    }
    
    /**
     * Process a question element
     * @param {HTMLElement} questionElement - The question container element
     */
    function processQuestion(questionElement) {
        // Generate a unique ID for this question if needed
        const questionId = getQuestionId(questionElement);
        log(`Processing question: ${questionId}`);
        
        // Initialize state for this question
        if (!state.questions[questionId]) {
            state.questions[questionId] = {
                answered: false,
                correct: false,
                hintViewed: false
            };
        }
        
        // Force enable all inputs
        forceEnableInputs(questionElement);
        
        // Set up a dedicated MutationObserver for this question's inputs
        watchInputsForDisabled(questionElement);
        
        // Check current state (in case we're processing after initial load)
        checkCurrentState(questionElement, questionId);
    }
    
    /**
     * Set up global event listeners
     */
    function setupEventListeners() {
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if (!e.target.matches(config.selectors.checkButton)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            // Wait for LearnDash to process the answer
            setTimeout(function() {
                const questionId = getQuestionId(questionElement);
                checkResponseState(questionElement, questionId);
            }, 300);
        });
        
        // Listen for input changes after a wrong answer
        document.addEventListener('change', function(e) {
            if (!e.target.matches(config.selectors.input)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            // Get question ID
            const questionId = getQuestionId(questionElement);
            
            // If they've already answered incorrectly
            if (state.questions[questionId] && !state.questions[questionId].correct) {
                // Hide response messages but keep hint visible
                hideResponseMessages(questionElement);
                
                // Make check button visible
                showCheckButton(questionElement);
            }
        });
        
        // Listen for hint button clicks
        document.addEventListener('click', function(e) {
            if (!e.target.matches(config.selectors.hintButton)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            const questionId = getQuestionId(questionElement);
            state.questions[questionId].hintViewed = true;
            
            log(`Hint viewed for question: ${questionId}`);
        });
        
        // Prevent next button from working after incorrect answer
        document.addEventListener('click', function(e) {
            if (!e.target.matches(config.selectors.nextButton)) return;
            
            const questionElement = e.target.closest(config.selectors.question);
            if (!questionElement) return;
            
            const questionId = getQuestionId(questionElement);
            
            // If this question has been answered incorrectly and next button shouldn't be active
            if (state.questions[questionId] && 
                !state.questions[questionId].correct && 
                questionElement.querySelector(config.selectors.incorrectMessage) &&
                getComputedStyle(questionElement.querySelector(config.selectors.incorrectMessage)).display !== 'none') {
                
                e.preventDefault();
                e.stopPropagation();
                
                // Force hide next button
                hideNextButton(questionElement);
                
                // Highlight hint button
                highlightHintButton(questionElement);
                
                log(`Prevented next button click for question: ${questionId}`);
                return false;
            }
        });
    }
    
    /**
     * Check the current state of the question
     * @param {HTMLElement} questionElement - The question container element
     * @param {string} questionId - The question ID
     */
    function checkCurrentState(questionElement, questionId) {
        // Check if incorrect message is visible
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        if (incorrectMessage && getComputedStyle(incorrectMessage).display !== 'none') {
            state.questions[questionId].answered = true;
            state.questions[questionId].correct = false;
            
            // Handle incorrect answer
            handleIncorrectAnswer(questionElement, questionId);
        }
        
        // Check if correct message is visible
        const correctMessage = questionElement.querySelector(config.selectors.correctMessage);
        if (correctMessage && getComputedStyle(correctMessage).display !== 'none') {
            state.questions[questionId].answered = true;
            state.questions[questionId].correct = true;
            
            // Make sure next button is visible
            showNextButton(questionElement);
        }
    }
    
    /**
     * Check the response state after the check button is clicked
     * @param {HTMLElement} questionElement - The question container element
     * @param {string} questionId - The question ID
     */
    function checkResponseState(questionElement, questionId) {
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        const correctMessage = questionElement.querySelector(config.selectors.correctMessage);
        
        // If incorrect
        if (incorrectMessage && getComputedStyle(incorrectMessage).display !== 'none') {
            state.questions[questionId].answered = true;
            state.questions[questionId].correct = false;
            
            handleIncorrectAnswer(questionElement, questionId);
        }
        // If correct
        else if (correctMessage && getComputedStyle(correctMessage).display !== 'none') {
            state.questions[questionId].answered = true;
            state.questions[questionId].correct = true;
            
            showNextButton(questionElement);
        }
    }
    
    /**
     * Handle incorrect answer
     * @param {HTMLElement} questionElement - The question container element
     * @param {string} questionId - The question ID
     */
    function handleIncorrectAnswer(questionElement, questionId) {
        log(`Handling incorrect answer for question: ${questionId}`);
        
        // Hide next button
        hideNextButton(questionElement);
        
        // Force enable inputs for reselection
        forceEnableInputs(questionElement);
        
        // Show check button
        showCheckButton(questionElement);
        
        // Highlight hint button
        highlightHintButton(questionElement);
        
        // Use a timeout to make sure inputs stay enabled
        setTimeout(function() {
            forceEnableInputs(questionElement);
        }, 500);
    }
    
    /**
     * Force enable all inputs in a question
     * @param {HTMLElement} questionElement - The question container element
     */
    function forceEnableInputs(questionElement) {
        // Find all inputs in this question
        const inputs = questionElement.querySelectorAll(config.selectors.input);
        
        log(`Forcing ${inputs.length} inputs to be enabled`);
        
        inputs.forEach(function(input) {
            // Remove disabled attribute
            input.disabled = false;
            input.removeAttribute('disabled');
            
            // Force clickability
            input.style.pointerEvents = 'auto';
            input.style.cursor = 'pointer';
            
            // Track initial disabled state
            if (input.getAttribute('data-was-checked') !== 'true') {
                input.setAttribute('data-original-checked', input.checked);
                input.setAttribute('data-was-checked', 'true');
            }
        });
        
        // Make labels clickable
        const labels = questionElement.querySelectorAll(config.selectors.label);
        labels.forEach(function(label) {
            label.style.pointerEvents = 'auto';
            label.style.cursor = 'pointer';
        });
    }
    
    /**
     * Watch inputs for disabled attribute being added back
     * @param {HTMLElement} questionElement - The question container element
     */
    function watchInputsForDisabled(questionElement) {
        if (!window.MutationObserver) return;
        
        const inputs = questionElement.querySelectorAll(config.selectors.input);
        inputs.forEach(function(input) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'disabled' && input.disabled) {
                        log('LearnDash tried to disable an input - re-enabling!');
                        input.disabled = false;
                        input.removeAttribute('disabled');
                    }
                });
            });
            
            observer.observe(input, { attributes: true });
            state.observers.push(observer);
        });
    }
    
    /**
     * Hide the next button
     * @param {HTMLElement} questionElement - The question container element
     */
    function hideNextButton(questionElement) {
        log('Aggressively hiding next button');
        
        // Try multiple selector strategies to find the next button
        const nextButtons = [];
        
        // Direct child of question
        const directNextButton = questionElement.querySelector(config.selectors.nextButton);
        if (directNextButton) nextButtons.push(directNextButton);
        
        // Find all next buttons within this question
        questionElement.querySelectorAll('input[type="button"][name="next"], button[name="next"], .wpProQuiz_button[name="next"]').forEach(btn => {
            nextButtons.push(btn);
        });
        
        // Look for buttons that contain text like "Next" or "הבא" (Hebrew for next)
        questionElement.querySelectorAll('input[type="button"], button, .wpProQuiz_button').forEach(btn => {
            const btnText = btn.value || btn.textContent || '';
            if (btnText.match(/next|הבא|continue|המשך|siguiente/i)) {
                nextButtons.push(btn);
            }
        });
        
        // Apply aggressive hiding to all found buttons
        nextButtons.forEach(btn => {
            // Hide with inline styles (multiple approaches for redundancy)
            btn.style.display = 'none';
            btn.style.visibility = 'hidden';
            btn.style.opacity = '0';
            btn.style.pointerEvents = 'none';
            
            // Force with !important attribute
            btn.setAttribute('style', 'display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important;');
            
            // Add a class we can target with CSS
            btn.classList.add('lilac-force-hidden');
            
            // Prevent click events
            btn.setAttribute('disabled', 'disabled');
            btn.setAttribute('aria-hidden', 'true');
            
            log('Next button hidden with multiple methods');
        });
        
        // If we didn't find any buttons, log a warning
        if (nextButtons.length === 0) {
            log('Warning: No next buttons found to hide!');
        } else {
            log(`Hidden ${nextButtons.length} next buttons`);
        }
    }
    
    /**
     * Show the next button
     * @param {HTMLElement} questionElement - The question container element
     */
    function showNextButton(questionElement) {
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (!nextButton) return;
        
        nextButton.style.display = 'inline-block';
        nextButton.style.visibility = 'visible';
        nextButton.removeAttribute('style');
        
        log('Next button shown');
    }
    
    /**
     * Show the check button
     * @param {HTMLElement} questionElement - The question container element
     */
    function showCheckButton(questionElement) {
        const checkButton = questionElement.querySelector(config.selectors.checkButton);
        if (!checkButton) return;
        
        checkButton.style.display = 'inline-block';
        checkButton.style.visibility = 'visible';
        
        log('Check button shown');
    }
    
    /**
     * Hide response messages but keep hint visible
     * @param {HTMLElement} questionElement - The question container element
     */
    function hideResponseMessages(questionElement) {
        const responseContainer = questionElement.querySelector(config.selectors.responseContainer);
        if (!responseContainer) return;
        
        // Hide incorrect/correct messages
        const incorrectMessage = responseContainer.querySelector(config.selectors.incorrectMessage);
        const correctMessage = responseContainer.querySelector(config.selectors.correctMessage);
        
        if (incorrectMessage) {
            incorrectMessage.style.display = 'none';
        }
        
        if (correctMessage) {
            correctMessage.style.display = 'none';
        }
    }
    
    /**
     * Highlight the hint button
     * @param {HTMLElement} questionElement - The question container element
     */
    function highlightHintButton(questionElement) {
        const hintButton = questionElement.querySelector(config.selectors.hintButton);
        if (!hintButton) return;
        
        // Add highlight class
        hintButton.classList.add(config.classes.highlight);
        
        // Style directly
        hintButton.style.backgroundColor = '#ffc107';
        hintButton.style.color = '#333';
        hintButton.style.fontWeight = 'bold';
        hintButton.style.boxShadow = '0 0 10px rgba(255, 193, 7, 0.7)';
        hintButton.style.animation = 'lilac-pulse-button 1.5s infinite';
        
        // Make hint content visible if user has previously clicked hint button
        const questionId = getQuestionId(questionElement);
        if (state.questions[questionId].hintViewed) {
            const hintContent = questionElement.querySelector(config.selectors.hintContent);
            if (hintContent) {
                hintContent.style.display = 'block';
            }
        }
        
        // Add tooltip if not already present
        if (!questionElement.querySelector('.lilac-hint-tooltip')) {
            const tooltip = document.createElement('div');
            tooltip.className = 'lilac-hint-tooltip';
            tooltip.textContent = 'טעית! להמשך חובה לקחת רמז!';
            tooltip.style.position = 'absolute';
            tooltip.style.backgroundColor = '#ffc107';
            tooltip.style.color = '#333';
            tooltip.style.padding = '8px 12px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontWeight = 'bold';
            tooltip.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            tooltip.style.zIndex = '100';
            tooltip.style.maxWidth = '250px';
            tooltip.style.textAlign = 'center';
            tooltip.style.fontSize = '14px';
            tooltip.style.whiteSpace = 'nowrap';
            tooltip.style.marginTop = '5px';
            
            // Add tooltip to question
            hintButton.parentNode.appendChild(tooltip);
            
            // Position tooltip
            const buttonRect = hintButton.getBoundingClientRect();
            tooltip.style.left = `${hintButton.offsetLeft + (hintButton.offsetWidth / 2)}px`;
            
            // Add arrow
            const arrow = document.createElement('div');
            arrow.className = 'lilac-tooltip-arrow';
            arrow.style.position = 'absolute';
            arrow.style.top = '-8px';
            arrow.style.left = '50%';
            arrow.style.marginLeft = '-8px';
            arrow.style.width = '0';
            arrow.style.height = '0';
            arrow.style.borderBottom = '8px solid #ffc107';
            arrow.style.borderRight = '8px solid transparent';
            arrow.style.borderLeft = '8px solid transparent';
            
            tooltip.appendChild(arrow);
        }
        
        // Auto-scroll to hint button
        hintButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Get a unique ID for a question
     * @param {HTMLElement} questionElement - The question container element
     * @returns {string} The question ID
     */
    function getQuestionId(questionElement) {
        // First try to get from data attribute
        let id = questionElement.getAttribute('data-question-id');
        
        if (!id) {
            // Then try to get from question list
            const questionList = questionElement.querySelector('.wpProQuiz_questionList');
            if (questionList) {
                id = questionList.getAttribute('data-question_id') || 
                     questionList.getAttribute('data-question-id');
            }
            
            // If still no ID, generate one based on position
            if (!id) {
                const allQuestions = Array.from(document.querySelectorAll(config.selectors.question));
                id = `question_${allQuestions.indexOf(questionElement)}`;
            }
            
            // Save ID as data attribute for future reference
            questionElement.setAttribute('data-question-id', id);
        }
        
        return id;
    }
    
    /**
     * Observe DOM changes
     */
    function observeDOMChanges() {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes) {
                    mutation.addedNodes.forEach(function(node) {
                        // If a question was added
                        if (node.nodeType === 1 && node.matches && node.matches(config.selectors.question)) {
                            processQuestion(node);
                        }
                        
                        // Check for questions inside added node
                        if (node.nodeType === 1 && node.querySelectorAll) {
                            node.querySelectorAll(config.selectors.question).forEach(processQuestion);
                        }
                    });
                }
            });
        });
        
        // Start observing the document body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        state.observers.push(observer);
    }
    
    /**
     * Add global styles
     */
    function addGlobalStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* AGGRESSIVE OVERRIDES - DO NOT MODIFY WITHOUT TESTING */
            
            /* Force inputs to be enabled and clickable */
            .wpProQuiz_questionInput {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
                user-select: auto !important;
            }
            
            /* Force labels to be clickable */
            .wpProQuiz_questionListItem label {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
                user-select: auto !important;
            }
            
            /* Override disabled attribute on ALL inputs */
            [disabled], .wpProQuiz_questionInput[disabled] {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
                user-select: auto !important;
            }
            
            /* Always show hint button */
            .wpProQuiz_button[name="tip"], .wpProQuiz_TipButton {
                display: inline-block !important;
                visibility: visible !important;
            }
            
            /* DIRECT RULE: Hide next button when incorrect answer is shown */
            .wpProQuiz_incorrect ~ .wpProQuiz_button[name="next"],
            .wpProQuiz_incorrect + div > .wpProQuiz_button[name="next"],
            .wpProQuiz_incorrect + p > .wpProQuiz_button[name="next"],
            .wpProQuiz_listItem:has(.wpProQuiz_incorrect) .wpProQuiz_button[name="next"] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            
            /* Animation for hint button */
            @keyframes lilac-pulse-button {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            /* Highlighted hint button */
            .lilac-highlight {
                background-color: #ffc107 !important;
                color: #333 !important;
                font-weight: bold !important;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.7) !important;
                animation: lilac-pulse-button 1.5s infinite !important;
            }
            
            /* Make the hint button stand out */
            .wpProQuiz_button[name="tip"], .wpProQuiz_TipButton {
                background-color: #ffc107 !important;
                color: #333 !important;
                border: 2px solid #e0a800 !important;
                font-weight: bold !important;
                position: relative !important;
                z-index: 999 !important;
            }
            
            /* Hint tooltip styling */
            .lilac-hint-tooltip {
                position: absolute;
                background-color: #ffc107;
                color: #333;
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: bold;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 100;
                max-width: 250px;
                text-align: center;
                font-size: 14px;
                margin-top: 5px;
            }
        `;
        
        document.head.appendChild(style);
        log('Added aggressive CSS overrides');
    }
    
    /**
     * Console log with prefix (only if debug is enabled)
     * @param {string} message - The message to log
     */
    function log(message) {
        if (config.debug) {
            console.log(`[QuizForceReselection] ${message}`);
        }
    }
    
    // Initialize
    init();
})();
