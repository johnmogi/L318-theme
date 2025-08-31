/**
 * LearnDash Quiz Answer Debug Tool
 * 
 * This script provides detailed debugging information for LearnDash quiz answers
 * to help diagnose why answer detection might be failing.
 */

(function() {
    'use strict';
    
    // Configuration
    const DEBUG_CONFIG = {
        showDebugPanel: true,        // Show floating debug panel
        logToConsole: true,          // Log debug info to console
        highlightElements: true,     // Add visual indicators to elements being analyzed
        pollInterval: 1000,          // How often to check for changes (ms)
        maxLogEntries: 100           // Maximum number of log entries to keep
    };
    
    // State
    const DEBUG_STATE = {
        logs: [],
        questions: {},
        initialized: false,
        panel: null
    };
    
    /**
     * Initialize the debug tool
     */
    function init() {
        if (DEBUG_STATE.initialized) return;
        
        console.log('🔍 LearnDash Quiz Answer Debug Tool initializing...');
        
        // Create debug panel if enabled
        if (DEBUG_CONFIG.showDebugPanel) {
            createDebugPanel();
        }
        
        // Find all questions
        scanQuestions();
        
        // Set up event listeners
        setupEventListeners();
        
        // Start polling for changes
        startPolling();
        
        DEBUG_STATE.initialized = true;
        logDebug('Debug tool initialized', { questions: Object.keys(DEBUG_STATE.questions).length });
    }
    
    /**
     * Scan the page for quiz questions
     */
    function scanQuestions() {
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        
        logDebug(`Found ${questionItems.length} questions`);
        
        questionItems.forEach((item, index) => {
            // Try to get question ID
            let questionId = getQuestionId(item, index);
            
            // Store question data
            DEBUG_STATE.questions[questionId] = {
                element: item,
                index: index,
                id: questionId,
                selectedAnswers: [],
                answerState: 'unanswered', // unanswered, answered, correct, incorrect
                lastChecked: null,
                debugData: {}
            };
            
            // Initial analysis
            analyzeQuestion(questionId);
        });
    }
    
    /**
     * Get a question ID using various methods
     */
    function getQuestionId(questionItem, fallbackIndex) {
        // Method 1: From data-question-id attribute
        if (questionItem.dataset.questionId) {
            return questionItem.dataset.questionId;
        }
        
        // Method 2: From data-post-id attribute
        if (questionItem.dataset.postId) {
            return questionItem.dataset.postId;
        }
        
        // Method 3: From question meta
        const metaElem = questionItem.querySelector('[data-question-meta]');
        if (metaElem && metaElem.dataset.questionMeta) {
            try {
                const meta = JSON.parse(metaElem.dataset.questionMeta);
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
            } catch (e) {
                // Ignore JSON parse errors
            }
        }
        
        // Fallback: Use index
        return `q_${fallbackIndex}`;
    }
    
    /**
     * Set up event listeners for quiz interactions
     */
    function setupEventListeners() {
        // Listen for answer selections
        document.addEventListener('click', function(event) {
            // Check if click is on an answer
            const answerItem = event.target.closest('.wpProQuiz_questionListItem');
            if (!answerItem) return;
            
            // Find the question
            const questionItem = answerItem.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            // Get question ID
            const questionId = getQuestionId(questionItem);
            if (!questionId) return;
            
            // Log the selection
            logDebug(`Answer selected for question ${questionId}`, { 
                answerText: answerItem.textContent.trim().substring(0, 50),
                answerClasses: answerItem.className,
                answerData: getElementData(answerItem)
            });
            
            // Update state
            if (DEBUG_STATE.questions[questionId]) {
                DEBUG_STATE.questions[questionId].selectedAnswers.push(answerItem);
                DEBUG_STATE.questions[questionId].answerState = 'answered';
                DEBUG_STATE.questions[questionId].lastChecked = new Date();
            }
            
            // Analyze after a short delay (let LearnDash process it)
            setTimeout(() => {
                analyzeQuestion(questionId);
            }, 500);
        });
        
        // Listen for check button clicks
        document.addEventListener('click', function(event) {
            // Check if click is on a check button
            const checkButton = event.target.closest('.wpProQuiz_button[name=check]');
            if (!checkButton) return;
            
            // Find the question
            const questionItem = checkButton.closest('.wpProQuiz_listItem');
            if (!questionItem) return;
            
            // Get question ID
            const questionId = getQuestionId(questionItem);
            if (!questionId) return;
            
            logDebug(`Check button clicked for question ${questionId}`);
            
            // Analyze after a short delay
            setTimeout(() => {
                analyzeQuestion(questionId);
            }, 500);
        });
    }
    
    /**
     * Start polling for changes
     */
    function startPolling() {
        setInterval(() => {
            // Check each question
            for (const questionId in DEBUG_STATE.questions) {
                analyzeQuestion(questionId);
            }
            
            // Update debug panel
            if (DEBUG_CONFIG.showDebugPanel && DEBUG_STATE.panel) {
                updateDebugPanel();
            }
        }, DEBUG_CONFIG.pollInterval);
    }
    
    /**
     * Analyze a question and its answers
     */
    function analyzeQuestion(questionId) {
        const questionData = DEBUG_STATE.questions[questionId];
        if (!questionData) return;
        
        const questionItem = questionData.element;
        
        // Check for selected answers
        const selectedInputs = questionItem.querySelectorAll('.wpProQuiz_questionListItem input:checked');
        const selectedItems = questionItem.querySelectorAll('.wpProQuiz_questionListItem.selected');
        
        // Combine selected items
        const allSelected = [...selectedInputs, ...selectedItems].map(item => {
            return item.closest('.wpProQuiz_questionListItem');
        }).filter(Boolean);
        
        // Update selected answers if changed
        if (allSelected.length > 0 && questionData.selectedAnswers.length !== allSelected.length) {
            questionData.selectedAnswers = allSelected;
            questionData.answerState = 'answered';
            questionData.lastChecked = new Date();
        }
        
        // Collect debug data
        const debugData = {
            // Question data
            questionClasses: questionItem.className,
            questionData: getElementData(questionItem),
            
            // Selected answers
            selectedCount: allSelected.length,
            selectedAnswers: allSelected.map(item => ({
                text: item.textContent.trim().substring(0, 50),
                classes: item.className,
                data: getElementData(item)
            })),
            
            // Response elements
            responseElements: Array.from(questionItem.querySelectorAll('.wpProQuiz_response, .learndash-quiz-question-response')).map(el => ({
                text: el.textContent.trim(),
                classes: el.className,
                style: {
                    display: window.getComputedStyle(el).display,
                    visibility: window.getComputedStyle(el).visibility,
                    backgroundColor: window.getComputedStyle(el).backgroundColor
                }
            })),
            
            // Check for correct/incorrect indicators
            correctIndicators: findElements(questionItem, [
                '.wpProQuiz_correct', 
                '.learndash-correct-response',
                '.wpProQuiz_answerCorrect',
                '.correct',
                '.is-correct'
            ]),
            
            incorrectIndicators: findElements(questionItem, [
                '.wpProQuiz_incorrect', 
                '.learndash-incorrect-response',
                '.wpProQuiz_answerIncorrect',
                '.incorrect',
                '.wrong'
            ]),
            
            // LearnDash internal data
            learnDashData: getLearnDashData(questionId)
        };
        
        // Determine if answer is correct
        const isCorrect = determineIfCorrect(debugData);
        
        // Update state
        questionData.debugData = debugData;
        if (isCorrect) {
            questionData.answerState = 'correct';
        } else if (debugData.selectedCount > 0 && debugData.incorrectIndicators.length > 0) {
            questionData.answerState = 'incorrect';
        }
        
        // Log if state changed
        if (isCorrect && questionData.answerState !== 'correct') {
            logDebug(`Question ${questionId} is CORRECT`, { 
                reasons: determineCorrectReasons(debugData)
            });
        }
    }
    
    /**
     * Determine if an answer is correct based on debug data
     */
    function determineIfCorrect(debugData) {
        // No selection means not correct
        if (debugData.selectedCount === 0) {
            return false;
        }
        
        // Check for correct indicators
        if (debugData.correctIndicators.length > 0) {
            return true;
        }
        
        // Check response elements
        for (const response of debugData.responseElements) {
            // Check for correct text
            if (response.text && (
                response.text.toLowerCase().includes('correct') ||
                response.text.toLowerCase().includes('right') ||
                response.text.toLowerCase().includes('נכון') // Hebrew for correct
            )) {
                return true;
            }
            
            // Check for green background (common for correct answers)
            if (response.style.backgroundColor && (
                response.style.backgroundColor.includes('green') ||
                response.style.backgroundColor.includes('rgb(0, 128, 0') ||
                response.style.backgroundColor.includes('#00') // Various green hex codes
            )) {
                return true;
            }
            
            // Check if correct response is visible and incorrect is hidden
            if (response.classes.includes('correct') && 
                response.style.display !== 'none' && 
                response.style.visibility !== 'hidden') {
                return true;
            }
        }
        
        // Check LearnDash data
        if (debugData.learnDashData && debugData.learnDashData.correct === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get reasons why an answer was determined to be correct
     */
    function determineCorrectReasons(debugData) {
        const reasons = [];
        
        // Check for correct indicators
        if (debugData.correctIndicators.length > 0) {
            reasons.push(`Found ${debugData.correctIndicators.length} correct indicators`);
        }
        
        // Check response elements
        for (const response of debugData.responseElements) {
            // Check for correct text
            if (response.text && (
                response.text.toLowerCase().includes('correct') ||
                response.text.toLowerCase().includes('right') ||
                response.text.toLowerCase().includes('נכון')
            )) {
                reasons.push(`Response text contains 'correct' or similar`);
            }
            
            // Check for green background
            if (response.style.backgroundColor && (
                response.style.backgroundColor.includes('green') ||
                response.style.backgroundColor.includes('rgb(0, 128, 0') ||
                response.style.backgroundColor.includes('#00')
            )) {
                reasons.push(`Response has green background: ${response.style.backgroundColor}`);
            }
            
            // Check if correct response is visible
            if (response.classes.includes('correct') && 
                response.style.display !== 'none' && 
                response.style.visibility !== 'hidden') {
                reasons.push(`Correct response element is visible`);
            }
        }
        
        // Check LearnDash data
        if (debugData.learnDashData && debugData.learnDashData.correct === true) {
            reasons.push(`LearnDash internal data indicates correct`);
        }
        
        return reasons;
    }
    
    /**
     * Find elements matching any of the selectors
     */
    function findElements(container, selectors) {
        const results = [];
        
        for (const selector of selectors) {
            const elements = container.querySelectorAll(selector);
            elements.forEach(el => {
                // Only include visible elements
                const style = window.getComputedStyle(el);
                if (style.display !== 'none' && style.visibility !== 'hidden') {
                    results.push({
                        selector: selector,
                        element: el,
                        text: el.textContent.trim(),
                        classes: el.className
                    });
                }
            });
        }
        
        return results;
    }
    
    /**
     * Get all data attributes from an element
     */
    function getElementData(element) {
        const data = {};
        
        // Get all data attributes
        for (const key in element.dataset) {
            data[key] = element.dataset[key];
        }
        
        // Special handling for inputs
        const inputs = element.querySelectorAll('input');
        if (inputs.length > 0) {
            data.inputs = Array.from(inputs).map(input => ({
                type: input.type,
                name: input.name,
                value: input.value,
                checked: input.checked,
                data: Object.assign({}, input.dataset)
            }));
        }
        
        return data;
    }
    
    /**
     * Get LearnDash internal data for a question
     */
    function getLearnDashData(questionId) {
        try {
            // Try to find quiz ID
            const quizContent = document.querySelector('.wpProQuiz_content');
            if (!quizContent) return null;
            
            const quizId = quizContent.id.replace('wpProQuiz_', '');
            
            // Check for wpProQuizInitList
            if (window.wpProQuizInitList && window.wpProQuizInitList[quizId]) {
                const quizData = window.wpProQuizInitList[quizId];
                
                // Check for results
                if (quizData.catResults && quizData.catResults[questionId]) {
                    return quizData.catResults[questionId];
                }
                
                // Check question list
                if (quizData.questionList) {
                    // Try to find by index
                    const questionElement = DEBUG_STATE.questions[questionId].element;
                    const index = Array.from(questionElement.parentNode.children).indexOf(questionElement);
                    
                    if (quizData.questionList[index]) {
                        return quizData.questionList[index];
                    }
                }
            }
            
            // Check global wpProQuiz_data
            if (window.wpProQuiz_data) {
                return window.wpProQuiz_data;
            }
        } catch (e) {
            console.error('Error getting LearnDash data:', e);
        }
        
        return null;
    }
    
    /**
     * Create the debug panel
     */
    function createDebugPanel() {
        // Create panel container
        const panel = document.createElement('div');
        panel.id = 'lilac-quiz-debug-panel';
        panel.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            max-height: 500px;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            font-family: monospace;
            font-size: 12px;
            padding: 10px;
            border-radius: 5px;
            z-index: 9999;
            overflow: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        `;
        
        // Create header
        const header = document.createElement('div');
        header.innerHTML = '<h3 style="margin: 0 0 10px; color: #00ff00;">LearnDash Quiz Debug</h3>';
        header.style.cursor = 'move';
        panel.appendChild(header);
        
        // Create content area
        const content = document.createElement('div');
        content.id = 'lilac-quiz-debug-content';
        panel.appendChild(content);
        
        // Create log area
        const logArea = document.createElement('div');
        logArea.id = 'lilac-quiz-debug-log';
        logArea.style.cssText = `
            margin-top: 10px;
            border-top: 1px solid #444;
            padding-top: 10px;
            max-height: 200px;
            overflow: auto;
        `;
        panel.appendChild(logArea);
        
        // Add to document
        document.body.appendChild(panel);
        
        // Make draggable
        makeDraggable(panel, header);
        
        // Store reference
        DEBUG_STATE.panel = panel;
        
        // Initial update
        updateDebugPanel();
    }
    
    /**
     * Update the debug panel with current state
     */
    function updateDebugPanel() {
        if (!DEBUG_STATE.panel) return;
        
        const content = document.getElementById('lilac-quiz-debug-content');
        const logArea = document.getElementById('lilac-quiz-debug-log');
        
        if (!content || !logArea) return;
        
        // Update questions
        let questionsHtml = '<div style="margin-bottom: 10px;"><strong>Questions:</strong></div>';
        
        for (const questionId in DEBUG_STATE.questions) {
            const questionData = DEBUG_STATE.questions[questionId];
            const stateColor = getStateColor(questionData.answerState);
            
            questionsHtml += `
                <div style="margin-bottom: 8px; padding: 5px; border-left: 3px solid ${stateColor};">
                    <div><strong>Q${questionData.index + 1}</strong> (ID: ${questionId})</div>
                    <div>State: <span style="color: ${stateColor};">${questionData.answerState}</span></div>
                    <div>Selected: ${questionData.selectedAnswers.length}</div>
                    <div>
                        <button onclick="window.lilacQuizDebug.showDetails('${questionId}')" 
                                style="background: #333; color: #fff; border: none; padding: 2px 5px; cursor: pointer;">
                            Show Details
                        </button>
                    </div>
                </div>
            `;
        }
        
        content.innerHTML = questionsHtml;
        
        // Update logs
        let logsHtml = '<div style="margin-bottom: 5px;"><strong>Debug Log:</strong></div>';
        
        for (const log of DEBUG_STATE.logs.slice(-DEBUG_CONFIG.maxLogEntries)) {
            logsHtml += `
                <div style="margin-bottom: 3px; font-size: 11px;">
                    <span style="color: #aaa;">[${formatTime(log.time)}]</span> 
                    <span>${log.message}</span>
                </div>
            `;
        }
        
        logArea.innerHTML = logsHtml;
        logArea.scrollTop = logArea.scrollHeight;
    }
    
    /**
     * Show detailed debug information for a question
     */
    function showDetails(questionId) {
        const questionData = DEBUG_STATE.questions[questionId];
        if (!questionData) return;
        
        // Create modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            overflow: auto;
            padding: 20px;
            box-sizing: border-box;
        `;
        
        // Create content
        const content = document.createElement('div');
        content.style.cssText = `
            background: #222;
            color: #fff;
            font-family: monospace;
            font-size: 13px;
            padding: 20px;
            border-radius: 5px;
            max-width: 800px;
            margin: 40px auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        `;
        
        // Header
        content.innerHTML = `
            <h2 style="margin-top: 0; color: #00ff00;">Question ${questionData.index + 1} Debug</h2>
            <div style="margin-bottom: 15px;">ID: ${questionId}</div>
        `;
        
        // Question data
        content.innerHTML += `
            <h3 style="margin-bottom: 5px; color: #00ccff;">Question Data</h3>
            <div style="margin-bottom: 15px;">
                <div>State: ${questionData.answerState}</div>
                <div>Classes: ${questionData.debugData.questionClasses}</div>
                <div>Data Attributes: ${formatObject(questionData.debugData.questionData)}</div>
            </div>
        `;
        
        // Selected answers
        content.innerHTML += `
            <h3 style="margin-bottom: 5px; color: #00ccff;">Selected Answers (${questionData.debugData.selectedCount})</h3>
            <div style="margin-bottom: 15px;">
                ${questionData.debugData.selectedAnswers.map((answer, i) => `
                    <div style="margin-bottom: 10px; padding: 5px; border-left: 2px solid #666;">
                        <div><strong>Answer ${i+1}:</strong> ${answer.text}</div>
                        <div>Classes: ${answer.classes}</div>
                        <div>Data: ${formatObject(answer.data)}</div>
                    </div>
                `).join('')}
            </div>
        `;
        
        // Correct/incorrect indicators
        content.innerHTML += `
            <h3 style="margin-bottom: 5px; color: #00ccff;">Correctness Indicators</h3>
            <div style="margin-bottom: 15px;">
                <div style="margin-bottom: 10px;">
                    <strong style="color: #4CAF50;">Correct Indicators (${questionData.debugData.correctIndicators.length}):</strong>
                    ${questionData.debugData.correctIndicators.map(ind => `
                        <div style="margin-left: 15px;">
                            Selector: ${ind.selector}, Text: ${ind.text}
                        </div>
                    `).join('') || '<div style="margin-left: 15px;">None found</div>'}
                </div>
                <div>
                    <strong style="color: #F44336;">Incorrect Indicators (${questionData.debugData.incorrectIndicators.length}):</strong>
                    ${questionData.debugData.incorrectIndicators.map(ind => `
                        <div style="margin-left: 15px;">
                            Selector: ${ind.selector}, Text: ${ind.text}
                        </div>
                    `).join('') || '<div style="margin-left: 15px;">None found</div>'}
                </div>
            </div>
        `;
        
        // Response elements
        content.innerHTML += `
            <h3 style="margin-bottom: 5px; color: #00ccff;">Response Elements</h3>
            <div style="margin-bottom: 15px;">
                ${questionData.debugData.responseElements.map((resp, i) => `
                    <div style="margin-bottom: 10px; padding: 5px; border-left: 2px solid #666;">
                        <div><strong>Response ${i+1}:</strong> ${resp.text}</div>
                        <div>Classes: ${resp.classes}</div>
                        <div>Display: ${resp.style.display}, Visibility: ${resp.style.visibility}</div>
                        <div>Background: ${resp.style.backgroundColor}</div>
                    </div>
                `).join('') || '<div>No response elements found</div>'}
            </div>
        `;
        
        // LearnDash data
        content.innerHTML += `
            <h3 style="margin-bottom: 5px; color: #00ccff;">LearnDash Internal Data</h3>
            <div style="margin-bottom: 15px;">
                <pre style="white-space: pre-wrap; word-break: break-all;">${formatObject(questionData.debugData.learnDashData)}</pre>
            </div>
        `;
        
        // Close button
        content.innerHTML += `
            <div style="text-align: center; margin-top: 20px;">
                <button id="lilac-debug-close-button" style="background: #333; color: #fff; border: none; padding: 8px 15px; cursor: pointer; font-size: 14px;">
                    Close
                </button>
            </div>
        `;
        
        // Add to document
        modal.appendChild(content);
        document.body.appendChild(modal);
        
        // Add close handler
        document.getElementById('lilac-debug-close-button').addEventListener('click', function() {
            document.body.removeChild(modal);
        });
    }
    
    /**
     * Make an element draggable
     */
    function makeDraggable(element, handle) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        
        handle.onmousedown = dragMouseDown;
        
        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            
            // Get mouse position
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            // Add event listeners
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }
        
        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            
            // Calculate new position
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            // Set element's new position
            element.style.top = (element.offsetTop - pos2) + "px";
            element.style.left = (element.offsetLeft - pos1) + "px";
            element.style.bottom = "auto";
            element.style.right = "auto";
        }
        
        function closeDragElement() {
            // Remove event listeners
            document.onmouseup = null;
            document.onmousemove = null;
        }
    }
    
    /**
     * Log a debug message
     */
    function logDebug(message, data) {
        // Add to logs
        DEBUG_STATE.logs.push({
            time: new Date(),
            message: message,
            data: data
        });
        
        // Trim logs if needed
        if (DEBUG_STATE.logs.length > DEBUG_CONFIG.maxLogEntries * 2) {
            DEBUG_STATE.logs = DEBUG_STATE.logs.slice(-DEBUG_CONFIG.maxLogEntries);
        }
        
        // Log to console if enabled
        if (DEBUG_CONFIG.logToConsole) {
            if (data) {
                console.log(`🔍 ${message}`, data);
            } else {
                console.log(`🔍 ${message}`);
            }
        }
    }
    
    /**
     * Format a time for display
     */
    function formatTime(date) {
        return date.toTimeString().split(' ')[0] + '.' + 
               date.getMilliseconds().toString().padStart(3, '0');
    }
    
    /**
     * Format an object for display
     */
    function formatObject(obj) {
        if (!obj) return 'null';
        
        try {
            return JSON.stringify(obj, null, 2);
        } catch (e) {
            return 'Error formatting object';
        }
    }
    
    /**
     * Get color for a state
     */
    function getStateColor(state) {
        switch (state) {
            case 'correct': return '#4CAF50';
            case 'incorrect': return '#F44336';
            case 'answered': return '#FFC107';
            default: return '#aaa';
        }
    }
    
    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', init);
    
    // Also initialize on LearnDash's quiz ready event
    document.addEventListener('learndash-quiz-ready', init);
    
    // Expose API for external access
    window.lilacQuizDebug = {
        init: init,
        getState: function() {
            return DEBUG_STATE;
        },
        showDetails: showDetails,
        analyzeAll: function() {
            for (const questionId in DEBUG_STATE.questions) {
                analyzeQuestion(questionId);
            }
            updateDebugPanel();
        }
    };
})();
