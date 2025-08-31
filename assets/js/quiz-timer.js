/**
 * Quiz Timer Tracker
 * Shows notifications based on admin settings
 */

jQuery(document).ready(function($) {
    // Default settings (will be overridden by admin settings)
    const settings = window.lilacQuizTimerVars || {
        enableTestTimer: true,
        testDuration: 60,    // minutes
        warningTime: 10,     // minutes
        criticalTime: 2,     // minutes
        enableSessionWarning: true,
        sessionTimeout: 30,  // minutes
        warningBefore: 29    // minutes
    };
    

    
    // Log the settings for debugging
    console.log('Quiz Timer Settings:', settings);
    // Check if we're on a quiz page
    if ($('body').hasClass('sfwd-quiz-template')) {
        console.log('Quiz page detected, initializing quiz timer...');
        
        // Get settings from localized script or use defaults
        const quizSettings = window.lilacQuizTimerVars || {};
        Object.assign(settings, quizSettings);
        
        console.log('Quiz timer settings:', settings);
        
        if (settings.enableTestTimer) {
            // Add a small delay to ensure DOM is fully loaded
            setTimeout(initializeQuizTimer, 500);
        }
    }

    function initializeQuizTimer() {
        // Try different selectors to find the timer element
        let timerElement = $('.time span');
        if (!timerElement.length) {
            timerElement = $('.learndash_timer_display');
        }
        if (!timerElement.length) {
            timerElement = $('.ld-quiz-timer');
        }
        
        let warningShown = false;
        let criticalWarningShown = false;
        let timerInterval;
        
        if (timerElement.length) {
            console.log('Timer element found:', timerElement);
            // Parse the initial time or use default from settings
            let initialTime = timerElement.text().trim();
            console.log('Initial timer text:', initialTime);
            
            // If no valid time found, use settings
            if (!initialTime || initialTime === '00:00:00' || !/\d{2}:\d{2}:\d{2}/.test(initialTime)) {
                // Set default time from settings (convert minutes to HH:MM:SS)
                const hours = Math.floor(settings.testDuration / 60);
                const minutes = settings.testDuration % 60;
                initialTime = [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    '00'
                ].join(':');
                timerElement.text(initialTime);
                console.log('Using default time from settings:', initialTime);
            }
            
            let [hours, minutes, seconds] = initialTime.split(':').map(Number);
            let totalSeconds = hours * 3600 + minutes * 60 + seconds;
            
            // Store initial time in session storage
            sessionStorage.setItem('quizTimer', initialTime);
            sessionStorage.setItem('quizTimerStart', Date.now());
            sessionStorage.setItem('quizTimerTotal', totalSeconds);
            
            // Show initial notification if test timer is enabled
            if (settings.enableTestTimer) {
                const totalMinutes = Math.floor(totalSeconds / 60);
                const minutesText = totalMinutes === 1 ? 'דקה' : 'דקות';
                showNotification(
                    'המבחן התחיל', 
                    `יש לך ${totalMinutes} ${minutesText} להשלמת המבחן.`, 
                    'info',
                    5000
                );
            }
            
            console.log('Quiz timer initialized:', {
                display: initialTime,
                totalSeconds: totalSeconds,
                timestamp: Date.now()
            });
            
            // Update time every second
            timerInterval = setInterval(updateTimer, 1000);
            
            // Initial update
            updateTimer();
            
            // Handle page visibility changes
            document.addEventListener('visibilitychange', handleVisibilityChange);
            
            // Clean up on page unload
            $(window).on('beforeunload', function() {
                clearInterval(timerInterval);
                document.removeEventListener('visibilitychange', handleVisibilityChange);
            });
        }

        function updateTimer() {
            try {
                const startTime = parseInt(sessionStorage.getItem('quizTimerStart') || Date.now());
                const totalSeconds = parseInt(sessionStorage.getItem('quizTimerTotal') || (settings.testDuration * 60));
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                const remainingSeconds = Math.max(0, totalSeconds - elapsedSeconds);
                
                // Check for warnings
                checkForWarnings(remainingSeconds);
                
                // Calculate hours, minutes, seconds
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                
                // Format as HH:MM:SS
                const formattedTime = [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0')
                ].join(':');
                
                // Update the display if element exists
                if (timerElement.length) {
                    timerElement.text(formattedTime);
                } else {
                    console.warn('Timer element not found in DOM');
                }
                
                // Update session storage
                sessionStorage.setItem('quizTimer', formattedTime);
                sessionStorage.setItem('quizTimerStart', startTime);
                sessionStorage.setItem('quizTimerTotal', totalSeconds);
                
                // Log for debugging
                if (elapsedSeconds % 10 === 0 || remainingSeconds <= settings.warningTime * 60) {
                    console.log('Quiz timer update:', {
                        display: formattedTime,
                        remainingSeconds: remainingSeconds,
                        remainingMinutes: Math.ceil(remainingSeconds / 60),
                        lastUpdate: new Date().toISOString()
                    });
                }
            
                // If time's up, stop the timer
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    showTimeUpNotification();
                    console.log('Quiz timer completed');
                    return;
                }
            } catch (error) {
                console.error('Error in updateTimer:', error);
                // Attempt to recover by reinitializing timer
                clearInterval(timerInterval);
                initializeQuizTimer();
            }
        }
        
        function checkForWarnings(remainingSeconds) {
            const remainingMinutes = Math.ceil(remainingSeconds / 60);
            
            // Show critical warning (2 minutes by default)
            if (remainingMinutes <= settings.criticalTime && !criticalWarningShown) {
                criticalWarningShown = true;
                const criticalText = settings.criticalTime === 1 ? 'דקה' : 'דקות';
                showNotification(
                    'הזמן אוזל!', 
                    `נשארו לך ${settings.criticalTime} ${criticalText} לסיום המבחן.`, 
                    'error'
                );
            } 
            // Show warning (10 minutes by default)
            else if (remainingMinutes <= settings.warningTime && !warningShown) {
                warningShown = true;
                const warningText = settings.warningTime === 1 ? 'דקה' : 'דקות';
                showNotification(
                    'אזהרת זמן', 
                    `נשארו ${settings.warningTime} ${warningText}. אנא השלימי/ה את המבחן בהקדם.`,
                    'warning'
                );
            }
        }
        
        function showTimeUpNotification() {
            showNotification(
                'הזמן נגמר!', 
                'זמן המבחן הסתיים. אנא שלח/י את התשובות שלך עכשיו.', 
                'error',
                0 // Don't auto-close
            );
            
            // Optional: Auto-submit the quiz when time is up
            const submitButton = $('input[type="submit"], button[type="submit"]').first();
            if (submitButton.length) {
                // Flash the submit button to draw attention
                submitButton.addClass('time-up-flash');
                setTimeout(() => submitButton.removeClass('time-up-flash'), 1000);
            }
        }
        
        function showNotification(title, message, type = 'info', autoClose = 5000) {
            console.log('Attempting to show notification:', { title, message, type, autoClose });
            
            // Check if LilacToast is available with the type method we need
            if (window.LilacToast && window.LilacToast[type] && typeof window.LilacToast[type] === 'function') {
                console.log(`Using LilacToast.${type} for notification`);
                try {
                    window.LilacToast[type](message, title, autoClose);
                    console.log('Notification shown successfully via LilacToast');
                    return;
                } catch (error) {
                    console.error(`Error showing LilacToast.${type}:`, error);
                }
            }
            // Check if LilacToast.showToast is available
            else if (window.LilacToast && window.LilacToast.showToast && typeof window.LilacToast.showToast === 'function') {
                console.log('Using LilacToast.showToast for notification');
                try {
                    window.LilacToast.showToast({
                        type: type,
                        title: title,
                        message: message,
                        duration: autoClose
                    });
                    console.log('Notification shown successfully via showToast');
                    return;
                } catch (error) {
                    console.error('Error showing LilacToast.showToast:', error);
                }
            }
            // Check if LilacShowToast is available
            else if (window.LilacShowToast && typeof window.LilacShowToast === 'function') {
                console.log('Using LilacShowToast for notification');
                try {
                    window.LilacShowToast({
                        type: type,
                        title: title,
                        message: message,
                        duration: autoClose,
                        position: 'top-right',
                        showCloseButton: true
                    });
                    console.log('Notification shown successfully via LilacShowToast');
                    return;
                } catch (error) {
                    console.error('Error showing LilacShowToast:', error);
                }
            }
            
            // Fallback to browser alert if no toast system is available
            console.warn('No compatible toast system found, falling back to alert');
            alert(`${title}: ${message}`);
        }
        
        function handleVisibilityChange() {
            if (!document.hidden) {
                // When tab becomes active again, update the timer start time
                const currentTime = timerElement.text().trim();
                const [h, m, s] = currentTime.split(':').map(Number);
                const totalSeconds = h * 3600 + m * 60 + s;
                
                sessionStorage.setItem('quizTimerStart', Date.now());
                sessionStorage.setItem('quizTimerTotal', totalSeconds);
                
                console.log('Tab became active, updated timer:', {
                    display: currentTime,
                    totalSeconds: totalSeconds,
                    timestamp: Date.now()
                });
            }
        }
    }

});
