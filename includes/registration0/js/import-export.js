jQuery(document).ready(function($) {
    'use strict';

    // Handle export form submission
    $('#export-codes-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $spinner = $button.siblings('.spinner');
        
        // Show loading state
        $spinner.addClass('is-active');
        $button.prop('disabled', true);
        
        // Get form data
        var formData = new FormData($form[0]);
        formData.append('action', 'export_registration_codes');
        formData.append('_ajax_nonce', registrationCodes.nonce);
        
        // Send AJAX request
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.responseType = 'blob';
                return xhr;
            },
            success: function(response, status, xhr) {
                // Get filename from Content-Disposition header
                var filename = 'registration-codes-export-' + new Date().toISOString().slice(0, 10) + '.';
                var contentType = xhr.getResponseHeader('content-type');
                
                // Set file extension based on format
                if (contentType && contentType.includes('json')) {
                    filename += 'json';
                } else {
                    filename += 'csv';
                }
                
                // Create download link
                var blob = new Blob([response], { type: contentType });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                // Show success message
                showNotice('success', 'ייצוא הקודים הושלם בהצלחה!');
            },
            error: function(xhr, status, error) {
                var errorMessage = 'שגיאה בייצוא הקודים. אנא נסה שוב.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                showNotice('error', errorMessage);
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Handle import form submission
    $('#import-codes-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $spinner = $button.siblings('.spinner');
        
        // Show loading state
        $spinner.addClass('is-active');
        $button.prop('disabled', true);
        
        // Get form data
        var formData = new FormData($form[0]);
        formData.append('action', 'import_registration_codes');
        formData.append('_ajax_nonce', registrationCodes.nonce);
        
        // Send AJAX request
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var message = response.data && response.data.message ? 
                        response.data.message : 'ייבוא הקודים הושלם בהצלחה!';
                    showNotice('success', message);
                    
                    // Show import results if available
                    if (response.data && response.data.results) {
                        var $results = $('#import-results');
                        var $resultsContent = $('#import-results-content');
                        
                        $resultsContent.html('<pre>' + JSON.stringify(response.data.results, null, 2) + '</pre>');
                        $results.show();
                    }
                } else {
                    var errorMessage = response.data && response.data.message ? 
                        response.data.message : 'אירעה שגיאה בעת ייבוא הקודים';
                    showNotice('error', errorMessage);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'שגיאה בייבוא הקודים. אנא נסה שוב.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                showNotice('error', errorMessage);
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Show notice function
    function showNotice(type, message) {
        var noticeClass = 'notice-' + (type === 'error' ? 'error' : 'success');
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Add close button
        $notice.prepend('<button type="button" class="notice-dismiss"><span class="screen-reader-text">סגור הודעה זו.</span></button>');
        
        // Insert after the first h2
        $('h2').first().after($notice);
        
        // Handle dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(300, function() { $(this).remove(); });
        });
        
        // Auto-dismiss after 10 seconds
        if (type !== 'error') {
            setTimeout(function() {
                $notice.fadeOut(300, function() { $(this).remove(); });
            }, 10000);
        }
    }
    
    // Handle sample CSV download
    $(document).on('click', '#download-sample-csv, #download-sample-csv-file', function(e) {
        e.preventDefault();
        
        var csvContent = 'code,class,role\n' +
                        'ABC123,כיתה יא1,student\n' +
                        'DEF456,כיתה יא1,student\n' +
                        'GHI789,כיתה יב2,student';
        
        var blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'sample-registration-codes.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    });
    
    // Initialize tabs using jQuery UI if available
    if (typeof $.fn.tabs === 'function') {
        $('.import-export-tabs').tabs({
            activate: function(event, ui) {
                // Update URL hash
                window.location.hash = ui.newPanel.attr('id');
                
                // Update active button
                $('.import-export-tab-btn').removeClass('active');
                $('.import-export-tab-btn[data-tab="' + ui.newPanel.attr('id') + '"]').addClass('active');
            }
        });
        
        // Handle direct linking to tab
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            $('.import-export-tabs').tabs('option', 'active', $('#' + hash).index() - 1);
        }
    } else {
        // Fallback to simple tab switching if jQuery UI tabs not available
        $('.import-export-tab-btn').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var tabId = $this.data('tab');
            
            // Update active button
            $('.import-export-tab-btn').removeClass('active');
            $this.addClass('active');
            
            // Show active tab content
            $('.import-export-tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // Update URL hash
            window.location.hash = tabId;
        });
        
        // Handle direct linking to tab
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            $('.import-export-tab-btn[data-tab="' + hash + '"]').trigger('click');
        }
    }
    
    // Initialize the correct tab based on URL hash
    if (!window.location.hash) {
        $('.import-export-tab-btn:first').trigger('click');
    }
});
