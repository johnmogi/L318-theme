/**
 * Strict Next Button Controller v7.0
 * 
 * This script ensures proper answer logging and Next button control:
 * - Logs all correct answers for reference
 * - Shows detailed question and answer information
 * - Enables Next button based on correct answers
 */

(function() {
    // Single initialization message
    console.log('📝 Quiz Answer Logger v7.0 initialized');
    
    // State tracking
    const hintViewed = {};
    const correctAnswersFound = {};
    
    // Initialize when DOM is ready or when LearnDash loads
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('wpProQuiz_initComplete', init);
    setTimeout(init, 1000); // Backup initialization
    
    /**
     * Main initialization function
     */
    function init() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        if (questionItems.length === 0) return;
        
        console.log(`Found ${questionItems.length} quiz questions`);
        
        // Log quiz information
        logQuizInfo();
        
        // Set up event listeners
        setupEventListeners(questionItems);
        
        // Initial scan of all answers
        scanAllAnswers(questionItems);
        
        // Update all Next buttons
        updateAllNextButtons(questionItems);
        
        // Set up periodic checker
        setInterval(() => {
            scanAllAnswers(questionItems);
            updateAllNextButtons(questionItems);
        }, 2000);
    }
    
    /**
     * Log basic quiz information
     */
    function logQuizInfo() {
        const quizContainer = document.querySelector('.wpProQuiz_quiz');
        const quizInfo = {
            id: quizContainer ? quizContainer.dataset.quizId : 'unknown',
            title: document.title,
            questionCount: document.querySelectorAll('.wpProQuiz_listItem').length,
            date: new Date().toLocaleString()
        };
        
        console.log('%c 📊 QUIZ INFO:', 'background: #4CAF50; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;', quizInfo);
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners(questionItems) {
        // Listen for answer selection
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('wpProQuiz_questionInput')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        scanAnswer(questionItem);
                        updateNextButton(questionItem);
                    }, 200);
                }
            }
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if (e.target.name === 'check' && e.target.classList.contains('wpProQuiz_button')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        scanAnswer(questionItem);
                        updateNextButton(questionItem);
                    }, 500);
                }
            }
        });
        
        // Listen for hint button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('wpProQuiz_TipButton')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    const questionId = getQuestionId(questionItem);
                    if (questionId) {
                        hintViewed[questionId] = true;
                        setTimeout(() => updateNextButton(questionItem), 100);
                    }
                }
            }
        });
    }
    
    /**
     * Scan all answers in the quiz
     */
    function scanAllAnswers(questionItems) {
        console.log('%c 🔍 SCANNING ALL ANSWERS:', 'background: #2196F3; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;');
        
        questionItems.forEach((question, index) => {
            scanAnswer(question);
        });
    }
    
    /**
     * Scan a single question for answers
     */
    function scanAnswer(questionItem) {
        const questionId = getQuestionId(questionItem);
        const questionIndex = Array.from(document.querySelectorAll('.wpProQuiz_listItem')).indexOf(questionItem);
        const questionText = getQuestionText(questionItem);
        
        // Check if this answer is marked as correct
        const isCorrect = isAnswerCorrect(questionItem);
        
        // Find all answers for this question
        const allAnswers = getAllAnswers(questionItem);
        const selectedAnswers = getSelectedAnswers(questionItem);
        
        // Update the correctAnswersFound object
        if (isCorrect && selectedAnswers.length > 0) {
            correctAnswersFound[questionId] = selectedAnswers;
        }
        
        // Log question information
        console.log(
            `%c Question ${questionIndex + 1}: ${questionId}`, 
            'font-weight: bold; color: #2196F3;',
            `\n${questionText.substring(0, 100)}${questionText.length > 100 ? '...' : ''}`
        );
        
        // Log answer information
        if (isCorrect) {
            console.log(
                '%c ✅ CORRECT ANSWER FOUND:', 
                'background: green; color: white; padding: 2px 5px; border-radius: 3px;',
                selectedAnswers
            );
        } else if (selectedAnswers.length > 0) {
            console.log(
                '%c Selected Answers (not marked correct yet):', 
                'color: orange;',
                selectedAnswers
            );
        }
        
        if (allAnswers.length > 0) {
            console.log(
                '%c All Possible Answers:', 
                'color: gray;',
                allAnswers
            );
        }
        
        console.log('----------------------------');
    }
    
    /**
     * Get the text of a question
     */
    function getQuestionText(questionItem) {
        const questionTextElement = questionItem.querySelector('.wpProQuiz_question_text');
        return questionTextElement ? questionTextElement.textContent.trim() : 'Question text not found';
    }
    
    /**
     * Get all answers for a question (correct or not)
     */
    function getAllAnswers(questionItem) {
        const answers = [];
        
        // Get all answer elements
        const answerElements = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        
        answerElements.forEach((answer, index) => {
            // For multiple choice/single choice questions
            const input = answer.querySelector('.wpProQuiz_questionInput');
            const answerText = answer.textContent.trim();
            
            let data = {
                index: index + 1,
                text: answerText,
                type: input ? input.type : 'unknown',
                isCorrect: answer.classList.contains('wpProQuiz_answerCorrect')
            };
            
            if (input && input.value) {
                data.value = input.value;
            }
            
            answers.push(data);
        });
        
        return answers;
    }
    
    /**
     * Get selected answers for a question
     */
    function getSelectedAnswers(questionItem) {
        const selectedAnswers = [];
        
        // Check for radio buttons and checkboxes
        const checkedInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        checkedInputs.forEach((input, index) => {
            const listItem = input.closest('.wpProQuiz_questionListItem');
            if (listItem) {
                selectedAnswers.push({
                    index: index + 1,
                    value: input.value,
                    text: listItem.textContent.trim(),
                    type: input.type
                });
            }
        });
        
        // Check for text inputs
        const textInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput[type="text"]');
        textInputs.forEach((input, index) => {
            if (input.value.trim() !== '') {
                selectedAnswers.push({
                    index: index + 1,
                    value: input.value,
                    type: 'text'
                });
            }
        });
        
        return selectedAnswers;
    }
    
    /**
     * Update all Next buttons
     */
    function updateAllNextButtons(questionItems) {
        questionItems.forEach(questionItem => {
            updateNextButton(questionItem);
        });
    }
    
    /**
     * Update a single Next button
     */
    function updateNextButton(questionItem) {
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
        }
        
        // Decision logic - ONLY enable for correct answers by default
        let enableNext = false;
        
        if (isCorrect) {
            // Always enable for correct answers
            enableNext = true;
            console.log(`Question ${questionId}: Next button enabled - answer is correct`);
        } else if (hasViewedHint && hasSelection) {
            // In force hint mode, enable Next if hint viewed and answer selected
            enableNext = true;
            console.log(`Question ${questionId}: Next button enabled - force hint mode bypass`);
        }
        
        // Apply the decision
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
     * Check if an answer is correct
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
    
    /**
     * Get the ID of a question
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
     * Add a debug button to show all answers found so far
     */
    setTimeout(() => {
        // Create show answers button
        const button = document.createElement('button');
        button.innerText = 'Show All Correct Answers';
        button.style.position = 'fixed';
        button.style.bottom = '10px';
        button.style.right = '10px';
        button.style.zIndex = '99999';
        button.style.padding = '10px';
        button.style.background = '#4CAF50';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '4px';
        button.style.cursor = 'pointer';
        button.style.fontSize = '14px';
        
        button.addEventListener('click', function() {
            console.log('%c 📋 ALL CORRECT ANSWERS FOUND:', 'background: #4CAF50; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;');
            console.log(correctAnswersFound);
        });
        
        document.body.appendChild(button);
    }, 2000);
})();
