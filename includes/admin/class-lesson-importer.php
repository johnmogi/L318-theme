<?php
/**
 * Class CCR_Lesson_Importer
 * 
 * Handles CSV-based import of LearnDash lessons and topics.
 * Allows bulk creation of course structure with parent-child relationships.
 * 
 * @since 2025-05-13
 */
class CCR_Lesson_Importer {
    /**
     * CSV columns for lesson/topic import/export
     */
    const CSV_COLUMNS = [
        'course_id',
        'course_title',
        'lesson_id',
        'lesson_title',
        'lesson_content',
        'lesson_order',
        'lesson_url',
        'topic_id',
        'topic_title',
        'topic_content',
        'topic_order',
        'topic_url',
        'materials_enabled',
        'materials',
        'video_enabled',
        'video_url',
        'video_shown',
        'video_auto_start',
        'video_show_controls'
    ];

    /**
     * Constructor: hooks into admin actions
     */
    public function __construct() {
        // Add admin menu item
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Handle CSV import
        add_action('admin_post_ccr_import_lessons', [$this, 'handle_import']);
        
        // Provide CSV template download
        add_action('admin_post_ccr_download_lesson_template', [$this, 'download_csv_template']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            __('Lesson Importer', 'hello-child'),
            __('Lesson Importer', 'hello-child'),
            'manage_options',
            'ccr-lesson-importer',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        // Get all courses for the dropdown
        $courses = get_posts([
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        // Include the admin view
        include_once get_stylesheet_directory() . '/includes/admin/views/lesson-importer.php';
    }

    /**
     * Provide CSV template download
     */
    public function download_csv_template() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'hello-child'));
        }

        $filename = 'lesson_import_template.csv';
        $csv_data = implode(',', self::CSV_COLUMNS) . "\n";
        
        // Sample data
        $csv_data .= '1,Driving Course,,Basic Rules,Learn basic driving rules,1,,,,,,,,,1,,,1,https://example.com/video1.mp4,1,1,1\n';
        $csv_data .= '1,Driving Course,101,Basic Rules,Learn basic driving rules,1,https://example.com/lesson1,,,,,,1,,,1,https://example.com/video2.mp4,1,0,1\n';
        $csv_data .= '1,Driving Course,101,Basic Rules,,,,,201,Speed Limits,Understand speed limits,1,https://example.com/topic1,1,,,0,,,,\n';
        $csv_data .= '1,Driving Course,101,Basic Rules,,,,,202,Traffic Signs,Learn traffic signs,2,https://example.com/topic2,1,,,0,,,,\n';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv_data;
        exit;
    }

    /**
     * Handle CSV import
     */
    public function handle_import() {
        if (!current_user_can('manage_options') || !isset($_FILES['csv_file'])) {
            wp_redirect(add_query_arg('import', 'error', wp_get_referer()));
            exit;
        }

        $file = $_FILES['csv_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('import', 'upload_error', wp_get_referer()));
            exit;
        }

        // Parse CSV file
        $csv_data = array_map('str_getcsv', file($file['tmp_name']));
        $headers = array_shift($csv_data);
        $headers = array_map('trim', $headers);
        
        // Map headers to indexes
        $header_map = [];
        foreach (self::CSV_COLUMNS as $column) {
            $index = array_search($column, $headers);
            if ($index !== false) {
                $header_map[$column] = $index;
            }
        }

        // Process each row
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $current_lesson_id = 0;
        $current_course_id = 0;

        foreach ($csv_data as $row_num => $row) {
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Get values from row
                $course_id = $this->get_row_value($row, $header_map, 'course_id');
                $lesson_id = $this->get_row_value($row, $header_map, 'lesson_id');
                $topic_id = $this->get_row_value($row, $header_map, 'topic_id');

                // Process course if specified and different from current
                if (!empty($course_id) && $course_id != $current_course_id) {
                    $current_course_id = $course_id;
                }

                // Process lesson if specified
                if (!empty($lesson_id)) {
                    $current_lesson_id = $this->process_lesson($row, $header_map, $current_course_id, $results);
                }
                // Process topic if specified and we have a valid lesson
                elseif (!empty($topic_id) && $current_lesson_id) {
                    $this->process_topic($row, $header_map, $current_lesson_id, $results);
                } else {
                    $results['skipped']++;
                    $results['errors'][] = sprintf(__('Row %d: Skipped - No valid lesson or topic data', 'hello-child'), $row_num + 2);
                }
            } catch (Exception $e) {
                $results['skipped']++;
                $results['errors'][] = sprintf(__('Row %d: %s', 'hello-child'), $row_num + 2, $e->getMessage());
            }
        }

        // Redirect with results
        $redirect_url = add_query_arg([
            'page' => 'ccr-lesson-importer',
            'imported' => $results['created'] + $results['updated'],
            'created' => $results['created'],
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'errors' => urlencode(implode('|', $results['errors']))
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Process a lesson row
     */
    private function process_lesson($row, $header_map, $course_id, &$results) {
        $lesson_id = $this->get_row_value($row, $header_map, 'lesson_id');
        $title = $this->get_row_value($row, $header_map, 'lesson_title');
        $content = $this->get_row_value($row, $header_map, 'lesson_content');
        $order = $this->get_row_value($row, $header_map, 'lesson_order', 0);
        $materials_enabled = $this->get_row_value($row, $header_map, 'materials_enabled', 0);
        $materials = $this->get_row_value($row, $header_map, 'materials', '');
        $video_enabled = $this->get_row_value($row, $header_map, 'video_enabled', 0);
        $video_url = $this->get_row_value($row, $header_map, 'video_url', '');
        $video_shown = $this->get_row_value($row, $header_map, 'video_shown', 0);
        $video_auto_start = $this->get_row_value($row, $header_map, 'video_auto_start', 0);
        $video_show_controls = $this->get_row_value($row, $header_map, 'video_show_controls', 1);

        // Check if lesson exists
        $lesson = get_post($lesson_id);
        $is_update = false;

        if ($lesson && $lesson->post_type === 'sfwd-lessons') {
            $is_update = true;
            $post_id = $lesson_id;
        } else {
            $post_id = wp_insert_post([
                'ID' => $lesson_id ?: 0,
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'sfwd-lessons',
                'post_status' => 'publish',
                'menu_order' => $order
            ]);
        }

        if (is_wp_error($post_id)) {
            throw new Exception(sprintf(__('Failed to create/update lesson: %s', 'hello-child'), $post_id->get_error_message()));
        }

        // Update post if it's an update
        if ($is_update) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $title,
                'post_content' => $content,
                'menu_order' => $order
            ]);
        }

        // Set course relationship
        update_post_meta($post_id, 'course_id', $course_id);

        // Set lesson meta
        update_post_meta($post_id, 'materials_enabled', $materials_enabled);
        update_post_meta($post_id, 'materials', $materials);
        
        // Set video settings
        update_post_meta($post_id, 'lesson_video_enabled', $video_enabled);
        update_post_meta($post_id, 'lesson_video_url', $video_url);
        update_post_meta($post_id, 'lesson_video_shown', $video_shown);
        update_post_meta($post_id, 'lesson_video_auto_start', $video_auto_start);
        update_post_meta($post_id, 'lesson_video_show_controls', $video_show_controls);

        // Update results
        if ($is_update) {
            $results['updated']++;
        } else {
            $results['created']++;
        }

        return $post_id;
    }

    /**
     * Process a topic row
     */
    private function process_topic($row, $header_map, $lesson_id, &$results) {
        $topic_id = $this->get_row_value($row, $header_map, 'topic_id');
        $title = $this->get_row_value($row, $header_map, 'topic_title');
        $content = $this->get_row_value($row, $header_map, 'topic_content');
        $order = $this->get_row_value($row, $header_map, 'topic_order', 0);
        $materials_enabled = $this->get_row_value($row, $header_map, 'materials_enabled', 0);
        $materials = $this->get_row_value($row, $header_map, 'materials', '');
        $video_enabled = $this->get_row_value($row, $header_map, 'video_enabled', 0);
        $video_url = $this->get_row_value($row, $header_map, 'video_url', '');
        $video_shown = $this->get_row_value($row, $header_map, 'video_shown', 0);
        $video_auto_start = $this->get_row_value($row, $header_map, 'video_auto_start', 0);
        $video_show_controls = $this->get_row_value($row, $header_map, 'video_show_controls', 1);

        // Check if topic exists
        $topic = get_post($topic_id);
        $is_update = false;

        if ($topic && $topic->post_type === 'sfwd-topic') {
            $is_update = true;
            $post_id = $topic_id;
        } else {
            $post_id = wp_insert_post([
                'ID' => $topic_id ?: 0,
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'sfwd-topic',
                'post_status' => 'publish',
                'menu_order' => $order
            ]);
        }

        if (is_wp_error($post_id)) {
            throw new Exception(sprintf(__('Failed to create/update topic: %s', 'hello-child'), $post_id->get_error_message()));
        }

        // Update post if it's an update
        if ($is_update) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $title,
                'post_content' => $content,
                'menu_order' => $order
            ]);
        }

        // Set lesson relationship
        update_post_meta($post_id, 'lesson_id', $lesson_id);
        
        // Get course ID from parent lesson
        $course_id = get_post_meta($lesson_id, 'course_id', true);
        if ($course_id) {
            update_post_meta($post_id, 'course_id', $course_id);
        }

        // Set topic meta
        update_post_meta($post_id, 'materials_enabled', $materials_enabled);
        update_post_meta($post_id, 'materials', $materials);
        
        // Set video settings
        update_post_meta($post_id, 'lesson_video_enabled', $video_enabled);
        update_post_meta($post_id, 'lesson_video_url', $video_url);
        update_post_meta($post_id, 'lesson_video_shown', $video_shown);
        update_post_meta($post_id, 'lesson_video_auto_start', $video_auto_start);
        update_post_meta($post_id, 'lesson_video_show_controls', $video_show_controls);

        // Update results
        if ($is_update) {
            $results['updated']++;
        } else {
            $results['created']++;
        }

        return $post_id;
    }

    /**
     * Helper to get value from row with fallback
     */
    private function get_row_value($row, $header_map, $key, $default = '') {
        if (!isset($header_map[$key]) || !isset($row[$header_map[$key]])) {
            return $default;
        }
        return trim($row[$header_map[$key]]);
    }
}

// Initialize the class
new CCR_Lesson_Importer();
