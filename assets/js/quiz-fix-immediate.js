/**
 * Quiz Fix Immediate 
 * 
 * This script runs immediately to fix the quiz state
 * It uses the most direct approach possible to ensure proper behavior
 */
(function() {
    // Run immediately
    if (document.readyState !== 'loading') {
        fixQuiz();
    } else {
        document.addEventListener('DOMContentLoaded', fixQuiz);
    }

    // Also run again after a delay to catch any dynamic content
    setTimeout(fixQuiz, 500);
    setTimeout(fixQuiz, 1000);
    setTimeout(fixQuiz, 2000);

    function fixQuiz() {
        console.log("[QUIZ-FIX] Running immediate fix");
        
        // STEP 1: Find all incorrect message containers that are visible
        const incorrectElements = document.querySelectorAll('.wpProQuiz_incorrect');
        incorrectElements.forEach(incorrectEl => {
            if (window.getComputedStyle(incorrectEl).display !== 'none') {
                console.log("[QUIZ-FIX] Found visible incorrect message");
                
                // STEP 2: Get the question container
                const questionEl = incorrectEl.closest('.wpProQuiz_listItem');
                if (!questionEl) return;
                
                // STEP 3: Find and HIDE the next button
                const nextButtons = questionEl.querySelectorAll('input[name="next"], .wpProQuiz_button[name="next"]');
                nextButtons.forEach(btn => {
                    console.log("[QUIZ-FIX] Hiding next button");
                    btn.style.display = 'none';
                    btn.style.visibility = 'hidden';
                    // Force with !important
                    btn.setAttribute('style', 'display: none !important; visibility: hidden !important;');
                });
                
                // STEP 4: Find and SHOW the hint button
                const hintButtons = questionEl.querySelectorAll('input[name="tip"], .wpProQuiz_TipButton, .wpProQuiz_button[name="tip"]');
                if (hintButtons.length === 0) {
                    console.log("[QUIZ-FIX] No hint button found!");
                }
                
                hintButtons.forEach(btn => {
                    console.log("[QUIZ-FIX] Showing hint button");
                    btn.style.display = 'inline-block';
                    btn.style.visibility = 'visible';
                    // Highlight it
                    btn.style.backgroundColor = '#ffc107';
                    btn.style.color = '#333';
                    btn.style.fontWeight = 'bold';
                    btn.style.border = '2px solid #e0a800';
                });
                
                // STEP 5: Re-enable all inputs
                const inputs = questionEl.querySelectorAll('.wpProQuiz_questionInput');
                inputs.forEach(input => {
                    console.log("[QUIZ-FIX] Enabling input");
                    input.disabled = false;
                    input.removeAttribute('disabled');
                    input.style.pointerEvents = 'auto';
                    input.style.cursor = 'pointer';
                });
            }
        });
        
        // Add an always-visible button that can re-run the fix when clicked
        if (!document.getElementById('lilac-quiz-fix-button')) {
            const fixButton = document.createElement('button');
            fixButton.id = 'lilac-quiz-fix-button';
            fixButton.textContent = 'תקן תצוגת מבחן';
            fixButton.style.position = 'fixed';
            fixButton.style.bottom = '10px';
            fixButton.style.right = '10px';
            fixButton.style.zIndex = '9999';
            fixButton.style.padding = '5px 10px';
            fixButton.style.backgroundColor = '#007bff';
            fixButton.style.color = 'white';
            fixButton.style.border = 'none';
            fixButton.style.borderRadius = '5px';
            fixButton.style.cursor = 'pointer';
            fixButton.style.fontSize = '14px';
            fixButton.addEventListener('click', fixQuiz);
            document.body.appendChild(fixButton);
        }
    }
})();
