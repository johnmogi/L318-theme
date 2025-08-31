/**
 * Strict Next Button Controller v6.0
 * 
 * This script ensures the Next button only appears for verified correct answers
 * with minimal console logging and proper support for force hint mode.
 */

(function() {
    // Initialization - single console message
    console.log('Strict Next Button Controller v6.1 initialized with answer logging');
    
    // State tracking
    const hintViewed = {};
    
    // Initialize on DOM ready and when LearnDash quiz events fire
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('wpProQuiz_initComplete', init);
    setTimeout(init, 1000); // Backup initialization
    
    /**
     * Initialize the controller
     */
    function init() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        if (questionItems.length === 0) return;
        
        // Get quiz settings
        const settings = {
            forceHintMode: checkForceHintMode(),
            requireCorrect: true, // Always require correct answers
            enableAnswerLogging: true // Enable logging of all answers
        };
        
        // Log quiz information
        logQuizInfo();
        
        // Set up event listeners
        setupEventListeners(questionItems, settings);
        
        // Initial check of all Next buttons
        updateAllNextButtons(questionItems, settings);
        
        // Analyze and log all quiz answers
        logAllQuizAnswers(questionItems);
        
        // Set up periodic checker for Next buttons
        setInterval(() => {
            updateAllNextButtons(questionItems, settings);
        }, 2000);
    }
    
    /**
     * Log quiz information
     */
    function logQuizInfo() {
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        let quizInfo = {
            id: quizContainer ? quizContainer.dataset.quizId : null,
            title: document.title,
            questionCount: document.querySelectorAll('.wpProQuiz_listItem').length
        };
        
        console.log('%c QUIZ INFO:', 'background: #4CAF50; color: white; padding: 2px 5px; border-radius: 3px;', quizInfo);
    }
    
    /**
     * Log all quiz answers
     */
    function logAllQuizAnswers(questionItems) {
        console.log('%c QUIZ ANSWERS:', 'background: #2196F3; color: white; padding: 2px 5px; border-radius: 3px;');
        
        questionItems.forEach((question, index) => {
            // Get question details
            const questionId = getQuestionId(question);
            const questionText = getQuestionText(question);
            const answers = findCorrectAnswers(question);
            
            console.log(
                `%c Question ${index + 1}: ${questionId}`, 
                'font-weight: bold; color: #2196F3;', 
                '\n' + questionText
            );
            
            if (answers.length > 0) {
                console.log(
                    '%c Correct Answers:', 
                    'color: green; font-weight: bold;', 
                    answers
                );
            } else {
                console.log(
                    '%c No correct answers found yet', 
                    'color: orange;'
                );
            }
            console.log('----------------------------');
        });
    }
    
    /**
     * Get the text of a question
     */
    function getQuestionText(questionItem) {
        const questionTextElement = questionItem.querySelector('.wpProQuiz_question_text');
        return questionTextElement ? questionTextElement.textContent.trim() : 'Question text not found';
    }
    
    /**
     * Find all correct answers for a question
     */
    function findCorrectAnswers(questionItem) {
        const answers = [];
        
        // Try to find correct answers based on LearnDash markup
        const answerElements = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        
        answerElements.forEach((answer) => {
            // Check if this is a correct answer
            const isCorrect = answer.classList.contains('wpProQuiz_answerCorrect');
            
            // For multiple choice/single choice questions
            const input = answer.querySelector('.wpProQuiz_questionInput');
            const answerText = answer.textContent.trim();
            
            if (isCorrect) {
                let data = {
                    text: answerText,
                    type: input ? input.type : 'unknown'
                };
                
                if (input && input.value) {
                    data.value = input.value;
                }
                
                answers.push(data);
            }
        });
        
        return answers;
    }
    
    /**
     * Check if force hint mode is enabled
     */
    function checkForceHintMode() {
        // Check for data attribute on quiz container
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        if (quizContainer && quizContainer.dataset.forceHint === '1') {
            return true;
        }
        
        // Check for URL parameter
        if (window.location.href.indexOf('force_hint=1') !== -1) {
            return true;
        }
        
        // Check for global settings
        if (window.lilacQuizSettings && window.lilacQuizSettings.force_hint_mode === '1') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners(questionItems, settings) {
        // Listen for hint button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('wpProQuiz_TipButton')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    const questionId = getQuestionId(questionItem);
                    if (questionId) {
                        hintViewed[questionId] = true;
                        updateNextButton(questionItem, settings);
                    }
                }
            }
        });
        
        // Listen for answer selection changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('wpProQuiz_questionInput')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        updateNextButton(questionItem, settings);
                    }, 100);
                }
            }
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if (e.target.name === 'check' && e.target.classList.contains('wpProQuiz_button')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        updateNextButton(questionItem, settings);
                    }, 500); // Wait for check results
                }
            }
        });
    }
    
    /**
     * Update all Next buttons on the page
     */
    function updateAllNextButtons(questionItems, settings) {
        questionItems.forEach(question => {
            updateNextButton(question, settings);
        });
    }
    
    /**
     * Update a single Next button based on answer correctness
     */
    function updateNextButton(questionItem, settings) {
        const nextButton = questionItem.querySelector('.wpProQuiz_button[name=next]');
        if (!nextButton) return;
        
        const questionId = getQuestionId(questionItem);
        if (!questionId) return;
        
        // Check if answer is correct
        const isCorrect = isAnswerCorrect(questionItem);
        const hasViewedHint = hintViewed[questionId] || false;
        const hasSelection = hasSelectedAnswer(questionItem);
        
        // Add visual indicators
        if (hasViewedHint) {
            questionItem.classList.add('lilac-hint-viewed');
        }
        
        if (isCorrect) {
            questionItem.classList.add('lilac-answer-correct');
            
            // If correct answer is found, log it
            logAnswerState(questionItem, questionId, true);
        }
        
        // Decision logic - strict, only enable for correct answers
        let enableNext = false;
        
        if (isCorrect) {
            // Always enable Next for correct answers
            enableNext = true;
        } else if (settings.forceHintMode && hasViewedHint && hasSelection) {
            // In force hint mode, if they've viewed hint and selected an answer, enable Next
            enableNext = true;
            
            // Log that we're using force hint mode bypass
            console.log(`Question ${questionId}: Force hint mode enabled Next button`);
        }
        
        // Apply decision
        if (enableNext) {
            nextButton.disabled = false;
            nextButton.classList.remove('disabled');
            nextButton.classList.add('lilac-next-enabled');
        } else {
            nextButton.disabled = true;
            nextButton.classList.add('disabled');
            nextButton.classList.remove('lilac-next-enabled');
        }
    }
    
    /**
     * Log the answer state when a correct answer is found
     */
    function logAnswerState(questionItem, questionId, isCorrect) {
        if (!isCorrect) return;
        
        // Find selected answers
        const selectedAnswers = [];
        
        // Check for radio buttons and checkboxes
        const checkedInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        checkedInputs.forEach(input => {
            const listItem = input.closest('.wpProQuiz_questionListItem');
            if (listItem) {
                selectedAnswers.push({
                    value: input.value,
                    text: listItem.textContent.trim(),
                    type: input.type
                });
            }
        });
        
        // Check for text inputs
        const textInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput[type="text"]');
        textInputs.forEach(input => {
            if (input.value.trim() !== '') {
                selectedAnswers.push({
                    value: input.value,
                    type: 'text'
                });
            }
        });
        
        // Log the correct answer
        if (selectedAnswers.length > 0) {
            console.log(
                `%c CORRECT ANSWER for Question ${questionId}:`, 
                'background: green; color: white; padding: 2px 5px; border-radius: 3px;', 
                selectedAnswers
            );
        }
    }
    }
    
    /**
     * Get the ID of a question item
     */
    function getQuestionId(questionItem) {
        // Try data-question-meta attribute first
        if (questionItem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(questionItem.dataset.questionMeta);
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
            } catch (e) {
                // Ignore JSON parse errors
            }
        }
        
        // Try question list data attribute
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.dataset.questionId) {
            return questionList.dataset.questionId;
        }
        
        // Use index as fallback
        const allQuestions = Array.from(document.querySelectorAll('.wpProQuiz_listItem'));
        const index = allQuestions.indexOf(questionItem);
        if (index !== -1) {
            return 'q_' + index;
        }
        
        return null;
    }
    
    /**
     * Check if an answer is correct using multiple detection methods
     */
    function isAnswerCorrect(questionItem) {
        // Method 1: Check if response shows correct
        const responseBlock = questionItem.querySelector('.wpProQuiz_response');
        if (responseBlock) {
            const correctDiv = responseBlock.querySelector('.wpProQuiz_correct');
            if (correctDiv && window.getComputedStyle(correctDiv).display !== 'none') {
                return true;
            }
            
            // If incorrect message is visible, definitely not correct
            const incorrectDiv = responseBlock.querySelector('.wpProQuiz_incorrect');
            if (incorrectDiv && window.getComputedStyle(incorrectDiv).display !== 'none') {
                return false;
            }
        }
        
        // Method 2: Check for LearnDash correct answer markings
        const answerItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        for (let i = 0; i < answerItems.length; i++) {
            if (answerItems[i].classList.contains('wpProQuiz_answerCorrect')) {
                return true;
            }
        }
        
        // Method 3: Check for data-correct attribute
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.dataset.correct === '1') {
            return true;
        }
        
        // Method 4: Check for points display
        const pointsDiv = questionItem.querySelector('.wpProQuiz_points');
        if (pointsDiv) {
            const pointsText = pointsDiv.textContent.trim();
            const pointsMatch = pointsText.match(/(\d+)\s*\/\s*(\d+)/);
            if (pointsMatch && pointsMatch.length > 2) {
                const points = parseInt(pointsMatch[1]);
                if (points > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if the user has selected an answer
     */
    function hasSelectedAnswer(questionItem) {
        // Method 1: Check for checked inputs
        const checkedInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        if (checkedInputs.length > 0) {
            return true;
        }
        
        // Method 2: Check for selected labels
        const selectedLabels = questionItem.querySelectorAll('label.is-selected');
        if (selectedLabels.length > 0) {
            return true;
        }
        
        // Method 3: Check for filled text inputs
        const textInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput[type="text"]');
        for (let i = 0; i < textInputs.length; i++) {
            if (textInputs[i].value.trim() !== '') {
                return true;
            }
        }
        
        return false;
    }
})();
