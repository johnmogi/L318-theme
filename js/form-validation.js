/**
 * Form Validation for Registration Forms
 * Handles client-side validation for all registration/purchase forms
 */

document.addEventListener('DOMContentLoaded', function() {
    // Select all forms with the needs-validation class
    const forms = document.querySelectorAll('.needs-validation');

    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            // Add validation for phone number format
            const phoneInputs = form.querySelectorAll('input[type="text"][name*="phone"]');
            phoneInputs.forEach(input => {
                if (input.value && !/^[0-9\-\+\(\)\s]+$/.test(input.value)) {
                    input.setCustomValidity('אנא הזן מספר טלפון תקין');
                    input.classList.add('is-invalid');
                } else {
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                }
            });

            // Add validation for ID number format (Israeli ID)
            const idInputs = form.querySelectorAll('input[type="text"][name*="id_number"]');
            idInputs.forEach(input => {
                if (input.value && !/^\d{9}$/.test(input.value)) {
                    input.setCustomValidity('אנא הזן תעודת זהות ישראלית בת 9 ספרות');
                    input.classList.add('is-invalid');
                } else {
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                }
            });

            // Check if phone numbers match
            const phone = form.querySelector('input[name="phone"]');
            const phoneConfirm = form.querySelector('input[name="phone_confirm"]');
            if (phone && phoneConfirm && phone.value !== phoneConfirm.value) {
                phoneConfirm.setCustomValidity('מספרי הטלפון אינם תואמים');
                phoneConfirm.classList.add('is-invalid');
            } else if (phoneConfirm) {
                phoneConfirm.setCustomValidity('');
                phoneConfirm.classList.remove('is-invalid');
            }

            // Check if ID numbers match
            const idNumber = form.querySelector('input[name="id_number"]');
            const idNumberConfirm = form.querySelector('input[name="id_number_confirm"]');
            if (idNumber && idNumberConfirm && idNumber.value !== idNumberConfirm.value) {
                idNumberConfirm.setCustomValidity('מספרי תעודת הזהות אינם תואמים');
                idNumberConfirm.classList.add('is-invalid');
            } else if (idNumberConfirm) {
                idNumberConfirm.setCustomValidity('');
                idNumberConfirm.classList.remove('is-invalid');
            }

            form.classList.add('was-validated');
        }, false);
    });

    // Real-time validation for form fields
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
            } else {
                this.classList.add('is-invalid');
            }
        });
    });
});
