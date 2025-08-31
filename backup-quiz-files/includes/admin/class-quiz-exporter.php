<?php
/**
 * Class CCR_Quiz_Exporter
 * 
 * Handles comprehensive LearnDash quiz exports with detailed answer data.
 * Creates backups before making significant changes to quiz data.
 */
class CCR_Quiz_Exporter {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into admin actions
        add_action('admin_menu', array($this, 'add_export_page'));
        add_action('admin_post_export_all_quizzes', array($this, 'handle_export_all_quizzes'));
        add_action('admin_post_export_single_quiz', array($this, 'handle_export_single_quiz'));
    }
    
    /**
     * Add export page to admin menu
     */
    public function add_export_page() {
        add_submenu_page(
            'edit.php?post_type=sfwd-quiz',
            __('Quiz Export Tool', 'hello-child'),
            __('Export Quizzes', 'hello-child'),
            'manage_options',
            'quiz-export-tool',
            array($this, 'render_export_page')
        );
    }
    
    /**
     * Render export page
     */
    public function render_export_page() {
        // Get all quizzes for the dropdown
        $quizzes = get_posts([
            'post_type' => 'sfwd-quiz',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('LearnDash Quiz Export Tool', 'hello-child'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Export All Quizzes', 'hello-child'); ?></h2>
                <p><?php echo esc_html__('Create a comprehensive backup of all quizzes, questions, and answers.', 'hello-child'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('export_all_quizzes_nonce', 'export_all_quizzes_nonce'); ?>
                    <input type="hidden" name="action" value="export_all_quizzes">
                    <p>
                        <label>
                            <input type="checkbox" name="include_meta" value="1" checked>
                            <?php echo esc_html__('Include detailed metadata (recommended for full backups)', 'hello-child'); ?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="include_pro_quiz_ids" value="1" checked>
                            <?php echo esc_html__('Include ProQuiz IDs (required for accurate imports)', 'hello-child'); ?>
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Export All Quizzes', 'hello-child'); ?></button>
                    </p>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php echo esc_html__('Export Single Quiz', 'hello-child'); ?></h2>
                <p><?php echo esc_html__('Export a specific quiz with all its questions and answers.', 'hello-child'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('export_single_quiz_nonce', 'export_single_quiz_nonce'); ?>
                    <input type="hidden" name="action" value="export_single_quiz">
                    <p>
                        <label for="quiz_id"><?php echo esc_html__('Select Quiz:', 'hello-child'); ?></label>
                        <select name="quiz_id" id="quiz_id">
                            <?php foreach ($quizzes as $quiz) : ?>
                                <option value="<?php echo esc_attr($quiz->ID); ?>"><?php echo esc_html($quiz->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="include_meta" value="1" checked>
                            <?php echo esc_html__('Include detailed metadata', 'hello-child'); ?>
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Export Selected Quiz', 'hello-child'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle export all quizzes
     */
    public function handle_export_all_quizzes() {
        // Security check
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['export_all_quizzes_nonce'], 'export_all_quizzes_nonce')) {
            wp_die(__('Security check failed', 'hello-child'));
        }
        
        $include_meta = isset($_POST['include_meta']) && $_POST['include_meta'] == '1';
        $include_pro_quiz_ids = isset($_POST['include_pro_quiz_ids']) && $_POST['include_pro_quiz_ids'] == '1';
        
        // Export all quizzes
        $this->export_quizzes('all', $include_meta, $include_pro_quiz_ids);
    }
    
    /**
     * Handle export single quiz
     */
    public function handle_export_single_quiz() {
        // Security check
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['export_single_quiz_nonce'], 'export_single_quiz_nonce')) {
            wp_die(__('Security check failed', 'hello-child'));
        }
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $include_meta = isset($_POST['include_meta']) && $_POST['include_meta'] == '1';
        
        if (!$quiz_id) {
            wp_die(__('No quiz selected', 'hello-child'));
        }
        
        // Export single quiz
        $this->export_quizzes($quiz_id, $include_meta, true);
    }
    
    /**
     * Export quizzes to CSV
     *
     * @param int|string $quiz_id Quiz ID or 'all'
     * @param bool $include_meta Include detailed metadata
     * @param bool $include_pro_quiz_ids Include ProQuiz IDs
     */
    private function export_quizzes($quiz_id, $include_meta = true, $include_pro_quiz_ids = true) {
        global $wpdb;
        
        // Header row
        $header = [
            'quiz_id',
            'quiz_title',
            'quiz_description',
            'question_id',
            'question_text',
            'question_type',
            'answer_text',
            'is_correct',
            'points',
            'category',
            'question_hint'
        ];
        
        // Add ProQuiz IDs to header if requested
        if ($include_pro_quiz_ids) {
            $header[] = 'pro_quiz_id';
            $header[] = 'pro_question_id';
            $header[] = 'pro_answer_id';
        }
        
        // Add extra meta columns if requested
        if ($include_meta) {
            $header[] = 'sort_order';
            $header[] = 'answer_hint';
            $header[] = 'quiz_settings';
        }
        
        $rows = [$header]; // Start with header row
        
        // Query quizzes
        $quiz_args = [
            'post_type' => 'sfwd-quiz',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        
        if ($quiz_id !== 'all') {
            $quiz_args['p'] = intval($quiz_id);
        }
        
        $quizzes = get_posts($quiz_args);
        
        foreach ($quizzes as $quiz) {
            $quiz_title = $quiz->post_title;
            $quiz_description = $quiz->post_content ?: '';
            
            // Get ProQuiz ID
            $pro_quiz_id = get_post_meta($quiz->ID, 'quiz_pro_id', true);
            if (empty($pro_quiz_id)) {
                // Try alternative meta keys
                $alt_keys = ['_quiz_pro', 'quiz_pro', 'quiz_pro_primary_id'];
                foreach ($alt_keys as $key) {
                    $alt_id = get_post_meta($quiz->ID, $key, true);
                    if (!empty($alt_id)) {
                        $pro_quiz_id = $alt_id;
                        break;
                    }
                }
            }
            
            // Get quiz settings if requested
            $quiz_settings = '';
            if ($include_meta) {
                $quiz_meta = get_post_meta($quiz->ID);
                $quiz_settings = json_encode($quiz_meta);
            }
            
            // Get questions from LearnDash
            $question_posts = [];
            $quiz_questions = get_post_meta($quiz->ID, 'quiz_questions', true);
            
            if (is_array($quiz_questions)) {
                foreach ($quiz_questions as $question_data) {
                    if (isset($question_data['id'])) {
                        $question_post = get_post($question_data['id']);
                        if ($question_post) {
                            $question_posts[] = $question_post;
                        }
                    }
                }
            }
            
            // If no questions found via post meta, try direct query
            if (empty($question_posts)) {
                // Find questions that reference this quiz
                $question_posts = get_posts([
                    'post_type' => 'sfwd-question',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'quiz_id',
                            'value' => $quiz->ID,
                            'compare' => '='
                        ]
                    ]
                ]);
            }
            
            // If we have a ProQuiz ID, try finding questions through the ProQuiz system
            if (!empty($pro_quiz_id) && empty($question_posts)) {
                // Try to find the correct table name
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
                
                if (!empty($table_question)) {
                    // Get questions from ProQuiz
                    $pro_questions = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table_question} WHERE quiz_id = %d",
                            $pro_quiz_id
                        )
                    );
                    
                    foreach ($pro_questions as $pro_question) {
                        // For each ProQuiz question, try to find the corresponding LearnDash question
                        $question_posts_from_pro = get_posts([
                            'post_type' => 'sfwd-question',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => 'question_pro_id',
                                    'value' => $pro_question->id,
                                    'compare' => '='
                                ]
                            ]
                        ]);
                        
                        if (!empty($question_posts_from_pro)) {
                            $question_posts = array_merge($question_posts, $question_posts_from_pro);
                        } else {
                            // Create a virtual question if we can't find the LearnDash question
                            $virtual_question = new stdClass();
                            $virtual_question->ID = 0;
                            $virtual_question->post_title = $pro_question->title;
                            $virtual_question->post_content = $pro_question->question;
                            $virtual_question->pro_question_id = $pro_question->id;
                            $virtual_question->is_virtual = true;
                            $question_posts[] = $virtual_question;
                        }
                    }
                }
            }
            
            // Process each question
            foreach ($question_posts as $question) {
                $is_virtual = isset($question->is_virtual) && $question->is_virtual;
                $question_id = $is_virtual ? 0 : $question->ID;
                $question_text = $is_virtual ? $question->post_content : $question->post_content;
                
                // Get question type
                $question_type = '';
                if (!$is_virtual) {
                    $question_type = get_post_meta($question_id, 'question_type', true);
                    if (empty($question_type)) {
                        // Try to determine from answer count
                        $answers = get_post_meta($question_id, 'answers', true);
                        $correct_count = 0;
                        if (is_array($answers)) {
                            foreach ($answers as $answer) {
                                if (isset($answer['correct']) && $answer['correct']) {
                                    $correct_count++;
                                }
                            }
                            
                            if ($correct_count > 1) {
                                $question_type = 'multiple_correct';
                            } else {
                                $question_type = 'multiple_choice';
                            }
                        }
                    }
                }
                
                // Get question meta and ProQuiz ID
                $pro_question_id = 0;
                if (!$is_virtual) {
                    $pro_question_id = get_post_meta($question_id, 'question_pro_id', true);
                } else if (isset($question->pro_question_id)) {
                    $pro_question_id = $question->pro_question_id;
                }
                
                // Get question hint
                $question_hint = '';
                if (!$is_virtual) {
                    $question_hint = get_post_meta($question_id, 'question_hint', true);
                }
                
                // Get category
                $category = '';
                if (!$is_virtual) {
                    $question_terms = wp_get_post_terms($question_id, 'question-category');
                    if (!empty($question_terms) && !is_wp_error($question_terms)) {
                        $category = $question_terms[0]->name;
                    }
                }
                
                // Get answers
                $answers = [];
                if (!$is_virtual) {
                    $answers = get_post_meta($question_id, 'answers', true);
                }
                
                // If we have a ProQuiz question ID, try to get answers from ProQuiz
                if (!empty($pro_question_id) && (empty($answers) || !is_array($answers))) {
                    // Try to find the correct table name for answers
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
                        // Get answers from ProQuiz
                        $pro_answers = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$table_answer} WHERE question_id = %d ORDER BY sort_order ASC",
                                $pro_question_id
                            )
                        );
                        
                        if (!empty($pro_answers)) {
                            $answers = [];
                            foreach ($pro_answers as $pro_answer) {
                                $answers[] = [
                                    'text' => $pro_answer->answer,
                                    'correct' => $pro_answer->correct ? 1 : 0,
                                    'points' => $pro_answer->points,
                                    'sort_order' => $pro_answer->sort_order,
                                    'pro_answer_id' => $pro_answer->id
                                ];
                            }
                        }
                    }
                }
                
                // If we have answers, add a row for each answer
                if (!empty($answers) && is_array($answers)) {
                    foreach ($answers as $index => $answer) {
                        // Skip empty answers
                        if (empty($answer['text'])) {
                            continue;
                        }
                        
                        $row = [
                            $quiz->ID,
                            $quiz_title,
                            $quiz_description,
                            $question_id,
                            $question_text,
                            $question_type,
                            $answer['text'],
                            isset($answer['correct']) ? ($answer['correct'] ? '1' : '0') : '0',
                            isset($answer['points']) ? $answer['points'] : '1',
                            $category,
                            $question_hint
                        ];
                        
                        // Add ProQuiz IDs if requested
                        if ($include_pro_quiz_ids) {
                            $row[] = $pro_quiz_id;
                            $row[] = $pro_question_id;
                            $row[] = isset($answer['pro_answer_id']) ? $answer['pro_answer_id'] : '';
                        }
                        
                        // Add extra meta columns if requested
                        if ($include_meta) {
                            $row[] = isset($answer['sort_order']) ? $answer['sort_order'] : $index;
                            $row[] = isset($answer['answer_hint']) ? $answer['answer_hint'] : '';
                            $row[] = $quiz_settings;
                        }
                        
                        $rows[] = $row;
                    }
                } else {
                    // If no answers, add a row just for the question
                    $row = [
                        $quiz->ID,
                        $quiz_title,
                        $quiz_description,
                        $question_id,
                        $question_text,
                        $question_type,
                        '', // No answer text
                        '', // No correct flag
                        '', // No points
                        $category,
                        $question_hint
                    ];
                    
                    // Add ProQuiz IDs if requested
                    if ($include_pro_quiz_ids) {
                        $row[] = $pro_quiz_id;
                        $row[] = $pro_question_id;
                        $row[] = ''; // No answer ID
                    }
                    
                    // Add extra meta columns if requested
                    if ($include_meta) {
                        $row[] = ''; // No sort order
                        $row[] = ''; // No answer hint
                        $row[] = $quiz_settings;
                    }
                    
                    $rows[] = $row;
                }
            }
            
            // If no questions found, still add a row for the quiz
            if (empty($question_posts)) {
                $row = [
                    $quiz->ID,
                    $quiz_title,
                    $quiz_description,
                    '', // No question ID
                    '', // No question text
                    '', // No question type
                    '', // No answer text
                    '', // No correct flag
                    '', // No points
                    '', // No category
                    '' // No hint
                ];
                
                // Add ProQuiz IDs if requested
                if ($include_pro_quiz_ids) {
                    $row[] = $pro_quiz_id;
                    $row[] = ''; // No question ID
                    $row[] = ''; // No answer ID
                }
                
                // Add extra meta columns if requested
                if ($include_meta) {
                    $row[] = ''; // No sort order
                    $row[] = ''; // No answer hint
                    $row[] = $quiz_settings;
                }
                
                $rows[] = $row;
            }
        }
        
        // Generate CSV
        $csv = '';
        foreach ($rows as $row) {
            $formatted_row = [];
            foreach ($row as $value) {
                // Format value for CSV
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Escape double quotes and enclose in quotes
                $value = str_replace('"', '""', $value);
                $formatted_row[] = '"' . $value . '"';
            }
            
            $csv .= implode(',', $formatted_row) . "\n";
        }
        
        // Generate filename
        $filename = 'quiz-export-' . ($quiz_id === 'all' ? 'all' : $quiz_id) . '-' . date('Y-m-d') . '.csv';
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output CSV
        echo $csv;
        exit;
    }
}

// Initialize the class
new CCR_Quiz_Exporter();
