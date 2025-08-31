// quiz-extensions.js
// Frontend logic to disable the quiz top bar navigation for selected quizzes
// Loaded only if at least one quiz has the 'disable_topbar' option enabled
(function($){
    // --- Always-available Lilac Message Box ---
    // Save a reference to the original alert
    var origAlert = window.alert;
    function showLilacMessage(msg, type) {
        var toastType = 'danger';
        var quizValidationPatterns = [/\u05e2\u05dc\u05d9\u05da \u05dc\u05e2\u05e0\u05d5\u05ea/, /\u05d9\u05e9 \u05dc\u05d4\u05e9\u05dc\u05d9\u05dd \u05d0\u05ea \u05db\u05dc \u05d4\u05e9\u05d0\u05dc\u05d5\u05ea/, /\u05d9\u05e9 \u05dc\u05d1\u05d7\u05d5\u05e8 \u05ea\u05e9\u05d5\u05d1\u05d4/];
        var isQuizValidation = quizValidationPatterns.some(function(rgx){ return rgx.test(msg); });

        // Make sure fallback toast is created if needed
        initLilacToastFallback();
        
        if (isQuizValidation) {
            // --- Lilac Toast ---
            // After the fallback is initialized, this should always be available
            if (window.LilacToast && typeof window.LilacToast.show === 'function') {
                try {
                    window.LilacToast.show(msg, toastType);
                } catch (e) {
                    console.error('Toast error:', e);
                }
            }
            // --- Inline message ---
            var $container = $('.wpProQuiz_content, .ld-quiz-content').first();
            if ($container.length) {
                var $msg = $('<div class="lilac-inner-message lilac-message-' + toastType + '">' + msg + '</div>');
                $container.find('.lilac-inner-message').remove();
                $container.prepend($msg);
                setTimeout(function() { $msg.fadeOut(400, function(){ $msg.remove(); }); }, 3500);
            }
            return;
        }
        // Fallback: show message inside quiz area ONLY if not a quiz validation error
        var $container = $('.wpProQuiz_content, .ld-quiz-content').first();
        if ($container.length) {
            var $msg = $('<div class="lilac-inner-message lilac-message-' + (type || 'info') + '">' + msg + '</div>');
            $container.find('.lilac-inner-message').remove();
            $container.prepend($msg);
            setTimeout(function() { $msg.fadeOut(400, function(){ $msg.remove(); }); }, 3500);
            return;
        }
        // As a last resort, use the original alert (never window.alert)
        if (typeof origAlert === 'function') {
            origAlert(msg);
        }
    }

    // Create a minimal fallback implementation of LilacToast if the main one isn't available
    function initLilacToastFallback() {
        if (window.LilacToast) {
            return; // Main toast system is available, no need for fallback
        }
        
        // Create a minimal fallback implementation
        window.LilacToast = {
            show: function(message, type) {
                var toastType = type || 'info';
                var cssColor = (toastType === 'danger') ? '#f44336' : 
                               (toastType === 'warning') ? '#ff9800' : 
                               (toastType === 'success') ? '#4CAF50' : '#2196F3';
                
                // Create toast container if it doesn't exist
                var $container = $('.lilac-toast-container');
                if ($container.length === 0) {
                    $container = $('<div class="lilac-toast-container"></div>')
                        .css({
                            'position': 'fixed',
                            'top': '10px',
                            'right': '10px',
                            'z-index': '9999999',
                            'max-width': '300px'
                        });
                    $('body').append($container);
                }
                
                // Create toast element
                var $toast = $('<div class="lilac-toast-fallback"></div>')
                    .css({
                        'background-color': cssColor,
                        'color': '#fff',
                        'padding': '12px 16px',
                        'margin-bottom': '10px',
                        'border-radius': '4px',
                        'box-shadow': '0 2px 5px rgba(0,0,0,0.3)',
                        'display': 'flex',
                        'align-items': 'center',
                        'justify-content': 'space-between',
                        'opacity': '0',
                        'transform': 'translateY(-20px)',
                        'transition': 'all 0.3s ease-in-out'
                    })
                    .html('<div>' + message + '</div><button style="background:none;border:none;color:#fff;cursor:pointer;margin-left:10px;font-size:18px;">&times;</button>');
                
                // Add to container and animate in
                $container.append($toast);
                setTimeout(function() {
                    $toast.css({
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                }, 10);
                
                // Add close functionality
                $toast.find('button').on('click', function() {
                    $toast.css({
                        'opacity': '0',
                        'transform': 'translateY(-20px)'
                    });
                    setTimeout(function() {
                        $toast.remove();
                    }, 300);
                });
                
                // Auto-close after 4 seconds
                setTimeout(function() {
                    $toast.find('button').click();
                }, 4000);
                
                return $toast;
            }
        };
        
        console.log('[Lilac QuizExt] Created fallback LilacToast implementation');
    }
    
    // Patch window.alert only after DOMContentLoaded and LilacToast is present
    function patchAlertForLilacToast() {
        // Initialize toast fallback if needed
        initLilacToastFallback();
        
        // Save a reference to the original alert
        var origAlert = window.alert;
        window.alert = function(msg) {
            // Show all messages with our custom handler
            showLilacMessage(msg);
        };
        console.log('[Lilac QuizExt] window.alert patched.');
    }

    $(function(){
        // Settings are injected via wp_localize_script
        if (typeof window.quizExtensionsSettings !== 'object' || !window.quizExtensionsSettings.disable_topbar_quiz_ids) {
            console.warn('[Lilac QuizExt] No quizExtensionsSettings or disable_topbar_quiz_ids');
            return;
        }
        var quizIds = window.quizExtensionsSettings.disable_topbar_quiz_ids;
        if (!Array.isArray(quizIds) || !quizIds.length) {
            console.warn('[Lilac QuizExt] No quiz IDs to process');
            return;
        }

        // Find current quiz ID from body classes (works for LearnDash and WpProQuiz)
        var body = document.body;
        var foundQuizId = null;
        body.className.split(/\s+/).forEach(function(cls){
            var m = cls.match(/^learndash-cpt-sfwd-quiz-(\d+)-current$/);
            if (m) foundQuizId = parseInt(m[1], 10);
        });
        if (!foundQuizId || quizIds.indexOf(foundQuizId) === -1) return;

        // Instead of hiding the top bar, disable jumping between questions
        // WpProQuiz: .wpProQuiz_reviewQuestion contains numbered <li> elements for navigation
        // LearnDash: (add more selectors as needed)

        // For WpProQuiz: disable all numbered question links/buttons
        $('.wpProQuiz_reviewQuestion li').each(function(){
            // Remove pointer events and style as disabled
            $(this)
                .css({
                    'pointer-events': 'none',
                    'opacity': 0.5,
                    'cursor': 'not-allowed'
                })
                .attr('title', 'הניווט בין שאלות מושבת');
        });
        // Optionally, if there are <a> or <button> inside, disable them too
        $('.wpProQuiz_reviewQuestion li a, .wpProQuiz_reviewQuestion li button').each(function(){
            $(this)
                .on('click', function(e){ e.preventDefault(); return false; })
                .prop('disabled', true)
                .css({
                    'pointer-events': 'none',
                    'opacity': 0.5,
                    'cursor': 'not-allowed'
                });
        });
        // Add more logic for LearnDash or other quiz types here as needed

        // Message box logic for Next button and validation
        // Use Lilac's toast/message system if available, otherwise fallback to inner message
        function innerShowLilacMessage(msg, type) {
            // Try Lilac Toast System
            if (window.LilacToast && typeof window.LilacToast.show === 'function') {
                window.LilacToast.show(msg, type || 'info');
                return;
            }
            // Fallback: show message inside quiz area
            var $container = $('.wpProQuiz_content, .ld-quiz-content').first();
            if ($container.length) {
                var $msg = $('<div class="lilac-inner-message lilac-message-' + (type || 'info') + '">' + msg + '</div>');
                $container.find('.lilac-inner-message').remove();
                $container.prepend($msg);
                setTimeout(function() { $msg.fadeOut(400, function(){ $msg.remove(); }); }, 3500);
            } else {
                // As a last resort, use alert (should be rare)
                alert(msg);
            }
        }

        // Example: intercept Next button
        $(document).on('click', '.wpProQuiz_button[name="next"], .ld-quiz-next', function(e) {
            var btnSelector = $(this).attr('class') + ' [name="' + $(this).attr('name') + '"]';
            console.debug('[Lilac QuizExt] Next button clicked:', this);
            // Validation: prevent skipping if required question is not answered
            var $question = $(this).closest('.wpProQuiz_content').find('.wpProQuiz_questionList:visible, .wpProQuiz_questionList').first();
            console.debug('[Lilac QuizExt] Located question block:', $question.length, $question.get(0));
            var answered = false;
            try {
                var radios = $question.find('input[type="radio"]:checked, input[type="checkbox"]:checked');
                var texts = $question.find('input[type="text"]').filter(function(){ return $(this).val().trim() !== ''; });
                var textareas = $question.find('textarea').filter(function(){ return $(this).val().trim() !== ''; });
                console.debug('[Lilac QuizExt] Checked radios/checkboxes:', radios.length, radios);
                console.debug('[Lilac QuizExt] Filled text inputs:', texts.length, texts);
                console.debug('[Lilac QuizExt] Filled textareas:', textareas.length, textareas);
                if (radios.length > 0 || texts.length > 0 || textareas.length > 0) {
                    answered = true;
                }
            } catch (err) {
                console.error('[Lilac QuizExt] Validation error:', err);
                answered = false;
            }
            console.info('[Lilac QuizExt] Validation result - answered:', answered);
            if (!answered) {
                innerShowLilacMessage('עליך לענות על שאלה זו.', 'error');
                e.preventDefault();
                return false;
            }
            // Optionally show a success message
            // innerShowLilacMessage('מעבר לשאלה הבאה', 'success');
        });
    });

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){
            // Patch window.alert to use Lilac Toast
            patchAlertForLilacToast();
            console.log('[Lilac QuizExt] quiz-extensions.js loaded');
        });
    } else {
        // Patch window.alert to use Lilac Toast
        patchAlertForLilacToast();
        console.log('[Lilac QuizExt] quiz-extensions.js loaded');
    }

    // --- Patch window.alert to use Lilac message box for quiz validation ---
    var origAlert = window.alert;
    window.alert = function(msg) {
        // Only intercept quiz validation alerts
        var quizValidationPatterns = [/עליך לענות/, /יש להשלים את כל השאלות/, /יש לבחור תשובה/];
        var isQuizValidation = quizValidationPatterns.some(function(rgx){ return rgx.test(msg); });
        if (isQuizValidation) {
            showLilacMessage(msg, 'error');
            return;
        }
        // Otherwise, call the original alert
        origAlert.apply(window, arguments);
    };
})(jQuery);
