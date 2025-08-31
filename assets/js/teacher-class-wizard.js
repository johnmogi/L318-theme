jQuery(document).ready(function($) {
    'use strict';

    // Toggle between existing and new teacher/class
    $('input[name^="teacher_"]').on('change', function() {
        var type = $(this).val();
        if (type === 'existing') {
            $('#existing-teacher-fields').show();
            $('#new-teacher-fields').hide();
        } else {
            $('#existing-teacher-fields').hide();
            $('#new-teacher-fields').show();
        }
    });

    $('input[name^="class_"]').on('change', function() {
        var type = $(this).val();
        if (type === 'existing') {
            $('#existing-class-fields').show();
            $('#new-class-fields').hide();
        } else {
            $('#existing-class-fields').hide();
            $('#new-class-fields').show();
        }
    });

    // Toggle student fields
    $('#create_students').on('change', function() {
        if ($(this).is(':checked')) {
            $('#student-fields-container').slideDown();
            // Auto-focus first field when showing
            $('#student-fields-container input:first').focus();
        } else {
            $('#student-fields-container').slideUp();
        }
    });

    // Add student field group
    var studentFieldIndex = 1;
    $('#add-student-field').on('click', function(e) {
        e.preventDefault();
        
        // Clone the template
        var $newField = $('#student-fields-template .student-field-group').first().clone();
        
        // Update the student number
        $newField.find('h4').text(teacherClassWizard.i18n.student + ' #' + (studentFieldIndex + 1));
        
        // Update field names and clear values
        $newField.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name').replace('[0]', '[' + studentFieldIndex + ']');
            $field.attr('name', name).val('');
            
            // Clear any validation errors
            $field.removeClass('error');
            $field.siblings('.validation-error').remove();
        });
        
        // Add remove button if not the first field
        if (studentFieldIndex > 0) {
            $newField.append(
                '<button type="button" class="button button-link remove-student-field" style="color: #b32d2e;">' +
                teacherClassWizard.i18n.removeStudent + '</button>'
            );
        }
        
        // Add to container
        $('#student-fields-container').append($newField);
        
        // Focus on the first input of the new field
        $newField.find('input:first').focus();
        
        // Scroll to the new field
        $('html, body').animate({
            scrollTop: $newField.offset().top - 100
        }, 500);
        
        studentFieldIndex++;
    });
    
    // Remove student field
    $(document).on('click', '.remove-student-field', function() {
        if (confirm(teacherClassWizard.i18n.confirmRemoveStudent)) {
            $(this).closest('.student-field-group').fadeOut(300, function() {
                $(this).remove();
                // Renumber remaining students
                $('.student-field-group').each(function(index) {
                    $(this).find('h4').text(teacherClassWizard.i18n.student + ' #' + (index + 1));
                    $(this).find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    });
                });
                studentFieldIndex = $('.student-field-group').length;
            });
        }
    });
    
    // Format phone number as user types
    $(document).on('input', 'input[type="tel"]', function() {
        var value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    });
    
    // Auto-generate username from phone
    $(document).on('blur', 'input[name$="[username]"]', function() {
        var $this = $(this);
        var phone = $this.val().trim();
        if (phone) {
            // Check if username already exists
            $.ajax({
                url: teacherClassWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'check_username_exists',
                    username: phone,
                    nonce: teacherClassWizard.nonce
                },
                success: function(response) {
                    if (response.success && response.data.exists) {
                        $this.addClass('error');
                        $this.after('<span class="validation-error" style="color:#b32d2e;display:block;margin-top:5px;">' + 
                            teacherClassWizard.i18n.usernameExists + '</span>');
                    } else {
                        $this.removeClass('error');
                        $this.siblings('.validation-error').remove();
                    }
                }
            });
        }
    });

    // Copy code to clipboard
    $(document).on('click', '.copy-code', function(e) {
        e.preventDefault();
        var code = $(this).data('code');
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(code).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show copied message
        var $button = $(this);
        var originalText = $button.text();
        $button.text(teacherClassWizard.i18n.copied);
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });

    // Download codes as CSV
    $('#download-codes').on('click', function() {
        var csv = [];
        var rows = $('.promo-code-item');
        
        // Add header
        csv.push(['Code', 'Status', 'Used By', 'Expiry Date']);
        
        // Add data rows
        rows.each(function() {
            var code = $(this).find('code').text();
            var status = 'Active';
            var usedBy = 'N/A';
            var expiry = $('#expiry_date').val() || 'N/A';
            
            csv.push([code, status, usedBy, expiry]);
        });
        
        // Convert to CSV string
        var csvContent = '';
        csv.forEach(function(rowArray) {
            var row = rowArray.join(',');
            csvContent += row + '\r\n';
        });
        
        // Create download link
        var encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
        var link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'promo-codes-' + new Date().toISOString().slice(0, 10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Validate student fields
    function validateStudentFields() {
        var isValid = true;
        var studentCount = 0;
        
        $('.student-field-group').each(function(index) {
            var $group = $(this);
            var $firstName = $group.find('input[name$="[first_name]"]');
            var $lastName = $group.find('input[name$="[last_name]"]');
            var $username = $group.find('input[name$="[username]"]');
            var $password = $group.find('input[name$="[password]"]');
            
            // Clear previous errors
            $group.find('.validation-error').remove();
            $group.find('input').removeClass('error');
            
            // Validate first name
            if (!$firstName.val().trim()) {
                showFieldError($firstName, teacherClassWizard.i18n.requiredField);
                isValid = false;
            }
            
            // Validate last name
            if (!$lastName.val().trim()) {
                showFieldError($lastName, teacherClassWizard.i18n.requiredField);
                isValid = false;
            }
            
            // Validate username (phone)
            var phone = $username.val().trim();
            if (!phone) {
                showFieldError($username, teacherClassWizard.i18n.requiredField);
                isValid = false;
            } else if (!/^\d{9,15}$/.test(phone)) {
                showFieldError($username, teacherClassWizard.i18n.invalidPhone);
                isValid = false;
            }
            
            // Validate password (student ID)
            if (!$password.val().trim()) {
                showFieldError($password, teacherClassWizard.i18n.requiredField);
                isValid = false;
            }
            
            if (isValid) {
                studentCount++;
            }
        });
        
        // If we have students, make sure at least one is valid
        if ($('#create_students').is(':checked') && studentCount === 0) {
            alert(teacherClassWizard.i18n.atLeastOneStudent);
            isValid = false;
            
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.student-field-group:first').offset().top - 100
            }, 500);
        }
        
        return isValid;
    }
    
    // Show field error
    function showFieldError($field, message) {
        $field.addClass('error');
        $field.after('<span class="validation-error" style="color:#b32d2e;display:block;margin-top:5px;">' + 
                    message + '</span>');
    }
    
    // Form submission
    $('form.wizard-form').on('submit', function(e) {
        var valid = true;
        var currentStep = $('input[name="step"]').val();
        
        // Clear previous errors
        $('.validation-error').remove();
        $('input, select, textarea').removeClass('error');
        
        // Step 3 validation (student creation)
        if (currentStep == 3 && $('#create_students').is(':checked')) {
            valid = validateStudentFields();
            if (!valid) {
                e.preventDefault();
                return false;
            }
        }
        
        // Original step validation
        if (currentStep == 1) {
            if ($('#teacher_id').val() === '' && 
                ($('#new_teacher_name').val() === '' || $('#new_teacher_email').val() === '')) {
                alert(teacherClassWizard.i18n.teacherRequired);
                valid = false;
            }
        }
        
        // Step 2 validation
        if (currentStep === '2' && $('#class_id').val() === '' && $('#new_class_name').val() === '') {
            alert(teacherClassWizard.i18n.classRequired);
            valid = false;
        }
        
        // Step 3 validation
        if (currentStep === '3') {
            var quantity = parseInt($('#quantity').val());
            if (isNaN(quantity) || quantity < 1 || quantity > 1000) {
                alert(teacherClassWizard.i18n.invalidQuantity);
                valid = false;
            }
            
            // Validate student fields if creating students
            if ($('#create_students').is(':checked')) {
                $('.student-field-group').each(function(index) {
                    var $group = $(this);
                    if ($group.find('input[name$="[first_name]"]').val() === '' || 
                        $group.find('input[name$="[last_name]"]').val() === '') {
                        alert(teacherClassWizard.i18n.studentNameRequired);
                        valid = false;
                        return false; // Break the loop
                    }
                });
            }
        }
        
        if (!valid) {
            e.preventDefault();
            return false;
        }
    });
});
