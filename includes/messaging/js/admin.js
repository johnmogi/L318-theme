/**
 * Messaging System Admin JavaScript
 */
(function($) {
    'use strict';

    // Initialize the admin interface when document is ready
    $(document).ready(function() {
        initTabs();
        initColorPickers();
        initMessagePreview();
        initFormValidation();
    });

    /**
     * Initialize tab switching functionality
     */
    function initTabs() {
        $('.messaging-admin-tabs .tab').on('click', function() {
            // Remove active class from all tabs
            $('.messaging-admin-tabs .tab').removeClass('active');
            // Add active class to clicked tab
            $(this).addClass('active');
            
            // Hide all tab content
            $('.messaging-admin-content .tab-content').removeClass('active').hide();
            
            // Show the selected tab content
            const tabId = $(this).data('tab');
            $('#' + tabId).addClass('active').show();
            
            // Store the active tab in localStorage
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('messagingActiveTab', tabId);
            }
        });
        
        // Check if there's a saved active tab
        if (typeof localStorage !== 'undefined') {
            const activeTab = localStorage.getItem('messagingActiveTab');
            if (activeTab) {
                $('.messaging-admin-tabs .tab[data-tab="' + activeTab + '"]').trigger('click');
            } else {
                // Default to first tab
                $('.messaging-admin-tabs .tab:first').trigger('click');
            }
        } else {
            // Default to first tab
            $('.messaging-admin-tabs .tab:first').trigger('click');
        }
    }
    
    /**
     * Initialize color picker for theme colors
     */
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Update color preview
                    const colorPreview = $(this).closest('.messaging-form-row').find('.color-preview');
                    if (colorPreview.length) {
                        colorPreview.css('background-color', ui.color.toString());
                    }
                    
                    // Update message preview if this is a toast color
                    updateMessagePreview();
                }
            });
        }
    }
    
    /**
     * Initialize the live message preview
     */
    function initMessagePreview() {
        // Update preview when form fields change
        $('#message-title, #message-content, #message-duration, #message-position').on('input change', function() {
            updateMessagePreview();
        });
        
        // Initial preview update
        updateMessagePreview();
    }
    
    /**
     * Update the message preview based on current form values
     */
    function updateMessagePreview() {
        const title = $('#message-title').val() || 'Message Title';
        const content = $('#message-content').val() || 'This is a preview of your message content.';
        const position = $('#message-position').val() || 'top-right';
        
        // Get colors if color picker is active
        let bgColor = '#f8f9fa';
        let textColor = '#212529';
        let borderColor = '#dee2e6';
        
        if ($.fn.wpColorPicker) {
            bgColor = $('#toast-bg-color').val() || bgColor;
            textColor = $('#toast-text-color').val() || textColor;
            borderColor = $('#toast-border-color').val() || borderColor;
        }
        
        // Update the preview
        const $preview = $('.message-preview-box');
        $preview.css({
            'background-color': bgColor,
            'color': textColor,
            'border-color': borderColor,
            'position': 'relative'
        });
        
        $preview.html(
            '<div class="preview-title" style="font-weight: bold; margin-bottom: 8px;">' + title + '</div>' +
            '<div class="preview-content">' + content + '</div>'
        );
        
        // Set position class
        $preview.removeClass('top-right top-left bottom-right bottom-left');
        $preview.addClass(position);
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        $('.messaging-admin-form').on('submit', function(e) {
            let valid = true;
            
            // Basic validation
            $(this).find('input[required], select[required], textarea[required]').each(function() {
                if (!$(this).val()) {
                    valid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }

})(jQuery);
