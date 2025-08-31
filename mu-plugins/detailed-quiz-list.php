<?php
/**
 * Detailed Quiz List Shortcode
 * 
 * Provides a detailed list of quiz attempts similar to learndash_course_progress
 * but with better design and Hebrew localization
 */

if (!defined('ABSPATH')) {
    exit;
}

class Detailed_Quiz_List_Shortcode {
    
    public function __construct() {
        add_shortcode('detailed_quiz_list', array($this, 'render_shortcode'));
    }
    
    /**
     * Render the detailed quiz list shortcode
     */
    public function render_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="detailed-quiz-list-error">יש להתחבר כדי לצפות ברשימת הבחינות.</div>';
        }
        
        $atts = shortcode_atts(array(
            'filter_promo_students' => 'true',
            'target_course_id' => '898',
            'show_course_names' => 'true',
            'per_page' => '20',
            'show_pagination' => 'true'
        ), $atts);
        
        $current_user_id = get_current_user_id();
        $is_promo_student = $this->is_promo_code_student($current_user_id);
        
        // Get quiz attempts
        if ($atts['filter_promo_students'] === 'true' && $is_promo_student) {
            $quiz_attempts = $this->get_quiz_attempts_for_course($current_user_id, intval($atts['target_course_id']));
        } else {
            $quiz_attempts = $this->get_all_quiz_attempts($current_user_id);
        }
        
        if (empty($quiz_attempts)) {
            return $this->render_empty_state($is_promo_student);
        }
        
        return $this->render_quiz_list($quiz_attempts, $atts, $is_promo_student);
    }
    
    /**
     * Check if user is a promo code student
     */
    private function is_promo_code_student($user_id) {
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        $course_ids = learndash_user_get_enrolled_courses($user_id);
        $only_course_898 = (count($course_ids) == 1 && in_array(898, $course_ids));
        $current_user = wp_get_current_user();
        $is_promo_role = in_array('student_private', $current_user->roles);
        
        return !empty($promo_access) || $only_course_898 || $is_promo_role;
    }
    
    /**
     * Get quiz attempts for specific course
     */
    private function get_quiz_attempts_for_course($user_id, $course_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                p.ID as quiz_id,
                p.post_title as quiz_name,
                p.post_parent as course_id,
                ua.activity_started,
                ua.activity_completed,
                ua.activity_meta_value as percentage,
                ua.activity_meta_key,
                ua.activity_id
            FROM {$wpdb->prefix}learndash_user_activity ua
            INNER JOIN {$wpdb->posts} p ON ua.post_id = p.ID
            WHERE ua.user_id = %d 
            AND p.post_parent = %d
            AND ua.activity_type = 'quiz'
            AND p.post_type = 'sfwd-quiz'
            AND p.post_status = 'publish'
            ORDER BY ua.activity_completed DESC
        ", $user_id, $course_id);
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get all quiz attempts for user
     */
    private function get_all_quiz_attempts($user_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                p.ID as quiz_id,
                p.post_title as quiz_name,
                p.post_parent as course_id,
                ua.activity_started,
                ua.activity_completed,
                ua.activity_meta_value as percentage,
                ua.activity_meta_key,
                ua.activity_id
            FROM {$wpdb->prefix}learndash_user_activity ua
            INNER JOIN {$wpdb->posts} p ON ua.post_id = p.ID
            WHERE ua.user_id = %d 
            AND ua.activity_type = 'quiz'
            AND p.post_type = 'sfwd-quiz'
            AND p.post_status = 'publish'
            ORDER BY ua.activity_completed DESC
        ", $user_id);
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get detailed score information for a quiz attempt
     */
    private function get_quiz_score_details($user_id, $quiz_id, $activity_id) {
        global $wpdb;
        
        // Try to get score details from the same activity
        $score_query = $wpdb->prepare("
            SELECT activity_meta_value
            FROM {$wpdb->prefix}learndash_user_activity
            WHERE user_id = %d 
            AND post_id = %d
            AND activity_type = 'quiz'
            AND activity_meta_key = 'score'
            AND activity_id = %d
            LIMIT 1
        ", $user_id, $quiz_id, $activity_id);
        
        $score_info = $wpdb->get_var($score_query);
        
        if ($score_info && strpos($score_info, '/') !== false) {
            list($correct, $total) = explode('/', $score_info);
            return array(
                'correct' => intval($correct),
                'total' => intval($total),
                'score_text' => $score_info
            );
        }
        
        return null;
    }
    
    /**
     * Render empty state
     */
    private function render_empty_state($is_promo_student) {
        $message = $is_promo_student ? 
            'לא ביצעת בחינות עדיין בקורס שלך.' : 
            'לא ביצעת בחינות עדיין.';
            
        return '
        <div class="detailed-quiz-list-container">
            <div class="detailed-quiz-list-header">
                <h4>ביצעת את המבחנים שלהלן:</h4>
            </div>
            <div class="detailed-quiz-list-empty">
                <p>' . $message . '</p>
            </div>
        </div>';
    }
    
    /**
     * Render quiz list
     */
    private function render_quiz_list($quiz_attempts, $atts, $is_promo_student) {
        // Group attempts by quiz and get the latest attempt for each
        $grouped_attempts = array();
        foreach ($quiz_attempts as $attempt) {
            if ($attempt->activity_meta_key === 'percentage') {
                $key = $attempt->quiz_id;
                if (!isset($grouped_attempts[$key]) || 
                    strtotime($attempt->activity_completed) > strtotime($grouped_attempts[$key]->activity_completed)) {
                    $grouped_attempts[$key] = $attempt;
                }
            }
        }
        
        ob_start();
        ?>
        <style>
        <?php echo $this->get_inline_css(); ?>
        </style>
        
        <div class="detailed-quiz-list-container">
            <div class="detailed-quiz-list-header">
                <h4>ביצעת את המבחנים שלהלן:</h4>
            </div>
            
            <div class="quiz-attempts-list">
                <?php foreach ($grouped_attempts as $attempt): ?>
                    <?php $this->render_quiz_attempt($attempt, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual quiz attempt
     */
    private function render_quiz_attempt($attempt, $atts) {
        $percentage = floatval($attempt->percentage);
        $performance_class = $this->get_performance_class($percentage);
        $score_details = $this->get_quiz_score_details(get_current_user_id(), $attempt->quiz_id, $attempt->activity_id);
        
        // Get course name if needed
        $course_name = '';
        if ($atts['show_course_names'] === 'true' && $attempt->course_id) {
            $course_name = get_the_title($attempt->course_id);
        }
        
        // Format date
        $date = date('d/m/Y G:i', strtotime($attempt->activity_completed));
        ?>
        
        <div class="quiz-attempt-item">
            <div class="quiz-attempt-header">
                <h5 class="quiz-name">
                    <?php echo esc_html($attempt->quiz_name); ?>
                    <?php if ($course_name): ?>
                        <span class="course-badge"><?php echo esc_html($course_name); ?></span>
                    <?php endif; ?>
                </h5>
                <div class="quiz-percentage <?php echo $performance_class; ?>">
                    <?php echo number_format($percentage, 0); ?>%
                </div>
            </div>
            
            <div class="quiz-attempt-details">
                <?php if ($score_details): ?>
                <div class="score-info">
                    ניקוד <?php echo $score_details['correct']; ?> מתוך <?php echo $score_details['total']; ?> שאלות
                    <?php if ($score_details['score_text']): ?>
                        . ניקוד: <?php echo $score_details['score_text']; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="attempt-date">
                    ב <?php echo $date; ?>
                </div>
                
                <div class="attempt-actions">
                    <a href="#" class="stats-link">סטטיסטיקה</a>
                    <span class="separator">|</span>
                    <a href="#" class="edit-link">עריכה</a>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Get performance class for color coding
     */
    private function get_performance_class($percentage) {
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 80) return 'good';
        if ($percentage >= 70) return 'average';
        return 'needs-improvement';
    }
    
    /**
     * Get inline CSS for the shortcode
     */
    private function get_inline_css() {
        return '
        .detailed-quiz-list-container {
            max-width: 800px;
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            direction: rtl;
            text-align: right;
        }
        
        .detailed-quiz-list-header h4 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e1e5e9;
            padding-bottom: 10px;
        }
        
        .quiz-attempts-list {
            space-y: 15px;
        }
        
        .quiz-attempt-item {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }
        
        .quiz-attempt-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .quiz-attempt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .quiz-name {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            flex: 1;
            margin-left: 15px;
        }
        
        .course-badge {
            display: inline-block;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 12px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 12px;
            margin-right: 8px;
        }
        
        .quiz-percentage {
            font-size: 18px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            min-width: 60px;
            text-align: center;
        }
        
        .quiz-percentage.excellent { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .quiz-percentage.good { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .quiz-percentage.average { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .quiz-percentage.needs-improvement { background: linear-gradient(135deg, #f44336, #d32f2f); }
        
        .quiz-attempt-details {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .score-info {
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .attempt-date {
            margin-bottom: 8px;
        }
        
        .attempt-actions {
            margin-top: 8px;
        }
        
        .stats-link, .edit-link {
            color: #007cba;
            text-decoration: none;
            font-size: 13px;
        }
        
        .stats-link:hover, .edit-link:hover {
            text-decoration: underline;
        }
        
        .separator {
            margin: 0 8px;
            color: #ccc;
        }
        
        .detailed-quiz-list-empty {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        
        .detailed-quiz-list-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .quiz-attempt-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .quiz-name {
                margin-left: 0;
            }
            
            .quiz-percentage {
                align-self: flex-start;
            }
        }
        ';
    }
}

// Initialize the shortcode
new Detailed_Quiz_List_Shortcode();
