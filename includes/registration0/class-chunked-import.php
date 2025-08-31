<?php
if (!defined('ABSPATH')) exit;

class Chunked_Import_Handler {
    private $chunk_size = 10;
    private $temp_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/import_temp/';
        
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
        
        add_action('wp_ajax_upload_import_file', [$this, 'handle_file_upload']);
        add_action('wp_ajax_process_import_chunk', [$this, 'process_import_chunk']);
        add_action('wp_ajax_cleanup_import_file', [$this, 'cleanup_import_file']);
    }
    
    public function handle_file_upload() {
        check_ajax_referer('import_users_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (empty($_FILES['import_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['import_file'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if (strtolower($file_ext) !== 'csv') {
            wp_send_json_error('Invalid file type. Please upload a CSV file.');
        }
        
        $filename = 'import_' . md5(uniqid()) . '.csv';
        $filepath = $this->temp_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $total_rows = $this->count_file_rows($filepath) - 1; // Exclude header
            wp_send_json_success([
                'filename' => $filename,
                'total_rows' => $total_rows,
                'chunks' => ceil($total_rows / $this->chunk_size)
            ]);
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }
    
    private function count_file_rows($filepath) {
        $linecount = 0;
        $handle = fopen($filepath, 'r');
        while (!feof($handle)) {
            $line = fgets($handle);
            $linecount++;
        }
        fclose($handle);
        return $linecount;
    }
    
    public function process_import_chunk() {
        check_ajax_referer('import_users_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $filename = sanitize_text_field($_POST['filename']);
        $chunk = intval($_POST['chunk']);
        $user_type = sanitize_text_field($_POST['user_type']);
        $filepath = $this->temp_dir . $filename;
        
        if (!file_exists($filepath)) {
            wp_send_json_error('File not found');
        }
        
        $results = $this->process_chunk($filepath, $chunk, $user_type);
        
        if ($results['is_complete']) {
            @unlink($filepath);
        }
        
        wp_send_json_success($results);
    }
    
    private function process_chunk($filepath, $chunk, $user_type) {
        $results = [
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'error_messages' => [],
            'is_complete' => false
        ];
        
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            $results['error_messages'][] = 'Could not open file for reading';
            return $results;
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            $results['error_messages'][] = 'Invalid CSV file';
            fclose($handle);
            return $results;
        }
        
        $start_row = $chunk * $this->chunk_size;
        $current_row = 0;
        
        while ($current_row < $start_row && ($row = fgetcsv($handle)) !== false) {
            $current_row++;
        }
        
        $processed_in_chunk = 0;
        while (($row = fgetcsv($handle)) !== false && $processed_in_chunk < $this->chunk_size) {
            $current_row++;
            $processed_in_chunk++;
            
            if (empty(array_filter($row))) continue;
            
            $user_data = array_combine($headers, $row);
            $result = $this->process_user($user_data, $user_type);
            
            if (is_wp_error($result)) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(
                    'Row %d: %s',
                    $current_row + 1,
                    $result->get_error_message()
                );
            } else {
                $results['success']++;
            }
            
            $results['processed']++;
        }
        
        $results['is_complete'] = feof($handle);
        fclose($handle);
        return $results;
    }
    
    private function process_user($user_data, $user_type) {
        $user_data = array_map('trim', $user_data);
        $user_data = array_map('sanitize_text_field', $user_data);
        
        $required_fields = ['first_name', 'last_name', 'phone', 'confirm_phone'];
        
        switch ($user_type) {
            case 'student_school':
                $required_fields = array_merge($required_fields, [
                    'id_number', 'confirm_id', 'course_code', 'subscription_period'
                ]);
                break;
            case 'school_teacher':
                $required_fields[] = 'school_code';
                break;
        }
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            return new WP_Error('missing_fields', 'Missing: ' . implode(', ', $missing_fields));
        }
        
        if ($user_data['phone'] !== $user_data['confirm_phone']) {
            return new WP_Error('phone_mismatch', 'Phone numbers do not match');
        }
        
        if ($user_type === 'student_school' && $user_data['id_number'] !== $user_data['confirm_id']) {
            return new WP_Error('id_mismatch', 'ID numbers do not match');
        }
        
        // TODO: Add user creation/update logic here
        
        return true;
    }
    
    public function cleanup_import_file() {
        check_ajax_referer('import_users_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $filename = sanitize_text_field($_POST['filename']);
        $filepath = $this->temp_dir . $filename;
        
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        
        wp_send_json_success();
    }
}

// Initialize the importer
new Chunked_Import_Handler();
