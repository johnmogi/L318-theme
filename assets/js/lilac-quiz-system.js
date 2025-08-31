/**
 * Lilac Quiz System - Main Entry Point
 * 
 * This file serves as the primary entry point for the Lilac Quiz enhancement system.
 * It loads all required modules and initializes the core functionality.
 */

// Wait for DOM content to load
document.addEventListener('DOMContentLoaded', function() {
    // Check if LilacQuiz is available (from QuizCore.js)
    if (typeof LilacQuiz === 'undefined') {
        console.error('LilacQuiz core module is not loaded!');
        return;
    }
    
    // Initialize with settings from WordPress
    // These settings will be populated by WordPress in functions.php
    const quizSettings = window.lilacQuizSettings || {
        enforceHint: true,
        allowReselection: true,
        debug: false
    };
    
    // Initialize the quiz system
    LilacQuiz.init(quizSettings);
    
    console.log('Lilac Quiz System initialized successfully');
});
