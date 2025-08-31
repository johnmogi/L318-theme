/**
 * Quiz Interaction Script
 * Handles quiz interactions and hint enforcement
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('Quiz script loaded');
        
        // Initialize quiz functionality
        initQuiz();
        
        // Initialize hint enforcement if needed
        if ($('body').hasClass('quiz-hint-enforced')) {
            initHintEnforcement();
        }
        
        // Handle responsive behavior
        handleResponsiveLayout();
        $(window).on('resize', handleResponsiveLayout);
    });
    
    /**
     * Initialize quiz functionality
     */
    function initQuiz() {
        // Check if we have the quiz data
        if (typeof lilacQuizData === 'undefined') {
            console.warn('lilacQuizData is not defined');
            return;
        }
        
        console.log('Initializing quiz with ID:', lilacQuizData.quizId);
        
        // Initialize any quiz-specific functionality here
        setupAnswerHandlers();
        
        // Log the initialization
        console.log('Quiz initialization complete');
    }
    
    /**
     * Set up answer selection handlers
     */
    function setupAnswerHandlers() {
        // Delegate events to handle dynamic content
        $(document).on('click', '.wpProQuiz_questionListItem input[type="radio"], .wpProQuiz_questionListItem input[type="checkbox"]', function() {
            const $questionItem = $(this).closest('.wpProQuiz_listItem');
            handleAnswerSelection($questionItem);
        });
        
        console.log('Answer handlers set up');
    }
    
    /**
     * Handle answer selection
     */
    function handleAnswerSelection($questionItem) {
        // Log the answer selection
        console.log('Answer selected');
        
        // Add visual feedback
        $questionItem.find('.wpProQuiz_questionListItem')
            .removeClass('selected')
            .has('input:checked')
            .addClass('selected');
            
        // Additional handling can be added here
    }
    
    /**
     * Initialize hint enforcement
     */
    function initHintEnforcement() {
        console.log('Initializing hint enforcement');
        
        // Hide next buttons initially
        $('.wpProQuiz_controls .wpProQuiz_button').hide();
        
        // Show hint buttons
        $('.wpProQuiz_TipButton').show();
        
        // Handle hint button click
        $(document).on('click', '.wpProQuiz_TipButton', function() {
            const $button = $(this);
            const $question = $button.closest('.wpProQuiz_listItem');
            
            // Mark question as having viewed hint
            $question.addClass('hint-viewed');
            
            // Show next button if answer is selected
            if ($question.find('input:checked').length > 0) {
                showNextButton($question);
            }
            
            console.log('Hint viewed for question');
        });
        
        // Handle answer selection with hint enforcement
        $(document).on('change', '.wpProQuiz_questionListItem input', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // If hint has been viewed, show next button
            if ($question.hasClass('hint-viewed')) {
                showNextButton($question);
            }
        });
    }
    
    /**
     * Show next button for a question
     */
    function showNextButton($question) {
        $question.find('.wpProQuiz_button.wpProQuiz_NextButton')
            .show()
            .addClass('enabled')
            .prop('disabled', false);
    }
    
    /**
     * Handle responsive layout
     */
    function handleResponsiveLayout() {
        // Add any responsive behavior here
        if ($(window).width() < 992) {
            // Mobile/tablet layout
            $('body').addClass('is-mobile');
        } else {
            // Desktop layout
            $('body').removeClass('is-mobile');
        }
    }
    
    // Make functions available globally if needed
    window.LilacQuiz = {
        init: initQuiz,
        handleAnswerSelection: handleAnswerSelection,
        showNextButton: showNextButton
    };
    
})(jQuery);
