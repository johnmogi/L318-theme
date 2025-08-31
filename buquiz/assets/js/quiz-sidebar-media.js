/**
 * Quiz Sidebar Media Handler
 * 
 * Dynamically updates the quiz sidebar content when navigating through questions.
 */
(function($) {
    'use strict';
    
    // Store media cache and question state
    const questionMedia = {};
    let currentQuestion = null;
    let previousQuestion = null;
    let isLoading = false;
    let lastLoadTime = 0;
    
    /**
     * Initialize the quiz sidebar media functionality
     */
    function initQuizSidebarMedia() {
        if (!$('.ld-quiz-sidebar').length) {
            return;
        }
        
        // Make sure the question-media container exists
        if (!$('#question-media').length) {
            $('.ld-quiz-sidebar-content').html('<div id="question-media"><div class="media-placeholder"><div class="media-icon"><i class="fas fa-image"></i></div><div class="media-message">Media will appear when you start the quiz</div></div></div>');
        }
        
        // Set up event listeners for question navigation
        setupEventListeners();
        
        // Load the first question's media with a slight delay to ensure LearnDash is ready
        setTimeout(loadInitialQuestion, 800);
    }

    /**
     * Setup event listeners for question navigation
     */
    function setupEventListeners() {
        // Listen for all possible navigation events
        
        // 1. Next/Previous buttons
        $(document).on('click', '.wpProQuiz_button[name="next"], .wpProQuiz_button[name="back"]', function() {
            setTimeout(checkCurrentQuestion, 300);
        });
        
        // 2. Review dots navigation
        $(document).on('click', '.wpProQuiz_reviewQuestion li', function() {
            var dotIndex = $(this).index();
            if (window.quizQuestionData && window.quizQuestionData[dotIndex]) {
                loadQuestionMedia(window.quizQuestionData[dotIndex]);
            } else {
                setTimeout(checkCurrentQuestion, 300);
            }
        });
        
        // 3. Quiz restart
        $(document).on('click', '.wpProQuiz_restart', function() {
            setTimeout(loadInitialQuestion, 1000);
        });
        
        // 4. Manual admin buttons (if present)
        $('.load-question-btn').on('click', function() {
            var questionId = $(this).data('question-id');
            if (questionId) {
                $('.load-question-btn').removeClass('active');
                $(this).addClass('active');
                loadQuestionMedia(questionId);
            }
        });
        
        // 5. Reduce interval frequency to prevent double loading
        setInterval(checkCurrentQuestion, 5000);
        
        // 6. Listen for LearnDash events
        $(document).on('learndash-question-navigation', handleQuestionNavigation);
    }

    /**
     * Load media for the initial question
     */
    function loadInitialQuestion() {
        // Try multiple methods to find the first question
        
        // Method 1: Active review dot
        var activeIndex = $('.wpProQuiz_reviewQuestionTarget').index();
        if (activeIndex >= 0 && window.quizQuestionData && window.quizQuestionData[activeIndex]) {
            loadQuestionMedia(window.quizQuestionData[activeIndex]);
            return;
        }
        
        // Method 2: Visible question
        var visibleQuestion = $('.wpProQuiz_listItem:visible');
        if (visibleQuestion.length) {
            var questionMeta = visibleQuestion.data('question-meta');
            if (questionMeta && questionMeta.question_post_id) {
                loadQuestionMedia(questionMeta.question_post_id);
                return;
            }
        }
        
        // Method 3: First question in the quiz
        if (window.quizQuestionData && window.quizQuestionData[0]) {
            loadQuestionMedia(window.quizQuestionData[0]);
            return;
        }
        
        // Method 4: Try other generic detection methods
        checkCurrentQuestion();
    }

    /**
     * Handle question navigation from LearnDash events
     */
    function handleQuestionNavigation(event, data) {
        if (data && data.questionNumber) {
            var questionId = data.questionId || getActiveQuestionId();
            if (questionId) {
                loadQuestionMedia(questionId);
            }
        }
    }

    /**
     * Check which question is currently active and load its media if it has changed
     */
    function checkCurrentQuestion() {
        var questionId = getActiveQuestionId();
        
        if (questionId && questionId !== currentQuestion && !isLoading) {
            // Don't reload if we just loaded this within the last 3 seconds
            var now = new Date().getTime();
            if (now - lastLoadTime < 3000) {
                return;
            }
            
            previousQuestion = currentQuestion;
            currentQuestion = questionId;
            loadQuestionMedia(questionId);
        }
    }
    
    /**
     * Get the ID of the active question using multiple detection methods
     */
    function getActiveQuestionId() {
        // Method 1: From visible question elements
        var $visibleQuestion = $('.wpProQuiz_listItem:visible');
        if ($visibleQuestion.length) {
            // Try to get from question-meta data attribute
            var questionMeta = $visibleQuestion.data('question-meta');
            if (questionMeta && questionMeta.question_post_id) {
                console.log('Found question ID from meta:', questionMeta.question_post_id);
                $('#question-debug').append('<div>Found ID from meta: ' + questionMeta.question_post_id + '</div>');
                return questionMeta.question_post_id;
            }
            
            // Try from the question list
            var $questionList = $visibleQuestion.find('.wpProQuiz_questionList');
            if ($questionList.length && $questionList.data('question-id')) {
                console.log('Found question ID from list:', $questionList.data('question-id'));

                return $questionList.data('question-id');
            }
        }
        
        // Method 2: From active review dot
        var $activeDot = $('.wpProQuiz_reviewQuestionTarget');
        if ($activeDot.length) {
            var dotIndex = $activeDot.index();
            if (dotIndex >= 0 && window.quizQuestionData && window.quizQuestionData[dotIndex]) {
                console.log('Found question ID from dot index:', dotIndex, window.quizQuestionData[dotIndex]);

                return window.quizQuestionData[dotIndex];
            }
        }
        
        // Method 3: Try to extract from URL or other elements
        var $questionInput = $('input[name="question_id"]');
        if ($questionInput.length) {
            console.log('Found question ID from input:', $questionInput.val());

            return $questionInput.val();
        }
        
        // Method 4: Try to use hardcoded first question ID from quizQuestionData
        if (window.quizQuestionData && window.quizQuestionData.length > 0) {
            console.log('Using first question from quizQuestionData:', window.quizQuestionData[0]);

            return window.quizQuestionData[0];
        }
        
        // Log failure
        console.log('Failed to find question ID');

        return null;
    }

    /**
     * Load media for a specific question
     */
    function loadQuestionMedia(questionId) {
        if (!questionId) {
            console.log('No question ID provided');
            return;
        }
        
        // Prevent multiple simultaneous loads
        if (isLoading) {
            return;
        }
        
        // Check if we already have this question's media cached
        if (questionMedia[questionId]) {
            $('#question-media').html(questionMedia[questionId].html);
            return;
        }
        
        isLoading = true;
        lastLoadTime = new Date().getTime();
        
        // Show fallback image as loading state
        $('#question-media').html('<div class="media-content question-media-image"><img src="http://lilacquiz.local/wp-content/uploads/2025/05/noPic.png" alt="Loading..." class="fallback-image"></div>');
        

        
        // Fetch question media data via AJAX
        $.ajax({
            url: ldMedia.ajaxUrl, // Use the correct localized variable
            type: 'POST',
            data: {
                action: 'get_question_acf_media',
                question_id: questionId,
                nonce: ldMedia.nonce || ''
            },
            success: function(response) {
                isLoading = false;
                
                // Always log the response for debugging
                console.log('AJAX Response:', response);
                
                if (response.success) {
                    // Store current media data
                    questionMedia[questionId] = response.data;
                    
                    // Display the media content
                    $('#question-media').html(response.data.html);
                    

                } else {
                    // Show error message
                    $('#question-media').html('<div class="media-error">Failed to load media content</div>');
                }
            },
            error: function(xhr, status, error) {
                isLoading = false;
                console.log('Error loading media:', error);
                $('#question-media').html('<div class="media-error">Failed to load media</div>');
            }
        });
    }

    // Initialize on document ready
    $(document).ready(initQuizSidebarMedia);

})(jQuery);
