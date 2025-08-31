<?php
/**
 * Quiz Answers Extractor
 * 
 * Extracts correct answers directly from the LearnDash database
 * Add to WordPress admin under LearnDash
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

class Lilac_Quiz_Answers_Extractor {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            'Quiz Answers',
            'Quiz Answers',
            'manage_options',
            'lilac-quiz-answers',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render the admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>LearnDash Quiz Answers Extractor</h1>
            
            <div class="notice notice-info">
                <p>This tool extracts correct answers directly from the database for any LearnDash quiz.</p>
            </div>
            
            <div class="metabox-holder">
                <div class="postbox">
                    <h2 class="hndle">Extract Quiz Answers</h2>
                    <div class="inside">
                        <form method="post" action="">
                            <?php wp_nonce_field('lilac_extract_answers', 'lilac_answers_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="quiz_id">Quiz ID or Title:</label>
                                    </th>
                                    <td>
                                        <input type="text" name="quiz_id" id="quiz_id" class="regular-text" placeholder="Enter Quiz ID or title">
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button('Extract Answers'); ?>
                        </form>
                    </div>
                </div>
                
                <?php
                // Process form submission
                if (isset($_POST['lilac_answers_nonce']) && wp_verify_nonce($_POST['lilac_answers_nonce'], 'lilac_extract_answers')) {
                    $quiz_id = sanitize_text_field($_POST['quiz_id']);
                    $this->display_quiz_answers($quiz_id);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display quiz answers
     */
    public function display_quiz_answers($quiz_id_or_title) {
        global $wpdb;
        
        // Try to get quiz ID if title was provided
        if (!is_numeric($quiz_id_or_title)) {
            $quiz = get_page_by_title($quiz_id_or_title, OBJECT, 'sfwd-quiz');
            if ($quiz) {
                $quiz_id = $quiz->ID;
            } else {
                echo '<div class="notice notice-error"><p>Quiz not found with title: ' . esc_html($quiz_id_or_title) . '</p></div>';
                return;
            }
        } else {
            $quiz_id = intval($quiz_id_or_title);
        }
        
        // Get quiz post
        $quiz_post = get_post($quiz_id);
        if (!$quiz_post || $quiz_post->post_type !== 'sfwd-quiz') {
            echo '<div class="notice notice-error"><p>Invalid Quiz ID: ' . esc_html($quiz_id) . '</p></div>';
            return;
        }
        
        echo '<div class="postbox">';
        echo '<h2 class="hndle">Quiz: ' . esc_html($quiz_post->post_title) . ' (ID: ' . esc_html($quiz_id) . ')</h2>';
        echo '<div class="inside">';
        
        // Get questions associated with this quiz
        $question_ids = learndash_get_quiz_questions($quiz_id);
        
        if (empty($question_ids)) {
            echo '<p>No questions found for this quiz.</p>';
        } else {
            echo '<h3>Questions and Answers:</h3>';
            echo '<div style="background:#f8f8f8; padding:15px; border:1px solid #ddd;">';
            
            foreach ($question_ids as $question_id) {
                $question_post = get_post($question_id);
                if (!$question_post) continue;
                
                echo '<div style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">';
                echo '<h4 style="color:#0073aa;">' . esc_html($question_post->post_title) . ' (ID: ' . esc_html($question_id) . ')</h4>';
                
                // Get the question text
                $question_text = strip_tags($question_post->post_content);
                echo '<p><strong>Question:</strong> ' . esc_html($question_text) . '</p>';
                
                // Get answer data from post meta
                $answer_data = get_post_meta($question_id, 'question_answer_data', true);
                
                if (!empty($answer_data)) {
                    echo '<p><strong>Correct Answers:</strong></p>';
                    echo '<ul style="background:#e7f7e8; padding:10px; border-left:4px solid #46b450;">';
                    
                    // Process answer data based on question type
                    $question_type = get_post_meta($question_id, 'question_type', true);
                    
                    switch ($question_type) {
                        case 'single':
                        case 'multiple':
                            foreach ($answer_data as $index => $answer) {
                                if (isset($answer['correct']) && $answer['correct'] == 1) {
                                    echo '<li style="margin-bottom:5px;"><span style="color:#46b450; font-weight:bold;">✓</span> ' . 
                                         esc_html(wp_strip_all_tags($answer['answer'])) . '</li>';
                                }
                            }
                            break;
                            
                        case 'free_answer':
                            if (isset($answer_data[0]['answer'])) {
                                $answers = explode(',', $answer_data[0]['answer']);
                                foreach ($answers as $answer) {
                                    echo '<li style="margin-bottom:5px;"><span style="color:#46b450; font-weight:bold;">✓</span> ' . 
                                         esc_html(trim($answer)) . '</li>';
                                }
                            }
                            break;
                            
                        case 'assessment_answer':
                            foreach ($answer_data as $index => $answer) {
                                echo '<li style="margin-bottom:5px;"><span style="color:#46b450; font-weight:bold;">✓</span> ' . 
                                     esc_html(wp_strip_all_tags($answer['answer'])) . ' (Points: ' . esc_html($answer['points']) . ')</li>';
                            }
                            break;
                            
                        case 'cloze_answer':
                            if (isset($answer_data[0]['answer'])) {
                                preg_match_all('/\{(.+?)\}/', $answer_data[0]['answer'], $matches);
                                if (!empty($matches[1])) {
                                    foreach ($matches[1] as $match) {
                                        $cloze_answers = explode('|', $match);
                                        echo '<li style="margin-bottom:5px;"><span style="color:#46b450; font-weight:bold;">✓</span> ' . 
                                             esc_html(implode(' or ', $cloze_answers)) . '</li>';
                                    }
                                }
                            }
                            break;
                            
                        default:
                            foreach ($answer_data as $index => $answer) {
                                if (isset($answer['correct']) && $answer['correct'] == 1) {
                                    echo '<li style="margin-bottom:5px;"><span style="color:#46b450; font-weight:bold;">✓</span> ' . 
                                         esc_html(wp_strip_all_tags($answer['answer'])) . '</li>';
                                }
                            }
                    }
                    
                    echo '</ul>';
                    
                    // Show all answers for reference
                    echo '<p><strong>All Answers:</strong></p>';
                    echo '<ol style="background:#f5f5f5; padding:10px;">';
                    foreach ($answer_data as $index => $answer) {
                        $correct = (isset($answer['correct']) && $answer['correct'] == 1) ? 
                                   '<span style="color:#46b450; font-weight:bold;">✓</span> ' : '';
                        echo '<li style="margin-bottom:5px;">' . $correct . 
                             esc_html(wp_strip_all_tags($answer['answer'])) . '</li>';
                    }
                    echo '</ol>';
                } else {
                    echo '<p>No answer data found for this question.</p>';
                }
                
                echo '</div>'; // End question container
            }
            
            echo '</div>'; // End questions container
            
            // Add export functionality
            echo '<div style="margin-top:20px;">';
            echo '<h3>Export Quiz Answers</h3>';
            echo '<button class="button button-primary" onclick="exportQuizAnswers()">Export as JSON</button>';
            echo '<button class="button" style="margin-left:10px;" onclick="copyToClipboard()">Copy to Clipboard</button>';
            
            // Create JSON data for export
            $export_data = array(
                'quiz_id' => $quiz_id,
                'quiz_title' => $quiz_post->post_title,
                'questions' => array()
            );
            
            foreach ($question_ids as $question_id) {
                $question_post = get_post($question_id);
                if (!$question_post) continue;
                
                $answer_data = get_post_meta($question_id, 'question_answer_data', true);
                $correct_answers = array();
                
                if (!empty($answer_data)) {
                    $question_type = get_post_meta($question_id, 'question_type', true);
                    
                    switch ($question_type) {
                        case 'single':
                        case 'multiple':
                            foreach ($answer_data as $index => $answer) {
                                if (isset($answer['correct']) && $answer['correct'] == 1) {
                                    $correct_answers[] = array(
                                        'index' => $index,
                                        'text' => wp_strip_all_tags($answer['answer'])
                                    );
                                }
                            }
                            break;
                            
                        case 'free_answer':
                            if (isset($answer_data[0]['answer'])) {
                                $answers = explode(',', $answer_data[0]['answer']);
                                foreach ($answers as $index => $answer) {
                                    $correct_answers[] = array(
                                        'index' => $index,
                                        'text' => trim($answer)
                                    );
                                }
                            }
                            break;
                            
                        case 'cloze_answer':
                            if (isset($answer_data[0]['answer'])) {
                                preg_match_all('/\{(.+?)\}/', $answer_data[0]['answer'], $matches);
                                if (!empty($matches[1])) {
                                    foreach ($matches[1] as $index => $match) {
                                        $cloze_answers = explode('|', $match);
                                        $correct_answers[] = array(
                                            'index' => $index,
                                            'text' => implode(' or ', $cloze_answers)
                                        );
                                    }
                                }
                            }
                            break;
                            
                        default:
                            foreach ($answer_data as $index => $answer) {
                                if (isset($answer['correct']) && $answer['correct'] == 1) {
                                    $correct_answers[] = array(
                                        'index' => $index,
                                        'text' => wp_strip_all_tags($answer['answer'])
                                    );
                                }
                            }
                    }
                }
                
                $export_data['questions'][] = array(
                    'id' => $question_id,
                    'title' => $question_post->post_title,
                    'text' => strip_tags($question_post->post_content),
                    'type' => get_post_meta($question_id, 'question_type', true),
                    'correct_answers' => $correct_answers
                );
            }
            
            // Add the JSON data to a hidden field
            echo '<textarea id="export-data" style="display:none;">' . esc_textarea(json_encode($export_data, JSON_PRETTY_PRINT)) . '</textarea>';
            
            // JavaScript for export and copy
            ?>
            <script>
                function exportQuizAnswers() {
                    const data = document.getElementById('export-data').value;
                    const blob = new Blob([data], {type: 'application/json'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'quiz_<?php echo esc_js($quiz_id); ?>_answers.json';
                    a.click();
                    URL.revokeObjectURL(url);
                }
                
                function copyToClipboard() {
                    const data = document.getElementById('export-data').value;
                    navigator.clipboard.writeText(data).then(() => {
                        alert('Quiz answers copied to clipboard!');
                    }).catch(err => {
                        console.error('Could not copy text: ', err);
                    });
                }
            </script>
            <?php
            echo '</div>'; // End export container
        }
        
        echo '</div>'; // End .inside
        echo '</div>'; // End .postbox
    }
    
    /**
     * Create a debug-friendly console output for JavaScript
     */
    public function generate_js_debug_code($quiz_id) {
        $question_ids = learndash_get_quiz_questions($quiz_id);
        $quiz_post = get_post($quiz_id);
        
        if (empty($question_ids) || !$quiz_post) {
            return '// No valid quiz data found';
        }
        
        $output = "/**\n";
        $output .= " * Debug data for quiz: {$quiz_post->post_title} (ID: {$quiz_id})\n";
        $output .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= " */\n\n";
        
        $output .= "console.log('%c QUIZ ANSWERS FOR: {$quiz_post->post_title}', 'background:#4CAF50;color:white;padding:5px;border-radius:3px;');\n\n";
        
        foreach ($question_ids as $question_id) {
            $question_post = get_post($question_id);
            if (!$question_post) continue;
            
            $question_text = addslashes(strip_tags($question_post->post_content));
            $output .= "// Question ID: {$question_id} - {$question_post->post_title}\n";
            $output .= "console.log('%c Question: {$question_text}', 'color:#2196F3;font-weight:bold;');\n";
            
            $answer_data = get_post_meta($question_id, 'question_answer_data', true);
            
            if (!empty($answer_data)) {
                $question_type = get_post_meta($question_id, 'question_type', true);
                $output .= "console.log('%c Correct Answers:', 'color:#4CAF50;font-weight:bold;');\n";
                
                switch ($question_type) {
                    case 'single':
                    case 'multiple':
                        foreach ($answer_data as $index => $answer) {
                            if (isset($answer['correct']) && $answer['correct'] == 1) {
                                $answer_text = addslashes(wp_strip_all_tags($answer['answer']));
                                $output .= "console.log(' ✓ " . ($index+1) . ". {$answer_text}');\n";
                            }
                        }
                        break;
                        
                    case 'free_answer':
                        if (isset($answer_data[0]['answer'])) {
                            $answers = explode(',', $answer_data[0]['answer']);
                            foreach ($answers as $index => $answer) {
                                $answer_text = addslashes(trim($answer));
                                $output .= "console.log(' ✓ {$answer_text}');\n";
                            }
                        }
                        break;
                        
                    default:
                        foreach ($answer_data as $index => $answer) {
                            if (isset($answer['correct']) && $answer['correct'] == 1) {
                                $answer_text = addslashes(wp_strip_all_tags($answer['answer']));
                                $output .= "console.log(' ✓ " . ($index+1) . ". {$answer_text}');\n";
                            }
                        }
                }
            } else {
                $output .= "console.log('No answer data found');\n";
            }
            
            $output .= "console.log('----------------------------');\n\n";
        }
        
        return $output;
    }
}

// Initialize the class
$lilac_quiz_answers = new Lilac_Quiz_Answers_Extractor();
