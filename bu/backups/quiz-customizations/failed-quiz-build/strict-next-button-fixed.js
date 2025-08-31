/**
 * Strict Next Button Controller for LearnDash Quiz
 * 
 * This script provides a more accurate Next button control that
 * only shows the button when the answer is truly correct.
 * Enhanced for proper handling of force hint mode quizzes.
 * 
 * @since 1.0.0
 */

(function() {
    // Debug logging with prefix
    const DEBUG_MODE = true;
    function debug(label, ...args) {
        if (DEBUG_MODE) {
            console.log('Strict Next Button:', label, ...args);
        }
    }

    // State tracking
    const hintViewed = {};
    const questionResults = {};
    const viewedHintThenChecked = {}; // Track if user has checked the answer AFTER viewing the hint
    const rightAnswers = {}; // Store right answers
    
    // Enhanced state for storing correct answers and their positions
    const quizAnswers = {
        // Structure: { questionId: { answerIndex: boolean, position: number } }
        correctAnswers: {},
        // Store raw answer text for reference
        answerText: {},
        // Track if we've already discovered the answers for this question
        discovered: {}
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
    
    // Add an additional initialization when possible LearnDash events occur
    document.addEventListener('wpProQuiz_initComplete', function() {
        debug('LearnDash quiz init detected, re-initializing');
        init();
    });
    
    // Delayed initialization for better DOM content loading
    setTimeout(init, 1000);

    function init() {
        debug('Initializing Strict Next Button Controller');
        
        // Get quiz settings
        const settings = getQuizSettings();
        if (!settings) {
            debug('No quiz settings found, exiting');
            return;
        }
        
        debug('Quiz settings loaded', settings);
        
        // Set up event listeners
        setupEventListeners(settings);
        
        // Add debug button
        addDebugButton();
        
        // Do an initial check of all questions/answers
        initialCheck(settings);
        
        // Additional check after a delay to ensure Next buttons are properly configured
        setTimeout(() => {
            fixAllNextButtons(settings);
        }, 1000);
    }
    
    /**
     * Get quiz settings from the global quizExtensionsSettings
     */
    function getQuizSettings() {
        debug('Getting quiz settings');
        
        if (!window.quizExtensionsSettings) {
            debug('quizExtensionsSettings not found');
            return getDefaultSettings();
        }
        
        // Extract the quiz IDs using multiple methods
        const quizData = getQuizData();
        debug('Quiz data:', quizData);
        
        if (!quizData.quizId) {
            debug('No quiz ID could be determined');
            return getDefaultSettings();
        }
        
        // Get settings from options
        const quizOptions = window.quizExtensionsSettings.quiz_options || {};
        const settings = quizOptions[quizData.quizId] || {};
        debug('Raw quiz settings for ID ' + quizData.quizId + ':', settings);
        
        // Normalize settings
        return normalizeSettings(settings);
    }
    
    /**
     * Get all quiz IDs and metadata through multiple methods
     */
    function getQuizData() {
        const quizData = {
            quizId: null, 
            quizProId: null,
            quizPostId: null,
            fromDom: false,
            fromSettings: false
        };
        
        // Method 1: From settings directly
        if (window.quizExtensionsSettings && window.quizExtensionsSettings.current_quiz_id) {
            quizData.quizId = window.quizExtensionsSettings.current_quiz_id;
            quizData.fromSettings = true;
            debug('Found quiz ID from settings:', quizData.quizId);
        }
        
        // Method 2: From DOM metadata
        const metaFields = document.querySelectorAll('.wpProQuiz_data');
        metaFields.forEach(metaField => {
            // Check quiz pro ID
            const quizProIdField = metaField.querySelector('input[name="quiz_pro_id"]');
            if (quizProIdField && quizProIdField.value) {
                quizData.quizProId = quizProIdField.value;
                debug('Found quiz pro ID from DOM:', quizData.quizProId);
            }
            
            // Check quiz post ID
            const quizPostIdField = metaField.querySelector('input[name="quiz_post_id"]');
            if (quizPostIdField && quizPostIdField.value) {
                quizData.quizPostId = quizPostIdField.value;
                quizData.quizId = quizData.quizPostId; // Use post ID as the main ID
                quizData.fromDom = true;
                debug('Found quiz post ID from DOM:', quizData.quizPostId);
            }
        });

        // Method 3: From quiz meta attribute
        const quizMetaElement = document.querySelector('[data-quiz-meta]');
        if (quizMetaElement) {
            try {
                const quizMeta = JSON.parse(quizMetaElement.getAttribute('data-quiz-meta'));
                if (quizMeta.quiz_post_id) {
                    quizData.quizPostId = quizMeta.quiz_post_id;
                    quizData.quizId = quizData.quizPostId;
                    quizData.fromDom = true;
                    debug('Found quiz post ID from data-quiz-meta:', quizData.quizPostId);
                }
            } catch (e) {
                debug('Error parsing quiz meta', e);
            }
        }
        
        return quizData;
    }
    
    /**
     * Normalize settings to handle different formats
     */
    function normalizeSettings(settings) {
        // Check if we have Force Hint Mode in any format
        const forceHintMode = 
            settings.force_hint_mode === 'Yes' || 
            settings.force_hint_mode === '1' || 
            settings['Force Hint Mode'] === 'ENABLED';
            
        // Check if we have Require Correct in any format
        const requireCorrect = 
            settings.require_correct === 'Yes' || 
            settings.require_correct === '1' || 
            settings['Require Correct'] === 'Yes';
            
        // Check if we have Show Hint in any format
        const showHint = 
            settings.show_hint === 'Yes' || 
            settings.show_hint === '1' || 
            settings['Show Hint'] === 'Yes';
            
        // Check if we have Auto Show Hint in any format
        const autoShowHint = 
            settings.auto_show_hint === 'Yes' || 
            settings.auto_show_hint === '1' || 
            settings['Auto Show Hint'] === 'Yes';
        
        debug('Normalized settings:', {
            forceHintMode,
            requireCorrect, 
            showHint,
            autoShowHint
        });
        
        return {
            forceHintMode,
            requireCorrect,
            showHint,
            autoShowHint,
            // Keep raw settings for debugging
            raw: settings
        };
    }
    
    /**
     * Default settings when no quiz settings are found
     */
    function getDefaultSettings() {
        debug('Using default settings');
        return {
            forceHintMode: false,
            requireCorrect: true,
            showHint: false,
            autoShowHint: false,
            raw: {}
        };
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners(settings) {
        debug('Setting up event listeners with settings:', settings);
        
        // Listen for hint button clicks
        document.addEventListener('click', function(event) {
            // Check if this is a hint button
            if (event.target.classList.contains('wpProQuiz_hint') || 
                event.target.closest('.wpProQuiz_hint')) {
                const hintButton = event.target.classList.contains('wpProQuiz_hint') ? 
                    event.target : 
                    event.target.closest('.wpProQuiz_hint');
                
                handleHintButtonClick(hintButton, settings);
            }
            
            // Check if this is a check button
            if (event.target.classList.contains('wpProQuiz_check') || 
                event.target.closest('.wpProQuiz_check')) {
                const checkButton = event.target.classList.contains('wpProQuiz_check') ? 
                    event.target : 
                    event.target.closest('.wpProQuiz_check');
                
                handleCheckButtonClick(checkButton, settings);
            }
        });
        
        // Listen for input changes (selections)
        document.addEventListener('change', function(event) {
            if (event.target.tagName === 'INPUT' && 
                (event.target.type === 'radio' || event.target.type === 'checkbox') &&
                event.target.closest('.wpProQuiz_questionList')) {
                
                // Get the question item
                const questionItem = event.target.closest('.wpProQuiz_listItem');
                if (questionItem) {
                    // Mark this question list item as having a user selection
                    const listItem = event.target.closest('.wpProQuiz_questionListItem');
                    if (listItem) {
                        listItem.classList.add('user-selected');
                    }
                    
                    // Update the next button based on settings
                    updateNextButton(questionItem, settings);
                }
            }
        });
    }
})
