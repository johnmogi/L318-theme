/**
 * Quiz Next Button Control
 * Handles quiz answer validation and next button functionality
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('Quiz Next Button Control loaded');
        initQuizNextButton();
    });

    /**
     * Initialize quiz next button functionality
     */
    function initQuizNextButton() {
        console.log('Initializing quiz next button control');
        
        // Hide next buttons initially on all questions
        hideAllNextButtons();
        
        // Setup answer handlers
        setupAnswerHandlers();
        
        // Initialize hint enforcement if enabled
        if ($('body').hasClass('quiz-hint-enforced')) {
            initHintEnforcement();
        }
        
        console.log('Quiz next button initialization complete');
    }
    
    /**
     * Hide all next buttons initially
     */
    function hideAllNextButtons() {
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            // Only hide if the question is active and not completed
            if (!$question.hasClass('wpProQuiz_answerCorrect')) {
                hideNextButton($question);
            }
        });
        console.log('All next buttons hidden initially');
    }
    
    /**
     * Set up answer selection handlers
     */
    function setupAnswerHandlers() {
        // Handle radio button selection (single choice questions)
        $(document).on('click', '.wpProQuiz_questionListItem input[type="radio"]', function() {
            const $questionItem = $(this).closest('.wpProQuiz_listItem');
            handleAnswerSelection($questionItem);
        });
        
        // Handle checkbox selection (multiple choice questions)
        $(document).on('change', '.wpProQuiz_questionListItem input[type="checkbox"]', function() {
            const $questionItem = $(this).closest('.wpProQuiz_listItem');
            handleAnswerSelection($questionItem);
        });
        
        // Handle check button click
        $(document).on('click', '.wpProQuiz_QuestionButton[name="check"]', function() {
            const $questionItem = $(this).closest('.wpProQuiz_listItem');
            validateAnswer($questionItem);
        });
        
        console.log('Answer handlers set up');
    }
    
    /**
     * Handle answer selection
     */
    function handleAnswerSelection($questionItem) {
        console.log('Answer selected');
        
        // Add visual feedback for selected items
        $questionItem.find('.wpProQuiz_questionListItem')
            .removeClass('is-selected')
            .has('input:checked')
            .addClass('is-selected');
        
        // Check if this question already has an answer validated
        if (!$questionItem.hasClass('answer-validated')) {
            // For automatic validation on selection
            validateAnswer($questionItem);
        }
    }
    
    /**
     * Validate the selected answer
     */
    function validateAnswer($questionItem) {
        console.log('Validating answer');
        
        // Mark that we've attempted validation on this question
        $questionItem.addClass('answer-validated');
        
        // Check if the question is locked (already answered correctly)
        if ($questionItem.hasClass('lilac-locked')) {
            console.log('Question is locked, skipping validation');
            return;
        }
        
        const $selectedInput = $questionItem.find('input:checked');
        if ($selectedInput.length === 0) {
            console.log('No answer selected');
            return; // No answer selected
        }
        
        // First check if the question already shows answer feedback
        const $selectedListItem = $selectedInput.closest('.wpProQuiz_questionListItem');
        
        // Check if the selected answer is correct
        const isCorrect = $selectedListItem.hasClass('wpProQuiz_answerCorrect') || 
                          $selectedListItem.find('.ld-quiz-question-item__status--correct:visible').length > 0;
        
        if (isCorrect) {
            console.log('Answer is correct!');
            handleCorrectAnswer($questionItem);
        } else {
            console.log('Answer is incorrect!');
            handleWrongAnswer($questionItem);
        }
    }
    
    /**
     * Handle correct answer selection
     */
    function handleCorrectAnswer($questionItem) {
        // Mark question as correctly answered
        $questionItem.addClass('answered-correctly');
        
        // Show the next button
        showNextButton($questionItem);
    }
    
    /**
     * Handle incorrect answer selection
     */
    function handleWrongAnswer($questionItem) {
        // Mark question as incorrectly answered
        $questionItem.addClass('answered-incorrectly')
                     .removeClass('answered-correctly');
        
        // Don't disable inputs - allow the user to change their answer
        $questionItem.find('input').prop('disabled', false);
        
        // Keep next button hidden
        hideNextButton($questionItem);
        
        // If hint enforcement is active, trigger hint display
        if ($('body').hasClass('quiz-hint-enforced')) {
            // If we have a hint message container, show it
            if ($questionItem.find('.lilac-hint-message').length) {
                $questionItem.find('.lilac-hint-message').show();
            } else {
                // Show hint button if available
                $questionItem.find('.wpProQuiz_TipButton').show().css({
                    'visibility': 'visible',
                    'opacity': 1,
                    'pointer-events': 'auto'
                });
            }
        }
    }
    
    /**
     * Show the next button for a question
     */
    function showNextButton($question) {
        const $nextButton = $question.find('.wpProQuiz_button[name="next"]');
        
        if ($nextButton.length) {
            // Make sure it's visible and enabled
            $nextButton.css({
                'visibility': 'visible',
                'opacity': 1,
                'pointer-events': 'auto'
            }).prop('disabled', false);
            
            console.log('Next button shown');
        }
    }
    
    /**
     * Hide the next button for a question
     */
    function hideNextButton($question) {
        const $nextButton = $question.find('.wpProQuiz_button[name="next"]');
        
        if ($nextButton.length) {
            // Hide the button but keep its space in layout
            $nextButton.css({
                'visibility': 'hidden',
                'opacity': 0,
                'pointer-events': 'none'
            }).prop('disabled', true);
            
            console.log('Next button hidden');
        }
    }
    
    /**
     * Initialize hint enforcement
     */
    function initHintEnforcement() {
        console.log('Initializing hint enforcement');
        
        // Handle hint button click
        $(document).on('click', '.wpProQuiz_TipButton, .lilac-force-hint', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            showHint($question);
        });
    }
    
    /**
     * Show hint for a question
     */
    function showHint($question) {
        // Mark question as having seen the hint
        $question.addClass('hint-viewed');
        
        // Show the hint content
        $question.find('.wpProQuiz_tipp').show();
        
        // Update sidebar if available
        updateHintSidebar($question);
        
        console.log('Hint shown for question');
    }
    
    /**
     * Update hint sidebar with current question's hint
     */
    function updateHintSidebar($question) {
        // Find the hint content
        const $hintContent = $question.find('.wpProQuiz_tipp');
        
        if ($hintContent.length && $('.quiz-sidebar').length) {
            // Clone the hint content to the sidebar
            const hintHTML = $hintContent.html();
            $('.quiz-sidebar .hint-content').html(hintHTML);
            $('.quiz-sidebar').addClass('has-hint');
            
            console.log('Hint sidebar updated');
        }
    }
    
    // Make functions available globally
    window.LilacQuizNextButton = {
        init: initQuizNextButton,
        validateAnswer: validateAnswer,
        showNextButton: showNextButton,
        hideNextButton: hideNextButton
    };
    
})(jQuery);
