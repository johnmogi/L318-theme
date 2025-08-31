/**
 * HintSystem.js - Module for handling hint display and interaction
 * 
 * This module manages hint visibility, styling, and interactions,
 * working alongside the AnswerReselection module.
 */

// Register the Hint System module with the core
LilacQuiz.registerModule('hintSystem', (function() {
    'use strict';
    
    // Module configuration
    const config = {
        selectors: {
            listItem: '.wpProQuiz_listItem',
            questionList: '.wpProQuiz_questionList',
            hintButton: '.wpProQuiz_TipButton, .wpProQuiz_hint',
            hintContainer: '.wpProQuiz_tipp',
            incorrectMessage: '.wpProQuiz_incorrect',
            questionInput: '.wpProQuiz_questionInput',
            hintContent: '.lilac-hint-content',
            customHintButton: '.lilac-hint-button'
        },
        classes: {
            customHintButton: 'lilac-hint-button',
            customHintContent: 'lilac-hint-content',
            highlighted: 'lilac-hint-highlighted',
            prompt: 'lilac-hint-prompt'
        },
        attributes: {
            questionId: 'data-question-id',
            hintShown: 'data-hint-shown'
        },
        hintPromptText: 'קח רמז כדי להמשיך',
        eventNamespace: '.lilacQuizHintSystem'
    };
    
    // Track hint state for each question
    let hintStates = {};
    
    /**
     * Initialize the module
     * @param {Object} settings - Settings from the core
     */
    function init(settings) {
        LilacQuiz.log('Hint System Module initializing');
        
        // Process existing questions
        processExistingQuestions();
        
        // Set up event handlers
        setupEventHandlers();
        
        // Set up observer for dynamic content
        setupObserver();
        
        // Add CSS for hint styling
        addStyles();
    }
    
    /**
     * Process existing questions on the page
     */
    function processExistingQuestions() {
        document.querySelectorAll(config.selectors.listItem).forEach(function(questionElement) {
            setupQuestionHints(questionElement);
        });
    }
    
    /**
     * Set up hints for a specific question
     * @param {HTMLElement} questionElement - The question container
     */
    function setupQuestionHints(questionElement) {
        // Get or create question ID
        const questionId = getQuestionId(questionElement);
        
        // Initialize hint state if needed
        if (!hintStates[questionId]) {
            hintStates[questionId] = {
                hasHint: false,
                hintShown: false,
                customButtonCreated: false
            };
        }
        
        // Look for original hint button
        const originalHint = questionElement.querySelector(config.selectors.hintButton);
        const hintContainer = questionElement.querySelector(config.selectors.hintContainer);
        
        if (originalHint || hintContainer) {
            hintStates[questionId].hasHint = true;
            
            // Create custom hint button if not already present
            if (!hintStates[questionId].customButtonCreated) {
                createCustomHintButton(questionElement, questionId);
            }
            
            // Prepare hint content display
            prepareHintContent(questionElement, questionId, hintContainer);
        }
    }
    
    /**
     * Create a custom hint button for better control and styling
     * @param {HTMLElement} questionElement - The question container
     * @param {string} questionId - Unique ID for the question
     */
    function createCustomHintButton(questionElement, questionId) {
        // Check if button container exists
        const buttonContainer = findButtonContainer(questionElement);
        if (!buttonContainer) return;
        
        // Check if we already created a custom button
        if (questionElement.querySelector(config.selectors.customHintButton)) {
            hintStates[questionId].customButtonCreated = true;
            return;
        }
        
        // Hide original button if it exists
        const originalButton = questionElement.querySelector(config.selectors.hintButton);
        if (originalButton) {
            originalButton.style.display = 'none';
            originalButton.setAttribute('style', 'display: none !important; visibility: hidden !important;');
        }
        
        // Create custom button
        const customButton = document.createElement('button');
        customButton.type = 'button';
        customButton.className = `${config.classes.customHintButton} wpProQuiz_button`;
        customButton.textContent = 'רמז';
        customButton.style.float = 'left';
        customButton.style.marginRight = '10px';
        customButton.style.display = 'inline-block';
        
        // Insert at the beginning of the button container
        buttonContainer.insertBefore(customButton, buttonContainer.firstChild);
        
        // Add click handler
        customButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleHintVisibility(questionElement, questionId);
            return false;
        });
        
        hintStates[questionId].customButtonCreated = true;
    }
    
    /**
     * Find button container in question
     * @param {HTMLElement} questionElement - The question container
     * @returns {HTMLElement|null} - The button container or null
     */
    function findButtonContainer(questionElement) {
        // First try to find by buttons
        const buttonElements = questionElement.querySelectorAll('.wpProQuiz_button');
        
        if (buttonElements.length) {
            // Find closest common ancestor
            return buttonElements[0].closest('p') || buttonElements[0].parentNode;
        }
        
        return null;
    }
    
    /**
     * Prepare hint content for display
     * @param {HTMLElement} questionElement - The question container
     * @param {string} questionId - Unique ID for the question
     * @param {HTMLElement} hintContainer - Original hint container
     */
    function prepareHintContent(questionElement, questionId, hintContainer) {
        // Check if custom hint content already exists
        let customHintContent = questionElement.querySelector(config.selectors.hintContent);
        
        if (!customHintContent) {
            // Create custom hint content container
            customHintContent = document.createElement('div');
            customHintContent.className = config.classes.customHintContent;
            
            // Extract hint content from original container if available
            let hintText = '';
            if (hintContainer) {
                // Try to get the inner HTML
                const hintInner = hintContainer.querySelector('div');
                hintText = hintInner ? hintInner.innerHTML : hintContainer.innerHTML;
            } else {
                // Default hint text
                hintText = '<h5 style="margin: 0 0 10px;">רמז</h5><p>נסה לחשוב על התשובה הנכונה. אם אתה מתקשה, פנה למורה לעזרה.</p>';
            }
            
            // Set content
            customHintContent.innerHTML = hintText;
            
            // Style the content
            customHintContent.style.backgroundColor = '#fffde7';
            customHintContent.style.border = '1px solid #ffd54f';
            customHintContent.style.borderRadius = '4px';
            customHintContent.style.padding = '15px';
            customHintContent.style.margin = '10px 0';
            customHintContent.style.position = 'relative';
            customHintContent.style.display = 'none'; // Initially hidden
            
            // Add after question list
            const questionList = questionElement.querySelector(config.selectors.questionList);
            if (questionList) {
                questionList.after(customHintContent);
            } else {
                questionElement.appendChild(customHintContent);
            }
        }
    }
    
    /**
     * Toggle hint visibility
     * @param {HTMLElement} questionElement - The question container
     * @param {string} questionId - Unique ID for the question
     */
    function toggleHintVisibility(questionElement, questionId) {
        console.log('[DEBUG HINT] Toggling hint visibility for question', questionId);
        
        const hintContent = questionElement.querySelector(config.selectors.hintContent);
        if (!hintContent) {
            console.log('[DEBUG HINT] No hint content found for question', questionId);
            return;
        }
        
        // Toggle visibility
        if (getComputedStyle(hintContent).display === 'none') {
            console.log('[DEBUG HINT] Showing hint content');
            hintContent.style.display = 'block';
            hintStates[questionId].hintShown = true;
            questionElement.setAttribute(config.attributes.hintShown, 'true');
            
            // Remove any prompts when hint is shown
            removeHintPrompts(questionElement);
            
            // Make sure inputs are still enabled after showing hint
            console.log('[DEBUG HINT] Re-enabling inputs after hint shown');
            enableInputsForQuestion(questionElement);
            
            // Custom event to inform other modules
            const event = new CustomEvent('lilacHintShown', { 
                detail: { questionId: questionId, element: questionElement }
            });
            document.dispatchEvent(event);
        } else {
            console.log('[DEBUG HINT] Hiding hint content');
            hintContent.style.display = 'none';
        }
    }
    
    /**
     * Set up event handlers
     */
    function setupEventHandlers() {
        // Handle original hint buttons (in case they're clicked)
        document.addEventListener('click', function(e) {
            // Check if this is an original hint button
            if (e.target.matches(config.selectors.hintButton) && 
                !e.target.matches(config.selectors.customHintButton)) {
                
                const questionElement = e.target.closest(config.selectors.listItem);
                if (!questionElement) return;
                
                const questionId = getQuestionId(questionElement);
                
                // Update state to reflect hint was shown
                if (hintStates[questionId]) {
                    hintStates[questionId].hintShown = true;
                }
                
                questionElement.setAttribute(config.attributes.hintShown, 'true');
                
                // Remove any prompts
                removeHintPrompts(questionElement);
                
                // Custom event
                const event = new CustomEvent('lilacHintShown', { 
                    detail: { questionId: questionId, element: questionElement }
                });
                document.dispatchEvent(event);
            }
        });
        
        // Listen for incorrect answers to highlight hint
        document.addEventListener('DOMNodeInserted', function(e) {
            if (e.target.matches && e.target.matches(config.selectors.incorrectMessage)) {
                const questionElement = e.target.closest(config.selectors.listItem);
                if (!questionElement) return;
                
                highlightHintButton(questionElement);
            }
        });
    }
    
    /**
     * Highlight the hint button when an incorrect answer is given
     * @param {HTMLElement} questionElement - The question container
     */
    function highlightHintButton(questionElement) {
        const hintButton = questionElement.querySelector(config.selectors.customHintButton) || 
                          questionElement.querySelector(config.selectors.hintButton);
        
        if (!hintButton) return;
        
        // Add highlighting
        hintButton.classList.add(config.classes.highlighted);
        
        // Add prompt if not already present
        if (!questionElement.querySelector(`.${config.classes.prompt}`)) {
            const prompt = document.createElement('span');
            prompt.className = config.classes.prompt;
            prompt.textContent = config.hintPromptText;
            prompt.style.marginLeft = '10px';
            prompt.style.color = '#d32f2f';
            prompt.style.fontWeight = 'bold';
            
            // Insert near hint button
            hintButton.after(prompt);
        }
    }
    
    /**
     * Remove hint prompts
     * @param {HTMLElement} questionElement - The question container
     */
    function removeHintPrompts(questionElement) {
        const prompts = questionElement.querySelectorAll(`.${config.classes.prompt}`);
        prompts.forEach(function(prompt) {
            prompt.remove();
        });
    }
    
    /**
     * Get a unique ID for a question
     * @param {HTMLElement} questionElement - The question container element
     * @returns {string} - A unique ID
     */
    function getQuestionId(questionElement) {
        // Check if question already has an ID attribute
        let id = questionElement.getAttribute(config.attributes.questionId);
        
        if (!id) {
            // Try to get ID from the DOM structure
            const questionList = questionElement.querySelector(config.selectors.questionList);
            if (questionList) {
                id = questionList.getAttribute('data-question_id') || 
                     questionList.getAttribute('data-question-id');
            }
            
            // If still no ID, generate one
            if (!id) {
                // Find position in parent
                const parent = questionElement.parentNode;
                const siblings = Array.from(parent.children);
                id = `q_${siblings.indexOf(questionElement)}`;
            }
        }
        
        return id;
    }
    
    /**
     * Set up MutationObserver to watch for DOM changes
     */
    function setupObserver() {
        if (!window.MutationObserver) return;
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check for added nodes
                if (mutation.addedNodes && mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType !== Node.ELEMENT_NODE) return;
                        
                        // If this is a question
                        if (node.matches(config.selectors.listItem)) {
                            setupQuestionHints(node);
                        }
                        
                        // Check for questions inside the added node
                        const questions = node.querySelectorAll(config.selectors.listItem);
                        questions.forEach(setupQuestionHints);
                    });
                }
            });
        });
        
        // Observe the entire body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Add global styles for hint system
     */
    function addStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            .lilac-hint-button {
                float: left !important;
                display: inline-block !important;
                margin-right: 10px !important;
            }
            
            .lilac-hint-button.lilac-hint-highlighted {
                background-color: #ffc107 !important;
                color: #333 !important;
                font-weight: bold !important;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.7) !important;
                animation: lilac-pulse-button 1.5s infinite !important;
            }
            
            .lilac-hint-content {
                background-color: #fffde7;
                border: 1px solid #ffd54f;
                border-radius: 4px;
                padding: 15px;
                margin: 10px 0;
                position: relative;
            }
            
            .lilac-hint-prompt {
                color: #d32f2f;
                font-weight: bold;
                margin-left: 10px;
            }
            
            /* Critical fix to force inputs to be active */
            .wpProQuiz_questionListItem input {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
            }
            
            .wpProQuiz_questionListItem label {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
            }
            
            /* Fix for when LearnDash tries to disable inputs */
            .wpProQuiz_questionInput[disabled] {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
            }
            
            /* Hide next button when incorrect answer */
            .wpProQuiz_incorrect ~ .wpProQuiz_button[name="next"] {
                display: none !important;
                visibility: hidden !important;
            }
            
            @keyframes lilac-pulse-button {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        
        document.head.appendChild(styleElement);
        console.log('[DEBUG HINT] Global hint styles added');
    }
    
    /**
     * Refresh the module
     */
    function refresh() {
        processExistingQuestions();
    }
    
    /**
     * Enable inputs for a question - helper method that coordinates with AnswerReselection module
     * @param {HTMLElement} questionElement - The question container
     */
    function enableInputsForQuestion(questionElement) {
        console.log('[DEBUG HINT] Trying to re-enable inputs from hint module');
        // Enable all inputs directly (backup in case AnswerReselection module fails)
        questionElement.querySelectorAll(config.selectors.questionInput).forEach(function(input) {
            input.disabled = false;
            input.removeAttribute('disabled');
            input.style.pointerEvents = 'auto';
            input.style.cursor = 'pointer';
        });
        
        // If we have direct access to the AnswerReselection module, use it
        if (typeof LilacQuiz.getModule === 'function') {
            const answerModule = LilacQuiz.getModule('answerReselection');
            if (answerModule && typeof answerModule.enableInputs === 'function') {
                answerModule.enableInputs(questionElement);
            }
        }
    }
    
    // Public API
    return {
        init: init,
        refresh: refresh,
        // Add enforce hint as a requirement for this module
        requires: ['enforceHint']
    };
})());
