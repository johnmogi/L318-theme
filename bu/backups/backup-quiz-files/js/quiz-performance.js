/**
 * Quiz Performance Helper
 * Provides performance enhancements and monitoring for LearnDash quizzes
 */

(function($) {
    'use strict';
    
    // Check if jQuery is available
    if (typeof $ === 'undefined') {
        console.error('jQuery is required but not loaded');
        return;
    }
    
    const quizPerformance = {
        // Store quiz data
        quizData: null,
        responseCache: {},
        
        // Initialize
        init: function() {
            console.log('Quiz Performance: Initializing...');
            
            // If we have quiz data mapping, use it
            if (typeof quizQuestionData !== 'undefined') {
                console.log('Quiz Performance: Question mapping available');
                this.setupAjaxErrorHandling();
            }
            
            // This helps reduce repeated AJAX calls
            this.setupResponseCaching();
        },
        
        // Setup AJAX error handling to catch and retry failed requests
        setupAjaxErrorHandling: function() {
            // Store original settings for jQuery AJAX
            const originalSettings = $.ajaxSettings;
            
            // Add a global AJAX error handler
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                console.log('Quiz Performance: AJAX Error detected', {
                    status: jqXHR.status,
                    url: ajaxSettings.url,
                    error: thrownError
                });
                
                // Only handle 502 errors for our specific endpoints
                if (jqXHR.status === 502 && 
                    ajaxSettings.url.includes('admin-ajax.php') && 
                    ajaxSettings.data && 
                    (ajaxSettings.data.includes('get_question_acf_data') || 
                     ajaxSettings.data.includes('quiz'))) {
                    
                    console.log('Quiz Performance: Retrying failed AJAX request in 1 second');
                    
                    // Retry after a delay
                    setTimeout(function() {
                        $.ajax(ajaxSettings);
                    }, 1000);
                }
            });
            
            console.log('Quiz Performance: AJAX error handling set up');
        },
        
        // Cache AJAX responses to reduce duplicate requests
        setupResponseCaching: function() {
            // Cache responses for specific AJAX calls
            const originalAjax = $.ajax;
            
            // Override jQuery's ajax method
            $.ajax = (options) => {
                // Only intercept our own AJAX calls related to questions
                if (options.data && 
                    typeof options.data === 'object' && 
                    options.data.action === 'get_question_acf_data') {
                    
                    const cacheKey = JSON.stringify(options.data);
                    
                    // Return cached response if available
                    if (this.responseCache[cacheKey]) {
                        console.log('Quiz Performance: Using cached response');
                        
                        const cachedResponse = this.responseCache[cacheKey];
                        
                        // Create a deferred object to simulate async behavior
                        const dfd = $.Deferred();
                        
                        // Simulate async response with setTimeout
                        setTimeout(() => {
                            if (options.success) {
                                options.success(cachedResponse);
                            }
                            dfd.resolve(cachedResponse);
                        }, 10);
                        
                        // Return promise
                        return dfd.promise();
                    }
                    
                    // Store the original success callback
                    const originalSuccess = options.success;
                    
                    // Override success to cache response
                    options.success = (response) => {
                        // Cache the response
                        this.responseCache[cacheKey] = response;
                        
                        // Call original callback
                        if (originalSuccess) {
                            originalSuccess(response);
                        }
                    };
                }
                
                // Call original ajax method
                return originalAjax.call($, options);
            };
            
            console.log('Quiz Performance: Response caching set up');
        }
    };
    
    // Initialize on document ready
    $(function() {
        quizPerformance.init();
    });
    
})(jQuery);
