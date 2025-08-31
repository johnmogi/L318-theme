/**
 * Disable Hints Script
 * Version 1.0.0
 * 
 * This script checks for the lilac-quiz-disable-hints meta tag
 * and hides all hint buttons if hints are disabled.
 */

jQuery(document).ready(function($) {
    console.log('Disable Hints Script: Initializing...');
    
    // Check if hints should be disabled
    const disableHintsMeta = $('meta[name="lilac-quiz-disable-hints"]');
    const hintsDisabled = disableHintsMeta.length > 0 && disableHintsMeta.attr('content') === 'true';
    
    if (hintsDisabled) {
        console.log('Disable Hints: Hints are disabled for this quiz');
        
        // Function to hide all hint buttons and containers
        function hideAllHints() {
            // Hide all hint buttons
            $('.wpProQuiz_hint, .wpProQuiz_TipButton').css({
                'display': 'none',
                'visibility': 'hidden'
            });
            
            // Hide any open hint containers
            $('.wpProQuiz_tipp').css({
                'display': 'none',
                'visibility': 'hidden'
            });
            
            // Add a class to the body for CSS-based hiding
            $('body').addClass('lilac-hints-disabled');
        }
        
        // Call immediately
        hideAllHints();
        
        // Also call after a delay to catch dynamically added elements
        setTimeout(hideAllHints, 500);
        setTimeout(hideAllHints, 1500);
        
        // Watch for quiz element changes and hide hints on new elements
        const observer = new MutationObserver(function(mutations) {
            hideAllHints();
        });
        
        // Start observing the quiz container
        const quizContainer = $('.wpProQuiz_content')[0];
        if (quizContainer) {
            observer.observe(quizContainer, {
                childList: true,
                subtree: true
            });
        }
        
        // Add a small debug indicator for admins
        if (typeof isAdmin !== 'undefined' && isAdmin) {
            $('body').append(
                '<div id="hint-status-indicator" style="position:fixed;bottom:10px;left:10px;background:#d54e21;color:white;padding:5px 10px;border-radius:4px;font-size:12px;z-index:9999;">' +
                'Hints: Disabled' +
                '</div>'
            );
        }
    } else {
        console.log('Disable Hints: Hints are enabled for this quiz');
        
        // Add a small debug indicator for admins
        if (typeof isAdmin !== 'undefined' && isAdmin) {
            $('body').append(
                '<div id="hint-status-indicator" style="position:fixed;bottom:10px;left:10px;background:#46b450;color:white;padding:5px 10px;border-radius:4px;font-size:12px;z-index:9999;">' +
                'Hints: Enabled' +
                '</div>'
            );
        }
    }
});
