<?php
/**
 * Class CCR_Quiz_Answer_Importer
 * 
 * Focused tool for importing answers into existing LearnDash quizzes.
 * Allows rebuilding quiz structure after wiping old data.
 */
class CCR_Quiz_Answer_Importer {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into admin actions
        add_action('admin_menu', array($this, 'add_importer_page'));
        add_action('admin_post_import_quiz_answers', array($this, 'handle_answer_import'));
    }
    
    /**
     * Add importer page to admin menu
     */
    public function add_importer_page() {
        add_submenu_page(
            'edit.php?post_type=sfwd-quiz',
            __('Answer Importer', 'hello-child'),
            __('Import Answers', 'hello-child'),
            'manage_options',
            'quiz-answer-importer',
            array($this, 'render_importer_page')
        );
    }
    
    /**
     * Render importer page
     */
    public function render_importer_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('LearnDash Quiz Answer Importer', 'hello-child'); ?></h1>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php echo esc_html__('Import Answers to Existing Quizzes', 'hello-child'); ?></h2>
                
                <p><?php echo esc_html__('This tool allows you to import answers into existing quizzes. Upload a CSV file with the quiz answers to import.', 'hello-child'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('import_quiz_answers_nonce', 'import_quiz_answers_nonce'); ?>
                    <input type="hidden" name="action" value="import_quiz_answers">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="answer_csv_file"><?php echo esc_html__('CSV File', 'hello-child'); ?></label></th>
                            <td>
                                <input type="file" name="answer_csv_file" id="answer_csv_file" accept=".csv" required>
                                <p class="description"><?php echo esc_html__('Upload a CSV file with answer data.', 'hello-child'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="import_mode"><?php echo esc_html__('Import Mode', 'hello-child'); ?></label></th>
                            <td>
                                <select name="import_mode" id="import_mode">
                                    <option value="add"><?php echo esc_html__('Add to existing questions', 'hello-child'); ?></option>
                                    <option value="replace"><?php echo esc_html__('Replace existing answers', 'hello-child'); ?></option>
                                    <option value="create_missing"><?php echo esc_html__('Create missing questions and answers', 'hello-child'); ?></option>
                                </select>
                                <p class="description"><?php echo esc_html__('Choose how to handle existing content.', 'hello-child'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="match_by"><?php echo esc_html__('Match Questions By', 'hello-child'); ?></label></th>
                            <td>
                                <select name="match_by" id="match_by">
                                    <option value="id"><?php echo esc_html__('Question ID', 'hello-child'); ?></option>
                                    <option value="text"><?php echo esc_html__('Question Text', 'hello-child'); ?></option>
                                    <option value="title"><?php echo esc_html__('Question Title', 'hello-child'); ?></option>
                                </select>
                                <p class="description"><?php echo esc_html__('How to match questions when importing answers.', 'hello-child'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 20px;">
                        <input type="submit" class="button button-primary" value="<?php echo esc_attr__('Import Answers', 'hello-child'); ?>">
                    </div>
                </form>
            </div>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
                <h2><?php echo esc_html__('CSV Format', 'hello-child'); ?></h2>
                <p><?php echo esc_html__('Your CSV should include the following columns:', 'hello-child'); ?></p>
                
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong>quiz_id</strong> - <?php echo esc_html__('The LearnDash quiz ID', 'hello-child'); ?></li>
                    <li><strong>question_id</strong> - <?php echo esc_html__('The LearnDash question ID (optional if matching by text)', 'hello-child'); ?></li>
                    <li><strong>question_text</strong> - <?php echo esc_html__('The text of the question', 'hello-child'); ?></li>
                    <li><strong>answer_text</strong> - <?php echo esc_html__('The text for this answer option', 'hello-child'); ?></li>
                    <li><strong>is_correct</strong> - <?php echo esc_html__('1 for correct answers, 0 for incorrect', 'hello-child'); ?></li>
                    <li><strong>points</strong> - <?php echo esc_html__('Points for this answer (optional)', 'hello-child'); ?></li>
                    <li><strong>sort_order</strong> - <?php echo esc_html__('Display order for this answer (optional)', 'hello-child'); ?></li>
                </ul>
                
                <p><?php echo esc_html__('For a template CSV file, export your existing quizzes using the Quiz Export Tool.', 'hello-child'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle answer import
     */
    public function handle_answer_import() {
        // Security check
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['import_quiz_answers_nonce'], 'import_quiz_answers_nonce')) {
            wp_die(__('Security check failed', 'hello-child'));
        }
        
        // Check for file upload
        if (!isset($_FILES['answer_csv_file']) || empty($_FILES['answer_csv_file']['tmp_name'])) {
            wp_die(__('No file uploaded or the file is empty', 'hello-child'));
        }
        
        // Get import settings
        $import_mode = isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'add';
        $match_by = isset($_POST['match_by']) ? sanitize_text_field($_POST['match_by']) : 'id';
        
        // Process the CSV file
        $result = $this->process_answer_import($_FILES['answer_csv_file']['tmp_name'], $import_mode, $match_by);
        
        // Redirect with status
        $redirect_url = add_query_arg(
            [
                'page' => 'quiz-answer-importer',
                'imported' => $result['success'] ? '1' : '0',
                'message' => urlencode($result['message']),
                'questions_updated' => $result['questions_updated'],
                'answers_added' => $result['answers_added']
            ],
            admin_url('edit.php?post_type=sfwd-quiz')
        );
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Process answer import from CSV
     *
     * @param string $file_path Path to the CSV file
     * @param string $import_mode How to handle existing content
     * @param string $match_by How to match questions
     * @return array Result with status details
     */
    private function process_answer_import($file_path, $import_mode, $match_by) {
        global $wpdb;
        
        // Initialize result
        $result = [
            'success' => false,
            'message' => '',
            'questions_updated' => 0,
            'answers_added' => 0
        ];
        
        // Open CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['message'] = __('Could not open the CSV file', 'hello-child');
            return $result;
        }
        
        // Detect BOM and set encoding
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // No BOM detected, move pointer back to start
            rewind($handle);
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $result['message'] = __('CSV file is empty or incorrectly formatted', 'hello-child');
            return $result;
        }
        
        // Map columns
        $column_map = [];
        $required_columns = ['quiz_id', 'question_text', 'answer_text', 'is_correct'];
        
        // Add question_id to required if matching by ID
        if ($match_by === 'id') {
            $required_columns[] = 'question_id';
        }
        
        foreach ($header as $index => $column_name) {
            $column_map[$column_name] = $index;
        }
        
        // Check for required columns
        $missing_columns = [];
        foreach ($required_columns as $required) {
            if (!isset($column_map[$required])) {
                $missing_columns[] = $required;
            }
        }
        
        if (!empty($missing_columns)) {
            fclose($handle);
            $result['message'] = sprintf(__('Missing required columns: %s', 'hello-child'), implode(', ', $missing_columns));
            return $result;
        }
        
        // Track processed questions and answers
        $processed_questions = [];
        $processed_answers = 0;
        
        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (count($row) < count($required_columns)) {
                continue;
            }
            
            // Get quiz ID
            $quiz_id = $row[$column_map['quiz_id']];
            if (!$quiz_id || !is_numeric($quiz_id)) {
                continue;
            }
            
            // Verify quiz exists
            $quiz = get_post($quiz_id);
            if (!$quiz || $quiz->post_type !== 'sfwd-quiz') {
                continue;
            }
            
            // Get question details
            $question_id = isset($column_map['question_id']) && isset($row[$column_map['question_id']]) ? 
                $row[$column_map['question_id']] : 0;
            
            $question_text = $row[$column_map['question_text']];
            $answer_text = $row[$column_map['answer_text']];
            $is_correct = $row[$column_map['is_correct']] ? 1 : 0;
            
            // Get optional columns
            $points = isset($column_map['points']) && isset($row[$column_map['points']]) ? 
                intval($row[$column_map['points']]) : 1;
            
            $sort_order = isset($column_map['sort_order']) && isset($row[$column_map['sort_order']]) ? 
                intval($row[$column_map['sort_order']]) : 0;
            
            // Find the question based on match_by method
            $matching_question_id = $this->find_matching_question($quiz_id, $question_id, $question_text, $match_by);
            
            // If no matching question and we should create missing questions
            if (!$matching_question_id && $import_mode === 'create_missing') {
                $matching_question_id = $this->create_new_question($quiz_id, $question_text);
            }
            
            // If we have a question, add the answer
            if ($matching_question_id) {
                $question_key = $quiz_id . '_' . $matching_question_id;
                
                // Only count each question once
                if (!isset($processed_questions[$question_key])) {
                    $processed_questions[$question_key] = true;
                    $result['questions_updated']++;
                }
                
                // Add the answer
                $answer_added = $this->add_answer_to_question(
                    $matching_question_id, 
                    $answer_text, 
                    $is_correct, 
                    $points, 
                    $sort_order, 
                    $import_mode
                );
                
                if ($answer_added) {
                    $processed_answers++;
                    $result['answers_added']++;
                }
            }
        }
        
        fclose($handle);
        
        // Set result based on processed data
        if ($result['questions_updated'] > 0) {
            $result['success'] = true;
            $result['message'] = sprintf(
                __('Successfully updated %d questions with %d answers', 'hello-child'),
                $result['questions_updated'],
                $result['answers_added']
            );
        } else {
            $result['message'] = __('No questions were updated. Check your CSV format and import settings.', 'hello-child');
        }
        
        return $result;
    }
    
    /**
     * Find a matching question based on the specified match method
     *
     * @param int $quiz_id Quiz ID
     * @param int $question_id Question ID (when matching by ID)
     * @param string $question_text Question text (when matching by text)
     * @param string $match_by Method to use for matching
     * @return int|false Question ID if found, false otherwise
     */
    private function find_matching_question($quiz_id, $question_id, $question_text, $match_by) {
        switch ($match_by) {
            case 'id':
                // Direct match by ID
                $question = get_post($question_id);
                if ($question && $question->post_type === 'sfwd-question') {
                    // Verify this question is associated with the quiz
                    $question_quiz_id = get_post_meta($question_id, 'quiz_id', true);
                    if ($question_quiz_id == $quiz_id) {
                        return $question_id;
                    }
                }
                break;
                
            case 'text':
                // Match by question content
                $questions = get_posts([
                    'post_type' => 'sfwd-question',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    's' => $question_text,
                    'meta_query' => [
                        [
                            'key' => 'quiz_id',
                            'value' => $quiz_id,
                            'compare' => '='
                        ]
                    ]
                ]);
                
                if (!empty($questions)) {
                    return $questions[0]->ID;
                }
                break;
                
            case 'title':
                // Match by question title
                $questions = get_posts([
                    'post_type' => 'sfwd-question',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'title' => $question_text,
                    'meta_query' => [
                        [
                            'key' => 'quiz_id',
                            'value' => $quiz_id,
                            'compare' => '='
                        ]
                    ]
                ]);
                
                if (!empty($questions)) {
                    return $questions[0]->ID;
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Create a new question for a quiz
     *
     * @param int $quiz_id Quiz ID
     * @param string $question_text Question text
     * @return int|false New question ID or false on failure
     */
    private function create_new_question($quiz_id, $question_text) {
        // Create question post
        $question_id = wp_insert_post([
            'post_title' => wp_trim_words($question_text, 10, '...'),
            'post_content' => $question_text,
            'post_status' => 'publish',
            'post_type' => 'sfwd-question'
        ]);
        
        if (is_wp_error($question_id)) {
            return false;
        }
        
        // Link question to quiz
        update_post_meta($question_id, 'quiz_id', $quiz_id);
        
        // Set default question type
        update_post_meta($question_id, 'question_type', 'multiple_choice');
        
        // Add to quiz questions list
        $this->add_question_to_quiz($quiz_id, $question_id);
        
        return $question_id;
    }
    
    /**
     * Add a question to a quiz's question list
     *
     * @param int $quiz_id Quiz ID
     * @param int $question_id Question ID
     */
    private function add_question_to_quiz($quiz_id, $question_id) {
        // Get existing questions for this quiz
        $questions = get_post_meta($quiz_id, 'quiz_questions', true);
        if (!is_array($questions)) {
            $questions = [];
        }
        
        // Check if question is already in the list
        foreach ($questions as $existing) {
            if (isset($existing['id']) && $existing['id'] == $question_id) {
                return; // Already added
            }
        }
        
        // Add this question
        $questions[] = [
            'id' => $question_id,
            'type' => 'question'
        ];
        
        update_post_meta($quiz_id, 'quiz_questions', $questions);
        
        // Also update the Pro Quiz relationship
        $this->ensure_proquiz_relationship($quiz_id, $question_id);
    }
    
    /**
     * Make sure the LearnDash and ProQuiz relationship is set up properly
     *
     * @param int $quiz_id Quiz ID
     * @param int $question_id Question ID
     */
    private function ensure_proquiz_relationship($quiz_id, $question_id) {
        global $wpdb;
        
        // Get ProQuiz ID for the quiz
        $pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        if (empty($pro_quiz_id)) {
            // Try alternative meta keys
            $alt_keys = ['_quiz_pro', 'quiz_pro', 'quiz_pro_primary_id'];
            foreach ($alt_keys as $key) {
                $alt_id = get_post_meta($quiz_id, $key, true);
                if (!empty($alt_id)) {
                    $pro_quiz_id = $alt_id;
                    update_post_meta($quiz_id, 'quiz_pro_id', $pro_quiz_id); // Save for future use
                    break;
                }
            }
        }
        
        if (empty($pro_quiz_id)) {
            return; // Can't proceed without ProQuiz ID
        }
        
        // Get ProQuiz ID for the question
        $pro_question_id = get_post_meta($question_id, 'question_pro_id', true);
        
        if (empty($pro_question_id)) {
            // Need to create a ProQuiz question
            
            // Find ProQuiz question table
            $possible_tables = [
                $wpdb->prefix . 'wp_pro_quiz_question',
                $wpdb->prefix . 'learndash_pro_quiz_question',
                $wpdb->prefix . 'pro_quiz_question',
                $wpdb->prefix . 'wpproquiz_question'
            ];
            
            $table_question = '';
            foreach ($possible_tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                if ($table_exists) {
                    $table_question = $table;
                    break;
                }
            }
            
            if (empty($table_question)) {
                return; // Can't find question table
            }
            
            // Create ProQuiz question
            $question_post = get_post($question_id);
            $wpdb->insert(
                $table_question,
                [
                    'quiz_id' => $pro_quiz_id,
                    'title' => $question_post->post_title,
                    'question' => $question_post->post_content,
                    'points' => 1,
                    'answer_type' => 'multiple', // Default to multiple choice
                    'answer_points_activated' => 0,
                    'answer_data' => '',
                    'correct_count' => 0,
                    'incorrect_count' => 0,
                    'correct_same_text' => 0,
                    'tip_enabled' => 0,
                    'tip_msg' => '',
                    'answer_html' => 0,
                    'category_id' => 0
                ]
            );
            
            if ($wpdb->insert_id) {
                $pro_question_id = $wpdb->insert_id;
                update_post_meta($question_id, 'question_pro_id', $pro_question_id);
            }
        }
    }
    
    /**
     * Add an answer to a question
     *
     * @param int $question_id Question ID
     * @param string $answer_text Answer text
     * @param int $is_correct Whether this answer is correct
     * @param int $points Points for this answer
     * @param int $sort_order Display order for this answer
     * @param string $import_mode How to handle existing answers
     * @return bool Whether the answer was added successfully
     */
    private function add_answer_to_question($question_id, $answer_text, $is_correct, $points, $sort_order, $import_mode) {
        global $wpdb;
        
        // Get existing answers
        $existing_answers = get_post_meta($question_id, 'answers', true);
        if (!is_array($existing_answers)) {
            $existing_answers = [];
        }
        
        // Check if this answer already exists
        $answer_exists = false;
        foreach ($existing_answers as $index => $existing) {
            if (isset($existing['answer']) && $existing['answer'] === $answer_text) {
                $answer_exists = true;
                
                // Update if replacing
                if ($import_mode === 'replace') {
                    $existing_answers[$index]['correct'] = $is_correct;
                    $existing_answers[$index]['points'] = $points;
                    $existing_answers[$index]['sort_order'] = $sort_order;
                }
                
                break;
            }
        }
        
        // Add new answer if it doesn't exist or we're always adding
        if (!$answer_exists || $import_mode === 'add') {
            $existing_answers[] = [
                'answer' => $answer_text,
                'correct' => $is_correct,
                'points' => $points,
                'sort_order' => $sort_order,
                'html' => 0
            ];
        }
        
        // Update post meta
        update_post_meta($question_id, 'answers', $existing_answers);
        
        // If we have a ProQuiz question ID, update ProQuiz answers too
        $pro_question_id = get_post_meta($question_id, 'question_pro_id', true);
        if ($pro_question_id) {
            // Find ProQuiz answer table
            $possible_tables = [
                $wpdb->prefix . 'wp_pro_quiz_answer',
                $wpdb->prefix . 'learndash_pro_quiz_answer',
                $wpdb->prefix . 'pro_quiz_answer',
                $wpdb->prefix . 'wpproquiz_answer'
            ];
            
            $table_answer = '';
            foreach ($possible_tables as $table) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                if ($table_exists) {
                    $table_answer = $table;
                    break;
                }
            }
            
            if (!empty($table_answer)) {
                // Check if this answer already exists in ProQuiz
                $existing_pro_answer = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$table_answer} WHERE question_id = %d AND answer = %s",
                        $pro_question_id,
                        $answer_text
                    )
                );
                
                if ($existing_pro_answer) {
                    // Update if replacing
                    if ($import_mode === 'replace') {
                        $wpdb->update(
                            $table_answer,
                            [
                                'correct' => $is_correct,
                                'points' => $points,
                                'sort_order' => $sort_order
                            ],
                            [
                                'id' => $existing_pro_answer->id
                            ]
                        );
                    }
                } else {
                    // Add new answer
                    $wpdb->insert(
                        $table_answer,
                        [
                            'question_id' => $pro_question_id,
                            'answer' => $answer_text,
                            'correct' => $is_correct,
                            'points' => $points,
                            'sort_order' => $sort_order,
                            'html' => 0
                        ]
                    );
                }
            }
        }
        
        return true;
    }
}

// Initialize the class
new CCR_Quiz_Answer_Importer();
