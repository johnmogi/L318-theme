/**
 * Promo Code Registration Form
 */
jQuery(document).ready(function($) {
    'use strict';

    var promoCodeForm = {
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        cacheElements: function() {
            this.$form = $('#promo-code-form');
            this.$promoCodeField = $('#promo_code');
            this.$validateButton = $('#validate-promo-code');
            this.$status = $('#promo-code-status');
            this.$registrationFields = $('#registration-fields');
            this.$submitButton = $('button[type="submit"]');
            this.$passwordField = $('#password');
            this.$confirmPasswordField = $('#confirm_password');
            this.$usernameField = $('#username');
            this.$emailField = $('#email');
        },

        bindEvents: function() {
            // Validate promo code
            this.$validateButton.on('click', this.validatePromoCode.bind(this));
            this.$promoCodeField.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    promoCodeForm.validatePromoCode();
                }
            });

            // Form submission
            this.$form.on('submit', this.handleSubmit.bind(this));

            // Password strength meter
            if (typeof wp !== 'undefined' && wp.hasOwnProperty('passwordStrength')) {
                this.$passwordField.on('keyup', this.checkPasswordStrength.bind(this));
            }

            // Username validation
            this.$usernameField.on('blur', this.checkUsernameAvailability.bind(this));
            
            // Email validation
            this.$emailField.on('blur', this.validateEmail.bind(this));
            
            // Password confirmation
            this.$confirmPasswordField.on('keyup', this.checkPasswordMatch.bind(this));
        },

        validatePromoCode: function() {
            const promoCode = this.$promoCodeField.val().trim();
            
            if (!promoCode) {
                this.showStatus('Please enter a promo code.', 'error');
                return;
            }

            this.$validateButton.prop('disabled', true).text(promoCodeData.messages.validating);
            this.showStatus('', '');

            $.ajax({
                url: promoCodeData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'validate_promo_code',
                    code: promoCode,
                    security: promoCodeData.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        promoCodeForm.showStatus(response.data.message, 'valid');
                        promoCodeForm.$registrationFields.slideDown();
                        promoCodeForm.$promoCodeField.data('valid', true);
                        $('html, body').animate({
                            scrollTop: promoCodeForm.$registrationFields.offset().top - 100
                        }, 500);
                    } else {
                        promoCodeForm.showStatus(response.data.message, 'error');
                        promoCodeForm.$promoCodeField.data('valid', false);
                    }
                },
                error: function() {
                    promoCodeForm.showStatus(promoCodeData.messages.error, 'error');
                },
                complete: function() {
                    promoCodeForm.$validateButton.prop('disabled', false).text('Validate');
                }
            });
        },

        handleSubmit: function(e) {
            e.preventDefault();
            
            // Basic validation
            let isValid = true;
            let firstError = null;
            
            // Check promo code
            if (!this.$promoCodeField.data('valid')) {
                this.showStatus('Please validate your promo code first.', 'error');
                if (!firstError) firstError = this.$promoCodeField;
                isValid = false;
            }
            
            // Check required fields
            this.$form.find('[required]').each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    $field.addClass('error').next('.error-message').remove();
                    $field.after('<div class="error-message visible">This field is required.</div>');
                    if (!firstError) firstError = $field;
                    isValid = false;
                } else {
                    $field.removeClass('error').next('.error-message').remove();
                }
            });
            
            // Check password match
            if (this.$passwordField.val() !== this.$confirmPasswordField.val()) {
                this.$confirmPasswordField.addClass('error').next('.error-message').remove();
                this.$confirmPasswordField.after('<div class="error-message visible">Passwords do not match.</div>');
                if (!firstError) firstError = this.$confirmPasswordField;
                isValid = false;
            } else {
                this.$confirmPasswordField.removeClass('error').next('.error-message').remove();
            }
            
            // Check password strength if available
            if (typeof wp !== 'undefined' && wp.hasOwnProperty('passwordStrength')) {
                const strength = wp.passwordStrength.meter(
                    this.$passwordField.val(),
                    wp.passwordStrength.userInputBlacklist(),
                    this.$passwordField.val()
                );
                
                if (strength < 2) {
                    this.$passwordField.addClass('error').next('.error-message').remove();
                    this.$passwordField.after('<div class="error-message visible">Please choose a stronger password.</div>');
                    if (!firstError) firstError = this.$passwordField;
                    isValid = false;
                }
            }
            
            if (!isValid) {
                // Scroll to first error
                if (firstError) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
                return false;
            }
            
            // All valid, submit the form
            this.$submitButton.prop('disabled', true).text('Processing...');
            this.$form[0].submit();
        },

        showStatus: function(message, type) {
            this.$status.text(message).removeClass('valid error');
            if (type) {
                this.$status.addClass(type);
            }
        },

        checkPasswordStrength: function() {
            const strength = wp.passwordStrength.meter(
                this.$passwordField.val(),
                wp.passwordStrength.userInputBlacklist(),
                this.$passwordField.val()
            );
            
            // Remove any existing indicators
            this.$passwordField.nextAll('.password-strength').remove();
            
            let message = '';
            let strengthClass = '';
            
            switch (strength) {
                case 0:
                case 1:
                    message = __('Very weak', 'hello-theme-child');
                    strengthClass = 'very-weak';
                    break;
                case 2:
                    message = __('Weak', 'hello-theme-child');
                    strengthClass = 'weak';
                    break;
                case 3:
                    message = __('Medium', 'hello-theme-child');
                    strengthClass = 'medium';
                    break;
                case 4:
                    message = __('Strong', 'hello-theme-child');
                    strengthClass = 'strong';
                    break;
            }
            
            if (message) {
                this.$passwordField.after(
                    '<div class="password-strength ' + strengthClass + '">' +
                    '<span class="strength-label">' + __('Strength:', 'hello-theme-child') + ' </span>' +
                    '<span class="strength-text">' + message + '</span>' +
                    '</div>'
                );
            }
        },

        checkUsernameAvailability: function() {
            const username = this.$usernameField.val().trim();
            
            if (!username) {
                return;
            }
            
            // Basic validation
            if (username.length < 4) {
                this.showFieldError(this.$usernameField, __('Username must be at least 4 characters long.', 'hello-theme-child'));
                return;
            }
            
            // Check for invalid characters
            if (!/^[a-zA-Z0-9_\-]+$/.test(username)) {
                this.showFieldError(this.$usernameField, __('Username can only contain letters, numbers, underscores, and hyphens.', 'hello-theme-child'));
                return;
            }
            
            // Check if username exists
            $.post(ajaxurl, {
                action: 'check_username',
                username: username,
                nonce: promoCodeData.nonce
            }, function(response) {
                if (response.success && response.data.available) {
                    promoCodeForm.clearFieldError(promoCodeForm.$usernameField);
                } else {
                    promoCodeForm.showFieldError(
                        promoCodeForm.$usernameField,
                        response.data.message || __('This username is already taken.', 'hello-theme-child')
                    );
                }
            }).fail(function() {
                promoCodeForm.showFieldError(
                    promoCodeForm.$usernameField,
                    __('Error checking username availability. Please try again.', 'hello-theme-child')
                );
            });
        },

        validateEmail: function() {
            const email = this.$emailField.val().trim();
            
            if (!email) {
                return;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.showFieldError(this.$emailField, __('Please enter a valid email address.', 'hello-theme-child'));
                return;
            }
            
            // Check if email exists
            $.post(ajaxurl, {
                action: 'check_email',
                email: email,
                nonce: promoCodeData.nonce
            }, function(response) {
                if (response.success && response.data.available) {
                    promoCodeForm.clearFieldError(promoCodeForm.$emailField);
                } else {
                    promoCodeForm.showFieldError(
                        promoCodeForm.$emailField,
                        response.data.message || __('This email is already registered.', 'hello-theme-child')
                    );
                }
            }).fail(function() {
                promoCodeForm.showFieldError(
                    promoCodeForm.$emailField,
                    __('Error checking email. Please try again.', 'hello-theme-child')
                );
            });
        },

        checkPasswordMatch: function() {
            if (this.$passwordField.val() !== this.$confirmPasswordField.val()) {
                this.showFieldError(
                    this.$confirmPasswordField,
                    __('Passwords do not match.', 'hello-theme-child')
                );
            } else {
                this.clearFieldError(this.$confirmPasswordField);
            }
        },

        showFieldError: function($field, message) {
            $field.addClass('error').next('.error-message').remove();
            $field.after('<div class="error-message visible">' + message + '</div>');
        },

        clearFieldError: function($field) {
            $field.removeClass('error').next('.error-message').remove();
        }
    };

    // Initialize the form
    promoCodeForm.init();
});
