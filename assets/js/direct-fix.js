/**
 * Direct Fix for Quiz Flow
 * 
 * A direct, aggressive fix for the three core issues:
 * 1. Inputs remain disabled
 * 2. Next button shows after wrong answer
 * 3. Hint button is not visible
 */
(function() {
    'use strict';
    
    // Map of correct answers (we'll build this if possible)
    const correctAnswers = {};
    
    // Debug is always on - we need to see what's happening
    console.log('[DirectFix] Script loaded');
    
    // Immediately execute on load - no delay
    fixCurrentState();
    setupObservers();
    addCriticalStyles();
    
    /**
     * Fix the current state of the DOM immediately
     */
    function fixCurrentState() {
        console.log('[DirectFix] Fixing current state');
        
        // 1. Get all the questions
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        console.log(`[DirectFix] Found ${questions.length} questions`);
        
        // 2. Process each question's current state
        questions.forEach((question, index) => {
            // Get question ID
            const questionList = question.querySelector('.wpProQuiz_questionList');
            const questionId = questionList ? questionList.getAttribute('data-question_id') : `unknown-${index}`;
            console.log(`[DirectFix] Processing question ${questionId}`);
            
            // Check if question has an incorrect message visible
            const incorrectMsg = question.querySelector('.wpProQuiz_incorrect');
            const isIncorrect = incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none';
            
            if (isIncorrect) {
                console.log(`[DirectFix] Question ${questionId} has INCORRECT answer showing`);
                
                // ISSUE 1: Enable all inputs that are disabled
                const disabledInputs = question.querySelectorAll('input[disabled], .wpProQuiz_questionInput[disabled]');
                console.log(`[DirectFix] Found ${disabledInputs.length} disabled inputs`);
                
                disabledInputs.forEach(input => {
                    console.log(`[DirectFix] Enabling input: ${input.name} value=${input.value}`);
                    input.disabled = false;
                    input.removeAttribute('disabled');
                    
                    // Also force the label to be clickable
                    const label = input.closest('label');
                    if (label) {
                        label.style.pointerEvents = 'auto';
                        label.style.cursor = 'pointer';
                        label.onclick = function() {
                            console.log(`[DirectFix] Label clicked for ${input.name}`);
                            input.checked = true;
                            // Also trigger change event
                            const event = new Event('change', { bubbles: true });
                            input.dispatchEvent(event);
                        };
                    }
                });
                
                // ISSUE 2: Hide the Next button completely
                const nextButton = question.querySelector('.wpProQuiz_button[name="next"]');
                if (nextButton) {
                    console.log(`[DirectFix] Found Next button: "${nextButton.value}"`);
                    console.log(`[DirectFix] Next button display was: ${window.getComputedStyle(nextButton).display}`);
                    console.log('[DirectFix] HIDING Next button');
                    nextButton.style.display = 'none';
                    nextButton.style.visibility = 'hidden';
                    
                    // Also block clicks
                    nextButton.addEventListener('click', function(e) {
                        console.log('[DirectFix] Blocked Next button click');
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }, true);
                }
                
                // ISSUE 3: Make sure hint button is visible
                const hintButton = question.querySelector('.wpProQuiz_TipButton, .wpProQuiz_button[name="tip"]');
                if (hintButton) {
                    console.log(`[DirectFix] Found Hint button: "${hintButton.value}"`);
                    console.log(`[DirectFix] Hint button display was: ${window.getComputedStyle(hintButton).display}`);
                    console.log('[DirectFix] SHOWING Hint button');
                    hintButton.style.display = 'inline-block';
                    hintButton.style.visibility = 'visible';
                    
                    // Force highlight on hint button
                    hintButton.style.backgroundColor = '#ffc107';
                    hintButton.style.color = '#333';
                    hintButton.style.fontWeight = 'bold';
                    hintButton.style.border = '2px solid #e0a800';
                } else {
                    // Create a hint button if one doesn't exist
                    console.log('[DirectFix] NO Hint button found! Creating one...');
                    const newHintButton = document.createElement('input');
                    newHintButton.type = 'button';
                    newHintButton.name = 'tip';
                    newHintButton.value = 'רמז';
                    newHintButton.className = 'wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton';
                    newHintButton.style.cssText = 'float: left; display: inline-block; margin-right: 10px; background-color: #ffc107; color: #333; font-weight: bold; border: 2px solid #e0a800;';
                    
                    // Find where to insert the button
                    const buttonContainer = question.querySelector('p:has(.wpProQuiz_button)') || 
                                          question.querySelector('.wpProQuiz_response').nextElementSibling;
                    
                    if (buttonContainer) {
                        console.log('[DirectFix] Adding new hint button');
                        buttonContainer.insertBefore(newHintButton, buttonContainer.firstChild);
                    }
                }
                
                // Show check button if needed
                const checkButton = question.querySelector('.wpProQuiz_button[name="check"]');
                if (checkButton) {
                    console.log(`[DirectFix] Check button display was: ${window.getComputedStyle(checkButton).display}`);
                    if (window.getComputedStyle(checkButton).display === 'none') {
                        console.log('[DirectFix] SHOWING Check button');
                        checkButton.style.display = 'inline-block';
                    }
                }
            } else {
                console.log(`[DirectFix] Question ${questionId} does NOT have incorrect answer showing`);
                
                // If no incorrect message, we still need to enable inputs for initial selection
                const allInputs = question.querySelectorAll('.wpProQuiz_questionInput');
                console.log(`[DirectFix] Ensuring all ${allInputs.length} inputs are enabled initially`);
                
                allInputs.forEach(input => {
                    if (input.disabled) {
                        console.log(`[DirectFix] Enabling input: ${input.name}`);
                        input.disabled = false;
                        input.removeAttribute('disabled');
                    }
                });
            }
            
            // Track selected answer and check-button click
            const inputs = question.querySelectorAll('.wpProQuiz_questionInput');
            inputs.forEach(input => {
                // Remove existing listeners to avoid duplicates
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                
                // Add new click listener
                newInput.addEventListener('click', function() {
                    console.log(`[DirectFix] Input clicked: ${this.name} value=${this.value}`);
                    
                    // Enable any siblings that might be disabled
                    const siblings = this.closest('.wpProQuiz_questionList').querySelectorAll('.wpProQuiz_questionInput');
                    siblings.forEach(sibling => {
                        if (sibling.disabled) {
                            console.log(`[DirectFix] Enabling sibling input: ${sibling.name}`);
                            sibling.disabled = false;
                            sibling.removeAttribute('disabled');
                        }
                    });
                });
                
                // Add change listener
                newInput.addEventListener('change', function() {
                    console.log(`[DirectFix] Input changed: ${this.name} value=${this.value}`);
                });
            });
            
            // Watch check button
            const checkButton = question.querySelector('.wpProQuiz_button[name="check"]');
            if (checkButton) {
                // Remove existing listeners
                const newCheckButton = checkButton.cloneNode(true);
                checkButton.parentNode.replaceChild(newCheckButton, checkButton);
                
                // Add new click listener
                newCheckButton.addEventListener('click', function() {
                    console.log('[DirectFix] Check button clicked');
                    
                    // After a delay, check the result
                    setTimeout(() => {
                        const incorrectMsg = question.querySelector('.wpProQuiz_incorrect');
                        const isIncorrect = incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none';
                        
                        if (isIncorrect) {
                            console.log('[DirectFix] Answer was INCORRECT');
                            
                            // Re-enable all inputs
                            const inputs = question.querySelectorAll('.wpProQuiz_questionInput');
                            inputs.forEach(input => {
                                console.log(`[DirectFix] Re-enabling input after wrong answer: ${input.name}`);
                                input.disabled = false;
                                input.removeAttribute('disabled');
                            });
                            
                            // Hide next button
                            const nextButton = question.querySelector('.wpProQuiz_button[name="next"]');
                            if (nextButton) {
                                console.log('[DirectFix] Hiding Next button after wrong answer');
                                nextButton.style.display = 'none';
                                nextButton.style.visibility = 'hidden';
                            }
                            
                            // Show hint button
                            const hintButton = question.querySelector('.wpProQuiz_TipButton, .wpProQuiz_button[name="tip"]');
                            if (hintButton) {
                                console.log('[DirectFix] Showing Hint button after wrong answer');
                                hintButton.style.display = 'inline-block';
                                hintButton.style.visibility = 'visible';
                                hintButton.style.backgroundColor = '#ffc107';
                            }
                        } else {
                            console.log('[DirectFix] Answer was CORRECT or still processing');
                        }
                    }, 100);
                });
            }
        });
    }
    
    /**
     * Set up observers to watch for DOM changes
     */
    function setupObservers() {
        console.log('[DirectFix] Setting up observers');
        
        // Watch for style changes on incorrect/correct messages
        const responseObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'style') {
                    const target = mutation.target;
                    
                    // If this is an incorrect message that became visible
                    if (target.classList.contains('wpProQuiz_incorrect') && 
                        window.getComputedStyle(target).display !== 'none') {
                        
                        console.log('[DirectFix] Observed incorrect message becoming visible');
                        
                        // Get the question container
                        const question = target.closest('.wpProQuiz_listItem');
                        if (question) {
                            // Fix the state again
                            setTimeout(() => fixCurrentState(), 10);
                        }
                    }
                }
            });
        });
        
        // Observe all response containers
        document.querySelectorAll('.wpProQuiz_response').forEach(response => {
            responseObserver.observe(response, { attributes: true, attributeFilter: ['style'], subtree: true });
        });
        
        // Watch for inputs being disabled
        const inputObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'disabled') {
                    const input = mutation.target;
                    
                    if (input.disabled) {
                        console.log(`[DirectFix] Observed input becoming disabled: ${input.name}`);
                        
                        // Find the question container
                        const question = input.closest('.wpProQuiz_listItem');
                        if (question) {
                            // Check if this question has an incorrect message
                            const incorrectMsg = question.querySelector('.wpProQuiz_incorrect');
                            const isIncorrect = incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none';
                            
                            if (isIncorrect) {
                                // Re-enable the input if incorrect
                                console.log(`[DirectFix] Re-enabling input that was wrongly disabled: ${input.name}`);
                                input.disabled = false;
                                input.removeAttribute('disabled');
                            }
                        }
                    }
                }
            });
        });
        
        // Observe all inputs
        document.querySelectorAll('.wpProQuiz_questionInput').forEach(input => {
            inputObserver.observe(input, { attributes: true, attributeFilter: ['disabled'] });
        });
        
        // Watch for next buttons changing display
        const buttonObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.attributeName === 'style') {
                    const button = mutation.target;
                    
                    // If this is a next button becoming visible
                    if (button.getAttribute('name') === 'next' &&
                        window.getComputedStyle(button).display !== 'none') {
                        
                        console.log('[DirectFix] Observed Next button becoming visible');
                        
                        // Get the question container
                        const question = button.closest('.wpProQuiz_listItem');
                        if (question) {
                            // Check if this question has an incorrect message
                            const incorrectMsg = question.querySelector('.wpProQuiz_incorrect');
                            const isIncorrect = incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none';
                            
                            if (isIncorrect) {
                                // Re-hide the next button
                                console.log('[DirectFix] Re-hiding Next button that appeared with incorrect answer');
                                button.style.display = 'none';
                                button.style.visibility = 'hidden';
                            } else {
                                console.log('[DirectFix] Next button visible is correct (no incorrect message)');
                            }
                        }
                    }
                }
            });
        });
        
        // Observe all next buttons
        document.querySelectorAll('.wpProQuiz_button[name="next"]').forEach(button => {
            buttonObserver.observe(button, { attributes: true, attributeFilter: ['style'] });
        });
        
        // Watch for new questions being added
        const contentObserver = new MutationObserver(mutations => {
            let needsReprocessing = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    // Check added nodes
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches && (
                                node.matches('.wpProQuiz_listItem') || 
                                node.querySelector && node.querySelector('.wpProQuiz_listItem, .wpProQuiz_questionInput, .wpProQuiz_button'))) {
                                needsReprocessing = true;
                            }
                        }
                    });
                    
                    // Also check removed nodes - might need to reprocess if important elements were removed
                    mutation.removedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches && (
                                node.matches('.wpProQuiz_questionInput, .wpProQuiz_button'))) {
                                needsReprocessing = true;
                            }
                        }
                    });
                }
            });
            
            if (needsReprocessing) {
                console.log('[DirectFix] Detected DOM changes, reprocessing...');
                setTimeout(fixCurrentState, 10);
            }
        });
        
        // Observe the entire quiz container
        document.querySelectorAll('.wpProQuiz_quiz').forEach(quiz => {
            contentObserver.observe(quiz, { childList: true, subtree: true });
        });
    }
    
    /**
     * Add critical CSS styles directly to the page
     */
    function addCriticalStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* Force inputs to be enabled */
            .wpProQuiz_questionInput[disabled] {
                pointer-events: auto !important;
                cursor: pointer !important;
                opacity: 1 !important;
            }
            
            /* Hide next button when incorrect is visible */
            .wpProQuiz_incorrect:not(.wpProQuiz_review) ~ .wpProQuiz_button[name="next"],
            .wpProQuiz_listItem:has(.wpProQuiz_incorrect) .wpProQuiz_button[name="next"] {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Force hint button to be visible */
            .wpProQuiz_TipButton,
            .wpProQuiz_button[name="tip"] {
                display: inline-block !important;
                visibility: visible !important;
            }
        `;
        
        document.head.appendChild(style);
        console.log('[DirectFix] Added critical CSS styles');
    }
    
    // Periodically check and fix state to ensure it stays fixed
    setInterval(fixCurrentState, 1000);
    
    console.log('[DirectFix] Initialization complete');
})();
