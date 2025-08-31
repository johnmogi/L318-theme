/**
 * Quiz Answer Logger v8.0
 * Minimal logging with clear highlighting of correct answers
 */

(function() {
    // Single initialization message
    console.log('📋 Quiz Answer Logger v8.0 initialized');
    
    // State tracking
    const hintViewed = {};
    const answersFound = {};
    let scanRunning = false;
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('wpProQuiz_initComplete', init);
    setTimeout(init, 1000);
    
    /**
     * Main initialization
     */
    function init() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        if (questionItems.length === 0) return;
        
        console.log(`📝 Found ${questionItems.length} quiz questions`);
        
        // Set up event listeners
        setupEventListeners(questionItems);
        
        // Log all answers once on initialization
        logCorrectAnswersOnly(questionItems);
        
        // Add Show Answers button
        addShowAnswersButton();
        
        // Setup answer checker
        setupAnswerChecker(questionItems);
    }
    
    /**
     * Set up event listeners
     */
    function setupEventListeners(questionItems) {
        // Listen for answer selection
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('wpProQuiz_questionInput')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        checkForCorrectAnswer(questionItem);
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
                    }
                }
            }
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(e) {
            if (e.target.name === 'check' && e.target.classList.contains('wpProQuiz_button')) {
                const questionItem = e.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    setTimeout(() => {
                        checkForCorrectAnswer(questionItem);
                    }, 500);
                }
            }
        });
    }
    
    /**
     * Setup periodic answer checker that won't flood the console
     */
    function setupAnswerChecker(questionItems) {
        // Only check for answers every 5 seconds to reduce console spam
        setInterval(() => {
            if (!scanRunning) {
                scanRunning = true;
                checkForNewAnswers(questionItems);
                setTimeout(() => {
                    scanRunning = false;
                }, 1000);
            }
        }, 5000);
    }
    
    /**
     * Check for any new correct answers
     */
    function checkForNewAnswers(questionItems) {
        let foundNewAnswer = false;
        
        questionItems.forEach(questionItem => {
            const wasCorrect = checkForCorrectAnswer(questionItem);
            if (wasCorrect) {
                foundNewAnswer = true;
            }
        });
        
        if (foundNewAnswer) {
            // Log a summary of all answers found so far
            logAnswerSummary();
        }
    }
    
    /**
     * Check if this question has a correct answer and log it
     */
    function checkForCorrectAnswer(questionItem) {
        const questionId = getQuestionId(questionItem);
        if (!questionId) return false;
        
        // Skip if we already found the answer for this question
        if (answersFound[questionId]) return false;
        
        // Check if answer is correct
        const isCorrect = isAnswerCorrect(questionItem);
        if (!isCorrect) return false;
        
        // Get answer details
        const selectedAnswers = getSelectedAnswers(questionItem);
        if (selectedAnswers.length === 0) return false;
        
        // Found a new correct answer!
        const questionInfo = {
            id: questionId,
            text: getQuestionText(questionItem),
            answers: selectedAnswers
        };
        
        // Store the answer
        answersFound[questionId] = questionInfo;
        
        // Log the answer prominently
        console.log(
            '%c ✅ CORRECT ANSWER FOUND!', 
            'background: #4CAF50; color: white; padding: 8px; border-radius: 4px; font-size: 14px; font-weight: bold;'
        );
        console.log(
            '%c Question:', 
            'color: #2196F3; font-weight: bold;', 
            questionInfo.text
        );
        console.log(
            '%c Answer:', 
            'color: #4CAF50; font-weight: bold;', 
            selectedAnswers.map(a => a.text || a.value).join(', ')
        );
        console.log('----------------------------');
        
        return true;
    }
    
    /**
     * Log a summary of all answers found so far
     */
    function logAnswerSummary() {
        const answerCount = Object.keys(answersFound).length;
        if (answerCount === 0) return;
        
        console.log(
            '%c 📋 ANSWER SUMMARY (Found ' + answerCount + ' answers):', 
            'background: #673AB7; color: white; padding: 5px; border-radius: 3px; font-weight: bold;'
        );
        
        Object.values(answersFound).forEach((question, index) => {
            console.log(
                `%c Q${index+1}: ${question.text}`, 
                'color: #2196F3; font-weight: bold;'
            );
            console.log(
                '%c A: ' + question.answers.map(a => a.text || a.value).join(', '),
                'color: #4CAF50; font-weight: bold;'
            );
        });
    }
    
    /**
     * Log all possible correct answers but only once
     */
    function logCorrectAnswersOnly(questionItems) {
        console.log('%c 🔍 CHECKING ALL ANSWERS:', 'background: #FF9800; color: white; padding: 4px; font-weight: bold;');
        
        questionItems.forEach((question, index) => {
            const questionId = getQuestionId(question);
            const questionText = getQuestionText(question);
            
            console.log(
                `%c Question ${index + 1}: ${questionText.substring(0, 100)}${questionText.length > 100 ? '...' : ''}`, 
                'color: #2196F3; font-weight: bold;'
            );
            
            // Show all possible answers
            const allAnswers = getAllAnswers(question);
            
            // Sort answers by index
            allAnswers.sort((a, b) => a.index - b.index);
            
            // Log each answer
            allAnswers.forEach(answer => {
                console.log(
                    `${answer.index}. ${answer.text.substring(0, 100)}`,
                );
            });
            
            console.log('----------------------------');
        });
    }
    
    /**
     * Get text of a question
     */
    function getQuestionText(questionItem) {
        const questionTextElement = questionItem.querySelector('.wpProQuiz_question_text');
        return questionTextElement ? questionTextElement.textContent.trim() : 'Question text not found';
    }
    
    /**
     * Get all answers for a question
     */
    function getAllAnswers(questionItem) {
        const answers = [];
        
        const answerElements = questionItem.querySelectorAll('.wpProQuiz_questionListItem');
        
        answerElements.forEach((answer, index) => {
            const input = answer.querySelector('.wpProQuiz_questionInput');
            let answerText = answer.textContent.trim();
            
            // Clean up answer text
            answerText = answerText.replace(/\s+/g, ' ').trim();
            
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
     * Get selected answers
     */
    function getSelectedAnswers(questionItem) {
        const selectedAnswers = [];
        
        // Check for radio buttons and checkboxes
        const checkedInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
        checkedInputs.forEach((input, index) => {
            const listItem = input.closest('.wpProQuiz_questionListItem');
            if (listItem) {
                let text = listItem.textContent.trim().replace(/\s+/g, ' ');
                selectedAnswers.push({
                    index: index + 1,
                    value: input.value,
                    text: text,
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
        
        // Method 2: Check for filled text inputs
        const textInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput[type="text"]');
        for (let i = 0; i < textInputs.length; i++) {
            if (textInputs[i].value.trim() !== '') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get question ID
     */
    function getQuestionId(questionItem) {
        if (!questionItem) return null;
        
        // Try data attribute first
        if (questionItem.dataset.questionId) {
            return questionItem.dataset.questionId;
        }
        
        // Try from question meta
        if (questionItem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(questionItem.dataset.questionMeta);
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
            } catch (e) {
                // Ignore JSON errors
            }
        }
        
        // Try question list
        const questionList = questionItem.querySelector('.wpProQuiz_questionList');
        if (questionList && questionList.dataset.questionId) {
            return questionList.dataset.questionId;
        }
        
        // Use index as fallback
        const allQuestions = Array.from(document.querySelectorAll('.wpProQuiz_listItem'));
        const index = allQuestions.indexOf(questionItem);
        if (index !== -1) {
            return index + 1;
        }
        
        return null;
    }
    
    /**
     * Update Next button state
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
        
        // Decision logic
        let enableNext = false;
        
        if (isCorrect) {
            // Always enable for correct answers
            enableNext = true;
        } else if (hasViewedHint && hasSelection) {
            // Force hint mode bypass
            enableNext = true;
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
     * Add button to show all found answers
     */
    function addShowAnswersButton() {
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
            logAnswerSummary();
        });
        
        document.body.appendChild(button);
    }
})();
