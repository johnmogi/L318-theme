/**
 * Toast Extensions Demo
 * Demonstrates the usage of toast extensions
 */

(function($) {
    'use strict';

    // Demo functionality - only initialize when explicitly requested
    // Do NOT auto-run demos to avoid unwanted test messages
    $(document).ready(function() {
        // Only add demo controls if explicitly enabled
        if (window.location.search.includes('lilac-demo=1') || window.lilacDemoMode === true) {
            console.log('Lilac Toast Demo Mode Enabled');
            initializeDemoMode();
        }
    });
    
    // Demo initialization function (only runs when explicitly enabled)
    function initializeDemoMode() {
        // Initialize session management with custom settings
        if (typeof window.LilacToast.session !== 'undefined') {
            window.LilacToast.session.init({
                sessionTimeout: 5 * 60 * 1000, // 5 minutes for demo purposes
                warningBeforeTimeout: 30 * 1000, // Show warning 30 seconds before timeout
                warningToastDuration: 0, // Don't auto-dismiss the warning
                onSessionAboutToExpire: function() {
                    console.log('Session is about to expire!');
                },
                onSessionExpired: function() {
                    console.log('Session has expired!');
                    window.LilacToast.info('Your session has expired. Please log in again.');
                },
                onSessionExtended: function() {
                    console.log('Session extended!');
                    window.LilacToast.success('Session extended!');
                }
            });
        }

        // Initialize test timer
        if (typeof window.LilacToast.testTimer !== 'undefined') {
            window.LilacToast.testTimer.init({
                testDuration: 10 * 60 * 1000, // 10 minutes for demo
                warningTime: 5 * 60 * 1000, // Show warning at 5 minutes
                criticalTime: 1 * 60 * 1000, // Show critical warning at 1 minute
                onTimeUp: function() {
                    window.LilacToast.error('Time is up! Your test will be submitted automatically.');
                },
                onWarningTime: function() {
                    window.LilacToast.warning('נותרו פחות מ-5 דקות!');
                },
                onCriticalTime: function() {
                    window.LilacToast.error('Less than 1 minute remaining! Hurry up!');
                }
            });
        }

        // Add demo controls to the page
        addDemoControls();
    }

    // Add demo controls to the page
    function addDemoControls() {
        const $controls = $(`
            <div class="toast-demo-controls" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;">Toast Demo Controls</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button id="simulate-activity" class="button button-primary">Simulate Activity</button>
                    <button id="reset-session" class="button">Reset Session</button>
                    <button id="show-test-timer" class="button">Show Test Timer</button>
                    <button id="reset-test-timer" class="button">Reset Test Timer</button>
                </div>
            </div>
        `);

        $('body').append($controls);

        // Event handlers
        $('#simulate-activity').on('click', function() {
            if (typeof window.LilacToast.session !== 'undefined') {
                window.LilacToast.session.trackActivity();
                window.LilacToast.success('Activity simulated! Session extended.');
            } else {
                window.LilacToast.error('Session extension not available');
            }
        });

        $('#reset-session').on('click', function() {
            if (typeof window.LilacToast.session !== 'undefined') {
                window.LilacToast.session.init({
                    sessionTimeout: 5 * 60 * 1000,
                    warningBeforeTimeout: 30 * 1000
                });
                window.LilacToast.success('Session timer reset!');
            } else {
                window.LilacToast.error('Session management not available');
            }
        });

        $('#show-test-timer').on('click', function() {
            if (typeof window.LilacToast.testTimer !== 'undefined') {
                window.LilacToast.testTimer.init({
                    testDuration: 10 * 60 * 1000,
                    warningTime: 5 * 60 * 1000,
                    criticalTime: 1 * 60 * 1000
                });
            } else {
                window.LilacToast.error('Test timer not available');
            }
        });

        $('#reset-test-timer').on('click', function() {
            if (typeof window.LilacToast.testTimer !== 'undefined') {
                window.LilacToast.testTimer.init({
                    testDuration: 10 * 60 * 1000,
                    warningTime: 5 * 60 * 1000,
                    criticalTime: 1 * 60 * 1000
                });
                window.LilacToast.success('Test timer reset!');
            } else {
                window.LilacToast.error('Test timer not available');
            }
        });
    }

})(jQuery);
