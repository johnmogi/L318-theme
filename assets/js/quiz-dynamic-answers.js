/**
 * Quiz Dynamic Answers
 * 
 * Retrieves correct answers from the server and integrates with
 * the answer reselection functionality.
 */
(function($) {
    'use strict';
    
    // Debug logging
    const DEBUG = true;
    const log = (...args) => DEBUG && console.log('[QUIZ ANSWERS]', ...args);
    
    // Store the correct answers
    let correctAnswers = {};
    
    /**
     * Initialize the quiz answer integration
     */
    function initQuizAnswers() {
        log('Initializing Quiz Answer Integration');
        
        // Load correct answers from server data
        if (window.lilacQuizData && window.lilacQuizData.correctAnswers) {
            correctAnswers = window.lilacQuizData.correctAnswers;
            log('Correct Answers Loaded:', correctAnswers);
        } else {
            log('No correct answers found in lilacQuizData');
        }
        
        // Set up event listeners
        setupEventListeners();
    }
    
    /**
     * Set up event listeners for question navigation and answer validation
     */
    function setupEventListeners() {
        // Handle check button clicks
        $(document).on('click', '.wpProQuiz_button[name="check"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // Let LearnDash process first, then validate
            setTimeout(() => validateQuestion($question), 300);
        });
        
        // Handle question changes
        $(document).on('learndash-quiz-question-loaded', function() {
            log('Question changed, preparing validator');
            setTimeout(prepareQuestion, 200);
        });
        
        // Initial preparation for all questions
        $('.wpProQuiz_listItem').each(function() {
            prepareQuestion($(this));
        });
    }
    
    /**
     * Prepare a question for validation
     */
    function prepareQuestion($question) {
        if (!$question) {
            $question = $('.wpProQuiz_listItem:visible');
        }
        
        if (!$question.length) return;
        
        // Get question ID using various methods
        const questionId = getQuestionId($question);
        if (!questionId) {
            log('Could not determine question ID');
            return;
        }
        
        log('Preparing question ID:', questionId);
        
        // Store the ID on the question element
        $question.attr('data-lilac-question-id', questionId);
    }
    
    /**
     * Validate a question's answer against known correct answers
     */
    function validateQuestion($question) {
        if (!$question || !$question.length) return;
        
        // Get the question ID
        const questionId = getQuestionId($question);
        if (!questionId) {
            log('Could not determine question ID for validation');
            return;
        }
        
        // Get the correct answer
        const correctAnswer = correctAnswers[questionId];
        if (!correctAnswer) {
            log('No correct answer found for question ID:', questionId);
            return;
        }
        
        log('Validating answer for question ID:', questionId, 'Correct answer:', correctAnswer);
        
        // Get the selected answer(s)
        const selectedAnswer = getSelectedAnswer($question);
        log('Selected answer:', selectedAnswer);
        
        // Compare with correct answer
        const isCorrect = compareAnswers(selectedAnswer, correctAnswer);
        log('Answer is correct:', isCorrect);
        
        // Handle result
        if (isCorrect) {
            showNextButton($question);
        } else {
            // Ensure the next button stays hidden
            hideNextButton($question);
            
            // Show hint
            highlightHint($question);
        }
    }
    
    /**
     * Get the selected answer(s) for a question
     */
    function getSelectedAnswer($question) {
        const selected = [];
        
        // Check radio buttons (single choice)
        $question.find('input[type="radio"]:checked').each(function(index) {
            selected.push(index + 1); // 1-based index
        });
        
        // Check checkboxes (multiple choice)
        $question.find('input[type="checkbox"]:checked').each(function(index) {
            selected.push(index + 1); // 1-based index
        });
        
        return selected.join(',');
    }
    
    /**
     * Compare selected answer with correct answer
     */
    function compareAnswers(selected, correct) {
        // Handle empty values
        if (!selected || !correct) return false;
        
        // Convert to arrays if they're strings
        const selectedArr = selected.toString().split(',').map(i => parseInt(i, 10)).sort();
        const correctArr = correct.toString().split(',').map(i => parseInt(i, 10)).sort();
        
        // Compare arrays
        if (selectedArr.length !== correctArr.length) return false;
        
        for (let i = 0; i < selectedArr.length; i++) {
            if (selectedArr[i] !== correctArr[i]) return false;
        }
        
        return true;
    }
    
    /**
     * Show the next button for a question
     */
    function showNextButton($question) {
        $question.find('.wpProQuiz_QuestionButton').show();
    }
    
    /**
     * Hide the next button for a question
     */
    function hideNextButton($question) {
        $question.find('.wpProQuiz_QuestionButton').hide();
    }
    
    /**
     * Highlight the hint button
     */
    function highlightHint($question) {
        const $hintButton = $question.find('.wpProQuiz_TipButton');
        
        if ($hintButton.length) {
            // Add highlight class
            $hintButton.addClass('lilac-hint-highlight');
            
            // Add tooltip if not already present
            if ($hintButton.find('.lilac-hint-tooltip').length === 0) {
                $hintButton.append(
                    '<span class="lilac-hint-tooltip">Click to view hint</span>'
                );
            }
        }
    }
    
    /**
     * Get the question ID using multiple methods
     */
    function getQuestionId($question) {
        // Try method 1: From question-meta data attribute
        const questionMeta = $question.data('question-meta');
        if (questionMeta && questionMeta.question_post_id) {
            return questionMeta.question_post_id;
        }
        
        // Try method 2: From our saved attribute
        const savedId = $question.attr('data-lilac-question-id');
        if (savedId) {
            return savedId;
        }
        
        // Try method 3: From the question list
        const $questionList = $question.find('.wpProQuiz_questionList');
        if ($questionList.length && $questionList.data('question-id')) {
            return $questionList.data('question-id');
        }
        
        // Try method 4: From index in the quiz
        if (window.quizQuestionData) {
            const index = $question.index('.wpProQuiz_listItem');
            if (index >= 0 && window.quizQuestionData[index]) {
                return window.quizQuestionData[index];
            }
        }
        
        return null;
    }
    
    // Initialize on document ready
    $(document).ready(initQuizAnswers);
    
})(jQuery);
