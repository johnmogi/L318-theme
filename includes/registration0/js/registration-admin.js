/**
 * Registration Admin JavaScript
 * Handles client-side functionality for the registration admin interface
 */

jQuery(document).ready(function($) {
    // Initialize datepicker on any datepicker field
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }

    // Handle generate codes form submission
    $('#generate-codes-form').on('submit', function(e) {
        e.preventDefault();
        
        // Show spinner
        var $spinner = $(this).find('.spinner');
        $spinner.addClass('is-active');
        
        // Disable submit button
        var $submitButton = $(this).find('#generate-codes-button');
        $submitButton.attr('disabled', true);
        
        // Collect form data
        var formData = {
            action: 'registration_generate_codes',
            nonce: registrationAdmin.nonce,
            count: $('#code-count').val(),
            role: $('#code-role').val(),
            group_name: $('#code-group').val(),
            course_id: $('#code-course').val(),
            max_uses: $('#code-max-uses').val(),
            expiry_date: $('#code-expiry').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: registrationAdmin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                // Hide spinner and enable button
                $spinner.removeClass('is-active');
                $submitButton.attr('disabled', false);
                
                if (response.success) {
                    // Display the generated codes
                    displayGeneratedCodes(response.data.codes);
                    
                    // Show success message
                    showNotice('success', response.data.message);
                } else {
                    // Show error message
                    showNotice('error', response.data.message || registrationAdmin.i18n.error_occurred);
                }
            },
            error: function() {
                // Hide spinner and enable button
                $spinner.removeClass('is-active');
                $submitButton.attr('disabled', false);
                
                // Show error message
                showNotice('error', registrationAdmin.i18n.error_occurred);
            }
        });
    });
    
    // Handle copy codes button
    $(document).on('click', '#copy-codes-button', function() {
        var codeText = '';
        
        // Get all code elements
        $('.code-output code').each(function() {
            codeText += $(this).text() + "\n";
        });
        
        // Copy to clipboard
        copyToClipboard(codeText);
        
        // Show success message
        showNotice('success', 'Codes copied to clipboard');
    });
    
    // Handle export CSV button
    $(document).on('click', '#export-codes-csv', function() {
        var csvContent = 'Code,Role,Group,Course ID,Max Uses,Expiry Date\n';
        
        // Loop through the stored codes
        if (window.generatedCodes && window.generatedCodes.length > 0) {
            window.generatedCodes.forEach(function(code) {
                csvContent += [
                    code.code,
                    code.role,
                    code.group_name || '',
                    code.course_id || '',
                    code.max_uses,
                    code.expiry_date || ''
                ].join(',') + '\n';
            });
            
            // Create download link
            var encodedCSV = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
            var downloadLink = document.createElement('a');
            downloadLink.href = encodedCSV;
            downloadLink.download = 'registration-codes.csv';
            
            // Trigger download
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });
    
    /**
     * Display generated codes
     * 
     * @param {Array} codes Array of code objects
     */
    function displayGeneratedCodes(codes) {
        // Store codes globally for export
        window.generatedCodes = codes;
        
        var $container = $('#generated-codes-container');
        var $output = $container.find('.code-output');
        
        // Clear previous codes
        $output.empty();
        
        // Add each code
        codes.forEach(function(code) {
            $output.append('<code>' + code.code + '</code>');
        });
        
        // Show container
        $container.show();
    }
    
    /**
     * Copy text to clipboard
     * 
     * @param {string} text Text to copy
     */
    function copyToClipboard(text) {
        // Create temporary textarea
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        // Copy text
        document.execCommand('copy');
        
        // Remove temporary element
        $temp.remove();
    }
    
    /**
     * Show admin notice
     * 
     * @param {string} type Notice type (success, error, warning, info)
     * @param {string} message Notice message
     */
    function showNotice(type, message) {
        // Remove any existing notices
        $('.registration-notice').remove();
        
        // Create notice
        var $notice = $('<div class="notice registration-notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Add dismiss button
        var $button = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        $button.on('click', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
        
        $notice.append($button);
        
        // Insert notice at the top of the page
        $('.wrap.registration-admin-wrap').prepend($notice);
    }
});
