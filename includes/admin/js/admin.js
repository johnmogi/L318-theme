/**
 * Registration Codes Admin JavaScript
 * 
 * Handles all the interactive functionality for the registration codes admin interface.
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initGenerateForm();
        initExportForm();
        initImportForm();
        initTabs();
        initCodeCount();
        initClipboard();
        initGroupManagement();
        initModals();
    });

    /**
     * Initialize the code generation form
     */
    function initGenerateForm() {
        $('#ccr-generate-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $results = $('#ccr-generate-results');
            const $spinner = $form.find('.spinner');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Get count value
            let count = $form.find('#ccr-code-count').val();
            if (count === 'custom') {
                count = $form.find('#ccr-custom-count').val();
            }
            
            const group = $form.find('#ccr-group-name').val();
            
            // Show loading state
            $spinner.addClass('is-active');
            $submitBtn.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: ccrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_registration_codes',
                    nonce: ccrAdmin.nonce,
                    count: count,
                    group: group
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update results info
                        $results.find('.ccr-results-info').html(
                            `<p>${data.count} codes generated successfully for group: <strong>${data.group || 'No Group'}</strong></p>`
                        );
                        
                        // Update textarea with codes
                        $results.find('#ccr-generated-codes').val(data.codes.join('\n'));
                        
                        // Show results
                        $results.show();
                        
                        // Refresh the page after a delay to show the new codes in the list
                        setTimeout(function() {
                            // Don't refresh, just show success message
                            $form.find('.ccr-form-actions').append(
                                `<div class="notice notice-success inline"><p>${ccrAdmin.success}</p></div>`
                            );
                        }, 500);
                    } else {
                        $form.find('.ccr-form-actions').append(
                            `<div class="notice notice-error inline"><p>${ccrAdmin.error}</p></div>`
                        );
                    }
                },
                error: function() {
                    $form.find('.ccr-form-actions').append(
                        `<div class="notice notice-error inline"><p>${ccrAdmin.error}</p></div>`
                    );
                },
                complete: function() {
                    // Hide loading state
                    $spinner.removeClass('is-active');
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize the export form
     */
    function initExportForm() {
        $('#ccr-export-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $spinner = $form.find('.spinner');
            const $submitBtn = $form.find('button[type="submit"]');
            
            const group = $form.find('#ccr-export-group').val();
            const status = $form.find('#ccr-export-status').val();
            
            // Show loading state
            $spinner.addClass('is-active');
            $submitBtn.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: ccrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'export_registration_codes',
                    nonce: ccrAdmin.nonce,
                    group: group,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Convert data to CSV
                        const csv = convertToCSV(data.csv_data);
                        
                        // Download the CSV file
                        downloadCSV(csv, data.filename);
                    } else {
                        $form.find('.ccr-form-actions').append(
                            `<div class="notice notice-error inline"><p>Error exporting codes: ${response.data}</p></div>`
                        );
                    }
                },
                error: function() {
                    $form.find('.ccr-form-actions').append(
                        `<div class="notice notice-error inline"><p>Server error while exporting codes.</p></div>`
                    );
                },
                complete: function() {
                    // Hide loading state
                    $spinner.removeClass('is-active');
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize the import form
     */
    function initImportForm() {
        $('#ccr-import-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $results = $('#ccr-import-results');
            const $spinner = $form.find('.spinner');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Check if file is selected
            const fileInput = document.getElementById('ccr-import-file');
            if (!fileInput.files.length) {
                $form.find('.ccr-form-actions').append(
                    `<div class="notice notice-error inline"><p>Please select a CSV file to import.</p></div>`
                );
                return;
            }
            
            // Create FormData object
            const formData = new FormData();
            formData.append('action', 'import_registration_codes');
            formData.append('nonce', ccrAdmin.nonce);
            formData.append('import_file', fileInput.files[0]);
            formData.append('group', $form.find('#ccr-import-group').val());
            
            // Show loading state
            $spinner.addClass('is-active');
            $submitBtn.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: ccrAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update results info
                        $results.find('.ccr-results-info').html(
                            `<div class="notice notice-success inline"><p>Import completed: ${data.imported} codes imported, ${data.skipped} skipped.</p></div>`
                        );
                        
                        // Show results
                        $results.show();
                        
                        // Refresh the page after a delay to show the new codes in the list
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $results.find('.ccr-results-info').html(
                            `<div class="notice notice-error inline"><p>Error importing codes: ${response.data}</p></div>`
                        );
                        $results.show();
                    }
                },
                error: function() {
                    $results.find('.ccr-results-info').html(
                        `<div class="notice notice-error inline"><p>Server error while importing codes.</p></div>`
                    );
                    $results.show();
                },
                complete: function() {
                    // Hide loading state
                    $spinner.removeClass('is-active');
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.ccr-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            
            // Update active tab button
            $('.ccr-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update active tab content
            $('.ccr-tab-content').removeClass('active');
            $(`#ccr-tab-${tab}`).addClass('active');
        });
    }

    /**
     * Initialize code count selector
     */
    function initCodeCount() {
        $('#ccr-code-count').on('change', function() {
            const value = $(this).val();
            const $customCount = $('#ccr-custom-count');
            
            if (value === 'custom') {
                $customCount.show();
            } else {
                $customCount.hide();
            }
        });
    }

    /**
     * Initialize clipboard functionality
     */
    function initClipboard() {
        $('#ccr-copy-codes').on('click', function() {
            const $textarea = $('#ccr-generated-codes');
            $textarea.select();
            document.execCommand('copy');
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.text('Copied!');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });
        
        $('#ccr-download-codes').on('click', function() {
            const codes = $('#ccr-generated-codes').val().split('\n');
            const csvData = [['Code']];
            
            codes.forEach(function(code) {
                if (code.trim()) {
                    csvData.push([code.trim()]);
                }
            });
            
            const csv = convertToCSV(csvData);
            const group = $('#ccr-group-name').val() || 'no-group';
            const filename = `registration-codes-${group}-${formatDate(new Date())}.csv`;
            
            downloadCSV(csv, filename);
        });
    }

    /**
     * Initialize group management functionality
     */
    function initGroupManagement() {
        // Delete group button click
        $('.ccr-delete-group').on('click', function() {
            const group = $(this).data('group');
            const $modal = $('#ccr-delete-group-modal');
            
            // Set group name in the modal
            $('#ccr-delete-group-name').text(group);
            
            // Show the modal
            $modal.show();
        });
        
        // Confirm delete group button click
        $('#ccr-confirm-delete-group').on('click', function() {
            const group = $('#ccr-delete-group-name').text();
            const $modal = $('#ccr-delete-group-modal');
            const $spinner = $modal.find('.spinner');
            const $buttons = $modal.find('button');
            
            // Show loading state
            $spinner.addClass('is-active');
            $buttons.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                url: ccrAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_group',
                    nonce: ccrAdmin.nonce,
                    group: group
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh the page to update the groups list
                        location.reload();
                    } else {
                        $modal.find('.ccr-modal-actions').prepend(
                            `<div class="notice notice-error inline"><p>Error deleting group: ${response.data}</p></div>`
                        );
                        
                        // Hide loading state
                        $spinner.removeClass('is-active');
                        $buttons.prop('disabled', false);
                    }
                },
                error: function() {
                    $modal.find('.ccr-modal-actions').prepend(
                        `<div class="notice notice-error inline"><p>Server error while deleting group.</p></div>`
                    );
                    
                    // Hide loading state
                    $spinner.removeClass('is-active');
                    $buttons.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize modal functionality
     */
    function initModals() {
        // Close modal when clicking the close button
        $('.ccr-modal-close, .ccr-modal-cancel').on('click', function() {
            $('.ccr-modal').hide();
        });
        
        // Close modal when clicking outside the modal content
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('ccr-modal')) {
                $('.ccr-modal').hide();
            }
        });
        
        // Close modal when pressing escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('.ccr-modal').hide();
            }
        });
    }

    /**
     * Convert array data to CSV string
     */
    function convertToCSV(data) {
        return data.map(function(row) {
            return row.map(function(cell) {
                // Escape quotes and wrap in quotes if contains comma or quote
                if (cell && (cell.includes(',') || cell.includes('"'))) {
                    return '"' + cell.replace(/"/g, '""') + '"';
                }
                return cell;
            }).join(',');
        }).join('\n');
    }

    /**
     * Download CSV data as a file
     */
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (navigator.msSaveBlob) {
            // IE 10+
            navigator.msSaveBlob(blob, filename);
        } else {
            // Other browsers
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.style.display = 'none';
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }

    /**
     * Format date as YYYY-MM-DD
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    }

})(jQuery);
