<?php
/**
 * Teacher Dashboard
 * 
 * Provides functionality for teachers to manage their students and view class statistics.
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Teacher_Dashboard {
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('teacher_dashboard', array($this, 'render_dashboard'));
        add_action('wp_ajax_get_student_data', array($this, 'ajax_get_student_data'));
    }

    /**
     * Initialize
     */
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        global $post;
        
        // Only load on pages with the teacher dashboard
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'teacher_dashboard')) {
            // CSS
            wp_enqueue_style(
                'teacher-dashboard',
                get_stylesheet_directory_uri() . '/assets/css/teacher-dashboard.css',
                array(),
                filemtime(get_stylesheet_directory() . '/assets/css/teacher-dashboard.css')
            );

            // JS
            wp_enqueue_script(
                'teacher-dashboard',
                get_stylesheet_directory_uri() . '/assets/js/teacher-dashboard.js',
                array('jquery'),
                filemtime(get_stylesheet_directory() . '/assets/js/teacher-dashboard.js'),
                true
            );

            // Localize script
            wp_localize_script('teacher-dashboard', 'teacherDashboardData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('teacher_dashboard_nonce'),
                'i18n' => array(
                    'loading' => __('Loading...', 'hello-theme-child'),
                    'error' => __('An error occurred. Please try again.', 'hello-theme-child'),
                    'noStudents' => __('No students found.', 'hello-theme-child'),
                )
            ));
        }
    }


    /**
     * Get teacher's students
     */
    private function get_teacher_students($teacher_id) {
        // Get all groups the teacher is a leader of
        $groups = learndash_get_administrators_group_ids($teacher_id, true);
        
        if (empty($groups)) {
            return array();
        }

        // Get all students in these groups
        $student_query_args = array(
            'meta_query' => array(
                array(
                    'key'     => 'is_student',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'meta_key' => 'last_name',
            'orderby'  => 'meta_value',
            'order'    => 'ASC',
            'fields'   => 'all_with_meta',
        );

        $students = array();
        
        foreach ($groups as $group_id) {
            $group_students = learndash_get_groups_users($group_id, $student_query_args);
            if (!empty($group_students)) {
                foreach ($group_students as $student) {
                    if (!isset($students[$student->ID])) {
                        $students[$student->ID] = $student;
                        $students[$student->ID]->groups = array();
                    }
                    $students[$student->ID]->groups[] = get_the_title($group_id);
                }
            }
        }

        return $students;
    }

    /**
     * Get student progress data
     */
    private function get_student_progress($student_id, $group_id = null) {
        $progress = array(
            'completed_courses' => 0,
            'in_progress_courses' => 0,
            'not_started_courses' => 0,
            'average_score' => 0,
            'last_activity' => '',
            'courses' => array()
        );

        // Get all courses for the group if group_id is provided
        $courses = array();
        if ($group_id) {
            $courses = learndash_group_enrolled_courses($group_id);
        } else {
            $courses = ld_get_mycourses($student_id);
        }

        if (empty($courses)) {
            return $progress;
        }

        $total_score = 0;
        $scored_courses = 0;
        $last_activity = 0;

        foreach ($courses as $course_id) {
            $course_progress = array(
                'id' => $course_id,
                'title' => get_the_title($course_id),
                'status' => 'not_started',
                'score' => 0,
                'last_activity' => 0,
                'url' => get_permalink($course_id)
            );

            // Get course progress
            $course_progress_data = learndash_user_get_course_progress($student_id, $course_id, 'cohort');
            
            if (!empty($course_progress_data)) {
                $completed = $course_progress_data['completed'];
                $total = $course_progress_data['total'];
                $percentage = ($total > 0) ? round(($completed / $total) * 100) : 0;
                
                if ($percentage >= 100) {
                    $progress['completed_courses']++;
                    $course_progress['status'] = 'completed';
                } elseif ($percentage > 0) {
                    $progress['in_progress_courses']++;
                    $course_progress['status'] = 'in_progress';
                    $course_progress['progress'] = $percentage;
                } else {
                    $progress['not_started_courses']++;
                }
                
                // Get quiz scores
                $quizzes = learndash_get_course_quiz_list($course_id, $student_id);
                if (!empty($quizzes)) {
                    $quiz_scores = array();
                    foreach ($quizzes as $quiz) {
                        $score = $this->get_quiz_score($student_id, $quiz['post']->ID);
                        if ($score !== false) {
                            $quiz_scores[] = $score;
                        }
                    }
                    
                    if (!empty($quiz_scores)) {
                        $course_score = array_sum($quiz_scores) / count($quiz_scores);
                        $course_progress['score'] = $course_score;
                        $total_score += $course_score;
                        $scored_courses++;
                    }
                }
                
                // Get last activity
                $activity = $this->get_last_activity($student_id, $course_id);
                if ($activity && $activity > $course_progress['last_activity']) {
                    $course_progress['last_activity'] = $activity;
                    if ($activity > $last_activity) {
                        $last_activity = $activity;
                    }
                }
            } else {
                $progress['not_started_courses']++;
            }
            
            $progress['courses'][] = $course_progress;
        }
        
        // Calculate average score
        if ($scored_courses > 0) {
            $progress['average_score'] = round($total_score / $scored_courses, 1);
        }
        
        // Set last activity
        if ($last_activity > 0) {
            $progress['last_activity'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_activity);
        }
        
        return $progress;
    }

    /**
     * Get quiz score
     */
    private function get_quiz_score($user_id, $quiz_id) {
        $score = 0;
        $quizzes = get_user_meta($user_id, '_sfwd-quizzes', true);
        
        if (!empty($quizzes)) {
            foreach ($quizzes as $quiz) {
                if ($quiz['quiz'] == $quiz_id && isset($quiz['percentage'])) {
                    return $quiz['percentage'];
                }
            }
        }
        
        return false;
    }

    /**
     * Get last activity timestamp
     */
    private function get_last_activity($user_id, $course_id) {
        global $wpdb;
        
        $activity = $wpdb->get_var($wpdb->prepare(
            "SELECT activity_updated FROM {$wpdb->prefix}learndash_user_activity 
            WHERE user_id = %d AND course_id = %d 
            ORDER BY activity_updated DESC LIMIT 1",
            $user_id, $course_id
        ));
        
        return $activity ? strtotime($activity) : 0;
    }

    /**
     * Render the teacher dashboard
     */
    public function render_dashboard($atts) {
        // Check if user is logged in and is a teacher
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the teacher dashboard.', 'hello-theme-child') . '</p>';
        }
        
        $current_user = wp_get_current_user();
        
        // Check if user is a teacher
        if (!in_array('school_teacher', (array) $current_user->roles)) {
            return '<p>' . __('You do not have permission to access this page.', 'hello-theme-child') . '</p>';
        }
        
        // Get teacher's students
        $students = $this->get_teacher_students($current_user->ID);
        
        if (empty($students)) {
            return '<p>' . __('No students found in your classes.', 'hello-theme-child') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        ?>
        <div class="teacher-dashboard">
            <div class="dashboard-header">
                <h1><?php _e('Teacher Dashboard', 'hello-theme-child'); ?></h1>
                <div class="teacher-info">
                    <span class="welcome"><?php echo sprintf(__('Welcome, %s', 'hello-theme-child'), esc_html($current_user->display_name)); ?></span>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="button"><?php _e('Logout', 'hello-theme-child'); ?></a>
                </div>
            </div>
            
            <div class="dashboard-content">
                <div class="dashboard-sidebar">
                    <div class="dashboard-widget">
                        <h3><?php _e('Quick Actions', 'hello-theme-child'); ?></h3>
                        <ul class="quick-actions">
                            <li><a href="#" class="button" id="refresh-stats"><?php _e('Refresh Stats', 'hello-theme-child'); ?></a></li>
                            <li><a href="#" class="button" id="export-data"><?php _e('Export Data', 'hello-theme-child'); ?></a></li>
                            <li><a href="#" class="button" id="message-students"><?php _e('Message Students', 'hello-theme-child'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="dashboard-widget">
                        <h3><?php _e('Class Statistics', 'hello-theme-child'); ?></h3>
                        <div class="class-stats">
                            <div class="stat-item">
                                <span class="stat-value" id="total-students"><?php echo count($students); ?></span>
                                <span class="stat-label"><?php _e('Total Students', 'hello-theme-child'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="avg-score">-</span>
                                <span class="stat-label"><?php _e('Avg. Score', 'hello-theme-child'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="completion-rate">-</span>
                                <span class="stat-label"><?php _e('Completion Rate', 'hello-theme-child'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-main">
                    <div class="dashboard-tabs">
                        <button class="tab-button active" data-tab="students"><?php _e('Students', 'hello-theme-child'); ?></button>
                        <button class="tab-button" data-tab="progress"><?php _e('Progress', 'hello-theme-child'); ?></button>
                        <button class="tab-button" data-tab="reports"><?php _e('Reports', 'hello-theme-child'); ?></button>
                    </div>
                    
                    <div class="tab-content active" id="students-tab">
                        <div class="students-filters">
                            <div class="search-box">
                                <input type="text" id="student-search" placeholder="<?php esc_attr_e('Search students...', 'hello-theme-child'); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="group-filter"><?php _e('Filter by Group:', 'hello-theme-child'); ?></label>
                                <select id="group-filter">
                                    <option value=""><?php _e('All Groups', 'hello-theme-child'); ?></option>
                                    <?php
                                    $groups = array();
                                    foreach ($students as $student) {
                                        if (!empty($student->groups)) {
                                            foreach ($student->groups as $group) {
                                                if (!in_array($group, $groups)) {
                                                    $groups[] = $group;
                                                    echo '<option value="' . esc_attr($group) . '">' . esc_html($group) . '</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="students-list-wrapper">
                            <table class="students-list">
                                <thead>
                                    <tr>
                                        <th class="select-column"><input type="checkbox" id="select-all-students"></th>
                                        <th class="name-column"><?php _e('Name', 'hello-theme-child'); ?></th>
                                        <th class="email-column"><?php _e('Email', 'hello-theme-child'); ?></th>
                                        <th class="groups-column"><?php _e('Groups', 'hello-theme-child'); ?></th>
                                        <th class="progress-column"><?php _e('Progress', 'hello-theme-child'); ?></th>
                                        <th class="score-column"><?php _e('Avg. Score', 'hello-theme-child'); ?></th>
                                        <th class="actions-column"><?php _e('Actions', 'hello-theme-child'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student) : 
                                        $progress = $this->get_student_progress($student->ID);
                                        $groups = !empty($student->groups) ? implode(', ', $student->groups) : '';
                                        $avatar = get_avatar($student->ID, 32);
                                        $display_name = $student->display_name ?: $student->user_login;
                                        $progress_percent = 0;
                                        $avg_score = 0;
                                        
                                        if (!empty($progress['courses'])) {
                                            $total_courses = count($progress['courses']);
                                            $completed = $progress['completed_courses'];
                                            $progress_percent = $total_courses > 0 ? round(($completed / $total_courses) * 100) : 0;
                                            $avg_score = $progress['average_score'];
                                        }
                                    ?>
                                    <tr data-student-id="<?php echo $student->ID; ?>">
                                        <td class="select-column"><input type="checkbox" class="student-checkbox"></td>
                                        <td class="name-column">
                                            <div class="student-avatar"><?php echo $avatar; ?></div>
                                            <div class="student-info">
                                                <span class="student-name"><?php echo esc_html($display_name); ?></span>
                                                <span class="student-username">@<?php echo esc_html($student->user_login); ?></span>
                                            </div>
                                        </td>
                                        <td class="email-column"><?php echo esc_html($student->user_email); ?></td>
                                        <td class="groups-column"><?php echo esc_html($groups); ?></td>
                                        <td class="progress-column">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                                                <span class="progress-text"><?php echo $progress_percent; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="score-column">
                                            <div class="score-display">
                                                <span class="score-value"><?php echo $avg_score; ?>%</span>
                                                <div class="score-bar">
                                                    <div class="score-fill" style="width: <?php echo $avg_score; ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="actions-column">
                                            <div class="action-buttons">
                                                <button class="button view-details" data-student-id="<?php echo $student->ID; ?>">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                                <button class="button message-student" data-student-id="<?php echo $student->ID; ?>">
                                                    <span class="dashicons dashicons-email"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="students-pagination">
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php 
                                    printf(
                                        _n('%s student', '%s students', count($students), 'hello-theme-child'),
                                        number_format_i18n(count($students))
                                    );
                                ?></span>
                                <span class="pagination-links">
                                    <a class="first-page button" href="#">
                                        <span class="screen-reader-text"><?php _e('First page', 'hello-theme-child'); ?></span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" href="#">
                                        <span class="screen-reader-text"><?php _e('Previous page', 'hello-theme-child'); ?></span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                    <span class="paging-input">
                                        <label for="current-page-selector" class="screen-reader-text">
                                            <?php _e('Current Page', 'hello-theme-child'); ?>
                                        </label>
                                        <input class="current-page" id="current-page-selector" type="text" 
                                               name="paged" value="1" size="2" aria-describedby="table-paging">
                                        <span class="tablenav-paging-text">
                                            <?php _e('of', 'hello-theme-child'); ?> <span class="total-pages">1</span>
                                        </span>
                                    </span>
                                    <a class="next-page button" href="#">
                                        <span class="screen-reader-text"><?php _e('Next page', 'hello-theme-child'); ?></span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" href="#">
                                        <span class="screen-reader-text"><?php _e('Last page', 'hello-theme-child'); ?></span>
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="progress-tab">
                        <div class="progress-overview">
                            <h3><?php _e('Class Progress Overview', 'hello-theme-child'); ?></h3>
                            <div class="progress-chart" id="progress-chart">
                                <!-- Chart will be rendered by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="course-progress">
                            <h3><?php _e('Course Progress', 'hello-theme-child'); ?></h3>
                            <div class="course-list">
                                <!-- Course progress will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="reports-tab">
                        <div class="reports-actions">
                            <h3><?php _e('Generate Reports', 'hello-theme-child'); ?></h3>
                            <div class="report-options">
                                <div class="report-option">
                                    <h4><?php _e('Export Student Data', 'hello-theme-child'); ?></h4>
                                    <p><?php _e('Export student information and progress to CSV', 'hello-theme-child'); ?></p>
                                    <button class="button button-primary" id="export-student-data">
                                        <?php _e('Export CSV', 'hello-theme-child'); ?>
                                    </button>
                                </div>
                                
                                <div class="report-option">
                                    <h4><?php _e('Course Completion', 'hello-theme-child'); ?></h4>
                                    <p><?php _e('Generate completion reports for selected courses', 'hello-theme-child'); ?></p>
                                    <button class="button button-primary" id="generate-completion-report">
                                        <?php _e('Generate Report', 'hello-theme-child'); ?>
                                    </button>
                                </div>
                                
                                <div class="report-option">
                                    <h4><?php _e('Quiz Results', 'hello-theme-child'); ?></h4>
                                    <p><?php _e('Export detailed quiz results for analysis', 'hello-theme-child'); ?></p>
                                    <button class="button button-primary" id="export-quiz-results">
                                        <?php _e('Export Results', 'hello-theme-child'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Details Modal -->
        <div id="student-details-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Student Details', 'hello-theme-child'); ?></h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded via AJAX -->
                    <div class="loading"><?php _e('Loading...', 'hello-theme-child'); ?></div>
                </div>
                <div class="modal-footer">
                    <button class="button close-modal"><?php _e('Close', 'hello-theme-child'); ?></button>
                </div>
            </div>
        </div>
        <?php
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting student data
     */
    public function ajax_get_student_data() {
        // Verify nonce
        check_ajax_referer('teacher_dashboard_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_users')) {
            wp_send_json_error(__('Permission denied', 'hello-theme-child'));
        }
        
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        
        if (!$student_id) {
            wp_send_json_error(__('Invalid student ID', 'hello-theme-child'));
        }
        
        // Get student data
        $student = get_userdata($student_id);
        
        if (!$student) {
            wp_send_json_error(__('Student not found', 'hello-theme-child'));
        }
        
        // Get student progress
        $progress = $this->get_student_progress($student_id);
        
        // Prepare response
        $response = array(
            'success' => true,
            'data' => array(
                'id' => $student->ID,
                'name' => $student->display_name,
                'email' => $student->user_email,
                'registered' => date_i18n(get_option('date_format'), strtotime($student->user_registered)),
                'last_login' => get_user_meta($student_id, 'last_login', true) ? 
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), get_user_meta($student_id, 'last_login', true)) : 
                    __('Never', 'hello-theme-child'),
                'progress' => $progress,
                'courses' => array()
            )
        );
        
        // Add course details
        if (!empty($progress['courses'])) {
            foreach ($progress['courses'] as $course) {
                $response['data']['courses'][] = array(
                    'id' => $course['id'],
                    'title' => $course['title'],
                    'status' => $course['status'],
                    'score' => $course['score'],
                    'last_activity' => $course['last_activity'] ? date_i18n(get_option('date_format'), $course['last_activity']) : __('No activity', 'hello-theme-child'),
                    'url' => $course['url']
                );
            }
        }
        
        wp_send_json($response);
    }
}

// Initialize the teacher dashboard
Teacher_Dashboard::instance();
