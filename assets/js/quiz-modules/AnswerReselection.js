/**
 * AnswerReselection.js - Module for handling answer reselection in LearnDash quizzes
 * 
 * This module enables users to reselect answers after submitting an incorrect response,
 * integrating with the hint system as needed.
 */

// Register the Answer Reselection module with the core
LilacQuiz.registerModule('answerReselection', (function() {
    'use strict';
    
    // Module configuration and state
    const config = {
        selectors: {
            listItem: '.wpProQuiz_listItem',
            question: '.wpProQuiz_question',
            questionInput: '.wpProQuiz_questionInput',
            checkButton: '.wpProQuiz_button[name="check"]',
            nextButton: '.wpProQuiz_button[name="next"]',
            hintButton: '.wpProQuiz_TipButton, .wpProQuiz_hint',
            incorrectMessage: '.wpProQuiz_incorrect',
            correctMessage: '.wpProQuiz_correct',
            questionList: '.wpProQuiz_questionList',
            questionLabel: '.wpProQuiz_questionListItem label'
        },
        attributes: {
            questionId: 'data-question-id',
            hintViewed: 'data-hint-viewed',
            hasHint: 'data-has-hint',
            answered: 'data-answered',
            correct: 'data-correct'
        },
        classes: {
            highlight: 'lilac-highlight',
            tooltip: 'lilac-hint-tooltip',
            tooltipArrow: 'lilac-tooltip-arrow'
        },
        tooltipText: 'טעית! להמשך חובה לקחת רמז!',
        eventNamespace: '.lilacQuizAnswerReselection'
    };
    
    // Keep track of question states
    let questionStates = {};
    
    /**
     * Initialize the module
     * @param {Object} settings - Settings from the core
     */
    function init(settings) {
        LilacQuiz.log('Answer Reselection Module initializing');
        
        // Set up all event handlers
        setupEventHandlers();
        
        // Process all visible questions
        processExistingQuestions();
        
        // Set up observer for dynamic content
        setupObserver();
    }
    
    /**
     * Process any existing questions on the page
     */
    function processExistingQuestions() {
        document.querySelectorAll(config.selectors.listItem).forEach(function(questionElement) {
            setupQuestion(questionElement);
        });
    }
    
    /**
     * Set up a single question
     * @param {HTMLElement} questionElement - The question container element
     */
    function setupQuestion(questionElement) {
        // Get or generate a unique ID for this question
        const questionId = getQuestionId(questionElement);
        
        // Initialize state for this question if not already done
        if (!questionStates[questionId]) {
            questionStates[questionId] = {
                answered: false,
                correct: false,
                hintViewed: false,
                hasHint: !!questionElement.querySelector(config.selectors.hintButton)
            };
        }
        
        // Store information as data attributes for easier access
        questionElement.setAttribute(config.attributes.questionId, questionId);
        questionElement.setAttribute(config.attributes.hasHint, questionStates[questionId].hasHint);
        questionElement.setAttribute(config.attributes.hintViewed, questionStates[questionId].hintViewed);
        
        // Make sure inputs are always enabled
        enableInputsForQuestion(questionElement);
        
        // Check current state (for page refreshes or navigation)
        checkQuestionState(questionElement);
    }
    
    /**
     * Check the current state of a question (correct, incorrect, etc.)
     * @param {HTMLElement} questionElement - The question container element
     */
    function checkQuestionState(questionElement) {
        // If question shows incorrect feedback
        if (questionElement.querySelector(config.selectors.incorrectMessage) &&
            window.getComputedStyle(questionElement.querySelector(config.selectors.incorrectMessage)).display !== 'none') {
            
            // Handle as if user just answered incorrectly
            handleIncorrectAnswer(questionElement);
        }
        // If question shows correct feedback
        else if (questionElement.querySelector(config.selectors.correctMessage) &&
                 window.getComputedStyle(questionElement.querySelector(config.selectors.correctMessage)).display !== 'none') {
            
            // Handle as if user just answered correctly
            handleCorrectAnswer(questionElement);
        }
    }
    
    /**
     * Set up all event handlers
     */
    function setupEventHandlers() {
        // Remove any existing handlers to prevent duplicates
        document.removeEventListener('click', handleCheckButtonClick);
        document.removeEventListener('change', handleInputChange);
        document.removeEventListener('click', handleNextButtonClick);
        document.removeEventListener('click', handleHintButtonClick);
        
        // Listen for check button clicks
        document.addEventListener('click', handleCheckButtonClick);
        
        // Listen for input changes
        document.addEventListener('change', handleInputChange);
        
        // Listen for next button clicks
        document.addEventListener('click', handleNextButtonClick);
        
        // Listen for hint button clicks
        document.addEventListener('click', handleHintButtonClick);
    }
    
    /**
     * Handle when the check button is clicked
     * @param {Event} event - The click event
     */
    function handleCheckButtonClick(event) {
        if (!event.target.matches(config.selectors.checkButton)) return;
        
        const questionElement = event.target.closest(config.selectors.listItem);
        if (!questionElement) return;
        
        // Set a timeout to allow LearnDash to process the answer first
        setTimeout(function() {
            processAnswerResult(questionElement);
        }, 300);
    }
    
    /**
     * Handle when an input is changed
     * @param {Event} event - The change event
     */
    function handleInputChange(event) {
        if (!event.target.matches(config.selectors.questionInput)) return;
        
        const questionElement = event.target.closest(config.selectors.listItem);
        if (!questionElement) return;
        
        // If this question previously showed incorrect feedback
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        if (incorrectMessage && window.getComputedStyle(incorrectMessage).display !== 'none') {
            // Hide feedback messages
            hideMessages(questionElement);
            
            // Make check button visible
            showCheckButton(questionElement);
            
            // Optional: remove any tooltips
            removeTooltip(questionElement);
        }
    }
    
    /**
     * Handle when the next button is clicked
     * @param {Event} event - The click event
     */
    function handleNextButtonClick(event) {
        if (!event.target.matches(config.selectors.nextButton)) return;
        
        const questionElement = event.target.closest(config.selectors.listItem);
        if (!questionElement) return;
        
        // If this question shows incorrect feedback
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        if (incorrectMessage && window.getComputedStyle(incorrectMessage).display !== 'none') {
            // Prevent navigating to next question
            event.preventDefault();
            event.stopPropagation();
            
            // Highlight hint button
            highlightHintButton(questionElement);
            
            return false;
        }
    }
    
    /**
     * Handle when the hint button is clicked
     * @param {Event} event - The click event
     */
    function handleHintButtonClick(event) {
        if (!event.target.matches(config.selectors.hintButton)) return;
        
        const questionElement = event.target.closest(config.selectors.listItem);
        if (!questionElement) return;
        
        // Update question state
        const questionId = getQuestionId(questionElement);
        if (questionStates[questionId]) {
            questionStates[questionId].hintViewed = true;
        }
        
        // Update attribute
        questionElement.setAttribute(config.attributes.hintViewed, 'true');
        
        // Remove tooltip
        removeTooltip(questionElement);
    }
    
    /**
     * Process the answer result after the check button is clicked
     * @param {HTMLElement} questionElement - The question container element
     */
    function processAnswerResult(questionElement) {
        // Check if the answer is incorrect
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        if (incorrectMessage && window.getComputedStyle(incorrectMessage).display !== 'none') {
            handleIncorrectAnswer(questionElement);
        }
        // Check if the answer is correct
        else {
            const correctMessage = questionElement.querySelector(config.selectors.correctMessage);
            if (correctMessage && window.getComputedStyle(correctMessage).display !== 'none') {
                handleCorrectAnswer(questionElement);
            }
        }
    }
    
    /**
     * Handle when an incorrect answer is given
     * @param {HTMLElement} questionElement - The question container element
     */
    function handleIncorrectAnswer(questionElement) {
        // Get question ID
        const questionId = getQuestionId(questionElement);
        console.log('[DEBUG] Handling incorrect answer for question', questionId);
        
        // Update question state
        if (questionStates[questionId]) {
            questionStates[questionId].answered = true;
            questionStates[questionId].correct = false;
        }
        
        // Update attributes
        questionElement.setAttribute(config.attributes.answered, 'true');
        questionElement.setAttribute(config.attributes.correct, 'false');
        
        // Hide next button
        hideNextButton(questionElement);
        console.log('[DEBUG] Next button should be hidden');
        
        // Show check button for re-submission
        showCheckButton(questionElement);
        console.log('[DEBUG] Check button should be visible');
        
        // CRITICAL: Make sure inputs remain enabled
        console.log('[DEBUG] About to enable inputs for question', questionId);
        enableInputsForQuestion(questionElement);
        
        // Force enabled state again with a delay
        setTimeout(function() {
            console.log('[DEBUG] Delayed re-enabling of inputs');
            enableInputsForQuestion(questionElement);
            
            // Check if they're actually enabled
            const stillDisabled = Array.from(questionElement.querySelectorAll(config.selectors.questionInput))
                .filter(input => input.disabled).length;
            
            if (stillDisabled > 0) {
                console.error('[DEBUG] CRITICAL: Inputs are still disabled after multiple enable attempts!');
            } else {
                console.log('[DEBUG] All inputs enabled successfully');
            }
        }, 300);
        
        // Highlight hint button if available
        highlightHintButton(questionElement);
    }
    
    /**
     * Handle when a correct answer is given
     * @param {HTMLElement} questionElement - The question container element
     */
    function handleCorrectAnswer(questionElement) {
        // Get question ID
        const questionId = getQuestionId(questionElement);
        
        // Update question state
        if (questionStates[questionId]) {
            questionStates[questionId].answered = true;
            questionStates[questionId].correct = true;
        }
        
        // Update attributes
        questionElement.setAttribute(config.attributes.answered, 'true');
        questionElement.setAttribute(config.attributes.correct, 'true');
        
        // Show next button
        showNextButton(questionElement);
        
        // Remove any tooltips
        removeTooltip(questionElement);
    }
    
    /**
     * Hide the next button for a question
     * @param {HTMLElement} questionElement - The question container element
     */
    function hideNextButton(questionElement) {
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (nextButton) {
            nextButton.style.display = 'none';
            nextButton.style.visibility = 'hidden';
            // Force style with !important via attribute
            nextButton.setAttribute('style', 'display: none !important; visibility: hidden !important;');
        }
    }
    
    /**
     * Show the next button for a question
     * @param {HTMLElement} questionElement - The question container element
     */
    function showNextButton(questionElement) {
        const nextButton = questionElement.querySelector(config.selectors.nextButton);
        if (nextButton) {
            nextButton.style.display = 'inline-block';
            nextButton.style.visibility = 'visible';
            // Remove forced style
            nextButton.removeAttribute('style');
        }
    }
    
    /**
     * Hide feedback messages
     * @param {HTMLElement} questionElement - The question container element
     */
    function hideMessages(questionElement) {
        const incorrectMessage = questionElement.querySelector(config.selectors.incorrectMessage);
        const correctMessage = questionElement.querySelector(config.selectors.correctMessage);
        
        if (incorrectMessage) {
            incorrectMessage.style.display = 'none';
        }
        
        if (correctMessage) {
            correctMessage.style.display = 'none';
        }
    }
    
    /**
     * Show the check button for a question
     * @param {HTMLElement} questionElement - The question container element
     */
    function showCheckButton(questionElement) {
        const checkButton = questionElement.querySelector(config.selectors.checkButton);
        if (checkButton) {
            checkButton.style.display = 'inline-block';
            checkButton.disabled = false;
        }
    }
    
    /**
     * Enable inputs for a question
     * @param {HTMLElement} questionElement - The question container element
     */
    function enableInputsForQuestion(questionElement) {
        console.log('[DEBUG] Enabling inputs for question', getQuestionId(questionElement));
        
        // Enable all inputs - using both approaches to be sure
        questionElement.querySelectorAll(config.selectors.questionInput).forEach(function(input) {
            // Debug what we found
            console.log('[DEBUG] Found input:', input.type, 'disabled:', input.disabled);
            
            // Remove disabled attribute and property
            input.disabled = false;
            input.removeAttribute('disabled');
            
            // Force clickability
            input.style.pointerEvents = 'auto';
            input.style.cursor = 'pointer';
            
            // Make parent label clickable too
            const parentLabel = input.closest('label');
            if (parentLabel) {
                parentLabel.style.pointerEvents = 'auto';
                parentLabel.style.cursor = 'pointer';
                
                // Add inline event handler to ensure clickability
                parentLabel.onclick = function(e) {
                    // This helps force the input to be clickable
                    if (input.disabled) {
                        console.log('[DEBUG] Input was still disabled - forcing enable');
                        setTimeout(function() {
                            input.disabled = false;
                            input.click();
                        }, 10);
                    }
                };
            }
            
            // Force enable with timeout backup
            setTimeout(function() {
                if (input.disabled) {
                    console.log('[DEBUG] Input still disabled after timeout - force re-enabling');
                    input.disabled = false;
                    input.removeAttribute('disabled');
                }
            }, 100);
        });
        
        // Make labels clickable
        questionElement.querySelectorAll(config.selectors.questionLabel).forEach(function(label) {
            label.style.pointerEvents = 'auto';
            label.style.cursor = 'pointer';
        });
        
        // More aggressive approach: use MutationObserver to watch for disabled being added back
        setupInputWatcher(questionElement);
    }
    
    /**
     * Highlight the hint button
     * @param {HTMLElement} questionElement - The question container element
     */
    function highlightHintButton(questionElement) {
        const hintButton = questionElement.querySelector(config.selectors.hintButton);
        if (!hintButton) return;
        
        // Add highlighting class
        hintButton.classList.add(config.classes.highlight);
        
        // Add styles directly
        hintButton.style.animation = 'lilac-pulse-button 1.5s infinite';
        hintButton.style.backgroundColor = '#ffc107';
        hintButton.style.color = '#333';
        hintButton.style.fontWeight = 'bold';
        hintButton.style.boxShadow = '0 0 10px rgba(255, 193, 7, 0.7)';
        
        // Remove any existing tooltips
        removeTooltip(questionElement);
        
        // Create tooltip
        const tooltip = document.createElement('div');
        tooltip.className = config.classes.tooltip;
        tooltip.textContent = config.tooltipText;
        
        // Add tooltip to question
        hintButton.parentNode.appendChild(tooltip);
        
        // Position tooltip
        const buttonRect = hintButton.getBoundingClientRect();
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
        tooltip.style.left = `${hintButton.offsetLeft + (hintButton.offsetWidth / 2)}px`;
        
        // Add arrow to tooltip
        const arrow = document.createElement('div');
        arrow.className = config.classes.tooltipArrow;
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
        
        // Auto-scroll to hint button
        setTimeout(function() {
            hintButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }
    
    /**
     * Remove tooltip from question
     * @param {HTMLElement} questionElement - The question container element
     */
    function removeTooltip(questionElement) {
        const tooltip = questionElement.querySelector(`.${config.classes.tooltip}`);
        if (tooltip) {
            tooltip.remove();
        }
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
                            setupQuestion(node);
                        }
                        
                        // Check for questions inside the added node
                        const questions = node.querySelectorAll(config.selectors.listItem);
                        questions.forEach(setupQuestion);
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
     * Refresh the module
     */
    function refresh() {
        processExistingQuestions();
    }
    
    /**
     * Set up a mutation observer to watch for inputs being disabled again
     * @param {HTMLElement} questionElement - The question container
     */
    function setupInputWatcher(questionElement) {
        if (!window.MutationObserver) return;
        
        // Create an observer instance to watch for the 'disabled' attribute
        const observer = new MutationObserver(function(mutations) {
            let needsReEnable = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'disabled' && 
                    mutation.target.hasAttribute('disabled')) {
                    needsReEnable = true;
                    console.log('[DEBUG] LearnDash re-disabled an input! Re-enabling...');
                }
            });
            
            if (needsReEnable) {
                // Re-enable all inputs
                questionElement.querySelectorAll(config.selectors.questionInput).forEach(function(input) {
                    input.disabled = false;
                    input.removeAttribute('disabled');
                });
            }
        });
        
        // Start observing all inputs for attribute changes
        questionElement.querySelectorAll(config.selectors.questionInput).forEach(function(input) {
            observer.observe(input, { attributes: true });
        });
    }
    
    // Public API - what is exposed to the core
    return {
        init: init,
        refresh: refresh,
        // Expose the enableInputs function for cross-module communication
        enableInputs: enableInputsForQuestion,
        // Add hint enforcement as a requirement for this module
        requires: ['allowReselection']
    };
})());
