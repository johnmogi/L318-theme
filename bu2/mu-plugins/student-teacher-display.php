<?php
/**
 * Student Teacher Display
 * 
 * Shows logged-in student their assigned teacher and class information
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Student_Teacher_Display {
    
    /**
     * Initialize the class
     */
    public static function init() {
        add_shortcode('student_teacher_info', array(__CLASS__, 'display_student_teacher_info'));
        add_shortcode('my_teacher', array(__CLASS__, 'display_student_teacher_info')); // Alternative name
    }
    
    /**
     * Display student's teacher and class information
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function display_student_teacher_info($atts = array()) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="student-teacher-info notice notice-warning"><p>' . __('יש להתחבר כדי לראות את המורה שלך', 'school-manager-lite') . '</p></div>';
        }
        
        $current_user_id = get_current_user_id();
        
        // Get student's class and teacher info
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                c.name as class_name, 
                c.description as class_description,
                u.ID as teacher_id,
                u.display_name as teacher_name, 
                u.user_email as teacher_email,
                g.ID as group_id,
                g.post_title as group_name
             FROM {$wpdb->prefix}school_students sc
             JOIN {$wpdb->prefix}school_classes c ON sc.class_id = c.id
             JOIN {$wpdb->users} u ON c.teacher_id = u.ID
             LEFT JOIN {$wpdb->posts} g ON c.group_id = g.ID AND g.post_type = 'groups'
             WHERE sc.wp_user_id = %d",
            $current_user_id
        ));
        
        $atts = shortcode_atts(array(
            'show_class' => 'yes',
            'show_email' => 'no',
            'show_quizzes' => 'yes',
            'quiz_limit' => '10',
            'style' => 'card' // card, list, simple
        ), $atts, 'student_teacher_info');
        
        if (empty($results)) {
            return '<div class="student-teacher-info notice notice-info"><p>' . __('לא נמצא מורה מוקצה עבורך', 'school-manager-lite') . '</p></div>';
        }
        
        return self::render_teacher_info($results, $atts);
    }
    
    /**
     * Get student's teacher information from database
     * 
     * @param int $student_id Student user ID
     * @return array Teacher information
     */
    private static function get_student_teacher_info($student_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT DISTINCT
                t.ID AS teacher_id,
                t.user_login AS teacher_username,
                t.display_name AS teacher_name,
                t.user_email AS teacher_email,
                c.id AS class_id,
                c.name AS class_name,
                c.description AS class_description,
                g.ID AS group_id,
                g.post_title AS group_name
            FROM
                {$wpdb->users} s
            JOIN
                {$wpdb->prefix}school_student_classes sc ON s.ID = sc.student_id
            JOIN
                {$wpdb->prefix}school_classes c ON sc.class_id = c.id
            JOIN 
                {$wpdb->users} t ON c.teacher_id = t.ID
            LEFT JOIN
                {$wpdb->posts} g ON c.group_id = g.ID AND g.post_type = 'groups'
            WHERE
                s.ID = %d
                AND c.teacher_id IS NOT NULL
                AND c.teacher_id != 0
            ORDER BY c.name ASC
        ", $student_id);
        
        $results = $wpdb->get_results($query);
        
        if ($wpdb->last_error) {
            error_log('Student Teacher Display Error: ' . $wpdb->last_error);
            return array();
        }
        
        return $results;
    }
    
    /**
     * Get quizzes created by a specific teacher
     * 
     * @param int $teacher_id Teacher user ID
     * @param int $limit Number of quizzes to retrieve
     * @return array Quiz information
     */
    private static function get_teacher_quizzes($teacher_id, $limit = 10) {
        $args = array(
            'post_type'      => 'sfwd-quiz',
            'posts_per_page' => intval($limit),
            'author'         => $teacher_id,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $quizzes = get_posts($args);
        
        // Add quiz metadata
        foreach ($quizzes as &$quiz) {
            $quiz->quiz_url = get_permalink($quiz->ID);
            $quiz->quiz_date = get_the_date('d/m/Y', $quiz->ID);
        }
        
        return $quizzes;
    }
    
    /**
     * Render teacher information HTML
     * 
     * @param array $teacher_info Teacher data
     * @param array $atts Display attributes
     * @return string HTML output
     */
    private static function render_teacher_info($teacher_info, $atts) {
        if (empty($teacher_info)) {
            return '';
        }
        
        $output = '<div class="student-teacher-info">';
        
        if ($atts['style'] === 'card') {
            $output .= self::render_card_style($teacher_info, $atts);
        } elseif ($atts['style'] === 'list') {
            $output .= self::render_list_style($teacher_info, $atts);
        } else {
            $output .= self::render_simple_style($teacher_info, $atts);
        }
        
        $output .= '</div>';
        
        // Add CSS
        $output .= self::get_css();
        
        return $output;
    }
    
    /**
     * Render card style
     */
    private static function render_card_style($teacher_info, $atts) {
        $output = '<div class="teacher-cards">';
        
        foreach ($teacher_info as $info) {
            $output .= '<div class="teacher-card">';
            $output .= '<div class="teacher-header">';
            $output .= '<h3 class="teacher-name">' . esc_html($info->teacher_name) . '</h3>';
            $output .= '<span class="teacher-title">' . __('המורה שלך', 'school-manager-lite') . '</span>';
            $output .= '</div>';
            
            if ($atts['show_class'] === 'yes') {
                $output .= '<div class="class-info">';
                $output .= '<strong>' . __('כיתה:', 'school-manager-lite') . '</strong> ' . esc_html($info->class_name);
                if (!empty($info->class_description)) {
                    $output .= '<br><small>' . esc_html($info->class_description) . '</small>';
                }
                $output .= '</div>';
            }
            
            if (!empty($info->group_name)) {
                $output .= '<div class="group-info">';
                $output .= '<strong>' . __('קבוצה:', 'school-manager-lite') . '</strong> ' . esc_html($info->group_name);
                $output .= '</div>';
            }
            
            if ($atts['show_email'] === 'yes' && !empty($info->teacher_email)) {
                $output .= '<div class="teacher-contact">';
                $output .= '<strong>' . __('דוא"ל:', 'school-manager-lite') . '</strong> ';
                $output .= '<a href="mailto:' . esc_attr($info->teacher_email) . '">' . esc_html($info->teacher_email) . '</a>';
                $output .= '</div>';
            }
            
            // Show teacher's quizzes
            if ($atts['show_quizzes'] === 'yes') {
                $quizzes = self::get_teacher_quizzes($info->teacher_id, $atts['quiz_limit']);
                if (!empty($quizzes)) {
                    $output .= '<div class="teacher-quizzes">';
                    $output .= '<h4>' . __('בחינות זמינות:', 'school-manager-lite') . '</h4>';
                    $output .= '<ul class="quiz-list">';
                    foreach ($quizzes as $quiz) {
                        $output .= '<li class="quiz-item">';
                        $output .= '<a href="' . esc_url($quiz->quiz_url) . '" class="quiz-link">';
                        $output .= esc_html($quiz->post_title);
                        $output .= '</a>';
                        $output .= '<span class="quiz-date"> (' . esc_html($quiz->quiz_date) . ')</span>';
                        $output .= '</li>';
                    }
                    $output .= '</ul>';
                    $output .= '</div>';
                }
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Render list style
     */
    private static function render_list_style($teacher_info, $atts) {
        $output = '<ul class="teacher-list">';
        
        foreach ($teacher_info as $info) {
            $output .= '<li class="teacher-item">';
            $output .= '<strong>' . esc_html($info->teacher_name) . '</strong>';
            
            if ($atts['show_class'] === 'yes') {
                $output .= ' - ' . __('כיתה:', 'school-manager-lite') . ' ' . esc_html($info->class_name);
            }
            
            if (!empty($info->group_name)) {
                $output .= ' (' . esc_html($info->group_name) . ')';
            }
            
            if ($atts['show_email'] === 'yes' && !empty($info->teacher_email)) {
                $output .= '<br><a href="mailto:' . esc_attr($info->teacher_email) . '">' . esc_html($info->teacher_email) . '</a>';
            }
            
            // Show teacher's quizzes
            if ($atts['show_quizzes'] === 'yes') {
                $quizzes = self::get_teacher_quizzes($info->teacher_id, $atts['quiz_limit']);
                if (!empty($quizzes)) {
                    $output .= '<br><strong>' . __('בחינות:', 'school-manager-lite') . '</strong> ';
                    $quiz_links = array();
                    foreach ($quizzes as $quiz) {
                        $quiz_links[] = '<a href="' . esc_url($quiz->quiz_url) . '">' . esc_html($quiz->post_title) . '</a>';
                    }
                    $output .= implode(', ', $quiz_links);
                }
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        return $output;
    }
    
    /**
     * Render simple style
     */
    private static function render_simple_style($teacher_info, $atts) {
        $output = '<div class="teacher-simple">';
        
        if (count($teacher_info) === 1) {
            $info = $teacher_info[0];
            $output .= '<p><strong>' . __('המורה שלך:', 'school-manager-lite') . '</strong> ' . esc_html($info->teacher_name);
            
            if ($atts['show_class'] === 'yes') {
                $output .= ' - ' . esc_html($info->class_name);
            }
            
            $output .= '</p>';
            
            if ($atts['show_email'] === 'yes' && !empty($info->teacher_email)) {
                $output .= '<p><a href="mailto:' . esc_attr($info->teacher_email) . '">' . esc_html($info->teacher_email) . '</a></p>';
            }
            
            // Show teacher's quizzes
            if ($atts['show_quizzes'] === 'yes') {
                $quizzes = self::get_teacher_quizzes($info->teacher_id, $atts['quiz_limit']);
                if (!empty($quizzes)) {
                    $output .= '<p><strong>' . __('בחינות זמינות:', 'school-manager-lite') . '</strong></p>';
                    $output .= '<ul>';
                    foreach ($quizzes as $quiz) {
                        $output .= '<li><a href="' . esc_url($quiz->quiz_url) . '">' . esc_html($quiz->post_title) . '</a></li>';
                    }
                    $output .= '</ul>';
                }
            }
        } else {
            $output .= '<p><strong>' . __('המורים שלך:', 'school-manager-lite') . '</strong></p>';
            foreach ($teacher_info as $info) {
                $output .= '<p>• ' . esc_html($info->teacher_name);
                if ($atts['show_class'] === 'yes') {
                    $output .= ' (' . esc_html($info->class_name) . ')';
                }
                $output .= '</p>';
                
                // Show teacher's quizzes
                if ($atts['show_quizzes'] === 'yes') {
                    $quizzes = self::get_teacher_quizzes($info->teacher_id, $atts['quiz_limit']);
                    if (!empty($quizzes)) {
                        $output .= '<ul style="margin-right: 20px;">';
                        foreach ($quizzes as $quiz) {
                            $output .= '<li><a href="' . esc_url($quiz->quiz_url) . '">' . esc_html($quiz->post_title) . '</a></li>';
                        }
                        $output .= '</ul>';
                    }
                }
            }
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Get CSS styles
     */
    private static function get_css() {
        static $css_added = false;
        
        if ($css_added) {
            return '';
        }
        
        $css_added = true;
        
        return '
        <style>
        .student-teacher-info {
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .teacher-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .teacher-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 280px;
        }
        
        .teacher-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0073aa;
        }
        
        .teacher-name {
            margin: 0 0 5px 0;
            color: #0073aa;
            font-size: 1.2em;
        }
        
        .teacher-title {
            color: #666;
            font-size: 0.9em;
            font-weight: normal;
        }
        
        .class-info, .group-info, .teacher-contact {
            margin: 10px 0;
            padding: 8px 0;
        }
        
        .teacher-list {
            list-style: none;
            padding: 0;
        }
        
        .teacher-item {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-right: 4px solid #0073aa;
            border-radius: 4px;
        }
        
        .teacher-simple {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #b3d9ff;
        }
        
        .student-teacher-info a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .student-teacher-info a:hover {
            text-decoration: underline;
        }
        
        /* Quiz Styles */
        .teacher-quizzes {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .teacher-quizzes h4 {
            margin: 0 0 10px 0;
            color: #0073aa;
            font-size: 1em;
        }
        
        .quiz-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .quiz-item {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .quiz-item:last-child {
            border-bottom: none;
        }
        
        .quiz-link {
            font-weight: 500;
            color: #0073aa;
            text-decoration: none;
        }
        
        .quiz-link:hover {
            text-decoration: underline;
            color: #005a87;
        }
        
        .quiz-date {
            color: #666;
            font-size: 0.9em;
            font-weight: normal;
        }
        
        /* RTL Support */
        .student-teacher-info {
            direction: rtl;
            text-align: right;
        }
        
        @media (max-width: 768px) {
            .teacher-cards {
                flex-direction: column;
            }
            
            .teacher-card {
                min-width: auto;
            }
        }
        </style>';
    }
}

// Initialize the class
Student_Teacher_Display::init();

// Log successful loading
error_log('Student Teacher Display: Shortcodes registered successfully');
