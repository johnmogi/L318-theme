/**
 * Hint Initialization Script
 * This script runs after everything else is loaded to ensure hints are properly hidden
 */
(function($) {
    // Global flag to track if a user has manually clicked a hint button
    window.userClickedHint = false;

    // Track real user clicks on hint buttons
    $(document).on('click', '.wpProQuiz_hint, .wpProQuiz_TipButton, #mark-hint', function(e) {
        if (e.originalEvent) {
            window.userClickedHint = true;
            console.log('Hint Initializer: User manually clicked a hint button');
            
            // Stop the observer temporarily to let the hint system work
            if (window.hintObserver) {
                window.hintObserver.disconnect();
                console.log('Hint Initializer: Mutation observer temporarily disabled');
                
                // Re-enable after 2 seconds to continue monitoring, but respect user clicks
                setTimeout(function() {
                    setupObserver();
                    console.log('Hint Initializer: Mutation observer re-enabled with user click awareness');
                }, 2000);
            }
        }
    });

    // Function to clean up hints
    function cleanupHints() {
        console.log('Hint Initializer: Cleaning up hints');
        
        // Only hide hints if the user hasn't clicked a hint button
        if (!window.userClickedHint) {
            // Forcefully hide all hint containers
            $('.wpProQuiz_tipp').removeClass('hint-visible').hide();
            
            // Remove any pre-existing background colors or styles
            $('.wpProQuiz_tipp p').css({
                'background-color': '',
                'padding': '',
                'display': ''
            });
            
            // Remove any acf-hint-content elements that don't have user-generated content
            $('.acf-hint-content:empty').remove();
            
            // Remove any markHint IDs
            $('#markHint').removeAttr('id');
            
            console.log('Hint Initializer: All hints have been hidden and styling reset');
        } else {
            console.log('Hint Initializer: User has clicked a hint button, preserving hint visibility');
        }
    }

    // Setup mutation observer to detect hint containers that might be dynamically added
    function setupObserver() {
        // Disconnect previous observer if it exists
        if (window.hintObserver) {
            window.hintObserver.disconnect();
        }
        
        // Create a new observer
        window.hintObserver = new MutationObserver(function(mutations) {
            var shouldCleanup = false;
            
            mutations.forEach(function(mutation) {
                // Only process if the user hasn't clicked a hint button
                if (!window.userClickedHint) {
                    // If attribute changes on hint container (style, display, etc.)
                    if (mutation.type === 'attributes' && 
                        (mutation.target.classList.contains('wpProQuiz_tipp') || 
                         $(mutation.target).find('.wpProQuiz_tipp').length > 0)) {
                        shouldCleanup = true;
                        console.log('Hint Initializer: Detected attribute change on hint container');
                    }
                    
                    // If new nodes are added that contain hint elements
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        for (var i = 0; i <mutation.addedNodes.length; i++) {
                            var node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && // Element node
                                ($(node).hasClass('wpProQuiz_tipp') || 
                                 $(node).find('.wpProQuiz_tipp').length > 0)) {
                                shouldCleanup = true;
                                console.log('Hint Initializer: Detected new hint container');
                                break;
                            }
                        }
                    }
                }
            });
            
            // Only perform cleanup if we detected hint-related changes and user hasn't clicked a hint
            if (shouldCleanup && !window.userClickedHint) {
                cleanupHints();
            }
        });
        
        // Start observing for hint containers
        window.hintObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    // Wait until the document is fully loaded
    $(window).on('load', function() {
        // Only perform initial cleanup if user hasn't clicked a hint
        if (!window.userClickedHint) {
            cleanupHints();
        }
        
        // Setup the observer
        setupObserver();
        
        // Monitor for programmatic click events on hint buttons
        // This helps prevent automatic hint display on page load
        var originalClick = $.fn.click;
        $.fn.click = function() {
            if (this.hasClass('wpProQuiz_hint') || 
                this.hasClass('wpProQuiz_TipButton') || 
                this.attr('id') === 'mark-hint') {
                console.log('Hint Initializer: Detected programmatic click on hint button');
                
                // If this is a programmatic click (not from user)
                if (!window.userClickedHint) {
                    console.log('Hint Initializer: Blocking automatic hint display');
                    return this; // Prevent the click from happening
                }
            }
            return originalClick.apply(this, arguments);
        };
        
        console.log('Hint Initializer: Setup complete with ongoing monitoring');
    });
})(jQuery);
