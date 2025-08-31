/**
 * LearnDash Quiz Sidebar Interaction
 * 
 * Handles the interaction between the quiz questions and the sidebar
 */
(function($) {
    'use strict';
    
    // Track the active question
    let activeQuestionId = null;
    
    /**
     * Initialize Quiz Sidebar
     */
    function initQuizSidebar() {
        // Check if we're on a quiz page with sidebar
        if (!$('.lilac-quiz-sidebar').length) {
            return;
        }
        
        console.log('Quiz Sidebar Active');
        
        // Hide all sidebar items initially
        $('.lilac-quiz-sidebar-item').hide();
        
        // Initialize sidebar when LearnDash quiz is ready
        watchForQuizReady();
        
        // Listen for question change events
        setupQuestionChangeListeners();
    }
    
    /**
     * Watch for LearnDash quiz to be fully loaded
     */
    function watchForQuizReady() {
        // Check if quiz is already loaded
        if ($('.wpProQuiz_list li.wpProQuiz_listItem:visible').length) {
            handleQuizReady();
            return;
        }
        
        // Poll for quiz to be ready
        const checkInterval = setInterval(function() {
            if ($('.wpProQuiz_list li.wpProQuiz_listItem:visible').length) {
                clearInterval(checkInterval);
                handleQuizReady();
            }
        }, 500);
    }
    
    /**
     * Handle quiz ready event
     */
    function handleQuizReady() {
        // Show the first question's sidebar content
        const firstQuestion = $('.wpProQuiz_list li.wpProQuiz_listItem:visible');
        if (firstQuestion.length) {
            const questionId = getQuestionId(firstQuestion);
            showSidebarForQuestion(questionId);
        }
    }
    
    /**
     * Set up listeners for question changes
     */
    function setupQuestionChangeListeners() {
        // Listen for next/back button clicks
        $(document).on('click', '.wpProQuiz_button[name="next"], .wpProQuiz_button[name="back"]', function() {
            // Allow time for LearnDash to update the display
            setTimeout(updateSidebarForVisibleQuestion, 300);
        });
        
        // Also watch for visibility changes of questions
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style' && 
                    $(mutation.target).hasClass('wpProQuiz_listItem')) {
                    updateSidebarForVisibleQuestion();
                }
            });
        });
        
        $('.wpProQuiz_listItem').each(function() {
            observer.observe(this, { attributes: true });
        });
    }
    
    /**
     * Update sidebar for the currently visible question
     */
    function updateSidebarForVisibleQuestion() {
        const visibleQuestion = $('.wpProQuiz_list li.wpProQuiz_listItem:visible');
        if (visibleQuestion.length) {
            const questionId = getQuestionId(visibleQuestion);
            showSidebarForQuestion(questionId);
        }
    }
    
    /**
     * Get question ID from a quiz item
     * 
     * @param {jQuery} $question The question element
     * @return {string} The question ID
     */
    function getQuestionId($question) {
        // Try to get from data attribute
        const meta = $question.data('question-meta');
        if (meta && meta.question_post_id) {
            return meta.question_post_id.toString();
        }
        
        // Fallback to data post id
        const postId = $question.data('post-id');
        if (postId) {
            return postId.toString();
        }
        
        return '';
    }
    
    /**
     * Show sidebar content for a specific question
     * 
     * @param {string} questionId The question ID to show
     */
    function showSidebarForQuestion(questionId) {
        if (!questionId || questionId === activeQuestionId) {
            return;
        }
        
        // Update active question tracking
        activeQuestionId = questionId;
        
        // Hide all items first
        $('.lilac-quiz-sidebar-item').removeClass('active').hide();
        
        // Show the matching item
        const $sidebarItem = $('.lilac-quiz-sidebar-item[data-question-id="' + questionId + '"]');
        
        if ($sidebarItem.length) {
            $sidebarItem.addClass('active').show();
            $('.lilac-quiz-sidebar').addClass('has-content');
        } else {
            $('.lilac-quiz-sidebar').removeClass('has-content');
        }
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        initQuizSidebar();
    });
    
})(jQuery);
