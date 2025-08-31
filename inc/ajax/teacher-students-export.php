<?php
/**
 * AJAX handler for exporting teacher's students to CSV
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only load if not already loaded
if (!function_exists('handle_teacher_students_export')) {
    
    /**
     * Handle the export request
     */
    function handle_teacher_students_export() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to export students.', 'hello-theme-child'));
            return;
        }
        
        // Verify nonce for logged-in users
        if (!isset($_REQUEST['_wpnonce']) || 
            !wp_verify_nonce($_REQUEST['_wpnonce'], 'export_teacher_students_' . get_current_user_id())) {
            wp_send_json_error(__('Security check failed. Please try again.', 'hello-theme-child'));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts') && !current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to export students.', 'hello-theme-child'));
            return;
        }
        
        // Get the class filter if set
        $class_filter = isset($_REQUEST['class_filter']) ? sanitize_text_field($_REQUEST['class_filter']) : 'all';
        $teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : get_current_user_id();
        
        // Initialize class manager
        if (!class_exists('School_Manager_Class')) {
            wp_send_json_error(__('School Manager plugin is not active.', 'hello-theme-child'));
            return;
        }
        
        $class_manager = new School_Manager_Class();
        $class_students = array();
        
        try {
            if ($class_filter === 'all' || empty($class_filter)) {
                // Get all classes for the teacher
                $teacher_classes = $class_manager->get_teacher_classes($teacher_id);
                
                if (!empty($teacher_classes)) {
                    foreach ($teacher_classes as $class) {
                        $students = $class_manager->get_class_students($class->ID);
                        if (!empty($students)) {
                            $class_students[$class->ID] = array(
                                'name' => $class->name,
                                'students' => $students
                            );
                        }
                    }
                }
            } else {
                // Get students for the specific class
                $class_id = str_replace('class-', '', $class_filter);
                $class = $class_manager->get_class($class_id);
                
                if ($class) {
                    $students = $class_manager->get_class_students($class_id);
                    if (!empty($students)) {
                        $class_students[$class_id] = array(
                            'name' => $class->name,
                            'students' => $students
                        );
                    }
                }
            }
            
            // If no students found, return error
            if (empty($class_students)) {
                wp_send_json_error(__('No students found to export.', 'hello-theme-child'));
                return;
            }
            
            // Generate CSV content
            $csv_data = array();
            
            // Headers in Hebrew
            $headers = array(
                __('ID', 'hello-theme-child'),
                __('Full Name', 'hello-theme-child'),
                __('Username', 'hello-theme-child'),
                __('Email', 'hello-theme-child'),
                __('Class', 'hello-theme-child'),
                __('Status', 'hello-theme-child'),
                __('Last Login', 'hello-theme-child'),
                __('Registration Date', 'hello-theme-child')
            );
            
            $csv_data[] = $headers;
            
            // Add student data
            foreach ($class_students as $class_id => $class_data) {
                $class_name = $class_data['name'];
                
                foreach ($class_data['students'] as $student) {
                    $user = get_user_by('id', $student->wp_user_id);
                    if (!$user) continue;
                    
                    // Get user meta
                    $last_login = get_user_meta($user->ID, 'last_login', true);
                    $last_login = $last_login ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_login) : __('Never', 'hello-theme-child');
                    
                    $row = array(
                        $user->ID,
                        !empty($student->name) ? $student->name : $user->display_name,
                        $user->user_login,
                        $user->user_email,
                        $class_name,
                        !empty($student->status) ? $student->status : __('Active', 'hello-theme-child'),
                        $last_login,
                        date_i18n(get_option('date_format'), strtotime($user->user_registered))
                    );
                    
                    $csv_data[] = $row;
                }
            }
            
            // Generate CSV file
            $upload_dir = wp_upload_dir();
            $export_dir = trailingslashit($upload_dir['basedir']) . 'exports/';
            
            // Create directory if it doesn't exist
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
                // Add index.php to prevent directory listing
                file_put_contents($export_dir . 'index.php', '<?php // Silence is golden');
                // Add .htaccess to prevent direct access
                file_put_contents($export_dir . '.htaccess', 'deny from all');
            }
            
            // Generate filename with timestamp and random string
            $filename = 'students-export-' . date('Y-m-d-H-i-s') . '-' . wp_generate_password(6, false) . '.csv';
            $filepath = $export_dir . $filename;
            
            // Open file for writing
            $file = fopen($filepath, 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Write CSV data
            foreach ($csv_data as $row) {
                // Convert array values to UTF-8
                $row = array_map(function($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'auto');
                }, $row);
                
                fputcsv($file, $row);
            }
            
            // Close file
            fclose($file);
            
            // Set file permissions
            chmod($filepath, 0644);
            
            // Get the URL to the file
            $file_url = trailingslashit($upload_dir['baseurl']) . 'exports/' . $filename;
            
            // Clean up old export files (older than 1 day)
            clean_old_export_files($export_dir);
            
            // Return success with file URL
            wp_send_json_success(array(
                'url' => $file_url,
                'filename' => 'students-export-' . date('Y-m-d') . '.csv',
                'count' => count($csv_data) - 1 // Subtract header row
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(sprintf(__('Export failed: %s', 'hello-theme-child'), $e->getMessage()));
        }
    }
    add_action('wp_ajax_export_teacher_students', 'handle_teacher_students_export');
    
    /**
     * Clean up old export files
     * 
     * @param string $directory Directory containing export files
     */
    function clean_old_export_files($directory) {
        // Skip if directory doesn't exist
        if (!is_dir($directory)) {
            return;
        }
        
        // Get all CSV files in the directory
        $files = glob(trailingslashit($directory) . 'students-export-*.csv');
        $now = time();
        $max_age = 24 * 60 * 60; // 24 hours in seconds
        
        foreach ($files as $file) {
            // Skip if not a file
            if (!is_file($file)) {
                continue;
            }
            
            // Delete files older than max age
            if (($now - filemtime($file)) >= $max_age) {
                @unlink($file);
            }
        }
    }
}

// Handle direct file access (for admin exports)
if (isset($_GET['action']) && $_GET['action'] === 'export_teacher_students' && isset($_GET['_wpnonce'])) {
    add_action('init', 'handle_teacher_students_export', 1);
}
