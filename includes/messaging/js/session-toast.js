/**
 * Session Management Toast Extensions
 * Handles session timeouts and user activity monitoring
 */

(function($) {
    'use strict';

    // Get settings from admin panel or use defaults
    const adminSettings = window.lilacToastAdmin || {};
    const progressSettings = adminSettings.progressSettings || {};
    
    // Default configuration with admin settings integration
    const DEFAULTS = {
        // Session timeout in milliseconds (45 minutes for non-admin users, admin settings for others)
        sessionTimeout: 45 * 60 * 1000, // 45 minutes for all users
        // Warning time in milliseconds (5 minutes before timeout)
        warningBefore: 5 * 60 * 1000, // 5 minutes warning
        // How long to show the warning toast (0 = until user action)
        warningToastDuration: 0,
        // Activity events to track
        activityEvents: ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'],
        // Callback when session is about to expire
        onSessionAboutToExpire: null,
        // Callback when session has expired
        onSessionExpired: function() {
            // Never auto-logout users - just show a warning
            window.LilacToast.warning('הפעילות שלך פגה. לחץ על רענון כדי להמשיך.', 'פעילות פגה');
            
            // Clear any existing timers to prevent further actions
            if (sessionTimer) {
                clearTimeout(sessionTimer);
                sessionTimer = null;
            }
            
            // Reset activity tracking
            lastActivity = Date.now();
            warningShown = false;
            
            // Restart the session timer with a fresh 45-minute window
            sessionTimer = setTimeout(checkSession, DEFAULTS.sessionTimeout - DEFAULTS.warningBefore);
            
            // Return false to indicate we've handled the expiration
            return false;
        },
        // Callback when session is extended
        onSessionExtended: null
    };

    // Keep track of the last activity timestamp
    let lastActivity = Date.now();
    let warningShown = false;
    let warningToast = null;
    let sessionTimer = null;
    let activityCheckInterval = null;
    
    // Check if user has admin/instructor/teacher privileges
    const checkUserRole = function() {
        // Check various indicators for admin/privileged users
        const bodyClasses = document.body.className;
        
        // WordPress admin indicators
        const isWpAdmin = bodyClasses.includes('wp-admin') || 
                         bodyClasses.includes('admin-bar') ||
                         window.location.href.includes('/wp-admin/');
        
        // LearnDash instructor/teacher indicators
        const isInstructor = bodyClasses.includes('sfwd-instructor') ||
                           bodyClasses.includes('group_leader') ||
                           bodyClasses.includes('course_author');
        
        // Check WordPress capabilities if available
        let hasAdminCaps = false;
        if (window.wp && window.wp.data) {
            try {
                const user = window.wp.data.select('core').getCurrentUser();
                hasAdminCaps = user && (user.capabilities?.manage_options || 
                                      user.capabilities?.edit_courses ||
                                      user.capabilities?.edit_lessons);
            } catch (e) {
                // Ignore errors if WP data not available
            }
        }
        
        // Check for admin role in global variables
        const hasAdminRole = window.lilacUserRole === 'administrator' ||
                           window.lilacUserRole === 'instructor' ||
                           window.lilacUserRole === 'group_leader';
        
        return isWpAdmin || isInstructor || hasAdminCaps || hasAdminRole;
    };

    // Track user activity
    const trackActivity = function() {
        lastActivity = Date.now();
        if (warningShown) {
            hideWarning();
            if (typeof window.LilacToast.config.session.onSessionExtended === 'function') {
                window.LilacToast.config.session.onSessionExtended();
            }
        }
    };

    // Show warning toast
    const showWarning = function() {
        if (warningShown) return;
        
        const timeLeft = Math.ceil((DEFAULTS.sessionTimeout - (Date.now() - lastActivity)) / 1000 / 60);
        
        // Hebrew text with proper pluralization
        const minutesText = timeLeft === 1 ? 'דקה' : 'דקות';
        
        // Create toast options
        const toastOptions = {
            title: 'האם אתה עדיין בעמוד זה?',
            message: `הפעילות שלך תפוג בעוד ${timeLeft} ${minutesText}. האם להמשיך?`,
            type: 'warning',
            duration: 0, // Don't auto-dismiss
            dismissible: false, // Force user to choose
            buttons: [
                {
                    text: 'כן',
                    class: 'button button-primary resume-timer',
                    click: function() {
                        // Extend session by 45 minutes
                        lastActivity = Date.now();
                        const extensionTime = 45 * 60 * 1000; // 45 minutes
                        DEFAULTS.sessionTimeout = extensionTime;
                        
                        // Clear existing timer and restart
                        if (sessionTimer) {
                            clearTimeout(sessionTimer);
                        }
                        
                        // Start new session timer
                        sessionTimer = setTimeout(checkSession, extensionTime - DEFAULTS.warningBefore);
                        
                        // Show confirmation
                        if (window.LilacToast && window.LilacToast.success) {
                            window.LilacToast.success('הפעילות הוארכה ב-45 דקות נוספות!', 'פעילות הוארכה');
                        }
                        
                        if (typeof window.LilacToast.config.session.onSessionExtended === 'function') {
                            window.LilacToast.config.session.onSessionExtended();
                        }
                        
                        return true; // Close the toast
                    }
                },
                {
                    text: 'התנתק',
                    class: 'button button-link',
                    click: function() {
                        // Show a message that they should log out manually
                        window.LilacToast.info('אנא התנתק דרך התפריט שלך', 'התנתקות', {
                            duration: 3000
                        });
                        
                        // Don't perform any automatic logout that could trigger bans
                        return true; // Close the toast
                    }
                }
            ]
        };
        
        // Use the appropriate toast method based on what's available
        if (window.LilacToast && typeof window.LilacToast.showToast === 'function') {
            warningToast = window.LilacToast.showToast(toastOptions);
        } else if (window.LilacShowToast) {
            warningToast = window.LilacShowToast(toastOptions);
        } else {
            console.error('Toast system not available');
            return;
        }
        
        warningShown = true;
    };

    // Hide warning toast
    const hideWarning = function() {
        if (warningToast) {
            // Check if the toast has a close method (from LilacShowToast)
            if (typeof warningToast.close === 'function') {
                warningToast.close();
            }
            // Fallback for Bootstrap toast
            else if (warningToast.toast) {
                warningToast.toast('hide');
            }
            warningToast = null;
        }
        warningShown = false;
    };

    // Check session status
    const checkSession = function() {
        const timeSinceLastActivity = Date.now() - lastActivity;
        const timeUntilTimeout = DEFAULTS.sessionTimeout - timeSinceLastActivity;
        
        // If we're within the warning period, show the warning
        if (timeUntilTimeout > 0 && timeUntilTimeout <= DEFAULTS.warningBeforeTimeout && !warningShown) {
            showWarning();
        }
        // If session has expired
        else if (timeUntilTimeout <= 0) {
            clearInterval(activityCheckInterval);
            if (typeof window.LilacToast.config.session.onSessionExpired === 'function') {
                window.LilacToast.config.session.onSessionExpired();
            }
        }
    };

    // Initialize session management
    const init = function(options) {
        // Extend defaults with user options
        window.LilacToast.config = window.LilacToast.config || {};
        window.LilacToast.config.session = $.extend({}, DEFAULTS, options);
        
        // Set up activity tracking
        $(document).on('mousemove keydown mousedown touchstart', trackActivity);
        
        // Start checking session status
        activityCheckInterval = setInterval(checkSession, DEFAULTS.activityCheckInterval);
        
        // Initial check
        checkSession();
        
        console.log('Session management initialized');
    };
    
    // Extend LilacToast with session management
    window.LilacToast = window.LilacToast || {};
    window.LilacToast.session = {
        init: init,
        trackActivity: trackActivity,
        showWarning: showWarning,
        hideWarning: hideWarning
    };
    
})(jQuery);
