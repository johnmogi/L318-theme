/**
 * LearnDash Timer Hook
 *
 * Hooks into the built-in WPProQuiz timer to display custom toasts.
 */

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        var timeSpan = document.querySelector('.wpProQuiz_time_limit .time span');
        if (!timeSpan) {
            return;
        }

        // Parse HH:MM:SS to seconds
        function parseTime(str) {
            var parts = str.split(':').map(function(p) { return parseInt(p, 10); });
            return parts[0] * 3600 + parts[1] * 60 + parts[2];
        }

        var lastSeconds = parseTime(timeSpan.textContent.trim());

        // Observe time changes
        var observer = new MutationObserver(function() {
            var text = timeSpan.textContent.trim();
            var secondsLeft = parseTime(text);
            if (secondsLeft !== lastSeconds) {
                // When timer ends
                if (secondsLeft === 0) {
                    if (window.LilacToast) {
                        window.LilacToast.showToast({
                            type: 'error',
                            message: 'לא ענית על מספיק שאלות. חזור למבחן.',
                            autoClose: 0,
                            messageId: 'ld-timer-ended'
                        });
                    }
                }
                lastSeconds = secondsLeft;
            }
        });
        observer.observe(timeSpan, { childList: true, characterData: true, subtree: true });
    });
})();
