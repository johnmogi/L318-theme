/**
 * QuizCore.js - Core functionality for the LearnDash Quiz enhancements
 * 
 * This file serves as the entry point and coordinator for all quiz enhancement modules.
 * It follows the module pattern for better organization and separation of concerns.
 */

const LilacQuiz = (function() {
    'use strict';
    
    // Main container for all quiz modules
    const modules = {};
    
    // Settings that will be populated from WordPress
    let settings = {
        enforceHint: false,
        allowReselection: false,
        debug: false
    };
    
    /**
     * Initialize the quiz enhancement system
     * @param {Object} options - Configuration options passed from WordPress
     */
    function init(options = {}) {
        console.log('LilacQuiz Core initializing...');
        
        // Merge provided options with defaults
        settings = Object.assign(settings, options);
        
        // Initialize registered modules that are enabled
        for (const [name, module] of Object.entries(modules)) {
            if (shouldInitModule(name, module)) {
                console.log(`Initializing module: ${name}`);
                module.init(settings);
            }
        }
        
        // Set up global event listeners
        setupEvents();
    }
    
    /**
     * Determine if a module should be initialized based on settings
     * @param {string} name - The module name
     * @param {Object} module - The module object
     * @returns {boolean} - Whether the module should be initialized
     */
    function shouldInitModule(name, module) {
        // If module requires specific settings, check those
        if (module.requires) {
            for (const requirement of module.requires) {
                if (!settings[requirement]) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Set up global events needed by multiple modules
     */
    function setupEvents() {
        // Watch for page changes (helpful for single page applications or AJAX-based quizzes)
        window.addEventListener('hashchange', function() {
            refresh();
        });
        
        // Set up a way to refresh module functionality when needed
        document.addEventListener('lilacQuizRefresh', function() {
            refresh();
        });
    }
    
    /**
     * Register a new module with the core
     * @param {string} name - The name of the module
     * @param {Object} module - The module object with at least an init function
     */
    function registerModule(name, module) {
        if (!module || typeof module.init !== 'function') {
            console.error(`Module ${name} is invalid - must have an init function`);
            return;
        }
        
        modules[name] = module;
        
        // If we're already initialized, initialize this module right away
        if (settings.initialized && shouldInitModule(name, module)) {
            module.init(settings);
        }
    }
    
    /**
     * Refresh all modules (useful after DOM changes)
     */
    function refresh() {
        for (const [name, module] of Object.entries(modules)) {
            if (shouldInitModule(name, module) && typeof module.refresh === 'function') {
                module.refresh();
            }
        }
    }
    
    /**
     * Debug logger that only logs if debug is enabled
     * @param {string} message - Message to log
     * @param {*} [data] - Optional data to log
     */
    function log(message, data) {
        if (settings.debug) {
            if (data !== undefined) {
                console.log(`[LilacQuiz] ${message}`, data);
            } else {
                console.log(`[LilacQuiz] ${message}`);
            }
        }
    }
    
    // Public API
    return {
        init: init,
        registerModule: registerModule,
        refresh: refresh,
        log: log,
        getSettings: function() {
            return {...settings};
        },
        // Add getModule function to allow access to modules for cross-module communication
        getModule: function(name) {
            return modules[name] || null;
        },
        // Debug function to log all module states
        debugModules: function() {
            console.log('--- LilacQuiz Modules Debug ---');
            console.log('Settings:', settings);
            console.log('Registered modules:', Object.keys(modules));
            for (const [name, module] of Object.entries(modules)) {
                console.log(`Module: ${name}`, module);
            }
            console.log('-----------------------------');
        }
    };
})();

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
    // The actual initialization will happen when WordPress calls LilacQuiz.init() with settings
    // This ensures everything is loaded first
    if (window.lilacQuizSettings) {
        LilacQuiz.init(window.lilacQuizSettings);
    }
});
