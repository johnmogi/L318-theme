/**
 * Quiz Error Handler
 * 
 * This script runs early and prevents loading of problematic scripts
 * while providing fallback functionality.
 */

(function() {
    // Immediately execute before other scripts
    console.log('Quiz Error Handler: Active');
    
    // Ultra-aggressive approach to block and prevent any problematic scripts
    
    // Block script loading via appendChild
    const originalAppendChild = Node.prototype.appendChild;
    Node.prototype.appendChild = function(child) {
        if (child && 
            child.tagName === 'SCRIPT' && 
            child.src && 
            child.src.includes('ultra-speed-quiz.js')) {
            console.log('Quiz Error Handler: Blocked loading of ultra-speed-quiz.js via appendChild');
            // Return a dummy node instead to prevent errors
            return document.createComment('Script loading blocked by Quiz Error Handler');
        }
        return originalAppendChild.call(this, child);
    };
    
    // Block script loading via insertBefore
    const originalInsertBefore = Node.prototype.insertBefore;
    Node.prototype.insertBefore = function(child, refChild) {
        if (child && 
            child.tagName === 'SCRIPT' && 
            child.src && 
            child.src.includes('ultra-speed-quiz.js')) {
            console.log('Quiz Error Handler: Blocked loading of ultra-speed-quiz.js via insertBefore');
            // Return a dummy node instead to prevent errors
            return document.createComment('Script loading blocked by Quiz Error Handler');
        }
        return originalInsertBefore.call(this, child, refChild);
    };
    
    // Block inline script execution that might create problems
    const originalCreateElement = Document.prototype.createElement;
    Document.prototype.createElement = function(tagName) {
        const element = originalCreateElement.call(this, tagName);
        
        if (tagName.toLowerCase() === 'script') {
            // Override the src setter for script elements
            const originalSrcSetter = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src').set;
            
            Object.defineProperty(element, 'src', {
                set: function(value) {
                    if (value && value.includes('ultra-speed-quiz.js')) {
                        console.log('Quiz Error Handler: Blocked setting src to ultra-speed-quiz.js');
                        return;
                    }
                    originalSrcSetter.call(this, value);
                },
                get: Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src').get
            });
        }
        
        return element;
    };
    
    // Create a global variable to prevent the problematic script from executing
    window.__QUIZ_SCRIPT_BLOCKED = true;
    
    // Override any global functions the script might rely on
    window.__OVERRIDE_QUIZ_SCRIPT = function() {
        // Clear any interval created by the problematic script
        if (window.__lilac_quiz_script_interval) {
            clearInterval(window.__lilac_quiz_script_interval);
        }
    };
    
    // Run the override periodically
    setInterval(window.__OVERRIDE_QUIZ_SCRIPT, 100);

    // Create a global debugging namespace for quiz management
    window.LilacQuizDebug = {
        version: '1.0.0',
        timestamp: new Date().toISOString(),
        log: function(label, data) {
            console.log(`Lilac Quiz Debug: ${label}`, data || '');
        },
        getQuizSettings: function() {
            // A reusable function to get quiz settings safely
            const settings = {
                quizId: null,
                options: {},
                detectedFrom: 'unknown'
            };
            
            try {
                // Try to get settings from the DOM first
                const quizMetaEl = document.querySelector('.wpProQuiz_quiz');
                if (quizMetaEl && quizMetaEl.dataset.quizId) {
                    settings.quizId = quizMetaEl.dataset.quizId;
                    settings.detectedFrom = 'DOM dataset';
                }
                
                // Try global quizExtensionsSettings if available
                if (window.quizExtensionsSettings) {
                    if (!settings.quizId && window.quizExtensionsSettings.current_quiz_id) {
                        settings.quizId = window.quizExtensionsSettings.current_quiz_id;
                        settings.detectedFrom = 'quizExtensionsSettings';
                    }
                    
                    // Get options
                    if (settings.quizId && window.quizExtensionsSettings.quiz_options) {
                        settings.options = window.quizExtensionsSettings.quiz_options[settings.quizId] || {};
                    }
                }
                
                // Provide normalized settings regardless of format
                const normalizedSettings = {
                    forceHintMode: (
                        settings.options.force_hint_mode === 'Yes' || 
                        settings.options['Force Hint Mode'] === 'ENABLED'
                    ),
                    requireCorrect: (
                        settings.options.require_correct === 'Yes' || 
                        settings.options['Require Correct'] === 'Yes'
                    ),
                    showHint: (
                        settings.options.show_hint === 'Yes' || 
                        settings.options['Show Hint'] === 'Yes'
                    ),
                    autoShowHint: (
                        settings.options.auto_show_hint === 'Yes' || 
                        settings.options['Auto Show Hint'] === 'Yes'
                    )
                };
                
                // Add normalized settings
                settings.normalized = normalizedSettings;
                
                // Log the result
                this.log('Quiz settings retrieved', settings);
                
            } catch (e) {
                console.error('Quiz Error Handler: Error retrieving quiz settings', e);
            }
            
            return settings;
        }
    };

    // Initialize as soon as possible
    document.addEventListener('DOMContentLoaded', function() {
        LilacQuizDebug.log('DOM loaded');
        LilacQuizDebug.getQuizSettings();
    });
})();
