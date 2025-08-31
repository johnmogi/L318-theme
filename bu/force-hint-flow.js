/**
 * Force Hint Mode - Student Learning Flow
 * Version 1.0.0
 * 
 * This script implements the proper learning flow for quizzes with force hint mode:
 * 1. Initially hide the next button for all questions
 * 2. When a student views a hint, mark it as viewed
 * 3. Only enable the next button when both:
 *    - The hint has been viewed
 *    - The student selects the correct answer
 */

(function($) {
    'use strict';
    
    // Track hints and answers
    const viewedHints = {};
    const correctAnswers = {};
    let forceHintActive = false;
    
    $(document).ready(function() {
        // Check if we're on a quiz page
        if ($('.wpProQuiz_content').length === 0) return;
        
        // Check if force hint mode is active via meta tag
        forceHintActive = $('meta[name="lilac-quiz-force-hint"]').attr('content') === 'true';
        
        if (forceHintActive) {
            console.log('Force Hint Mode: Active - Next buttons will require viewing hints and selecting correct answers');
            initForceHintMode();
        } else {
            console.log('Force Hint Mode: Inactive - Standard quiz behavior');
        }
        
        // Small admin indicator for debugging
        if (typeof adminData !== 'undefined' && adminData.isAdmin) {
            $('body').append(
                '<div id="force-hint-status" style="position:fixed;bottom:40px;left:10px;background:#7b8a8b;color:white;padding:5px 10px;border-radius:4px;font-size:12px;z-index:9999;opacity:0.7;">' +
                'Force Hint: ' + (forceHintActive ? 'Active' : 'Inactive') +
                '</div>'
            );
        }
    });
    
    /**
     * Initialize Force Hint Mode
     */
    function initForceHintMode() {
        // Initially hide all next buttons
        hideAllNextButtons();
        
        // Setup hint click tracking
        $(document).on('click', '.wpProQuiz_QuestionButton[name="tip"], .wpProQuiz_TipButton', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
            
            if (questionId) {
                console.log('Hint viewed for question', questionId);
                viewedHints[questionId] = true;
                
                // Check if we should enable the next button
                updateNextButtonStatus($question);
            }
        });
        
        // Setup answer tracking (both click and change events)
        $(document).on('click change', '.wpProQuiz_questionList input[type="radio"], .wpProQuiz_questionList input[type="checkbox"]', function() {
            const $question = $(this).closest('.wpProQuiz_listItem');
            processAnswerSelection($question);
        });
        
        // Add a small notice to each question
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            
            // Add info about the requirement (only if not already added)
            if ($question.find('.force-hint-notice').length === 0) {
                $question.find('.wpProQuiz_question').append(
                    '<div class="force-hint-notice" style="margin-top:10px;font-size:13px;color:#666;font-style:italic;">' +
                    'רמז: יש לצפות ברמז ולבחור בתשובה הנכונה כדי להמשיך' +
                    '</div>'
                );
            }
        });
        
        // Set up observers for dynamically loaded content
        setupObservers();
    }
    
    /**
     * Process answer selection for a question
     */
    function processAnswerSelection($question) {
        // Get question ID
        const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
        if (!questionId) return;
        
        // Check if any answers are selected
        const hasSelection = $question.find('input:checked').length > 0;
        
        // Wait a short time to allow LearnDash to process the answer
        setTimeout(function() {
            // Check if the answer is correct
            const isCorrect = $question.find('.wpProQuiz_correct').length > 0;
            
            if (isCorrect) {
                console.log('Correct answer for question', questionId);
                correctAnswers[questionId] = true;
            } else {
                // If user has made a selection but it's not marked correct yet,
                // this could be because LearnDash only shows the result after clicking Check
                if (hasSelection && $question.find('.wpProQuiz_incorrect').length === 0) {
                    console.log('Answer selected but not yet checked for question', questionId);
                } else {
                    console.log('Incorrect answer for question', questionId);
                    correctAnswers[questionId] = false;
                }
            }
            
            // Update next button visibility based on the latest state
            updateNextButtonStatus($question);
        }, 100);
    }
    
    /**
     * Update the visibility of the next button based on hint viewed and correct answer
     */
    function updateNextButtonStatus($question) {
        const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
        if (!questionId) return;
        
        const $nextButton = $question.find('.wpProQuiz_button:contains("הבא")');
        if ($nextButton.length === 0) return;
        
        const hintViewed = viewedHints[questionId] === true;
        const answeredCorrectly = correctAnswers[questionId] === true;
        
        // Only show next button if both conditions are met
        if (hintViewed && answeredCorrectly) {
            console.log('Showing next button for question', questionId);
            $nextButton.css('display', '').removeClass('hint-force-hidden');
            
            // Update notice
            $question.find('.force-hint-notice').html(
                '<span style="color:#2c7b46;">✓ ניתן להמשיך לשאלה הבאה</span>'
            );
        } else {
            console.log('Hiding next button for question', questionId, 
                       'hint viewed:', hintViewed, 
                       'answered correctly:', answeredCorrectly);
            $nextButton.css('display', 'none').addClass('hint-force-hidden');
            
            // Update notice with what's missing
            let message = 'יש ';
            if (!hintViewed && !answeredCorrectly) {
                message += 'לצפות ברמז ולבחור בתשובה הנכונה כדי להמשיך';
            } else if (!hintViewed) {
                message += 'לצפות ברמז כדי להמשיך';
            } else {
                message += 'לבחור בתשובה הנכונה כדי להמשיך';
            }
            
            $question.find('.force-hint-notice').html(message);
        }
    }
    
    /**
     * Hide all next buttons initially
     */
    function hideAllNextButtons() {
        $('.wpProQuiz_listItem').each(function() {
            const $question = $(this);
            const $nextButton = $question.find('.wpProQuiz_button:contains("הבא")');
            
            if ($nextButton.length > 0) {
                $nextButton.css('display', 'none').addClass('hint-force-hidden');
            }
        });
    }
    
    /**
     * Set up mutation observers to handle dynamically added elements
     */
    function setupObservers() {
        // Create an observer instance
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // If new questions are added, apply our logic
                    $(mutation.addedNodes).find('.wpProQuiz_listItem').each(function() {
                        const $question = $(this);
                        
                        // Hide next button
                        const $nextButton = $question.find('.wpProQuiz_button:contains("הבא")');
                        if ($nextButton.length > 0) {
                            $nextButton.css('display', 'none').addClass('hint-force-hidden');
                        }
                        
                        // Add notice
                        if ($question.find('.force-hint-notice').length === 0) {
                            $question.find('.wpProQuiz_question').append(
                                '<div class="force-hint-notice" style="margin-top:10px;font-size:13px;color:#666;font-style:italic;">' +
                                'רמז: יש לצפות ברמז ולבחור בתשובה הנכונה כדי להמשיך' +
                                '</div>'
                            );
                        }
                    });
                }
            });
        });
        
        // Start observing the quiz content for changes
        const quizContent = document.querySelector('.wpProQuiz_content');
        if (quizContent) {
            observer.observe(quizContent, { 
                childList: true, 
                subtree: true 
            });
        }
    }
    
})(jQuery);
