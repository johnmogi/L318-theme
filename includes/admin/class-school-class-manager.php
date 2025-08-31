<?php
/**
 * School Class Manager admin page
 *
 * @package Hello_Theme_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the classes list table
require_once dirname(__FILE__) . '/class-classes-list-table.php';

/**
 * Class Hello_Theme_Child_School_Class_Manager
 *
 * Implements the Manage School Class admin page with role-based access:
 * - Teachers see only their own students/class
 * - Admins see all teachers/classes/students
 */
class Hello_Theme_Child_School_Class_Manager {

    /**
     * Instance of this class.
     *
     * @var Hello_Theme_Child_School_Class_Manager
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class.
     *
     * @return Hello_Theme_Child_School_Class_Manager
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Re-enabled to provide unified access to class management
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Add AJAX handlers
        add_action( 'wp_ajax_get_teacher_classes', array( $this, 'ajax_get_teacher_classes' ) );
        add_action( 'wp_ajax_get_class_students', array( $this, 'ajax_get_class_students' ) );
        
        // Debug hook to check if class is loaded
        add_action('admin_notices', function() {
            if (isset($_GET['debug_class_manager']) && current_user_can('manage_options')) {
                echo '<div class="notice notice-info"><p>School Class Manager loaded. Current hook: ' . current_filter() . '</p></div>';
            }
        });
    }

    /**
     * Add admin menu item.
     */
    public function add_admin_menu() {
        // Check user roles - always cast to array to avoid errors
        $current_user = wp_get_current_user();
        $roles = (array) $current_user->roles;
        
        $is_admin = current_user_can('manage_options');
        $is_teacher = in_array('school_teacher', $roles);

        // Single unified menu with 'read' capability so both roles can see it
        add_menu_page(
            __('School Classes', 'hello-theme-child'),
            $is_admin ? __('School Classes', 'hello-theme-child') : __('My Classes', 'hello-theme-child'),
            'read', // Basic capability that both admin and teachers have
            'class-management',
            array($this, 'render_admin_page'),
            'dashicons-groups',
            25
        );
    
        
        // Add redirects for compatibility with existing links
        global $pagenow;
        if ('admin.php' === $pagenow && isset($_GET['page'])) {
            if ('manage-school-class' === $_GET['page'] || 
                'class-management-admin' === $_GET['page'] || 
                'class-management-teacher' === $_GET['page']) {
                wp_redirect(admin_url('admin.php?page=class-management'));
                exit;
            }
        }
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Support all class management page hooks - both unified and separate
        if ( strpos($hook, 'school-classes-admin') === false && 
             strpos($hook, 'school-classes-teacher') === false && 
             strpos($hook, 'class-management') === false ) {
            // Debug hook information
            echo '<!-- School Class Manager - Current hook: ' . esc_html($hook) . ' -->';
            return;
        }

        wp_enqueue_style(
            'school-class-manager',
            get_stylesheet_directory_uri() . '/assets/css/admin/school-class-manager.css',
            array(),
            filemtime( get_stylesheet_directory() . '/assets/css/admin/school-class-manager.css' )
        );

        wp_enqueue_script(
            'school-class-manager',
            get_stylesheet_directory_uri() . '/assets/js/admin/school-class-manager.js',
            array( 'jquery' ),
            filemtime( get_stylesheet_directory() . '/assets/js/admin/school-class-manager.js' ),
            true
        );

        // Add student count handler script
        wp_enqueue_script(
            'student-count-handler',
            get_stylesheet_directory_uri() . '/assets/js/admin/student-count-handler.js',
            array( 'jquery', 'school-class-manager' ),
            filemtime( get_stylesheet_directory() . '/assets/js/admin/student-count-handler.js' ),
            true
        );

        wp_localize_script(
            'school-class-manager',
            'schoolClassManagerData',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'school-class-manager' ),
                'i18n'    => array(
                    'loading'        => __( 'Loading...', 'hello-theme-child' ),
                    'noDataAvailable' => __( 'No data available', 'hello-theme-child' ),
                    'selectTeacher'  => __( 'Select a teacher', 'hello-theme-child' ),
                    'selectClass'    => __( 'Select a class', 'hello-theme-child' ),
                )
            )
        );
    }

    /**
     * Render admin page.
     * Legacy function kept for compatibility - redirects to appropriate view
     */
    public function render_admin_page() {
        $current_user = wp_get_current_user();
        $is_admin = current_user_can( 'manage_options' );
        $is_teacher = in_array( 'school_teacher', (array)$current_user->roles );
        
        // If neither admin nor teacher, show error
        if ( !$is_admin && !$is_teacher ) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . 
                __( 'You do not have permission to access this page.', 'hello-theme-child' ) . 
                '</p></div></div>';
            return;
        }

        // Debug information
        echo '<!-- Debug: User ID: ' . esc_html( $current_user->ID ) . 
             ', Is Admin: ' . ($is_admin ? 'yes' : 'no') . 
             ', Is Teacher: ' . ($is_teacher ? 'yes' : 'no') . ' -->';

        if ($is_admin) {
            $this->render_admin_view();
        } elseif ($is_teacher) {
            $this->render_teacher_view();
        }
    }
    
    /**
     * Render admin-specific view.
     */
    public function render_admin_view() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . 
                esc_html__('You do not have permission to access this page.', 'hello-theme-child') . 
                '</p></div></div>';
            return;
        }
        
        // Get all teachers for dropdown
        $teachers = $this->get_teachers();
        
        // Get all classes for selected teacher or all classes if no teacher selected
        $selected_teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
        $classes = $selected_teacher_id ? $this->get_teacher_classes($selected_teacher_id) : $this->get_all_classes();
        
        // Get statistics for selected class or first class
        $selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
        if (!$selected_class_id && !empty($classes)) {
            $selected_class_id = reset($classes)['id'] ?? 0;
        }
        
        // Define view path
        $view_path = dirname(__FILE__) . '/views/admin-class-manager.php';
        
        // Include the admin-specific view
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . 
                esc_html__('Admin view template not found.', 'hello-theme-child') . 
                '</p></div></div>';
        }
    }
    
    /**
     * Render teacher-specific view.
     */
    public function render_teacher_view() {
        // Check permissions
        $current_user = wp_get_current_user();
        $is_teacher = in_array('school_teacher', (array) $current_user->roles);
        
        if (!$is_teacher) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . 
                __( 'You do not have permission to access this page.', 'hello-theme-child' ) . 
                '</p></div></div>';
            return;
        }
        
        // Get teacher's classes
        $teacher_id = $current_user->ID;
        $classes = $this->get_teacher_classes($teacher_id);
        
        // Get statistics for selected class or first class
        $selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
        if (!$selected_class_id && !empty($classes)) {
            $selected_class_id = reset($classes)['id'] ?? 0;
        }
        
        // Include the teacher-specific view
        include_once dirname(__FILE__) . '/views/teacher-class-manager.php';
    }

    // This second declaration was removed to fix the PHP Fatal error of duplicate method declaration

    /**
     * Get all teachers (users with role 'school_teacher').
     *
     * @return WP_User[] Array of teacher user objects.
     */
    private function get_teachers() {
        $args = array(
            'role'    => 'school_teacher',
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => 100, // Limit to avoid performance issues
            'fields'  => array('ID', 'user_login', 'user_email', 'display_name'), // Only get necessary fields
        );
        
        return get_users($args);
        
        return $teachers;
    }

    /**
     * Get all classes (LearnDash groups) with teacher and student information.
     *
     * @return array Array of classes with id, name, teacher info, and student count.
     */
    private function get_all_classes() {
        $classes = array();
        
        // Query all LearnDash groups
        $args = array(
            'post_type'      => 'groups',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => array('publish', 'draft', 'private'),
        );
        
        $groups = get_posts($args);
        
        if (!empty($groups)) {
            foreach ($groups as $group) {
                // Get group leaders (teachers) for this group
                $teacher_names = array();
                $group_leaders = array();
                
                // Try multiple methods to get group leaders
                if (function_exists('learndash_get_groups_administrator_ids')) {
                    $group_leaders = learndash_get_groups_administrator_ids($group->ID);
                }
                
                // Fallback to meta query if function doesn't exist or returns empty
                if (empty($group_leaders)) {
                    $leader_meta = get_post_meta($group->ID, '_ld_group_leaders', true);
                    if (!empty($leader_meta) && is_array($leader_meta)) {
                        $group_leaders = $leader_meta;
                    }
                }
                
                // Another fallback - check for post meta we might have set directly
                if (empty($group_leaders)) {
                    $teacher_id = get_post_meta($group->ID, '_ld_promo_code_teacher_id', true);
                    if (!empty($teacher_id)) {
                        $group_leaders = array($teacher_id);
                    }
                }
                
                error_log('School Class Manager: Group ' . $group->ID . ' has leaders: ' . print_r($group_leaders, true));
                
                if (!empty($group_leaders)) {
                    foreach ($group_leaders as $leader_id) {
                        $teacher = get_user_by('id', $leader_id);
                        if ($teacher) {
                            $teacher_names[] = $teacher->display_name;
                            error_log('School Class Manager: Found teacher: ' . $teacher->display_name);
                        }
                    }
                }
                
                // Get student count for this group
                $student_count = 0;
                if (function_exists('learndash_get_groups_user_ids')) {
                    $student_count = count(learndash_get_groups_user_ids($group->ID));
                }
                
                $classes[] = array(
                    'id'            => $group->ID,
                    'name'          => $group->post_title,
                    'teacher_name'  => !empty($teacher_names) ? implode(', ', $teacher_names) : __('N/A', 'hello-theme-child'),
                    'student_count' => $student_count
                );
            }
        }
        
        // If no groups found, add a default class
        if (empty($classes)) {
            $classes[] = array(
                'id'            => 0,
                'name'          => __('Default Class', 'hello-theme-child'),
                'teacher_name'  => '',
                'student_count' => 0
            );
        }
        
        return $classes;
    }
    
    /**
     * Get classes (LearnDash groups) for a specific teacher.
     *
     * @param int $teacher_id The ID of the teacher.
     * @return array Array of classes with id and name.
     */
    private function get_teacher_classes($teacher_id) {
        $classes = array();
        
        // First, get all groups the teacher is a group leader of
        if (function_exists('learndash_get_administrators_group_ids')) {
            $group_ids = learndash_get_administrators_group_ids($teacher_id, true);
            
            if (!empty($group_ids)) {
                $groups = get_posts(array(
                    'post_type'      => 'groups',
                    'post__in'       => $group_ids,
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => array('publish', 'draft', 'private'),
                ));
                
                foreach ($groups as $group) {
                    $classes[] = array(
                        'id'   => $group->ID,
                        'name' => $group->post_title,
                    );
                }
            }
        }
        
        // If no groups found, return an empty array
        // You might want to add a default class here if needed
        if (empty($classes)) {
            // Uncomment to add a default class if no groups found
            /*
            $classes[] = array(
                'id'   => 0,
                'name' => 'Default Class',
            );
            */
        }
        
        return $classes;
    }

    /**
     * Get students for a class (LearnDash group).
     *
     * @param int $class_id Class/group ID.
     * @return array Array of student user objects.
     */
    private function get_class_students( $class_id ) {
        $students = array();

        if ( !function_exists( 'learndash_get_groups_user_ids' ) || empty( $class_id ) ) {
            return $students;
        }

        $user_ids = learndash_get_groups_user_ids( $class_id );

        if ( !empty( $user_ids ) ) {
            $students = get_users( array(
                'include' => $user_ids,
                'orderby' => 'display_name',
                'order'   => 'ASC',
            ) );
        }

        return $students;
    }

    /**
     * Get statistics for a class.
     *
     * @param int $class_id Class/group ID.
     * @return array Statistics data.
     */
    private function get_class_statistics( $class_id ) {
        // Initialize statistics
        $statistics = array(
            'total_students'      => 0,
            'course_completion'   => 0,
            'average_quiz_score'  => 0,
            'courses'             => array(),
            'promo_codes'         => array(),
        );
        
        if ( empty( $class_id ) ) {
            return $statistics;
        }
        
        // Get promo codes associated with this class
        $promo_codes = $this->get_class_promo_codes($class_id);
        $statistics['promo_codes'] = $promo_codes;

        // Get students
        $students = $this->get_class_students( $class_id );
        $statistics['total_students'] = count( $students );
        
        // If no students, return default statistics
        if ( empty( $students ) ) {
            return $statistics;
        }

        // Get associated courses
        if ( function_exists( 'learndash_group_enrolled_courses' ) ) {
            $course_ids = learndash_group_enrolled_courses( $class_id );
            
            if ( !empty( $course_ids ) ) {
                // Course completion and quiz scores
                $total_completion = 0;
                $total_score = 0;
                $score_count = 0;
                
                foreach ( $course_ids as $course_id ) {
                    $course_title = get_the_title( $course_id );
                    $statistics['courses'][$course_id] = array(
                        'title'      => $course_title,
                        'completion' => 0,
                        'avg_score'  => 0,
                    );
                    
                    $course_completion = 0;
                    $course_score = 0;
                    $course_score_count = 0;
                    
                    foreach ( $students as $student ) {
                        // Course completion
                        if ( function_exists( 'learndash_course_progress' ) ) {
                            $progress = learndash_course_progress( array(
                                'user_id'   => $student->ID,
                                'course_id' => $course_id,
                                'array'     => true
                            ) );
                            
                            if ( !empty( $progress ) && isset( $progress['percentage'] ) ) {
                                $course_completion += $progress['percentage'];
                            }
                        }
                        
                        // Quiz scores
                        if ( function_exists( 'learndash_get_user_course_quiz_scores' ) ) {
                            $quiz_scores = learndash_get_user_course_quiz_scores( $student->ID, $course_id );
                            
                            if ( !empty( $quiz_scores ) ) {
                                foreach ( $quiz_scores as $score ) {
                                    if ( isset( $score['percentage'] ) ) {
                                        $course_score += $score['percentage'];
                                        $course_score_count++;
                                        $total_score += $score['percentage'];
                                        $score_count++;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Calculate averages for this course
                    if ( count( $students ) > 0 ) {
                        $statistics['courses'][$course_id]['completion'] = round( $course_completion / count( $students ), 2 );
                        $total_completion += $statistics['courses'][$course_id]['completion'];
                    }
                    
                    if ( $course_score_count > 0 ) {
                        $statistics['courses'][$course_id]['avg_score'] = round( $course_score / $course_score_count, 2 );
                    }
                }
                
                // Overall averages
                if ( count( $statistics['courses'] ) > 0 ) {
                    $statistics['course_completion'] = round( $total_completion / count( $statistics['courses'] ), 2 );
                }
                
                if ( $score_count > 0 ) {
                    $statistics['average_quiz_score'] = round( $total_score / $score_count, 2 );
                }
            }
        }
        
        return $statistics;
    }

    /**
     * AJAX handler to get teacher classes.
     */
    public function ajax_get_teacher_classes() {
        check_ajax_referer( 'school-class-manager', 'nonce' );
        
        $teacher_id = isset( $_POST['teacher_id'] ) ? intval( $_POST['teacher_id'] ) : 0;
        
        // Check if user has permission
        if ( !current_user_can( 'manage_options' ) && get_current_user_id() !== $teacher_id ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hello-theme-child' ) ) );
        }
        
        $classes = $this->get_teacher_classes( $teacher_id );
        
        wp_send_json_success( array(
            'classes' => $classes,
        ) );
    }

    /**
     * Get promo codes associated with a class/group.
     *
     * @param int $class_id Class/group ID.
     * @return array Promo code data.
     */
    private function get_class_promo_codes( $class_id ) {
        $promo_codes = array();
        
        if ( empty( $class_id ) ) {
            return $promo_codes;
        }
        
        // Check if promo codes class exists
        if ( !class_exists('Hello_Theme_Child_Promo_Codes') ) {
            return $promo_codes;
        }
        
        // Query promo codes associated with this class/group
        $args = array(
            'post_type'      => 'ld_promo_code',  // Changed to match the registered post type
            'posts_per_page' => 100,
            'meta_query'     => array(
                array(
                    'key'   => 'group_id',
                    'value' => $class_id,
                )
            )
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $code_id = get_the_ID();
                $code = get_post_meta( $code_id, 'code', true );
                $status = get_post_meta( $code_id, 'status', true );
                $student_id = get_post_meta( $code_id, 'student_id', true );
                
                $promo_codes[] = array(
                    'id'         => $code_id,
                    'code'       => $code,
                    'status'     => $status,
                    'student_id' => $student_id,
                );
            }
            
            wp_reset_postdata();
        }
        
        return $promo_codes;
    }

    /**
     * AJAX handler to get class students.
     */
    public function ajax_get_class_students() {
        check_ajax_referer( 'school-class-manager', 'nonce' );
        
        $class_id = isset( $_POST['class_id'] ) ? intval( $_POST['class_id'] ) : 0;
        
        // Check if user has permission
        if ( !current_user_can( 'manage_options' ) ) {
            $user_id = get_current_user_id();
            $user_groups = learndash_get_administrators_group_ids( $user_id );
            
            if ( !in_array( $class_id, $user_groups ) ) {
                wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hello-theme-child' ) ) );
            }
        }
        
        $students = $this->get_class_students( $class_id );
        $statistics = $this->get_class_statistics( $class_id );
        
        // Format student data for display
        $formatted_students = array();
        foreach ( $students as $student ) {
            $formatted_students[] = array(
                'id'         => $student->ID,
                'name'       => $student->display_name,
                'email'      => $student->user_email,
                'username'   => $student->user_login,
                'joined'     => get_the_date( get_option( 'date_format' ), $student->ID ),
            );
        }
        
        wp_send_json_success( array(
            'students'   => $formatted_students,
            'statistics' => $statistics,
        ) );
    }
}

// Initialize the class
function hello_theme_child_school_class_manager_init() {
    return Hello_Theme_Child_School_Class_Manager::instance();
}

// Start the class manager
add_action( 'init', 'hello_theme_child_school_class_manager_init', 20 );
