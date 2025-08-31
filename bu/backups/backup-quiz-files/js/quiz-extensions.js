// quiz-extensions.js
// Frontend logic to disable the quiz top bar navigation for selected quizzes
// Loaded only if at least one quiz has the 'disable_topbar' option enabled
(function($){
    // --- Floating Debug Panel Setup ---
    function lilacDebugPanelLog(msg) {
        // Use Lilac floating debugger if present
        if (window.LilacDebugPanel && typeof window.LilacDebugPanel.log === 'function') {
            window.LilacDebugPanel.log(msg);
            return;
        }
        // Otherwise, create a simple floating panel
        var id = 'lilac-quizext-debug-panel';
        var $panel = $('#' + id);
        if (!$panel.length) {
            $panel = $('<div id="' + id + '" style="display:none;position:fixed;bottom:10px;right:10px;z-index:99999;background:#222;color:#fff;padding:10px 15px;border-radius:6px;font-size:14px;max-width:350px;max-height:200px;overflow:auto;box-shadow:0 2px 8px #0008;opacity:0.85;"></div>');
            $('body').append($panel);
        }
        var now = new Date().toLocaleTimeString();
        $panel.append('<div style="margin-bottom:4px;border-bottom:1px solid #444;">[' + now + '] ' + msg + '</div>');
        $panel.scrollTop($panel[0].scrollHeight);
    }

    // --- Always-available Lilac Message Box ---
    // Save a reference to the original alert
    var origAlert = window.alert;
    function showLilacMessage(msg, type) {
        var toastType = 'danger';
        var quizValidationPatterns = [/עליך לענות/, /יש להשלים את כל השאלות/, /יש לבחור תשובה/];
        var isQuizValidation = quizValidationPatterns.some(function(rgx){ return rgx.test(msg); });
        var toastResult = 'not attempted';
        var inlineResult = 'not attempted';

        // Make sure fallback toast is created if needed
        initLilacToastFallback();
        
        if (isQuizValidation) {
            // --- Lilac Toast ---
            // After the fallback is initialized, this should always be available
            if (window.LilacToast && typeof window.LilacToast.show === 'function') {
                try {
                    window.LilacToast.show(msg, toastType);
                    toastResult = 'shown';
                    lilacDebugPanelLog('[TOAST DEBUG] Quiz validation: "' + msg + '" | type: ' + toastType + ' | shown successfully.');
                } catch (e) {
                    toastResult = 'error: ' + e;
                    lilacDebugPanelLog('[TOAST DEBUG] Quiz validation: "' + msg + '" | type: ' + toastType + ' | error: ' + e);
                }
            } else {
                toastResult = 'still not available - critical error';
                lilacDebugPanelLog('[TOAST CRITICAL ERROR] Toast system still not available even after fallback attempt. Please check for JavaScript errors.');
            }
            // --- Inline message ---
            var $container = $('.wpProQuiz_content, .ld-quiz-content').first();
            if ($container.length) {
                var $msg = $('<div class="lilac-inner-message lilac-message-' + toastType + '">' + msg + '</div>');
                $container.find('.lilac-inner-message').remove();
                $container.prepend($msg);
                setTimeout(function() { $msg.fadeOut(400, function(){ $msg.remove(); }); }, 3500);
                inlineResult = 'shown';
            } else {
                inlineResult = 'container not found';
            }
            lilacDebugPanelLog('[INLINE DEBUG] Quiz validation: "' + msg + '" | inlineResult: ' + inlineResult);
            // --- Summary ---
            lilacDebugPanelLog('[SHOWLILACMESSAGE SUMMARY] msg: "' + msg + '" | toast: ' + toastResult + ' | inline: ' + inlineResult);
            return;
        }
        // Fallback: show message inside quiz area ONLY if not a quiz validation error
        var $container = $('.wpProQuiz_content, .ld-quiz-content').first();
        if ($container.length) {
            var $msg = $('<div class="lilac-inner-message lilac-message-' + (type || 'info') + '">' + msg + '</div>');
            $container.find('.lilac-inner-message').remove();
            $container.prepend($msg);
            setTimeout(function() { $msg.fadeOut(400, function(){ $msg.remove(); }); }, 3500);
            lilacDebugPanelLog('[INLINE DEBUG] Non-quiz message: "' + msg + '" | shown inline.');
            return;
        }
        // As a last resort, use the original alert (never window.alert)
        lilacDebugPanelLog('[SHOWLILACMESSAGE FALLBACK] msg: "' + msg + '" | no container, no toast.');
        if (typeof origAlert === 'function') {
            origAlert(msg);
        }
    }

    // Create a minimal fallback implementation of LilacToast if the main one isn't available
    function initLilacToastFallback() {
        if (window.LilacToast) {
            lilacDebugPanelLog('[TOAST SYSTEM] Using main LilacToast system');
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
                
                lilacDebugPanelLog('[TOAST SYSTEM] Using fallback toast: "' + message + '" (' + toastType + ')');
                return $toast;
            }
        };
        
        lilacDebugPanelLog('[TOAST SYSTEM] Fallback LilacToast created because main system was not available');
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
        lilacDebugPanelLog('window.alert patched to always use LilacToast for user messages.');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){
            // Debug: Check LilacToast system availability at page load
            var toastState = '[TOAST SYSTEM CHECK] window.LilacToast: ' + (typeof window.LilacToast) + ', LilacToast.show: ' + (window.LilacToast && typeof window.LilacToast.show);
            console.log(toastState);
            lilacDebugPanelLog(toastState);
            // Patch window.alert to use Lilac Toast
            patchAlertForLilacToast();
            console.log('[Lilac QuizExt] quiz-extensions.js loaded');
            lilacDebugPanelLog('quiz-extensions.js loaded');
        });
    } else {
        // Debug: Check LilacToast system availability at page load
        var toastState = '[TOAST SYSTEM CHECK] window.LilacToast: ' + (typeof window.LilacToast) + ', LilacToast.show: ' + (window.LilacToast && typeof window.LilacToast.show);
        console.log(toastState);
        lilacDebugPanelLog(toastState);
        // Patch window.alert to use Lilac Toast
        patchAlertForLilacToast();
        console.log('[Lilac QuizExt] quiz-extensions.js loaded');
        lilacDebugPanelLog('quiz-extensions.js loaded');
    }


    // --- Patch window.alert to use Lilac message box for quiz validation ---
    var origAlert = window.alert;
    window.alert = function(msg) {
        // Only intercept quiz validation alerts
        var quizValidationPatterns = [/עליך לענות/, /יש להשלים את כל השאלות/, /יש לבחור תשובה/];
        var isQuizValidation = quizValidationPatterns.some(function(rgx){ return rgx.test(msg); });
        if (isQuizValidation) {
            lilacDebugPanelLog('window.alert intercepted: ' + msg);
            showLilacMessage(msg, 'error');
            return;
        }
        // Otherwise, call the original alert
        origAlert.apply(window, arguments);
    };

    // Debug function to show quiz force hint settings
    function debugQuizSettings() {
        if (!$('.wpProQuiz_quiz').length) return;
        
        // Get quiz settings from meta tags
        var forceHintMode = $('meta[name="lilac-quiz-force-hint"]').attr('content') === 'true';
        var showHint = $('meta[name="lilac-quiz-show-hint"]').attr('content') === 'true';
        var richSidebar = $('meta[name="lilac-quiz-rich-sidebar"]').attr('content') === 'true';
        var quizId = $('meta[name="lilac-quiz-id"]').attr('content');
        
        // Get settings from data attributes as fallback
        var quizContainer = $('.wpProQuiz_quiz')[0];
        if (quizContainer) {
            if (!forceHintMode && quizContainer.getAttribute('data-force-hint-mode') === 'true') {
                forceHintMode = true;
            }
            if (!richSidebar && quizContainer.getAttribute('data-rich-sidebar') === 'true') {
                richSidebar = true;
            }
        }
        
        // Also check database settings directly
        var quizPostId = $('.wpProQuiz_quiz').data('quiz-post-id') || '';
        lilacDebugPanelLog('<strong>Quiz Post ID:</strong> ' + quizPostId, true);
        
        // Check for post meta settings
        var forceHintPostMeta = $('meta[name="lilac-quiz-force-hint-postmeta"]').attr('content') === 'true';
        var richSidebarPostMeta = $('meta[name="lilac-quiz-rich-sidebar-postmeta"]').attr('content') === 'true';
        
        // Log information
        lilacDebugPanelLog('<strong>Quiz ID:</strong> ' + (quizId || 'Unknown'));
        lilacDebugPanelLog('<strong>Force Hint Mode (option):</strong> ' + (forceHintMode ? 'Enabled' : 'Disabled'));
        lilacDebugPanelLog('<strong>Force Hint Mode (post meta):</strong> ' + (forceHintPostMeta ? 'Enabled' : 'Disabled'));
        lilacDebugPanelLog('<strong>Show Hint:</strong> ' + (showHint ? 'Enabled' : 'Disabled'));
        lilacDebugPanelLog('<strong>Rich Sidebar (option):</strong> ' + (richSidebar ? 'Enabled' : 'Disabled'));
        lilacDebugPanelLog('<strong>Rich Sidebar (post meta):</strong> ' + (richSidebarPostMeta ? 'Enabled' : 'Disabled'));
        
        // Check for Next buttons and their status
        var nextButtons = $('.wpProQuiz_button[name="next"]');
        lilacDebugPanelLog('<strong>Next Buttons Found:</strong> ' + nextButtons.length);
        
        if (nextButtons.length > 0) {
            var visibleCount = 0;
            nextButtons.each(function() {
                if ($(this).is(':visible')) {
                    visibleCount++;
                }
            });
            lilacDebugPanelLog('<strong>Visible Next Buttons:</strong> ' + visibleCount + ' / ' + nextButtons.length);
        }
        
        // Add toggle button for debug panel if it doesn't exist
        if ($('#lilac-toggle-debug').length === 0) {
            var $debugToggle = $('<button id="lilac-toggle-debug" style="position:fixed;bottom:10px;right:10px;z-index:99998;background:#4caf50;color:#fff;border:none;border-radius:4px;padding:5px 10px;font-size:12px;cursor:pointer;">Quiz Debug</button>');
            $('body').append($debugToggle);
            
            $debugToggle.on('click', function() {
                $('#lilac-quizext-debug-panel').toggle();
            });
        }
    }
    
    $(function(){
        // Run the quiz settings debug
        if ($('.wpProQuiz_quiz').length) {
            setTimeout(debugQuizSettings, 1000); // Slight delay to ensure everything is loaded
        }
        
        // Settings are injected via wp_localize_script
        if (typeof window.quizExtensionsSettings !== 'object' || !window.quizExtensionsSettings.disable_topbar_quiz_ids) {
            console.warn('[Lilac QuizExt] No quizExtensionsSettings or disable_topbar_quiz_ids');
            lilacDebugPanelLog('No quizExtensionsSettings or disable_topbar_quiz_ids – script not running');
            return;
        }
        var quizIds = window.quizExtensionsSettings.disable_topbar_quiz_ids;
        if (!Array.isArray(quizIds) || !quizIds.length) {
            console.warn('[Lilac QuizExt] No quiz IDs to process');
            lilacDebugPanelLog('No quiz IDs to process – script not running');
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
        function showLilacMessage(msg, type) {
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
            lilacDebugPanelLog('Next button clicked: ' + btnSelector);
            // Validation: prevent skipping if required question is not answered
            var $question = $(this).closest('.wpProQuiz_content').find('.wpProQuiz_questionList:visible, .wpProQuiz_questionList').first();
            console.debug('[Lilac QuizExt] Located question block:', $question.length, $question.get(0));
            lilacDebugPanelLog('Located question block: ' + $question.length + ' found');
            var answered = false;
            try {
                var radios = $question.find('input[type="radio"]:checked, input[type="checkbox"]:checked');
                var texts = $question.find('input[type="text"]').filter(function(){ return $(this).val().trim() !== ''; });
                var textareas = $question.find('textarea').filter(function(){ return $(this).val().trim() !== ''; });
                console.debug('[Lilac QuizExt] Checked radios/checkboxes:', radios.length, radios);
                console.debug('[Lilac QuizExt] Filled text inputs:', texts.length, texts);
                console.debug('[Lilac QuizExt] Filled textareas:', textareas.length, textareas);
                lilacDebugPanelLog('Radios/Checkboxes: ' + radios.length + ', Texts: ' + texts.length + ', Textareas: ' + textareas.length);
                if (radios.length > 0 || texts.length > 0 || textareas.length > 0) {
                    answered = true;
                }
            } catch (err) {
                console.error('[Lilac QuizExt] Validation error:', err);
                lilacDebugPanelLog('Validation error: ' + err);
                answered = false;
            }
            console.info('[Lilac QuizExt] Validation result - answered:', answered);
            lilacDebugPanelLog('Validation result - answered: ' + answered);
            if (!answered) {
                lilacDebugPanelLog('Validation failed: עליך לענות על שאלה זו.');
                showLilacMessage('עליך לענות על שאלה זו.', 'error');
                e.preventDefault();
                return false;
            }
            // Optionally show a success message
            // showLilacMessage('מעבר לשאלה הבאה', 'success');
        });

        // You can call showLilacMessage(msg, type) anywhere for consistent UX
    });
})(jQuery);
