/**
 * LearnDash Quiz - Answer Reselection
 * 
 * Enables users to reselect and submit answers after incorrect submission
 * Part of the Enforce Hint feature
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        initQuizAnswerReselection();
    });
    
    // Also initialize after a short delay to catch dynamically loaded content
    setTimeout(function() {
        initQuizAnswerReselection();
    }, 1000);
    
    /**
     * Initialize the quiz answer reselection functionality
     */
    function initQuizAnswerReselection() {
        console.log('Quiz Answer Reselection Module initialized');
        
        // Make sure inputs are always enabled
        enableAllInputs();
        
        // Set up event handlers
        setupEventHandlers();
        
        // Perform initial setup for questions
        $('.wpProQuiz_listItem').each(setupQuestion);
        
        // Set up MutationObserver to watch for DOM changes
        setupObserver();
    }
    
    /**
     * Set up all event handlers
     */
    function setupEventHandlers() {
        // Handle Check button clicks - processes after LearnDash evaluation
        $(document).on('click', '.wpProQuiz_button[name="check"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // Set a timeout to allow LearnDash to process the answer first
            setTimeout(function() {
                handleAnswerResult($question);
            }, 300);
        });
        
        // Handle when a new answer option is selected
        $(document).on('change', '.wpProQuiz_questionInput', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // If this question previously had an incorrect answer
            if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
                // Hide feedback message but keep hint visible if shown
                $question.find('.wpProQuiz_incorrect, .wpProQuiz_correct').hide();
                
                // Make sure the check button is visible
                $question.find('.wpProQuiz_button[name="check"]').show()
                    .css('display', 'inline-block')
                    .prop('disabled', false);
                
                // Hide tooltip if it exists
                $question.find('.hint-tooltip').remove();
            }
        });
        
        // Prevent proceeding with Next button if answer is incorrect
        $(document).on('click', '.wpProQuiz_button[name="next"]', function(e) {
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show tooltip to take hint
                highlightHintButton($question);
                return false;
            }
        });
        
        // Track when hint button is clicked
        $(document).on('click', '.wpProQuiz_TipButton, .wpProQuiz_hint', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            $question.attr('data-hint-viewed', 'true');
            
            // Remove tooltip when hint button is clicked
            $('.hint-tooltip').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Setup an individual question
     */
    function setupQuestion(index, element) {
        const $question = $(element);
        
        // Ensure inputs are always enabled
        enableInputsForQuestion($question);
        
        // Store hint status
        $question.attr('data-has-hint', $question.find('.wpProQuiz_hint, .wpProQuiz_TipButton').length > 0 ? 'true' : 'false');
        $question.attr('data-hint-viewed', 'false');
        
        // Check if question is already answered
        setTimeout(function() {
            handleAnswerResult($question);
        }, 300);
    }
    
    /**
     * Handle the answer result (correct or incorrect)
     */
    function handleAnswerResult($question) {
        // Check if the answer is incorrect
        if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
            // Hide the Next button
            $question.find('.wpProQuiz_button[name="next"]').hide()
                .css('display', 'none')
                .attr('style', 'display: none !important; visibility: hidden !important;');
            
            // Make sure the check button is visible for re-submission
            $question.find('.wpProQuiz_button[name="check"]').show()
                .css('display', 'inline-block')
                .prop('disabled', false);
            
            // Make sure inputs are enabled
            enableInputsForQuestion($question);
            
            // Highlight the hint button
            highlightHintButton($question);
        } 
        // If the answer is correct, show the Next button
        else if ($question.find('.wpProQuiz_correct').is(':visible')) {
            $question.find('.wpProQuiz_button[name="next"]').show()
                .css('display', 'inline-block')
                .removeAttr('style');
                
            // Hide any tooltip
            $question.find('.hint-tooltip').remove();
        }
    }
    
    /**
     * Make sure inputs for a question are enabled and clickable
     */
    function enableInputsForQuestion($question) {
        $question.find('.wpProQuiz_questionInput').prop('disabled', false)
            .removeAttr('disabled')
            .css('pointer-events', 'auto');
        
        $question.find('.wpProQuiz_questionListItem label').css('pointer-events', 'auto');
    }
    
    /**
     * Enable all inputs in the quiz
     */
    function enableAllInputs() {
        $('.wpProQuiz_questionInput').prop('disabled', false)
            .removeAttr('disabled')
            .css('pointer-events', 'auto');
        
        $('.wpProQuiz_questionListItem label').css('pointer-events', 'auto');
    }
    
    /**
     * Highlight the hint button with tooltip
     */
    function highlightHintButton($question) {
        const $hintBtn = $question.find('.wpProQuiz_TipButton, .wpProQuiz_hint').first();
        
        if (!$hintBtn.length) return;
        
        // Add highlighting
        $hintBtn.addClass('highlight')
            .css({
                'animation': 'pulse-button 1.5s infinite',
                'background-color': '#ffc107',
                'color': '#333',
                'font-weight': 'bold',
                'box-shadow': '0 0 10px rgba(255, 193, 7, 0.7)'
            });
        
        // Remove any existing tooltips
        $('.hint-tooltip').remove();
        
        // Add tooltip
        const $tooltip = $('<div class="hint-tooltip">טעית! להמשך חובה לקחת רמז!</div>');
        $hintBtn.after($tooltip);
        
        // Style the tooltip
        $tooltip.css({
            'position': 'absolute',
            'background-color': '#ffc107',
            'color': '#333',
            'padding': '8px 12px',
            'border-radius': '4px',
            'font-weight': 'bold',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
            'z-index': '100',
            'max-width': '250px',
            'text-align': 'center',
            'font-size': '14px',
            'white-space': 'nowrap',
            'margin-top': '5px',
            'right': 'auto',
            'left': $hintBtn.position().left + ($hintBtn.outerWidth() / 2)
        });
        
        // Add arrow
        $tooltip.append('<div class="tooltip-arrow"></div>');
        $('.tooltip-arrow').css({
            'position': 'absolute',
            'top': '-8px',
            'left': '50%',
            'margin-left': '-8px',
            'width': '0',
            'height': '0',
            'border-bottom': '8px solid #ffc107',
            'border-right': '8px solid transparent',
            'border-left': '8px solid transparent'
        });
        
        // Auto-scroll to hint button
        $('html, body').animate({
            scrollTop: $hintBtn.offset().top - 100
        }, 500);
    }
    
    /**
     * Set up MutationObserver to watch for DOM changes
     */
    function setupObserver() {
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Look for added nodes
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        for (let i = 0; i < mutation.addedNodes.length; i++) {
                            const node = mutation.addedNodes[i];
                            if (node.nodeType === 1) { // Element node
                                // If this is a question or contains questions
                                if ($(node).hasClass('wpProQuiz_listItem')) {
                                    setupQuestion(0, node);
                                } else {
                                    const $questions = $(node).find('.wpProQuiz_listItem');
                                    if ($questions.length) {
                                        $questions.each(setupQuestion);
                                    }
                                }
                            }
                        }
                    }
                });
            });
            
            // Observe the body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            // Fallback for older browsers
            setInterval(function() {
                $('.wpProQuiz_listItem').each(setupQuestion);
            }, 1000);
        }
    }
    
    // Add CSS for animations
    const styleSheet = document.createElement('style');
    styleSheet.type = 'text/css';
    styleSheet.innerHTML = `
        @keyframes pulse-button {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .hint-tooltip {
            position: absolute;
            background-color: #ffc107;
            color: #333;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 100;
            max-width: 250px;
            text-align: center;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .wpProQuiz_TipButton.highlight,
        .wpProQuiz_hint.highlight {
            animation: pulse-button 1.5s infinite;
            background-color: #ffc107 !important;
            color: #333 !important;
            font-weight: bold !important;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.7) !important;
        }
    `;
    document.head.appendChild(styleSheet);
    
})(jQuery);
