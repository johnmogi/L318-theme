/**
 * Course Progress System JavaScript
 * 
 * Handles course progress tracking, view counting, and activity timing
 */
 
(function($) {
    'use strict';
    
    // Course Progress system object
    var LilacProgress = {
        // Timer variables
        activityTimerInterval: null,
        lastActivityTime: 0,
        
        // Initialize the progress system
        init: function() {
            // Initialize data from server
            this.lastActivityTime = lilacProgressData.lastActivity || Math.floor(Date.now() / 1000);
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Setup live activity timer updater
            this.setupActivityTimer();
            
            // Log initialization
            console.log('Lilac Course Progress System Initialized');
            console.log('Course Views:', lilacProgressData.courseViews);
            console.log('Course Progress:', lilacProgressData.courseProgress);
        },
        
        // Set up event listeners
        setupEventListeners: function() {
            // Update activity time on user interaction
            $(document).on('click keypress scroll', function() {
                LilacProgress.updateActivityTime();
            });
            
            // Add listener for manual progress updates
            $(document).on('click', '.lilac-course-progress-update', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var courseId = $this.data('course-id');
                var progress = $this.data('progress');
                
                if (courseId && progress !== undefined) {
                    LilacProgress.updateProgress(courseId, progress);
                }
            });
            
            // Add listener for progress bar completion
            $(window).on('beforeunload', function() {
                // Optional: send any final updates before page unload
                // This is usually unreliable, so main updates should happen during the session
            });
        },
        
        // Set up live activity timer
        setupActivityTimer: function() {
            // Find all live activity timers
            $('.lilac-activity-timer[data-live="true"]').each(function() {
                var $timer = $(this);
                var format = $timer.data('format') || 'formatted';
                
                // Update immediately
                LilacProgress.updateTimerDisplay($timer, format);
                
                // Clear any existing interval
                if (LilacProgress.activityTimerInterval) {
                    clearInterval(LilacProgress.activityTimerInterval);
                }
                
                // Set interval to update every second
                LilacProgress.activityTimerInterval = setInterval(function() {
                    $('.lilac-activity-timer[data-live="true"]').each(function() {
                        LilacProgress.updateTimerDisplay($(this), $(this).data('format') || 'formatted');
                    });
                }, 1000);
            });
        },
        
        // Update activity timer display
        updateTimerDisplay: function($timer, format) {
            var now = Math.floor(Date.now() / 1000);
            var seconds = now - this.lastActivityTime;
            var formattedTime = '';
            
            switch (format) {
                case 'seconds':
                    formattedTime = seconds + ' ' + 'sec';
                    break;
                case 'minutes':
                    formattedTime = Math.floor(seconds / 60) + ' ' + 'min';
                    break;
                case 'hours':
                    formattedTime = Math.floor(seconds / 3600) + ' ' + 'hr';
                    break;
                case 'formatted':
                default:
                    var hours = Math.floor(seconds / 3600);
                    var minutes = Math.floor((seconds % 3600) / 60);
                    var secs = seconds % 60;
                    
                    if (hours > 0) {
                        formattedTime = hours + ':' + LilacProgress.padZero(minutes) + ':' + LilacProgress.padZero(secs);
                    } else {
                        formattedTime = minutes + ':' + LilacProgress.padZero(secs);
                    }
                    break;
            }
            
            $timer.find('.lilac-activity-timer-value').text(formattedTime);
        },
        
        // Pad numbers with leading zero
        padZero: function(num) {
            return (num < 10 ? '0' : '') + num;
        },
        
        // Update course progress via AJAX
        updateProgress: function(courseId, progress) {
            $.ajax({
                url: lilacProgressData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_course_progress',
                    course_id: courseId,
                    progress: progress,
                    nonce: lilacProgressData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update progress bar if it exists
                        $('.lilac-course-progress[data-course-id="' + courseId + '"]').each(function() {
                            var $progressContainer = $(this);
                            var $progressBar = $progressContainer.find('.lilac-course-progress-bar');
                            var $progressPercentage = $progressContainer.find('.lilac-course-progress-percentage');
                            
                            // Update progress bar
                            if ($progressBar.length) {
                                $progressBar.css('width', progress + '%');
                            }
                            
                            // Update percentage text
                            if ($progressPercentage.length) {
                                $progressPercentage.text(Math.round(progress) + '%');
                            }
                        });
                        
                        // Show success toast if toast system is available
                        if (typeof window.LilacToast !== 'undefined') {
                            window.LilacToast.showToast({
                                type: 'success',
                                title: 'התקדמות עודכנה',
                                message: 'התקדמות הקורס עודכנה בהצלחה',
                                position: 'top-right',
                                autoClose: 3
                            });
                        }
                    } else {
                        console.error('Failed to update progress:', response.data);
                        
                        // Show error toast if toast system is available
                        if (typeof window.LilacToast !== 'undefined') {
                            window.LilacToast.showToast({
                                type: 'error',
                                title: 'שגיאה',
                                message: 'לא ניתן לעדכן את ההתקדמות',
                                position: 'top-right',
                                autoClose: 3
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    
                    // Show error toast if toast system is available
                    if (typeof window.LilacToast !== 'undefined') {
                        window.LilacToast.showToast({
                            type: 'error',
                            title: 'שגיאה',
                            message: 'שגיאת שרת בעת עדכון ההתקדמות',
                            position: 'top-right',
                            autoClose: 3
                        });
                    }
                }
            });
        },
        
        // Update activity time
        updateActivityTime: function() {
            this.lastActivityTime = Math.floor(Date.now() / 1000);
        }
    };
    
    // Initialize the progress system when document is ready
    $(document).ready(function() {
        LilacProgress.init();
        
        // Add to global scope for external usage
        window.LilacProgress = LilacProgress;
    });
    
})(jQuery);