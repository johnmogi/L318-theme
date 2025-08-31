/**
 * Quiz Enforce Hint Override
 * 
 * A completely independent implementation that avoids conflicts with LearnDash
 */
(function($) {
    'use strict';
    
    // Initialize immediately on document ready
    $(document).ready(initEnforceHint);
    
    // Also run again after a short delay to catch any dynamic content
    setTimeout(initEnforceHint, 1000);
    
    /**
     * Initialize the enforce hint functionality
     */
    function initEnforceHint() {
        console.log('Quiz Enforce Hint Override activated');
        
        // Process each question and add our custom elements
        $('.wpProQuiz_listItem').each(processQuestion);
        
        // Watch for form submissions
        $('.wpProQuiz_questionList').on('submit', handleQuestionSubmit);
        
        // Watch for check button clicks
        $(document).on('click', '.wpProQuiz_button[name="check"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            setTimeout(function() {
                checkQuestionResult($question);
            }, 300);
        });
        
        // Handle radio/checkbox input clicks to enable re-checking after incorrect answers
        $(document).on('change', '.wpProQuiz_questionInput', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            // Make sure check button is visible and enabled
            $question.find('.wpProQuiz_button[name="check"]').show()
                .css('display', 'inline-block')
                .prop('disabled', false);
                
            // Hide any previous result messages but keep the hint visible if shown
            $question.find('.wpProQuiz_incorrect, .wpProQuiz_correct').hide();
        });
        
        // Handle navigation buttons to prevent proceeding with wrong answers
        $(document).on('click', '.wpProQuiz_button[name="next"]', function(e) {
            const $question = $(this).closest('.wpProQuiz_listItem');
            if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
                e.preventDefault();
                e.stopPropagation();
                highlightHintButton($question);
                return false;
            }
        });
        
        // Make inputs remain active for re-selection
        $('.wpProQuiz_questionInput').prop('disabled', false)
            .removeAttr('disabled')
            .css('pointer-events', 'auto')
            .closest('label').css('pointer-events', 'auto');
            
        // Observe DOM for new questions
        observeQuizChanges();
    }
    
    /**
     * Process a question to add our custom hint button
     * @param {number} index - Array index
     * @param {Element} question - Question element
     */
    function processQuestion(index, question) {
        const $question = $(question);
        const $buttonContainer = $question.find('p:has(.wpProQuiz_QuestionButton)');
        
        // Hide the original LearnDash hint button if it exists
        $question.find('.wpProQuiz_TipButton').hide().css('display', 'none !important');
        
        // Only add our custom button if it doesn't exist yet
        if ($buttonContainer.length && !$buttonContainer.find('.lilac-hint-button').length) {
            // Create our own completely custom hint button
            const $customHintBtn = $('<button type="button" class="lilac-hint-button wpProQuiz_button">רמז</button>');
            $buttonContainer.prepend($customHintBtn);
            
            // Style the button
            $customHintBtn.css({
                'float': 'left',
                'margin-right': '10px',
                'display': 'inline-block'
            });
            
            // Prepare the hint content immediately
            // Find existing hint content or create it
            let $hintContent = $question.find('.lilac-hint-content');
            if (!$hintContent.length) {
                // Try to get the hint from the original hint content if available
                let hintText = '';
                const $originalHint = $question.find('.wpProQuiz_tipp');
                if ($originalHint.length) {
                    hintText = $originalHint.find('div').html();
                } else {
                    hintText = '<h5 style="margin: 0 0 10px;">רמז</h5><p>נסה לחשוב על התשובה הנכונה. אם אתה מתקשה, פנה למורה לעזרה.</p>';
                }
                
                // Create our custom hint content
                $hintContent = $('<div class="lilac-hint-content"></div>').html(hintText);
                $hintContent.css({
                    'background-color': '#fffde7',
                    'border': '1px solid #ffd54f',
                    'border-radius': '4px',
                    'padding': '15px',
                    'margin': '10px 0',
                    'position': 'relative',
                    'display': 'none' // Initialize as hidden
                });
                
                // Add it after the question list
                $question.find('.wpProQuiz_questionList').after($hintContent);
            }
            
            // Add click handler
            $customHintBtn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Simple toggle
                if ($hintContent.is(':visible')) {
                    $hintContent.hide();
                } else {
                    $hintContent.show();
                }
                
                return false;
            });
        }
    }
    
    /**
     * Check if the question has been answered correctly
     * @param {jQuery} $question - Question element
     */
    function checkQuestionResult($question) {
        if ($question.find('.wpProQuiz_incorrect').is(':visible')) {
            // Wrong answer
            blockNextButton($question);
            highlightHintButton($question);
            
            // Make inputs remain active
            $question.find('.wpProQuiz_questionInput').prop('disabled', false)
                .removeAttr('disabled')
                .css('pointer-events', 'auto')
                .closest('label').css('pointer-events', 'auto');
                
            // Make sure check button is visible for re-submission
            $question.find('.wpProQuiz_button[name="check"]').show()
                .css('display', 'inline-block')
                .prop('disabled', false);
        } else if ($question.find('.wpProQuiz_correct').is(':visible')) {
            // Correct answer, ensure next button is visible
            $question.find('.wpProQuiz_button[name="next"]').show()
                .css('display', 'inline-block')
                .removeAttr('style');
                
            // Hide the hint tooltip if present
            $question.find('.lilac-hint-tooltip').remove();
        }
    }
    
    /**
     * Block the next button
     * @param {jQuery} $question - Question element
     */
    function blockNextButton($question) {
        $question.find('.wpProQuiz_button[name="next"]').hide()
            .css('display', 'none')
            .attr('style', 'display: none !important; visibility: hidden !important;');
    }
    
    /**
     * Highlight the hint button with tooltip
     * @param {jQuery} $question - Question element
     */
    function highlightHintButton($question) {
        const $hintBtn = $question.find('.lilac-hint-button');
        if (!$hintBtn.length) return;
        
        // Add highlighting
        $hintBtn.addClass('highlight');
        
        // Remove any existing tooltips
        $('.lilac-hint-tooltip').remove();
        
        // Add tooltip
        const $tooltip = $('<div class="lilac-hint-tooltip">טעית! להמשך חובה לקחת רמז!</div>');
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
        $tooltip.append('<div class="lilac-tooltip-arrow"></div>');
        $('.lilac-tooltip-arrow').css({
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
        
        // Add highlight animation to button
        $hintBtn.css({
            'animation': 'pulse-button 1.5s infinite',
            'background-color': '#ffc107',
            'color': '#333',
            'font-weight': 'bold',
            'box-shadow': '0 0 10px rgba(255, 193, 7, 0.7)'
        });
        
        // Auto-scroll to hint button
        $('html, body').animate({
            scrollTop: $hintBtn.offset().top - 100
        }, 500);
        
        // Hide tooltip when hint button is clicked
        $hintBtn.one('click', function() {
            $('.lilac-hint-tooltip').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Handle question submission
     * @param {Event} e - Submit event
     */
    function handleQuestionSubmit(e) {
        const $questionList = $(e.target);
        const $question = $questionList.closest('.wpProQuiz_listItem');
        
        // Check after a short delay to allow LearnDash to process
        setTimeout(function() {
            checkQuestionResult($question);
        }, 300);
    }
    
    /**
     * Observe quiz changes to catch dynamically added questions
     */
    function observeQuizChanges() {
        // If MutationObserver is available, use it to detect dynamic content changes
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Look for added questions
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        for (let i = 0; i < mutation.addedNodes.length; i++) {
                            const node = mutation.addedNodes[i];
                            // Check if this is a question or contains questions
                            if (node.nodeType === 1) { // Element node
                                const $node = $(node);
                                if ($node.hasClass('wpProQuiz_listItem')) {
                                    processQuestion(0, node);
                                    
                                    // Make sure inputs remain active for re-selection
                                    $node.find('.wpProQuiz_questionInput').prop('disabled', false)
                                        .removeAttr('disabled')
                                        .css('pointer-events', 'auto')
                                        .closest('label').css('pointer-events', 'auto');
                                } else {
                                    const $questions = $node.find('.wpProQuiz_listItem');
                                    if ($questions.length) {
                                        $questions.each(processQuestion);
                                        
                                        // Make sure inputs remain active for re-selection
                                        $questions.find('.wpProQuiz_questionInput').prop('disabled', false)
                                            .removeAttr('disabled')
                                            .css('pointer-events', 'auto')
                                            .closest('label').css('pointer-events', 'auto');
                                    }
                                }
                            }
                        }
                    }
                });
            });
            
            // Start observing the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            // Fallback: periodically check for new questions
            setInterval(function() {
                $('.wpProQuiz_listItem').each(processQuestion);
            }, 1000);
        }
    }
    
    // Add keyframe animation for button pulsing
    const styleSheet = document.createElement('style');
    styleSheet.type = 'text/css';
    styleSheet.innerHTML = `
    @keyframes pulse-button {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }`;
    document.head.appendChild(styleSheet);
    
})(jQuery);
