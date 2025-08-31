/**
 * Handles the subscription toggle functionality for course access
 */
jQuery(document).ready(function($) {
    'use strict';

    var $messageContainer = $('#subscription-message');
    var isProcessing = false;

    // Handle course subscription toggle
    $(document).on('change', '.course-subscription-toggle', function() {
        if (isProcessing) return;
        
        var $toggle = $(this);
        var $container = $toggle.closest('.course-item');
        var $status = $container.find('.subscription-status');
        var $expiryContainer = $container.find('.subscription-expiry');
        var orderId = $toggle.data('order-id');
        var courseId = $toggle.data('course-id');
        var isChecked = $toggle.is(':checked');
        
        isProcessing = true;
        
        // Update UI immediately for better UX
        $status.text(lilacSubscription.i18n.updating);
        $messageContainer.removeClass('notice-success notice-error').empty();
        
        // Make AJAX request
        $.ajax({
            url: lilacSubscription.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_subscription',
                order_id: orderId,
                course_id: courseId,
                subscribed: isChecked,
                security: lilacSubscription.nonce
            },
            success: function(response) {
                if (response.success) {
                    var messageClass = isChecked ? 'notice-success' : 'notice-info';
                    $messageContainer.html(
                        '<div class="notice ' + messageClass + '"><p>' + 
                        response.data.message + '</p></div>'
                    );
                    
                    // Update status text
                    $status.text(isChecked ? 
                        lilacSubscription.i18n.active : 
                        lilacSubscription.i18n.inactive
                    );
                    
                    // Update expiry date if available
                    if (isChecked && response.data.expiry_date) {
                        if ($expiryContainer.length) {
                            $expiryContainer.show().find('span').html(
                                lilacSubscription.i18n.expires + ' ' + 
                                '<strong>' + response.data.expiry_date + '</strong>'
                            );
                        } else {
                            $toggle.closest('.subscription-toggle').append(
                                '<div class="subscription-expiry" style="margin-top: 10px; padding: 8px; background: #e6f7ee; border-radius: 4px; display: inline-block;">' +
                                '<span style="color: #0e6245; font-size: 0.9em;">' +
                                lilacSubscription.i18n.expires + ' ' +
                                '<strong>' + response.data.expiry_date + '</strong>' +
                                '</span></div>'
                            );
                        }
                    } else {
                        $expiryContainer.hide();
                    }
                    
                    // Update toggle state in case it was changed by the server
                    $toggle.prop('checked', isChecked);
                    
                } else {
                    // Revert the toggle if there was an error
                    $toggle.prop('checked', !isChecked);
                    $messageContainer.html(
                        '<div class="notice notice-error"><p>' + 
                        (response.data && response.data.message || lilacSubscription.i18n.error) + 
                        '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                // Revert the toggle on error
                $toggle.prop('checked', !isChecked);
                console.error('Subscription update error:', error);
                $messageContainer.html(
                    '<div class="notice notice-error"><p>' + 
                    lilacSubscription.i18n.error + 
                    ' (' + error + ')</p></div>'
                );
            },
            complete: function() {
                isProcessing = false;
                
                // Auto-hide success messages after 5 seconds
                if ($messageContainer.find('.notice-success').length) {
                    setTimeout(function() {
                        $messageContainer.fadeOut(500, function() {
                            $(this).empty().show();
                        });
                    }, 5000);
                }
            }
        });
    });
});
