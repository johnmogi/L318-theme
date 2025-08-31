jQuery(document).ready(function($) {
    'use strict';

    // Function to handle answer selection
    function handleAnswerSelection() {
        $('.wpProQuiz_questionListItem').off('click').on('click', function(e) {
            // Don't trigger if clicking on the radio input directly
            if ($(e.target).is('input[type="radio"]')) {
                return;
            }

            const $listItem = $(this);
            const $radio = $listItem.find('input[type="radio"]');
            
            // If already selected, don't do anything
            if ($radio.prop('checked')) {
                return;
            }

            // Mark the answer as selected
            $radio.prop('checked', true).trigger('change');
            
            // Remove selection from other items in the same question
            $listItem.siblings().removeClass('is-selected');
            
            // Add selected class to current item
            $listItem.addClass('is-selected');
            
            // Trigger the check button click
            const $question = $listItem.closest('.wpProQuiz_question');
            const $checkButton = $question.find('input[name="check"]');
            
            if ($checkButton.length) {
                // Small delay to ensure radio state is registered
                setTimeout(function() {
                    $checkButton.trigger('click');
                }, 50);
            }
        });
    }

    // Function to style and position navigation buttons
    function styleNavigationButtons() {
        // Check if we're on a quiz page and buttons exist
        if ($('.wpProQuiz_QuestionButton').length > 0) {
            $('.wpProQuiz_page').each(function() {
                const $page = $(this);
                const $nextButton = $page.find('input[name="next"]');
                const $backButton = $page.find('input[name="back"]');
                
                // Only proceed if we have navigation buttons
                if ($nextButton.length > 0 || $backButton.length > 0) {
                    // Remove any existing navigation container to avoid duplicates
                    $('.quiz-navigation-container').remove();
                    
                    // Create container for buttons
                    const $container = $('<div class="quiz-navigation-container"></div>');
                    
                    // Add buttons to container
                    if ($backButton.length) $container.append($backButton);
                    if ($nextButton.length) $container.append($nextButton);
                    
                    // Add container after the question list
                    $page.find('.wpProQuiz_list').after($container);
                    
                    // Style the buttons
                    $container.find('input[type="button"]').css({
                        'display': 'inline-block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'position': 'relative',
                        'pointer-events': 'auto',
                        'margin': '0 10px',
                        'padding': '12px 30px',
                        'border-radius': '50px',
                        'border': 'none',
                        'background': '#4CAF50',
                        'color': 'white',
                        'font-weight': 'bold',
                        'cursor': 'pointer',
                        'transition': 'all 0.3s ease',
                        'box-shadow': '0 4px 6px rgba(0,0,0,0.1)'
                    });
                    
                    // Style the Back button differently
                    $backButton.css({
                        'background': '#f0f0f0',
                        'color': '#333'
                    });
                    
                    // Handle Next button visibility based on quiz type
                    const isForcedHint = $('body').hasClass('forced-hint-quiz');
                    if (isForcedHint) {
                        $nextButton.hide();
                    } else {
                        $nextButton.show();
                    }
                    
                    // Add hover effects
                    $container.find('input[type="button"]').hover(
                        function() {
                            $(this).css({
                                'transform': 'translateY(-2px)',
                                'box-shadow': '0 6px 12px rgba(0,0,0,0.15)'
                            });
                        },
                        function() {
                            $(this).css({
                                'transform': 'translateY(0)',
                                'box-shadow': '0 4px 6px rgba(0,0,0,0.1)'
                            });
                        }
                    );
                }
            });
        }
    }
    
    // Function to remove unwanted buttons
    function removeUnwantedButtons() {
        // Remove Review button
        $('input[name="review"]').remove();
        
        // Hide the Mark button but keep it in DOM for functionality
        $('input[name="check"]').css({
            'display': 'none',
            'visibility': 'hidden',
            'opacity': '0',
            'position': 'absolute',
            'pointer-events': 'none'
        });
    }

    // Force remove specific buttons on forced hint quizzes
    function removeForcedHintButtons() {
        const $body = $('body');
        const isForcedHint = $body.hasClass('quiz-type-forced-hint') || $body.hasClass('forced-hint-quiz');
        
        if (isForcedHint) {
            // Add the quiz type class if not present (for backward compatibility)
            if (!$body.hasClass('quiz-type-forced-hint')) {
                $body.addClass('quiz-type-forced-hint');
            }
            
            // Remove Next/Submit buttons but keep the check button
            $('input[name="next"], input[value="Next"], input[value="הבא"], input[value="סיים מבחן"], input[value="Complete Quiz"]').each(function() {
                const $btn = $(this);
                // Only remove if not already processed and not the check button
                if (!$btn.hasClass('processed-by-quiz-handler') && $btn.attr('name') !== 'check') {
                    $btn.addClass('processed-by-quiz-handler').remove();
                }
            });
            
            // Remove any button containers that might contain Next buttons
            $('.wpProQuiz_QuestionButton').filter(function() {
                const $btn = $(this);
                if ($btn.hasClass('processed-by-quiz-handler') || $btn.attr('name') === 'check') return false;
                
                const val = ($btn.val() || '').toString();
                const shouldRemove = ['Next', 'הבא', 'סיים מבחן', 'Complete Quiz'].some(text => val.includes(text));
                
                if (shouldRemove) {
                    $btn.addClass('processed-by-quiz-handler');
                    return true;
                }
                return false;
            }).remove();
            
            // Ensure the hint and check buttons are visible and styled
            $('.wpProQuiz_QuestionButton[value="Hint"]')
                .addClass('hint-button')
                .css({
                    'display': 'inline-block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
                
            // Ensure the check button is visible and styled
            $('input[name="check"]')
                .css({
                    'display': 'inline-block !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important',
                    'position': 'relative !important',
                    'pointer-events': 'auto !important',
                    'background-color': '#4CAF50 !important',
                    'color': 'white !important',
                    'font-weight': 'bold !important',
                    'border': '2px solid #2E7D32 !important',
                    'border-radius': '4px !important',
                    'padding': '8px 24px !important',
                    'cursor': 'pointer !important',
                    'font-size': '16px !important',
                    'margin-right': '10px !important',
                    'box-shadow': '0 3px 5px rgba(0,0,0,0.2) !important',
                    'float': 'right !important',
                    'z-index': '1000 !important'
                });
        }
    }

    // Initialize
    function init() {
        // Initial cleanup and setup
        removeUnwantedButtons();
        removeForcedHintButtons();
        handleAnswerSelection();
        styleNavigationButtons();
        
        // Handle quiz page load and navigation
        function handleQuizChanges() {
            removeUnwantedButtons();
            removeForcedHintButtons();
            handleAnswerSelection();
            styleNavigationButtons();
            
            // Ensure Next button is visible on non-forced hint quizzes
            if (!$('body').hasClass('forced-hint-quiz')) {
                $('input[name="next"]').each(function() {
                    $(this).css({
                        'display': 'inline-block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'position': 'relative',
                        'pointer-events': 'auto',
                        'height': 'auto',
                        'width': 'auto',
                        'padding': '12px 30px',
                        'margin': '0 10px',
                        'border': 'none'
                    });
                });
            } else {
                // Double-check for any remaining Next buttons on forced hint quizzes
                removeForcedHintButtons();
            }
        }
        
        // Set up a mutation observer to handle dynamically loaded questions
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    shouldUpdate = true;
                }
            });
            
            if (shouldUpdate) {
                handleQuizChanges();
            }
        });

        // Start observing the document with the configured parameters
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        // Also listen for quiz page changes
        $(document).on('wpProQuiz:questionDisplayed', handleQuizChanges);
    }

    // Run initialization
    init();
    
    // Set up a dedicated observer for the check button
    function setupCheckButtonObserver() {
        const checkButtonObserver = new MutationObserver(function(mutations) {
            $('input[name="check"]').each(function() {
                const $btn = $(this);
                if ($btn.css('display') === 'none' || 
                    $btn.css('visibility') === 'hidden' || 
                    $btn.css('opacity') === '0' ||
                    $btn.css('pointer-events') === 'none') {
                    
                    $btn.css({
                        'display': 'inline-block !important',
                        'visibility': 'visible !important',
                        'opacity': '1 !important',
                        'position': 'relative !important',
                        'pointer-events': 'auto !important',
                        'background-color': '#4CAF50 !important',
                        'color': 'white !important',
                        'font-weight': 'bold !important',
                        'border': '2px solid #2E7D32 !important',
                        'border-radius': '4px !important',
                        'padding': '8px 24px !important',
                        'cursor': 'pointer !important',
                        'font-size': '16px !important',
                        'margin-right': '10px !important',
                        'box-shadow': '0 3px 5px rgba(0,0,0,0.2) !important',
                        'float': 'right !important',
                        'z-index': '1000 !important',
                        'height': 'auto !important',
                        'width': 'auto !important',
                        'line-height': 'normal !important'
                    });
                }
            });
        });

        // Start observing the document with the configured parameters
        checkButtonObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        // Also run immediately
        $('input[name="check"]').css({
            'display': 'inline-block !important',
            'visibility': 'visible !important',
            'opacity': '1 !important',
            'position': 'relative !important',
            'pointer-events': 'auto !important',
            'background-color': '#4CAF50 !important',
            'color': 'white !important',
            'font-weight': 'bold !important',
            'border': '2px solid #2E7D32 !important',
            'border-radius': '4px !important',
            'padding': '8px 24px !important',
            'cursor': 'pointer !important',
            'font-size': '16px !important',
            'margin-right': '10px !important',
            'box-shadow': '0 3px 5px rgba(0,0,0,0.2) !important',
            'float': 'right !important',
            'z-index': '1000 !important',
            'height': 'auto !important',
            'width': 'auto !important',
            'line-height': 'normal !important'
        });
    }
    
    // Initialize the check button observer
    setupCheckButtonObserver();
    
    // Also run on LearnDash quiz page load event
    $(document).on('sfwd-quiz-page-loaded', function() {
        removeUnwantedButtons();
        handleAnswerSelection();
    });

});
