<?php
/**
 * AJAX handler for exporting teacher's students to CSV
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the export functionality
 */
function init_teacher_students_export() {
    // Register AJAX action for both logged-in and non-logged-in users
    add_action('wp_ajax_export_teacher_students', 'handle_teacher_students_export');
    add_action('wp_ajax_nopriv_export_teacher_students', 'handle_teacher_students_export');
}
add_action('init', 'init_teacher_students_export');

/**
 * Handle the export request
 */
function handle_teacher_students_export() {
    // Check if teacher_id is provided and valid
    $teacher_id = isset($_REQUEST['teacher_id']) ? intval($_REQUEST['teacher_id']) : 0;
    
    // Verify nonce with the provided teacher_id
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'export_teacher_students_' . $teacher_id)) {
        wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'hello-theme-child'));
        return;
    }
    
    // Only allow teachers to export their own data or administrators
    $current_user_id = get_current_user_id();
    if (!current_user_can('manage_options') && $current_user_id != $teacher_id) {
        wp_send_json_error(__('Insufficient permissions', 'hello-theme-child'));
        return;
    }
    
    // Get the class filter if set
    $class_filter = isset($_REQUEST['class_filter']) ? sanitize_text_field($_REQUEST['class_filter']) : 'all';
    $class_id = str_replace('class-', '', $class_filter);
    
    // Get students based on the filter
    $class_students = array();
    $class_manager = new School_Manager_Class();
    
    if ($class_filter === 'all' || empty($class_filter)) {
        // Get all classes for the teacher
        $teacher_classes = $class_manager->get_teacher_classes($teacher_id);
        
        if (!empty($teacher_classes)) {
            foreach ($teacher_classes as $class) {
                $students = $class_manager->get_class_students($class->ID);
                if (!empty($students)) {
                    $class_students[$class->ID] = $students;
                }
            }
        }
    } else {
        // Get students for the specific class
        $students = $class_manager->get_class_students($class_id);
        if (!empty($students)) {
            $class_students[$class_id] = $students;
        }
    }
    
    // If no students found, return error
    if (empty($class_students)) {
        wp_send_json_error(__('No students found to export', 'hello-theme-child'));
        return;
    }
    
    // Generate CSV content
    $csv_data = array();
    
    // Headers
    $headers = array(
        __('Student ID', 'hello-theme-child'),
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
    foreach ($class_students as $class_id => $students) {
        $class_name = $class_manager->get_class_name($class_id);
        
        foreach ($students as $student) {
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
    }
    
    // Generate filename
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
    
    // Return success with file URL
    wp_send_json_success(array(
        'url' => $file_url,
        'filename' => 'students-export-' . date('Y-m-d') . '.csv'
    ));
    
    // Clean up old export files (older than 1 day)
    clean_old_export_files($export_dir);
    
    exit;
}

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

/**
 * Add the export handler to the AJAX handlers list
 */
function register_teacher_students_export_script() {
    wp_register_script(
        'teacher-students-export',
        get_stylesheet_directory_uri() . '/js/teacher-students-export.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/js/teacher-students-export.js'),
        true
    );
    
    wp_localize_script(
        'teacher-students-export',
        'teacherStudentsExport',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('export_teacher_students_' . get_current_user_id()),
            'i18n' => array(
                'exporting' => __('Exporting...', 'hello-theme-child'),
                'preparing' => __('Preparing export...', 'hello-theme-child'),
                'complete' => __('Export complete!', 'hello-theme-child'),
                'error' => __('Error: Could not generate export', 'hello-theme-child'),
                'connectionError' => __('Error: Could not connect to server', 'hello-theme-child')
            )
        )
    );
    
    // Enqueue the script on pages where the shortcode is used
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'teacher_students')) {
        wp_enqueue_script('teacher-students-export');
    }
}
add_action('wp_enqueue_scripts', 'register_teacher_students_export_script');
