/**
 * Simple Quiz Hint and Button Fix
 * 
 * Minimal version to fix two specific issues:
 * 1. Ensures hint appears correctly on the first question
 * 2. Hides the Next button when answers are incorrect
 */
(function() {
    // Wait for jQuery and document ready
    function checkJQuery() {
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(init);
        } else {
            setTimeout(checkJQuery, 100);
        }
    }
    
    checkJQuery();
    
    // Run the fix immediately and after page load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(runFix, 500);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(runFix, 500);
        });
    }
    
    // Also run it periodically to catch any changes
    setTimeout(runFix, 1000);
    setTimeout(runFix, 2000);
    
    function init() {
        var $ = jQuery;
        console.log('[SIMPLE-FIX] Initializing quiz fix');
        
        // Run fix periodically
        runFix();
        setInterval(runFix, 2000);
        
        // Event handlers for clicks
        $(document).on('click', '.wpProQuiz_questionInput, .wpProQuiz_button', function() {
            setTimeout(runFix, 200);
        });
    }
    
    function runFix() {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        
        console.log('[SIMPLE-FIX] Running fix...');
        
        // 1. Make sure hint buttons are visible
        $('.wpProQuiz_TipButton, input[name="tip"]').each(function() {
            var $button = $(this);
            $button.show();
            $button.css({
                'display': 'inline-block',
                'visibility': 'visible'
            });
        });
        
        // 2. If incorrect answer is shown, hide next button
        $('.wpProQuiz_incorrect').each(function() {
            var $incorrectDiv = $(this);
            if ($incorrectDiv.is(':visible')) {
                var $questionItem = $incorrectDiv.closest('.wpProQuiz_listItem');
                var $nextButton = $questionItem.find('.wpProQuiz_button[name="next"]');
                
                if ($nextButton.length) {
                    $nextButton.hide();
                    console.log('[SIMPLE-FIX] Hidden next button on incorrect answer');
                }
            }
        });
        
        // 3. If correct answer is shown, make sure next button is visible
        $('.wpProQuiz_correct').each(function() {
            var $correctDiv = $(this);
            if ($correctDiv.is(':visible')) {
                var $questionItem = $correctDiv.closest('.wpProQuiz_listItem');
                var $nextButton = $questionItem.find('.wpProQuiz_button[name="next"]');
                
                if ($nextButton.length) {
                    $nextButton.show();
                    console.log('[SIMPLE-FIX] Showing next button on correct answer');
                }
            }
        });
        
        // 4. Enable any disabled inputs
        $('.wpProQuiz_questionInput:disabled').prop('disabled', false);
    }
})();
