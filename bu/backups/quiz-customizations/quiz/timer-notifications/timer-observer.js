/**
 * Timer Observer for LearnDash Quizzes
 * 
 * Monitors the LearnDash timer and provides hooks for notifications.
 */

(function($) {
    'use strict';
    
    // Global LilacTimerObserver object
    window.LilacTimerObserver = {
        timerElement: null,
        secondsLeft: 0,
        thresholds: [60, 30, 10],
        triggeredThresholds: {},
        demoMode: false,
        timeDisplay: null,
        
        init: function() {
            console.log('Timer Observer initializing...');
            this.findTimerElement();
            this.setupDemoMode();
            this.timeDisplay = $('#lilac-time-display');
            
            // Update display initially
            this.updateTimeDisplay();
        },
        
        findTimerElement: function() {
            // Find the LearnDash timer element
            this.timerElement = $('.wpProQuiz_time_limit .time span');
            
            if (this.timerElement.length) {
                console.log('Timer element found:', this.timerElement.text());
                
                // Set initial seconds
                this.secondsLeft = this.parseTime(this.timerElement.text().trim());
                console.log('Initial time in seconds:', this.secondsLeft);
                
                // Start observing
                this.startObserver();
            } else {
                // Retry after a delay as LearnDash may load elements asynchronously
                setTimeout(this.findTimerElement.bind(this), 1000);
            }
        },
        
        parseTime: function(timeStr) {
            const parts = timeStr.split(':').map(part => parseInt(part, 10));
            if (parts.length === 3) {
                return parts[0] * 3600 + parts[1] * 60 + parts[2];
            } else if (parts.length === 2) {
                return parts[0] * 60 + parts[1];
            } else {
                return parts[0] || 0;
            }
        },
        
        formatTime: function(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            } else {
                return `${minutes}:${secs.toString().padStart(2, '0')}`;
            }
        },
        
        startObserver: function() {
            if (!this.timerElement.length) return;
            
            // Use a more reliable approach - check the timer value periodically
            // This is less prone to issues than MutationObserver
            let lastLoggedTime = null;
            
            setInterval(() => {
                const text = this.timerElement.text().trim();
                const currentSeconds = this.parseTime(text);
                
                if (currentSeconds !== this.secondsLeft) {
                    // Only log when significant changes occur (every 30 seconds) to reduce noise
                    if (!lastLoggedTime || lastLoggedTime - currentSeconds >= 30) {
                        console.log('Time changed to', text);
                        lastLoggedTime = currentSeconds;
                    }
                    
                    this.timeChanged(currentSeconds);
                    this.secondsLeft = currentSeconds;
                    this.updateTimeDisplay();
                }
            }, 500); // Check every half second
        },
        
        timeChanged: function(seconds) {
            // Check thresholds
            this.thresholds.forEach(threshold => {
                if (seconds <= threshold && !this.triggeredThresholds[threshold]) {
                    this.triggeredThresholds[threshold] = true;
                    this.triggerThresholdWarning(threshold);
                }
            });
            
            // Check for timer completion
            if (seconds === 0 && !this.triggeredThresholds.zero) {
                this.triggeredThresholds.zero = true;
                this.triggerTimerEnd();
            }
        },
        
        triggerThresholdWarning: function(threshold) {
            console.log(`Threshold warning triggered: ${threshold} seconds`);
            
            // Only trigger in demo mode or live when timer is really at this value
            if (this.demoMode || this.secondsLeft === threshold) {
                const messages = {
                    60: 'דקה אחת נותרה!',
                    30: '30 שניות נותרו!',
                    10: 'זהירות! 10 שניות נותרו!'
                };
                
                if (window.LilacToast) {
                    window.LilacToast.showToast({
                        type: 'warning',
                        message: messages[threshold] || `${threshold} seconds remaining!`,
                        autoClose: 5000,
                        messageId: `timer-${threshold}-seconds`
                    });
                } else {
                    alert(messages[threshold] || `${threshold} seconds remaining!`);
                }
            }
        },
        
        triggerTimerEnd: function() {
            console.log('Timer ended');
            
            if (this.demoMode || this.secondsLeft === 0) {
                if (window.LilacToast) {
                    window.LilacToast.showToast({
                        type: 'error',
                        message: 'לא ענית על מספיק שאלות. חזור למבחן.',
                        autoClose: 0,
                        messageId: 'timer-ended'
                    });
                } else {
                    alert('לא ענית על מספיק שאלות. חזור למבחן.');
                }
            }
        },
        
        setupDemoMode: function() {
            // Setup demo mode toggle
            $(document).on('change', '#lilac-demo-mode', e => {
                this.demoMode = $(e.target).prop('checked');
                console.log('Demo mode changed to:', this.demoMode);
            });
        },
        
        updateTimeDisplay: function() {
            if (this.timeDisplay && this.timeDisplay.length) {
                this.timeDisplay.text(`Current time: ${this.formatTime(this.secondsLeft)}`);
            }
            
            // Also update existing test panel if present
            const testPanel = $('#acf-hint-test');
            if (testPanel.length) {
                let timeInfo = $('<div>Timer: ' + this.formatTime(this.secondsLeft) + '</div>');
                
                // Remove existing timer info if any
                testPanel.find('div.timer-info').remove();
                
                // Add the new timer info with a class for easy targeting
                timeInfo.addClass('timer-info');
                testPanel.append(timeInfo);
            }
        },
        
        // Method for UI to trigger test notifications
        testNotification: function(seconds) {
            if (seconds === 0) {
                this.triggerTimerEnd();
            } else if ([60, 30, 10].includes(parseInt(seconds))) {
                this.triggerThresholdWarning(parseInt(seconds));
            } else if (seconds === 'inactive') {
                if (window.LilacToast) {
                    window.LilacToast.showToast({
                        type: 'info',
                        message: 'לא זיהינו תנועה באתר כבר זמן מה. אתה עדיין כאן? כן/לא',
                        autoClose: 0,
                        messageId: 'inactivity'
                    });
                } else {
                    alert('לא זיהינו תנועה באתר כבר זמן מה. אתה עדיין כאן? כן/לא');
                }
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        LilacTimerObserver.init();
    });
    
})(jQuery);
