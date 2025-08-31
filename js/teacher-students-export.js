/**
 * Teacher Students Export
 * Handles the export functionality for the teacher's students list
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if the export button exists
    if ($('#export-students-csv').length === 0) {
        return;
    }
    
    // Initialize the export functionality
    initExportButton();
    
    /**
     * Initialize the export button click handler
     */
    function initExportButton() {
        var $button = $('#export-students-csv');
        var $status = $('.export-status');
        
        $button.on('click', function(e) {
            e.preventDefault();
            
            // Disable button and show loading state
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="dashicons dashicons-update-alt spin" style="margin-right: 5px;"></span> ' + 
                teacherStudentsExport.i18n.exporting
            );
            
            // Update status
            $status.text(teacherStudentsExport.i18n.preparing).show();
            
            // Get the current filter
            var filter = $('#teacher-students-class-filter').val() || 'all';
            
            // Prepare data for export
            var data = {
                'action': 'export_teacher_students',
                'class_filter': filter,
                'nonce': teacherStudentsExport.nonce
            };
            
            // Send AJAX request
            $.ajax({
                url: teacherStudentsExport.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                timeout: 300000, // 5 minutes timeout for large exports
                
                success: function(response) {
                    if (response.success && response.data && response.data.url) {
                        // Create a temporary link to trigger download
                        var a = document.createElement('a');
                        a.href = response.data.url;
                        a.download = response.data.filename || 'students-export.csv';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        
                        // Show success message
                        showStatusMessage(teacherStudentsExport.i18n.complete, 'success');
                    } else {
                        // Show error message
                        var errorMsg = response.data && response.data.message ? 
                            response.data.message : teacherStudentsExport.i18n.error;
                        showStatusMessage(errorMsg, 'error');
                        
                        // Log error to console
                        console.error('Export error:', response);
                    }
                },
                
                error: function(xhr, status, error) {
                    // Show error message
                    var errorMsg = xhr.statusText || teacherStudentsExport.i18n.connectionError;
                    showStatusMessage(errorMsg, 'error');
                    
                    // Log error to console
                    console.error('AJAX error:', status, error);
                },
                
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false).html(originalText);
                    
                    // Clear status after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut(500, function() {
                            $status.text('').show();
                        });
                    }, 5000);
                }
            });
        });
    }
    
    /**
     * Show a status message
     * 
     * @param {string} message The message to display
     * @param {string} type The message type (success, error, warning, info)
     */
    function showStatusMessage(message, type) {
        var $status = $('.export-status');
        var icon = '';
        var color = '';
        
        // Set icon and color based on message type
        switch (type) {
            case 'success':
                icon = 'dashicons dashicons-yes-alt';
                color = '#155724';
                break;
            case 'error':
                icon = 'dashicons dashicons-warning';
                color = '#721c24';
                break;
            case 'warning':
                icon = 'dashicons dashicons-warning';
                color = '#856404';
                break;
            default: // info
                icon = 'dashicons dashicons-info';
                color = '#0c5460';
        }
        
        // Update status message
        $status.html(
            '<span class="dashicons ' + icon + '" style="vertical-align: middle; margin-left: 5px;"></span> ' +
            '<span style="vertical-align: middle;">' + message + '</span>'
        ).css({
            'color': color,
            'display': 'inline-block'
        }).fadeIn();
    }
    
    /**
     * Helper function to download a file from a URL
     * 
     * @param {string} url The URL of the file to download
     * @param {string} filename The suggested filename for the download
     */
    function downloadFile(url, filename) {
        var a = document.createElement('a');
        a.href = url;
        a.download = filename || 'download';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // Add click handler for any existing export links with class .export-students-link
    $(document).on('click', '.export-students-link', function(e) {
        e.preventDefault();
        var url = $(this).data('url') || $(this).attr('href');
        var filename = $(this).data('filename') || 'students-export.csv';
        downloadFile(url, filename);
    });
    
});
