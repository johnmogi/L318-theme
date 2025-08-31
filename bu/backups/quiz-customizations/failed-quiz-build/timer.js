// LearnDash Quiz Timer (Self-Contained Version)
(function() {
    class QuizTimer {
        constructor(container, duration = 300, warningThresholds = [60, 30, 10]) {
            this.container = container || document.body;
            this.duration = duration;
            // Persistent end time across page loads
            this.storageKey = 'ldQuizTimer_' + (container.id || 'ld-timer');
            const storedEnd = sessionStorage.getItem(this.storageKey);
            this.endTime = storedEnd ? parseInt(storedEnd, 10) : Date.now() + duration * 1000;
            sessionStorage.setItem(this.storageKey, this.endTime);
            this.timeLeft = Math.ceil((this.endTime - Date.now()) / 1000);
            this.warningThresholds = warningThresholds.sort((a,b) => b-a);
            this.timerElement = document.createElement('div');
            this.timerElement.className = 'ld-quiz-timer';
        }

        tick() {
            const now = Date.now();
            const rawLeft = Math.ceil((this.endTime - now) / 1000);
            this.timeLeft = rawLeft > 0 ? rawLeft : 0;
            this.updateDisplay();
            if (rawLeft <= 0) {
                this.endQuiz();
            } else {
                this.checkWarnings();
            }
        }

        start() {
            this.updateDisplay();
            this.interval = setInterval(() => this.tick(), 1000);
            this.container.appendChild(this.timerElement);
            // DEBUG: Reset button
            this.resetButton = document.createElement('button');
            this.resetButton.textContent = 'Reset Timer';
            this.resetButton.className = 'ld-timer-reset';
            this.resetButton.style.marginLeft = '10px';
            this.resetButton.addEventListener('click', () => {
                sessionStorage.removeItem(this.storageKey);
                this.endTime = Date.now() + this.duration * 1000;
                sessionStorage.setItem(this.storageKey, this.endTime);
                clearInterval(this.interval);
                this.timeLeft = this.duration;
                this.updateDisplay();
                this.interval = setInterval(() => this.tick(), 1000);
            });
            this.container.appendChild(this.resetButton);
            // Expose for debugging (time stored in sessionStorage under key this.storageKey)
            window.LDQuizTimers = window.LDQuizTimers || [];
            window.LDQuizTimers.push(this);
        }

        updateDisplay() {
            const mins = Math.floor(this.timeLeft / 60);
            const secs = this.timeLeft % 60;
            this.timerElement.textContent = `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }

        checkWarnings() {
            this.warningThresholds.forEach(threshold => {
                if(this.timeLeft === threshold) {
                    this.showWarning(threshold);
                }
            });
        }

        showWarning(secondsLeft) {
            const messages = {
                60: '1 minute remaining!',
                30: '30 seconds remaining!',
                10: 'Hurry! 10 seconds left!'
            };
            const msg = messages[secondsLeft];
            if (msg) {
                if (window.LilacToast) {
                    window.LilacToast.showToast({
                        type: 'warning',
                        message: msg,
                        autoClose: 5,
                        messageId: 'timer-warning-' + secondsLeft
                    });
                }
                this.timerElement.classList.add(secondsLeft <= 30 ? 'critical' : 'warning');
            }
        }

        endQuiz() {
            clearInterval(this.interval);
            this.timeLeft = 0;
            this.updateDisplay();
            if (window.LilacToast) {
                window.LilacToast.showToast({
                    type: 'error',
                    message: 'לא ענית על מספיק שאלות. חזור למבחן.',
                    autoClose: 0,
                    messageId: 'timer-ended-' + (this.container.id || Date.now())
                });
            }
            // TODO: Trigger quiz form submission here
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const timerElements = document.querySelectorAll('[data-ld-timer]');
        timerElements.forEach(el => {
            const duration = parseInt(el.getAttribute('data-duration')) || 300;
            new QuizTimer(el, duration).start();
        });

        if(timerElements.length === 0 && document.querySelector('.learndash-quiz')) {
            new QuizTimer().start();
        }
    });
})();
