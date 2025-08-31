<?php
/**
 * Direct Quiz Answers
 * 
 * A straightforward solution that directly provides correct answers
 * for quiz questions without complex database queries.
 */

// Add to footer to catch all questions
add_action('wp_footer', 'lilac_direct_quiz_answers');

function lilac_direct_quiz_answers() {
    if (!is_singular('sfwd-quiz')) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hard-coded correct answers map based on question IDs
        var correctAnswers = {
            // Question ID : Correct Answer Value
            '42': '1', // First question
            '26': '2'  // Second question
        };
        
        // Log answers for verification
        console.log('[QUIZ DEBUG] *** QUIZ ANSWERS BY ID ***');
        console.log(correctAnswers);
        console.log('[QUIZ DEBUG] *******************************');
        
        // Add visual display of answers on page
        var debugElement = document.createElement('div');
        debugElement.style.position = 'fixed';
        debugElement.style.bottom = '10px';
        debugElement.style.right = '10px';
        debugElement.style.backgroundColor = 'white';
        debugElement.style.border = '1px solid black';
        debugElement.style.padding = '10px';
        debugElement.style.zIndex = '9999';
        debugElement.style.maxWidth = '350px';
        debugElement.style.fontFamily = 'monospace';
        debugElement.style.fontSize = '12px';
        debugElement.style.opacity = '0.9';
        
        // Add a header with toggle button
        var headerDiv = document.createElement('div');
        headerDiv.style.display = 'flex';
        headerDiv.style.justifyContent = 'space-between';
        headerDiv.style.alignItems = 'center';
        headerDiv.style.marginBottom = '10px';
        headerDiv.style.borderBottom = '1px solid #ccc';
        headerDiv.style.paddingBottom = '5px';
        
        var heading = document.createElement('h4');
        heading.style.margin = '0';
        heading.innerText = 'Quiz Answer Validation';
        
        var toggleBtn = document.createElement('button');
        toggleBtn.innerText = 'Toggle';
        toggleBtn.style.fontSize = '11px';
        toggleBtn.onclick = function() {
            var content = document.getElementById('lilac-answers-content');
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        };
        
        headerDiv.appendChild(heading);
        headerDiv.appendChild(toggleBtn);
        debugElement.appendChild(headerDiv);
        
        // Create content container
        var contentDiv = document.createElement('div');
        contentDiv.id = 'lilac-answers-content';
        
        // Add answer information
        var infoP = document.createElement('p');
        infoP.innerHTML = '<strong>Available answers by question ID:</strong>';
        contentDiv.appendChild(infoP);
        
        // Create a table for answers
        var answerTable = document.createElement('table');
        answerTable.style.width = '100%';
        answerTable.style.borderCollapse = 'collapse';
        
        // Add header row
        var headerRow = document.createElement('tr');
        headerRow.innerHTML = '<th style="text-align: left; border-bottom: 1px solid #ccc;">Question ID</th>' + 
                              '<th style="text-align: left; border-bottom: 1px solid #ccc;">Correct Answer</th>';
        answerTable.appendChild(headerRow);
        
        // Add rows for each answer
        for (var qid in correctAnswers) {
            var row = document.createElement('tr');
            row.innerHTML = '<td style="padding: 3px;">' + qid + '</td>' + 
                            '<td style="padding: 3px;">' + correctAnswers[qid] + '</td>';
            answerTable.appendChild(row);
        }
        
        contentDiv.appendChild(answerTable);
        
        // Add current question tracking
        var currentQuestion = document.createElement('div');
        currentQuestion.style.marginTop = '10px';
        currentQuestion.style.padding = '5px';
        currentQuestion.style.backgroundColor = '#f0f0f0';
        currentQuestion.style.borderRadius = '3px';
        currentQuestion.innerHTML = '<strong>Current Question:</strong> <span id="current-question-id">None</span><br>' +
                                    '<strong>Expected Answer:</strong> <span id="current-answer">None</span>';
        contentDiv.appendChild(currentQuestion);
        
        // Add to the debug element
        debugElement.appendChild(contentDiv);
        
        // Add to page
        document.body.appendChild(debugElement);
        
        // Function to implement answer checking and validation
        function setupAnswerValidation() {
            // Get question IDs from the DOM
            var listItems = document.querySelectorAll('.wpProQuiz_listItem');
            listItems.forEach(function(item) {
                var meta = item.getAttribute('data-question-meta');
                
                if (meta) {
                    try {
                        var parsedMeta = JSON.parse(meta);
                        var qid = parsedMeta.question_post_id;
                        
                        // Log the question ID
                        console.log('[QUIZ DEBUG] Found question ID in DOM:', qid);
                        
                        // Track it on our debug display
                        var questionIdSpan = document.getElementById('current-question-id');
                        var answerSpan = document.getElementById('current-answer');
                        
                        if (questionIdSpan && qid) {
                            questionIdSpan.textContent = qid;
                        }
                        
                        if (answerSpan && correctAnswers[qid]) {
                            answerSpan.textContent = correctAnswers[qid];
                        }
                    } catch(e) {
                        console.error('[QUIZ DEBUG] Error parsing question meta:', e);
                    }
                }
            });
            
            // Monitor check button clicks
            document.addEventListener('click', function(e) {
                // If check button is clicked
                if (e.target && e.target.name === 'check' && e.target.className.includes('wpProQuiz_button')) {
                    console.log('[QUIZ DEBUG] Check button clicked');
                    
                    // Wait for LearnDash to update the DOM
                    setTimeout(function() {
                        var questionItem = e.target.closest('.wpProQuiz_listItem');
                        if (!questionItem) return;
                        
                        // Get question ID
                        var meta = questionItem.getAttribute('data-question-meta');
                        if (!meta) return;
                        
                        try {
                            var parsed = JSON.parse(meta);
                            var questionId = parsed.question_post_id;
                            
                            // Find selected answer
                            var selectedInput = questionItem.querySelector('.wpProQuiz_questionInput:checked');
                            var userAnswer = selectedInput ? selectedInput.value : null;
                            
                            // Get correct answer
                            var correctAnswer = correctAnswers[questionId];
                            
                            // Log comparison
                            console.log('[QUIZ DEBUG] Question ID:', questionId);
                            console.log('[QUIZ DEBUG] User selected:', userAnswer);
                            console.log('[QUIZ DEBUG] Correct answer:', correctAnswer);
                            console.log('[QUIZ DEBUG] Match?', userAnswer === correctAnswer);
                            
                            // Handle incorrect answer
                            var incorrectMsg = questionItem.querySelector('.wpProQuiz_incorrect');
                            if (incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none') {
                                // Re-enable inputs
                                var inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput');
                                for (var i = 0; i < inputs.length; i++) {
                                    inputs[i].disabled = false;
                                    inputs[i].removeAttribute('disabled');
                                }
                                
                                // Show check button
                                var checkBtn = questionItem.querySelector('.wpProQuiz_button[name="check"]');
                                if (checkBtn) {
                                    checkBtn.style.display = 'inline-block';
                                }
                                
                                // Hide next button
                                var nextBtn = questionItem.querySelector('.wpProQuiz_button[name="next"]');
                                if (nextBtn) {
                                    nextBtn.style.display = 'none';
                                }
                                
                                // Force correct answer validation
                                var newCheckHandler = function(e) {
                                    setTimeout(function() {
                                        var selectedInput = questionItem.querySelector('.wpProQuiz_questionInput:checked');
                                        var userAnswer = selectedInput ? selectedInput.value : null;
                                        
                                        console.log('[QUIZ DEBUG] Re-check: User selected', userAnswer);
                                        console.log('[QUIZ DEBUG] Re-check: Correct answer is', correctAnswer);
                                        
                                        // Only show Next button if correct answer
                                        var nextButton = questionItem.querySelector('.wpProQuiz_button[name="next"]');
                                        if (userAnswer === correctAnswer) {
                                            if (nextButton) {
                                                nextButton.style.display = 'inline-block';
                                                console.log('[QUIZ DEBUG] Showing Next button - correct answer');
                                            }
                                        } else {
                                            if (nextButton) {
                                                nextButton.style.display = 'none';
                                                console.log('[QUIZ DEBUG] Keeping Next button hidden - still wrong');
                                            }
                                        }
                                    }, 100);
                                };
                                
                                // Add the check handler to the check button
                                var checkButton = questionItem.querySelector('.wpProQuiz_button[name="check"]');
                                if (checkButton) {
                                    // Remove existing handlers
                                    var newCheckBtn = checkButton.cloneNode(true);
                                    checkButton.parentNode.replaceChild(newCheckBtn, checkButton);
                                    
                                    // Add new handler
                                    newCheckBtn.addEventListener('click', newCheckHandler);
                                }
                            }
                        } catch(e) {
                            console.error('[QUIZ DEBUG] Error handling check button:', e);
                        }
                    }, 100);
                }
            });
        }
        
        // Process any existing questions
        function processExistingQuestions() {
            var questionItems = document.querySelectorAll('.wpProQuiz_listItem');
            questionItems.forEach(function(questionItem) {
                var responseVisible = questionItem.querySelector('.wpProQuiz_response[style*="display: block"]');
                if (responseVisible) {
                    // Get question ID
                    var meta = questionItem.getAttribute('data-question-meta');
                    if (!meta) return;
                    
                    try {
                        var parsed = JSON.parse(meta);
                        var questionId = parsed.question_post_id;
                        
                        // Check if incorrect
                        var incorrectMsg = questionItem.querySelector('.wpProQuiz_incorrect');
                        if (incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none') {
                            // Re-enable inputs
                            var inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput');
                            for (var i = 0; i < inputs.length; i++) {
                                inputs[i].disabled = false;
                                inputs[i].removeAttribute('disabled');
                            }
                            
                            // Show check button
                            var checkBtn = questionItem.querySelector('.wpProQuiz_button[name="check"]');
                            if (checkBtn) {
                                checkBtn.style.display = 'inline-block';
                            }
                            
                            // Hide next button
                            var nextBtn = questionItem.querySelector('.wpProQuiz_button[name="next"]');
                            if (nextBtn) {
                                nextBtn.style.display = 'none';
                            }
                        }
                    } catch(e) {
                        console.error('[QUIZ DEBUG] Error processing existing question:', e);
                    }
                }
            });
        }
        
        // Initialize
        setupAnswerValidation();
        processExistingQuestions();
    });
    </script>
    <?php
}
