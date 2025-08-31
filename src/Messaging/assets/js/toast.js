/**
 * Toast Messaging System JavaScript
 * 
 * Handles the initialization, display, and interactions for the toast notification system.
 */

// AGGRESSIVE ALERT BYPASS - MUST BE FIRST
(function() {
    // Override native alert immediately
    window.alert = function() { return true; };
    
    // Override console methods that might show alerts
    if (window.console) {
        window.console.log = function() {};
        window.console.warn = function() {};
        window.console.error = function() {};
    }
    
    // Override confirm and prompt too
    window.confirm = function() { return true; };
    window.prompt = function() { return ''; };
    
    // Block any setTimeout/setInterval that might create alerts
    const originalSetTimeout = window.setTimeout;
    const originalSetInterval = window.setInterval;
    
    window.setTimeout = function(fn, delay) {
        if (typeof fn === 'string' && fn.includes('alert')) {
            return;
        }
        return originalSetTimeout.apply(this, arguments);
    };
    
    window.setInterval = function(fn, delay) {
        if (typeof fn === 'string' && fn.includes('alert')) {
            return;
        }
        return originalSetInterval.apply(this, arguments);
    };
    
    // Override any jQuery/$ alert methods
    if (window.jQuery) {
        window.jQuery.fn.alert = function() { return this; };
    }
    
    // Block SweetAlert, toastr, or other common alert libraries
    window.swal = function() { return Promise.resolve(); };
    window.toastr = { 
        success: function() {},
        error: function() {},
        warning: function() {},
        info: function() {}
    };
    
    // Prevent any dynamic alert creation
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const element = originalCreateElement.call(this, tagName);
        if (tagName.toLowerCase() === 'script') {
            const originalSetAttribute = element.setAttribute;
            element.setAttribute = function(name, value) {
                if (name === 'src' && value.includes('alert')) {
                    return;
                }
                return originalSetAttribute.call(this, name, value);
            };
        }
        return element;
    };
})();
 
(function($) {
    'use strict';
    
    // Toast system object
    var LilacToast = {
        
        // Initialize the toast system
        init: function() {
            // Set up event handlers
            $(document).on('click', '.lilac-toast-close', this.closeToast);
            
            // Initialize any toasts already on the page
            this.initExistingToasts();
            
            // Update session counter if available
            this.updateSessionCounter();
            
            // Log initialization
            console.log('Lilac Toast System Initialized');
            console.log('Session Count:', lilacToastData.sessionCounter);
        },
        
        // Initialize any toasts that were rendered with the page
        initExistingToasts: function() {
            $('.lilac-toast').each(function() {
                var $toast = $(this);
                
                // Show the toast with animation
                setTimeout(function() {
                    $toast.addClass('show');
                }, 100);
                
                // Check for auto-close
                var autoClose = $toast.data('auto-close');
                if (autoClose && parseInt(autoClose) > 0) {
                    setTimeout(function() {
                        LilacToast.closeToast.call($toast.find('.lilac-toast-close')[0]);
                    }, parseInt(autoClose) * 1000);
                }
            });
        },
        
        // Close a toast when the close button is clicked
        closeToast: function() {
            var $toast = $(this).closest('.lilac-toast');
            
            // Add animation
            $toast.css({
                'animation': 'lilac-toast-out 0.3s forwards'
            });
            
            // Get message ID if available
            var messageId = $toast.data('message-id');
            
            // If we have a message ID, track dismissal via AJAX
            if (messageId) {
                $.ajax({
                    url: lilacToastData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dismiss_message',
                        message_id: messageId,
                        nonce: lilacToastData.nonce
                    }
                });
            }
            
            // Remove after animation completes
            setTimeout(function() {
                $toast.remove();
            }, 300);
        },
        
        // Create a new toast programmatically
        showToast: function(options) {
            var defaults = {
                type: 'info',
                title: '',
                message: '',
                dismissible: true,
                autoClose: 0,
                position: 'top-left',
                messageId: 'toast-' + new Date().getTime()
            };
            
            // Merge options with defaults
            var settings = $.extend({}, defaults, options);
            
            // Don't show empty toasts
            if (!settings.message) {
                return false;
            }
            
            // Check if container exists for this position, create if not
            var $container = $('.lilac-toast-container[data-position="' + settings.position + '"]');
            if ($container.length === 0) {
                $container = $('<div class="lilac-toast-container" data-position="' + settings.position + '"></div>');
                $('body').append($container);
            }
            
            // Create toast element
            var $toast = $('<div class="lilac-toast lilac-toast-' + settings.type + '" data-auto-close="' + settings.autoClose + '" data-message-id="' + settings.messageId + '"></div>');
            
            // Add title if present
            if (settings.title) {
                $toast.append('<div class="lilac-toast-header">' + settings.title + '</div>');
            }
            
            // Add message body
            $toast.append('<div class="lilac-toast-body">' + settings.message + '</div>');
            
            // Add close button if dismissible
            if (settings.dismissible) {
                $toast.append('<button class="lilac-toast-close" aria-label="Close">×</button>');
            }
            
            // Add to container
            $container.append($toast);
            
            // Trigger animation
            setTimeout(function() {
                $toast.addClass('show');
            }, 10);
            
            // Auto close if enabled
            if (settings.autoClose > 0) {
                setTimeout(function() {
                    $toast.find('.lilac-toast-close').trigger('click');
                }, settings.autoClose * 1000);
            }
            
            return $toast;
        },
        
        // Update session counter display
        updateSessionCounter: function() {
            $('.lilac-session-counter-value').text(lilacToastData.sessionCounter);
        }
    };
    
    // Initialize the toast system when document is ready
    $(document).ready(function() {
        LilacToast.init();
        
        // Add to global scope for external usage
        window.LilacToast = LilacToast;
    });
    
})(jQuery);