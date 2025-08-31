/**
 * Simple Hint Marker Script
 * A minimal approach for highlighting quiz hints
 */
jQuery(document).ready(function($) {
    console.log('Simple Hint Marker loaded');
    
    // Hide all hint containers on page load by removing hint-visible class
    $('.wpProQuiz_tipp').removeClass('hint-visible');
    
    // Reset any highlight-hint elements to inactive state
    $('.highlight-hint').removeClass('active');
    
    // Remove any pre-existing backgrounds
    $('.wpProQuiz_tipp p').css('background-color', '');
    
    // Handle native LearnDash hint button clicks
    $(document).on('click', '.wpProQuiz_hint, .wpProQuiz_TipButton', function(e) {
        // Stop if it's a programmatic trigger (not a real user click)
        if (!e.originalEvent) return;
        
        console.log('Simple Hint Marker: Hint button clicked by user');
        
        // Toggle hint visibility using the hint-visible class
        var $hintContainer = $(this).closest('.wpProQuiz_listItem').find('.wpProQuiz_tipp');
        
        if ($hintContainer.hasClass('hint-visible')) {
            $hintContainer.removeClass('hint-visible');
        } else {
            $hintContainer.addClass('hint-visible');
        }
    });
    
    // Handle the mark-hint button click
    $(document).on('click', '#mark-hint', function(e) {
        // Stop if it's a programmatic trigger (not a real user click)
        if (!e.originalEvent) return;
        
        console.log('Mark hint button clicked by user');
        
        // Find the container that holds the hint content
        var $hintContainer = $(this).closest('.wpProQuiz_tipp').find('div');
        
        // First, reset all paragraph styling
        $hintContainer.find('p').css('background-color', '');
        
        // Reset any highlight-hint elements to inactive state
        $('.highlight-hint').removeClass('active');
        
        // Find all paragraphs
        var $paragraphs = $hintContainer.find('p');
        
        // Try to find a suitable paragraph to highlight
        var $targetParagraph = null;
        
        // Check if there are any highlight-hint elements to activate
        var $existingHighlightHints = $hintContainer.find('.highlight-hint');
        if ($existingHighlightHints.length > 0) {
            // Activate the existing highlight-hint elements
            $existingHighlightHints.addClass('active');
            
            // Scroll to the first highlighted hint
            scrollToHighlight($existingHighlightHints.first());
            return;
        }
        
        // Look for the paragraph that contains the most important information
        $paragraphs.each(function(i, p) {
            var $p = $(p);
            var text = $p.text();
            
            // Otherwise, look for substantive paragraphs (more than 40 chars)
            if (text.length > 40 && !$targetParagraph) {
                $targetParagraph = $p;
            }
        });
        
        // If we couldn't find a suitable paragraph, just use the third one or the first one
        if (!$targetParagraph && $paragraphs.length >= 3) {
            $targetParagraph = $($paragraphs[2]); // Third paragraph
        } else if (!$targetParagraph && $paragraphs.length > 0) {
            $targetParagraph = $($paragraphs[0]); // First paragraph
        }
        
        // Apply yellow background if we found a paragraph
        if ($targetParagraph) {
            // Add markHint ID which will trigger the CSS styling
            $targetParagraph.attr('id', 'markHint');
            
            // Scroll to the paragraph
            scrollToHighlight($targetParagraph);
        }
    });
    
    // Helper function to scroll to highlighted element
    function scrollToHighlight($element) {
        if ($element && $element.length > 0) {
            $('html, body').animate({
                scrollTop: $element.offset().top - 100
            }, 300);
            
            // Add a flash effect to draw attention
            $element.fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
        }
    }
    
    // Handle the close-hint button
    $(document).on('click', '#close-hint', function(e) {
        // Stop if it's a programmatic trigger (not a real user click)
        if (!e.originalEvent) return;
        
        // Hide the hint container by removing the hint-visible class
        $(this).closest('.wpProQuiz_tipp').removeClass('hint-visible');
    });
});
