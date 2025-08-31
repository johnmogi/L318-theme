<?php
/**
 * Teacher Students Shortcode
 *
 * Displays all students in classes taught by the current teacher.
 * Shows a consolidated table with student information and class filtering.
 * Only visible to users with the `school_teacher` role (or administrators for testing).
 *
 * Usage: [teacher_students]
 *
 * @package Hello_Child_Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Render the teacher students shortcode content.
 *
 * @return string HTML output
 */
function hello_theme_teacher_students_shortcode() {
    global $wpdb;
    
    // Debug logging
    error_log('=== TEACHER STUDENTS SHORTCODE STARTED ===');
    error_log('Current user ID: ' . get_current_user_id());
    
    // Require teacher capability - silently return empty for non-teachers
    if (!current_user_can('school_teacher') && !current_user_can('administrator')) {
        error_log('Teacher shortcode accessed by non-teacher user ID: ' . get_current_user_id());
        // Return empty string instead of error message for better UX
        return '';
    }

    $teacher_id = get_current_user_id();
    
    // Debug: Get current user data
    $current_user = wp_get_current_user();
    error_log('Current user: ' . print_r(array(
        'ID' => $current_user->ID,
        'login' => $current_user->user_login,
        'roles' => $current_user->roles,
        'caps' => $current_user->caps
    ), true));

    // Direct database query to verify teacher's classes
    $teacher_classes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}school_classes WHERE teacher_id = %d",
        $teacher_id
    ));
    
    error_log('Direct DB query - Teacher classes: ' . print_r($teacher_classes, true));

    // Fallback: If no classes found, try alternative method
    if (empty($teacher_classes)) {
        error_log('No classes found via direct query, trying alternative method');
        $teacher_classes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}school_classes WHERE teacher_id = %s",
            (string)$teacher_id
        ));
    }

    if (empty($teacher_classes)) {
        return '<div class="notice notice-warning" style="padding: 20px; margin: 20px 0; background: #fff3cd; border-left: 4px solid #ffc107;">' .
               '<h3 style="margin-top: 0; color: #856404;">' . esc_html__('No Classes Found', 'hello-theme-child') . '</h3>' .
               '<p>' . esc_html__('You are not assigned to any classes yet.', 'hello-theme-child') . '</p>' .
               '<p><small>Teacher ID: ' . esc_html($teacher_id) . '</small></p>' .
               '</div>';
    }
    
    // Get all students in these classes with direct query
    $class_ids = wp_list_pluck($teacher_classes, 'id');
    $class_ids_placeholder = implode(',', array_fill(0, count($class_ids), '%d'));
    
    $students_query = $wpdb->prepare(
        "SELECT s.*, u.user_login, u.user_email, c.name as class_name, c.id as class_id 
         FROM {$wpdb->prefix}school_students s
         JOIN {$wpdb->prefix}school_classes c ON s.class_id = c.id
         LEFT JOIN {$wpdb->users} u ON s.wp_user_id = u.ID
         WHERE c.teacher_id = %d",
        $teacher_id
    );
    
    $all_students = $wpdb->get_results($students_query);
    
    // Debug: Log the raw query and results
    error_log('Students query: ' . $wpdb->last_query);
    error_log('Students found: ' . count($all_students));
    
    // Organize students by class
    $class_students = array();
    $classes_info = array();
    
    // Initialize classes info
    foreach ($teacher_classes as $class) {
        $classes_info[$class->id] = $class;
        $class_students[$class->id] = array();
    }
    
    // Assign students to their classes
    foreach ($all_students as $student) {
        if (isset($class_students[$student->class_id])) {
            $class_students[$student->class_id][] = $student;
        }
    }
    
    // Remove empty classes
    foreach ($class_students as $class_id => $students) {
        if (empty($students)) {
            unset($class_students[$class_id]);
        }
    }
    
    $total_students = count($all_students);

    if ($total_students === 0) {
        $debug_info = '';
        // Show debug info only to teachers and administrators
        if (current_user_can('administrator') || current_user_can('teacher')) {
            $debug_info = '<div class="debug-info" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; font-size: 0.9em;">' .
                        '<h4 style="margin-top: 0;">' . esc_html__('Debug Information (Admin Only)', 'hello-theme-child') . '</h4>' .
                        '<p><strong>' . esc_html__('Teacher ID:', 'hello-theme-child') . '</strong> ' . esc_html($teacher_id) . '</p>' .
                        '<p><strong>' . esc_html__('Classes Found:', 'hello-theme-child') . '</strong> ' . count($teacher_classes) . '</p>' .
                        '<p><strong>' . esc_html__('Class IDs:', 'hello-theme-child') . '</strong> ' . implode(', ', wp_list_pluck($teacher_classes, 'id')) . '</p>' .
                        '<p><strong>' . esc_html__('Last Query:', 'hello-theme-child') . '</strong> ' . esc_html($wpdb->last_query) . '</p>' .
                        '<p><strong>' . esc_html__('Last Error:', 'hello-theme-child') . '</strong> ' . esc_html($wpdb->last_error) . '</p>' .
                        '</div>';
        }
        
        return '<div class="notice notice-info" style="padding: 20px; margin: 20px 0; background: #e7f5ff; border-left: 4px solid #4dabf7;">' .
               '<h3 style="margin-top: 0; color: #0c5460;">' . esc_html__('No Students Found', 'hello-theme-child') . '</h3>' .
               '<p>' . esc_html__('There are no students currently enrolled in your classes.', 'hello-theme-child') . '</p>' .
               $debug_info .
               '</div>';
    }

    // Build a mapping of class names for filter dropdown
    $classes_for_filter = array();
    foreach ($class_students as $cid => $studs) {
        $class_name = isset($classes_info[$cid]->name) ? $classes_info[$cid]->name : (__('Class', 'hello-theme-child') . ' #' . $cid);
        $classes_for_filter[$cid] = esc_html($class_name);
        
        // Log class info for debugging
        error_log(sprintf('Class %d: %s - %d students', $cid, $class_name, count($studs)));
    }

    // Begin output
    ob_start();
    
    // Debug info for admins
    $debug_info = '';
    if (current_user_can('administrator')) {
        $debug_info = '<div class="debug-info" style="margin: 15px 0; padding: 10px; background: #f0f7ff; border: 1px solid #b3d7ff; border-radius: 4px; font-size: 0.9em;">' .
                    '<strong>Debug Info:</strong> ' . count($class_students) . ' classes, ' . $total_students . ' total students' .
                    '</div>';
    }
    
    // Filter UI with improved styling
    echo '<div class="teacher-students-wrapper" style="margin: 20px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;">';
    echo '<div class="teacher-students-header" style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">';
    echo '<h2 style="margin: 0 0 10px 0; color: #333; font-weight: 600;">' . esc_html__('My Students', 'hello-theme-child') . '</h2>';
    echo '<p style="margin: 0; color: #6c757d; font-size: 0.95em;">' . 
         sprintf(esc_html__('Viewing %d students across %d classes', 'hello-theme-child'), $total_students, count($class_students)) . 
         '</p>';
    echo '</div>';
    
    echo $debug_info;
    
    // Only show filter if there are multiple classes
    if (count($classes_for_filter) > 1) {
        echo '<div class="teacher-students-filter" style="margin-bottom: 20px; padding: 12px 15px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; display: flex; align-items: center;">';
        echo '<label for="teacher-students-class-filter" style="margin: 0 10px 0 0; font-weight: 500; color: #495057; min-width: 100px;">' . 
             esc_html__('Filter by class:', 'hello-theme-child') . 
             '</label>';
        echo '<select id="teacher-students-class-filter" style="padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background-color: #fff; cursor: pointer; flex: 1; max-width: 300px;">';
        echo '<option value="all">' . 
             esc_html__('All Classes', 'hello-theme-child') . ' (' . $total_students . ' ' . esc_html__('students', 'hello-theme-child') . ')' . 
             '</option>';
        
        foreach ($classes_for_filter as $cid => $cname) {
            $student_count = count($class_students[$cid]);
            echo '<option value="class-' . intval($cid) . '">' . 
                 $cname . ' (' . $student_count . ' ' . esc_html__('students', 'hello-theme-child') . ')' . 
                 '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    // Students table with improved styling
    echo '<div class="teacher-students-list" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">';
    echo '<table class="teacher-students-table" style="width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
    
    // Table header
    echo '<thead>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<th style="padding: 12px 15px; border-bottom: 2px solid #dee2e6; text-align: right; font-weight: 600; color: #495057; white-space: nowrap;">' . 
         esc_html__('Student Name', 'hello-theme-child') . 
         '</th>';
    echo '<th style="padding: 12px 15px; border-bottom: 2px solid #dee2e6; text-align: right; font-weight: 600; color: #495057; white-space: nowrap;">' . 
         esc_html__('Username', 'hello-theme-child') . 
         '</th>';
    echo '<th style="padding: 12px 15px; border-bottom: 2px solid #dee2e6; text-align: right; font-weight: 600; color: #495057; white-space: nowrap;">' . 
         esc_html__('Email', 'hello-theme-child') . 
         '</th>';
    
    // Only show class column if there are multiple classes
    if (count($classes_for_filter) > 1) {
        echo '<th style="padding: 12px 15px; border-bottom: 2px solid #dee2e6; text-align: right; font-weight: 600; color: #495057; white-space: nowrap;">' . 
             esc_html__('Class', 'hello-theme-child') . 
             '</th>';
    }
    
    echo '<th style="padding: 12px 15px; border-bottom: 2px solid #dee2e6; text-align: right; font-weight: 600; color: #495057; white-space: nowrap;">' . 
         esc_html__('Status', 'hello-theme-child') . 
         '</th>';
    echo '</tr>';
    echo '</thead>';
    
    // Table body
    echo '<tbody>';
    
    if ($total_students === 0) {
        echo '<tr><td colspan="' . (count($classes_for_filter) > 1 ? '5' : '4') . '" style="padding: 20px; text-align: center; color: #6c757d; font-style: italic;">' . 
             esc_html__('No students found in any of your classes.', 'hello-theme-child') . 
             '</td></tr>';
    } else {
        $row_counter = 0;
        
        foreach ($class_students as $class_id => $students) {
            $class_label = isset($classes_for_filter[$class_id]) ? $classes_for_filter[$class_id] : __('Class', 'hello-theme-child') . ' #' . $class_id;
            
            foreach ($students as $student) {
                $row_counter++;
                $status = !empty($student->status) ? esc_html($student->status) : esc_html__('Active', 'hello-theme-child');
                $user = get_user_by('id', $student->wp_user_id);
                $username = $user ? esc_html($user->user_login) : esc_html__('(no username)', 'hello-theme-child');
                $email = $user ? esc_html($user->user_email) : esc_html__('(no email)', 'hello-theme-child');
                $name = !empty($student->name) ? esc_html($student->name) : esc_html__('(no name)', 'hello-theme-child');
                $row_class = 'class-' . intval($class_id) . ($row_counter % 2 === 0 ? ' even' : ' odd');
                
                // Add hover effect class
                $row_class .= ' student-row';
                
                echo '<tr class="' . esc_attr($row_class) . '" style="border-bottom: 1px solid #e9ecef; transition: background-color 0.2s ease;">';
                
                // Student name with avatar
                echo '<td style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; position: relative;">';
                if ($user) {
                    echo get_avatar($user->ID, 32, '', '', array('class' => 'avatar avatar-32 photo', 'style' => 'width: 32px; height: 32px; border-radius: 50%; margin-left: 10px; vertical-align: middle;'));
                }
                echo '<span style="font-weight: 500; color: #212529;">' . $name . '</span>';
                echo '</td>';
                
                // Username
                echo '<td style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; direction: ltr; text-align: right;">';
                echo '<code style="background: #f1f3f5; padding: 2px 5px; border-radius: 3px; font-size: 0.9em;">' . $username . '</code>';
                echo '</td>';
                
                // Email with mailto link
                echo '<td style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle;">';
                if ($user && !empty($user->user_email)) {
                    echo '<a href="mailto:' . esc_attr($user->user_email) . '" style="color: #0066cc; text-decoration: none;" title="' . esc_attr__('Send email', 'hello-theme-child') . '">' . $email . '</a>';
                } else {
                    echo '<span style="color: #6c757d; font-style: italic;">' . $email . '</span>';
                }
                echo '</td>';
                
                // Class (only if multiple classes)
                if (count($classes_for_filter) > 1) {
                    echo '<td style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle;">';
                    echo '<span class="class-badge" style="display: inline-block; padding: 3px 8px; background: #e9ecef; border-radius: 12px; font-size: 0.8em; color: #495057;">' . 
                         $class_label . 
                         '</span>';
                    echo '</td>';
                }
                
                // Status with color coding
                $status_class = strtolower($status) === 'active' ? 'status-active' : 'status-inactive';
                echo '<td style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle;">';
                echo '<span class="status-badge ' . $status_class . '" style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 500; ' . 
                     (strtolower($status) === 'active' ? 'background-color: #d4edda; color: #155724;' : 'background-color: #f8d7da; color: #721c24;') . '">' . 
                     $status . 
                     '</span>';
                echo '</td>';
                
                echo '</tr>';
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // .teacher-students-list

    // Add export button and actions
    if ($total_students > 0) {
        echo '<div class="teacher-students-actions" style="margin-top: 25px; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">';

        // Export to CSV button
        echo '<button id="export-students-csv" class="button button-primary" style="margin-right: 10px;">';
        echo '<span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>';
        echo esc_html__('Export to CSV', 'hello-theme-child');
        echo '</button>';

        // Print button
        echo '<button id="print-students" class="button" style="margin-right: 10px;">';
        echo '<span class="dashicons dashicons-printer" style="vertical-align: middle; margin-right: 5px;"></span>';
        echo esc_html__('Print List', 'hello-theme-child');
        echo '</button>';

        // Selected count
        echo '<span class="selected-count" style="margin-left: auto; color: #6c757d; font-size: 0.9em;">';
        echo '<span class="count">0</span> ' . esc_html__('selected', 'hello-theme-child');
        echo '</span>';

        echo '</div>'; // .teacher-students-actions

        // Export status indicator
        echo '<div class="export-status" style="display: none; margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #17a2b8; border-radius: 2px;">';
        echo '<span class="dashicons dashicons-update spin" style="vertical-align: middle; margin-right: 5px;"></span>';
        echo '<span class="status-text">' . esc_html__('Preparing export...', 'hello-theme-child') . '</span>';
        echo '</div>';

        // Hidden form for export
        echo '<form id="students-export-form" method="post" style="display: none;">';
        echo '<input type="hidden" name="action" value="export_teacher_students">';
        echo '<input type="hidden" name="class_filter" id="export-class-filter" value="">';
        echo '<input type="hidden" name="teacher_id" value="' . esc_attr($teacher_id) . '">';
        wp_nonce_field('export_teacher_students_' . $teacher_id, '_wpnonce', false);
        echo '</form>';
    }

    echo '</div>'; // .teacher-students-wrapper

    // Enqueue scripts and styles
    wp_enqueue_style('teacher-students-style');
    wp_enqueue_script('teacher-students-script');

    // Localize script with AJAX URL and nonce for security
    wp_localize_script('teacher-students-script', 'teacherStudentsVars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('export_teacher_students_' . $teacher_id),
        'exporting' => __('Exporting students...', 'hello-theme-child'),
        'exported' => __('Export complete. Download will start automatically...', 'hello-theme-child'),
        'error' => __('Error exporting students. Please try again.', 'hello-theme-child'),
        'noStudents' => __('No students found to export.', 'hello-theme-child'),
        'teacherId' => $teacher_id
    ));

    // Add inline styles for print
    /* Print styles */
    ?>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm;
            }

            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #000;
                background: #fff;
            }

            .teacher-students-filter,
            .teacher-students-actions,
            .debug-info,
            .export-status,
            .screen-reader-text {
                display: none !important;
            }

            .teacher-students-header {
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #000;
            }

            .teacher-students-header h2 {
                margin: 0 0 5px 0;
                font-size: 24px;
                color: #000;
            }

            .teacher-students-header p {
                margin: 0;
                font-size: 14px;
                color: #555;
            }

            .teacher-students-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                page-break-inside: auto;
            }

            .teacher-students-table th,
            .teacher-students-table td {
                border: 1px solid #ddd;
                padding: 8px 12px;
                text-align: right;
                vertical-align: middle;
            }

            .teacher-students-table th {
                background-color: #f5f5f5;
                font-weight: bold;
                text-align: right;
            }

            .teacher-students-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .teacher-students-table tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            .status-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 0.9em;
                font-weight: 500;
            }

            .status-active {
                background-color: #d4edda;
                color: #155724;
            }

            .status-inactive {
                background-color: #f8d7da;
                color: #721c24;
            }

            .class-badge {
                display: inline-block;
                padding: 2px 8px;
                background: #e9ecef;
                color: #495057;
                border-radius: 10px;
                font-size: 0.9em;
            }

            /* Print footer */
            .teacher-students-footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #ddd;
                font-size: 11px;
                color: #777;
                text-align: center;
            }

            /* Page breaks */
            .page-break {
                page-break-before: always;
            }

            /* Hide elements */
            .no-print,
            .dashicons,
            .avatar {
                display: none !important;
            }
        }
    </style>

    // JavaScript for filtering and interactivity
    <script>
        jQuery(document).ready(function($) {
            // Initialize variables
            var $table = $('.teacher-students-table');
            var $filter = $('#teacher-students-class-filter');
            var $status = $('.export-status .status-text');
            var $exportForm = $('#students-export-form');
            var $exportClassFilter = $('#export-class-filter');
            var isExporting = false;
            var $statusIcon = $('.export-status .dashicons');

            // Show a status message
            function showStatus(message, type) {
                var colors = {
                    'success': '#155724',
                    'error': '#721c24',
                    'info': '#0c5460',
                    'warning': '#856404'
                };

                var icons = {
                    'success': 'yes-alt',
                    'error': 'warning',
                    'info': 'info',
                    'warning': 'warning'
                };

                // Update status
                $status.text(message);
                $statusIcon
                    .removeClass()
                    .addClass('dashicons dashicons-' + (icons[type] || 'info'))
                    .css('color', colors[type] || colors.info);

                $('.export-status')
                    .css('color', colors[type] || colors.info)
                    .show();

                // Auto-hide after 5 seconds if not an error
                if (type !== 'error') {
                    setTimeout(function() {
                        $status.text('');
                        $statusIcon.removeClass().addClass('dashicons dashicons-info').css('color', '');
                        $('.export-status').css('color', '#6c757d');
                    }, 5000);
                }
            }

            // Update the student count display
            function updateStudentCount(count) {
                var countText = count === 0 ?
                    '<?php echo esc_js(__('No students found', 'hello-theme-child')); ?>' :
                    count + ' ' + (count === 1 ?
                        '<?php echo esc_js(__('student', 'hello-theme-child')); ?>' :
                        '<?php echo esc_js(__('students', 'hello-theme-child')); ?>');

                $('.teacher-students-header p').text(countText);
            }

            // Class filtering
            $filter.on('change', function() {
                var selectedClass = $(this).val();
                var $rows = $table.find('tbody tr');
                var $visibleRows;

                if (selectedClass === 'all') {
                    $rows.fadeIn(200);
                    $visibleRows = $rows.filter(':visible');
                } else {
                    $rows.hide();
                    $visibleRows = $('.' + selectedClass).fadeIn(200);
                }

                // Update student count
                updateStudentCount($visibleRows.length);

                // Store the filter in localStorage
                localStorage.setItem('teacher_students_last_filter', selectedClass);

                // Log for debugging
                console.log('Filtered by: ' + selectedClass + ', visible rows: ' + $visibleRows.length);

                // Show status message
                if (selectedClass === 'all') {
                    showStatus('<?php echo esc_js(__('Showing all students', 'hello-theme-child')); ?>', 'info');
                } else {
                    var className = $filter.find('option:selected').text().split(' (')[0];
                    showStatus(
                        '<?php echo esc_js(__('Showing students from: ', 'hello-theme-child')); ?>' + className,
                        'info'
                    );
                }
            });

            // Initialize count
            updateStudentCount(<?php echo $total_students; ?>);

            // Add row interactivity
            $('.student-row')
                .on('mouseenter', function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css('background-color', '#f8f9fa');
                    }
                })
                .on('mouseleave', function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css('background-color', '');
                    }
                })
                .on('click', function(e) {
                    // Don't trigger for links inside the row
                    if ($(e.target).is('a, button, input, select, textarea')) {
                        return;
                    }

                    var $row = $(this);
                    $row.toggleClass('selected')
                        .find('td')
                        .css('background-color', $row.hasClass('selected') ? '#e2f0ff' : '');

                    // Update selected count
                    var selectedCount = $('.student-row.selected').length;
                    if (selectedCount > 0) {
                        showStatus(
                            selectedCount + ' ' + (selectedCount === 1 ?
                                '<?php echo esc_js(__('student selected', 'hello-theme-child')); ?>' :
                                '<?php echo esc_js(__('students selected', 'hello-theme-child')); ?>'),
                            'info'
                        );
                    } else {
                        showStatus('', 'info');
                    }
                });

            // Handle print button
            $('#print-students-list').on('click', function(e) {
                e.preventDefault();
                window.print();
            });

            // Handle export to CSV
            $('#export-students-csv').on('click', function(e) {
                e.preventDefault();

                // Prevent multiple clicks
                if (isExporting) return;
                isExporting = true;

                var $button = $(this);
                var buttonText = $button.html();

                // Show loading state
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align: middle; margin-right: 5px;"></span>' + teacherStudentsVars.exporting);

                // Show status
                var $exportStatus = $('.export-status');
                $exportStatus.stop(true, true).fadeIn(200);
                $status.text(teacherStudentsVars.exporting);

                // Get current filter value
                var classFilter = $filter.val() || 'all';
                $exportClassFilter.val(classFilter);

                // Submit the form via AJAX
                $.ajax({
                    url: teacherStudentsVars.ajaxurl,
                    type: 'POST',
                    data: $exportForm.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data && response.data.url) {
                            // Update status
                            $status.html(teacherStudentsVars.exported);

                            // Create a temporary link to trigger download
                            var a = document.createElement('a');
                            a.href = response.data.url;
                            a.download = response.data.filename || 'students-export-' + new Date().toISOString().split('T')[0] + '.csv';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);

                            // Reset button after a short delay
                            setTimeout(function() {
                                $button.prop('disabled', false).html(buttonText);
                                $exportStatus.fadeOut(1000);
                                isExporting = false;
                            }, 2000);
                        } else {
                            // Show error
                            var errorMsg = response.data && response.data.message ?
                                response.data.message :
                                teacherStudentsVars.error;
                            $status.html('<span style="color: #dc3545;">' + errorMsg + '</span>');
                            $button.prop('disabled', false).html(buttonText);
                            isExporting = false;

                            // Hide status after 5 seconds
                            setTimeout(function() {
                                $exportStatus.fadeOut(1000);
                            }, 5000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Export error:', error);
                        console.log('Response:', xhr.responseText);
                        $status.html('<span style="color: #dc3545;">' + teacherStudentsVars.error + ' (' + xhr.status + ' ' + status + ')</span>');
                        $button.prop('disabled', false).html(buttonText);
                        isExporting = false;

                        // Hide status after 5 seconds
                        setTimeout(function() {
                            $exportStatus.fadeOut(1000);
                        }, 5000);
        });
        
        // Add keyboard navigation for accessibility
        $table.attr('tabindex', '0')
            .on('keydown', function(e) {
                var $rows = $('.student-row:visible');
                var $focused = $(':focus');
                var currentIndex = $rows.index($focused.closest('tr'));
                
                if (e.key === 'ArrowDown' && currentIndex < $rows.length - 1) {
                    e.preventDefault();
                    $rows.eq(currentIndex + 1).focus();
                } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                    e.preventDefault();
                    $rows.eq(currentIndex - 1).focus();
                } else if ((e.key === 'Enter' || e.key === ' ') && $focused.length) {
                    e.preventDefault();
                    $focused.trigger('click');
                }
            });
            
        // Restore last used filter
        var lastFilter = localStorage.getItem('teacher_students_last_filter');
        if (lastFilter && lastFilter !== 'all' && $filter.find('option[value="' + lastFilter + '"]').length) {
            $filter.val(lastFilter).trigger('change');
        }
        
        // Initialize tooltips
        if ($.fn.tooltip) {
            $('[title]').tooltip({
                position: {
                    my: 'center bottom-10',
                    at: 'center top',
                    using: function(position, feedback) {
                        $(this).css(position);
                        $('<div>')
                            .addClass('arrow')
                            .addClass(feedback.vertical)
                            .addClass(feedback.horizontal)
                            .appendTo(this);
                    }
                }
            });
        }
    });
    </script>
    <?php
    
    return ob_get_clean();
}
add_shortcode('teacher_students', 'hello_theme_teacher_students_shortcode');
