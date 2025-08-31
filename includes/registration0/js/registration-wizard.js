jQuery(document).ready(function($) {
    'use strict';
    
    // Debug logging to verify script loading
    console.log('Registration Wizard JS loaded');
    
    // Explicitly handle any "New" button clicks to prevent page refresh
    $(document).on('click', '.page-title-action, button:contains("New"), a:contains("חדש")', function(e) {
        console.log('New button intercepted: ' + $(this).text());
        e.preventDefault();
        
        // If this is in the context of the registration wizard, handle it appropriately
        if ($(this).closest('.registration-wizard').length) {
            console.log('In registration wizard context, handling new button');
            // Execute appropriate wizard functionality here
            // For example, reset form or show first step
            $('.wizard-step').removeClass('active');
            $('.wizard-step[data-step="1"]').addClass('active');
            $('.wizard-pane').removeClass('active');
            $('.wizard-pane[data-step="1"]').addClass('active');
            
            // Clear form fields if needed
            $('#registration-wizard-form')[0].reset();
            
            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.registration-wizard').offset().top - 20
            }, 300);
        }
    });

    // Initialize datepicker with Hebrew localization
    $('.datepicker').datepicker({
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        yearRange: '2023:2030',
        // Hebrew localization
        closeText: 'סגור',
        prevText: 'הקודם',
        nextText: 'הבא',
        currentText: 'היום',
        monthNames: ['ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני', 'יולי', 'אוגוסט', 'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר'],
        monthNamesShort: ['ינו', 'פבר', 'מרץ', 'אפר', 'מאי', 'יוני', 'יולי', 'אוג', 'ספט', 'אוק', 'נוב', 'דצמ'],
        dayNames: ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'],
        dayNamesShort: ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'],
        dayNamesMin: ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש'],
        weekHeader: 'Wk',
        firstDay: 0,
        isRTL: true,
        showMonthAfterYear: false,
        yearSuffix: ''
    });

    // Set default expiry date to end of current year if empty
    if (!$('#expiry-date').val()) {
        const today = new Date();
        const year = today.getFullYear();
        const defaultDate = `31/12/${year}`;
        $('#expiry-date').val(defaultDate);
    }

    // Wizard navigation
    $('.next-step').on('click', function(e) {
        e.preventDefault();
        const currentStep = $(this).closest('.wizard-pane');
        const nextStepId = $(this).data('next');
        const nextStep = $(`[data-step="${nextStepId}"]`);
        
        if (validateStep(currentStep)) {
            currentStep.removeClass('active');
            nextStep.addClass('active');
            updateProgressBar(nextStepId);
            
            // Scroll to top of the form
            $('html, body').animate({
                scrollTop: $('.registration-wizard').offset().top - 20
            }, 300);
        }
    });

    $('.previous-step').on('click', function(e) {
        e.preventDefault();
        const currentStep = $(this).closest('.wizard-pane');
        const prevStepId = $(this).data('prev');
        const prevStep = $(`[data-step="${prevStepId}"]`);
        
        currentStep.removeClass('active');
        prevStep.addClass('active');
        updateProgressBar(prevStepId);
        
        // Scroll to top of the form
        $('html, body').animate({
            scrollTop: $('.registration-wizard').offset().top - 20
        }, 300);
    });

    // Update progress bar and active step
    function updateProgressBar(stepId) {
        // Update step indicators
        $('.wizard-step').removeClass('active');
        $(`.wizard-step[data-step="${stepId}"]`).addClass('active');
    }

    // Form validation
    function validateStep(step) {
        const stepNumber = step.data('step');
        let isValid = true;
        
        // Step 1: Teacher selection
        if (stepNumber === 1) {
            const existingTeacher = $('#existing-teacher').val();
            const newTeacherName = $('#new-teacher-name').val();
            const newTeacherEmail = $('#new-teacher-email').val();
            
            if (!existingTeacher && (!newTeacherName || !newTeacherEmail)) {
                alert('אנא בחר מורה קיים או הזן פרטי מורה חדש');
                isValid = false;
            } else if (newTeacherEmail && !isValidEmail(newTeacherEmail)) {
                alert('אנא הזן כתובת אימייל תקינה');
                isValid = false;
            }
        } 
        // Step 2: Class selection
        else if (stepNumber === 2) {
            const existingClass = $('#existing-class').val();
            const newClass = $('#new-class').val();
            
            if (!existingClass && !newClass) {
                alert('אנא בחר כיתה קיימת או הזן שם כיתה חדשה');
                isValid = false;
            }
        }
        
        return isValid;
    }

    // Email validation
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Toggle between existing and new teacher/class
    $('#existing-teacher').on('change', function() {
        if ($(this).val()) {
            $('#new-teacher-name, #new-teacher-email').val('').prop('disabled', true);
        } else {
            $('#new-teacher-name, #new-teacher-email').prop('disabled', false);
        }
    });

    $('#existing-class').on('change', function() {
        if ($(this).val()) {
            $('#new-class').val('').prop('disabled', true);
        } else {
            $('#new-class').prop('disabled', false);
        }
    });

    // Handle Excel file upload
    const dropZone = $('#excel-dropzone');
    const fileInput = $('#excel-file');
    const browseBtn = $('#browse-excel');
    const preview = $('#excel-preview');

    // Click handler for browse button
    browseBtn.on('click', function() {
        fileInput.trigger('click');
    });

    // Handle file selection
    fileInput.on('change', function() {
        handleFiles(this.files);
    });

    // Handle drag and drop
    if (dropZone.length > 0) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.on(eventName, preventDefaults);
        });

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.on(eventName, highlight);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.on(eventName, unhighlight);
        });

        // Handle dropped files
        dropZone.on('drop', handleDrop);
    }


    // Helper functions for file handling
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.addClass('highlight');
    }

    function unhighlight() {
        dropZone.removeClass('highlight');
    }

    function handleDrop(e) {
        const dt = e.originalEvent.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        if (files.length === 0) return;
        
        const file = files[0];
        
        // Validate file type
        if (!file.name.match(/\.(xlsx|xls|csv)$/i)) {
            alert('אנא העלה קובץ Excel חוקי (XLSX, XLS, CSV)');
            return;
        }
        
        // Update UI
        preview.html(`<p>קובץ נבחר: <strong>${file.name}</strong></p>`);
        preview.append('<p>הקובץ יפורש בעת שליחת הטופס...</p>');
        
        // Update file input (for form submission)
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput[0].files = dataTransfer.files;
    }

    // Form submission
    $('#registration-wizard-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $spinner = $form.find('.spinner');
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Prepare form data
        const formData = new FormData($form[0]);
        
        // Add action for AJAX
        formData.append('action', 'generate_registration_codes');
        
        // Submit via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showResults(response.data);
                } else {
                    alert(response.data.message || 'אירעה שגיאה בעת יצירת הקודים');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('אירעה שגיאה בעת שליחת הטופס. אנא נסה שוב.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Show results
    function showResults(data) {
        let html = '<div class="notice notice-success">';
        html += '<p>הקודים נוצרו בהצלחה!</p>';
        
        if (data.codes && data.codes.length > 0) {
            html += '<h4>קודים שנוצרו:</h4>';
            html += '<div class="codes-list"><pre>' + data.codes.join('\n') + '</pre></div>';
            
            // Add copy to clipboard button
            html += '<button type="button" class="button button-secondary copy-codes" data-codes="' + 
                    data.codes.join('\n') + '">העתק קודים ללוח</button>';
            
            // Add download button
            html += ' <button type="button" class="button button-primary download-codes" data-codes="' + 
                   data.codes.join('\n') + '">הורד קודים כקובץ טקסט</button>';
        }
        
        html += '</div>';
        
        $('#wizard-results-content').html(html);
        $('#wizard-results').show();
        
        // Initialize copy and download buttons
        $('.copy-codes').on('click', function() {
            const codes = $(this).data('codes');
            navigator.clipboard.writeText(codes).then(function() {
                alert('הקודים הועתקו ללוח!');
            });
        });
        
        $('.download-codes').on('click', function() {
            const codes = $(this).data('codes');
            const blob = new Blob([codes], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'registration-codes-' + new Date().toISOString().split('T')[0] + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#wizard-results').offset().top - 100
        }, 500);
    }
});
