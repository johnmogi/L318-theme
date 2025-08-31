// Combined hint and answer reveal handlers
jQuery(function($){
  // Disable default green styling for correct labels
  $('.wpProQuiz_questionListItem.wpProQuiz_answerCorrectIncomplete label').css({
    'border-color': 'transparent',
    'background': 'none'
  });

  // Hide hint container and wrap highlight spans
  $('.wpProQuiz_tipp').hide();
  // Wrap each hint's text in a highlight span for targeted coloring
  $('.wpProQuiz_tipp p#hinted').wrapInner('<span class="highlight-hint"></span>');

  // Stage 1: Show educational hint only
  $(document).on('click', '.wpProQuiz_TipButton', function(){
    var $item = $(this).closest('li.wpProQuiz_listItem');
    var qid   = parseInt($item.find('ul.wpProQuiz_questionList').attr('data-question_id'), 10) || 0;
    console.log('TipButton clicked. question_id:', qid, 'listItem element:', $item);
    // Debug: show question metadata and text
    var metaRaw = $item.attr('data-question-meta');
    console.log('TipButton question_meta raw:', metaRaw);
    try { console.log('TipButton question_meta parsed:', JSON.parse(metaRaw)); } catch(e) { console.warn('Parsing question_meta failed', e); }
    console.log('TipButton question_text:', $item.find('.wpProQuiz_question_text').text().trim());
    var $tipp = $item.find('.wpProQuiz_tipp').show();
    // Clear previous highlights
    $tipp.find('span.highlight-hint').css('background', '');
    // Highlight just the hint text span
    var $span = $tipp.find('p#hinted .highlight-hint');
    $span.css('background', 'yellow');
    if($span.length){ $span[0].scrollIntoView({behavior:'smooth', block:'center'}); }
    // Debug: fetch correct answer and log
    $.post(quizHint.ajaxUrl, {
      action:      quizHint.getAnswerAction,
      question_id: qid,
      nonce:       quizHint.nonce
    })
    .done(function(response){
      console.log('Hint AJAX response (Stage1):', response);
      console.log('Correct answer (Stage1):', response.data.correct);
    })
    .fail(function(jqXHR, textStatus, error){
      console.error('Hint AJAX error (Stage1):', textStatus, error);
    });
  });

  // Stage 2: Reveal correct answer
  $(document).on('click', '#mark-hint', function(){
    var $item = $(this).closest('li.wpProQuiz_listItem');
    var $tipp = $item.find('.wpProQuiz_tipp');
    $tipp.find('.hint-correct-answer').remove();
    var qid = parseInt($item.find('ul.wpProQuiz_questionList').attr('data-question_id'), 10) || 0;
    console.log('MarkHint clicked. question_id:', qid, 'hint container:', $tipp);
    // Debug: show question metadata and text
    var metaRaw = $item.attr('data-question-meta');
    console.log('MarkHint question_meta raw:', metaRaw);
    try { console.log('MarkHint question_meta parsed:', JSON.parse(metaRaw)); } catch(e) { console.warn('Parsing question_meta failed', e); }
    console.log('MarkHint question_text:', $item.find('.wpProQuiz_question_text').text().trim());
    $.post(quizHint.ajaxUrl, {
      action: quizHint.getAnswerAction,
      question_id: qid,
      nonce: quizHint.nonce
    })
    .done(function(response){
      console.log('Hint AJAX response:', response);
      if(response.success){
        var correct = response.data.correct;
        console.log('Correct answer:', correct);
        // Fallback: use JSON metadata before DOM with expanded scanning
        if(!correct) {
          var metaRaw = $item.attr('data-question-meta');
          try {
            var meta = JSON.parse(metaRaw);
            console.log('Parsed question_meta keys:', Object.keys(meta));
            // original fallback on common keys
            var answers = meta.answerData || meta.answers || [];
            var ansObj = answers.find(function(a){ return a.correct == 1 || a.isCorrect == 1; });
            if(ansObj){
              correct = ansObj.answer || ansObj.answer_text || '';
              console.log('Correct answer from metadata fallback:', correct);
            }
            // Additional scan across all array properties in meta
            Object.keys(meta).forEach(function(key){
              if(!correct && Array.isArray(meta[key])){
                meta[key].forEach(function(obj){
                  if(!correct && (obj.correct == 1 || obj.isCorrect == 1 || obj.is_correct || obj.isCorrectAnswer)){
                    correct = obj.answer || obj.answer_text || obj.text || '';
                    console.log('Correct answer from meta['+key+'] fallback:', correct);
                  }
                });
              }
            });
          } catch(e){
            console.error('Metadata parsing fallback failed:', e);
          }
        }
        // DOM fallback: scan answer list items for any class containing 'answerCorrect'
        if(!correct) {
          var $list = $item.find('ul.wpProQuiz_questionList').first();
          var $correctLi = $list.find('li').filter(function() {
            return /answerCorrect/i.test(this.className);
          }).first();
          if($correctLi.length) {
            correct = $correctLi.find('label').text().trim();
            console.log('Correct answer from DOM fallback regex:', correct);
          }
        }
        // All-choices fallback when still no correct answer
        if(!correct) {
          var allLabels = $item.find('li.wpProQuiz_questionListItem label')
            .map(function(){ return $(this).text().trim(); }).get();
          console.log('All possible answers (DOM):', allLabels);
          // Heuristic: select the longest choice as the correct answer
          var candidate = allLabels.reduce(function(a, b) {
            return a.length >= b.length ? a : b;
          }, '');
          console.log('Correct answer by longest fallback:', candidate);
          correct = candidate;
          var allHtml = '<p>All choices:</p><ul class="hint-all-answers">' +
            allLabels.map(function(a){ return '<li>'+a+'</li>'; }).join('') +
            '</ul>';
          $tipp.children('div').first().prepend(allHtml);
        }
        var answerHtml = '<p><span class="highlight-hint hint-correct-answer active">' + correct + '</span></p>';
        // Mark container and question as hint-revealed
        $tipp.attr('id', 'hint-' + qid);
        $item.attr('data-hinted', 'true').addClass('hinted');
        // Insert correct answer into inner hint window and scroll it into view
        var $inner = $tipp.children('div').first();
        $inner.prepend(answerHtml);
        // Scroll the hint container into view at top of viewport
        $tipp[0].scrollIntoView({behavior:'smooth', block:'start'});
        var $answer = $inner.find('.hint-correct-answer').first();
        if($answer.length) {
          $answer[0].scrollIntoView({behavior: 'smooth', block: 'center'});
        }
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown){
      console.error('Hint AJAX error:', textStatus, errorThrown);
    });
  });

  // Close hint and clear
  $(document).on('click', '#close-hint', function(){
    var $tipp = $(this).closest('.wpProQuiz_tipp');
    $tipp.hide();
    $tipp.find('span.highlight-hint').css('background', '');
    $tipp.find('.hint-correct-answer').remove();
  });
});
