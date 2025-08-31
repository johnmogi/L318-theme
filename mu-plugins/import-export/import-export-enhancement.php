<?php
/**
 * Import/Export Enhancement
 * 
 * Enhances the School Manager Import/Export page with:
 * - Chunked student import functionality
 * - Test CSV generation
 * - Better progress tracking
 * - Integration with existing teacher import
 */

if (!defined('ABSPATH')) {
    exit;
}

class Import_Export_Enhancement {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into the import/export page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_generate_test_csv', array($this, 'ajax_generate_test_csv'));
        add_action('wp_ajax_start_chunked_import', array($this, 'ajax_start_chunked_import'));
        add_action('wp_ajax_process_import_chunk', array($this, 'ajax_process_import_chunk'));
        add_action('wp_ajax_get_import_progress', array($this, 'ajax_get_import_progress'));
        
        // Enhance the import/export page
        add_action('admin_footer', array($this, 'add_enhanced_functionality'));
        
        // Handle CSV downloads
        add_action('init', array($this, 'handle_csv_download'));
    }
    
    /**
     * Enqueue scripts for enhanced functionality
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'school-manager_page_school-manager-import-export') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'importExportAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('import_export_nonce')
        ));
    }
    
    /**
     * Add enhanced functionality to the import/export page
     */
    public function add_enhanced_functionality() {
        $screen = get_current_screen();
        if ($screen->id !== 'school-manager_page_school-manager-import-export') {
            return;
        }
        ?>
        <style>
        .enhanced-import-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .test-csv-generator {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .progress-container {
            display: none;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
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
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        
        .chunked-import-controls {
            display: none;
        }
        
        .chunked-import-controls.active {
            display: block;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Show/hide chunked import controls based on import type
            $('#import_type').on('change', function() {
                var importType = $(this).val();
                if (importType === 'students') {
                    $('.chunked-import-controls').addClass('active');
                } else {
                    $('.chunked-import-controls').removeClass('active');
                }
            });
            
            // Generate test CSV
            $(document).on('click', '.generate-test-csv', function(e) {
                e.preventDefault();
                var type = $(this).data('type');
                var count = $('#test-csv-count').val();
                
                $(this).prop('disabled', true).text('Generating...');
                
                // Get fresh nonce from the page
                var nonce = $('input[name="_wpnonce"]').val() || importExportAjax.nonce;
                
                // Add loading state
                var $button = $('.generate-test-csv[data-type="' + type + '"]');
                var originalText = $button.text();
                $button.text('Generating...').prop('disabled', true);
                
                // Send AJAX request with nonce in both URL and data
                $.ajax({
                    url: importExportAjax.ajaxurl + '?action=generate_test_csv&_wpnonce=' + encodeURIComponent(nonce),
                    type: 'POST',
                    data: {
                        action: 'generate_test_csv',
                        type: type,
                        count: count,
                        _wpnonce: nonce
                    },
                    success: function(response) {
                        try {
                            if (response.success) {
                                // Trigger download
                                window.location.href = response.data.download_url;
                                
                                // Update UI with success message
                                var message = '<div class="notice notice-success inline"><p>Successfully generated ' + response.data.count + ' test ' + type + ' records. <a href="' + response.data.download_url + '" download>Download CSV</a></p></div>';
                                $('.import-export-container').prepend(message);
                                
                                // Hide success message after 10 seconds
                                setTimeout(function() {
                                    $('.notice-success').fadeOut('slow', function() { $(this).remove(); });
                                }, 10000);
                            } else {
                                var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                                throw new Error(errorMsg);
                            }
                        } catch (e) {
                            console.error('Error processing response:', e);
                            alert('Error: ' + e.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        
                        var errorMessage = 'Error: ';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage += response.data.message;
                            } else if (response.message) {
                                errorMessage += response.message;
                            } else {
                                errorMessage += 'Unknown error occurred. Please check console for details.';
                            }
                        } catch (e) {
                            errorMessage += 'Failed to process server response. ' + (xhr.responseText || 'No response from server');
                        }
                        
                        // Show error message in UI
                        var errorHtml = '<div class="notice notice-error inline"><p>' + errorMessage + '</p></div>';
                        $('.import-export-container').prepend(errorHtml);
                        
                        // Hide error after 10 seconds
                        setTimeout(function() {
                            $('.notice-error').fadeOut('slow', function() { $(this).remove(); });
                        }, 10000);
                    },
                    complete: function() {
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Start chunked import
            $('#start-chunked-import').on('click', function(e) {
                e.preventDefault();
                
                var fileInput = $('#import_file')[0];
                if (!fileInput.files.length) {
                    alert('Please select a CSV file first.');
                    return;
                }
                
                var formData = new FormData();
                formData.append('action', 'start_chunked_import');
                formData.append('csv_file', fileInput.files[0]);
                formData.append('chunk_size', $('#chunk-size').val());
                formData.append('_wpnonce', importExportAjax.nonce);
                
                $(this).prop('disabled', true);
                $('.progress-container').show();
                $('#import-status').text('Starting import...');
                
                $.ajax({
                    url: importExportAjax.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            processImportChunks(response.data.import_id);
                        } else {
                            alert('Error starting import: ' + response.data);
                            $('#start-chunked-import').prop('disabled', false);
                            $('.progress-container').hide();
                        }
                    },
                    error: function() {
                        alert('Error starting import. Please try again.');
                        $('#start-chunked-import').prop('disabled', false);
                        $('.progress-container').hide();
                    }
                });
            });
            
            // Process import chunks
            function processImportChunks(importId) {
                $.post(importExportAjax.ajaxurl, {
                    action: 'process_import_chunk',
                    import_id: importId,
                    _wpnonce: importExportAjax.nonce
                }, function(response) {
                    if (response.success) {
                        updateProgress(response.data);
                        
                        if (!response.data.completed) {
                            // Continue processing
                            setTimeout(function() {
                                processImportChunks(importId);
                            }, 500);
                        } else {
                            // Import completed
                            $('#import-status').text('Import completed successfully!');
                            $('#start-chunked-import').prop('disabled', false).text('Start New Import');
                        }
                    } else {
                        alert('Error during import: ' + response.data);
                        $('#start-chunked-import').prop('disabled', false);
                    }
                });
            }
            
            // Update progress display
            function updateProgress(data) {
                var percent = Math.round((data.processed / data.total) * 100);
                
                $('#progress-bar').attr('value', percent);
                $('#processed-records').text(data.processed);
                $('#total-records').text(data.total);
                $('#progress-percent').text(percent + '%');
                
                $('#imported-count').text(data.imported);
                $('#updated-count').text(data.updated);
                $('#error-count').text(data.errors);
                
                $('#import-status').text('Processing chunk ' + Math.ceil(data.processed / data.chunk_size) + '...');
                
                // Update error list
                if (data.error_messages && data.error_messages.length > 0) {
                    var errorHtml = '';
                    data.error_messages.slice(-10).forEach(function(error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    $('#error-list').html(errorHtml);
                }
            }
        });
        </script>
        
        <div class="enhanced-import-section">
            <h2>Enhanced Import Options</h2>
            
            <!-- Test CSV Generator -->
            <div class="test-csv-generator">
                <h3>Test CSV Generator</h3>
                <p>Generate test CSV files for testing import functionality:</p>
                <table class="form-table">
                    <tr>
                        <th><label for="test-csv-count">Number of Records:</label></th>
                        <td>
                            <select id="test-csv-count">
                                <option value="50">50 records</option>
                                <option value="100">100 records</option>
                                <option value="250" selected>250 records</option>
                                <option value="500">500 records</option>
                                <option value="1000">1000 records</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <button type="button" class="button generate-test-csv" data-type="students">
                    Generate Test Student CSV
                </button>
                <button type="button" class="button generate-test-csv" data-type="teachers">
                    Generate Test Teacher CSV
                </button>
            </div>
            
            <!-- Chunked Import Controls -->
            <div class="chunked-import-controls">
                <h3>Chunked Import Settings</h3>
                <p>For large CSV files, use chunked import to prevent timeouts and memory issues:</p>
                <table class="form-table">
                    <tr>
                        <th><label for="chunk-size">Chunk Size:</label></th>
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
                <button type="button" id="start-chunked-import" class="button button-primary">
                    Start Chunked Import
                </button>
            </div>
        </div>
        
        <!-- Progress Container -->
        <div class="progress-container" id="progress-container">
            <h2>Import Progress</h2>
            <div id="import-status">Importing records...</div>
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
        <?php
    }
    
    /**
     * AJAX: Generate test CSV
     */
    public function ajax_generate_test_csv() {
        // Check if this is an AJAX request
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error('Invalid request');
        }
        
        // Verify nonce - check both POST and GET for flexibility
        $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'import_export_nonce')) {
            wp_send_json_error('Security check failed. Please refresh the page and try again.');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $type = sanitize_text_field($_POST['type']);
        $count = intval($_POST['count']);
        
        if (!in_array($type, array('students', 'teachers'))) {
            wp_send_json_error('Invalid type');
        }
        
        if ($count < 1 || $count > 10000) {
            wp_send_json_error('Invalid count');
        }
        
        // Generate CSV content
        $csv_content = $this->generate_csv_content($type, $count);
        
        // Create filename with timestamp
        $timestamp = date('Y-m-d-H-i-s');
        $filename = "test-{$type}-{$count}-{$timestamp}.csv";
        
        // Store CSV content in transient for download
        $download_key = 'csv_download_' . wp_generate_password(12, false);
        set_transient($download_key, array(
            'content' => $csv_content,
            'filename' => $filename,
            'type' => $type
        ), 300); // 5 minutes
        
        wp_send_json_success(array(
            'download_url' => admin_url('admin.php?page=school-manager-import-export&download_csv=' . $download_key),
            'filename' => $filename,
            'count' => $count
        ));
    }
    
    /**
     * Generate CSV content
     */
    private function generate_csv_content($type, $count) {
        if ($type === 'students') {
            return $this->generate_student_csv($count);
        } else {
            return $this->generate_teacher_csv($count);
        }
    }
    
    /**
     * Generate student CSV content
     */
    private function generate_student_csv($count) {
        $csv = "ID,Name,Email,Class ID,Teacher ID,Course ID,Registration Date,Expiry Date,Status\n";
        
        $classes = array(1, 2, 3, 4, 5);
        $teachers = array(14, 18, 24);
        $statuses = array('active', 'pending', 'inactive');
        
        for ($i = 1; $i <= $count; $i++) {
            $student_num = str_pad($i, 4, '0', STR_PAD_LEFT);
            $class_id = $classes[array_rand($classes)];
            $teacher_id = $teachers[array_rand($teachers)];
            $status = $statuses[array_rand($statuses)];
            
            // Random registration date in the past year
            $reg_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 365) . ' days'));
            $exp_date = '2026-06-30';
            
            $csv .= ",Student{$student_num},student{$i}@test.com,{$class_id},{$teacher_id},898,{$reg_date},{$exp_date},{$status}\n";
        }
        
        return $csv;
    }
    
    /**
     * Generate teacher CSV content
     */
    private function generate_teacher_csv($count) {
        $csv = "Username,Email,First Name,Last Name,Class ID,Class Name\n";
        
        for ($i = 1; $i <= $count; $i++) {
            $teacher_num = str_pad($i, 3, '0', STR_PAD_LEFT);
            $class_id = $i;
            
            $csv .= "teacher{$teacher_num},teacher{$i}@test.com,Teacher,{$teacher_num},{$class_id},Test Class {$i}\n";
        }
        
        return $csv;
    }
    
    /**
     * Handle CSV downloads
     */
    public function handle_csv_download() {
        if (!isset($_GET['download_csv']) || !is_admin()) {
            return;
        }
        
        $download_key = sanitize_text_field($_GET['download_csv']);
        $csv_data = get_transient($download_key);
        
        if (!$csv_data) {
            wp_die('Download link expired or invalid.');
        }
        
        // Delete the transient
        delete_transient($download_key);
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $csv_data['filename'] . '"');
        header('Content-Length: ' . strlen($csv_data['content']));
        
        // Output CSV content
        echo $csv_data['content'];
        exit;
    }
    
    /**
     * AJAX: Start chunked import
     */
    public function ajax_start_chunked_import() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'import_export_nonce')) {
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
        
        // Move uploaded file to temp location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/temp_import_' . uniqid() . '.csv';
        
        if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $temp_file)) {
            wp_send_json_error('Could not save uploaded file');
        }
        
        // Read and validate CSV
        $handle = fopen($temp_file, 'r');
        if (!$handle) {
            wp_send_json_error('Could not open CSV file');
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            unlink($temp_file);
            wp_send_json_error('Could not read CSV headers');
        }
        
        // Count total records
        $total_records = 0;
        while (fgetcsv($handle) !== false) {
            $total_records++;
        }
        fclose($handle);
        
        // Store import data
        $import_id = uniqid('import_');
        $import_data = array(
            'file_path' => $temp_file,
            'headers' => $headers,
            'total' => $total_records,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'errors' => 0,
            'chunk_size' => $chunk_size,
            'error_messages' => array(),
            'completed' => false
        );
        
        set_transient('import_data_' . $import_id, $import_data, 3600); // 1 hour
        
        wp_send_json_success(array(
            'import_id' => $import_id,
            'total' => $total_records,
            'chunk_size' => $chunk_size
        ));
    }
    
    /**
     * AJAX: Process import chunk
     */
    public function ajax_process_import_chunk() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'import_export_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $import_id = sanitize_text_field($_POST['import_id']);
        $import_data = get_transient('import_data_' . $import_id);
        
        if (!$import_data) {
            wp_send_json_error('Import session expired');
        }
        
        if ($import_data['completed']) {
            wp_send_json_success($import_data);
            return;
        }
        
        // Process chunk
        $result = $this->process_chunk($import_data);
        
        // Update transient
        set_transient('import_data_' . $import_id, $result, 3600);
        
        wp_send_json_success($result);
    }
    
    /**
     * Process a chunk of records
     */
    private function process_chunk($import_data) {
        global $wpdb;
        
        // Increase limits
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);
        
        $handle = fopen($import_data['file_path'], 'r');
        if (!$handle) {
            $import_data['error_messages'][] = 'Could not open CSV file';
            return $import_data;
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
        
        // Check if completed
        if ($import_data['processed'] >= $import_data['total']) {
            $import_data['completed'] = true;
            // Clean up temp file
            if (file_exists($import_data['file_path'])) {
                unlink($import_data['file_path']);
            }
        }
        
        return $import_data;
    }
    
    /**
     * Process a single student row
     */
    private function process_student_row($row, $headers) {
        global $wpdb;
        
        // Map CSV columns to data
        $data = array();
        foreach ($headers as $index => $header) {
            $data[trim($header)] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        
        // Validate required fields
        if (empty($data['Name'])) {
            return new WP_Error('missing_name', 'Student name is required');
        }
        
        // Prepare student data
        $student_data = array(
            'name' => sanitize_text_field($data['Name']),
            'email' => sanitize_email($data['Email']),
            'class_id' => intval($data['Class ID']),
            'teacher_id' => intval($data['Teacher ID']),
            'course_id' => intval($data['Course ID']),
            'registration_date' => !empty($data['Registration Date']) ? $data['Registration Date'] : current_time('mysql'),
            'expiry_date' => !empty($data['Expiry Date']) ? $data['Expiry Date'] : '2026-06-30',
            'status' => sanitize_text_field($data['Status']) ?: 'active'
        );
        
        // Check if student exists
        $table_name = $wpdb->prefix . 'school_students';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE name = %s OR email = %s",
            $student_data['name'],
            $student_data['email']
        ));
        
        if ($existing) {
            // Update existing student
            $result = $wpdb->update(
                $table_name,
                $student_data,
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', 'Failed to update student: ' . $wpdb->last_error);
            }
            
            return array('created' => false, 'student_id' => $existing->id);
        } else {
            // Insert new student
            $result = $wpdb->insert(
                $table_name,
                $student_data,
                array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', 'Failed to insert student: ' . $wpdb->last_error);
            }
            
            return array('created' => true, 'student_id' => $wpdb->insert_id);
        }
    }
}

// Initialize the enhancement
Import_Export_Enhancement::instance();
