/**
 * Simple Quiz Answer Revealer
 * Shows correct answers with a single button and fixes Next/Back button visibility
 */

(function() {
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Fix for Next/Back buttons - ensure they're visible
        setTimeout(fixButtonVisibility, 1000);
        setInterval(fixButtonVisibility, 3000); // Keep checking periodically
        // Create a button that will reveal answers
        const button = document.createElement('button');
        button.innerText = 'Reveal Correct Answers';
        button.style.position = 'fixed';
        button.style.bottom = '20px';
        button.style.right = '20px';
        button.style.padding = '10px 15px';
        button.style.background = '#4CAF50';
        button.style.color = 'white';
        button.style.border = 'none';
        button.style.borderRadius = '4px';
        button.style.zIndex = '999999';
        button.style.cursor = 'pointer';
        button.style.fontWeight = 'bold';
        button.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        
        // Add the button to the page
        document.body.appendChild(button);
        
        // Set up button click handler
        button.addEventListener('click', revealAnswers);
    });
    
    // Function to reveal answers
    function revealAnswers() {
        // Clear any existing overlay
        removeExistingOverlay();
        
        // Create an overlay to show answers
        const overlay = document.createElement('div');
        overlay.className = 'answer-revealer-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '50%';
        overlay.style.left = '50%';
        overlay.style.transform = 'translate(-50%, -50%)';
        overlay.style.background = 'white';
        overlay.style.padding = '20px';
        overlay.style.borderRadius = '8px';
        overlay.style.boxShadow = '0 4px 20px rgba(0,0,0,0.3)';
        overlay.style.zIndex = '9999999';
        overlay.style.maxWidth = '80%';
        overlay.style.maxHeight = '80vh';
        overlay.style.overflow = 'auto';
        overlay.style.direction = 'rtl'; // For Hebrew content
        
        // Add a title
        const title = document.createElement('h2');
        title.innerText = 'Quiz Correct Answers';
        title.style.color = '#4CAF50';
        title.style.marginTop = '0';
        overlay.appendChild(title);
        
        // Extract answers from the quiz
        const questions = document.querySelectorAll('.wpProQuiz_listItem');
        
        if (questions.length === 0) {
            overlay.innerHTML += '<p>No quiz questions found on this page.</p>';
        } else {
            // Process each question
            questions.forEach((question, index) => {
                // Get question text
                const questionText = question.querySelector('.wpProQuiz_question_text');
                const questionTextContent = questionText ? questionText.textContent.trim() : `Question ${index + 1}`;
                
                // Create question container
                const questionContainer = document.createElement('div');
                questionContainer.style.borderBottom = '1px solid #eee';
                questionContainer.style.marginBottom = '15px';
                questionContainer.style.paddingBottom = '15px';
                
                // Add question title
                const questionTitle = document.createElement('h3');
                questionTitle.innerText = `${index + 1}. ${questionTextContent}`;
                questionTitle.style.color = '#2196F3';
                questionTitle.style.margin = '10px 0';
                questionContainer.appendChild(questionTitle);
                
                // Add answers container
                const answersContainer = document.createElement('div');
                const answerItems = question.querySelectorAll('.wpProQuiz_questionListItem');
                
                // This part contains hardcoded answers if we can't detect them
                const hardcodedAnswers = {
                    // Question: מיהו "עובר דרך"?
                    'מיהו "עובר דרך"?': {
                        text: '4. המשתמש בדרך לנסיעה, להליכה, לעמידה או לכל מטרה אחרת',
                        value: '4'
                    },
                    // Add more hardcoded answers if needed
                };
                
                // Check if we have a hardcoded answer for this question
                if (hardcodedAnswers[questionTextContent]) {
                    const answerText = document.createElement('p');
                    answerText.innerHTML = '<strong style="color: #4CAF50; font-size: 16px;">✓ ' + 
                                    hardcodedAnswers[questionTextContent].text + '</strong>';
                    answerText.style.marginLeft = '20px';
                    answersContainer.appendChild(answerText);
                    
                    // Try to mark the correct answer in the UI
                    answerItems.forEach(item => {
                        const input = item.querySelector('input[value="' + hardcodedAnswers[questionTextContent].value + '"]');
                        if (input) {
                            item.style.background = 'rgba(76, 175, 80, 0.1)';
                            item.style.borderLeft = '4px solid #4CAF50';
                        }
                    });
                } else {
                    // No hardcoded answer, try to detect from the UI
                    let foundCorrect = false;
                    
                    answerItems.forEach((item, i) => {
                        // Look for answers with correct class
                        if (item.classList.contains('wpProQuiz_answerCorrect')) {
                            const answerText = document.createElement('p');
                            answerText.innerHTML = '<strong style="color: #4CAF50; font-size: 16px;">✓ ' + 
                                            (i+1) + '. ' + item.textContent.trim() + '</strong>';
                            answerText.style.marginLeft = '20px';
                            answersContainer.appendChild(answerText);
                            foundCorrect = true;
                            
                            // Mark it in the UI
                            item.style.background = 'rgba(76, 175, 80, 0.1)';
                            item.style.borderLeft = '4px solid #4CAF50';
                        }
                    });
                    
                    // If no correct answer found, say so
                    if (!foundCorrect) {
                        const answerText = document.createElement('p');
                        answerText.innerText = 'Could not automatically detect correct answer.';
                        answerText.style.color = 'orange';
                        answerText.style.marginLeft = '20px';
                        answersContainer.appendChild(answerText);
                    }
                }
                
                questionContainer.appendChild(answersContainer);
                overlay.appendChild(questionContainer);
            });
        }
        
        // Add close button
        const closeButton = document.createElement('button');
        closeButton.innerText = 'Close';
        closeButton.style.background = '#f44336';
        closeButton.style.color = 'white';
        closeButton.style.border = 'none';
        closeButton.style.padding = '8px 16px';
        closeButton.style.borderRadius = '4px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.marginTop = '15px';
        
        closeButton.addEventListener('click', function() {
            document.body.removeChild(overlay);
        });
        
        overlay.appendChild(closeButton);
        document.body.appendChild(overlay);
    }
    
    function removeExistingOverlay() {
        const existingOverlay = document.querySelector('.answer-revealer-overlay');
        if (existingOverlay) {
            document.body.removeChild(existingOverlay);
        }
    }
})();
