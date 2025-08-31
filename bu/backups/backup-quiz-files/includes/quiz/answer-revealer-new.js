/**
 * Simple Quiz Answer Revealer
 * Reveals correct answers and fixes next/back button visibility
 * Version 2.0
 */

(function() {
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📋 Quiz Answer Revealer v2.0 initialized');
        
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
                
                // This part contains hardcoded answers
                const hardcodedAnswers = {
                    // Question 1: מיהו "עובר דרך"?
                    'מיהו "עובר דרך"?': {
                        text: '4. המשתמש בדרך לנסיעה, להליכה, לעמידה או לכל מטרה אחרת',
                        value: '4'
                    },
                    // Question 2: מהם מרכיבי ה"מרחב התעבורתי"?
                    'מהם מרכיבי ה"מרחב התעבורתי"?': {
                        text: '2. הדרך, עוברי הדרך והסביבה',
                        value: '2'
                    },
                    // Question 3: במרחב התעבורתי מתקיימים מפגשים רבים בין עוברי דרך בהם עליהם להתחשב אחד בשני
                    'במרחב התעבורתי מתקיימים מפגשים רבים בין עוברי דרך בהם עליהם להתחשב אחד בשני. נכון או לא נכון?': {
                        text: '1. נכון',
                        value: '1'
                    },
                    // Question 4: הנהיגה היא משימה מורכבת
                    'הנהיגה היא משימה מורכבת. נכון או לא נכון?': {
                        text: '1. נכון',
                        value: '1'
                    },
                    // Question 5: האם תלמיד נהיגה הוא חלק מהמרחב התעבורתי?
                    'האם תלמיד נהיגה הוא חלק מהמרחב התעבורתי?': {
                        text: '1. כן',
                        value: '1'
                    },
                    // Question 6: האם יש סיכונים במרחב התעבורתי?
                    'האם יש סיכונים במרחב התעבורתי?': {
                        text: '1. כן',
                        value: '1'
                    },
                    // Question 7: מפגש בין עובר דרך לסביבה הוא:
                    'מפגש בין עובר דרך לסביבה הוא:': {
                        text: '2. מפגש מורכב הדורש ריכוז',
                        value: '2'
                    },
                    // Question 8: מפגש בין הדרך לעובר דרך הוא:
                    'מפגש בין הדרך לעובר דרך הוא:': {
                        text: '2. מפגש מורכב הדורש התאמה מתמדת',
                        value: '2'
                    },
                    // Question 9: מהו "מרחב תעבורתי"?
                    'מהו "מרחב תעבורתי"?': {
                        text: '1. מרחב הכולל את הדרך, עוברי הדרך והסביבה',
                        value: '1'
                    },
                    // Generic fallback for questions with just "איזו שאלה"
                    'איזו שאלה': {
                        text: '1. תשובה אחת אפשרית',
                        value: '1'
                    }
                };
                
                // Check if we have a hardcoded answer for this question
                let foundInHardcoded = false;
                for (const key in hardcodedAnswers) {
                    if (questionTextContent.includes(key)) {
                        const answerText = document.createElement('p');
                        answerText.innerHTML = '<strong style="color: #4CAF50; font-size: 16px;">✓ ' + 
                                         hardcodedAnswers[key].text + '</strong>';
                        answerText.style.marginLeft = '20px';
                        answersContainer.appendChild(answerText);
                        
                        // Try to mark the correct answer in the UI
                        answerItems.forEach(item => {
                            const input = item.querySelector('input[value="' + hardcodedAnswers[key].value + '"]');
                            if (input) {
                                item.style.background = 'rgba(76, 175, 80, 0.1)';
                                item.style.borderLeft = '4px solid #4CAF50';
                            }
                        });
                        
                        foundInHardcoded = true;
                        break;
                    }
                }
                
                if (!foundInHardcoded) {
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
    
    /**
     * Fix the visibility of the Back and Next buttons
     * This ensures they are always displayed regardless of quiz settings
     */
    function fixButtonVisibility() {
        // Get all Back and Next buttons
        const buttons = document.querySelectorAll('input[name="back"], input[name="next"]');
        
        // Ensure they're visible
        buttons.forEach(button => {
            if (button.style.display === 'none') {
                button.style.display = 'block';
            }
            
            // Remove any inline style that might be hiding the button
            if (button.hasAttribute('style')) {
                const styleAttr = button.getAttribute('style');
                if (styleAttr.includes('display: none') || styleAttr.includes('display:none')) {
                    button.setAttribute('style', styleAttr.replace(/display\s*:\s*none\s*;?/g, ''));
                }
            }
        });
        
        // Also ensure the quiz form itself is working properly
        const forms = document.querySelectorAll('.wpProQuiz_forms');
        forms.forEach(form => {
            if (form.style.display === 'none') {
                form.style.display = 'block';
            }
        });
    }
})();
