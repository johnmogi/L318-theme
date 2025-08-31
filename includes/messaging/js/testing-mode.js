/**
 * Testing Mode for Welcome Messages
 * 
 * This script disables session blocking so you can see welcome messages on every page load
 */

(function($) {
    'use strict';
    
    console.log('🧪 Testing Mode: Welcome Message Session Blocking Disabled');
    
    // Clear all session storage keys related to welcome messages
    const clearWelcomeSession = function() {
        // Clear lilac bonus session
        sessionStorage.removeItem('lilac_bonus_shown');
        
        // Clear any other welcome-related session keys
        sessionStorage.removeItem('lilac_welcome_shown');
        sessionStorage.removeItem('welcome_message_shown');
        
        // Clear localStorage as well
        localStorage.removeItem('lilac_bonus_shown');
        localStorage.removeItem('lilac_welcome_shown');
        localStorage.removeItem('welcome_message_shown');
        
        console.log('🧹 Cleared all welcome message session flags');
    };
    
    // Clear session on page load
    $(document).ready(function() {
        clearWelcomeSession();
        
        // Also clear every 5 seconds to ensure messages keep showing
        setInterval(clearWelcomeSession, 5000);
    });
    
    // Add a global function to manually clear session
    window.clearWelcomeSession = clearWelcomeSession;
    
    // Add console helper
    console.log('💡 Testing Mode Active: Run clearWelcomeSession() in console to manually clear session');
    
})(jQuery);
