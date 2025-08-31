/**
 * Ultimate Member Integration Scripts
 */
jQuery(document).ready(function($) {
    // Wait a short delay to ensure UM is fully loaded
    setTimeout(function() {
        // Handle registration flow visibility
        if ($('.ccr-code-form').length) {
            // Hide the registration form initially until code is verified
            $('.um-register').hide();
            
            // If there's a success message, show the form
            if ($('.ccr-code-verified').length) {
                $('.um-register').show();
            }
        }
        
        // Handle form submission
        $('.ccr-code-form').on('submit', function() {
            // Add loading state
            $(this).find('button').prop('disabled', true).text('מאמת קוד...');
        });
        
        // Force check if we need to show/hide forms
        if ($('.ccr-code-verified').length) {
            console.log('Code verified, showing registration form');
            $('.um-register').show();
        } else if ($('.ccr-code-form').length) {
            console.log('Code form exists, hiding registration form');
            $('.um-register').hide();
        }
    }, 500);
});
