/**
 * Promo Code Admin Scripts
 */
jQuery(document).ready(function($) {
    'use strict';

    var promoCodeAdmin = {
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.setupDatepicker();
            this.setupQuickEdit();
        },

        cacheElements: function() {
            this.$promoCodeField = $('#promo_code');
            this.$generateButton = $('#generate-promo-code');
            this.$expiryDate = $('#expiry_date');
            this.$usageLimit = $('#usage_limit');
            this.$postForm = $('#post');
            this.$quickEditForm = $('.inline-edit-row');
        },

        bindEvents: function() {
            // Generate promo code
            if (this.$generateButton.length) {
                this.$generateButton.on('click', this.generatePromoCode.bind(this));
            }

            // Save post action
            if (this.$postForm.length) {
                this.$postForm.on('submit', this.validateForm.bind(this));
            }

            // Quick edit save
            $(document).on('click', '#bulk_edit', this.saveQuickEdit.bind(this));
        },

        setupDatepicker: function() {
            if (this.$expiryDate.length && $.fn.datepicker) {
                this.$expiryDate.datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    showButtonPanel: true,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: 'c-10:c+10'
                });

                // Add clear button
                $('<a href="#" class="clear-date">' + promoCodeAdminVars.clearText + '</a>')
                    .insertAfter(this.$expiryDate)
                    .on('click', function(e) {
                        e.preventDefault();
                        $('#expiry_date').val('');
                    });
            }
        },

        setupQuickEdit: function() {
            $(document).on('click', '.editinline', function() {
                var postId = $(this).closest('tr').attr('id').replace('post-', '');
                var $row = $('#edit-' + postId);
                
                // Get the promo code data
                var promoCode = $('td.promo_code', '#post-' + postId).text().trim();
                var usageLimit = $('td.usage', '#post-' + postId).data('limit');
                var expiryDate = $('td.expiry', '#post-' + postId).data('expiry') || '';
                
                // Populate the quick edit form
                $('.promo-code', $row).val(promoCode);
                $('.usage-limit', $row).val(usageLimit);
                $('.expiry-date', $row).val(expiryDate);
                
                // Setup datepicker for quick edit
                $('.expiry-date', $row).datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    showButtonPanel: true,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: 'c-10:c+10'
                });
            });
        },

        generatePromoCode: function(e) {
            e.preventDefault();
            
            // Generate a random alphanumeric code
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            var code = '';
            
            for (var i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            // Format as XXXX-XXXX
            code = code.replace(/(\w{4})(?=\w)/g, '$1-');
            
            // Set the value
            this.$promoCodeField.val(code).trigger('change');
            
            // Focus the field and select the text
            this.$promoCodeField.focus().select();
        },

        validateForm: function(e) {
            var isValid = true;
            
            // Check promo code
            if (!this.$promoCodeField.val().trim()) {
                alert(promoCodeAdminVars.messages.requiredCode);
                this.$promoCodeField.focus();
                isValid = false;
            }
            
            // Check usage limit if set
            if (this.$usageLimit.val() && isNaN(parseInt(this.$usageLimit.val()))) {
                alert(promoCodeAdminVars.messages.invalidUsage);
                this.$usageLimit.focus();
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            
            return true;
        },

        saveQuickEdit: function() {
            var $row = this.$quickEditForm;
            var postId = $('.id', $row).val();
            var data = {
                action: 'save_quick_edit_promo_code',
                post_id: postId,
                promo_code: $('.promo-code', $row).val(),
                usage_limit: $('.usage-limit', $row).val(),
                expiry_date: $('.expiry-date', $row).val(),
                nonce: promoCodeAdminVars.nonce
            };
            
            // Make the AJAX request
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    // Update the row
                    var $postRow = $('#post-' + postId);
                    
                    // Update the promo code
                    $('td.promo_code', $postRow).text(data.promo_code);
                    
                    // Update usage limit
                    $('td.usage', $postRow).data('limit', data.usage_limit);
                    
                    // Update expiry date
                    $('td.expiry', $postRow)
                        .text(data.expiry_date ? new Date(data.expiry_date).toLocaleDateString() : '—')
                        .data('expiry', data.expiry_date);
                    
                    // Show updated message
                    var $notice = $('<div class="notice notice-success is-dismissible"><p>' + promoCodeAdminVars.messages.updated + '</p></div>');
                    $('.wrap h1').after($notice);
                    
                    // Auto-dismiss after 2 seconds
                    setTimeout(function() {
                        $notice.fadeOut(400, function() {
                            $(this).remove();
                        });
                    }, 2000);
                } else {
                    alert(response.data.message || promoCodeAdminVars.messages.error);
                }
                
                // Close the quick edit
                $('td', $row).removeAttr('colspan').find('.inline-edit-row').hide();
                $('tr.inline-editor', '#the-list').remove();
                
            }).fail(function() {
                alert(promoCodeAdminVars.messages.error);
            });
            
            return false;
        }
    };

    // Initialize the admin
    promoCodeAdmin.init();
});
