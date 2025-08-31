(function($){
    $(document).on('click', '.ld-show-hint-button', function(){
        var wrapper = $(this).closest('.ld-hint-wrapper');
        var qId = wrapper.data('question-id');
        var hintMsg = wrapper.find('.ld-hint-message');
        var revealBtn = wrapper.find('.ld-reveal-answer-button');

        if (!hintMsg.text()) {
            $.post(LD_HINTS_AJAX.ajax_url, {
                action: 'ld_hints_get_hint',
                question_id: qId,
                nonce: LD_HINTS_AJAX.nonce
            }, function(res){
                if (res.success) {
                    hintMsg.html(res.data.hint).show();
                    revealBtn.show();
                }
            });
        } else {
            hintMsg.toggle();
        }
    });

    $(document).on('click', '.ld-reveal-answer-button', function(){
        var wrapper = $(this).closest('.ld-hint-wrapper');
        var qId = wrapper.data('question-id');
        var ansMsg = wrapper.find('.ld-answer-message');

        if (!ansMsg.text()) {
            $.post(LD_HINTS_AJAX.ajax_url, {
                action: 'ld_hints_reveal_answer',
                question_id: qId,
                nonce: LD_HINTS_AJAX.nonce
            }, function(res){
                if (res.success) {
                    ansMsg.html('<mark>'+res.data.answer+'</mark>').show();
                }
            });
        } else {
            ansMsg.toggle();
        }
    });
})(jQuery);
