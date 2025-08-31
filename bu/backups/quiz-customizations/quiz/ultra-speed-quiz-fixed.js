/**
 * LearnDash Quiz Force Hint Mode
 * 
 * This script overrides LearnDash's default answer feedback behavior to:
 * 1. HIDE correct/incorrect answer feedback
 * 2. FORCE users to view hints explaining the question
 * 3. REQUIRE correct answer before Next button enables
 */

// Global variables
let responseCache = {};
let isProcessing = false;

// Get settings from the page
function getSettings() {
    // Default settings
    let settings = {
        forceHintMode: false,
        requireCorrectForNext: false,
        autoShowHint: false,
        showHint: true
    };
    
    // Get quiz ID
    const quizId = getQuizId();
    if (!quizId) return settings;
    
    // Try to get settings from quizExtensionsSettings global
    if (window.quizExtensionsSettings && window.quizExtensionsSettings.quiz_options) {
        const quizSettings = window.quizExtensionsSettings.quiz_options[quizId] || {};
        
        // Update settings
        settings = {
            forceHintMode: quizSettings.force_hint_mode === '1',
            requireCorrectForNext: quizSettings.require_correct === '1',
            autoShowHint: quizSettings.auto_show_hint === '1',
            showHint: quizSettings.show_hint !== '0',
            quizId: quizId
        };
        
        console.log('Quiz settings loaded:', settings);
    }
    
    return settings;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only run on quiz pages
    if (!document.querySelector('.wpProQuiz_content')) {
        return;
    }
    
    const settings = getSettings();
    
    // Only proceed if any features are enabled
    if (settings.forceHintMode || settings.requireCorrectForNext) {
        setupQuizEnhancements();
    }
});

/**
 * Set up quiz enhancements
 */
function setupQuizEnhancements() {
    console.log('Setting up Force Hint Mode');
    
    // Add CSS for visual feedback
    addFeedbackStyles();
    
    // Intercept check button clicks
    interceptCheckButtons();
}

/**
 * Add CSS styles for visual feedback
 */
function addFeedbackStyles() {
    const styleEl = document.createElement('style');
    styleEl.textContent = `
        /* Force Hint Mode Styles */
        .selected-correct {
            border: 2px solid #060 !important;
            background-color: rgba(0, 102, 0, 0.1) !important;
        }
        
        .selected-incorrect {
            border: 2px solid #c00 !important;
            background-color: rgba(204, 0, 0, 0.1) !important;
        }
        
        .lilac-hint-message {
            margin: 10px 0;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f8f8f8;
            font-weight: bold;
        }
        
        /* Animation for hint button */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 120, 255, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(0, 120, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 120, 255, 0); }
        }
    `;
    document.head.appendChild(styleEl);
}

/**
 * Intercept all check button clicks
 */
function interceptCheckButtons() {
    document.addEventListener('click', function(e) {
        // Look for check buttons
        const button = e.target.closest('.wpProQuiz_QuestionButton[name="check"]');
        if (button) {
            // Let the original click happen first
            setTimeout(() => {
                // Apply our custom handling afterward
                handleCheckButtonClick(button);
            }, 100);
        }
    });
}

/**
 * Handle check button click to show hints instead of correct/incorrect feedback
 */
function handleCheckButtonClick(button) {
    // Get settings
    const settings = getSettings();
    
    // Only apply changes if Force Hint Mode is enabled
    if (!settings.forceHintMode) return;
    
    // Get the question container
    const questionItem = button.closest('.wpProQuiz_listItem');
    if (!questionItem) return;
    
    // Check if the answer is correct
    const isCorrect = isAnswerCorrect(questionItem);
    
    // Find selected answers
    const questionList = questionItem.querySelector('.wpProQuiz_questionList');
    const selectedInputs = questionList.querySelectorAll('.wpProQuiz_questionInput:checked');
    const selectedItems = [];
    
    selectedInputs.forEach(input => {
        const item = input.closest('li');
        if (item) selectedItems.push(item);
    });
    
    // Hide the standard LearnDash feedback
    const responseDiv = questionItem.querySelector('.wpProQuiz_response');
    const correctDiv = responseDiv?.querySelector('.wpProQuiz_correct');
    const incorrectDiv = responseDiv?.querySelector('.wpProQuiz_incorrect');
    
    if (correctDiv) correctDiv.style.display = 'none';
    if (incorrectDiv) incorrectDiv.style.display = 'none';
    
    // Style the selected answers
    selectedItems.forEach(item => {
        // Clear existing classes first
        item.classList.remove('selected-correct', 'selected-incorrect');
        
        // Add correct/incorrect styling
        if (isCorrect) {
            item.classList.add('selected-correct');
        } else {
            item.classList.add('selected-incorrect');
        }
    });
    
    // Show hint message
    let hintMessage = questionItem.querySelector('.lilac-hint-message');
    if (!hintMessage) {
        hintMessage = document.createElement('div');
        hintMessage.className = 'lilac-hint-message';
        
        // Find a place to insert the message
        const hintButton = questionItem.querySelector('.wpProQuiz_hint_button');
        if (hintButton) {
            hintButton.parentNode.insertBefore(hintMessage, hintButton);
        } else if (responseDiv) {
            responseDiv.appendChild(hintMessage);
        }
    }
    
    // Update message based on correctness
    if (isCorrect) {
        hintMessage.innerHTML = '<strong>תשובתך נכונה!</strong> ניתן לראות הסבר בלחיצה על כפתור הרמז.';
        hintMessage.style.color = '#060';
    } else {
        hintMessage.innerHTML = '<strong>תשובתך אינה נכונה.</strong> לחץ על כפתור הרמז כדי לקבל הסבר.';
        hintMessage.style.color = '#c00';
    }
    
    // Handle Next button behavior if answer is wrong
    if (!isCorrect && settings.requireCorrectForNext) {
        const nextButton = questionItem.querySelector('.wpProQuiz_nextButton');
        if (nextButton) {
            // Disable Next button
            nextButton.style.opacity = '0.5';
            nextButton.style.cursor = 'not-allowed';
            
            // Save original text if not already saved
            if (!nextButton.dataset.originalText) {
                nextButton.dataset.originalText = nextButton.textContent;
            }
            
            // Change text
            nextButton.textContent = 'יש לבחור תשובה נכונה כדי להמשיך';
            
            // Add click handler to prevent navigation
            nextButton.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                alert('יש לבחור את התשובה הנכונה כדי להמשיך לשאלה הבאה');
                return false;
            };
        }
    }
    
    // Enhance hint button
    const hintButton = questionItem.querySelector('.wpProQuiz_hint_button');
    if (hintButton) {
        // Make hint button more visible
        hintButton.style.animation = 'pulse 1.5s infinite';
        hintButton.style.boxShadow = '0 0 0 2px rgba(0,120,255,0.5)';
        hintButton.style.borderRadius = '3px';
        
        // Auto-show hint if enabled
        if (settings.autoShowHint) {
            setTimeout(() => {
                try {
                    hintButton.click();
                } catch(e) {
                    console.error('Error auto-showing hint:', e);
                }
            }, 500);
        }
    }
}

/**
 * Determines if the selected answer is actually correct
 */
function isAnswerCorrect(questionItem) {
    try {
        // Check if LearnDash marked the answer as correct
        const correctElement = questionItem.querySelector('.wpProQuiz_correct');
        if (correctElement && window.getComputedStyle(correctElement).display !== 'none') {
            return true;
        }
        
        // Check if any selected answers have the correct class
        const selectedCorrect = questionItem.querySelector('li.wpProQuiz_answerCorrect');
        if (selectedCorrect) {
            return true;
        }
        
        // Check if there's an incorrect message shown
        const incorrectElement = questionItem.querySelector('.wpProQuiz_incorrect');
        if (incorrectElement && window.getComputedStyle(incorrectElement).display !== 'none') {
            return false;
        }
        
        // Fall back to checking the data
        const questionId = getQuestionId(questionItem);
        const quizId = getQuizId();
        
        if (questionId && quizId && window.wpProQuizInitQuestions) {
            const quizData = window.wpProQuizInitQuestions[quizId];
            if (quizData && quizData.json && quizData.json.questions) {
                const questionData = quizData.json.questions.find(q => 
                    q.question_id == questionId || q.id == questionId);
                
                if (questionData && questionData.correct) {
                    // Get correct answers
                    const correctPositions = questionData.correct;
                    
                    // Get selected answers
                    const questionList = questionItem.querySelector('.wpProQuiz_questionList');
                    const questionType = questionList.getAttribute('data-type') || '';
                    const selectedInputs = questionItem.querySelectorAll('.wpProQuiz_questionInput:checked');
                    
                    if (questionType === 'single') {
                        // Single choice
                        const selectedLi = selectedInputs[0]?.closest('li');
                        if (selectedLi) {
                            const pos = parseInt(selectedLi.getAttribute('data-pos') || '0', 10);
                            return correctPositions.indexOf(pos) !== -1;
                        }
                    } else if (questionType === 'multiple') {
                        // Multiple choice - must select all correct answers and no incorrect ones
                        const selectedPositions = Array.from(selectedInputs).map(input => {
                            const li = input.closest('li');
                            return li ? parseInt(li.getAttribute('data-pos') || '0', 10) : -1;
                        }).filter(pos => pos !== -1);
                        
                        // Check if the selected positions match the correct ones
                        if (selectedPositions.length !== correctPositions.length) {
                            return false;
                        }
                        
                        // Check if all selected positions are in correct positions
                        return selectedPositions.every(pos => correctPositions.indexOf(pos) !== -1);
                    }
                }
            }
        }
        
        // Default to false if we can't determine
        return false;
    } catch(e) {
        console.error('Error checking answer correctness:', e);
        return false;
    }
}

/**
 * Get the question ID from a question item
 */
function getQuestionId(questionItem) {
    if (!questionItem) return null;
    
    // First try data attribute
    const dataId = questionItem.getAttribute('data-question-id');
    if (dataId) return dataId;
    
    // Next try extracting from ID
    const idMatch = questionItem.id?.match(/wpProQuiz_\\d+_(\\d+)/);
    if (idMatch && idMatch[1]) return idMatch[1];
    
    // Finally try class name
    const classMatch = questionItem.className.match(/wpProQuiz_listItem_(\\d+)/);
    if (classMatch && classMatch[1]) return classMatch[1];
    
    return null;
}

/**
 * Get the quiz ID from the page
 */
function getQuizId() {
    // Try from URL first
    const urlMatch = window.location.href.match(/quiz_id=(\\d+)/);
    if (urlMatch && urlMatch[1]) return urlMatch[1];
    
    // Try quiz form
    const quizForm = document.querySelector('.wpProQuiz_quiz');
    if (quizForm) {
        const formMatch = quizForm.id?.match(/wpProQuiz_(\\d+)/);
        if (formMatch && formMatch[1]) return formMatch[1];
    }
    
    // Try quiz container
    const quizContent = document.querySelector('.wpProQuiz_content');
    if (quizContent) {
        const contentMatch = quizContent.id?.match(/wpProQuiz_(\\d+)/);
        if (contentMatch && contentMatch[1]) return contentMatch[1];
    }
    
    return null;
}
