<?php
/**
 * Quiz Answer Display Shortcode
 *
 * Creates a shortcode that displays LearnDash quiz answers in an admin-only box.
 *
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the quiz answers display shortcode.
 */
function lilac_register_quiz_answer_shortcode() {
    add_shortcode('lilac_quiz_answers', 'lilac_quiz_answer_shortcode');
}
add_action('init', 'lilac_register_quiz_answer_shortcode');

/**
 * Shortcode callback function for displaying quiz answers.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the quiz answers display.
 */
function lilac_quiz_answer_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(
        array(
            'quiz_id' => 0,           // Quiz ID (required)
            'title_text' => 'Quiz Answers Debug',  // Custom title
        ),
        $atts,
        'lilac_quiz_answers'
    );
    
    // Check if user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="lilac-admin-only-notice">Content available to administrators only.</div>';
    }
    
    // Quiz ID is required
    $quiz_id = intval($atts['quiz_id']);
    if (empty($quiz_id)) {
        // Check if we're on a quiz page
        if (is_singular('sfwd-quiz')) {
            $quiz_id = get_the_ID();
        } else {
            return '<div class="lilac-error-message">Error: Quiz ID is required. Use [lilac_quiz_answers quiz_id="123"]</div>';
        }
    }
    
    // Verify this is a valid quiz
    if (get_post_type($quiz_id) !== 'sfwd-quiz') {
        return '<div class="lilac-error-message">Error: Invalid Quiz ID or post is not a LearnDash quiz.</div>';
    }
    
    // Start output buffering
    ob_start();
    
    // Get quiz data
    $quiz_post = get_post($quiz_id);
    $quiz_pro_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
    $quiz_settings = get_post_meta($quiz_id, '_sfwd-quiz', true);
    
    // Get the questions and answers
    global $wpdb;
    
    // This improved query joins with the question_mapping table to get proper relationships
    $questions_query = $wpdb->prepare(
        "SELECT q.id, q.title, q.question_post_id, q.points, q.answer_type, q.sort
         FROM {$wpdb->prefix}learndash_pro_quiz_question q
         WHERE q.quiz_id = %d
         ORDER BY q.sort ASC",
        $quiz_pro_id
    );
    
    $questions = $wpdb->get_results($questions_query);
    ?>
    <div class="lilac-quiz-answers-container">
        <h3 class="lilac-quiz-answers-title"><?php echo esc_html($atts['title_text']); ?> - <?php echo esc_html($quiz_post->post_title); ?></h3>
        
        <div class="lilac-quiz-debug-info">
            <p><strong>Quiz ID:</strong> <?php echo esc_html($quiz_id); ?></p>
            <p><strong>Quiz Pro ID:</strong> <?php echo esc_html($quiz_pro_id); ?></p>
            <p><strong>Questions Found:</strong> <?php echo count($questions); ?></p>
        </div>
        
        <div class="lilac-quiz-answers-content">
            <?php if (empty($questions)): ?>
                <p class="lilac-no-data">No questions found for this quiz.</p>
            <?php else: ?>
                <?php foreach ($questions as $index => $question): 
                    // Get answers for this question
                    $answers_query = $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}learndash_pro_quiz_answer
                         WHERE question_id = %d
                         ORDER BY sort ASC",
                        $question->id
                    );
                    
                    $answers = $wpdb->get_results($answers_query);
                    
                    // Get question post data if available
                    $question_post_data = [];
                    if (!empty($question->question_post_id)) {
                        $question_post = get_post($question->question_post_id);
                        if ($question_post) {
                            $question_post_data = [
                                'title' => $question_post->post_title,
                                'content' => $question_post->post_content,
                                'hint' => get_post_meta($question_post->ID, '_answerMessage', true),
                            ];
                        }
                    }
                    ?>
                    <div class="lilac-question-item">
                        <div class="lilac-question-header">
                            <h4>
                                <span class="lilac-question-number"><?php echo esc_html($index + 1); ?></span>
                                <?php echo wp_kses_post($question->title); ?>
                            </h4>
                            <div class="lilac-question-meta">
                                <span class="lilac-question-type">Type: <?php echo esc_html($question->answer_type); ?></span>
                                <span class="lilac-question-points">Points: <?php echo esc_html($question->points); ?></span>
                                <span class="lilac-question-id">ID: <?php echo esc_html($question->id); ?></span>
                                <?php if (!empty($question->question_post_id)): ?>
                                    <span class="lilac-question-post-id">Post ID: <?php echo esc_html($question->question_post_id); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="lilac-answers-list">
                            <h5>Answers:</h5>
                            <?php if (empty($answers)): ?>
                                <p class="lilac-no-data">No answers found for this question.</p>
                            <?php else: ?>
                                <div class="lilac-answers-table-wrap">
                                    <table class="lilac-answers-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Answer Text</th>
                                                <th>Correct</th>
                                                <th>Points</th>
                                                <th>Sort Order</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($answers as $answer): ?>
                                                <tr class="<?php echo !empty($answer->correct) ? 'lilac-correct-answer' : ''; ?>">
                                                    <td><?php echo esc_html($answer->id); ?></td>
                                                    <td><?php echo !empty($answer->html) ? wp_kses_post($answer->answer) : esc_html($answer->answer); ?></td>
                                                    <td><?php echo !empty($answer->correct) ? '✓ Yes' : '✗ No'; ?></td>
                                                    <td><?php echo esc_html($answer->points); ?></td>
                                                    <td><?php echo esc_html($answer->sort); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($question_post_data) && !empty($question_post_data['hint'])): ?>
                            <div class="lilac-question-hint">
                                <h5>Hint/Explanation:</h5>
                                <div class="lilac-hint-content">
                                    <?php echo wp_kses_post($question_post_data['hint']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .lilac-quiz-answers-container {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            direction: ltr;
            text-align: left;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .lilac-quiz-answers-title {
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            color: #333;
        }
        
        .lilac-quiz-debug-info {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #0073aa;
            font-family: monospace;
        }
        
        .lilac-question-item {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        
        .lilac-question-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .lilac-question-number {
            display: inline-block;
            width: 25px;
            height: 25px;
            line-height: 25px;
            text-align: center;
            background-color: #0073aa;
            color: #fff;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .lilac-question-header h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .lilac-question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .lilac-answers-list {
            margin-bottom: 15px;
        }
        
        .lilac-answers-list h5 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #333;
        }
        
        .lilac-answers-table-wrap {
            overflow-x: auto;
        }
        
        .lilac-answers-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        
        .lilac-answers-table th,
        .lilac-answers-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .lilac-answers-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .lilac-correct-answer {
            background-color: #e8f5e9;
        }
        
        .lilac-question-hint {
            margin-top: 15px;
            padding: 15px;
            background-color: #e3f2fd;
            border-radius: 4px;
        }
        
        .lilac-question-hint h5 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #0277bd;
        }
        
        .lilac-no-data {
            padding: 10px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        
        .lilac-error-message,
        .lilac-admin-only-notice {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
    </style>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
