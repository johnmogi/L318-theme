<?php
/**
 * Quiz Debug Panel
 * 
 * Creates a visual panel that shows all quiz data including:
 * - Question IDs
 * - Question content
 * - Correct answers
 * - Database tables and query results
 */

// Add the debug panel to the quiz page
add_action('wp_footer', 'lilac_quiz_debug_panel');

function lilac_quiz_debug_panel() {
    if (!is_singular('sfwd-quiz')) {
        return;
    }

    global $post;
    $quiz_id = $post->ID;
    $debug_data = array();
    
    // Add quiz info
    $debug_data['quiz_info'] = array(
        'quiz_id' => $quiz_id,
        'quiz_title' => get_the_title($quiz_id),
        'quiz_meta' => get_post_meta($quiz_id)
    );
    
    // Get all questions for this quiz
    $questions = learndash_get_quiz_questions($quiz_id);
    $debug_data['question_count'] = count($questions);
    $debug_data['questions'] = array();
    
    if (!empty($questions)) {
        foreach ($questions as $index => $question) {
            // Safety check
            if (!is_object($question)) {
                $debug_data['questions'][] = array(
                    'error' => 'Question is not an object at index: ' . $index
                );
                continue;
            }
            
            $question_id = $question->ID;
            $question_meta = get_post_meta($question_id);
            $question_pro_id = isset($question_meta['question_pro_id']) ? $question_meta['question_pro_id'][0] : '';
            $question_type = isset($question_meta['question_type']) ? $question_meta['question_type'][0] : '';
            
            // Get DB information
            global $wpdb;
            $db_info = array();
            
            // Check tables that might exist
            $possible_question_tables = array(
                "{$wpdb->prefix}learndash_pro_quiz_question",
                "{$wpdb->prefix}wp_pro_quiz_question",
                "wp_learndash_pro_quiz_question",
                "wp_wp_pro_quiz_question",
                "learndash_pro_quiz_question"
            );
            
            $possible_answer_tables = array(
                "{$wpdb->prefix}learndash_pro_quiz_answer",
                "{$wpdb->prefix}wp_pro_quiz_answer",
                "wp_learndash_pro_quiz_answer",
                "wp_wp_pro_quiz_answer",
                "learndash_pro_quiz_answer"
            );
            
            foreach ($possible_question_tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                $db_info['tables'][$table] = !empty($table_exists);
            }
            
            // Try to get question data
            $db_info['question_data'] = array();
            foreach ($possible_question_tables as $table) {
                if (!$db_info['tables'][$table]) continue;
                
                $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $question_pro_id);
                $result = $wpdb->get_row($query);
                
                if ($result && !$wpdb->last_error) {
                    $db_info['question_data'][$table] = $result;
                } else {
                    $db_info['question_data'][$table] = array(
                        'error' => $wpdb->last_error,
                        'query' => $query
                    );
                }
            }
            
            // Try to get answer data
            $db_info['answer_data'] = array();
            foreach ($possible_answer_tables as $table) {
                if (!isset($db_info['tables'][$table]) || !$db_info['tables'][$table]) continue;
                
                $query = $wpdb->prepare("SELECT * FROM {$table} WHERE question_id = %d ORDER BY sort ASC", $question_pro_id);
                $results = $wpdb->get_results($query);
                
                if ($results && !$wpdb->last_error) {
                    $db_info['answer_data'][$table] = $results;
                    
                    // Look for correct answers
                    foreach ($results as $index => $answer) {
                        if (isset($answer->correct) && $answer->correct) {
                            $db_info['correct_answer'] = array(
                                'index' => $index,
                                'value' => $index + 1, // LearnDash uses 1-indexed values
                                'text' => isset($answer->answer) ? $answer->answer : 'Unknown'
                            );
                            break;
                        }
                    }
                } else {
                    $db_info['answer_data'][$table] = array(
                        'error' => $wpdb->last_error,
                        'query' => $query
                    );
                }
            }
            
            $debug_data['questions'][] = array(
                'question_id' => $question_id,
                'question_pro_id' => $question_pro_id,
                'question_type' => $question_type,
                'question_title' => get_the_title($question_id),
                'question_content' => $question->post_content,
                'db_info' => $db_info
            );
        }
    }
    
    // Render the debug panel
    ?>
    <style>
    #lilac-quiz-debug {
        position: fixed;
        bottom: 0;
        right: 0;
        width: 500px;
        max-height: 80vh;
        background: #fff;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        z-index: 9999;
        font-family: monospace;
        font-size: 12px;
        overflow-y: auto;
    }
    #lilac-quiz-debug-header {
        background: #f1f1f1;
        padding: 10px;
        border-bottom: 1px solid #ccc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #lilac-quiz-debug-header h3 {
        margin: 0;
        font-size: 14px;
    }
    #lilac-quiz-debug-content {
        padding: 10px;
    }
    .lilac-debug-section {
        margin-bottom: 15px;
    }
    .lilac-debug-section h4 {
        margin: 0 0 5px;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }
    .lilac-debug-question {
        margin-bottom: 10px;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 3px;
    }
    .lilac-debug-correct {
        color: #00a32a;
        font-weight: bold;
    }
    .lilac-debug-error {
        color: #d63638;
    }
    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 200px;
        overflow-y: auto;
        background: #f1f1f1;
        padding: 5px;
        border-radius: 3px;
    }
    </style>
    
    <div id="lilac-quiz-debug">
        <div id="lilac-quiz-debug-header">
            <h3>Lilac Quiz Debug Panel</h3>
            <div>
                <button onclick="document.getElementById('lilac-quiz-debug-content').style.display = document.getElementById('lilac-quiz-debug-content').style.display === 'none' ? 'block' : 'none';">Toggle</button>
                <button onclick="document.getElementById('lilac-quiz-debug').style.display = 'none';">Close</button>
            </div>
        </div>
        <div id="lilac-quiz-debug-content">
            <div class="lilac-debug-section">
                <h4>Quiz Info (ID: <?php echo esc_html($quiz_id); ?>)</h4>
                <div>
                    <strong>Title:</strong> <?php echo esc_html($debug_data['quiz_info']['quiz_title']); ?>
                </div>
                <div>
                    <strong>Questions:</strong> <?php echo esc_html($debug_data['question_count']); ?>
                </div>
            </div>
            
            <div class="lilac-debug-section">
                <h4>Questions</h4>
                <?php if (empty($debug_data['questions'])) : ?>
                    <div class="lilac-debug-error">No questions found!</div>
                <?php else : ?>
                    <?php foreach ($debug_data['questions'] as $question) : ?>
                        <div class="lilac-debug-question">
                            <div>
                                <strong>ID:</strong> <?php echo isset($question['question_id']) ? esc_html($question['question_id']) : 'Unknown'; ?>
                                (Pro ID: <?php echo isset($question['question_pro_id']) ? esc_html($question['question_pro_id']) : 'Unknown'; ?>)
                            </div>
                            <div>
                                <strong>Type:</strong> <?php echo isset($question['question_type']) ? esc_html($question['question_type']) : 'Unknown'; ?>
                            </div>
                            <div>
                                <strong>Title:</strong> <?php echo isset($question['question_title']) ? esc_html($question['question_title']) : 'Unknown'; ?>
                            </div>
                            <div>
                                <strong>Content:</strong> <?php echo isset($question['question_content']) ? esc_html(wp_trim_words($question['question_content'], 20)) : 'Unknown'; ?>
                            </div>
                            
                            <?php if (isset($question['db_info']['correct_answer'])) : ?>
                                <div class="lilac-debug-correct">
                                    <strong>Correct Answer:</strong> 
                                    Value: <?php echo esc_html($question['db_info']['correct_answer']['value']); ?> 
                                    (Text: <?php echo esc_html($question['db_info']['correct_answer']['text']); ?>)
                                </div>
                            <?php else : ?>
                                <div class="lilac-debug-error">
                                    <strong>No correct answer found!</strong>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <details>
                                    <summary>Database Details</summary>
                                    <pre><?php print_r($question['db_info']); ?></pre>
                                </details>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="lilac-debug-section">
                <h4>Database Tables Check</h4>
                <ul>
                    <?php 
                    $tables_checked = array();
                    if (!empty($debug_data['questions'][0]['db_info']['tables'])) {
                        $tables_checked = $debug_data['questions'][0]['db_info']['tables'];
                    }
                    
                    if (empty($tables_checked)) : ?>
                        <li class="lilac-debug-error">No tables checked!</li>
                    <?php else : ?>
                        <?php foreach ($tables_checked as $table => $exists) : ?>
                            <li>
                                <?php echo esc_html($table); ?>: 
                                <?php if ($exists) : ?>
                                    <span style="color: #00a32a;">Exists</span>
                                <?php else : ?>
                                    <span style="color: #d63638;">Not Found</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="lilac-debug-section">
                <h4>JavaScript Answer Map</h4>
                <pre id="lilac-js-answer-map">Loading...</pre>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Create answer map
                        var answerMap = {};
                        <?php foreach ($debug_data['questions'] as $question) : ?>
                            <?php if (isset($question['db_info']['correct_answer'])) : ?>
                                answerMap["<?php echo esc_js($question['question_id']); ?>"] = "<?php echo esc_js($question['db_info']['correct_answer']['value']); ?>";
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        // Display answer map
                        document.getElementById('lilac-js-answer-map').innerText = JSON.stringify(answerMap, null, 2);
                        
                        // Add to window for testing
                        window.lilacDebugAnswers = answerMap;
                        
                        // Log for convenience
                        console.log('[QUIZ DEBUG] *** COMPLETE LIST OF CORRECT ANSWERS ***');
                        console.log(answerMap);
                        console.log('[QUIZ DEBUG] ***************************************');
                        
                        // Basic validation script
                        document.addEventListener('click', function(e) {
                            if (e.target && e.target.name === 'check' && e.target.className.includes('wpProQuiz_button')) {
                                setTimeout(function() {
                                    var questionItem = e.target.closest('.wpProQuiz_listItem');
                                    if (!questionItem) return;
                                    
                                    // Get question ID from meta
                                    var meta = questionItem.getAttribute('data-question-meta');
                                    if (!meta) return;
                                    
                                    try {
                                        var parsed = JSON.parse(meta);
                                        var questionId = parsed.question_post_id;
                                        
                                        // Get the selected and correct answers
                                        var selected = questionItem.querySelector('.wpProQuiz_questionInput:checked');
                                        var userAnswer = selected ? selected.value : null;
                                        var correctAnswer = answerMap[questionId];
                                        
                                        console.log('[QUIZ DEBUG] Question ID:', questionId);
                                        console.log('[QUIZ DEBUG] User selected:', userAnswer);
                                        console.log('[QUIZ DEBUG] Correct answer:', correctAnswer);
                                        console.log('[QUIZ DEBUG] Match?', userAnswer === correctAnswer);
                                        
                                        // Re-enable inputs and hide Next button if incorrect
                                        var nextButton = questionItem.querySelector('.wpProQuiz_button[name="next"]');
                                        var incorrectMsg = questionItem.querySelector('.wpProQuiz_incorrect');
                                        
                                        if (incorrectMsg && window.getComputedStyle(incorrectMsg).display !== 'none') {
                                            // Re-enable inputs
                                            var inputs = questionItem.querySelectorAll('.wpProQuiz_questionInput');
                                            for (var i = 0; i < inputs.length; i++) {
                                                inputs[i].disabled = false;
                                            }
                                            
                                            // Show check button
                                            var checkBtn = questionItem.querySelector('.wpProQuiz_button[name="check"]');
                                            if (checkBtn) {
                                                checkBtn.style.display = 'inline-block';
                                            }
                                            
                                            // Hide next button until correct answer selected
                                            if (nextButton) {
                                                nextButton.style.display = 'none';
                                            }
                                        }
                                    } catch(e) {
                                        console.error('[QUIZ DEBUG] Error:', e);
                                    }
                                }, 100);
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
    <?php
}
