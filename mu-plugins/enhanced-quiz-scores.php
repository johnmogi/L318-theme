<?php
/**
 * Enhanced Quiz Scores Shortcode
 * 
 * Provides a beautifully designed quiz scores display with:
 * - Course names and quiz averages
 * - Different layouts for promo students (course 898) vs regular students
 * - Modern card design with Hebrew RTL support
 * - Color-coded performance indicators
 */

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Quiz_Scores_Shortcode {
    
    public function __construct() {
        add_shortcode('enhanced_quiz_scores', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue styles for the shortcode
     */
    public function enqueue_styles() {
        // Inline CSS will be added directly to avoid extra HTTP requests
    }
    
    /**
     * Render the enhanced quiz scores shortcode
     */
    public function render_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="enhanced-quiz-scores-error">יש להתחבר כדי לצפות בציוני הבחינות.</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_course_names' => 'true',
            'filter_promo_students' => 'true',
            'target_course_id' => '898',
            'show_averages' => 'true'
        ), $atts);
        
        $current_user_id = get_current_user_id();
        $is_promo_student = $this->is_promo_code_student($current_user_id);
        
        // Get user's courses based on their type
        if ($atts['filter_promo_students'] === 'true' && $is_promo_student) {
            $courses = $this->get_promo_student_courses($current_user_id, intval($atts['target_course_id']));
        } else {
            $courses = $this->get_all_user_courses($current_user_id);
        }
        
        if (empty($courses)) {
            return $this->render_empty_state($is_promo_student);
        }
        
        return $this->render_quiz_scores($courses, $current_user_id, $atts, $is_promo_student);
    }
    
    /**
     * Check if user is a promo code student
     */
    private function is_promo_code_student($user_id) {
        // Method 1: Check promo code metadata
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        
        // Method 2: Check if enrolled only in course 898
        $user_courses = ld_get_mycourses($user_id);
        $course_ids = array();
        foreach ($user_courses as $course) {
            $course_ids[] = $course->ID;
        }
        $only_course_898 = (count($course_ids) == 1 && in_array(898, $course_ids));
        
        // Method 3: Check role
        $current_user = wp_get_current_user();
        $is_promo_role = in_array('student_private', $current_user->roles);
        
        return !empty($promo_access) || $only_course_898 || $is_promo_role;
    }
    
    /**
     * Get courses for promo students (only course 898)
     */
    private function get_promo_student_courses($user_id, $target_course_id) {
        $user_courses = ld_get_mycourses($user_id);
        $filtered_courses = array();
        
        foreach ($user_courses as $course) {
            if ($course->ID == $target_course_id) {
                $filtered_courses[] = $course;
                break;
            }
        }
        
        return $filtered_courses;
    }
    
    /**
     * Get all courses for regular students
     */
    private function get_all_user_courses($user_id) {
        return ld_get_mycourses($user_id);
    }
    
    /**
     * Render empty state
     */
    private function render_empty_state($is_promo_student) {
        $message = $is_promo_student ? 
            'לא נמצאו בחינות עבור הקורס שלך.' : 
            'לא נמצאו בחינות עבור הקורסים שלך.';
            
        return '
        <div class="enhanced-quiz-scores-container">
            <div class="enhanced-quiz-scores-header">
                <h3 class="quiz-scores-title">ציוני הבחינות שלי</h3>
            </div>
            <div class="enhanced-quiz-scores-empty">
                <p>' . $message . '</p>
            </div>
        </div>';
    }
    
    /**
     * Render quiz scores display
     */
    private function render_quiz_scores($courses, $user_id, $atts, $is_promo_student) {
        ob_start();
        ?>
        <style>
        <?php echo $this->get_inline_css(); ?>
        </style>
        
        <div class="enhanced-quiz-scores-container">
            <div class="enhanced-quiz-scores-header">
                <h3 class="quiz-scores-title">ציוני הבחינות שלי</h3>
                <div class="quiz-scores-subtitle">
                    <?php if ($is_promo_student): ?>
                        תוצאות הבחינות עבור הקורס שלך
                    <?php else: ?>
                        תוצאות הבחינות לפי קורסים
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="quiz-scores-grid">
                <?php foreach ($courses as $course): ?>
                    <?php $this->render_course_quiz_card($course, $user_id, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual course quiz card
     */
    private function render_course_quiz_card($course, $user_id, $atts) {
        $course_id = $course->ID;
        $course_title = get_the_title($course_id);
        $course_url = get_permalink($course_id);
        
        // Get quiz average and quiz attempts
        $quiz_average = $this->get_course_quiz_average($course_id, $user_id);
        $quiz_attempts = $this->get_course_quiz_attempts($course_id, $user_id);
        
        // Get performance class for styling
        $performance_class = $this->get_performance_class($quiz_average);
        ?>
        
        <div class="quiz-score-card">
            <?php if ($atts['show_course_names'] === 'true'): ?>
            <div class="quiz-card-header">
                <h4 class="course-name">
                    <a href="<?php echo esc_url($course_url); ?>" class="course-link">
                        <?php echo esc_html($course_title); ?>
                    </a>
                </h4>
            </div>
            <?php endif; ?>
            
            <div class="quiz-card-content">
                <?php if ($atts['show_averages'] === 'true' && $quiz_average !== null): ?>
                <div class="quiz-average-section">
                    <div class="average-label">ממוצע בחינות:</div>
                    <div class="average-value <?php echo $performance_class; ?>">
                        <?php echo number_format($quiz_average, 1); ?>%
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($quiz_attempts)): ?>
                <div class="quiz-attempts-section">
                    <div class="attempts-label">בחינות שבוצעו:</div>
                    <div class="attempts-list">
                        <?php foreach ($quiz_attempts as $attempt): ?>
                        <div class="quiz-attempt-item">
                            <div class="attempt-name"><?php echo esc_html($attempt['quiz_name']); ?></div>
                            <div class="attempt-score <?php echo $this->get_performance_class($attempt['percentage']); ?>">
                                <?php echo number_format($attempt['percentage'], 1); ?>%
                            </div>
                            <div class="attempt-details">
                                <?php echo $attempt['correct']; ?>/<?php echo $attempt['total']; ?> נכונות
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-attempts">
                    <p>לא בוצעו בחינות עדיין</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
    }
    

    
    /**
     * Get detailed quiz attempts for a course
     */
    private function get_course_quiz_attempts($course_id, $user_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT p.post_title as quiz_name,
                   ua.activity_meta_value as percentage,
                   ua.activity_started,
                   ua.activity_completed
            FROM {$wpdb->prefix}learndash_user_activity ua
            INNER JOIN {$wpdb->posts} p ON ua.post_id = p.ID
            WHERE ua.user_id = %d 
            AND p.post_parent = %d
            AND ua.activity_type = 'quiz'
            AND ua.activity_meta_key = 'percentage'
            AND p.post_type = 'sfwd-quiz'
            AND p.post_status = 'publish'
            ORDER BY ua.activity_completed DESC
            LIMIT 10
        ", $user_id, $course_id);
        
        $attempts = $wpdb->get_results($query);
        $formatted_attempts = array();
        
        foreach ($attempts as $attempt) {
            $percentage = floatval($attempt->percentage);
            
            // Try to get more detailed score info
            $score_query = $wpdb->prepare("
                SELECT activity_meta_value
                FROM {$wpdb->prefix}learndash_user_activity
                WHERE user_id = %d 
                AND post_id = (SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_parent = %d)
                AND activity_type = 'quiz'
                AND activity_meta_key = 'score'
                AND activity_completed = %s
                LIMIT 1
            ", $user_id, $attempt->quiz_name, $course_id, $attempt->activity_completed);
            
            $score_info = $wpdb->get_var($score_query);
            $correct = 0;
            $total = 0;
            
            if ($score_info && strpos($score_info, '/') !== false) {
                list($correct, $total) = explode('/', $score_info);
                $correct = intval($correct);
                $total = intval($total);
            }
            
            $formatted_attempts[] = array(
                'quiz_name' => $attempt->quiz_name,
                'percentage' => $percentage,
                'correct' => $correct,
                'total' => $total,
                'date' => $attempt->activity_completed
            );
        }
        
        return $formatted_attempts;
    }
    
    /**
     * Get performance class for color coding
     */
    private function get_performance_class($percentage) {
        if ($percentage === null) return 'no-data';
        
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
        .enhanced-quiz-scores-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            direction: rtl;
            text-align: right;
        }
        
        .enhanced-quiz-scores-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .quiz-scores-title {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .quiz-scores-subtitle {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .quiz-scores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .quiz-score-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e1e5e9;
        }
        
        .quiz-score-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        
        .quiz-card-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 20px;
            color: white;
        }
        
        .course-name {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .course-link {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .course-link:hover {
            opacity: 0.8;
            text-decoration: underline;
        }
        
        .quiz-card-content {
            padding: 25px;
        }
        
        .quiz-average-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 4px solid #667eea;
        }
        
        .average-label {
            font-weight: 600;
            font-size: 16px;
            color: #495057;
        }
        
        .average-value {
            font-size: 24px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
        }
        
        .average-value.excellent { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .average-value.good { background: linear-gradient(135deg, #2196F3, #1976D2); }
        .average-value.average { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .average-value.needs-improvement { background: linear-gradient(135deg, #f44336, #d32f2f); }
        .average-value.no-data { background: linear-gradient(135deg, #9E9E9E, #757575); }
        
        .quiz-attempts-section {
            margin-top: 20px;
        }
        
        .attempts-label {
            font-weight: 600;
            font-size: 16px;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .attempts-list {
            space-y: 10px;
        }
        
        .quiz-attempt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }
        
        .quiz-attempt-item:hover {
            background: #e9ecef;
        }
        
        .attempt-name {
            font-weight: 500;
            color: #495057;
            flex: 1;
            margin-left: 15px;
        }
        
        .attempt-score {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 12px;
            color: white;
            font-size: 14px;
        }
        
        .attempt-details {
            font-size: 12px;
            color: #6c757d;
            margin-right: 10px;
        }
        
        .no-attempts {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }
        
        .enhanced-quiz-scores-empty {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .enhanced-quiz-scores-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .quiz-scores-grid {
                grid-template-columns: 1fr;
            }
            
            .quiz-attempt-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .attempt-name {
                margin-left: 0;
            }
            
            .quiz-average-section {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
        ';
    }
    
    /**
     * Get course quiz average for user using the working logic from teacher dashboard
     */
    private function get_course_quiz_average($course_id, $user_id) {
        global $wpdb;
        
        // Debug logging
        error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Getting quiz average for user {$user_id}, course {$course_id}");
        
        // Method 1: Use the working logic from teacher dashboard - Pro Quiz tables
        $query = "
            SELECT 
                COUNT(ref.statistic_ref_id) as total_attempts,
                COUNT(DISTINCT ref.quiz_post_id) as unique_quizzes,
                -- Overall: includes all attempts
                COALESCE(ROUND(AVG(
                    CASE 
                        WHEN quiz_scores.total_questions > 0 
                        THEN (quiz_scores.earned_points / quiz_scores.total_questions) * 100
                        ELSE 0
                    END
                ), 1), 0) as overall_success_rate,
                -- Completed: only attempts with earned_points > 0
                COALESCE(ROUND(AVG(
                    CASE 
                        WHEN quiz_scores.total_questions > 0 AND quiz_scores.earned_points > 0 
                        THEN (quiz_scores.earned_points / quiz_scores.total_questions) * 100
                        ELSE NULL
                    END
                ), 1), 0) as completed_only_rate
            FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref ref
            INNER JOIN (
                SELECT 
                    statistic_ref_id,
                    SUM(points) as earned_points,
                    COUNT(*) as total_questions
                FROM {$wpdb->prefix}learndash_pro_quiz_statistic
                GROUP BY statistic_ref_id
                HAVING COUNT(*) > 0
            ) quiz_scores ON ref.statistic_ref_id = quiz_scores.statistic_ref_id
            INNER JOIN {$wpdb->posts} p ON ref.quiz_post_id = p.ID
            WHERE ref.user_id = %d
            AND p.post_parent = %d
            GROUP BY ref.user_id
        ";
        
        $result = $wpdb->get_row($wpdb->prepare($query, $user_id, $course_id));
        
        error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Pro Quiz query result: " . print_r($result, true));
        
        $success_rate = 0;
        
        if ($result && $result->total_attempts > 0) {
            // Use completed-only rate if available, otherwise overall rate
            $success_rate = $result->completed_only_rate > 0 ? $result->completed_only_rate : $result->overall_success_rate;
            
            error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Using Pro Quiz data - Overall: {$result->overall_success_rate}%, Completed: {$result->completed_only_rate}%, Final: {$success_rate}%");
        } else {
            // Fallback method: Using learndash_user_activity table
            error_log("[QUIZ DEBUG] Enhanced Quiz Scores: No Pro Quiz data, trying learndash_user_activity fallback");
            
            $fallback_query = "
                SELECT 
                    ua.activity_meta,
                    ua.activity_status
                FROM {$wpdb->prefix}learndash_user_activity ua
                INNER JOIN {$wpdb->posts} p ON ua.post_id = p.ID
                WHERE ua.user_id = %d
                AND ua.activity_type = 'quiz'
                AND ua.activity_completed > 0
                AND p.post_parent = %d
                ORDER BY ua.activity_completed DESC
            ";
            
            $activities = $wpdb->get_results($wpdb->prepare($fallback_query, $user_id, $course_id));
            
            error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Found " . count($activities) . " activity records");
            
            $total_score = 0;
            $total_attempts = 0;
            
            foreach ($activities as $activity) {
                $meta = maybe_unserialize($activity->activity_meta);
                $score = null;
                
                if (is_array($meta)) {
                    // Try different meta key variations
                    if (isset($meta['percentage']) && $meta['percentage'] > 0) {
                        $score = floatval($meta['percentage']);
                    } elseif (isset($meta['score']) && $meta['score'] > 0) {
                        $score = floatval($meta['score']);
                    } elseif (isset($meta['points']) && isset($meta['total_points']) && $meta['total_points'] > 0) {
                        $score = ($meta['points'] / $meta['total_points']) * 100;
                    }
                }
                
                if ($score !== null && $score > 0) {
                    $total_score += $score;
                    $total_attempts++;
                    
                    error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Activity score: {$score}%");
                }
            }
            
            if ($total_attempts > 0) {
                $success_rate = round($total_score / $total_attempts, 1);
                
                error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Fallback calculation: {$success_rate}% from {$total_attempts} attempts");
            }
        }
        
        // Return null if no data found (will hide the average section)
        if ($success_rate == 0) {
            error_log("[QUIZ DEBUG] Enhanced Quiz Scores: No quiz data found for user {$user_id}, course {$course_id}");
            return null;
        }
        
        error_log("[QUIZ DEBUG] Enhanced Quiz Scores: Final result for user {$user_id}: {$success_rate}%");
        
        return $success_rate;
    }
}

// Initialize the shortcode
new Enhanced_Quiz_Scores_Shortcode();
