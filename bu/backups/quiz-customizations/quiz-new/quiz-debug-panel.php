<?php
/**
 * Quiz Debug Panel
 * 
 * Adds a floating debug panel for administrators to diagnose quiz issues
 */

defined('ABSPATH') || exit;

/**
 * Add the debug panel to the footer
 */
function lilac_quiz_debug_panel() {
    // Only for admins and editors
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Only on quiz pages
    if (!is_singular('sfwd-quiz')) {
        return;
    }
    
    ?>
    <div id="lilac-quiz-admin-panel" style="position: fixed; top: 32px; right: 10px; z-index: 999999; background: #23282d; color: #fff; padding: 10px; font-size: 12px; border-radius: 3px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
        <div style="margin-bottom: 10px; font-weight: bold; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
            <span>Lilac Quiz Debug</span>
            <span id="lilac-debug-toggle" style="cursor: pointer; font-size: 18px;">−</span>
        </div>
        
        <div id="lilac-debug-content">
            <div style="margin-bottom: 10px;">
                <button onclick="window.lilacQuizDebug.analyzeAll()" style="background: #0073aa; color: #fff; border: none; padding: 5px 10px; cursor: pointer; margin-right: 5px;">
                    Analyze All Questions
                </button>
                
                <button onclick="window.location.reload()" style="background: #555; color: #fff; border: none; padding: 5px 10px; cursor: pointer;">
                    Reload Page
                </button>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px;">Debug Level:</label>
                <select id="lilac-debug-level" style="width: 100%; padding: 5px;">
                    <option value="basic">Basic (UI only)</option>
                    <option value="detailed" selected>Detailed (Console + UI)</option>
                    <option value="verbose">Verbose (All Data)</option>
                </select>
            </div>
            
            <div style="margin-top: 15px;">
                <div style="font-weight: bold; margin-bottom: 5px;">Script Status:</div>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li id="lilac-debug-controller" style="margin-bottom: 3px; display: flex; justify-content: space-between;">
                        <span>Quiz Controller:</span>
                        <span style="color: #4CAF50;">Active</span>
                    </li>
                    <li id="lilac-debug-debugger" style="margin-bottom: 3px; display: flex; justify-content: space-between;">
                        <span>Answer Debugger:</span>
                        <span style="color: #4CAF50;">Active</span>
                    </li>
                    <li id="lilac-debug-conflicts" style="margin-bottom: 3px; display: flex; justify-content: space-between;">
                        <span>Conflict Check:</span>
                        <span>Checking...</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        // Toggle panel visibility
        document.getElementById('lilac-debug-toggle').addEventListener('click', function() {
            const content = document.getElementById('lilac-debug-content');
            const toggle = document.getElementById('lilac-debug-toggle');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '−';
            } else {
                content.style.display = 'none';
                toggle.textContent = '+';
            }
        });
        
        // Set debug level
        document.getElementById('lilac-debug-level').addEventListener('change', function() {
            const level = this.value;
            
            if (window.lilacQuizDebug) {
                window.lilacQuizDebug.setDebugLevel(level);
                console.log(`Quiz debug level set to: ${level}`);
            }
        });
        
        // Check for script conflicts
        setTimeout(function() {
            const conflictStatus = document.getElementById('lilac-debug-conflicts');
            const scripts = document.querySelectorAll('script');
            let conflicts = [];
            
            // List of known conflicting scripts
            const conflictingScripts = [
                'strict-next-button',
                'force-hint-debug',
                'ultra-speed-quiz',
                'quiz-sidebar',
                'next-button-manager'
            ];
            
            // Check for conflicting scripts
            scripts.forEach(script => {
                if (script.src) {
                    conflictingScripts.forEach(conflict => {
                        if (script.src.includes(conflict)) {
                            conflicts.push(conflict);
                        }
                    });
                }
            });
            
            // Update conflict status
            if (conflicts.length > 0) {
                conflictStatus.innerHTML = `
                    <span>Conflict Check:</span>
                    <span style="color: #F44336;">Found ${conflicts.length} conflicts</span>
                `;
                console.warn('Quiz script conflicts detected:', conflicts);
            } else {
                conflictStatus.innerHTML = `
                    <span>Conflict Check:</span>
                    <span style="color: #4CAF50;">No conflicts</span>
                `;
            }
        }, 1000);
        
        // Check if debugger is active
        setTimeout(function() {
            const debuggerStatus = document.getElementById('lilac-debug-debugger');
            
            if (!window.lilacQuizDebug) {
                debuggerStatus.innerHTML = `
                    <span>Answer Debugger:</span>
                    <span style="color: #F44336;">Not loaded</span>
                `;
            }
        }, 1000);
        
        // Check if controller is active
        setTimeout(function() {
            const controllerStatus = document.getElementById('lilac-debug-controller');
            
            if (!window.LilacQuiz) {
                controllerStatus.innerHTML = `
                    <span>Quiz Controller:</span>
                    <span style="color: #F44336;">Not loaded</span>
                `;
            }
        }, 1000);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'lilac_quiz_debug_panel');

/**
 * Add debug mode query parameter to quiz links in admin
 */
function lilac_add_debug_to_quiz_links($permalink, $post) {
    // Only for admins and editors
    if (!current_user_can('edit_posts')) {
        return $permalink;
    }
    
    // Only for quizzes
    if ($post->post_type !== 'sfwd-quiz') {
        return $permalink;
    }
    
    // Add debug parameter
    return add_query_arg('debug', '1', $permalink);
}
add_filter('post_type_link', 'lilac_add_debug_to_quiz_links', 10, 2);
