<?php
/**
 * Class CCR_Quiz_Builder
 * 
 * Handles CSV-based quiz creation, import, and export functionality.
 * This class extends the quiz management capabilities of the LearnDash LMS system,
 * allowing administrators and teachers to:
 * 
 * 1. Export existing quizzes to CSV format
 * 2. Import quizzes from CSV files
 * 3. Update existing quizzes with new questions from CSV
 * 4. Create question templates for easy reuse
 * 
 * @since 2025-05-12
 */
class CCR_Quiz_Builder {
    /**
     * CSV columns for quiz import/export
     */
    const CSV_COLUMNS = [
        'quiz_set',
        'quiz_title',
        'quiz_description',
        'question_text',
        'question_type',
        'points',
        'category',
        'question_hint',
        'answer_1',
        'answer_2',
        'answer_3',
        'answer_4',
        'answer_5',
        'answer_6',
        'correct_answer'
    ];
    
    /**
     * Alternate CSV column mappings for compatibility with different formats
     */
    const ALTERNATE_COLUMN_MAPPINGS = [
        // Standard format mapping
        'quiz_set' => ['quiz_set', 'set', 'category_set'],
        'quiz_title' => ['quiz_title', 'title'],
        'quiz_description' => ['quiz_description', 'description'],
        'question_text' => ['question_text', 'question', 'text'],
        'question_type' => ['question_type', 'type'],
        'points' => ['points', 'point', 'score'],
        'category' => ['category', 'question_category'],
        'question_hint' => ['question_hint', 'hint'],
        'correct_answer' => ['correct_answer', 'correct', 'correct_answers']
    ];

    /**
     * Constructor: hooks into admin actions
     */
    public function __construct() {
        // Handle CSV export
        add_action('admin_post_ccr_export_quizzes', [$this, 'handle_quiz_export']);
        
        // Handle CSV import
        add_action('admin_post_ccr_import_quizzes', [$this, 'handle_quiz_import']);
        
        // Provide CSV template download
        add_action('admin_post_ccr_download_quiz_template', [$this, 'download_csv_template']);
        
        // Add JS for tab switching
        add_action('admin_footer', [$this, 'add_tab_switching_script']);
    }

    /**
     * Provide CSV template download
     */
    public function download_csv_template() {
        $filename = 'quiz_template.csv';
        $csv_data = "quiz_set,quiz_title,quiz_description,question_text,question_type,points,category,question_hint,answer_1,answer_2,answer_3,answer_4,answer_5,answer_6,correct_answer\n";
        
        // Sample data for a quiz set called "Driving Theory"
        $csv_data .= "Driving Theory,Basic Rules,Learn basic driving rules,What is the maximum speed limit in urban areas?,multiple_choice,1,Speed Limits,Check local regulations,30 km/h,50 km/h,60 km/h,70 km/h,80 km/h,90 km/h,2\n";
        $csv_data .= "Driving Theory,Basic Rules,Learn basic driving rules,Is it legal to use a mobile phone while driving?,true_false,1,Safety Rules,Think about distractions,True,False,,,,,2\n";
        $csv_data .= "Driving Theory,Basic Rules,Learn basic driving rules,Select all required documents when driving,multiple_correct,1,Documentation,Documents to carry,Driver's License,Insurance Certificate,Vehicle Registration,First Aid Kit,Fire Extinguisher,Car Manual,1,2,3\n";
        
        // Sample data for a second quiz in the same set
        $csv_data .= "Driving Theory,Road Signs,Learn common road signs,What shape are most warning signs?,multiple_choice,1,Road Signs,Think about standard shapes,Circle,Triangle,Rectangle,Diamond,Octagon,Pentagon,2\n";
        $csv_data .= "Driving Theory,Road Signs,Learn common road signs,A red octagonal sign means:,multiple_choice,1,Road Signs,,Stop,Yield,No Entry,One Way,Speed Limit,Caution,1\n";
        
        // Sample data for a different quiz set called "Programming Basics"
        $csv_data .= "Programming Basics,HTML Fundamentals,Learn HTML basics,Is HTML a programming language?,true_false,1,Web Development,Think about what programming means,True,False,,,,,2\n";
        $csv_data .= "Programming Basics,HTML Fundamentals,Learn HTML basics,Which tags are used to create a hyperlink in HTML?,multiple_choice,1,Web Development,,<a>,<link>,<href>,<h>,<url>,<p>,1\n";
        $csv_data .= "Programming Basics,CSS Basics,Learn CSS basics,Select all valid CSS selectors,multiple_correct,1,Web Development,,#id,.class,element,*,@media,/pattern/,1,2,3,4,5\n";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv_data;
        exit;
    }

    /**
     * Handle quiz export to CSV
     */
    public function handle_quiz_export() {
        // Security check
        if (!current_user_can('manage_options') || !check_admin_referer('ccr_quiz_export')) {
            wp_die(__('Unauthorized request', 'hello-child'));
        }

        $quiz_id = isset($_POST['quiz_id']) ? sanitize_text_field($_POST['quiz_id']) : 'all';
        
        // Get quiz data
        $quiz_data = $this->get_quiz_data_for_export($quiz_id);
        
        // Generate CSV
        $csv = $this->generate_csv($quiz_data);
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=quiz-export-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output CSV
        echo $csv;
        exit;
    }

    /**
     * Handle quiz import from CSV
     */
    public function handle_quiz_import() {
        // Security check
        if (!current_user_can('manage_options') || !check_admin_referer('ccr_quiz_import')) {
            wp_die(__('Unauthorized request', 'hello-child'));
        }

        // Check for file upload
        if (!isset($_FILES['quiz_csv_file']) || empty($_FILES['quiz_csv_file']['tmp_name'])) {
            wp_die(__('No file uploaded or the file is empty', 'hello-child'));
        }
        
        // Get import target
        $import_target = isset($_POST['import_target']) ? sanitize_text_field($_POST['import_target']) : 'new';
        $update_quiz_id = isset($_POST['update_quiz_id']) ? intval($_POST['update_quiz_id']) : 0;
        
        // Process the CSV file
        $result = $this->process_csv_import($_FILES['quiz_csv_file']['tmp_name'], $import_target, $update_quiz_id);
        
        // Redirect with status
        $redirect_url = add_query_arg(
            [
                'page' => 'quiz-extensions',
                'tab' => 'builder',
                'imported' => $result['success'] ? '1' : '0',
                'message' => urlencode($result['message'])
            ],
            admin_url('admin.php')
        );
        
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Process CSV data for import
     * 
     * @param string $file_path Path to CSV file
     * @param string $import_target 'new' or 'update'
     * @param int $update_quiz_id Quiz ID to update (if import_target is 'update')
     * @return array Result with success status and message
     */
    private function process_csv_import($file_path, $import_target, $update_quiz_id) {
        // Result array
        $result = [
            'success' => false,
            'message' => '',
            'quiz_id' => 0
        ];
        
        // Open CSV file with UTF-8 encoding for Hebrew characters
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $result['message'] = __('Could not open the CSV file', 'hello-child');
            return $result;
        }
        
        // Set encoding and detect BOM if present
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
        
        // Map columns from different CSV formats to our standard format
        $column_indexes = $this->map_csv_columns($header);
        
        // Validate that we have the minimum required columns to proceed
        if (!isset($column_indexes['quiz_title']) || 
            !isset($column_indexes['question_text']) || 
            !isset($column_indexes['question_type']) || 
            !isset($column_indexes['answer_1']) || 
            !isset($column_indexes['correct_answer'])) {
            
            fclose($handle);
            $missing_columns = [];
            
            foreach (['quiz_title', 'question_text', 'question_type', 'answer_1', 'correct_answer'] as $required) {
                if (!isset($column_indexes[$required])) {
                    $missing_columns[] = $required;
                }
            }
            
            $result['message'] = sprintf(__('Missing required columns: %s', 'hello-child'), implode(', ', $missing_columns));
            return $result;
        }
        
        // Add defaults for missing but optional columns
        if (!isset($column_indexes['quiz_set'])) {
            $column_indexes['quiz_set'] = false; // Will use default set
        }
        
        if (!isset($column_indexes['quiz_description'])) {
            $column_indexes['quiz_description'] = false; // Will use empty description
        }
        
        // Process quiz creation/update based on import target
        if ($import_target === 'update' && $update_quiz_id > 0) {
            // Update existing quiz
            $result = $this->update_existing_quiz($handle, $column_indexes, $update_quiz_id);
        } else {
            // Create new quiz
            $result = $this->create_new_quiz($handle, $column_indexes);
        }
        
        fclose($handle);
        return $result;
    }

    /**
     * Create a new quiz from CSV data
     * 
     * @param resource $handle CSV file handle
     * @param array $column_indexes Column index map
     * @return array Result
     */
    private function create_new_quiz($handle, $column_indexes) {
        // Read all rows to analyze and group by quiz set and title
        $rows = [];
        $quiz_groups = [];
        
        // Skip header row
        fgetcsv($handle);
        
        // First pass: group rows by quiz set and title
        while (($row = fgetcsv($handle)) !== false) {
            $quiz_set = isset($column_indexes['quiz_set']) && isset($row[$column_indexes['quiz_set']]) 
                ? $row[$column_indexes['quiz_set']] 
                : 'Default Set';
                
            $quiz_title = isset($column_indexes['quiz_title']) && isset($row[$column_indexes['quiz_title']]) 
                ? $row[$column_indexes['quiz_title']] 
                : __('Imported Quiz', 'hello-child');
                
            $quiz_description = isset($column_indexes['quiz_description']) && isset($row[$column_indexes['quiz_description']]) 
                ? $row[$column_indexes['quiz_description']] 
                : '';
                
            $quiz_key = $quiz_set . '|' . $quiz_title; // Combined key for grouping
            
            if (!isset($quiz_groups[$quiz_key])) {
                $quiz_groups[$quiz_key] = [
                    'set' => $quiz_set,
                    'title' => $quiz_title,
                    'description' => $quiz_description,
                    'rows' => []
                ];
            }
            
            $quiz_groups[$quiz_key]['rows'][] = $row;
        }
        
        if (empty($quiz_groups)) {
            return [
                'success' => false,
                'message' => __('No valid quiz data found in CSV', 'hello-child')
            ];
        }
        
        // Create quizzes from grouped data
        $created_quizzes = [];
        $total_questions = 0;
        
        foreach ($quiz_groups as $quiz_key => $group) {
            // Create quiz post
            $quiz_id = wp_insert_post([
                'post_title' => $group['title'],
                'post_content' => '',
                'post_excerpt' => $group['description'],
                'post_status' => 'publish',
                'post_type' => 'sfwd-quiz'
            ]);
            
            if (is_wp_error($quiz_id)) {
                continue; // Skip this quiz on error
            }
            
            // Save quiz set information
            update_post_meta($quiz_id, 'quiz_set', $group['set']);
            
            // Setup quiz settings
            $this->setup_learndash_quiz($quiz_id, $group['title']);
            
            // Add questions to quiz
            $questions_added = $this->add_questions_to_quiz($quiz_id, $group['rows'], $column_indexes);
            $total_questions += $questions_added;
            
            $created_quizzes[] = [
                'id' => $quiz_id,
                'title' => $group['title'],
                'set' => $group['set'],
                'questions' => $questions_added
            ];
        }
        
        return [
            'success' => !empty($created_quizzes),
            'message' => sprintf(__('Created %d quizzes with %d total questions', 'hello-child'), 
                              count($created_quizzes), $total_questions),
            'quizzes' => $created_quizzes
        ];
    }

    /**
     * Update an existing quiz with CSV data
     * 
     * @param resource $handle CSV file handle
     * @param array $column_indexes Column index map
     * @param int $quiz_id Quiz ID to update
     * @return array Result
     */
    private function update_existing_quiz($handle, $column_indexes, $quiz_id) {
        $result = [
            'success' => false,
            'message' => '',
            'quiz_id' => $quiz_id
        ];
        
        // Verify quiz exists
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'sfwd-quiz') {
            $result['message'] = __('Quiz not found', 'hello-child');
            return $result;
        }
        
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        
        if (empty($rows)) {
            $result['message'] = __('No questions found in CSV', 'hello-child');
            return $result;
        }
        
        // Add questions to quiz
        $questions_added = $this->add_questions_to_quiz($quiz_id, $rows, $column_indexes);
        
        if ($questions_added) {
            $result['success'] = true;
            $result['message'] = sprintf(__('Quiz "%s" updated with %d new questions', 'hello-child'), 
                                       $quiz->post_title, $questions_added);
        } else {
            $result['message'] = __('Failed to add questions to quiz', 'hello-child');
        }
        
        return $result;
    }

    /**
     * Add questions to a quiz
     * 
     * @param int $quiz_id Quiz ID
     * @param array $rows CSV data rows
     * @param array $column_indexes Column index map
     * @return int Number of questions added
     */
    private function add_questions_to_quiz($quiz_id, $rows, $column_indexes) {
        $questions_added = 0;
        error_log('Starting to add questions to quiz ID: ' . $quiz_id);
        error_log('Total rows in CSV: ' . count($rows));
        
        foreach ($rows as $row_index => $row) {
            error_log('Processing row #' . ($row_index + 1));
            
            // Skip header row if it was included
            if ($row_index === 0 && isset($row[0]) && $row[0] === 'quiz_title') {
                error_log('Skipping header row');
                continue;
            }
            
            $question_data = $this->prepare_question_data($row, $column_indexes);
            
            // Debug output for question data
            error_log('Question text: ' . (isset($question_data['question_text']) ? $question_data['question_text'] : 'EMPTY'));
            error_log('Question type: ' . (isset($question_data['question_type']) ? $question_data['question_type'] : 'EMPTY'));
            error_log('Answers count: ' . (isset($question_data['answers']) ? count($question_data['answers']) : 0));
            
            // Skip if missing required data
            if (empty($question_data['question_text'])) {
                error_log('Skipping row - missing question text');
                continue;
            }
            
            if (empty($question_data['answers'])) {
                error_log('Skipping row - missing answers');
                continue;
            }
            
            // Create question
            error_log('Creating question: ' . $question_data['question_text']);
            $question_id = $this->create_learndash_question($quiz_id, $question_data);
            
            if ($question_id) {
                error_log('Successfully created question with ID: ' . $question_id);
                $questions_added++;
            } else {
                error_log('Failed to create question');
            }
        }
        
        error_log('Total questions added: ' . $questions_added . ' out of ' . count($rows) . ' rows');
        return $questions_added;
    }

    /**
     * Prepare question data from CSV row
     * 
     * @param array $row CSV data row
     * @param array $column_indexes Column index map
     * @return array Question data
     */
    /**
     * Map CSV columns from various formats to standard format
     *
     * @param array $header CSV header row
     * @return array Column index map
     */
    private function map_csv_columns($header) {
        $column_indexes = [];
        
        // First try direct matches
        foreach (self::CSV_COLUMNS as $column) {
            $index = array_search($column, $header);
            if ($index !== false) {
                $column_indexes[$column] = $index;
            }
        }
        
        // Then try alternate column names
        foreach (self::ALTERNATE_COLUMN_MAPPINGS as $standard_col => $alternate_cols) {
            if (!isset($column_indexes[$standard_col])) {
                foreach ($alternate_cols as $alt_col) {
                    $index = array_search($alt_col, $header);
                    if ($index !== false) {
                        $column_indexes[$standard_col] = $index;
                        break;
                    }
                }
            }
        }
        
        return $column_indexes;
    }
    
    /**
     * Ensure string is properly encoded in UTF-8
     * 
     * @param string $text Text to convert
     * @return string UTF-8 encoded text
     */
    private function ensure_utf8($text) {
        if (!is_string($text)) {
            return $text;
        }
        
        // If text is not UTF-8, try to convert it
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try Windows-1255 (Hebrew), ISO-8859-8, or CP1255 encodings
            $possible_encodings = ['Windows-1255', 'ISO-8859-8', 'CP1255'];
            
            foreach ($possible_encodings as $encoding) {
                $converted = @iconv($encoding, 'UTF-8//IGNORE', $text);
                if ($converted !== false) {
                    return $converted;
                }
            }
            
            // If all fails, try to force UTF-8
            return utf8_encode($text);
        }
        
        return $text;
    }
    
    private function prepare_question_data($row, $column_indexes) {
        // Debug log for troubleshooting
        error_log('Processing row: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
        error_log('Column indexes: ' . json_encode($column_indexes, JSON_UNESCAPED_UNICODE));
        
        // Safely get values with proper encoding handling and conversion for Hebrew
        $question_text = isset($column_indexes['question_text']) && isset($row[$column_indexes['question_text']]) 
            ? $this->ensure_utf8($row[$column_indexes['question_text']]) : '';
        $raw_question_type = isset($column_indexes['question_type']) && isset($row[$column_indexes['question_type']]) 
            ? strtolower(trim($this->ensure_utf8($row[$column_indexes['question_type']]))) : 'multiple_choice';
            
        // Map CSV question types to LearnDash question types
        $type_mapping = [
            'single' => 'multiple_choice',
            'multiple' => 'multiple_correct',
            'essay' => 'essay',
            'free' => 'free_answer',
            'assessment' => 'assessment_answer',
            'matrix' => 'matrix_sort_answer',
            'fill' => 'fill_in_the_blank',
            'true_false' => 'true_false',
            'sort' => 'sort_answer',
        ];
        
        // Find the matching type or default to multiple_choice
        $question_type = 'multiple_choice'; // Default
        foreach ($type_mapping as $csv_type => $ld_type) {
            if (strpos($raw_question_type, $csv_type) !== false) {
                $question_type = $ld_type;
                break;
            }
        }
        
        error_log("Raw question type: {$raw_question_type}, Mapped to: {$question_type}");
        $points = isset($column_indexes['points']) && isset($row[$column_indexes['points']]) 
            ? intval($row[$column_indexes['points']]) : 1;
        $question_hint = isset($column_indexes['question_hint']) && isset($row[$column_indexes['question_hint']]) 
            ? $this->ensure_utf8($row[$column_indexes['question_hint']]) : '';
        $category = isset($column_indexes['category']) && isset($row[$column_indexes['category']]) 
            ? $this->ensure_utf8($row[$column_indexes['category']]) : '';
        
        $question_data = [
            'question_text' => $question_text,
            'question_type' => $question_type,
            'answers' => [],
            'correct_answers' => [],
            'points' => $points,
            'question_hint' => $question_hint,
            'category' => $category
        ];
        
        error_log('Question data (initial): ' . json_encode($question_data, JSON_UNESCAPED_UNICODE));
        
        // Get correct answer indexes
        $correct_answers = isset($column_indexes['correct_answer']) && isset($row[$column_indexes['correct_answer']]) 
            ? $row[$column_indexes['correct_answer']] : '';
            
        error_log('Column indexes: ' . json_encode($column_indexes));
        if (isset($column_indexes['correct_answer'])) {
            error_log('Correct answer column index: ' . $column_indexes['correct_answer']);
            error_log('Row value at this index: ' . (isset($row[$column_indexes['correct_answer']]) ? $row[$column_indexes['correct_answer']] : 'NOT FOUND'));
        } else {
            error_log('No correct_answer column found in CSV');
        }
            
        // Support both comma and semicolon separators
        $correct_answers = str_replace(';', ',', $correct_answers); // Convert semicolons to commas
        $correct_indexes = array_map('trim', explode(',', $correct_answers));
        
        error_log('Correct answer string: ' . $correct_answers);
        error_log('Parsed correct indexes: ' . json_encode($correct_indexes));
        
        // Prepare answers with proper UTF-8 encoding for Hebrew
        for ($i = 1; $i <= 6; $i++) {
            $answer_key = 'answer_' . $i;
            if (isset($column_indexes[$answer_key]) && isset($row[$column_indexes[$answer_key]]) && !empty($row[$column_indexes[$answer_key]])) {
                $answer_text = $this->ensure_utf8($row[$column_indexes[$answer_key]]);
                $is_correct = in_array((string)$i, $correct_indexes);
                
                // Debug log for answer processing
                error_log("Processing answer {$i}: {$answer_text}, correct: " . ($is_correct ? 'yes' : 'no'));
                
                // Only add non-empty answers
                if (!empty(trim($answer_text))) {
                    $question_data['answers'][] = [
                        'text' => $answer_text,
                        'correct' => $is_correct
                    ];
                    
                    if ($is_correct) {
                        $question_data['correct_answers'][] = $answer_text;
                    }
                }
            }
        }
        
        // Log final question data
        error_log('Final question data: ' . json_encode($question_data, JSON_UNESCAPED_UNICODE));
        error_log('Answer count: ' . count($question_data['answers']));
        error_log('-------------------------------------');
        
        return $question_data;
    }

    /**
     * Setup LearnDash quiz settings
     * 
     * @param int $quiz_id Quiz post ID
     * @param string $quiz_title Quiz title
     * @return int ProQuiz ID
     */
    /**
     * Ensure all required quiz settings are properly set to avoid the "Missing ProQuiz Associated Settings" error
     * 
     * @param int $quiz_id The LearnDash quiz post ID
     * @param int $pro_quiz_id The ProQuiz ID
     */
    private function ensure_complete_quiz_settings($quiz_id, $pro_quiz_id) {
        global $wpdb;
        
        // Debug log
        error_log('Ensuring complete quiz settings for quiz: ' . $quiz_id . ' with ProQuiz ID: ' . $pro_quiz_id);
        
        // Update essential quiz post meta
        update_post_meta($quiz_id, 'quiz_pro_id', $pro_quiz_id);
        update_post_meta($quiz_id, 'quiz_pro_id_' . $pro_quiz_id, $pro_quiz_id);
        
        // Set critical LearnDash quiz settings
        $quiz_settings = [
            'quiz_pro' => $pro_quiz_id,
            'course' => 0,
            'lesson' => 0,
            'topic' => 0,
            'threshold' => '80',
            'passingpercentage' => '80',
            'quiz_materials_enabled' => '',
            'quiz_materials' => '',
            'certificate' => '',
            'quiz_time_limit_enabled' => '',
            'timeLimit' => 0,
            'custom_sorting' => '',
            'autostart' => '',
            'showReviewQuestion' => 'on',
            'quizSummaryHide' => '',
            'skipQuestionDisabled' => 'on',
            'sortCategories' => '',
            'question_display' => '',
            'displayMaxQuestion' => '',
            'maxShowQuestion' => 0,
            'formActivated' => '',
            'formShowPosition' => 0,
            'btn_restart_quiz_hidden' => '',
            'quiz_resume_enabled' => '',
            'quiz_reset_cookies' => '',
            'repeats' => '',
            'retry_restrictions' => '',
            'repeats_num' => '',
            'quiz_points_enabled' => 'on',
            'custom_question_points' => '',
            'retry_specific_points' => '',
            'retry_points' => '',
            'quiz_disable_autocomplete' => '',
            'advanced_settings' => ''
        ];
        
        // Apply each setting individually
        foreach ($quiz_settings as $key => $value) {
            update_post_meta($quiz_id, '_' . $key, $value);
        }
        
        // Set LearnDash quiz complete options
        if (function_exists('learndash_set_quiz_complete_options')) {
            // Get the pro_quiz_id if it's not already provided
            if (empty($pro_quiz_id)) {
                $pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
            }
            
            if (!empty($pro_quiz_id)) {
                learndash_set_quiz_complete_options($quiz_id, $pro_quiz_id);
            } else {
                error_log('Warning: Cannot set quiz complete options - missing pro_quiz_id for quiz ' . $quiz_id);
            }
        }
    }
    
    private function setup_learndash_quiz($quiz_id, $quiz_title) {
        global $wpdb;
        
        // Check if quiz already has a ProQuiz ID
        $existing_pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        if (!empty($existing_pro_quiz_id)) {
            // Even if ProQuiz ID exists, make sure settings are complete
            $this->ensure_complete_quiz_settings($quiz_id, $existing_pro_quiz_id);
            return $existing_pro_quiz_id; // Return existing ID if found
        }
        
        // Debug log
        error_log('Creating new ProQuiz entry for quiz: ' . $quiz_id);
        
        // Get the correct table prefix
        $table_prefix = $wpdb->prefix; // This should return 'edc_'
        error_log('Using table prefix: ' . $table_prefix);
        
        // For debugging, output all available tables
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $table_list = array();
        foreach ($tables as $table) {
            $table_list[] = $table[0];
        }
        error_log('Available tables: ' . json_encode($table_list));
        
        // Check if wp_pro_quiz_master and wp_pro_quiz_question tables exist
        $table_master = $table_prefix . 'wp_pro_quiz_master';
        $table_question = $table_prefix . 'wp_pro_quiz_question';
        
        $exists_master = $wpdb->get_var("SHOW TABLES LIKE '{$table_master}'");
        $exists_question = $wpdb->get_var("SHOW TABLES LIKE '{$table_question}'");
        
        error_log("Table {$table_master} exists: " . ($exists_master ? 'yes' : 'no'));
        error_log("Table {$table_question} exists: " . ($exists_question ? 'yes' : 'no'));
        
        // Alternative table names to try if the main ones don't exist
        if (!$exists_master) {
            $alt_tables = [
                $table_prefix . 'pro_quiz_master',
                $table_prefix . 'learndash_pro_quiz_master',
                $table_prefix . 'wpproquiz_master'
            ];
            
            foreach ($alt_tables as $alt_table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'");
                if ($exists) {
                    $table_master = $alt_table;
                    error_log("Found alternative table: {$table_master}");
                    break;
                }
            }
        }
        
        // First, create the wp_pro_quiz entry
        error_log('Inserting into table: ' . $table_master);
        
        $wpdb->insert(
            $table_master,
            [
                'name' => $quiz_title,
                'text' => $quiz_title,
                'result_text' => 'Congratulations! You have completed the quiz successfully.',
                'btn_restart_quiz_text' => 'Restart Quiz',
                'btn_view_question_text' => 'Review Questions',
                'question_random' => 0,
                'answer_random' => 0,
                'time_limit' => 0,
                'statistics_on' => 1,
                'statistics_ip_lock' => 0,
                'show_points' => 1,
                'quiz_run_once' => 0,
                'quiz_run_once_type' => 0,
                'quiz_run_once_cookie' => 0,
                'quiz_run_once_time' => 0,
                'numbered_answer' => 0,
                'hide_answer_message_box' => 0,
                'disabled_answer_mark' => 0,
                'show_max_question' => 0,
                'show_max_question_value' => 0,
                'preview_mode' => 0,
                'question_on_single_page' => 0,
                'sort_categories' => 0,
                'auto_start' => 0,
                'form_activated' => 0,
                'form_show_position' => 0,
                'start_only_registered_user' => 0,
                'questions_per_page' => 0,
                'ld_quiz_id' => $quiz_id
            ]
        );
        
        // Get the ProQuiz ID
        $pro_quiz_id = $wpdb->insert_id;
        
        // Set quiz post content
        wp_update_post([
            'ID' => $quiz_id,
            'post_content' => '[ld_quiz quiz_id="' . $quiz_id . '"]'
        ]);
        
        // Use our comprehensive settings function to set up all required quiz settings
        // This will prevent the "Missing ProQuiz Associated Settings" error
        $this->ensure_complete_quiz_settings($quiz_id, $pro_quiz_id);
        
        error_log('Successfully set up all required quiz settings for quiz ID: ' . $quiz_id);
        
        return $pro_quiz_id;
    }

    /**
     * Create a LearnDash question
     * 
     * @param int $quiz_id Quiz ID
     * @param array $question_data Question data
     * @return int|false Question ID or false on failure
     */
    private function create_learndash_question($quiz_id, $question_data) {
        global $wpdb;
        
        error_log('Creating question for quiz_id: ' . $quiz_id);
        error_log('Question data: ' . json_encode($question_data, JSON_UNESCAPED_UNICODE));
        
        // Get ProQuiz ID
        $pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        
        // Check all quiz meta for debugging
        $all_quiz_meta = get_post_meta($quiz_id);
        error_log('All quiz meta: ' . json_encode(array_keys($all_quiz_meta)));
        
        if (empty($pro_quiz_id)) {
            error_log('Error: Cannot create question - Missing ProQuiz ID for quiz ' . $quiz_id);
            // Try to get ProQuiz ID through alternative meta keys
            $alt_keys = ['_quiz_pro', 'quiz_pro', 'quiz_pro_primary_id'];
            foreach ($alt_keys as $key) {
                $alt_id = get_post_meta($quiz_id, $key, true);
                if (!empty($alt_id)) {
                    error_log('Found alternative ProQuiz ID through key ' . $key . ': ' . $alt_id);
                    $pro_quiz_id = $alt_id;
                    // Save this as the standard key for future use
                    update_post_meta($quiz_id, 'quiz_pro_id', $pro_quiz_id);
                    break;
                }
            }
            
            if (empty($pro_quiz_id)) {
                return false; // Still can't proceed without ProQuiz ID
            }
        }
        
        error_log('Found ProQuiz ID: ' . $pro_quiz_id . ' for quiz ' . $quiz_id);
        
        // Create question post
        $question_id = wp_insert_post([
            'post_title' => wp_trim_words($question_data['question_text'], 10, '...'),
            'post_content' => $question_data['question_text'],
            'post_status' => 'publish',
            'post_type' => 'sfwd-question'
        ]);
        
        if (is_wp_error($question_id)) {
            return false;
        }
        
        // Get the correct table prefix
        $table_prefix = $wpdb->prefix; // This should return 'edc_'
        
        // Check if wp_pro_quiz_question table exists
        $table_question = $table_prefix . 'wp_pro_quiz_question';
        
        $exists_question = $wpdb->get_var("SHOW TABLES LIKE '{$table_question}'");
        error_log("Table {$table_question} exists: " . ($exists_question ? 'yes' : 'no'));
        
        // Alternative table names to try if the main one doesn't exist
        if (!$exists_question) {
            $alt_tables = [
                $table_prefix . 'pro_quiz_question',
                $table_prefix . 'learndash_pro_quiz_question',
                $table_prefix . 'wpproquiz_question'
            ];
            
            foreach ($alt_tables as $alt_table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'");
                if ($exists) {
                    $table_question = $alt_table;
                    error_log("Found alternative question table: {$table_question}");
                    break;
                }
            }
        }
        
        error_log("Inserting question into table: {$table_question}");
        
        // Insert quiz question in ProQuiz
        $result = $wpdb->insert(
            $table_question,
            [
                'quiz_id' => $pro_quiz_id,
                'title' => wp_trim_words($question_data['question_text'], 10, '...'),
                'question' => $question_data['question_text'],
                'correct_count' => count($question_data['correct_answers']),
                'incorrect_count' => count($question_data['answers']) - count($question_data['correct_answers']),
                'answer_type' => $this->get_pro_quiz_answer_type($question_data['question_type']),
                'points' => $question_data['points'],
                'answer_points_activated' => 0,
                'answer_data' => '',
                'category_id' => 0, // No category by default
                'tips_enabled' => !empty($question_data['question_hint']) ? 1 : 0,
                'tips' => $question_data['question_hint']
            ]
        );
        
        if ($result === false) {
            error_log("Error inserting question into ProQuiz table: " . $wpdb->last_error);
            return false;
        }
        
        error_log("Successfully inserted question into ProQuiz table");
        
        // Get the ProQuiz question ID
        $pro_question_id = $wpdb->insert_id;
        
        // Set question meta
        update_post_meta($question_id, 'question_type', $question_data['question_type']);
        update_post_meta($question_id, 'points', $question_data['points']);
        update_post_meta($question_id, 'question_pro_id', $pro_question_id);
        
        // Save category if provided
        if (!empty($question_data['category'])) {
            update_post_meta($question_id, 'question_category', $question_data['category']);
        }
        
        // Save hint if provided
        if (!empty($question_data['question_hint'])) {
            update_post_meta($question_id, 'question_hint', $question_data['question_hint']);
        }
        
        // Set quiz association
        update_post_meta($question_id, 'quiz_id', $quiz_id);
        
        // Save answers
        $this->save_question_answers($question_id, $question_data['answers'], $pro_question_id);
        
        // Assign question to quiz
        $this->assign_question_to_quiz($quiz_id, $question_id);
        
        return $question_id;
    }
    
    /**
     * Get ProQuiz answer type based on LearnDash question type
     * 
     * @param string $question_type LearnDash question type
     * @return string ProQuiz answer type ID
     */
    private function get_pro_quiz_answer_type($question_type) {
        $type_map = [
            'multiple_choice' => 'single', // Single choice (radio buttons)
            'multiple_correct' => 'multiple', // Multiple choice (checkboxes)
            'true_false' => 'single',
            'free_answer' => 'free_answer',
            'essay' => 'essay',
            'fill_in_the_blank' => 'cloze_answer',
            'matrix_sort_answer' => 'matrix_sort_answer',
            'assessment_answer' => 'assessment_answer',
            'sort_answer' => 'sort_answer'
        ];
        
        $pro_type = isset($type_map[$question_type]) ? $type_map[$question_type] : 'single';
        
        // Map to ProQuiz numeric type IDs
        $pro_type_map = [
            'single' => 0,
            'multiple' => 1,
            'free_answer' => 2,
            'sort_answer' => 3,
            'matrix_sort_answer' => 4,
            'cloze_answer' => 5,
            'assessment_answer' => 6,
            'essay' => 7
        ];
        
        return isset($pro_type_map[$pro_type]) ? $pro_type_map[$pro_type] : 0; // Default to single choice
    }

    /**
     * Save question answers
     * 
     * @param int $question_id Question ID
     * @param array $answers Answer data
     * @param int $pro_question_id ProQuiz question ID
     */
    private function save_question_answers($question_id, $answers, $pro_question_id) {
        global $wpdb;
        $processed_answers = [];
        
        // First save to LearnDash post meta
        foreach ($answers as $index => $answer) {
            $processed_answers[] = [
                'answer' => $answer['text'],
                'correct' => $answer['correct'] ? 1 : 0,
                'points' => $answer['correct'] ? 1 : 0,
                'sort_order' => $index,
                'html' => 0 // No HTML
            ];
        }
        
        update_post_meta($question_id, 'answers', $processed_answers);
        
        // Get the correct table prefix
        $table_prefix = $wpdb->prefix; // This should return 'edc_'
        
        // Check if wp_pro_quiz_answer table exists
        $table_answer = $table_prefix . 'wp_pro_quiz_answer';
        
        $exists_answer = $wpdb->get_var("SHOW TABLES LIKE '{$table_answer}'");
        error_log("Table {$table_answer} exists: " . ($exists_answer ? 'yes' : 'no'));
        
        // Alternative table names to try if the main one doesn't exist
        if (!$exists_answer) {
            $alt_tables = [
                $table_prefix . 'pro_quiz_answer',
                $table_prefix . 'learndash_pro_quiz_answer',
                $table_prefix . 'wpproquiz_answer'
            ];
            
            foreach ($alt_tables as $alt_table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'");
                if ($exists) {
                    $table_answer = $alt_table;
                    error_log("Found alternative answer table: {$table_answer}");
                    break;
                }
            }
        }
        
        error_log("Saving answers to table: {$table_answer}");
        
        // Then save to ProQuiz tables
        foreach ($processed_answers as $index => $answer) {
            $result = $wpdb->insert(
                $table_answer,
                [
                    'question_id' => $pro_question_id,
                    'answer' => $answer['answer'],
                    'correct' => $answer['correct'],
                    'points' => $answer['points'],
                    'sort_order' => $answer['sort_order'],
                    'html' => $answer['html']
                ]
            );
            
            if ($result === false) {
                error_log("Error saving answer to table {$table_answer}: " . $wpdb->last_error);
            } else {
                error_log("Successfully saved answer {$index} to table {$table_answer}");
            }
        }
    }

    /**
     * Assign question to quiz
     * 
     * @param int $quiz_id Quiz ID
     * @param int $question_id Question ID
     */
    private function assign_question_to_quiz($quiz_id, $question_id) {
        // Get existing questions for this quiz
        $questions = get_post_meta($quiz_id, 'quiz_questions', true);
        if (!is_array($questions)) {
            $questions = [];
        }
        
        // Add this question
        $questions[] = [
            'id' => $question_id,
            'type' => 'question'
        ];
        
        update_post_meta($quiz_id, 'quiz_questions', $questions);
    }

    /**
     * Get quiz data for export
     * 
     * @param int|string $quiz_id Quiz ID or 'all'
     * @return array Quiz data for CSV
     */
    private function get_quiz_data_for_export($quiz_id) {
        $data = [];
        
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
        
        if (empty($quizzes)) {
            return $data;
        }
        
        foreach ($quizzes as $quiz) {
            $quiz_title = $quiz->post_title;
            $quiz_description = $quiz->post_excerpt ?: '';
            
            // Get quiz set from meta if available
            $quiz_set = get_post_meta($quiz->ID, 'quiz_set', true) ?: 'Default Set';
            
            // Get questions for this quiz
            $questions = $this->get_quiz_questions($quiz->ID);
            
            foreach ($questions as $question) {
                $row = [
                    'quiz_set' => $quiz_set,
                    'quiz_title' => $quiz_title,
                    'quiz_description' => $quiz_description,
                    'question_text' => $question['text'],
                    'question_type' => $question['type'],
                    'points' => $question['points'],
                    'category' => $question['category'],
                    'question_hint' => $question['hint']
                ];
                
                // Add answers
                foreach ($question['answers'] as $index => $answer) {
                    $answer_key = 'answer_' . ($index + 1);
                    $row[$answer_key] = $answer['text'];
                }
                
                // Add correct answer indexes
                $correct_indexes = [];
                foreach ($question['answers'] as $index => $answer) {
                    if ($answer['correct']) {
                        $correct_indexes[] = $index + 1;
                    }
                }
                $row['correct_answer'] = implode(',', $correct_indexes);
                
                $data[] = $row;
            }
        }
        
        return $data;
    }

    /**
     * Get questions for a quiz
     * 
     * @param int $quiz_id Quiz ID
     * @return array Questions data
     */
    private function get_quiz_questions($quiz_id) {
        $questions = [];
        
        // Get question IDs from quiz meta
        $quiz_questions = get_post_meta($quiz_id, 'quiz_questions', true);
        if (!is_array($quiz_questions)) {
            return $questions;
        }
        
        foreach ($quiz_questions as $quiz_question) {
            if (!isset($quiz_question['id']) || $quiz_question['type'] !== 'question') {
                continue;
            }
            
            $question_id = $quiz_question['id'];
            $question_post = get_post($question_id);
            
            if (!$question_post) {
                continue;
            }
            
            $question_type = get_post_meta($question_id, 'question_type', true);
            $answers = get_post_meta($question_id, 'answers', true);
            $points = get_post_meta($question_id, 'points', true) ?: 1;
            $hint = get_post_meta($question_id, 'question_hint', true) ?: '';
            // Get the question category from meta if available
            $category = get_post_meta($question_id, 'question_category', true) ?: '';
            
            $processed_answers = [];
            if (is_array($answers)) {
                foreach ($answers as $answer) {
                    $processed_answers[] = [
                        'text' => isset($answer['answer']) ? $answer['answer'] : '',
                        'correct' => isset($answer['correct']) && $answer['correct'] ? true : false
                    ];
                }
            }
            
            $questions[] = [
                'id' => $question_id,
                'text' => $question_post->post_content,
                'type' => $question_type,
                'answers' => $processed_answers,
                'points' => $points,
                'hint' => $hint,
                'category' => $category
            ];
        }
        
        return $questions;
    }

    /**
     * Generate CSV from quiz data
     * 
     * @param array $data Quiz data
     * @return string CSV content
     */
    private function generate_csv($data) {
        if (empty($data)) {
            // Return header row only for empty data
            return implode(',', self::CSV_COLUMNS) . "\n";
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write header row
        fputcsv($output, self::CSV_COLUMNS);
        
        // Write data rows
        foreach ($data as $row) {
            $csv_row = [];
            
            // Ensure all columns are present
            foreach (self::CSV_COLUMNS as $column) {
                $csv_row[] = isset($row[$column]) ? $row[$column] : '';
            }
            
            fputcsv($output, $csv_row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Add tab switching script for admin page
     */
    public function add_tab_switching_script() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_quiz-extensions') {
            return;
        }
        
        // Check for import status message
        $imported = isset($_GET['imported']) ? intval($_GET['imported']) : -1;
        $message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
        
        // Show selected tab
        $selected_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
        
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Tab switching
                $('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    
                    // Update active tab
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    // Show corresponding tab content
                    var tabId = $(this).data('tab');
                    $('.tab-content').hide();
                    $('#' + tabId).show();
                    
                    // Update URL without reloading page
                    if (history.pushState) {
                        var url = new URL(window.location.href);
                        url.searchParams.set('tab', tabId.replace('tab-', ''));
                        window.history.pushState({path: url.toString()}, '', url.toString());
                    }
                });
                
                // Show selected tab if specified
                <?php if (!empty($selected_tab)) : ?>
                $('.nav-tab[data-tab="tab-<?php echo esc_js($selected_tab); ?>"]').click();
                <?php endif; ?>
                
                // Toggle existing quiz field visibility
                $('#quiz-import-target').on('change', function() {
                    if ($(this).val() === 'update') {
                        $('.existing-quiz-field').show();
                    } else {
                        $('.existing-quiz-field').hide();
                    }
                });
                
                // Display import result message if present
                <?php if ($imported !== -1) : ?>
                var successClass = <?php echo $imported ? 'true' : 'false'; ?> ? 'notice-success' : 'notice-error';
                var $notice = $('<div class="notice ' + successClass + ' is-dismissible"><p>' + <?php echo json_encode($message); ?> + '</p></div>');
                $('.wrap > h1').after($notice);
                <?php endif; ?>
            });
        </script>
        <?php
    }
}

// Initialize the class
new CCR_Quiz_Builder();
