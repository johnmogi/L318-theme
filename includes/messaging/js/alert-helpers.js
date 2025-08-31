/**
 * Lilac Alert Helpers
 * 
 * Helper functions to enhance the JavaScript alert integration
 * with the Lilac Toast System.
 */

(function($) {
    'use strict';
    
    // Make sure the toast system is loaded
    $(document).ready(function() {
        // Initialize the LilacAlert namespace if it doesn't exist
        if (!window.LilacAlert) {
            window.LilacAlert = {};
        }
    });
    
    /**
     * Show an alert with more control over presentation
     * 
     * @param {string} message - The message to display
     * @param {Object} options - Configuration options
     * @returns {jQuery} The toast element
     */
    window.LilacAlert = function(message, options = {}) {
        return window.alert(message, options);
    };
    
    /**
     * Show a success alert
     * 
     * @param {string} message - The message to display
     * @param {string} title - Optional title override
     * @returns {jQuery} The toast element
     */
    window.LilacAlert.success = function(message, title = 'Success') {
        return window.alert(message, {
            type: 'success',
            title: title
        });
    };
    
    /**
     * Show an error alert
     * 
     * @param {string} message - The message to display
     * @param {string} title - Optional title override
     * @returns {jQuery} The toast element
     */
    window.LilacAlert.error = function(message, title = 'Error') {
        return window.alert(message, {
            type: 'error',
            title: title,
            duration: 7000
        });
    };
    
    /**
     * Show a warning alert
     * 
     * @param {string} message - The message to display
     * @param {string} title - Optional title override
     * @returns {jQuery} The toast element
     */
    window.LilacAlert.warning = function(message, title = 'Warning') {
        return window.alert(message, {
            type: 'warning',
            title: title,
            duration: 6000
        });
    };
    
    /**
     * Intercept alert calls from third-party scripts
     * Use this when you need to monitor or modify alerts from scripts you don't control
     * 
     * @param {Function} callback - Function to call when an alert is triggered
     */
    window.LilacAlert.interceptAlerts = function(callback) {
        // Store the current alert function
        const currentAlert = window.alert;
        
        // Replace with our interceptor
        window.alert = function(message, options = {}) {
            // Call the callback with the message and options
            if (typeof callback === 'function') {
                const result = callback(message, options);
                
                // If the callback returns false, suppress the alert entirely
                if (result === false) {
                    return;
                }
                
                // If the callback returns an object, use it as the new options
                if (typeof result === 'object') {
                    options = result;
                }
            }
            
            // Call the current alert function
            return currentAlert(message, options);
        };
    };
    
    /**
     * Restore the original browser alert function
     * Use this if you need to temporarily disable the toast alert system
     */
    window.LilacAlert.restoreOriginalAlert = function() {
        window.alert = window.originalAlert || alert;
    };
    
    // Alert helpers initialized
    
})(jQuery);
