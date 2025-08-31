jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Teacher Class Wizard JS loaded');
    console.log('teacherClassWizard object:', teacherClassWizard);
    
    // Add required field message to i18n if not exists
    if (!teacherClassWizard.i18n.requiredField) {
        teacherClassWizard.i18n.requiredField = 'This field is required';
    }

    // Initialize Select2 for teacher selection
    function initTeacherSelect() {
        console.log('Initializing teacher select');
        $('#teacher_id').select2({
            placeholder: 'Search teachers...',
            allowClear: true,
            width: '100%',
            templateResult: function(teacher) {
                if (teacher.loading) return teacher.text;
                return $('<span>').text(teacher.text).addClass('teacher-option');
            },
            templateSelection: function(teacher) {
                return teacher.text || 'Select a teacher';
            },
            language: {
                noResults: function() {
                    return 'No teachers found';
                },
                searching: function() {
                    return 'Searching...';
                },
                inputTooShort: function(args) {
                    return 'Please enter ' + (args.minimum - args.input.length) + ' more characters';
                }
            }
        });
        
        // Log available options for debugging
        console.log('Available teacher options:', $('#teacher_id').html());
    }

    // Format how the selected item is displayed
    function formatTeacherSelection(teacher) {
        if (!teacher.id) {
            return teacher.text;
        }
        return teacher.text || teacher.name || '';
    }

    // Format how search results are displayed
    function formatTeacher(teacher) {
        if (teacher.loading) {
            return teacher.text;
        }

        var $container = $(
            '<div class="teacher-select-option">' +
                '<div class="teacher-name">' + teacher.name + '</div>' +
                '<div class="teacher-details">' +
                    (teacher.email ? '<div class="teacher-email">' + teacher.email + '</div>' : '') +
                    (teacher.phone ? '<div class="teacher-phone">' + teacher.phone + '</div>' : '') +
                '</div>' +
            '</div>'
        );

        return $container;
    }

    // Toggle between new and existing teacher forms
    $('input[name="teacher_type"]').on('change', function() {
        if ($(this).val() === 'new') {
            $('.new-teacher-section').show();
            $('.existing-teacher-section').hide();
            $('#teacher_id').val(null).trigger('change');
        } else {
            $('.new-teacher-section').hide();
            $('.existing-teacher-section').show();
        }
    });

    // Auto-format phone number
    $('#new_teacher_phone').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Initialize the teacher select
    if ($('#teacher_id').length) {
        initTeacherSelect();
    }

    // Handle form submission
    $('form.teacher-class-wizard-form').on('submit', function(e) {
        var $form = $(this);
        var currentStep = parseInt($('input[name="step"]', $form).val()) || 1;
        var isValid = true;
        
        // Clear previous errors
        $('.error-message').remove();
        $('.error').removeClass('error');
        
        // Step-specific validation
        if (currentStep === 1) {
            // Teacher selection/creation validation
            if ($('input[name="teacher_type"]:checked').val() === 'existing') {
                if (!$('#teacher_id').val()) {
                    isValid = false;
                    $('#teacher_id').addClass('error')
                        .after('<span class="error-message">' + teacherClassWizard.i18n.teacherRequired + '</span>');
                }
            } else {
                // New teacher validation
                $('.new-teacher-section input[required]').each(function() {
                    if (!$(this).val().trim()) {
                        isValid = false;
                        $(this).addClass('error')
                            .after('<span class="error-message">' + $(this).attr('data-required-message') || teacherClassWizard.i18n.requiredField + '</span>');
                    }
                });
            }
        } else if (currentStep === 2) {
            // Class selection/creation validation
            var classSelected = $('input[name="class_id"]:checked').length > 0;
            var newClassName = $('input[name="new_class_name"]').val().trim();
            
            if (!classSelected && !newClassName) {
                isValid = false;
                $('.class-table-container').before('<div class="error-message" style="color: #dc3232; margin-bottom: 15px;">' + teacherClassWizard.i18n.classRequired + '</div>');
            }
        } else if (currentStep === 3) {
            // Handle promo code generation if button was clicked
            if ($('input[name="generate_codes"]').length > 0) {
                // Continue submission if generating codes
                return true;
            }
            
            // Student creation validation (if enabled)
            if ($('#create_students').is(':checked')) {
                $('.student-field-group:visible').each(function() {
                    var $group = $(this);
                    var hasError = false;
                    
                    $('input[data-required="true"]', $group).each(function() {
                        if ($(this).val() === '') {
                            hasError = true;
                            $(this).addClass('error');
                            if ($(this).next('.error-message').length === 0) {
                                $(this).after('<span class="error-message">' + (teacherClassWizard.i18n.requiredField || 'This field is required') + '</span>');
                            }
                        } else {
                            $(this).removeClass('error');
                            $(this).next('.error-message').remove();
                        }
                    });
                    
                    if (hasError) {
                        isValid = false;
                    }
                });
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // If we got here, form is valid
        $form.addClass('processing').find('button[type="submit"]').prop('disabled', true);
    });
    
    // Clear error when user starts typing
    $('form.teacher-class-wizard-form').on('input', 'input[required]', function() {
        if ($(this).val() !== '') {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });
    
    // Toggle student fields
    $('#create_students').on('change', function() {
        var isChecked = $(this).is(':checked');
        console.log('Create students checkbox changed:', isChecked ? 'CHECKED' : 'UNCHECKED');
        
        if (isChecked) {
            console.log('Showing student fields container');
            $('#student-fields-container').slideDown();
            // Enable validation for required fields
            $('#student-fields-container').find('[data-required="true"]').prop('required', true);
            console.log('Required fields enabled for validation');
        } else {
            console.log('Hiding student fields container');
            $('#student-fields-container').slideUp();
            // Disable validation for required fields when hidden
            $('#student-fields-container').find('[data-required="true"]').prop('required', false);
            console.log('Required fields disabled for validation');
        }
    });
    
    // Execute once on page load to ensure correct initial state
    $(document).ready(function() {
        console.log('Initializing student fields visibility');
        var createStudentsChecked = $('#create_students').is(':checked');
        console.log('Initial create_students state:', createStudentsChecked ? 'CHECKED' : 'UNCHECKED');
        
        if (createStudentsChecked) {
            $('#student-fields-container').show();
            $('#student-fields-container').find('[data-required="true"]').prop('required', true);
            console.log('Student fields shown and required on page load');
        } else {
            $('#student-fields-container').hide();
            $('#student-fields-container').find('[data-required="true"]').prop('required', false);
            console.log('Student fields hidden and not required on page load');
        }
    });
    
    // Add new student field
    $('#add-student-field').on('click', function() {
        var studentCount = $('.student-field-group').length;
        var newStudentHtml = $('#student-fields-template .student-field-group').first().clone();
        
        // Update the field names and IDs
        newStudentHtml.find('h4').text('Student #' + (studentCount + 1));
        
        newStudentHtml.find('input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('[0]', '[' + studentCount + ']'));
                // Clear any values
                $(this).val('');
                
                // Set data-required attribute instead of required
                if ($(this).attr('required')) {
                    $(this).removeAttr('required').attr('data-required', 'true');
                }
                
                // If create_students is checked, add required attribute
                if ($('#create_students').is(':checked') && $(this).attr('data-required') === 'true') {
                    $(this).prop('required', true);
                }
            }
        });
        
        // Add a remove button
        if (studentCount > 0) {
            var removeBtn = $('<button type="button" class="button remove-student">Remove</button>');
            newStudentHtml.append(removeBtn);
        }
        
        // Add the new student field
        $('#student-fields-container').append(newStudentHtml);
    });
    
    // Handle click of generate button
    $('#generate-codes-button').on('click', function(e) {
        e.preventDefault();
        console.log('Generate codes button clicked');
        
        var quantity = $('#quantity').val();
        var prefix = $('#prefix').val();
        var expiryDate = $('#expiry_date').val();
        var classId = $('input[name="class_id"]').val() || '';
        
        // Validate quantity
        if (!quantity || quantity < 1 || quantity > 1000) {
            alert(teacherClassWizard.i18n.invalidQuantity || 'Please enter a valid quantity (1-1000)');
            return false;
        }
        
        // Show loading state
        var $button = $(this);
        var originalText = $button.text();
        $button.text('Generating...').prop('disabled', true);
        
        // AJAX call to generate codes
        $.ajax({
            url: teacherClassWizard.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_promo_codes',
                nonce: teacherClassWizard.nonce,
                quantity: quantity,
                prefix: prefix,
                expiry_date: expiryDate,
                class_id: classId
            },
            success: function(response) {
                console.log('Promo code generation response:', response);
                
                if (response.success) {
                    // Display generated codes
                    var codes = response.data.codes || [];
                    var $codesContainer = $('.promo-codes-generated');
                    
                    if (!$codesContainer.length) {
                        $codesContainer = $('<div class="promo-codes-generated">').insertAfter('#generate-codes-button');
                    }
                    
                    // Clear existing content
                    $codesContainer.empty();
                    
                    // Add heading
                    $codesContainer.append('<h3>Generated Promo Codes</h3>');
                    
                    // Add codes list
                    var $codesList = $('<div class="promo-codes-list">');
                    $.each(codes, function(i, code) {
                        $codesList.append('<div class="promo-code-item"><span class="code">' + code.code + '</span></div>');
                    });
                    
                    // Add to container
                    $codesContainer.append($codesList);
                    
                    // Enable download button
                    $('#download-codes').prop('disabled', false);
                    
                    // Store codes in hidden input for form submission
                    $('input[name="promo_codes_json"]').remove(); // Remove any existing
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'promo_codes_json',
                        value: JSON.stringify(codes)
                    }).appendTo('form.teacher-class-wizard-form');
                    
                    // Show success message
                    alert('Successfully generated ' + codes.length + ' promo codes!');
                } else {
                    // Show error message
                    alert(response.data.message || 'Error generating promo codes');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error: ' + error);
            },
            complete: function() {
                // Restore button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Handle download CSV button
    $('#download-codes').on('click', function(e) {
        e.preventDefault();
        console.log('Download CSV button clicked');
        
        var $button = $(this);
        var codesJson = $('input[name="promo_codes_json"]').val();
        
        if (!codesJson) {
            // Try to get codes from the displayed list if hidden input is empty
            var codes = [];
            $('.promo-codes-list .code').each(function() {
                codes.push({code: $(this).text().trim()});
            });
            
            if (codes.length > 0) {
                codesJson = JSON.stringify(codes);
                console.log('Retrieved ' + codes.length + ' codes from displayed list');
            } else {
                alert('No codes to download. Please generate codes first.');
                return false;
            }
        }
        
        // Log the codes we're sending
        console.log('Codes JSON to send:', codesJson);
        
        // Show loading state
        var originalText = $button.text();
        $button.text('Preparing...').prop('disabled', true);
        
        // AJAX call to generate CSV
        $.ajax({
            url: teacherClassWizard.ajaxurl,
            type: 'POST',
            data: {
                action: 'download_promo_codes_csv',
                nonce: teacherClassWizard.nonce,
                codes_json: codesJson
            },
            success: function(response) {
                console.log('CSV download response:', response);
                
                if (response && response.success) {
                    // Create hidden download link
                    var csvContent = response.data.csv_content;
                    if (!csvContent) {
                        console.error('CSV content is empty in the response');
                        alert('Error: CSV content is empty');
                        return;
                    }
                    
                    try {
                        var encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
                        var downloadLink = document.createElement('a');
                        downloadLink.setAttribute('href', encodedUri);
                        downloadLink.setAttribute('download', 'promo_codes_' + (new Date().toISOString().slice(0, 10)) + '.csv');
                        document.body.appendChild(downloadLink);
                        
                        console.log('Triggering download with URI length:', encodedUri.length);
                        // Trigger download
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        
                        // Show success message
                        alert('CSV file has been downloaded successfully!');
                    } catch (e) {
                        console.error('Error creating download link:', e);
                        alert('Error creating download: ' + e.message);
                        
                        // Fallback method - display in new window
                        var newWindow = window.open('', '_blank');
                        newWindow.document.write('<pre>' + csvContent + '</pre>');
                        newWindow.document.title = 'Promo Codes CSV';
                    }
                } else {
                    // Show error message
                    console.error('Error response:', response);
                    alert(response && response.data && response.data.message ? response.data.message : 'Error generating CSV');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr: xhr, status: status, error: error});
                alert('Error: ' + error + '. Please check browser console for details.');
            },
            complete: function() {
                // Restore button state
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
});
