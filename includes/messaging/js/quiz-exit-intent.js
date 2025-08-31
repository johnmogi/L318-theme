/**
 * Quiz Exit Intent Handler
 * Shows a warning when users try to leave an incomplete quiz
 */
(function($) {
    'use strict';

    // Default settings
    const DEFAULTS = {
        enabled: true,
        message: 'You have not completed this quiz yet. Are you sure you want to leave?',
        confirmButton: 'Leave Anyway',
        cancelButton: 'Stay on Page',
        cookieName: 'quiz_in_progress_',
        cookieExpiryDays: 1,
        debug: false
    };

    class QuizExitIntent {
        constructor(quizId, options = {}) {
            this.quizId = quizId || 'default';
            this.settings = $.extend({}, DEFAULTS, options);
            this.initialized = false;
            this.hasInteracted = false;
            this.mouseOutHandler = null;
            
            // Initialize when DOM is ready
            $(() => this.init());
        }

        /**
         * Initialize the exit intent handler
         */
        init() {
            if (this.initialized) return;
            
            // Check if enabled
            if (!this.settings.enabled) {
                this.log('Quiz exit intent is disabled');
                return;
            }

            // Check if we should track this quiz
            if (!this.shouldTrackQuiz()) {
                this.log('Not tracking this quiz');
                return;
            }

            this.setupEventListeners();
            this.initialized = true;
            this.log('Quiz exit intent initialized for quiz ID: ' + this.quizId);
        }

        /**
         * Set up event listeners for exit intent
         */
        setupEventListeners() {
            // Track user interaction
            $(document).on('click keydown scroll', () => {
                this.hasInteracted = true;
            });

            // Handle beforeunload event
            $(window).on('beforeunload', (e) => {
                if (this.isQuizInProgress()) {
                    // Standard way to show the browser's native confirmation
                    e.preventDefault();
                    e.returnValue = this.settings.message;
                    return this.settings.message;
                }
            });

            // Handle mouse leave (exit intent)
            this.mouseOutHandler = (e) => this.handleMouseOut(e);
            document.addEventListener('mouseout', this.mouseOutHandler);
        }

        /**
         * Handle mouse out event for exit intent
         */
        handleMouseOut(e) {
            // Only trigger if moving outside the window
            if (!e.toElement && !e.relatedTarget && e.clientY < 10) {
                this.showExitWarning();
            }
        }

        /**
         * Show exit warning toast
         */
        showExitWarning() {
            if (!this.isQuizInProgress() || !this.hasInteracted) {
                return;
            }

            // Use our toast system if available
            if (window.LilacToast) {
                window.LilacToast.warning(
                    this.settings.message,
                    'Wait!',
                    {
                        buttons: [
                            {
                                text: this.settings.cancelButton,
                                class: 'button secondary',
                                onClick: (toast) => {
                                    toast.hide();
                                    return false;
                                }
                            },
                            {
                                text: this.settings.confirmButton,
                                class: 'button primary',
                                onClick: (toast) => {
                                    this.markQuizAsComplete();
                                    return true; // Allow default action
                                }
                            }
                        ],
                        autoHide: false,
                        closeButton: false
                    }
                );
            } else {
                // Fallback to browser confirm
                if (confirm(this.settings.message)) {
                    this.markQuizAsComplete();
                }
            }
        }

        /**
         * Check if we should track this quiz
         */
        shouldTrackQuiz() {
            // Check URL for quiz parameter or specific class on the page
            return window.location.href.includes('quiz') || 
                   $('.quiz-container, .wp-quiz, .learndash-quiz').length > 0;
        }

        /**
         * Check if quiz is in progress
         */
        isQuizInProgress() {
            // Check localStorage or cookie for quiz progress
            const cookieName = this.settings.cookieName + this.quizId;
            const quizData = localStorage.getItem(cookieName) || 
                            this.getCookie(cookieName) ||
                            '{}';
            
            try {
                const data = JSON.parse(quizData);
                return data.status !== 'completed';
            } catch (e) {
                return true; // Default to true if we can't parse the data
            }
        }

        /**
         * Mark quiz as complete
         */
        markQuizAsComplete() {
            const cookieName = this.settings.cookieName + this.quizId;
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + this.settings.cookieExpiryDays);
            
            const data = {
                status: 'completed',
                completedAt: new Date().toISOString(),
                quizId: this.quizId
            };
            
            // Save to localStorage
            try {
                localStorage.setItem(cookieName, JSON.stringify(data));
            } catch (e) {
                this.log('Error saving to localStorage: ' + e.message);
            }
            
            // Also set a cookie as fallback
            document.cookie = `${cookieName}=${encodeURIComponent(JSON.stringify(data))}; ` +
                            `expires=${expiryDate.toUTCString()}; path=/; samesite=lax`;
            
            this.log('Quiz marked as complete: ' + this.quizId);
        }

        /**
         * Get cookie value by name
         */
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        }

        /**
         * Helper for debug logging
         */
        log(message) {
            if (this.settings.debug && window.console) {
                console.log('[QuizExitIntent] ' + message);
            }
        }
    }

    // Add to global namespace
    window.LilacQuizExitIntent = QuizExitIntent;

    // Auto-initialize if data attribute is present
    $(document).ready(function() {
        $('[data-lilac-quiz-exit]').each(function() {
            const quizId = $(this).data('lilac-quiz-id') || 'default';
            new QuizExitIntent(quizId, {
                enabled: $(this).data('lilac-quiz-exit') === 'true',
                message: $(this).data('lilac-quiz-message') || DEFAULTS.message,
                confirmButton: $(this).data('lilac-quiz-confirm') || DEFAULTS.confirmButton,
                cancelButton: $(this).data('lilac-quiz-cancel') || DEFAULTS.cancelButton,
                debug: $(this).data('lilac-quiz-debug') === 'true' || DEFAULTS.debug
            });
        });
    });

})(jQuery);
