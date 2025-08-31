<?php
/**
 * Quiz Script Conflict Resolver
 * 
 * This file provides aggressive conflict resolution for quiz scripts
 * to ensure only our new quiz system runs without interference.
 */

defined('ABSPATH') || exit;

/**
 * Aggressively disable all conflicting quiz scripts
 */
function lilac_resolve_quiz_script_conflicts() {
    // Only run on quiz pages
    if (!is_singular('sfwd-quiz') && !has_shortcode(get_post()->post_content ?? '', 'ld_quiz')) {
        return;
    }
    
    // List of scripts to disable - be comprehensive
    $scripts_to_disable = array(
        // Core conflicting scripts
        'strict-next-button',
        'force-hint-debug',
        'ultra-speed-quiz',
        'quiz-sidebar',
        'next-button-manager',
        'correct-answer-manager',
        
        // Other potential conflicts
        'prevent-selection-loop',
        'quiz-performance',
        'quiz-error-handler',
        'question-id-debug',
        'lilac-quiz-hint-integration',
        'acf-quiz-hints',
        'acf-hint-test',
        'quiz-extensions',
        'quiz-debug-tools',
        'quiz-admin-tools',
        'quiz-sidebar-manager',
        'rich-media-quiz',
        'strict-next',
        'hint-manager',
        'lilac-quiz-script',
        'lilac-quiz-timer',
        'lilac-quiz-sidebar',
        'lilac-quiz-hint'
    );
    
    // Dequeue and deregister each script
    foreach ($scripts_to_disable as $script) {
        wp_dequeue_script($script);
        wp_deregister_script($script);
    }
    
    // Also remove any script with 'quiz' in the handle except our new ones
    global $wp_scripts;
    if (!empty($wp_scripts->registered)) {
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($handle, 'quiz') !== false && 
                !in_array($handle, array('lilac-quiz-controller', 'lilac-answer-debug'))) {
                wp_dequeue_script($handle);
            }
        }
    }
    
    // Add inline script to prevent other scripts from running
    add_action('wp_head', 'lilac_add_script_blocker', 1);
}
add_action('wp_enqueue_scripts', 'lilac_resolve_quiz_script_conflicts', 999);

/**
 * Add inline script to block conflicting scripts
 */
function lilac_add_script_blocker() {
    // Only run on quiz pages
    if (!is_singular('sfwd-quiz') && !has_shortcode(get_post()->post_content ?? '', 'ld_quiz')) {
        return;
    }
    
    ?>
    <script>
    // Block conflicting scripts from initializing
    (function() {
        // List of function names to block
        const blockedFunctions = [
            'initStrictNextButton',
            'initForceHint',
            'initUltraSpeedQuiz',
            'initQuizSidebar',
            'initNextButtonManager',
            'initCorrectAnswerManager',
            'initPreventSelectionLoop',
            'initQuizPerformance',
            'initQuizErrorHandler',
            'initQuestionIdDebug'
        ];
        
        // Create empty functions to replace the real ones
        blockedFunctions.forEach(function(funcName) {
            window[funcName] = function() {
                console.log('Blocked: ' + funcName + ' (replaced by new quiz system)');
                return false;
            };
        });
        
        // Also block common jQuery initialization patterns
        const originalReady = jQuery.fn.ready;
        jQuery.fn.ready = function(callback) {
            // Check if the callback contains quiz-related code
            if (callback && callback.toString().match(/quiz|wpProQuiz|learndash.*quiz|strict.*next|force.*hint/i)) {
                console.log('Blocked jQuery initialization for quiz script');
                return this;
            }
            return originalReady.apply(this, arguments);
        };
        
        console.log('Quiz script conflict resolver active');
    })();
    </script>
    <?php
}

/**
 * Add inline script to the footer to clean up any remaining conflicts
 */
function lilac_add_script_cleanup() {
    // Only run on quiz pages
    if (!is_singular('sfwd-quiz') && !has_shortcode(get_post()->post_content ?? '', 'ld_quiz')) {
        return;
    }
    
    ?>
    <script>
    // Clean up any remaining conflicts
    (function() {
        // Wait for page to fully load
        window.addEventListener('load', function() {
            // Remove any duplicate event listeners on quiz elements
            const quizElements = document.querySelectorAll('.wpProQuiz_questionList, .wpProQuiz_questionListItem, .wpProQuiz_button');
            
            if (quizElements.length > 0) {
                console.log('Cleaning up potential event listener conflicts on ' + quizElements.length + ' quiz elements');
                
                // Clone and replace elements to remove all event listeners
                quizElements.forEach(function(element) {
                    const clone = element.cloneNode(true);
                    if (element.parentNode) {
                        element.parentNode.replaceChild(clone, element);
                    }
                });
                
                // Reinitialize our quiz controller
                if (window.LilacQuiz && window.LilacQuiz.init) {
                    console.log('Reinitializing Lilac Quiz Controller after cleanup');
                    setTimeout(function() {
                        window.LilacQuiz.init();
                    }, 100);
                }
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'lilac_add_script_cleanup', 999);
