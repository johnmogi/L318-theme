/**
 * Force Hint Mode Implementation - Clean Version
 * Created for LILAC quiz system - version 3.0.3
 */
(function($) {
    'use strict';
    
    console.log('Force Hint Mode v3.0.3 loaded');
    
    // Simple global cache of viewed hints
    const viewedHints = {};
    let forceHintActive = false;
    
    /**
     * Initialize the plugin
     */
    function init() {
        // Only continue if we're on a quiz page
        if ($('.wpProQuiz_content').length === 0) return;
        
        // Add toggle button
        addToggleButton();
        
        // Auto-enable if meta tag is present
        if ($('meta[name="lilac-quiz-force-hint"]').attr('content') === 'true') {
            $('#force-hint-toggle').click();
        }
        
        // Track when hints are viewed
        $(document).on('click', '.wpProQuiz_QuestionButton[name="tip"]', onHintClick);
        
        // Watch for answer checks
        $(document).on('click', '.wpProQuiz_QuestionButton[name="check"]', function() {
            if (!forceHintActive) return;
            
            const $question = $(this).closest('.wpProQuiz_listItem');
            
            // Give LearnDash time to update correct/incorrect feedback
            setTimeout(function() {
                checkButtonVisibility($question);
            }, 300);
        });
    }
    
    /**
     * Add the toggle button to the page
     */
    function addToggleButton() {
        const $toggle = $(
            '<button id="force-hint-toggle" ' +
            'style="position:fixed; bottom:10px; right:10px; ' +
            'background:#3498db; color:white; padding:5px 10px; ' +
            'border:none; border-radius:3px; font-size:12px; ' +
            'z-index:999999; cursor:pointer;">' +
            'Force Hint: OFF' +
            '</button>'
        );
        
        $('body').append($toggle);
        
        $toggle.on('click', function() {
            forceHintActive = !forceHintActive;
            updateToggleButton($toggle, forceHintActive);
            
            if (forceHintActive) {
                activateForceHintMode();
            } else {
                deactivateForceHintMode();
            }
        });
    }
    
    /**
     * Update the toggle button state
     * @param {jQuery} $button - The button element
     * @param {boolean} isActive - Whether the force hint mode is active
     */
    function updateToggleButton($button, isActive) {
        $button.text('Force Hint: ' + (isActive ? 'ON' : 'OFF'));
        $button.css('background', isActive ? '#e74c3c' : '#3498db');
    }
    
    /**
     * Handle hint button click
     */
    function onHintClick() {
        const $question = $(this).closest('.wpProQuiz_listItem');
        const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
        
        if (questionId) {
            // Mark hint as viewed
            viewedHints[questionId] = true;
            $(this).addClass('hint-viewed');
            
            // If force hint mode is active, check if we should show next button
            if (forceHintActive) {
                checkButtonVisibility($question);
            }
        }
    }
    
    /**
     * Activate force hint mode
     */
    function activateForceHintMode() {
        console.log('Activating Force Hint Mode');
        
        // Add active class to body
        $('body').addClass('force-hint-active');
        
        // Add styles
        addStyles();
        
        // Check all questions
        $('.wpProQuiz_listItem').each(function() {
            checkButtonVisibility($(this));
        });
    }
    
    /**
     * Deactivate force hint mode
     */
    function deactivateForceHintMode() {
        console.log('Deactivating Force Hint Mode');
        
        // Remove active class from body
        $('body').removeClass('force-hint-active');
        
        // Show all next buttons
        $('.wpProQuiz_QuestionButton[name="next"]').show();
        
        // Remove hint required messages
        $('.hint-required-message').remove();
    }
    
    /**
     * Check if next button should be visible
     * @param {jQuery} $question - The question element
     */
    function checkButtonVisibility($question) {
        const questionId = $question.find('.wpProQuiz_questionList').data('question_id');
        const $nextButton = $question.find('.wpProQuiz_QuestionButton[name="next"]');
        const $hintButton = $question.find('.wpProQuiz_QuestionButton[name="tip"]');
        
        // If no next button, nothing to do
        if ($nextButton.length === 0) return;
        
        // If hint was viewed, show next button
        if (viewedHints[questionId]) {
            $nextButton.show();
            $hintButton.removeClass('hint-required');
            $question.find('.hint-required-message').remove();
        } else {
            // Hide next button and show hint required message
            $nextButton.hide();
            $hintButton.addClass('hint-required');
            
            // Add hint required message if not present
            if ($question.find('.hint-required-message').length === 0) {
                $hintButton.after(
                    '<div class="hint-required-message" style="color: #e74c3c; font-size: 0.9em; margin-top: 5px;">' +
                    'Please view the hint before continuing.' +
                    '</div>'
                );
            }
        }
    }
    
    /**
     * Add necessary styles
     */
    function addStyles() {
        // Only add styles once
        if ($('#force-hint-styles').length > 0) return;
        
        const styles = [
            '/* Force Hint Mode Styles */',
            '.force-hint-active .wpProQuiz_QuestionButton[name="next"] {',
            '    display: none !important;',
            '}',
            '',
            '.force-hint-active .hint-required {',
            '    background-color: #f39c12 !important;',
            '    color: white !important;',
            '    animation: pulse 2s infinite;',
            '}',
            '',
            '.hint-required-message {',
            '    color: #e74c3c;',
            '    font-size: 0.9em;',
            '    margin-top: 5px;',
            '}',
            '',
            '@keyframes pulse {',
            '    0% { opacity: 1; }',
            '    50% { opacity: 0.7; }',
            '    100% { opacity: 1; }',
            '}'
        ].join('\n');
        
        $('<style id="force-hint-styles">' + styles + '</style>').appendTo('head');
    }
    
    // Initialize the plugin when document is ready
    $(document).ready(init);
    
    // Export to global scope if needed
    window.forceHintMode = {
        activate: function() {
            forceHintActive = true;
            $('body').addClass('force-hint-active');
            updateToggleButton($('#force-hint-toggle'), true);
            activateForceHintMode();
        },
        deactivate: function() {
            forceHintActive = false;
            $('body').removeClass('force-hint-active');
            updateToggleButton($('#force-hint-toggle'), false);
            deactivateForceHintMode();
        }
    };

})(jQuery);
