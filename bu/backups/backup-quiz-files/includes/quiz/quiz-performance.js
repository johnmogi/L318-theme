/**
 * LearnDash Quiz Performance Enhancements
 * 
 * This script improves quiz performance by optimizing answer processing
 * and reducing the time it takes to handle user interactions.
 */

(function() {
    // Wait for document to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Log that our performance script is active
        console.log('Quiz Performance Enhancement Active');

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

        // Optimize quiz interaction times
        optimizeQuizProcessing();
        
        // Add event listener for clicking answer buttons
        improveAnswerProcessing();

        // Add visual cue that indicates the answer is being processed
        addProcessingIndicator();
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
                    if (window.wpProQuizInitList[quizId].config.timelimit) {
                        // Speed up quiz processing
                        window.wpProQuizInitList[quizId]._buttonCheckEvent = window.wpProQuizInitList[quizId].methode.checkQuestionAnswers;
                        
                        // Replace with optimized handler
                        window.wpProQuizInitList[quizId].methode.checkQuestionAnswers = function() {
                            // Add loading state
                            const checkButton = document.querySelector('.wpProQuiz_QuestionButton[name="check"]');
                            if (checkButton) {
                                checkButton.classList.add('processing');
                            }
                            
                            // Process answer immediately
                            return window.wpProQuizInitList[quizId]._buttonCheckEvent.apply(this, arguments);
                        };
                    }
                }
            }
        }
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
        `;
        document.head.appendChild(style);
    }
})();
