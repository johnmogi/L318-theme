jQuery(document).ready(function($) {
    'use strict';

    // Import state
    let importInProgress = false;
    let currentChunk = 0;
    let totalChunks = 0;
    let totalRows = 0;
    let processedRows = 0;
    let successCount = 0;
    let errorCount = 0;
    let errorMessages = [];
    let currentFilename = '';
    let userType = '';
    
    // Initialize
    initImportForm();
    
    /**
     * Initialize the import form
     */
    function initImportForm() {
        // Handle form submission
        $('#ccr-import-form').on('submit', function(e) {
            e.preventDefault();
            
            if (importInProgress) {
                return false;
            }
            
            // Reset state
            resetImportState();
            
            // Get form data
            const fileInput = $('#import_file')[0];
            
            if (!fileInput.files.length) {
                showError(ccrImport.i18n.select_file);
                return false;
            }
            
            userType = $('#user_type').val();
            if (!userType) {
                showError(ccrImport.i18n.select_user_type);
                return false;
            }
            
            // Show progress UI
            $('.import-progress').show();
            updateProgress(0, ccrImport.i18n.uploading);
            
            // Start the import process
            startImport(fileInput.files[0], userType);
        });
        
        // Handle user type change
        $('#user_type').on('change', function() {
            toggleRequiredFields($(this).val());
        });
    }
    
    /**
     * Toggle required fields based on user type
     */
    function toggleRequiredFields(selectedType) {
        // Reset all field indicators
        $('.field-required').removeClass('field-required');
        
        // Add required indicators based on user type
        switch(selectedType) {
            case 'student_school':
                $('.field-student').addClass('field-required');
                break;
            case 'school_teacher':
                $('.field-teacher').addClass('field-required');
                break;
        }
    }
    
    /**
     * Start the import process
     */
    function startImport(file, type) {
        importInProgress = true;
        const formData = new FormData();
        formData.append('action', 'ccr_upload_import_file');
        formData.append('nonce', ccrImport.nonce);
        formData.append('import_file', file);
        
        // Show loading state
        $('#start-import').prop('disabled', true).text(ccrImport.i18n.uploading);
        
        // First, upload the file
        $.ajax({
            url: ccrImport.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // File uploaded successfully, start processing chunks
                    currentFilename = response.data.filename;
                    totalChunks = response.data.chunks;
                    totalRows = response.data.total_rows;
                    
                    // Update UI with total rows
                    $('.total-rows').text(totalRows);
                    
                    // Start processing the first chunk
                    processChunk(0, currentFilename, type);
                } else {
                    showError(response.data || ccrImport.i18n.error);
                    resetImportState();
                }
            },
            error: function(xhr, status, error) {
                showError(ccrImport.i18n.error + ': ' + error);
                resetImportState();
            }
        });
    }
    
    /**
     * Process a chunk of the import
     */
    function processChunk(chunk, filename, type) {
        if (!importInProgress) return;
        
        // Calculate progress
        const progress = Math.round((chunk / totalChunks) * 100);
        const processed = chunk * 10; // Assuming 10 rows per chunk
        const remaining = Math.max(0, totalRows - processed);
        const statusText = ccrImport.i18n.processing_row
            .replace('%1$d', processed + 1)
            .replace('%2$d', Math.min(processed + 10, totalRows));
        
        updateProgress(progress, statusText);
        
        // Process the chunk via AJAX
        $.ajax({
            url: ccrImport.ajaxurl,
            type: 'POST',
            data: {
                action: 'ccr_process_import_chunk',
                nonce: ccrImport.nonce,
                filename: filename,
                chunk: chunk,
                user_type: type
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update counters
                    successCount += response.data.success || 0;
                    errorCount += response.data.errors || 0;
                    processedRows += response.data.processed || 0;
                    
                    // Update UI
                    $('.success-count').text(successCount);
                    $('.error-count').text(errorCount);
                    
                    // Add any error messages
                    if (response.data.messages && response.data.messages.length) {
                        errorMessages = errorMessages.concat(response.data.messages);
                        updateErrorMessages();
                    }
                    
                    // Process next chunk or finish
                    if (chunk < totalChunks - 1) {
                        processChunk(chunk + 1, filename, type);
                    } else {
                        finishImport();
                    }
                } else {
                    showError(response.data || ccrImport.i18n.error);
                    resetImportState();
                }
            },
            error: function(xhr, status, error) {
                showError(ccrImport.i18n.error + ': ' + error);
                resetImportState();
            }
        });
    }
    
    /**
     * Update the progress bar and status
     */
    function updateProgress(percent, message) {
        $('.ccr-progress').css('width', percent + '%');
        $('.ccr-progress-text').text(percent + '%');
        $('.import-status').text(message);
    }
    
    /**
     * Update error messages display
     */
    function updateErrorMessages() {
        const $errorContainer = $('.ccr-error-messages');
        const $errorList = $errorContainer.find('ul');
        
        // Clear existing messages
        $errorList.empty();
        
        // Add new messages (limit to 10)
        const maxErrors = Math.min(errorMessages.length, 10);
        for (let i = 0; i < maxErrors; i++) {
            $errorList.append('<li>' + errorMessages[i] + '</li>');
        }
        
        // Show "X more" message if there are more errors
        if (errorMessages.length > 10) {
            $errorList.append('<li>...and ' + (errorMessages.length - 10) + ' more errors</li>');
        }
        
        // Show the error container
        $errorContainer.show();
    }
    
    /**
     * Show an error message
     */
    function showError(message) {
        $('.import-status')
            .removeClass('processing')
            .addClass('error')
            .html('<strong>' + ccrImport.i18n.error + ':</strong> ' + message);
    }
    
    /**
     * Finish the import process
     */
    function finishImport() {
        updateProgress(100, ccrImport.i18n.complete);
        
        // Update summary
        $('.summary-success').text(successCount);
        $('.summary-errors').text(errorCount);
        $('.summary-total').text(processedRows);
        
        // Show summary section
        $('.import-summary').show();
        
        // Reset form
        resetImportState();
        
        // Clean up the file on the server
        if (currentFilename) {
            $.post(ccrImport.ajaxurl, {
                action: 'ccr_cleanup_import_file',
                nonce: ccrImport.nonce,
                filename: currentFilename
            });
        }
    }
    
    /**
     * Reset the import state
     */
    function resetImportState() {
        importInProgress = false;
        $('#start-import')
            .prop('disabled', false)
            .text(ccrImport.i18n.start_import);
    }
    
    function processNextChunk() {
        if (importPaused || !importInProgress) {
            return;
        }
        
        updateProgress(
            Math.round((processedRows / totalRows) * 100),
            `Processing chunk ${currentChunk + 1} of ${totalChunks}...`
        );
        
        $.post(ajaxurl, {
            action: 'process_import_chunk',
            nonce: ccrImport.nonce,
            filename: currentFilename,
            chunk: currentChunk,
            user_type: $('#user-type').val()
        })
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update counters
                processedRows += data.processed;
                successCount += data.success;
                errorCount += data.errors;
                
                // Add error messages
                if (data.error_messages && data.error_messages.length) {
                    errorMessages = errorMessages.concat(data.error_messages);
                    updateErrorMessages();
                }
                
                // Update progress
                updateProgress(
                    Math.round((processedRows / totalRows) * 100),
                    `Processed ${processedRows} of ${totalRows} rows`
                );
                
                // Update counters
                $('#processed-count').text(processedRows);
                $('#success-count').text(successCount);
                $('#error-count').text(errorCount);
                
                // Check if we're done
                if (data.is_complete) {
                    finishImport();
                } else {
                    // Process next chunk
                    currentChunk++;
                    setTimeout(processNextChunk, 500); // Small delay between chunks
                }
            } else {
                showError(response.data || 'Error processing chunk');
                cleanupAndReset();
            }
        })
        .fail(function() {
            showError('Failed to process chunk');
            cleanupAndReset();
        });
    }
    
    function updateProgress(percent, message) {
        $('.progress').width(percent + '%');
        $('.progress-percent').text(percent + '%');
        if (message) {
            $('.progress-message').text(message);
        }
    }
    
    function updateErrorMessages() {
        const $container = $('#import-messages');
        $container.empty();
        
        if (errorMessages.length > 0) {
            $container.append('<h4>Errors:</h4>');
            const $list = $('<ul>');
            
            // Show only the last 5 errors to prevent UI clutter
            const recentErrors = errorMessages.slice(-5);
            recentErrors.forEach(function(error) {
                $list.append($('<li>').text(error));
            });
            
            $container.append($list);
            
            if (errorMessages.length > 5) {
                $container.append(`<p>... and ${errorMessages.length - 5} more errors</p>`);
            }
            
            // Scroll to bottom of messages
            $container.scrollTop($container[0].scrollHeight);
        }
    }
    
    function showError(message) {
        const $error = $('<div class="notice notice-error">').text(message);
        $('#import-messages').append($error);
    }
    
    function finishImport() {
        resetImport();
        
        if (errorCount > 0) {
            showError(`Import completed with ${errorCount} error(s). See details below.`);
        } else {
            showError('Import completed successfully!');
        }
        
        // Clean up the file
        if (currentFilename) {
            $.post(ajaxurl, {
                action: 'cleanup_import_file',
                nonce: ccrImport.nonce,
                filename: currentFilename
            });
        }
    }
    
    function cleanupAndReset() {
        // Clean up the file
        if (currentFilename) {
            $.post(ajaxurl, {
                action: 'cleanup_import_file',
                nonce: ccrImport.nonce,
                filename: currentFilename
            });
        }
        resetImport();
    }
    
    function resetImport() {
        importInProgress = false;
        importPaused = false;
        $('button[type="submit"]').text('Start Import');
        $('#pause-import').hide();
    }
    
    // Handle pause button
    $('#pause-import').on('click', function() {
        importPaused = !importPaused;
        $(this).text(importPaused ? 'Resume' : 'Pause');
        
        if (!importPaused) {
            processNextChunk();
        }
    });
});
