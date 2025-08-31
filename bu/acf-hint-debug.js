/**
 * ACF Hint Debug Script
 * This script will output debug information to help diagnose ACF hint issues
 */
jQuery(document).ready(function($) {
    console.log('ACF HINT DEBUG: Script loaded and initialized');
    
    // Check if we're on a quiz page
    var hasQuizElements = $('.wpProQuiz_content').length > 0;
    console.log('ACF HINT DEBUG: Quiz elements found on page:', hasQuizElements);
    
    if (hasQuizElements) {
        // Log quiz questions
        var questionCount = $('.wpProQuiz_listItem').length;
        console.log('ACF HINT DEBUG: Found ' + questionCount + ' quiz questions');
        
        // Check for hint buttons
        var hintButtonCount = $('.wpProQuiz_hint').length;
        var markHintButtonCount = $('#mark-hint').length;
        console.log('ACF HINT DEBUG: Found ' + hintButtonCount + ' hint buttons and ' + markHintButtonCount + ' mark-hint buttons');
        
        // Add visual indicator that our script is running
        $('body').append('<div id="acf-hint-debug-indicator" style="position: fixed; top: 10px; right: 10px; background-color: #ff5722; color: white; padding: 5px 10px; border-radius: 5px; z-index: 9999; font-size: 12px;">ACF Hint Debug Active</div>');
        
        // Add strong event listeners to both button types
        $(document).on('click', '.wpProQuiz_hint', function(e) {
            console.log('ACF HINT DEBUG: Native hint button clicked');
            console.log('ACF HINT DEBUG: Button data:', {
                question_id: $(this).data('question_id'),
                classList: this.className,
                element: this
            });
            
            // Get question item
            var $questionItem = $(this).closest('.wpProQuiz_listItem');
            var questionMetaRaw = $questionItem.data('question-meta');
            console.log('ACF HINT DEBUG: Question meta raw:', questionMetaRaw);
            
            // Highlight the button to show it was clicked
            $(this).css('outline', '3px solid red');
            
            // Update debug indicator
            $('#acf-hint-debug-indicator').html('Hint Button Clicked').css('background-color', '#4caf50');
        });
        
        $(document).on('click', '#mark-hint', function(e) {
            console.log('ACF HINT DEBUG: Custom mark-hint button clicked');
            console.log('ACF HINT DEBUG: Button element:', this);
            
            // Get question item
            var $questionItem = $(this).closest('.wpProQuiz_listItem');
            var questionMetaRaw = $questionItem.data('question-meta');
            console.log('ACF HINT DEBUG: Question meta raw:', questionMetaRaw);
            
            // Highlight the button to show it was clicked
            $(this).css('outline', '3px solid blue');
            
            // Update debug indicator
            $('#acf-hint-debug-indicator').html('Mark Hint Button Clicked').css('background-color', '#2196f3');
        });
        
        // Check AJAX setup
        console.log('ACF HINT DEBUG: AJAX URL available:', typeof ldvars !== 'undefined' && ldvars.ajaxurl ? 'Yes' : 'No');
        if (typeof ldvars !== 'undefined') {
            console.log('ACF HINT DEBUG: ldvars object:', ldvars);
        }
    }
});
