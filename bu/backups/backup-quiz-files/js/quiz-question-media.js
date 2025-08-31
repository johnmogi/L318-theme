/**
 * Quiz Question Media Handler
 * Handles displaying media content for quiz questions
 * Enhanced for faster loading and performance
 */

// Create a namespace for the module

// Preload the default image for immediate display
const preloadDefaultImage = () => {
    const img = new Image();
    img.src = '/wp-content/uploads/2025/02/noPic.png';
};
preloadDefaultImage(); // Execute immediately

// Wrap in IIFE to safely use $ for jQuery
(function($) {
    'use strict';
    
    // Global preloaded image cache for jQuery
    const preloadedImages = {};
    
    // Aggressive preloader for quiz questions
    const preloadQuizImages = function() {
        // Find all quiz questions on the page
        const questionIds = [];
        $('.wpProQuiz_listItem').each(function() {
            const id = $(this).data('question-id');
            if (id) questionIds.push(id);
        });

        // Preload media for first 3 questions immediately
        const firstQuestions = questionIds.slice(0, 3);
        if (firstQuestions.length && typeof quizQuestionMediaVars !== 'undefined') {
            console.log('Preloading media for first questions:', firstQuestions);
            firstQuestions.forEach(id => {
                $.ajax({
                    url: quizQuestionMediaVars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_question_acf_data',
                        question_post_id: id,
                        nonce: quizQuestionMediaVars.nonce
                    },
                    success: (response) => {
                        if (response.success && response.data && response.data.acf) {
                            // Store for later use
                            if (window.quizQuestionMedia && window.quizQuestionMedia.questions) {
                                const cacheKey = 'question_' + id;
                                window.quizQuestionMedia.questions[cacheKey] = response.data;
                            }
                            
                            // Preload images
                            if (response.data.acf.rich_media_url) {
                                if (!preloadedImages[response.data.acf.rich_media_url]) {
                                    const img = new Image();
                                    img.src = response.data.acf.rich_media_url;
                                    preloadedImages[response.data.acf.rich_media_url] = img;
                                    console.log('Preloaded image:', response.data.acf.rich_media_url);
                                }
                            }
                        }
                    }
                });
            });
        }
    };
    
    // Run preloader when document is ready
    $(document).ready(function() {
        setTimeout(preloadQuizImages, 500); // Slight delay to prioritize initial page load
    });

    // Check if jQuery is available
    if (typeof $ === 'undefined') {
        console.error('jQuery is required but not loaded');
        return;
    }
    
    console.log('jQuery version:', $.fn.jquery);
    
    // Debug: Check if quizQuestionMediaVars is available
    if (typeof window.quizQuestionMediaVars === 'undefined') {
        console.error('quizQuestionMediaVars is not defined. Make sure the script is properly enqueued in WordPress.');
        // Try to get the script data from the DOM as a fallback
        var scriptData = $('script[data-quiz-question-media-vars]');
        if (scriptData.length) {
            try {
                window.quizQuestionMediaVars = JSON.parse(scriptData.data('quiz-question-media-vars'));
                console.log('Loaded quizQuestionMediaVars from data attribute');
            } catch (e) {
                console.error('Failed to parse quizQuestionMediaVars from data attribute', e);
                return;
            }
        } else {
            return;
        }
    }
    
    console.log('quizQuestionMediaVars:', window.quizQuestionMediaVars);
    
    // Global object to store question media data
    var quizQuestionMedia = {
    questions: {},
    currentQuestionId: null,
    noImageUrl: '/wp-content/uploads/2025/02/noPic.png',
    debugMode: false,
    cachedImages: {}, // Store preloaded images
    
    // Initialize the media handler
    init: function() {
        this.log('Initializing quiz question media handler');
        
        // Check if we're on a quiz page
        if (typeof quizQuestionMediaVars === 'undefined') {
            this.log('Not on a quiz page, skipping initialization');
            return;
        }
        
        // Set debug to false to hide the debug UI
        this.debugMode = false; // Always hide debug UI from sidebar
        this.questions = {};
        
        // Handle all navigation buttons
        $(document).on('click', '.wpProQuiz_QuestionButton', (e) => {
            const $button = $(e.currentTarget);
            
            // Handle any button click for navigation
            setTimeout(() => {
                const $currentQuestion = $('.wpProQuiz_questionList:visible');
                if ($currentQuestion.length) {
                    this.handleQuestionChange(
                        this.getQuestionId($currentQuestion[0]),
                        $currentQuestion[0]
                    );
                }
            }, 150);
        });
        
        // Handle question navigation via numbered list
        $(document).on('click', '.wpProQuiz_reviewQuestion li', (e) => {
            const $clickedItem = $(e.currentTarget);
            
            // Wait for LearnDash to update the DOM
            setTimeout(() => {
                const $currentQuestion = $('.wpProQuiz_questionList:visible');
                if ($currentQuestion.length) {
                    this.handleQuestionChange(
                        this.getQuestionId($currentQuestion[0]),
                        $currentQuestion[0]
                    );
                }
            }, 150);
        });
        
        // Additional handler for direct number navigation
        $(document).on('click', '.wpProQuiz_questionListItem > a', (e) => {
            setTimeout(() => {
                const $currentQuestion = $('.wpProQuiz_questionList:visible');
                if ($currentQuestion.length) {
                    this.handleQuestionChange(
                        this.getQuestionId($currentQuestion[0]),
                        $currentQuestion[0]
                    );
                }
            }, 150);
        });
        
        // Set up observers and listeners
        this.setupQuestionObserver();
        this.setupGlobalListeners();
        
        // Check for initial question
        this.checkForQuestion();
    },
    
    // Log messages only in debug mode
    log: function() {
        if (this.debugMode) {
            console.log.apply(console, ['[QuizQuestionMedia]'].concat(Array.from(arguments)));
        }
    },
    
    // Check for current question on page load
    checkForQuestion: function() {
        const currentQuestion = document.querySelector('.wpProQuiz_question:not([style*="display: none"])');
        if (currentQuestion) {
            const questionId = this.getQuestionId(currentQuestion);
            if (questionId) {
                this.log('Found initial question:', questionId);
                this.handleQuestionChange(questionId, currentQuestion);
            }
        }
    },
    
    // Set up observer for question changes
    setupQuestionObserver: function() {
        const targetNode = document.querySelector('.wpProQuiz_content');
        if (!targetNode) {
            this.log('No quiz content found, will retry...');
            setTimeout(() => this.setupQuestionObserver(), 1000);
            return;
        }
        
        this.log('Setting up question observer');
        
        const observer = new MutationObserver((mutations) => {
            const currentQuestion = document.querySelector('.wpProQuiz_question:not([style*="display: none"])');
            if (currentQuestion) {
                const questionId = this.getQuestionId(currentQuestion);
                if (questionId && questionId !== this.currentQuestionId) {
                    this.log('Question changed to ID:', questionId);
                    this.currentQuestionId = questionId;
                    this.handleQuestionChange(questionId, currentQuestion);
                }
            }
        });
        
        observer.observe(targetNode, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    },
    
    // Set up global event listeners
    setupGlobalListeners: function() {
        // Listen for custom event when question changes
        document.addEventListener('quizQuestionChanged', (e) => {
            if (e.detail && e.detail.questionId) {
                this.log('Received quizQuestionChanged event for question:', e.detail.questionId);
                this.currentQuestionId = e.detail.questionId;
                this.updateMediaForQuestion(e.detail.questionId);
            }
        });
    },
    
    // Get question ID from element
    getQuestionId: function(questionElement) {
        const $el = $(questionElement);
        return $el.find('.wpProQuiz_questionList').data('question_id') || 
               $el.data('question_id') ||
               $el.closest('[data-question_id]').data('question_id') ||
               $el.closest('.wpProQuiz_listItem').data('question_id');
    },
    
    // Handle when question changes
    handleQuestionChange: function(questionId, questionElement) {
        try {
            this.log('Question changed to ID:', questionId);
            
            if (!questionId) {
                this.log('No question ID provided');
                return;
            }
            
            // Show loading state with default image
            const $sidebar = $('.quiz-sidebar-area');
            if ($sidebar.length) {
                $sidebar.html(`<div class="quiz-media-loading" style="padding: 20px; text-align: center;">
                    <img src="${this.noImageUrl}" alt="Loading" style="max-width: 100%; height: auto; margin: 0 auto; display: block;">
                </div>`);
            }
            
            // Get the actual post ID from the mapping if available
            let postId = questionId;
            if (typeof quizQuestionData !== 'undefined' && 
                quizQuestionData.questionMapping && 
                quizQuestionData.questionMapping[questionId]) {
                postId = quizQuestionData.questionMapping[questionId];
                this.log('Mapped question ID', questionId, 'to post ID:', postId);
            }
            
            // Small delay to ensure LearnDash has updated the DOM
            setTimeout(() => {
                // Check if we already have the data
                if (this.questions[postId]) {
                    this.log('Using cached data for question:', postId);
                    this.updateSidebarWithMedia(this.questions[postId]);
                } else {
                    // Fetch ACF data for this question
                    this.fetchQuestionData(questionId, questionElement);
                }
            }, 100);
            
        } catch (error) {
            console.error('Error in handleQuestionChange:', error);
            this.showError('Error updating question: ' + error.message);
        }
    },
    
    // Fetch ACF data for a question
    fetchQuestionData: function(questionId, questionElement, retryCount = 0) {
        // Start tracking performance
        this.loadingStartTime = performance.now();
        this.log('Fetching ACF data for question ID:', questionId);
        
        // Get the question post ID from the mapping
        let questionPostId = questionId;
        
        // If we have a mapping available, use it to get the correct post ID
        if (typeof quizQuestionData !== 'undefined' && 
            quizQuestionData.questionMapping && 
            quizQuestionData.questionMapping[questionId]) {
            questionPostId = quizQuestionData.questionMapping[questionId];
            this.log('Mapped question ID', questionId, 'to post ID:', questionPostId);
        } else {
            this.log('No mapping found for question ID, using as-is:', questionId);
        }
        
        // If we couldn't find the post ID, try to get it from the question meta
        if (!questionPostId) {
            const $el = $(questionElement);
            const questionMeta = $el.find('.wpProQuiz_questionList').data('question-meta');
            if (questionMeta) {
                try {
                    const meta = JSON.parse(questionMeta);
                    questionPostId = meta.question_post_id || meta.question_pro_id;
                } catch (e) {
                    this.log('Error parsing question meta:', e);
                }
            }
        }
        
        if (!questionPostId) {
            this.log('No question post ID found for question:', questionId);
            return;
        }
        
        this.log('Found question post ID:', questionPostId);
        
        // Configure the AJAX request
        this.log('Sending AJAX request to:', quizQuestionMediaVars.ajaxurl);
        this.log('Question Post ID:', questionPostId);
        
        // Check if we have this data cached
        const cacheKey = 'question_' + questionPostId;
        if (this.questions[cacheKey]) {
            this.log('Using cached data for question:', questionPostId);
            this.handleAjaxResponse({ success: true, data: this.questions[cacheKey] }, questionElement);
            // Log performance for cached response
            const loadTime = performance.now() - this.loadingStartTime;
            console.log(`Performance: Question data loaded from cache in ${loadTime.toFixed(2)}ms`);
            return;
        }
        
        // Make AJAX request to get ACF data
        $.ajax({
            url: quizQuestionMediaVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_question_acf_data',
                question_post_id: questionPostId,
                nonce: quizQuestionMediaVars.nonce
            },
            success: (response) => {
                // Cache the response
                if (response && response.success && response.data) {
                    this.questions[cacheKey] = response.data;
                }
                // Log performance for AJAX response
                const loadTime = performance.now() - this.loadingStartTime;
                console.log(`Performance: Question data loaded via AJAX in ${loadTime.toFixed(2)}ms`);
                this.handleAjaxResponse(response, questionElement);
            },
            error: (xhr, status, error) => {
                console.error('AJAX error:', error, xhr.responseText, 'Status:', xhr.status);
                
                // If it's a server error (5xx), try again after a delay
                if (xhr.status >= 500 && retryCount < 3) {
                    this.log('Retrying AJAX request in ' + ((retryCount + 1) * 1000) + 'ms');
                    setTimeout(() => {
                        this.fetchQuestionData(questionId, questionElement, retryCount + 1);
                    }, 1000 * (retryCount + 1)); // Exponential backoff
                    return;
                }
                
                // Show error but still update UI with default content
                this.showError('Failed to load question data: ' + error);
                
                // Create default data for display
                const defaultData = {
                    id: questionPostId,
                    title: 'Question ' + questionId,
                    post_type: 'sfwd-question',
                    acf_active: false,
                    acf: null
                };
                
                this.updateSidebarWithMedia(defaultData);
            }
        });
    },
    
    // Handle AJAX response - optimized for faster processing
    handleAjaxResponse: function(response, questionElement) {
        try {
            if (!response || response.success === false) {
                throw new Error(response.data?.message || 'Unknown error occurred');
            }
            
            // Preload any images immediately to make content appear faster
            if (response.data && response.data.acf && response.data.acf.rich_media_url) {
                const img = new Image();
                img.src = response.data.acf.rich_media_url;
            }
            
            const questionData = response.data;
            
            // Debug log the response
            this.log('Received question data:', questionData);
            
            // Log specific ACF fields for debugging
            if (questionData && questionData.acf) {
                console.log('ACF Fields Debug:', {
                    'choose_media': questionData.acf.choose_media,
                    'video_url': questionData.acf.video_url,
                    'rich_media': questionData.acf.rich_media,
                    'rich_media_url': questionData.acf.rich_media_url
                });
            }
            
            // Update the sidebar with the question data
            this.updateSidebarWithMedia(questionData);
            
            // Setup hint button functionality
            this.setupHintButton(questionData);
            
            // Also update any debug display
            if (this.debugMode) {
                this.debugDisplayACFFields(questionData.id, questionData);
            }
            
            // Log the mapping for debugging
            if (typeof quizQuestionData !== 'undefined' && quizQuestionData.questionMapping) {
                this.log('Available question mapping:', quizQuestionData.questionMapping);
            }
            
        } catch (error) {
            console.error('Error handling AJAX response:', error);
            this.showError('Error loading question data: ' + error.message);
        }
    },
    
    // Update media display for current question
    updateMediaForQuestion: function(questionId) {
        const questionData = this.questions[questionId];
        if (!questionData) {
            this.log('No media data available for question:', questionId);
            return;
        }
        
        this.log('Updating media for question:', questionId, questionData);
        
        // Display the ACF fields in a debug panel
        this.debugDisplayACFFields(questionId, questionData);
        
        // Update the sidebar with the media content
        this.updateSidebarWithMedia(questionData);
    },
    
    // Show error message to user - simplified and less intrusive
    showError: function(message) {
        console.error('Quiz Media Error:', message);
        
        // Only log to console to avoid disrupting user experience
        // Display default image instead of error
        const $sidebar = $('.quiz-sidebar-area');
        if ($sidebar.length) {
            $sidebar.html(`<div class="quiz-media-container">
                <div class="quiz-media-error" style="padding: 20px; text-align: center;">
                    <img src="${this.noImageUrl}" alt="No Media Available" style="max-width: 100%; height: auto; margin: 0 auto; display: block;">
                </div>
            </div>`);
        }
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            $errorContainer.fadeOut();
        }, 10000);
    },
    
    // Update the sidebar with media content
    updateSidebarWithMedia: function(questionData) {
        try {
            const $sidebar = $('.quiz-sidebar-area');
            if (!$sidebar.length) {
                throw new Error('No sidebar element found with class "quiz-sidebar-area"');
            }
            
            // Store the current scroll position
            const scrollPosition = $(window).scrollTop();
            
            this.log('Updating sidebar with question data:', questionData);
            
            // Show default image immediately instead of loading indicator
            $sidebar.html(`
                <div class="quiz-media-loading" style="padding: 20px; text-align: center;">
                    <img src="${this.noImageUrl}" alt="Question Media" style="max-width: 100%; height: auto; margin: 0 auto; display: block;"/>
                </div>
            `);
            
            // Record loading start time for performance tracking
            this.loadingStartTime = performance.now();
            
            // Process the question data - optimized for performance
            const processData = () => {
                // Only log debug info when debug mode is on
                if (this.debugMode) {
                    // Create a safe copy of the data for debugging (avoid circular references)
                    const debugData = {};
                    if (questionData) {
                        Object.keys(questionData).forEach(key => {
                            try {
                                // Skip large data or functions
                                if (typeof questionData[key] !== 'function' && 
                                    !(questionData[key] instanceof HTMLElement)) {
                                    debugData[key] = questionData[key];
                                }
                            } catch (e) {
                                debugData[key] = '[Error processing ' + key + ']';
                            }
                        });
                    }
                    
                    // Log to console instead of showing in the UI (minimal logging for performance)
                    console.log('Question data loaded:', questionData?.id || 'N/A');
                }
                
                // No debug HTML in the UI
                let debugHtml = '';
                
                let html = `
                    <div class="question-media-container">
                        ${questionData.title ? `<h3 class="question-title">${questionData.title}</h3>` : ''}
                        ${debugHtml}`;
                
                // Add media content if available
                if (questionData.acf) {
                    // Check if we have the choose_media field and ensure it's properly accessed
                    let mediaChoice = questionData.acf.choose_media;
                    
                    // Log the exact value and type for debugging
                    console.log('Media choice raw value:', mediaChoice, 'Type:', typeof mediaChoice);
                    
                    // Log all relevant media fields for debugging
                    console.log('Available media fields:', {
                        video_url: questionData.acf.video_url || 'Not available',
                        rich_media: questionData.acf.rich_media || 'Not available',
                        rich_media_url: questionData.acf.rich_media_url || 'Not available'
                    });
                    
                    // Handle case when field might be missing or empty
                    if (!mediaChoice) {
                        mediaChoice = 'תמונה'; // Default to image if not specified
                    }
                    
                    this.log('Media choice (processed):', mediaChoice);
                    
                    // First handle video option with full string check
                    const isVideoOption = mediaChoice && 
                        (mediaChoice.toString().trim() === 'סרטון' || 
                         mediaChoice.toString().includes('סרטון'));
                     
                    // Auto-detect video if we have a video_url but not explicitly selected
                    const hasVideoUrl = questionData.acf.video_url && questionData.acf.video_url.trim() !== '';
                    const hasImageUrl = questionData.acf.rich_media_url && questionData.acf.rich_media_url.trim() !== '';
                    
                    console.log('Media availability:', { hasVideoUrl, hasImageUrl, isVideoOption });
                    
                    // Handle based on media choice - ensure exact string comparison
                    if (isVideoOption || (hasVideoUrl && !hasImageUrl)) { // Video option or only video URL available
                        // Add video if available
                        if (questionData.acf.video_url) {
                            try {
                                const videoUrl = questionData.acf.video_url;
                                console.log('Processing video URL:', videoUrl);
                                
                                // Validate video URL format
                                if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be') || 
                                    videoUrl.includes('vimeo.com') || videoUrl.includes('.mp4')) {
                                    const videoHtml = this.getVideoHtml(videoUrl);
                                    if (videoHtml) {
                                        html += videoHtml;
                                        this.log('Added video to sidebar based on media choice');
                                    }
                                } else {
                                    console.warn('Invalid video URL format:', videoUrl);
                                    html += '<div class="quiz-media-error">Invalid video URL format</div>';
                                }
                            } catch (e) {
                                console.error('Error adding video:', e);
                                html += '<div class="quiz-media-error">Error loading video content</div>';
                            }
                        } else {
                            this.log('Video choice selected but no video_url provided');
                        }
                    } else { // Image option (default)
                        // Add rich media/image if available
                        if (questionData.acf.rich_media_url) {
                            try {
                                const mediaHtml = this.getMediaHtml(questionData.acf.rich_media_url);
                                if (mediaHtml) {
                                    html += mediaHtml;
                                    this.log('Added image to sidebar based on media choice');
                                }
                            } catch (e) {
                                console.error('Error adding media:', e);
                                html += '<div class="quiz-media-error">Error loading image content</div>';
                            }
                        } else {
                            this.log('Image choice selected but no rich_media_url provided');
                        }
                    }
                    
                    // For backwards compatibility or when media choice doesn't match expected content
                    // Make sure we display something if the choice doesn't align with available content
                    const hasVideo = html.includes('question-video');
                    const hasImage = html.includes('question-media');
                    
                    // If we chose video but no video displayed, try to show image as fallback
                    if (isVideoOption && !hasVideo && questionData.acf.rich_media_url) {
                        try {
                            const mediaHtml = this.getMediaHtml(questionData.acf.rich_media_url);
                            if (mediaHtml) {
                                html += mediaHtml;
                                this.log('Added image as fallback when video was selected but not available');
                            }
                        } catch (e) {
                            console.error('Error adding fallback image:', e);
                        }
                    }
                    
                    // If we chose image but no image displayed, try to show video as fallback
                    if (!isVideoOption && !hasImage && questionData.acf.video_url) {
                        try {
                            const videoHtml = this.getVideoHtml(questionData.acf.video_url);
                            if (videoHtml) {
                                html += videoHtml;
                                this.log('Added video as fallback when image was selected but not available');
                            }
                        } catch (e) {
                            console.error('Error adding fallback video:', e);
                        }
                    }
                    
                    // If we still don't have any media content, try both options as last resort
                    if (!hasVideo && !hasImage) {
                        // Try video first
                        if (questionData.acf.video_url) {
                            try {
                                const videoHtml = this.getVideoHtml(questionData.acf.video_url);
                                if (videoHtml) {
                                    html += videoHtml;
                                    this.log('Added video as last resort fallback');
                                }
                            } catch (e) {
                                console.error('Error adding fallback video:', e);
                            }
                        }
                        
                        // Then try image if no video
                        if (!html.includes('question-video') && questionData.acf.rich_media_url) {
                            try {
                                const mediaHtml = this.getMediaHtml(questionData.acf.rich_media_url);
                                if (mediaHtml) {
                                    html += mediaHtml;
                                    this.log('Added image as last resort fallback');
                                }
                            } catch (e) {
                                console.error('Error adding fallback image:', e);
                            }
                        }
                    }
                    
                    // Add hint if available
                    if (questionData.acf.add_hint) {
                        try {
                            const hintContent = questionData.acf.add_hint;
                            // Clean up hint content if it contains HTML from the editor
                            const cleanHint = hintContent.replace(/<[^>]*>?/gm, '').trim();
                            if (cleanHint) {
                                const hintHtml = this.getHintHtml(cleanHint);
                                if (hintHtml) {
                                    html += hintHtml;
                                    this.log('Added hint to sidebar');
                                }
                            }
                        } catch (e) {
                            console.error('Error adding hint:', e);
                            html += '<div class="quiz-media-error">Error loading hint</div>';
                        }
                    }
                    
                    // If no media was added, show a message
                    if (html.indexOf('question-hint') === -1 && 
                        html.indexOf('question-video') === -1 && 
                        html.indexOf('question-media') === -1) {
                        html += `
                        <div class="quiz-no-media" style="padding: 20px; text-align: center;">
                            <img src="${this.noImageUrl}" alt="No media available" style="max-width: 100%; height: auto; margin: 0 auto; display: block;"/>
                            <p style="margin-top: 10px; color: #666;">No additional media available for this question.</p>
                        </div>`;
                        this.log('No media content available for question, showing fallback image');
                    }
                } else {
                    // No ACF data available - show fallback image
                    html += `
                        <div class="quiz-no-media" style="padding: 20px; text-align: center;">
                            <img src="${this.noImageUrl}" alt="No media available" style="max-width: 100%; height: auto; margin: 0 auto; display: block;"/>
                            <p style="margin-top: 10px; color: #666;">No additional media available for this question.</p>
                        </div>`;
                    this.log('No ACF data available for question, showing fallback image');
                }
                
                html += '</div>'; // Close container
                return html;
            };
            
            // Process and update the DOM
            try {
                const html = processData();
                $sidebar.html(html);
                
                // Restore scroll position
                $(window).scrollTop(scrollPosition);
                
                // Trigger an event that the sidebar was updated
                $(document).trigger('quiz:sidebarUpdated');
                
            } catch (e) {
                console.error('Error processing question data:', e);
                $sidebar.html('<div class="quiz-media-error" style="padding: 20px; color: #dc3545;">Error loading question media. Please try again.</div>');
            }
            
        } catch (e) {
            console.error('Error in updateSidebarWithMedia:', e);
            this.showError('Failed to update question media: ' + (e.message || 'Unknown error'));
        }
    },
    
    // Get HTML for media (image)
    getMediaHtml: function(mediaUrl) {
        if (!mediaUrl) return '';
        
        // Performance tracking
        const startTime = performance.now();
        
        // Check if it's an image
        if (mediaUrl.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
            // Log performance
            const processTime = performance.now() - startTime;
            console.log(`Performance: Image HTML processed in ${processTime.toFixed(2)}ms`);
            
            return `
                <div class="question-media">
                    <img src="${mediaUrl}" alt="Question Media" style="max-width: 100%; height: auto; display: block;">
                </div>`;
        }
        
        // Handle other media types if needed
        return '';
    },
    
    // Get HTML for video
    getVideoHtml: function(videoUrl) {
            // Performance tracking
            const startTime = performance.now();
            
            let videoHtml = '<div class="question-video">';
            
            // Check if it's a YouTube video
            if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                let videoId = '';
                
                // Handle youtu.be/ID format
                if (videoUrl.includes('youtu.be/')) {
                    videoId = videoUrl.split('youtu.be/')[1].split('?')[0];
                } 
                // Handle youtube.com/watch?v=ID format
                else if (videoUrl.includes('youtube.com/watch')) {
                    const urlParams = new URLSearchParams(videoUrl.split('?')[1]);
                    videoId = urlParams.get('v');
                }
                
                if (videoId) {
                    videoHtml += `
                        <div class="youtube-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">
                            <iframe 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                src="https://www.youtube.com/embed/${videoId}" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>`;
                } else {
                    videoHtml += '<p>Invalid YouTube URL</p>';
                }
            } 
            // Handle Vimeo videos
            else if (videoUrl.includes('vimeo.com')) {
                const videoId = videoUrl.split('vimeo.com/')[1].split('?')[0];
                videoHtml += `
                    <div class="vimeo-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">
                        <iframe 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                            src="https://player.vimeo.com/video/${videoId}" 
                            frameborder="0" 
                            allow="autoplay; fullscreen" 
                            allowfullscreen>
                        </iframe>
                    </div>`;
            } 
            // Handle direct video files
            else if (videoUrl.match(/\.(mp4|webm|ogg)$/i)) {
                videoHtml += `
                    <video controls style="width: 100%; max-width: 100%;">
                        <source src="${videoUrl}" type="video/${videoUrl.split('.').pop().toLowerCase()}">
                        Your browser does not support the video tag.
                    </video>`;
            }
            // Handle iframe embeds (for other video services)
            else if (videoUrl.includes('iframe')) {
                videoHtml += videoUrl;
            }
            // Fallback to link
            else {
                videoHtml += `<p><a href="${videoUrl}" target="_blank">Watch Video</a></p>`;
            }
            
            videoHtml += '</div>';
            
            // Log performance
            const processTime = performance.now() - startTime;
            console.log(`Performance: Video HTML processed in ${processTime.toFixed(2)}ms`);
            
            return videoHtml;
        },
        
        // Get HTML for image
        getImageHtml: function(imageUrl) {
            // Performance tracking
            const startTime = performance.now();
            
            const html = `
                <div class="question-image">
                    <img 
                        src="${imageUrl}" 
                        alt="Question Media" 
                        style="max-width: 100%; height: auto; display: block;"
                    >
                </div>`;
                
            // Log performance
            const processTime = performance.now() - startTime;
            console.log(`Performance: Image HTML processed in ${processTime.toFixed(2)}ms`);
            
            return html;
        },
        
        // Get HTML for hint
        getHintHtml: function(hintContent) {
            try {
                if (!hintContent) {
                    return '';
                }
                
                return `
                    <div class="question-hint">
                        ${hintContent}
                    </div>`;
            } catch (e) {
                console.error('Error getting hint HTML:', e);
                return '<div class="quiz-media-error">Error loading hint</div>';
            }
        },
        
        // Setup hint button functionality - optimized for better placement and visibility
        setupHintButton: function(questionData) {
            // Remove previous handlers first to avoid duplicates
            $(document).off('click', '#mark-hint');
            $(document).off('click', '#close-hint');
            
            // Only setup if we have hint content
            if (!questionData || !questionData.acf || !questionData.acf.add_hint) {
                return;
            }
            
            const hintContent = questionData.acf.add_hint;
            
            // Add click handler for mark hint button
            $(document).on('click', '#mark-hint', function() {
                console.log('Mark hint button clicked');
                
                // Find the wpProQuiz_tipp element
                const $tippContainer = $('.wpProQuiz_tipp');
                if (!$tippContainer.length) {
                    console.error('Hint container not found');
                    return;
                }
                
                // Find or create the hint container
                let $hintContainer = $('#acf-hint-container');
                
                // If it doesn't exist or was removed, recreate it
                if (!$hintContainer.length) {
                    // Move any existing content into a wrapper if needed
                    let $existingContent = $tippContainer.find('> div').first();
                    
                    // Replace the first div in the tipp with our hint container followed by existing content
                    $existingContent.html(`
                        <div id="acf-hint-container" class="acf-hint-content marked" style="transition: background-color 0.3s; border-bottom: 2px solid #e5e5e5; margin-bottom: 15px; padding-bottom: 15px;">
                            <div class="postbox-header">
                                <h3 style="margin: 0 0 10px; font-weight: bold;">רמז ACF</h3>
                            </div>
                            <div class="inside acf-fields -top">
                                <div class="acf-hint-content-inner">${hintContent}</div>
                            </div>
                        </div>
                        <div class="original-hint-content"></div>
                    `);
                    
                    // Move any existing content into the original-hint-content div
                    const $originalContent = $existingContent.find('h5, p').not('#acf-hint-container h5, #acf-hint-container p');
                    $existingContent.find('.original-hint-content').append($originalContent);
                } else {
                    // Update existing container
                    $hintContainer.find('.acf-hint-content-inner').html(hintContent);
                    $hintContainer.show();
                }
                
                // Highlight any text with highlight-hint class - more prominent effect
                $('.highlight-hint').css({
                    'background-color': 'yellow',
                    'padding': '2px 4px',
                    'border-radius': '3px',
                    'font-weight': 'bold'
                });
            });
            
            // Add click handler for close hint button
            $(document).on('click', '#close-hint', function() {
                console.log('Close hint button clicked');
                $('.wpProQuiz_tipp .acf-hint-content').hide();
            });
        },
        
        // Display the ACF fields in a debug panel
        debugDisplayACFFields: function(questionId, questionData) {
            if (!this.debugMode) return;
            // Create a clean object with just the ACF fields
            const acfFields = {
                questionId: questionId,
                title: questionData.title || 'No title',
                fields: {}
            };
            
            // Include ACF data if available
            if (questionData.acf) {
                acfFields.fields = questionData.acf;
            }
            
            // Log to console in a readable format
            console.group(`[QuizQuestionMedia] Question ${questionId} ACF Fields`);
            console.log('Title:', acfFields.title);
            
            if (Object.keys(acfFields.fields).length > 0) {
                console.log('ACF Fields:', acfFields.fields);
            } else {
                console.log('No ACF fields found');
            }
            
            console.groupEnd();
        }
    };

    // Assign to global scope
    window.quizQuestionMedia = quizQuestionMedia;

    // Initialize the plugin when the document is ready
    $(function() {
        if (window.quizQuestionMedia) {
            console.log('Initializing quizQuestionMedia');
            window.quizQuestionMedia.init();
        } else {
            console.error('quizQuestionMedia is not defined');
        }
    });

})(jQuery);
