/**
 * ACF Quiz Hint Integration - Simplified Version
 * 
 * This script injects ACF hints into LearnDash quiz questions.
 * It preserves the hint formatting and applies highlighting to elements
 * with the 'highlight-hint' class.
 */

jQuery(function($) {
    console.log('ACF Quiz Hints: Initializing...');
    
    // Check if other hint scripts are active - avoid duplication
    if (window.acfHintHandlerActive) {
        console.log('ACF Quiz Hints: Another hint handler is already active, skipping initialization');
        
        // Update debug panel if it exists
        if ($('#acf-hint-test').length) {
            $('#acf-hint-test').append(
                '<div style="color: orange;">Multiple hint handlers detected. Using existing handler.</div>'
            );
        }
        return;
    }
    
    // Mark this handler as active
    window.acfHintHandlerActive = true;
    
    // Store processed questions to avoid duplicate processing
    var processedQuestions = {};
    
    // When the standard LearnDash hint button is clicked
    $('body').on('click', '.wpProQuiz_TipButton', function(e) {
        // Find the question item and get its ID
        var $item = $(this).closest('li.wpProQuiz_listItem');
        var questionId = parseInt($item.find('ul.wpProQuiz_questionList').attr('data-question_id'), 10) || 0;
        var $tipp = $item.find('.wpProQuiz_tipp');
        
        // Skip if already processed
        if (processedQuestions[questionId]) {
            return;
        }
        
        console.log('Processing hint for question ID:', questionId);
        
        // Parse question metadata
        var meta = {};
        try {
            var metaRaw = $item.attr('data-question-meta') || '{}';
            meta = JSON.parse(metaRaw);
            console.log('Question metadata:', meta);
        } catch (e) {
            console.warn('Failed to parse question metadata:', e);
        }
        
        // Extract question post ID if available
        var questionPostId = meta.question_post_id || 0;
        
        // Add a close button if needed
        if ($tipp.find('#close-hint').length === 0) {
            $tipp.find('> div').append(
                '<div class="hint-controls">' +
                    '<button id="close-hint" type="button">סגור</button>' +
                '</div>'
            );
        }
        
        // Get the correct AJAX parameters based on what's available
        var ajaxUrl = typeof quizHint !== 'undefined' ? quizHint.ajaxUrl : 
                     (typeof ldvars !== 'undefined' ? ldvars.ajaxurl : ajaxurl);
        
        var nonce = typeof quizHint !== 'undefined' ? quizHint.nonce : 
                   (typeof ldvars !== 'undefined' ? ldvars.nonce : '');
        
        console.log('Using nonce from:', (typeof quizHint !== 'undefined' ? 'quizHint' : 
                                        (typeof ldvars !== 'undefined' ? 'ldvars' : 'none')));
        console.log('AJAX URL:', ajaxUrl);
        
        // Get ACF hint content via AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_acf_hint',
                question_id: questionId,
                question_post_id: questionPostId,
                nonce: nonce
            },
            success: function(response) {
                if (!response || !response.success) {
                    console.error('Failed to get ACF hint:', response);
                    
                    // Update debug panel if it exists
                    if ($('#acf-hint-test').length) {
                        $('#acf-hint-test').append(
                            '<div style="color: red;">Error loading hint for question ID ' + questionId + '</div>'
                        );
                    }
                    return;
                }
                
                // Get the hint content and debug info
                var acfHint = response.data && response.data.hint ? response.data.hint : '';
                var debugInfo = response.data && response.data.debug ? response.data.debug : {};
                
                // Log detailed information about the hint content
                console.log('ACF hint response received:');
                console.log('- Content length: ' + (acfHint ? acfHint.length : 0) + ' characters');
                console.log('- Question ID: ' + questionId + ', Post ID: ' + (debugInfo.question_post_id || questionPostId));
                
                // If we have ACF hint content, use it
                if (acfHint) {
                    // Remove any existing ACF hint content
                    $tipp.find('.acf-hint-content').remove();
                    
                    // Create and insert the hint content with highlight-hint class and hinted ID
                    var $hintContent = $('<div class="acf-hint-content" id="hinted"></div>').html(acfHint);
                    
                    // Insert ACF hint content at the beginning of the hint box
                    $tipp.find('> div').prepend($hintContent);
                    
                    // Create a button container if it doesn't exist
                    if ($tipp.find('.btn-wrapper').length === 0) {
                        $tipp.find('> div').append('<div class="btn-wrapper" style="margin-top:10px;"></div>');
                    }
                    
                    // Add "Mark Hint" button if it doesn't exist
                    if ($tipp.find('#mark-hint').length === 0) {
                        $tipp.find('.btn-wrapper').append('<button id="mark-hint" type="button">סמן רמז</button><br>');
                    }
                    
                    // Add "Close Hint" button if it doesn't exist
                    if ($tipp.find('#close-hint').length === 0) {
                        $tipp.find('.btn-wrapper').append('<button id="close-hint" type="button">סגור רמז</button>');
                    }
                    
                    // Mark this question as processed
                    processedQuestions[questionId] = true;
                    
                    // Update hint count in debug panel if it exists
                    if ($('#acf-hint-test').length) {
                        var hintCount = parseInt($('#acf-hint-test').find('.acf-hint-count').text() || '0', 10);
                        $('#acf-hint-test').find('.acf-hint-count').text(hintCount + 1);
                    }
                    
                    // Add hint content preview to the debug panel
                    if ($('#acf-hint-test').length) {
                        $('#acf-hint-test').append(
                            '<div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 5px;">'+
                                '<strong>Question '+questionId+' Hint:</strong>'+
                                '<div style="max-height: 100px; overflow-y: auto; border: 1px solid #eee; padding: 5px; margin-top: 5px; background: #f9f9f9; font-size: 12px;">'+
                                    acfHint+
                                '</div>'+
                            '</div>'
                        );
                    }
                } else {
                    // If no ACF hint, add a notice but only if debug panel exists
                    if ($('#acf-hint-test').length) {
                        $('#acf-hint-test').append(
                            '<div style="color: orange;">No ACF hint found for question ID ' + questionId + '</div>'
                        );
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error getting hint:', status, error);
                
                // Show error in debug panel if it exists
                if ($('#acf-hint-test').length) {
                    $('#acf-hint-test').append(
                        '<div style="color: red;">AJAX error loading hint for question ID ' + questionId + ': ' + error + '</div>'
                    );
                }
            }
        });
    });
    
    // Handle clicking the mark hint button
    $('body').on('click', '#mark-hint', function() {
        // Try to find a hint in the active question first
        var $activeQuestion = $('.wpProQuiz_listItem[style*="display: block"]');
        var $hintContent = $activeQuestion.find('.acf-hint-content');
        
        // If no hint in active question, try to find it globally
        if (!$hintContent.length) {
            $hintContent = $('#hinted');
        }
        
        // Make sure we have a hint to work with
        if ($hintContent.length) {
            // Always show the hint
            $hintContent.addClass('marked').show();
            
            // Look for spans with highlight-hint class
            var $highlightedText = $hintContent.find('span.highlight-hint');
            if (!$highlightedText.length) {
                // If not found inside the hint, look in the current question
                $highlightedText = $activeQuestion.find('span.highlight-hint');
            }
            
            // Scroll to highlighted text if found, otherwise to the hint content
            if ($highlightedText.length) {
                $('html, body').animate({
                    scrollTop: $highlightedText.offset().top - 100
                }, 500);
            } else {
                $('html, body').animate({
                    scrollTop: $hintContent.offset().top - 100
                }, 500);
            }
            
            // Add visual feedback that the hint is highlighted
            $hintContent.css({
                'transition': 'background-color 0.3s ease',
                'background-color': '#fff9c4'
            });
            setTimeout(function() {
                $hintContent.css('background-color', '');
            }, 1500);
        } else {
            console.log('No hint content found to highlight');
        }
    });
    
    // Handle closing the hint
    $('body').on('click', '#close-hint', function() {
        var $tipp = $(this).closest('.wpProQuiz_tipp');
        if ($tipp.length) {
            $tipp.hide();
        } else {
            // Try to find and hide the hint content if the tipp isn't found
            $('#hinted').hide();
        }
    });
    
    // Add info to the ACF hint test panel
    if ($('#acf-hint-test').length) {
        $('#acf-hint-test').append(
            '<div class="acf-hint-status">ACF Hint Integration: Active</div>' +
            '<div>Hints Loaded: <span class="acf-hint-count">0</span></div>' +
            '<div>LearnDash Quiz Integration: <span style="color:green">✓</span></div>' +
            '<div><button id="check-acf-hint-status" style="margin-top:5px;background:#0073aa;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">Check Hint Status</button>' +
            '<button id="db-search-acf-hints" style="margin-top:5px;margin-left:5px;background:#e74c3c;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">Search DB for Hints</button></div>'
        );
        
        // Add handler for checking hint status
        $(document).on('click', '#check-acf-hint-status', function() {
            // Find all questions on the page
            var questionItems = $('.wpProQuiz_listItem');
            var questionReport = '<div style="margin-top:10px;"><strong>Question Analysis:</strong></div>';
            
            questionItems.each(function(index) {
                var $item = $(this);
                var questionId = parseInt($item.find('ul.wpProQuiz_questionList').attr('data-question_id'), 10) || 0;
                var meta = {};
                try {
                    meta = JSON.parse($item.attr('data-question-meta') || '{}');
                } catch (e) { }
                
                var questionPostId = meta.question_post_id || 0;
                var questionText = $item.find('.wpProQuiz_question_text').text().trim().substring(0, 30) + '...';
                
                questionReport += '<div style="margin:5px 0;padding:5px;border:1px solid #eee;font-size:12px;">' +
                    'Q' + (index + 1) + ': ID=' + questionId + 
                    ', Post ID=' + questionPostId + 
                    '<br><small>' + questionText + '</small>' +
                    '</div>';
            });
            
            $('#acf-hint-test').append(questionReport);
        });
        
        // Add handler for database search
        $(document).on('click', '#db-search-acf-hints', function() {
            var searchStatusDiv = $('<div style="margin-top:10px;color:#3498db;"><strong>Searching database for ACF hints...</strong></div>');
            $('#acf-hint-test').append(searchStatusDiv);
            
            // Get all question IDs on the page
            var questionIds = [];
            var questionPostIds = [];
            
            $('.wpProQuiz_listItem').each(function() {
                var $item = $(this);
                var questionId = parseInt($item.find('ul.wpProQuiz_questionList').attr('data-question_id'), 10) || 0;
                var meta = {};
                try {
                    meta = JSON.parse($item.attr('data-question-meta') || '{}');
                } catch (e) { }
                
                var questionPostId = meta.question_post_id || 0;
                
                if (questionId) {
                    questionIds.push(questionId);
                }
                
                if (questionPostId) {
                    questionPostIds.push(questionPostId);
                }
            });
            
            // Make the AJAX request to search the database
            $.ajax({
                url: typeof quizHint !== 'undefined' ? quizHint.ajaxUrl : 
                     (typeof ldvars !== 'undefined' ? ldvars.ajaxurl : ajaxurl),
                type: 'POST',
                data: {
                    action: 'search_acf_hints_db',
                    question_ids: questionIds,
                    question_post_ids: questionPostIds,
                    nonce: typeof quizHint !== 'undefined' ? quizHint.nonce : 
                           (typeof ldvars !== 'undefined' ? ldvars.nonce : '')
                },
                success: function(response) {
                    searchStatusDiv.html('<strong>Database Search Results:</strong>');
                    
                    if (!response || !response.success) {
                        searchStatusDiv.append('<div style="color:red;">Error: Failed to search database</div>');
                        return;
                    }
                    
                    if (!response.data || !response.data.results) {
                        searchStatusDiv.append('<div>No ACF hints found in database</div>');
                        return;
                    }
                    
                    var results = response.data.results;
                    
                    if (results.length === 0) {
                        searchStatusDiv.append('<div>No ACF hints found in database</div>');
                        return;
                    }
                    
                    // Display results
                    searchStatusDiv.append('<div style="color:green;">' + results.length + ' hint records found!</div>');
                    
                    $.each(results, function(index, item) {
                        searchStatusDiv.append(
                            '<div style="margin:5px 0;padding:5px;border:1px solid #ddd;font-size:12px;max-height:100px;overflow-y:auto;">' +
                                '<div><strong>Post ID:</strong> ' + item.post_id + '</div>' +
                                '<div><strong>Meta Key:</strong> ' + item.meta_key + '</div>' +
                                '<div><strong>Content Preview:</strong> ' + 
                                    (item.meta_value ? item.meta_value.substring(0, 100) + '...' : 'No content') +
                                '</div>' +
                            '</div>'
                        );
                    });
                },
                error: function(xhr, status, error) {
                    searchStatusDiv.html('<strong>Database Search Failed:</strong> ' + error);
                }
            });
        });
    }
});
