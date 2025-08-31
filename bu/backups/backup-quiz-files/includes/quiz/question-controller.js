/**
 * LearnDash Quiz Question Controller
 * Uses LearnDash's native answer data to control quiz flow
 */

(function() {
    // Minimal initialization message
    console.log('🔍 Quiz Question Controller initialized');
    
    // Store which hints have been viewed
    const viewedHints = new Set();
    
    // Load previously viewed hints from localStorage
    function loadViewedHints(quizId) {
        if (!quizId) return;
        
        const storedHints = localStorage.getItem(`lilac_viewed_hints_${quizId}`);
        if (storedHints) {
            try {
                const hintsArray = JSON.parse(storedHints);
                hintsArray.forEach(hint => viewedHints.add(hint));
                console.log(`Loaded ${hintsArray.length} previously viewed hints from storage`);
            } catch (e) {
                console.error('Error loading viewed hints from storage:', e);
            }
        }
    }
    
    // Save viewed hints to localStorage
    function saveViewedHints(quizId) {
        if (!quizId) return;
        
        const hintsArray = Array.from(viewedHints);
        localStorage.setItem(`lilac_viewed_hints_${quizId}`, JSON.stringify(hintsArray));
    }
    
    // Initialize when DOM is ready and after LearnDash loads
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('wpProQuiz_initComplete', initialize);
    
    // Add notification message for force hint mode
    function addForceHintNotification() {
        const quizForm = document.querySelector('.wpProQuiz_quiz');
        if (!quizForm) return;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'force-hint-notification';
        notification.innerHTML = '<strong>שים לב:</strong> בשאלות בבוחן זה, עליך לצפות ברמז ולבחור את התשובה הנכונה כדי להמשיך לשאלה הבאה.';
        
        // Insert at the top of the quiz
        quizForm.insertBefore(notification, quizForm.firstChild);
    }
    
    // Set up once loaded
    function initialize() {
        // Find all quiz questions
        const quizQuestions = document.querySelectorAll('.wpProQuiz_listItem');
        if (!quizQuestions.length) return;
        
        console.log(`Found ${quizQuestions.length} quiz questions`);
        
        // Get the quiz settings
        const settings = {
            forceHintMode: isForceHintModeEnabled(),
            quizId: getQuizId(),
            richSidebarEnabled: isRichSidebarEnabled()
        };
        
        // Add a class to the quiz container for styling purposes
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        if (quizContainer) {
            if (settings.forceHintMode) {
                quizContainer.classList.add('force-hint-mode');
            }
            if (settings.richSidebarEnabled) {
                quizContainer.classList.add('rich-sidebar-enabled');
            }
        }
        
        // Log quiz settings
        console.log('Quiz settings:', settings);
        
        // Load previously viewed hints
        if (settings.quizId) {
            loadViewedHints(settings.quizId);
        }
        
        if (settings.forceHintMode) {
            console.log('Force hint mode enabled for this quiz');
            // Initially hide all Next buttons in force hint mode
            quizQuestions.forEach(question => {
                const nextButton = question.querySelector('.wpProQuiz_QuestionButton[name="next"]');
                if (nextButton) {
                    nextButton.style.display = 'none';
                    console.log('Force hint mode: Initially hiding Next button');
                }
            });
            
            // Add the instructions notification at the top
            addForceHintNotification();
        } else {
            console.log('Regular quiz mode (no force hint requirement)');
            // For regular quizzes, ensure Next buttons are visible
            quizQuestions.forEach(question => {
                const nextButton = question.querySelector('.wpProQuiz_QuestionButton[name="next"]');
                if (nextButton) {
                    nextButton.style.display = '';
                    enableButton(nextButton);
                    console.log('Regular quiz mode: Ensuring Next button is visible');
                }
            });
        }
        
        // Set up event listeners for each question's buttons
        setupEventListeners(quizQuestions, settings);
        
        // Set up observer for answer correctness
        observeAnswerSubmission(quizQuestions, settings);
        
        // Check initial status of all questions
        quizQuestions.forEach(question => {
            checkAnswerStatus(question, settings);
        });
        
        // Integrate with ACF hint sidebar if available
        if (settings.richSidebarEnabled) {
            setupACFIntegration(quizQuestions, settings);
        }
    }
    
    // Check if rich sidebar is enabled for this quiz
    function isRichSidebarEnabled() {
        // Check for meta tag indicator
        const richSidebarFlag = document.querySelector('meta[name="lilac-quiz-rich-sidebar"]');
        if (richSidebarFlag && richSidebarFlag.getAttribute('content') === 'true') {
            return true;
        }
        
        // Check if the rich media sidebar script is active
        if (document.querySelector('#rich-media-debug, #script-indicator')) {
            return true;
        }
        
        // Check for ACF hints container
        if (document.querySelector('.quiz-sidebar-area, .quiz-media-sidebar')) {
            return true;
        }
        
        return false;
    }
    
    // Setup integration with ACF hint and rich media sidebar
    function setupACFIntegration(questions, settings) {
        console.log('Setting up ACF integration for rich sidebar content');
        
        // Make sure any existing sidebar is visible
        const sidebarContainers = document.querySelectorAll('.quiz-sidebar-area, .quiz-media-sidebar, .quiz-with-sidebar-container');
        if (sidebarContainers.length > 0) {
            console.log(`Found ${sidebarContainers.length} sidebar containers`);
            sidebarContainers.forEach(container => {
                container.style.display = 'block';
                container.style.visibility = 'visible';
                container.style.opacity = '1';
            });
        } else {
            console.log('No sidebar containers found');
        }
        
        // Check for ACF fields in the current quiz
        const acfContainers = document.querySelectorAll('[data-acf-field], [data-field-type="acf"], .acf-field, .media-item-hint');
        console.log(`Found ${acfContainers.length} ACF field containers`);
        
        // Listen for hint viewed events to update ACF sidebar content
        document.addEventListener('hintViewed', function(e) {
            const questionId = e.detail.questionId;
            console.log(`Hint viewed for question ${questionId}, looking for matching sidebar content`);
            
            // Try to highlight matching sidebar content if available
            const sidebarItem = document.querySelector(`#media-question-${questionId}, .quiz-sidebar-item[data-question-id="${questionId}"], .media-item[id*="${questionId}"]`);
            if (sidebarItem) {
                console.log(`Found matching sidebar item for question ${questionId}`);
                // Remove active class from all items
                document.querySelectorAll('.quiz-sidebar-item.active, .media-item.active').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to matching item
                sidebarItem.classList.add('active');
                sidebarItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                // Make sure it's visible
                sidebarItem.style.display = 'block';
                sidebarItem.style.visibility = 'visible';
                sidebarItem.style.opacity = '1';
            } else {
                console.log(`No matching sidebar item found for question ${questionId}`);
            }
        });
        
        // If rich sidebar enabled, make sure our content is visible
        if (settings.richSidebarEnabled) {
            // Look for all hint containers
            const hintContainers = document.querySelectorAll('.wpProQuiz_tipp, .media-item-hint');
            hintContainers.forEach(container => {
                // Make the hint containers better visible
                if (container.classList.contains('wpProQuiz_tipp')) {
                    // Adjust LearnDash's native hint container
                    container.style.maxWidth = '100%';
                    container.style.width = '100%';
                }
            });
            console.log(`Enhanced ${hintContainers.length} hint containers for rich media display`);
        }
    }
    
    // Detect if force hint mode is enabled on this quiz
    function isForceHintModeEnabled() {
        // 1. Look for the server-side flag embedded in the page
        const forceHintFlag = document.querySelector('meta[name="lilac-quiz-force-hint"]');
        if (forceHintFlag && forceHintFlag.getAttribute('content') === 'true') {
            return true;
        }
        
        // 2. Check if the option was set via URL parameter for testing
        if (new URLSearchParams(window.location.search).has('force-hint')) {
            return true;
        }
        
        // 3. Look for quiz-specific data attributes that might indicate force hint mode
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        if (quizContainer && quizContainer.getAttribute('data-force-hint-mode') === 'true') {
            return true;
        }
        
        // 4. For backward compatibility, check if we have specific markup indicating a force hint quiz
        const markers = document.querySelectorAll('.force-hint-quiz-marker, .wpProQuiz_quiz.force-hint-mode');
        if (markers.length > 0) {
            return true;
        }
        
        return false;
    }
    
    // Get quiz ID
    function getQuizId() {
        const quizForm = document.querySelector('.wpProQuiz_quiz');
        return quizForm ? quizForm.getAttribute('data-quiz-id') : null;
    }
    
    // Set up event listeners for quiz buttons
    function setupEventListeners(questions, settings) {
        questions.forEach(question => {
            // Add visual indicators for force hint mode
            if (settings.forceHintMode) {
                addQuestionIndicators(question);
            }
            
            // Track when Check button is clicked
            const checkButton = question.querySelector('.wpProQuiz_QuestionButton[name="check"]');
            if (checkButton) {
                checkButton.addEventListener('click', function() {
                    handleCheckClick(question, settings);
                });
            }
            
            // Track when Hint button is clicked
            const hintButton = question.querySelector('.wpProQuiz_QuestionButton[name="tip"]');
            if (hintButton) {
                hintButton.addEventListener('click', function() {
                    handleHintClick(question, settings);
                });
            }
            
            // Observe answer selection
            observeAnswerSelection(question, settings);
        });
    }
    
    // Add visual indicators to quiz questions in force hint mode
    function addQuestionIndicators(question) {
        // Add a hint requirement indicator to the question header
        const header = question.querySelector('.wpProQuiz_header');
        if (header) {
            const indicator = document.createElement('span');
            indicator.className = 'hint-required-indicator';
            indicator.textContent = '(נדרש רמז)';
            header.appendChild(indicator);
        }
        
        // Enhance the hint button appearance
        const hintButton = question.querySelector('.wpProQuiz_QuestionButton[name="tip"]');
        if (hintButton) {
            hintButton.classList.add('force-hint-button');
        }
    }
    
    // Handle when Check button is clicked
    function handleCheckClick(question, settings) {
        // Wait for LearnDash to update the DOM
        setTimeout(() => {
            checkAnswerStatus(question, settings);
        }, 300);
    }
    
    // Handle when Hint button is clicked
    function handleHintClick(question, settings) {
        const questionId = getQuestionId(question);
        
        // Add this question to the set of viewed hints
        viewedHints.add(questionId);
        
        // Save to localStorage
        if (settings.quizId) {
            saveViewedHints(settings.quizId);
        }
        
        // Update visual state
        const hintButton = question.querySelector('.wpProQuiz_QuestionButton[name="tip"]');
        if (hintButton) {
            hintButton.classList.add('hint-viewed');
            hintButton.classList.remove('highlight-hint');
        }
        
        // Dispatch custom event for tracking hint view
        const event = new CustomEvent('hintViewed', { 
            detail: { questionId } 
        });
        document.dispatchEvent(event);
        
        // Re-check button status
        checkAnswerStatus(question, settings);
    }
    
    // Observe when a user selects an answer
    function observeAnswerSelection(question, settings) {
        const inputs = question.querySelectorAll('.wpProQuiz_questionInput');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                // Get the parent list item
                const answerItem = this.closest('.wpProQuiz_questionListItem');
                if (answerItem) {
                    // Mark selected answers
                    const allItems = question.querySelectorAll('.wpProQuiz_questionListItem');
                    allItems.forEach(item => {
                        item.classList.remove('user-selected');
                    });
                    answerItem.classList.add('user-selected');
                }
            });
        });
    }
    
    // Observer answer submission using MutationObserver
    function observeAnswerSubmission(questions, settings) {
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    
                    // If a response container changed visibility
                    if (mutation.target.classList.contains('wpProQuiz_response') ||
                        mutation.target.classList.contains('wpProQuiz_correct') ||
                        mutation.target.classList.contains('wpProQuiz_incorrect')) {
                        
                        const question = mutation.target.closest('.wpProQuiz_listItem');
                        if (question) {
                            checkAnswerStatus(question, settings);
                        }
                    }
                }
            });
        });
        
        // Observe all questions
        questions.forEach(question => {
            const responseBlock = question.querySelector('.wpProQuiz_response');
            if (responseBlock) {
                observer.observe(responseBlock, { 
                    attributes: true,
                    childList: true,
                    subtree: true
                });
            }
        });
    }
    
    // Check the current answer status and hint view status for a question
    function checkAnswerStatus(question, settings) {
        const questionId = getQuestionId(question);
        const isCorrect = isAnswerCorrect(question);
        const nextButton = question.querySelector('.wpProQuiz_QuestionButton[name="next"]');
        const hintButton = question.querySelector('.wpProQuiz_QuestionButton[name="tip"]');
        const hasViewedHint = viewedHints.has(questionId);
        
        if (!nextButton) return;
        
        // Debug status
        console.log(`Question ${questionId} Status: correct=${isCorrect}, hint-viewed=${hasViewedHint}, force-hint=${settings.forceHintMode}`);
        
        // Update hint button visual status if previously viewed
        if (hasViewedHint && hintButton) {
            hintButton.classList.add('hint-viewed');
            hintButton.classList.remove('highlight-hint');
        }
        
        // Handle Next button visibility based on correctness and settings
        if (settings.forceHintMode) {
            // Check for explicit incorrect answers first (they take precedence)
            const hasIncorrectAnswer = question.querySelector('.wpProQuiz_answerIncorrect') !== null;
            const hasIncorrectMessage = question.querySelector('.wpProQuiz_incorrect') !== null && 
                                      window.getComputedStyle(question.querySelector('.wpProQuiz_incorrect')).display !== 'none';
            
            if (hasIncorrectAnswer || hasIncorrectMessage || !isCorrect) {
                // For incorrect answers, always hide the Next button
                nextButton.style.display = 'none';
                console.log(`Next button hidden for question ${questionId} - answer is incorrect`);
                
                // Remove the completed class if answer is no longer correct
                question.classList.remove('hint-question-completed');
                
                // Show hint tooltip if answer is wrong and emphasize it strongly
                if (hintButton) {
                    hintButton.title = 'צפה ברמז לעזרה בשאלה זו';
                    hintButton.classList.add('highlight-hint');
                    // Add a pulsing effect to draw attention
                    hintButton.classList.add('pulse-animation');
                    // Add an arrow indicator pointing to the hint button
                    if (!question.querySelector('.hint-arrow-indicator')) {
                        const arrowIndicator = document.createElement('div');
                        arrowIndicator.className = 'hint-arrow-indicator';
                        arrowIndicator.innerHTML = '⬅️ צפה ברמז';
                        hintButton.parentNode.insertBefore(arrowIndicator, hintButton);
                    }
                }
            } else if (isCorrect) {
                if (hasViewedHint) {
                    // Show Next button only if the hint has been viewed AND the answer is correct
                    nextButton.style.display = '';
                    enableButton(nextButton);
                    console.log(`Next button enabled for question ${questionId} - answer is correct and hint was viewed`);
                    
                    // Add a class to the question to show it's complete
                    question.classList.add('hint-question-completed');
                } else if (hintButton) {
                    // Hide Next button and emphasize that hint needs to be viewed
                    nextButton.style.display = 'none';
                    hintButton.title = 'עליך לצפות ברמז כדי להמשיך';
                    hintButton.classList.add('highlight-hint');
                    console.log(`Hint required for question ${questionId} before Next is shown`);
                }
            }
        } else {
            // In regular mode, always make sure the Next button is visible and enabled
            nextButton.style.display = '';
            enableButton(nextButton);
            console.log(`Regular quiz mode - Next button enabled for question ${questionId}`);
        }
    }
    
    // Check if the answer is correct using LearnDash's native indicators
    function isAnswerCorrect(question) {
        // Method 1: Check LearnDash's correct message visibility
        const correctBlock = question.querySelector('.wpProQuiz_correct');
        if (correctBlock && window.getComputedStyle(correctBlock).display !== 'none') {
            return true;
        }
        
        // Method 2: Check LearnDash's data attribute
        const questionList = question.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.dataset.correct === '1') {
            return true;
        }
        
        // Method 3: Check for correct answer classes
        const answerItems = question.querySelectorAll('.wpProQuiz_questionListItem');
        for (let i = 0; i < answerItems.length; i++) {
            if (answerItems[i].classList.contains('wpProQuiz_answerCorrect')) {
                return true;
            }
        }
        
        return false;
    }
    
    // Get the question ID from the question element
    function getQuestionId(question) {
        // Try to get the ID from data attribute
        if (question.dataset.questionId) {
            return question.dataset.questionId;
        }
        
        // Try to get from question list
        const questionList = question.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.id) {
            return questionList.id;
        }
        
        // Fallback: use the index as ID
        const allQuestions = document.querySelectorAll('.wpProQuiz_listItem');
        return 'q_' + Array.from(allQuestions).indexOf(question);
    }
    
    // Enable a button
    function enableButton(button) {
        if (!button) return;
        button.disabled = false;
        button.classList.remove('disabled');
        button.style.display = '';
        button.style.opacity = '';
        button.style.cursor = '';
    }
    
    // Disable a button
    function disableButton(button) {
        if (!button) return;
        button.disabled = true;
        button.classList.add('disabled');
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
    }
})();
