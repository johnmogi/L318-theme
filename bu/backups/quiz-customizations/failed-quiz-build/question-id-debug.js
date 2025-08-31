/**
 * Question ID Detection Debugger
 * 
 * This standalone script checks all possible methods for question ID detection
 * and displays the results in the console.
 */

(function() {
    console.log('🔍 Question ID Detection Debugger');
    
    // Run on DOMContentLoaded and after a delay
    document.addEventListener('DOMContentLoaded', detectAllQuestionIds);
    setTimeout(detectAllQuestionIds, 2000);
    
    function detectAllQuestionIds() {
        console.log('🔍 Scanning for question elements...');
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        console.log(`Found ${questionItems.length} question elements`);
        
        questionItems.forEach((questionItem, index) => {
            console.group(`Question #${index + 1}`);
            
            // Method 1: data-post-id attribute
            const postId = questionItem.getAttribute('data-post-id');
            console.log('data-post-id:', postId);
            
            // Method 2: data-question-meta attribute
            const metaAttr = questionItem.getAttribute('data-question-meta');
            if (metaAttr) {
                try {
                    const meta = JSON.parse(metaAttr);
                    console.log('data-question-meta:', {
                        question_post_id: meta.question_post_id,
                        question_pro_id: meta.question_pro_id
                    });
                } catch (e) {
                    console.error('Failed to parse data-question-meta:', e);
                }
            } else {
                console.log('data-question-meta: Not found');
            }
            
            // Method 3: Look for input fields with question ID
            const idsInInputs = [];
            questionItem.querySelectorAll('input').forEach(input => {
                if (input.name && input.name.includes('question_id')) {
                    idsInInputs.push({
                        name: input.name,
                        value: input.value
                    });
                }
            });
            console.log('IDs in input fields:', idsInInputs);
            
            // Method 4: Look at question label
            const questionLabel = questionItem.querySelector('.wpProQuiz_question_text');
            if (questionLabel) {
                console.log('Question text:', questionLabel.textContent.trim().substring(0, 50) + '...');
            }
            
            // Method 5: Check for any data attributes
            const allDataAttrs = {};
            Array.from(questionItem.attributes).forEach(attr => {
                if (attr.name.startsWith('data-')) {
                    allDataAttrs[attr.name] = attr.value;
                }
            });
            console.log('All data attributes:', allDataAttrs);
            
            // Result: Best ID found
            const bestId = getBestQuestionId(questionItem);
            console.log('✅ Best question ID found:', bestId);
            
            console.groupEnd();
        });
    }
    
    function getBestQuestionId(questionItem) {
        // Try data-post-id attribute
        if (questionItem.hasAttribute('data-post-id')) {
            return questionItem.getAttribute('data-post-id');
        }
        
        // Try data-question-meta
        if (questionItem.hasAttribute('data-question-meta')) {
            try {
                const meta = JSON.parse(questionItem.getAttribute('data-question-meta'));
                if (meta.question_post_id) {
                    return meta.question_post_id;
                }
                if (meta.question_pro_id) {
                    return meta.question_pro_id;
                }
            } catch (e) {
                // Silent error
            }
        }
        
        // Look for inputs with question ID
        let inputId = null;
        questionItem.querySelectorAll('input').forEach(input => {
            if (input.name && input.name.includes('question_id')) {
                inputId = input.value;
            }
        });
        if (inputId) {
            return inputId;
        }
        
        // Fall back to question index
        const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
        const index = Array.from(questionItems).indexOf(questionItem);
        return 'q_' + index;
    }
})();
