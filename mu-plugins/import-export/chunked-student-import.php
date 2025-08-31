<?php
/**
 * Chunked Student Import System
 * 
 * Handles large student imports (250-1K+) with memory management and progress tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chunked_Student_Import {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle AJAX requests
        add_action('wp_ajax_start_student_import', array($this, 'ajax_start_import'));
        add_action('wp_ajax_process_student_chunk', array($this, 'ajax_process_chunk'));
        add_action('wp_ajax_get_import_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_generate_test_csv', array($this, 'ajax_generate_test_csv'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Student Import',
            'Student Import',
            'manage_options',
            'chunked-student-import',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'chunked-student-import') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_import_script());
        wp_add_inline_style('wp-admin', $this->get_import_styles());
    }
    
    /**
     * Get import JavaScript
     */
    private function get_import_script() {
        return "
        jQuery(document).ready(function($) {
            let importInProgress = false;
            let importId = null;
            
            // Generate test CSV
            $('#generate-test-csv').click(function() {
                const count = $('#test-csv-count').val();
                const button = $(this);
                
                button.prop('disabled', true).text('Generating...');
                
                $.post(ajaxurl, {
                    action: 'generate_test_csv',
                    count: count,
                    _wpnonce: '" . wp_create_nonce('student_import') . "'
                }, function(response) {
                    if (response.success) {
                        alert('Test CSV generated successfully!\\nFile: ' + response.data.filename + '\\nStudents: ' + response.data.count);
                        $('#csv-file-info').html('<p><strong>Generated:</strong> ' + response.data.filename + ' (' + response.data.count + ' students)</p>');
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Generate Test CSV');
                });
            });
            
            // Start import
            $('#start-import').click(function() {
                const fileInput = $('#csv-file')[0];
                const chunkSize = $('#chunk-size').val();
                
                if (!fileInput.files.length) {
                    alert('Please select a CSV file');
                    return;
                }
                
                if (importInProgress) {
                    alert('Import already in progress');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'start_student_import');
                formData.append('csv_file', fileInput.files[0]);
                formData.append('chunk_size', chunkSize);
                formData.append('_wpnonce', '" . wp_create_nonce('student_import') . "');
                
                importInProgress = true;
                $('#start-import').prop('disabled', true).text('Starting...');
                $('#progress-container').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            importId = response.data.import_id;
                            $('#total-records').text(response.data.total_records);
                            $('#progress-bar').attr('max', response.data.total_records);
                            processNextChunk();
                        } else {
                            alert('Error starting import: ' + response.data);
                            resetImport();
                        }
                    },
                    error: function() {
                        alert('Error starting import');
                        resetImport();
                    }
                });
            });
            
            function processNextChunk() {
                $.post(ajaxurl, {
                    action: 'process_student_chunk',
                    import_id: importId,
                    _wpnonce: '" . wp_create_nonce('student_import') . "'
                }, function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        // Update progress
                        $('#processed-records').text(data.processed);
                        $('#progress-bar').val(data.processed);
                        $('#progress-percent').text(Math.round((data.processed / data.total) * 100) + '%');
                        
                        // Update stats
                        $('#imported-count').text(data.imported);
                        $('#updated-count').text(data.updated);
                        $('#error-count').text(data.errors);
                        
                        // Show recent errors
                        if (data.recent_errors && data.recent_errors.length > 0) {
                            $('#error-list').html(data.recent_errors.map(e => '<li>' + e + '</li>').join(''));
                        }
                        
                        if (data.completed) {
                            // Import completed
                            $('#import-status').text('Import completed successfully!').addClass('success');
                            resetImport();
                        } else {
                            // Process next chunk
                            setTimeout(processNextChunk, 500);
                        }
                    } else {
                        alert('Error processing chunk: ' + response.data);
                        resetImport();
                    }
                }).fail(function() {
                    alert('Error processing chunk');
                    resetImport();
                });
            }
            
            function resetImport() {
                importInProgress = false;
                importId = null;
                $('#start-import').prop('disabled', false).text('Start Import');
            }
        });
        ";
    }
    
    /**
     * Get import styles
     */
    private function get_import_styles() {
        return "
        .import-container {
            max-width: 800px;
            margin: 20px 0;
        }
        
        .import-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .progress-container {
            display: none;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            margin: 10px 0;
        }
        
        .import-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f1f1f1;
            border-radius: 4px;
            flex: 1;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff2f2;
            border: 1px solid #ffcccc;
            padding: 10px;
            border-radius: 4px;
        }
        
        .success {
            color: #00a32a;
            font-weight: bold;
        }
        ";
    }
    
    /**
     * Render the import page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1>Chunked Student Import</h1>
            <p>Import large numbers of students (250-1K+) with progress tracking and memory management.</p>
            
            <div class="import-container">
                <!-- Test CSV Generation -->
                <div class="import-section">
                    <h2>Generate Test CSV</h2>
                    <p>Generate a test CSV file with sample student data for testing.</p>
                    <table class="form-table">
                        <tr>
                            <th>Number of Students</th>
                            <td>
                                <select id="test-csv-count">
                                    <option value="50">50 students</option>
                                    <option value="100">100 students</option>
                                    <option value="250" selected>250 students</option>
                                    <option value="500">500 students</option>
                                    <option value="1000">1000 students</option>
                                </select>
                                <button type="button" id="generate-test-csv" class="button">Generate Test CSV</button>
                            </td>
                        </tr>
                    </table>
                    <div id="csv-file-info"></div>
                </div>
                
                <!-- File Upload -->
                <div class="import-section">
                    <h2>Upload CSV File</h2>
                    <p>CSV Format: ID, Name, Email, Class ID, Teacher ID, Course ID, Registration Date, Expiry Date, Status</p>
                    <table class="form-table">
                        <tr>
                            <th>CSV File</th>
                            <td><input type="file" id="csv-file" accept=".csv" /></td>
                        </tr>
                        <tr>
                            <th>Chunk Size</th>
                            <td>
                                <select id="chunk-size">
                                    <option value="10">10 records per chunk</option>
                                    <option value="25" selected>25 records per chunk</option>
                                    <option value="50">50 records per chunk</option>
                                    <option value="100">100 records per chunk</option>
                                </select>
                                <p class="description">Smaller chunks are safer for large imports but take longer.</p>
                            </td>
                        </tr>
                    </table>
                    <button type="button" id="start-import" class="button button-primary">Start Import</button>
                </div>
                
                <!-- Progress Tracking -->
                <div class="import-section progress-container" id="progress-container">
                    <h2>Import Progress</h2>
                    <div id="import-status">Importing students...</div>
                    <progress id="progress-bar" value="0" max="100"></progress>
                    <div>
                        <span id="processed-records">0</span> of <span id="total-records">0</span> records processed 
                        (<span id="progress-percent">0%</span>)
                    </div>
                    
                    <div class="import-stats">
                        <div class="stat-item">
                            <div class="stat-number" id="imported-count">0</div>
                            <div class="stat-label">Imported</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="updated-count">0</div>
                            <div class="stat-label">Updated</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="error-count">0</div>
                            <div class="stat-label">Errors</div>
                        </div>
                    </div>
                    
                    <div id="error-section" style="margin-top: 20px;">
                        <h3>Recent Errors</h3>
                        <ul id="error-list" class="error-list"></ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Start import
     */
    public function ajax_start_import() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'student_import')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
        }
        
        $chunk_size = intval($_POST['chunk_size']);
        if ($chunk_size < 1 || $chunk_size > 1000) {
            $chunk_size = 25;
        }
        
        // Increase memory and time limits
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);
        
        $file_path = $_FILES['csv_file']['tmp_name'];
        $import_id = uniqid('student_import_');
        
        // Read and validate CSV
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            wp_send_json_error('Could not open CSV file');
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            wp_send_json_error('Could not read CSV headers');
        }
        
        // Count total records
        $total_records = 0;
        while (fgetcsv($handle) !== false) {
            $total_records++;
        }
        fclose($handle);
        
        // Store import data
        $import_data = array(
            'file_path' => $file_path,
            'headers' => $headers,
            'total_records' => $total_records,
            'chunk_size' => $chunk_size,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
            'error_messages' => array(),
            'started' => time()
        );
        
        // Move uploaded file to a permanent location
        $upload_dir = wp_upload_dir();
        $permanent_path = $upload_dir['path'] . '/' . $import_id . '.csv';
        move_uploaded_file($file_path, $permanent_path);
        $import_data['file_path'] = $permanent_path;
        
        set_transient('student_import_' . $import_id, $import_data, 3600); // 1 hour
        
        wp_send_json_success(array(
            'import_id' => $import_id,
            'total_records' => $total_records,
            'chunk_size' => $chunk_size
        ));
    }
    
    /**
     * AJAX: Process chunk
     */
    public function ajax_process_chunk() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'student_import')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $import_id = sanitize_text_field($_POST['import_id']);
        $import_data = get_transient('student_import_' . $import_id);
        
        if (!$import_data) {
            wp_send_json_error('Import session not found');
        }
        
        // Increase limits
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        
        $handle = fopen($import_data['file_path'], 'r');
        if (!$handle) {
            wp_send_json_error('Could not open CSV file');
        }
        
        // Skip headers
        fgetcsv($handle);
        
        // Skip already processed records
        for ($i = 0; $i < $import_data['processed']; $i++) {
            fgetcsv($handle);
        }
        
        // Process chunk
        $chunk_processed = 0;
        $chunk_imported = 0;
        $chunk_updated = 0;
        $chunk_errors = 0;
        
        while ($chunk_processed < $import_data['chunk_size'] && ($row = fgetcsv($handle)) !== false) {
            $result = $this->process_student_row($row, $import_data['headers']);
            
            if (is_wp_error($result)) {
                $chunk_errors++;
                $import_data['error_messages'][] = 'Row ' . ($import_data['processed'] + $chunk_processed + 1) . ': ' . $result->get_error_message();
                
                // Keep only last 50 errors
                if (count($import_data['error_messages']) > 50) {
                    $import_data['error_messages'] = array_slice($import_data['error_messages'], -50);
                }
            } else {
                if ($result['created']) {
                    $chunk_imported++;
                } else {
                    $chunk_updated++;
                }
            }
            
            $chunk_processed++;
        }
        
        fclose($handle);
        
        // Update import data
        $import_data['processed'] += $chunk_processed;
        $import_data['imported'] += $chunk_imported;
        $import_data['updated'] += $chunk_updated;
        $import_data['errors'] += $chunk_errors;
        
        $completed = $import_data['processed'] >= $import_data['total_records'];
        
        if ($completed) {
            // Clean up
            if (file_exists($import_data['file_path'])) {
                unlink($import_data['file_path']);
            }
            delete_transient('student_import_' . $import_id);
        } else {
            set_transient('student_import_' . $import_id, $import_data, 3600);
        }
        
        // Clear caches
        wp_cache_flush();
        
        wp_send_json_success(array(
            'processed' => $import_data['processed'],
            'total' => $import_data['total_records'],
            'imported' => $import_data['imported'],
            'updated' => $import_data['updated'],
            'errors' => $import_data['errors'],
            'recent_errors' => array_slice($import_data['error_messages'], -5),
            'completed' => $completed
        ));
    }
    
    /**
     * Process a single student row
     */
    private function process_student_row($row, $headers) {
        global $wpdb;
        
        // Map row data to headers
        $data = array();
        foreach ($headers as $i => $header) {
            $data[trim($header)] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        
        // Validate required fields
        if (empty($data['Name'])) {
            return new WP_Error('missing_name', 'Student name is required');
        }
        
        // Prepare student data
        $student_data = array(
            'name' => $data['Name'],
            'email' => !empty($data['Email']) ? $data['Email'] : '',
            'class_id' => !empty($data['Class ID']) ? intval($data['Class ID']) : null,
            'teacher_id' => !empty($data['Teacher ID']) ? intval($data['Teacher ID']) : null,
            'course_id' => !empty($data['Course ID']) ? intval($data['Course ID']) : null,
            'registration_date' => !empty($data['Registration Date']) ? $data['Registration Date'] : current_time('mysql'),
            'expiry_date' => !empty($data['Expiry Date']) ? $data['Expiry Date'] : null,
            'status' => !empty($data['Status']) ? $data['Status'] : 'active'
        );
        
        $table_name = $wpdb->prefix . 'school_students';
        
        // Check if student exists
        $existing_student = null;
        if (!empty($data['ID'])) {
            $existing_student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                intval($data['ID'])
            ));
        }
        
        if (!$existing_student && !empty($data['Name'])) {
            $existing_student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE name = %s",
                $data['Name']
            ));
        }
        
        if ($existing_student) {
            // Update existing student
            $result = $wpdb->update(
                $table_name,
                $student_data,
                array('id' => $existing_student->id),
                array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', 'Failed to update student: ' . $wpdb->last_error);
            }
            
            return array('student_id' => $existing_student->id, 'created' => false);
        } else {
            // Create new student
            $result = $wpdb->insert(
                $table_name,
                $student_data,
                array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', 'Failed to create student: ' . $wpdb->last_error);
            }
            
            return array('student_id' => $wpdb->insert_id, 'created' => true);
        }
    }
    
    /**
     * AJAX: Generate test CSV
     */
    public function ajax_generate_test_csv() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'student_import')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $count = intval($_POST['count']);
        if ($count < 1 || $count > 2000) {
            $count = 250;
        }
        
        $filename = 'test-students-' . $count . '-' . date('Y-m-d-H-i-s') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $handle = fopen($file_path, 'w');
        if (!$handle) {
            wp_send_json_error('Could not create CSV file');
        }
        
        // Write headers
        fputcsv($handle, array('ID', 'Name', 'Email', 'Class ID', 'Teacher ID', 'Course ID', 'Registration Date', 'Expiry Date', 'Status'));
        
        // Generate test data
        $class_ids = array(1, 2, 3, 4, 5);
        $teacher_ids = array(14, 18, 24);
        $course_id = 898;
        $statuses = array('active', 'inactive', 'pending');
        
        for ($i = 1; $i <= $count; $i++) {
            $name = 'Student' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $email = 'student' . $i . '@test.com';
            $class_id = $class_ids[array_rand($class_ids)];
            $teacher_id = $teacher_ids[array_rand($teacher_ids)];
            $status = $statuses[array_rand($statuses)];
            $reg_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days'));
            $exp_date = date('Y-m-d', strtotime('+1 year'));
            
            fputcsv($handle, array(
                '', // ID (empty for new students)
                $name,
                $email,
                $class_id,
                $teacher_id,
                $course_id,
                $reg_date,
                $exp_date,
                $status
            ));
        }
        
        fclose($handle);
        
        wp_send_json_success(array(
            'filename' => $filename,
            'filepath' => $file_path,
            'url' => $upload_dir['url'] . '/' . $filename,
            'count' => $count
        ));
    }
}

// Initialize the chunked student import
Chunked_Student_Import::instance();
