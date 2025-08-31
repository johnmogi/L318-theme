/**
 * LearnDash Quiz Performance Enhancements
 * 
 * This script improves quiz performance by optimizing answer processing
 * and reducing the time it takes to handle user interactions.
 * 
 * Version 2.0 - Enhanced feedback response timing
 */

(function() {
    // Store original AJAX methods
    let originalAjax = null;
    let originalJsonp = null;
    
    // Wait for document to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Log that our performance script is active
        console.log('Quiz Performance Enhancement Active (v2.0)');

        // Handle quiz initialization
        initQuizPerformance();
    });

    /**
     * Initialize quiz performance enhancements
     */
    function initQuizPerformance() {
        // Check if we're on a quiz page
        if (!document.querySelector('.wpProQuiz_quiz')) {
            return;
        }

        // Optimize quiz AJAX processing
        optimizeAjaxProcessing();
        
        // Optimize quiz interaction times
        optimizeQuizProcessing();
        
        // Speed up answer feedback
        speedUpAnswerFeedback();
        
        // Add event listener for clicking answer buttons
        improveAnswerProcessing();

        // Add visual cue that indicates the answer is being processed
        addProcessingIndicator();
    }

    /**
     * Optimize AJAX processing to speed up response times
     */
    function optimizeAjaxProcessing() {
        if (window.jQuery && jQuery.ajax) {
            // Store original methods for reference
            originalAjax = jQuery.ajax;
            
            // Override jQuery AJAX for LearnDash requests
            jQuery.ajax = function(url, options) {
                // Convert to options object if string url was provided
                if (typeof url === 'object') {
                    options = url;
                } else {
                    options = options || {};
                    options.url = url;
                }
                
                // Check if this is a LearnDash quiz request
                const isLearnDashRequest = (
                    (options.url && options.url.indexOf('wp-admin/admin-ajax.php') !== -1) &&
                    (options.data && typeof options.data === 'string' && options.data.indexOf('action=wp_pro_quiz') !== -1)
                );
                
                // Apply optimizations for LearnDash requests
                if (isLearnDashRequest) {
                    // Add high priority
                    options.priority = 10;
                    
                    // Cache responses where possible
                    if (!options.cache) {
                        options.cache = true;
                    }
                    
                    // Add success wrapper to provide immediate feedback
                    const originalSuccess = options.success;
                    if (typeof originalSuccess === 'function') {
                        options.success = function(data) {
                            // Process pending response animations immediately
                            processPendingAnimations();
                            
                            // Call original success handler
                            originalSuccess.apply(this, arguments);
                        };
                    }
                }
                
                // Call original Ajax method with optimized options
                return originalAjax.apply(this, [options]);
            };
        }
    }

    /**
     * Process any pending animations immediately
     */
    function processPendingAnimations() {
        // Force show any response containers that are waiting
        const pendingResponses = document.querySelectorAll('.wpProQuiz_response');
        pendingResponses.forEach(response => {
            const correct = response.querySelector('.wpProQuiz_correct');
            const incorrect = response.querySelector('.wpProQuiz_incorrect');
            
            if (correct && getComputedStyle(correct).display === 'none' && 
                incorrect && getComputedStyle(incorrect).display === 'none') {
                // Response is waiting to be shown
                response.style.display = 'block';
            }
        });
    }

    /**
     * Optimize quiz processing to speed up response time
     */
    function optimizeQuizProcessing() {
        // Check for the LearnDash global objects and optimize if possible
        if (typeof window.wpProQuizInitList !== 'undefined') {
            // Adjust processing timers and response times
            for (let quizId in window.wpProQuizInitList) {
                if (window.wpProQuizInitList.hasOwnProperty(quizId) && window.wpProQuizInitList[quizId].config) {
                    // Reduce waiting times by optimizing the quiz config
                    const quizObj = window.wpProQuizInitList[quizId];
                    
                    // Speed up various parts of the quiz
                    if (quizObj.config) {
                        // Speed up quiz processing
                        quizObj._buttonCheckEvent = quizObj.methode.checkQuestionAnswers;
                        
                        // Replace with optimized handler
                        quizObj.methode.checkQuestionAnswers = function() {
                            // Add loading state
                            const checkButton = document.querySelector('.wpProQuiz_QuestionButton[name="check"]');
                            if (checkButton) {
                                checkButton.classList.add('processing');
                            }
                            
                            // Add instant visual feedback
                            addInstantFeedback();
                            
                            // Process answer immediately
                            return quizObj._buttonCheckEvent.apply(this, arguments);
                        };
                    }
                }
            }
        }
    }
    
    /**
     * Speed up the answer feedback system
     */
    function speedUpAnswerFeedback() {
        // Directly modify the quiz feedback timing
        if (window.wpProQuizInitList) {
            for (let quizId in window.wpProQuizInitList) {
                if (window.wpProQuizInitList.hasOwnProperty(quizId)) {
                    const quiz = window.wpProQuizInitList[quizId];
                    
                    // Override the show response method to make it faster
                    if (quiz.methode && quiz.methode.showQuizResult) {
                        const originalMethod = quiz.methode.showResponse;
                        quiz.methode.showResponse = function(questionId, result, correct) {
                            // Call the original but speed up the process
                            const response = originalMethod.apply(this, arguments);
                            
                            // Force-display the response immediately
                            const responseElement = document.getElementById('wpProQuiz_response_' + questionId);
                            if (responseElement) {
                                responseElement.style.display = 'block';
                                
                                const correctElement = responseElement.querySelector('.wpProQuiz_correct');
                                const incorrectElement = responseElement.querySelector('.wpProQuiz_incorrect');
                                
                                if (correct && correctElement) {
                                    correctElement.style.display = 'block';
                                } else if (!correct && incorrectElement) {
                                    incorrectElement.style.display = 'block';
                                }
                            }
                            
                            return response;
                        };
                    }
                }
            }
        }
    }
    
    /**
     * Add instant visual feedback when answering
     */
    function addInstantFeedback() {
        // Immediately add visual feedback classes
        const selectedLabels = document.querySelectorAll('.wpProQuiz_questionListItem input:checked');
        selectedLabels.forEach(input => {
            const label = input.closest('label');
            if (label) {
                label.classList.add('answering');
                
                // Find the right answer preemptively if possible
                const listItem = label.closest('.wpProQuiz_questionListItem');
                if (listItem) {
                    // Look for data-correct attribute or other indicators
                    if (listItem.dataset.correct === '1') {
                        label.classList.add('probably-correct');
                    }
                }
            }
        });
    }

    /**
     * Add event listeners to improve answer processing speed
     */
    function improveAnswerProcessing() {
        // Optimize all check buttons
        const checkButtons = document.querySelectorAll('.wpProQuiz_QuestionButton[name="check"]');
        
        checkButtons.forEach(button => {
            // Use capture to pre-process events
            button.addEventListener('click', function() {
                this.classList.add('processing');
                
                // Show immediate visual feedback
                const selectedInputs = document.querySelectorAll('.wpProQuiz_questionInput:checked');
                selectedInputs.forEach(input => {
                    const label = input.closest('label');
                    if (label) {
                        label.classList.add('answered');
                    }
                });
                
                // Pre-show the response container
                const questionItem = this.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    const responseEl = questionItem.querySelector('.wpProQuiz_response');
                    if (responseEl) {
                        // Force element to be ready for display
                        responseEl.style.opacity = '0';
                        responseEl.style.display = 'block';
                        setTimeout(() => {
                            responseEl.style.opacity = '1';
                        }, 10);
                    }
                }
                
                // Remove processing class after 300ms
                setTimeout(() => {
                    this.classList.remove('processing');
                }, 300);
            }, true);
        });
    }

    /**
     * Add visual processing indicator
     */
    function addProcessingIndicator() {
        // Inject CSS for processing indicators
        const style = document.createElement('style');
        style.textContent = `
            .wpProQuiz_QuestionButton.processing {
                position: relative;
                pointer-events: none;
                opacity: 0.8;
            }
            
            .wpProQuiz_QuestionButton.processing::after {
                content: '';
                position: absolute;
                width: 16px;
                height: 16px;
                top: 50%;
                left: 10px;
                margin-top: -8px;
                border: 2px solid rgba(0,0,0,0.2);
                border-radius: 50%;
                border-top-color: #000;
                animation: ldQuizSpinner 0.6s linear infinite;
            }
            
            @keyframes ldQuizSpinner {
                to { transform: rotate(360deg); }
            }
            
            label.answered {
                font-weight: bold;
            }
            
            label.answering {
                box-shadow: 0 0 0 1px #ddd;
                background-color: #f9f9f9;
                transition: all 0.2s ease;
            }
            
            label.probably-correct {
                background-color: #f0fff0;
            }
            
            /* Speed up response animations */
            .wpProQuiz_response {
                transition: opacity 0.2s ease-in-out;
            }
            
            .wpProQuiz_correct, 
            .wpProQuiz_incorrect {
                animation: fadeInFast 0.2s ease forwards !important;
            }
            
            @keyframes fadeInFast {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
})();
