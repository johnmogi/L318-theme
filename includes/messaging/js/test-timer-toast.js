/**
 * Test Timer Toast Extension
 * Handles test timing and countdown functionality
 */

(function($) {
    'use strict';

    // Default configuration
    const DEFAULTS = {
        // Total test duration in milliseconds (1 hour)
        testDuration: 60 * 60 * 1000,
        // Show warning when this much time is left (10 minutes)
        warningTime: 10 * 60 * 1000,
        // Show critical warning when this much time is left (2 minutes)
        criticalTime: 2 * 60 * 1000,
        // Update the display every second
        updateInterval: 1000,
        // Callback when test time is up
        onTimeUp: null,
        // Callback when warning time is reached
        onWarningTime: null,
        // Callback when critical time is reached
        onCriticalTime: null
    };

    let startTime = null;
    let endTime = null;
    let timerInterval = null;
    let timeLeftToast = null;
    let warningShown = false;
    let criticalWarningShown = false;

    // Format time in milliseconds to MM:SS
    const formatTime = function(ms) {
        const totalSeconds = Math.ceil(ms / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    };

    // Update the time left display
    const updateTimeLeft = function() {
        if (!startTime || !endTime) return;

        const now = Date.now();
        const remaining = endTime - now;
        
        // Check if time is up
        if (remaining <= 0) {
            clearInterval(timerInterval);
            if (timeLeftToast) {
                timeLeftToast.toast('hide');
                timeLeftToast = null;
            }
            
            if (typeof window.LilacToast.config.testTimer.onTimeUp === 'function') {
                window.LilacToast.config.testTimer.onTimeUp();
            }
            return;
        }
        
        // Check for warning time
        if (remaining <= DEFAULTS.criticalTime && !criticalWarningShown) {
            criticalWarningShown = true;
            showTimeWarning('critical');
            if (typeof window.LilacToast.config.testTimer.onCriticalTime === 'function') {
                window.LilacToast.config.testTimer.onCriticalTime();
            }
        } else if (remaining <= DEFAULTS.warningTime && !warningShown) {
            warningShown = true;
            showTimeWarning('warning');
            if (typeof window.LilacToast.config.testTimer.onWarningTime === 'function') {
                window.LilacToast.config.testTimer.onWarningTime();
            }
        }
        
        // Update the time left toast if it exists
        if (timeLeftToast) {
            const timeLeftElement = timeLeftToast.find('.time-left');
            if (timeLeftElement.length) {
                timeLeftElement.text(formatTime(remaining));
            }
        }
    };

    // Show the test timer toast
    const showTimerToast = function() {
        if (!timeLeftToast) return;
        
        timeLeftToast = window.LilacShowToast({
            title: 'Time Remaining',
            message: '<div class="test-timer"><span class="time-left">' + 
                    formatTime(DEFAULTS.testDuration) + 
                    '</span> remaining</div>',
            type: 'info',
            duration: 0, // Don't auto-dismiss
            closable: false,
            cssClass: 'test-timer-toast'
        });
    };

    // Initialize the test timer
    const init = function(options) {
        // Extend defaults with user options
        window.LilacToast.config = window.LilacToast.config || {};
        window.LilacToast.config.testTimer = $.extend({}, DEFAULTS, options);
        
        // Set start and end times
        startTime = Date.now();
        endTime = startTime + window.LilacToast.config.testTimer.testDuration;
        
        // Show the timer toast
        showTimerToast();
        
        // Start the timer
        timerInterval = setInterval(updateTimeLeft, window.LilacToast.config.testTimer.updateInterval);
        
        // Initial update
        updateTimeLeft();
        
        console.log('Test timer initialized');
    };
    
    // Extend LilacToast with test timer
    window.LilacToast = window.LilacToast || {};
    window.LilacToast.testTimer = {
        init: init,
        getTimeLeft: function() {
            return endTime ? endTime - Date.now() : 0;
        },
        formatTime: formatTime
    };
    
})(jQuery);
