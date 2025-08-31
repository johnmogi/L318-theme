(function($){
    console.log('LearnDash debug script loaded');

    // Click-based hint insertion
    $(document).on('click', '.wpProQuiz_questionInput', function() {
        var $input = $(this);
        var $wrapper = $input.closest('.wpProQuiz');
        // Quiz and question IDs
        var wrapperId = $wrapper.attr('id') || '';
        var parts = wrapperId.split(/[-_]/);
        var quizId = parseInt(parts.pop(), 10) || 0;
        var qid = parseInt( $input.closest('ul.wpProQuiz_questionList').data('question_id'), 10 ) || 0;
        var value = $input.val();
        console.log('Answer clicked:', { quizId: quizId, questionId: qid, value: value });
        // Fetch correct answer via AJAX
        $.post(learndashDebug.ajaxUrl, {
            action: learndashDebug.getAnswerAction,
            question_id: qid,
            quiz_id: quizId,
            nonce: learndashDebug.nonce
        })
        .done(function(response) {
            var $li = $input.closest('li.wpProQuiz_questionListItem');
            // Debug: raw AJAX response
            console.log('AJAX raw response:', response);
            // Display JSON on page for inspection
            var debugHtml = '<pre class="ld-debug-json">' +
                JSON.stringify(response, null, 2) +
                '</pre>';
            $li.find('.ld-debug-json').remove();
            $li.append(debugHtml);
            if (response.success) {
                var correct = response.data.correct;
                // Determine position of correct answer
                var formatted = response.data.formatted_answers || [];
                var idx = -1;
                formatted.some(function(a, i) { if (a.correct) { idx = i; return true; } });
                var pos = idx >= 0 ? (idx + 1) : '';
                var hintText = pos + ' ' + correct;
                console.log('Hint for quiz', quizId, ':', hintText);
                $li.find('.ld-debug-correct-answer').remove();
                $li.append('<div class="ld-debug-correct-answer" data-quiz-id="' + quizId + '">Hint: ' + hintText + '</div>');
            } else {
                console.error('Error fetching correct answer:', response);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown);
        });
    });

    // Highlight hint section when 'סמן רמז' button clicked
    $(document).on('click', '#mark-hint', function() {
        var $tipp = $(this).closest('.wpProQuiz_tipp');
        $tipp.find('.highlight-hint').css('background-color', 'yellow');
    });

    // Remove highlight when 'סגור רמז' button clicked
    $(document).on('click', '#close-hint', function() {
        var $tipp = $(this).closest('.wpProQuiz_tipp');
        $tipp.find('.highlight-hint').css('background-color', '');
    });

    // Page-load flood hints
    $(document).ready(function() {
        $('.wpProQuiz_questionListItem').each(function() {
            var $li = $(this);
            var $wrapper = $li.closest('.wpProQuiz');
            // Quiz and question IDs for flood hints
            var wrapperId = $wrapper.attr('id') || '';
            var parts = wrapperId.split(/[-_]/);
            var quizId = parseInt(parts.pop(), 10) || 0;
            var qid = parseInt( $li.closest('ul.wpProQuiz_questionList').data('question_id'), 10 ) || 0;
            $.post(learndashDebug.ajaxUrl, {
                action: learndashDebug.getAnswerAction,
                question_id: qid,
                quiz_id: quizId,
                nonce: learndashDebug.nonce
            })
            .done(function(response) {
                if (response.success) {
                    var correct = response.data.correct;
                    var formatted = response.data.formatted_answers || [];
                    var idx = -1;
                    formatted.some(function(a, i) { if (a.correct) { idx = i; return true; } });
                    var pos = idx >= 0 ? (idx + 1) : '';
                    var hintText = pos + ' ' + correct;
                    $li.prepend('<div class="ld-debug-correct-answer" data-quiz-id="' + quizId + '">Hint: ' + hintText + '</div>');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
            });
        });
    });

})(jQuery);
