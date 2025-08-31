<?php
/**
 * Handles chunked user imports to prevent timeouts with large files
 */
class Chunked_Import_Handler {
    /**
     * @var string Temporary directory for storing import files
     */
    private $temp_dir;
    
    /**
     * @var int Number of rows to process per chunk
     */
    private $chunk_size = 10;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/ccr_imports/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
        
        // Register AJAX handlers
        add_action('wp_ajax_upload_import_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_process_import_chunk', array($this, 'process_import_chunk'));
        add_action('wp_ajax_cleanup_import_file', array($this, 'cleanup_import_file'));
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        check_ajax_referer('ccr_import_nonce', 'nonce');
        
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
    
    /**
     * Count the number of rows in a file
     */
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
    
    /**
     * Process a chunk of the import
     */
    public function process_import_chunk() {
        check_ajax_referer('ccr_import_nonce', 'nonce');
        
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
        
        // If this was the last chunk, clean up the file
        if ($results['is_complete']) {
            @unlink($filepath);
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Process a specific chunk of the import file
     */
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
            $results['is_complete'] = true;
            return $results;
        }
        
        // Skip header
        $headers = fgetcsv($handle);
        if (!$headers) {
            $results['error_messages'][] = 'Invalid CSV file';
            $results['is_complete'] = true;
            fclose($handle);
            return $results;
        }
        
        // Skip to the start of our chunk
        $start_row = $chunk * $this->chunk_size;
        $current_row = 0;
        
        // Skip rows we've already processed
        while ($current_row < $start_row && ($row = fgetcsv($handle)) !== false) {
            $current_row++;
        }
        
        // Process up to chunk_size rows
        $processed_in_chunk = 0;
        while (($row = fgetcsv($handle)) !== false && $processed_in_chunk < $this->chunk_size) {
            $current_row++;
            $processed_in_chunk++;
            
            // Skip empty rows
            if (empty(array_filter($row))) continue;
            
            $user_data = array_combine($headers, $row);
            
            // Process the user
            $result = $this->process_user($user_data, $user_type);
            
            if (is_wp_error($result)) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(
                    'Row %d: %s',
                    $current_row + 1, // +1 for header
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
    
    /**
     * Process a single user from the import
     */
    private function process_user($user_data, $user_type) {
        // Trim all values
        $user_data = array_map('trim', $user_data);
        
        // Sanitize all values
        $user_data = array_map('sanitize_text_field', $user_data);
        
        // Define required fields based on user type
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
        
        // Check for missing required fields
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            return new WP_Error('missing_fields', 'Missing required fields: ' . implode(', ', $missing_fields));
        }
        
        // Validate phone confirmation
        if ($user_data['phone'] !== $user_data['confirm_phone']) {
            return new WP_Error('phone_mismatch', 'Phone numbers do not match');
        }
        
        // Validate ID confirmation for school students
        if ($user_type === 'student_school' && $user_data['id_number'] !== $user_data['confirm_id']) {
            return new WP_Error('id_mismatch', 'ID numbers do not match');
        }
        
        // Check if user already exists by phone
        $user_exists = get_users([
            'meta_key' => 'phone',
            'meta_value' => $user_data['phone'],
            'number' => 1,
            'count_total' => false
        ]);
        
        $is_new_user = empty($user_exists);
        $user_id = $is_new_user ? 0 : $user_exists[0]->ID;
        
        try {
            // Create or update user
            $userdata = [
                'user_login' => sanitize_user($user_data['phone'], true),
                'user_email' => sanitize_email($user_data['phone'] . '@' . $_SERVER['HTTP_HOST']),
                'first_name' => $user_data['first_name'],
                'last_name' => $user_data['last_name'],
                'display_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                'user_pass' => !empty($user_data['id_number']) ? $user_data['id_number'] : wp_generate_password(12, true, true),
                'role' => $user_type === 'school_teacher' ? 'teacher' : 'subscriber'
            ];
            
            if ($is_new_user) {
                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    throw new Exception($user_id->get_error_message());
                }
                
                // Add user meta
                update_user_meta($user_id, 'phone', $user_data['phone']);
                update_user_meta($user_id, 'registration_method', 'import');
                
                // Set additional meta based on user type
                if ($user_type === 'student_school') {
                    update_user_meta($user_id, 'id_number', $user_data['id_number']);
                    update_user_meta($user_id, 'course_code', $user_data['course_code']);
                    update_user_meta($user_id, 'subscription_period', $user_data['subscription_period']);
                    
                    if (!empty($user_data['school_code'])) {
                        update_user_meta($user_id, 'school_code', $user_data['school_code']);
                    }
                } elseif ($user_type === 'school_teacher') {
                    update_user_meta($user_id, 'school_code', $user_data['school_code']);
                }
                
                // Handle shipping information if provided
                $shipping_fields = ['shipping_method', 'shipping_city', 'shipping_street', 'shipping_phone'];
                foreach ($shipping_fields as $field) {
                    if (!empty($user_data[$field])) {
                        update_user_meta($user_id, $field, $user_data[$field]);
                    }
                }
                
                // Add coupon code if provided
                if (!empty($user_data['coupon_code'])) {
                    update_user_meta($user_id, 'coupon_code', $user_data['coupon_code']);
                }
                
                // TODO: Send welcome email with login details
                
            } else {
                // Update existing user
                $userdata['ID'] = $user_id;
                wp_update_user($userdata);
                
                // Update meta if needed
                if ($user_type === 'student_school') {
                    update_user_meta($user_id, 'course_code', $user_data['course_code']);
                    update_user_meta($user_id, 'subscription_period', $user_data['subscription_period']);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('user_creation_failed', $e->getMessage());
        }
    }
    
    /**
     * Clean up temporary import file
     */
    public function cleanup_import_file() {
        check_ajax_referer('ccr_import_nonce', 'nonce');
        
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

// Initialize the import handler
new Chunked_Import_Handler();
