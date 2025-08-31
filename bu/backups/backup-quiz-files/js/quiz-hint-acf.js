/**
 * Quiz Hint ACF Integration
 * Loads custom hint content from ACF fields when hint buttons are clicked
 */
jQuery(document).ready(function($) {
    console.log('ACF Quiz Hint script loaded and ready');
    
    // Listen for the clicks on both the tip button and mark hint button
    $(document).on('click', '.wpProQuiz_hint, #mark-hint', function(e) {
        console.log('ACF hint handler: Hint button clicked');
        
        // Find the question container
        var $questionItem = $(this).closest('.wpProQuiz_listItem');
        var questionId = null;
        var questionPostId = null;
        
        // Get the question meta data which contains the post ID
        var questionMetaRaw = $questionItem.data('question-meta');
        if (questionMetaRaw) {
            try {
                var questionMeta = JSON.parse(questionMetaRaw);
                questionId = questionMeta.question_pro_id;
                questionPostId = questionMeta.question_post_id;
                console.log('ACF hint handler: Question meta found', questionMeta);
            } catch (e) {
                console.error('ACF hint handler: Error parsing question meta:', e);
            }
        }
        
        // If we couldn't get the ID from meta, try other methods
        if (!questionId) {
            questionId = $(this).data('question_id') || $questionItem.find('.wpProQuiz_questionList').data('question_id');
            console.log('ACF hint handler: Fallback question ID:', questionId);
        }
        
        console.log('ACF hint handler: Processing - Question ID:', questionId, 'Post ID:', questionPostId);
        
        // Only proceed if we have a question ID
        if (!questionId && !questionPostId) {
            console.log('ACF hint handler: No question ID or post ID found, skipping');
            return; // Allow default behavior
        }
        
        // AJAX call to get ACF field content
        $.ajax({
            url: ldvars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_acf_hint',
                question_id: questionId,
                question_post_id: questionPostId,
                nonce: ldvars.nonce
            },
            success: function(response) {
                console.log('ACF hint handler: AJAX response:', response);
                
                if (response.success && response.data.hint && response.data.hint.trim() !== '') {
                    console.log('ACF hint handler: ACF hint found');
                    
                    // Find the existing hint container
                    var $hintContainer = $questionItem.find('.wpProQuiz_tipp > div');
                    
                    if ($hintContainer.length > 0) {
                        // Clear existing content and insert ACF hint
                        $hintContainer.html(response.data.hint);
                        $hintContainer.parent().show(); // Make sure the container is visible
                        console.log('ACF hint handler: Inserted ACF hint into existing container');
                        
                        // Prevent default behavior
                        e.stopPropagation();
                        return false;
                    } else {
                        // If no hint container exists yet, create one after question text
                        console.log('ACF hint handler: Creating new hint container');
                        $hintContainer = $('<div class="wpProQuiz_tipp" style="display: block;"><div>' + 
                            response.data.hint + '</div></div>');
                        $questionItem.find('.wpProQuiz_question_text').after($hintContainer);
                    }
                } else {
                    // No ACF hint found, let default behavior proceed
                    console.log('ACF hint handler: No ACF hint content found, allowing default behavior');
                }
            },
            error: function(xhr, status, error) {
                console.error('ACF hint handler: Error fetching ACF hint:', error);
            }
        });
    });
});
