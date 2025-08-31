jQuery(document).ready(function($) {
    'use strict';
    
    // Show error message in the content area
    function showError(message) {
        var $content = $('.registration-codes-content');
        $content.html('<div class="notice notice-error"><p>' + message + '</p></div>');
    }
    
    // Initialize datepicker if it exists
    if (typeof $.fn.datepicker !== 'undefined' && $('#code-expiry').length) {
        $('#code-expiry').datepicker({
            dateFormat: typeof registrationCodes !== 'undefined' ? registrationCodes.dateFormat : 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Tab handling with event delegation to prevent multiple bindings
    var $tabs = $('.nav-tab-wrapper');
    
    // Only initialize if tabs exist and haven't been initialized yet
    if ($tabs.length > 0 && !$tabs.data('initialized')) {
        // Mark as initialized
        $tabs.data('initialized', true);
        
        // Handle tab clicks
        $(document).on('click', '.nav-tab:not(.nav-tab-active)', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var tab = $this.data('tab');
            
            // Get current URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            var group = urlParams.get('group') || '';
            var status = urlParams.get('status') || '';
            var role = urlParams.get('role') || '';
            
            // Build new URL with current filter parameters
            var newUrl = window.location.pathname + '?page=registration-codes&tab=' + tab;
            if (group) newUrl += '&group=' + encodeURIComponent(group);
            if (status) newUrl += '&status=' + encodeURIComponent(status);
            if (role) newUrl += '&role=' + encodeURIComponent(role);
            
            window.history.pushState({ 
                tab: tab,
                group: group,
                status: status,
                role: role
            }, '', newUrl);
            
            // Update UI
            updateActiveTab(tab);
            
            // Load content via AJAX
            loadTabContent(tab);
        });
        
        // Handle browser back/forward
        $(window).on('popstate', function(e) {
            var tab = (e.originalEvent.state && e.originalEvent.state.tab) || 'manage';
            updateActiveTab(tab);
        });
        
        // Update active tab UI
        function updateActiveTab(tab) {
            $('.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
        }
        
        // Show error message in the content area
        function showError(message) {
            var $content = $('.registration-codes-content');
            $content.html('<div class="notice notice-error"><p>' + message + '</p></div>');
        }

        // Load content via AJAX
        function loadTabContent(tab) {
            if (!tab) return;
            
            // Show loading state
            var $content = $('.registration-codes-content');
            $content.html('<div class="notice notice-info"><p>Loading ' + tab + ' tab content...</p></div>');
            
            // Get all form data including the new fields
            var formData = new FormData();
            var data = {
                action: 'load_registration_codes_tab',
                _ajax_nonce: registrationCodes.nonce,
                tab: tab  // Make sure tab is included in the request
            };
            
            // Debug - log the form data
            console.log('Submitting form with data:', data);
            
            // Submit the form via AJAX
            console.log('Sending AJAX request to:', registrationCodes.ajax_url);
            console.log('Request data:', data);
            
            $.ajax({
                url: registrationCodes.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json', // Ensure we're expecting JSON
                success: function(response, status, xhr) {
                    console.log('AJAX Success:', {
                        status: xhr.status,
                        response: response,
                        statusText: xhr.statusText,
                        responseHeaders: xhr.getAllResponseHeaders()
                    });
                    
                    // Check if response is valid JSON and has success flag
                    if (response && typeof response === 'object') {
                        if (response.success) {
                            if (response.data && response.data.content) {
                                $content.html(response.data.content);
                                return;
                            } else {
                                console.error('Missing content in response:', response);
                                showError('No content received in response');
                                return;
                            }
                        } else if (response.data && response.data.message) {
                            console.error('Server returned error:', response.data.message);
                            showError(response.data.message);
                            return;
                        }
                    }
                    
                    // Handle non-JSON response (like login page HTML)
                    if (typeof response === 'string' && response.indexOf('<html') !== -1) {
                        console.error('Received HTML instead of JSON. Possible authentication issue.');
                        showError('Session expired. Please refresh the page and log in again.');
                    } else {
                        console.error('Unexpected response format:', response);
                        showError('Unexpected response format from server');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error,
                        responseHeaders: xhr.getAllResponseHeaders()
                    });
                    
                    var errorMsg = 'Error loading tab content: ' + status;
                    
                    // Try to parse the response as JSON
                    if (xhr.responseText) {
                        try {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                errorMsg = jsonResponse.data.message;
                            }
                        } catch (e) {
                            // If we can't parse the response, check if it's HTML
                            if (xhr.responseText.indexOf('<html') !== -1) {
                                errorMsg = 'Session expired. Please refresh the page and log in again.';
                            } else {
                                errorMsg = xhr.responseText.substring(0, 200); // Limit length
                            }
                        }
                    }
                    
                    showError(errorMsg);
                }
            });
        }
        
        // Initialize the correct tab and filters on page load
        var url = new URL(window.location.href);
        var tab = url.searchParams.get('tab') || 'manage';
        updateActiveTab(tab);
        
        // Preserve filter values when tab is changed via AJAX
        $(document).on('submit', '.registration-codes-filters form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            var tab = $('input[name="tab"]', $form).val() || 'manage';
            
            // Update URL with form parameters
            var newUrl = window.location.pathname + '?' + formData;
            window.history.pushState({ 
                tab: tab,
                group: $('select[name="group"]', $form).val() || '',
                status: $('select[name="status"]', $form).val() || '',
                role: $('select[name="role"]', $form).val() || ''
            }, '', newUrl);
            
            // Reload the tab content with filters
            loadTabContent(tab);
        });
    }

    // Handle code generation - using event delegation with off() to prevent multiple bindings
    $(document).off('submit', '#generate-codes-form').on('submit', '#generate-codes-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        
        // Prevent multiple submissions
        if ($form.data('submitting') === true) {
            return false;
        }
        
        $form.data('submitting', true);
        
        var $button = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        var $generatedContainer = $('#generated-codes-container');
        var $generatedOutput = $('#generated-codes-output');
        var $generatedNotice = $('.generated-codes-notice');
        
        // Reset previous notices and reset container
        $generatedNotice.removeClass('notice-error').addClass('notice-success');
        $generatedNotice.find('p').text(registrationCodes.i18n.generating_codes || 'Generating codes...');
        $generatedNotice.show();
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Store the form reference for use in callbacks
        var formSubmission = {
            $form: $form,
            $button: $button,
            $spinner: $spinner,
            $generatedContainer: $generatedContainer,
            $generatedOutput: $generatedOutput,
            $generatedNotice: $generatedNotice,
            resetForm: function() {
                this.$form.data('submitting', false);
                this.$button.prop('disabled', false);
                this.$spinner.removeClass('is-active');
            }
        };
        
        // Create FormData object from the form
        var formData = new FormData($form[0]);
        
        // Add action and nonce
        formData.append('action', 'generate_registration_codes');
        formData.append('nonce', registrationCodes.nonce);
        
        // Add all form fields to the data object
        var formFields = [
            'first_name', 'last_name', 'school_name', 'school_city', 
            'school_code', 'mobile_phone', 'user_password', 'code_count',
            'code_role', 'code_group', 'code_course', 'code_expiry',
            'code_max_uses', 'code_format'
        ];
        
        // Add each field to formData if it exists
        formFields.forEach(function(field) {
            var $field = $form.find('[name="' + field + '"]');
            if ($field.length) {
                formData.append(field, $field.val());
            }
        });
        
        // Debug - log the form data
        var formDataObj = {};
        formData.forEach((value, key) => {
            formDataObj[key] = value;
        });
        console.log('Submitting form with data:', formDataObj);
        
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            context: formSubmission,
            success: function(response) {
                console.log('AJAX Response:', response); // Debug log
                
                if (response.success && response.data && response.data.codes && response.data.codes.length > 0) {
                    // First update the notice with success message
                    this.$generatedNotice.find('p').text(response.data.message || 'Codes generated successfully');
                    
                    // Build codes table
                    var html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Code</th><th>Role</th>';
                    
                    // Add Course header if available
                    var courseName = $form.find('#code-course option:selected').text();
                    if (courseName && courseName !== '-- None --') {
                        html += '<th>Course</th>';
                    }
                    
                    // Add other headers
                    html += '<th>Max Uses</th><th>Expires</th></tr></thead><tbody>';
                    
                    // Add rows for each code
                    response.data.codes.forEach(function(code) {
                        var roleName = $form.find('#code-role option:selected').text();
                        var maxUses = $form.find('#code-max-uses').val() || '1';
                        var expiryDate = $form.find('#code-expiry').val() || 'Never';
                        
                        html += '<tr><td><code>' + code + '</code></td>';
                        html += '<td>' + roleName + '</td>';
                        
                        // Add course cell if available
                        if (courseName && courseName !== '-- None --') {
                            html += '<td>' + courseName + '</td>';
                        }
                        
                        // Add other cells
                        html += '<td>' + maxUses + '</td>';
                        html += '<td>' + expiryDate + '</td></tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    // Show the results
                    this.$generatedOutput.html(html);
                    this.$generatedContainer.show();
                    
                    // Prepare the copy to clipboard functionality
                    var plainTextCodes = response.data.codes.join('\n');
                    $('#copy-codes').data('codes', plainTextCodes);
                    
                    // Scroll to the generated codes container
                    $('html, body').animate({
                        scrollTop: this.$generatedContainer.offset().top - 50
                    }, 500);
                    
                } else {
                    // Handle error
                    this.$generatedNotice.removeClass('notice-success').addClass('notice-error');
                    this.$generatedNotice.find('p').text(response.data && response.data.message ? 
                        response.data.message : 'Error generating codes');
                }
                
                this.resetForm();
            },
            error: function(xhr, status, error) {
                console.error('Error generating codes:', error);
                this.$generatedNotice.removeClass('notice-success').addClass('notice-error');
                this.$generatedNotice.find('p').text(registrationCodes.i18n.error_occurred || 'An error occurred. Please try again.');
                this.resetForm();
            },
            complete: function() {
                this.$button.prop('disabled', false);
                this.$spinner.removeClass('is-active');
            }
        });
    });
    
    // Copy codes to clipboard
    $('#copy-codes').on('click', function() {
        var $this = $(this);
        var codes = $this.data('codes');
        
        if (codes) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(codes).select();
            document.execCommand('copy');
            $temp.remove();
            
            var originalText = $this.text();
            $this.text('Copied!');
            setTimeout(function() {
                $this.text(originalText);
            }, 2000);
        }
    });
    
    // Download as CSV
    $('#download-csv').on('click', function() {
        var codes = $('#copy-codes').data('codes');
        var role = $('#code-role option:selected').text();
        var group = $('#code-group').val();
        var course = $('#code-course option:selected').text();
        
        if (codes) {
            var rows = codes.split('\n');
            var csvContent = 'Code,Role,Group,Course\n';
            
            rows.forEach(function(code) {
                csvContent += code + ',' + role + ',' + (group || 'None') + ',' + (course || 'None') + '\n';
            });
            
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'registration-codes.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
    
    // Print codes
    $('#print-codes').on('click', function() {
        var $printArea = $('<div>');
        $printArea.html($('#generated-codes-output').html());
        
        var $body = $('body');
        var bodyHtml = $body.html();
        
        $body.html($printArea);
        window.print();
        $body.html(bodyHtml);
    });
    
    // Toggle code details view
    $('.registration-codes-container').on('click', '.code-toggle', function() {
        $(this).closest('tr').next('.code-details-row').toggle();
        $(this).find('span').toggleClass('dashicons-arrow-down dashicons-arrow-right');
    });
    
    // Handle code validation
    $('#validate-code-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $results = $('#code-validation-results');
        
        $button.prop('disabled', true).text('Validating...');
        $results.html('<p>Validating code, please wait...</p>');
        
        $.ajax({
            url: registrationCodes.ajax_url,
            type: 'POST',
            data: {
                action: 'validate_code',
                nonce: registrationCodes.nonce,
                code: $form.find('#code-to-validate').val()
            },
            success: function(response) {
                if (response.success) {
                    var html = '<div class="notice notice-success">';
                    html += '<p><strong>Code is valid!</strong></p>';
                    html += '<ul>';
                    html += '<li><strong>Code:</strong> ' + response.data.code + '</li>';
                    html += '<li><strong>Role:</strong> ' + response.data.role + '</li>';
                    html += '<li><strong>Status:</strong> ' + (response.data.is_used ? 'Used' : 'Available') + '</li>';
                    if (response.data.used_by) {
                        html += '<li><strong>Used by:</strong> User #' + response.data.used_by + '</li>';
                    }
                    if (response.data.used_at) {
                        html += '<li><strong>Used at:</strong> ' + response.data.used_at + '</li>';
                    }
                    html += '</ul></div>';
                    $results.html(html);
                } else {
                    $results.html('<div class="notice notice-error"><p>' + (response.data.message || 'Invalid code') + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p>Error: Could not connect to the server. Please try again.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Validate Code');
            }
        });
    });
});
