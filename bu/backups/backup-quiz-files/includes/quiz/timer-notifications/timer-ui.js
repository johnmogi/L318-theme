/**
 * Timer UI Components for LearnDash Quizzes
 * 
 * Provides UI for testing and controlling timer notifications.
 */

(function($) {
    'use strict';
    
    // LilacTimerUI object
    const LilacTimerUI = {
        init: function() {
            console.log('Timer UI initializing...');
            this.setupEventHandlers();
            this.positionControlPanel();
            this.addTestButtons();
        },
        
        setupEventHandlers: function() {
            // Test buttons
            $('.lilac-test-btn').on('click', function() {
                const seconds = $(this).data('seconds');
                const message = $(this).data('message');
                
                if (window.LilacTimerObserver) {
                    if (message) {
                        window.LilacTimerObserver.testNotification(message);
                    } else {
                        window.LilacTimerObserver.testNotification(seconds);
                    }
                } else {
                    console.error('Timer Observer not available');
                }
            });
            
            // Toggle control panel visibility using Alt+Q instead of Ctrl+Shift+T
            // (which is reserved for browser's reopen closed tab function)
            $(document).on('keydown', function(e) {
                // Alt+Q to toggle
                if (e.altKey && e.which === 81) {
                    $('#lilac-timer-controls').toggle();
                }
            });
        },
        
        positionControlPanel: function() {
            // Position the control panel at the top of the quiz
            const quizElement = $('.wpProQuiz_content');
            if (quizElement.length) {
                $('#lilac-timer-controls').prependTo(quizElement);
            }
        },
        
        // Add timer test buttons to existing ACF hint test panel if it exists
        addTestButtons: function() {
            // Check if there's an existing test panel to integrate with
            const testPanel = $('#acf-hint-test');
            if (!testPanel.length) return;
            
            // Create timer test buttons
            const timerButtons = $('<div class="timer-test-buttons"></div>');
            
            // Create button container 
            timerButtons.append('<div><strong>Timer Tests:</strong></div>');
            
            // Add test buttons
            [
                { label: '60s Warning', seconds: 60 },
                { label: '30s Warning', seconds: 30 },
                { label: '10s Warning', seconds: 10 },
                { label: 'Timer End', seconds: 0 },
                { label: 'Inactive', message: 'inactive' }
            ].forEach(btn => {
                const $btn = $('<button>')
                    .text(btn.label)
                    .css({
                        'margin': '2px',
                        'padding': '3px 5px',
                        'font-size': '11px',
                        'background': '#0073aa',
                        'color': 'white',
                        'border': 'none',
                        'border-radius': '3px',
                        'cursor': 'pointer'
                    })
                    .on('click', function() {
                        if (window.LilacTimerObserver) {
                            if (btn.message) {
                                window.LilacTimerObserver.testNotification(btn.message);
                            } else {
                                window.LilacTimerObserver.testNotification(btn.seconds);
                            }
                        }
                    });
                
                timerButtons.append($btn);
            });
            
            // Add demo mode checkbox
            const demoMode = $('<div></div>');
            demoMode.append('<label><input type="checkbox" id="lilac-demo-mode"> Demo Mode</label>');
            timerButtons.append(demoMode);
            
            // Add to the existing panel
            testPanel.append(timerButtons);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        LilacTimerUI.init();
    });
    
})(jQuery);
