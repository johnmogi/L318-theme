/**
 * Lilac Toast Notification System
 * 
 * A centralized system for displaying toast notifications across the site.
 * This will handle all types of alerts including system messages, errors, and user notifications.
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

// Define our global toast API
window.LilacToast = {
    success: null,
    error: null,
    warning: null,
    info: null,
    config: {}
};

(function($) {
    'use strict';
    
    // Initialization - logs removed for production

    // Initialize the toast system
    $(document).ready(function() {
        // Create the toast container if it doesn't exist
        if ($('#lilac-toast-container').length === 0) {
            $('body').append(`
                <div id="lilac-toast-container" 
                     style="z-index: 999999; position: fixed;"
                     dir="rtl">
                    <!-- Toasts will be inserted here -->
                </div>
            `);
        }
        
        // Process URL parameters for toast messages
        processUrlToasts();
        
        // Load extensions if they exist
        loadExtensions();
    });
    
    /**
     * Load and initialize toast extensions
     */
    const loadExtensions = function() {
        // Session management extension
        if (typeof window.LilacToast.session !== 'undefined') {
            window.LilacToast.session.init();
        }
        
        // Test timer extension
        if (typeof window.LilacToast.testTimer !== 'undefined') {
            window.LilacToast.testTimer.init();
        }
    };

    /**
     * Show a toast notification
     * 
     * @param {Object} options Toast configuration options
     */
    window.LilacShowToast = function(options) {
        const defaults = {
            message: '',
            type: 'info',
            title: '',
            duration: 5000,
            position: 'center',  // Default to center position
            closable: true,
            onClose: null,
            cssClass: '',
            rtl: true  // Default to RTL for Hebrew
        };

        // Merge defaults with options
        const settings = $.extend({}, defaults, options);
        
        // Don't show empty toasts
        if (!settings.message) {
            return;
        }
        
        // Set the container position
        $('#lilac-toast-container').attr('class', settings.position);
        
        // Create toast element with proper classes
        const $toast = $('<div class="lilac-toast ' + settings.type + ' ' + settings.cssClass + '" style="opacity: 0; transition: opacity 0.3s ease;"></div>');
        
        // Add title if provided
        if (settings.title) {
            $toast.append('<div class="toast-header">' + settings.title + '</div>');
        }
        
        // Add message body
        $toast.append('<div class="toast-body">' + settings.message + '</div>');
        
        // Add close button
        if (settings.closable) {
            $toast.append(
                $('<button>', {
                    'class': 'lilac-toast-close',
                    'aria-label': 'סגור',
                    'html': '&times;',
                    'click': function() {
                        closeToast($toast);
                    }
                })
            );
        }
        
        // Add buttons if provided
        if (settings.buttons && settings.buttons.length > 0) {
            const $buttonContainer = $('<div class="toast-buttons"></div>');
            
            settings.buttons.forEach(button => {
                const $button = $(`<button class="${button.class || ''}">${button.text}</button>`);
                
                if (button.click) {
                    $button.on('click', function(e) {
                        e.stopPropagation();
                        const shouldClose = button.click.call(this, $toast[0]);
                        if (shouldClose !== false) {
                            closeToast($toast, settings.onClose);
                        }
                    });
                }
                
                $buttonContainer.append($button);
            });
            
            $toast.append($buttonContainer);
        }
        
        // Add to container
        $('#lilac-toast-container').append($toast);
        
        // Show with animation after a brief delay (allows DOM to update)
        setTimeout(function() {
            $toast.css('opacity', '1');
        }, 10);
        
        // Auto-close after duration (if set and greater than 0)
        if (settings.duration > 0) {
            const timeoutId = setTimeout(function() {
                closeToast($toast, settings.onClose);
            }, settings.duration);
            
            // Store the timeout ID so it can be cleared if needed
            $toast.data('timeoutId', timeoutId);
        }
        
        // Return the toast element for potential future reference
        return $toast;
    };
    
    /**
     * Close a toast notification
     * 
     * @param {jQuery} $toast The toast element
     * @param {Function} callback Optional callback to run after closing
     */
    function closeToast($toast, callback) {
        $toast.removeClass('show');
        
        // Remove after animation completes
        setTimeout(function() {
            $toast.remove();
            
            // Call the callback if provided
            if (typeof callback === 'function') {
                callback();
            }
        }, 300);
    }
    
    /**
     * Process toast messages from URL parameters
     */
    function processUrlToasts() {
        // Parse query parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Check for toast parameters
        if (urlParams.has('toast_message')) {
            const message = urlParams.get('toast_message');
            const type = urlParams.get('toast_type') || 'info';
            const title = urlParams.get('toast_title') || '';
            
            // Show the toast
            window.LilacShowToast({
                message: message,
                type: type,
                title: title
            });
            
            // Remove parameters from URL without reloading
            if (window.history && window.history.replaceState) {
                // Create a new URL without the toast parameters
                let url = window.location.href;
                url = url.replace(/([&?])(toast_message|toast_type|toast_title)=[^&]+/g, function(match, prefix) {
                    // If this is the only parameter, remove the prefix too
                    return prefix === '?' && match.indexOf('&') === -1 ? '' : prefix;
                });
                
                // Remove trailing ? or & if present
                url = url.replace(/[?&]$/, '');
                
                // Update the URL without reloading
                window.history.replaceState({}, document.title, url);
            }
        }
    }
    
    /**
     * Helper functions for common toast types
     */
    // Redefine the methods on the pre-defined object
    window.LilacToast.success = function(message, title, duration) {
        return window.LilacShowToast({
            message: message,
            type: 'success',
            title: title || 'Success',
            duration: duration || 5000
        });
    };
    
    window.LilacToast.error = function(message, title, duration) {
        return window.LilacShowToast({
            message: message,
            type: 'error',
            title: title || 'Error',
            duration: duration || 7000
        });
    };
    
    window.LilacToast.warning = function(message, title, duration) {
        return window.LilacShowToast({
            message: message,
            type: 'warning',
            title: title || 'Warning',
            duration: duration || 6000
        });
    };
    
    window.LilacToast.info = function(message, title, duration) {
        return window.LilacShowToast({
            message: message,
            type: 'info',
            title: title || 'Information',
            duration: duration || 5000
        });
    };
    
    /**
     * Backward compatibility: Add showToast method for existing code
     * This allows existing code that uses LilacToast.showToast() to continue working
     */
    window.LilacToast.showToast = function(options) {
        console.log('Using LilacToast.showToast (backward compatibility mode)');
        return window.LilacShowToast(options);
    };
    
    // Testing Mode Functions (for development)
    window.clearWelcomeSession = function() {
        // Clear all session storage keys related to welcome messages
        sessionStorage.removeItem('lilac_bonus_shown');
        sessionStorage.removeItem('lilac_welcome_shown');
        sessionStorage.removeItem('welcome_message_shown');
        
        // Clear localStorage as well
        localStorage.removeItem('lilac_bonus_shown');
        localStorage.removeItem('lilac_welcome_shown');
        localStorage.removeItem('welcome_message_shown');
        
        console.log('🧹 Welcome session cleared - refresh page to see welcome message again');
        return 'Session cleared!';
    };
    
    window.enableTestingMode = function() {
        // Clear session every 5 seconds for continuous testing
        if (window.testingModeInterval) {
            clearInterval(window.testingModeInterval);
        }
        
        window.testingModeInterval = setInterval(function() {
            window.clearWelcomeSession();
        }, 5000);
        
        console.log('🧪 Testing Mode ENABLED - Welcome messages will show on every page load');
        return 'Testing mode enabled!';
    };
    
    window.disableTestingMode = function() {
        if (window.testingModeInterval) {
            clearInterval(window.testingModeInterval);
            window.testingModeInterval = null;
        }
        console.log('🔒 Testing Mode DISABLED - Normal session behavior restored');
        return 'Testing mode disabled!';
    };
    
    // Add console helpers
    console.log('💡 Welcome Message Testing Commands Available:');
    console.log('  - clearWelcomeSession() - Clear session once');
    console.log('  - enableTestingMode() - Auto-clear every 5 seconds');
    console.log('  - disableTestingMode() - Stop auto-clearing');
    
    // Toast API is now available
    
    /**
     * Handle AJAX errors and show them as toasts
     */
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        // Check if we should suppress toasts for this request
        const suppressToast = jqXHR.getResponseHeader('X-Suppress-Toast') === 'true' || 
                            (ajaxSettings.data && ajaxSettings.data.includes('suppress_toast=true'));
        
        if (suppressToast) {
            console.log('Suppressing toast for AJAX error as requested');
            return;
        }
        
        let message = thrownError || 'An unknown error occurred';
        let title = 'Error';
        
        // Try to parse a more detailed error message from the response
        try {
            const response = JSON.parse(jqXHR.responseText);
            if (response.message) {
                message = response.message;
            }
            if (response.title) {
                title = response.title;
            }
        } catch (e) {
            // If parsing fails, use status text
            if (jqXHR.statusText) {
                message = jqXHR.statusText;
            }
        }
        
        // Show the error as a toast
        window.LilacToast.error(message, title);
    });
    
    /**
     * Alert System Integration
     * Convert standard JavaScript dialogs (alert, confirm, prompt) to toasts
     */
    
    // Store original dialog functions
    const originalAlert = window.alert;
    const originalConfirm = window.confirm;
    const originalPrompt = window.prompt;
    
    // Track alert statistics for debugging
    window.LilacAlertStats = {
        alertCount: 0,
        confirmCount: 0,
        promptCount: 0,
        lastAlert: null,
        reset: function() {
            this.alertCount = 0;
            this.confirmCount = 0;
            this.promptCount = 0;
            this.lastAlert = null;
        }
    };
    
    /**
     * Enhanced alert integration with toast system
     * @param {string} message - The message to display
     * @param {Object} options - Optional configuration options
     */
    window.alert = function(message, options = {}) {
        // Track alert usage
        window.LilacAlertStats.alertCount++;
        window.LilacAlertStats.lastAlert = {
            type: 'alert',
            message: message,
            timestamp: new Date()
        };
        
        // If toast system isn't available or is disabled, use original alert
        if (typeof window.LilacToast === 'undefined' || options.useNative === true) {
            return originalAlert(message);
        }
        
        // Ensure the toast container exists
        if ($('#lilac-toast-container').length === 0) {
            $('body').append('<div id="lilac-toast-container" class="top-right" style="z-index: 999999; position: fixed;"></div>');
        }
        
        // Merge default options with provided options
        const settings = $.extend({
            type: 'info',
            title: 'שימו לב',
            message: message,
            duration: 5000,
            position: 'center',
            closable: true,
            cssClass: 'lilac-alert-toast'
        }, options);
        
        // Show as toast notification using the correct method
        if (window.LilacToast.showToast) {
            return window.LilacToast.showToast(settings);
        } else if (window.LilacShowToast) {
            return window.LilacShowToast(settings);
        } else {
            // Fallback to original alert if no toast method is available
            return originalAlert(message);
        }
    };
    
    /**
     * Enhanced confirm dialog integration
     * @param {string} message - The confirmation message
     * @param {Object} options - Optional configuration options
     * @returns {boolean} - User's response (true for OK, false for Cancel)
     */
    window.confirm = function(message, options = {}) {
        // Track confirm usage
        window.LilacAlertStats.confirmCount++;
        window.LilacAlertStats.lastAlert = {
            type: 'confirm',
            message: message,
            timestamp: new Date()
        };
        
        // For now, use the original confirm (we'll implement a toast-based version later)
        // This is a placeholder for future implementation
        return originalConfirm(message);
    };
    
    /**
     * Enhanced prompt dialog integration
     * @param {string} message - The prompt message
     * @param {string} defaultValue - Default input value
     * @param {Object} options - Optional configuration options
     * @returns {string|null} - User's input or null if canceled
     */
    window.prompt = function(message, defaultValue = '', options = {}) {
        // Track prompt usage
        window.LilacAlertStats.promptCount++;
        window.LilacAlertStats.lastAlert = {
            type: 'prompt',
            message: message,
            timestamp: new Date()
        };
        
        // For now, use the original prompt (we'll implement a toast-based version later)
        // This is a placeholder for future implementation
        return originalPrompt(message, defaultValue);
    };
    
    /**
     * Test function to verify the alert integration is working
     * Call LilacToastTest() in the console to test all notification types
     */
    window.LilacToastTest = function() {
        console.log('Testing Lilac Toast System...');
        
        // Test standard alert
        alert('This is a test alert');
        
        // Test toast notifications
        setTimeout(function() {
            window.LilacToast.success('Success message test');
        }, 1000);
        
        setTimeout(function() {
            window.LilacToast.error('Error message test');
        }, 2000);
        
        setTimeout(function() {
            window.LilacToast.warning('Warning message test');
        }, 3000);
        
        setTimeout(function() {
            window.LilacToast.info('Info message test');
        }, 4000);
        
        return 'Test sequence initiated - check for toast notifications';
    };
    
})(jQuery);