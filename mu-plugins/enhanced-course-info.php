<?php
/**
 * Enhanced Course Info Shortcode
 * 
 * Provides a better designed course info display with:
 * - Course filtering for promo code students
 * - Quiz average calculations
 * - Modern, clean design
 * - Hebrew RTL support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Course_Info_Shortcode {
    
    public function __construct() {
        add_shortcode('enhanced_course_info', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue styles for the shortcode
     */
    public function enqueue_styles() {
        // Register and enqueue our custom style
        wp_register_style('enhanced-course-info', false);
        wp_enqueue_style('enhanced-course-info');
        wp_add_inline_style('enhanced-course-info', $this->get_inline_css());
    }
    
    /**
     * Render the enhanced course info shortcode
     */
    public function render_shortcode($atts) {
        // Debug logging - shortcode execution start
        error_log("[QUIZ DEBUG] Enhanced Course Info: Shortcode render_shortcode called with attributes: " . print_r($atts, true));
        
        // Ensure styles are loaded
        $this->enqueue_styles();
        
        if (!is_user_logged_in()) {
            return '<div class="enhanced-course-info-error">יש להתחבר כדי לצפות במידע על הקורסים.</div>';
        }
        
        // Debug mode for troubleshooting
        $debug_mode = false; // Disabled for clean display
        $debug_output = '';
        
        $atts = shortcode_atts(array(
            'show_progress' => 'true',
            'show_quiz_average' => 'true',
            'filter_promo_students' => 'true',  // Re-enabled with better logic
            'target_course_id' => '898',
            'debug' => 'false'
        ), $atts);
        
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        // Debug collection
        if ($debug_mode) {
            $debug_output .= "<p><strong>User ID:</strong> {$current_user_id}</p>";
            
            // Check user roles
            $debug_output .= "<p><strong>User roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
            
            // Check promo metadata
            $promo_meta = get_user_meta($current_user_id, 'school_promo_course_access', true);
            $debug_output .= "<p><strong>Promo metadata:</strong> " . (empty($promo_meta) ? 'None' : '<pre>' . esc_html(print_r($promo_meta, true)) . '</pre>') . "</p>";
            
            // Check shortcode attributes
            $debug_output .= "<p><strong>Shortcode attributes:</strong> <pre>" . esc_html(print_r($atts, true)) . "</pre></p>";
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== ENHANCED COURSE INFO DEBUG START ===");
            error_log("User ID: {$current_user_id}");
            
            // Check user roles
            error_log("User roles: " . implode(', ', $current_user->roles));
            
            // Check promo metadata
            $promo_meta = get_user_meta($current_user_id, 'school_promo_course_access', true);
            error_log("Promo metadata: " . print_r($promo_meta, true));
            
            // Check raw LearnDash courses
            $raw_courses = ld_get_mycourses($current_user_id);
            error_log("Raw LearnDash courses count: " . count($raw_courses));
            if (!empty($raw_courses)) {
                foreach ($raw_courses as $course) {
                    error_log("Raw course: ID={$course->ID}, Title={$course->post_title}");
                }
            }
            
            // Check shortcode attributes
            error_log("Shortcode attributes: " . print_r($atts, true));
        }
        
        $courses = $this->get_user_courses($current_user_id, $atts);
        
        // Debug collection after filtering
        if ($debug_mode) {
            $debug_output .= "<p><strong>Filtered courses count:</strong> " . count($courses) . "</p>";
            if (!empty($courses)) {
                $debug_output .= "<p><strong>Filtered courses:</strong></p><ul>";
                foreach ($courses as $course) {
                    $debug_output .= "<li>ID={$course->ID}, Title={$course->post_title}</li>";
                }
                $debug_output .= "</ul>";
            }
        }
        
        // More debug after filtering
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Filtered courses count: " . count($courses));
            if (!empty($courses)) {
                foreach ($courses as $course) {
                    error_log("Filtered course: ID={$course->ID}, Title={$course->post_title}");
                }
            }
            error_log("=== ENHANCED COURSE INFO DEBUG END ===");
        }
        
        if (empty($courses)) {
            return '
            <div class="enhanced-course-info-container">
                <div class="enhanced-course-info-header">
                    <h3 class="course-info-title">הקורסים שלי</h3>
                    <div class="course-info-subtitle">לא נמצאו קורסים זמינים</div>
                </div>
                <div class="enhanced-course-info-empty">
                    <p>אין לך קורסים רשומים כרגע.</p>
                    <p>אם יש לך קוד הטבה, אנא הפעל אותו כדי להתחיל ללמוד.</p>
                </div>
            </div>';
        }
        
        // Check if user is promo student for header display
        $is_promo_student = $this->is_promo_code_student($current_user_id);
        $should_filter = $atts['filter_promo_students'] === 'true' && $is_promo_student;
        
        ob_start();
        ?>
        <div class="enhanced-course-info-container">
            <div class="enhanced-course-info-header">
                <?php if ($should_filter && !empty($courses)): ?>
                    <h3 class="course-info-title"><?php echo esc_html(get_the_title($courses[0]->ID)); ?></h3>
                    <div class="course-info-subtitle">הקורס שלך - מידע מפורט על התקדמות ובחינות</div>
                <?php else: ?>
                    <h3 class="course-info-title">הקורסים שלי</h3>
                    <div class="course-info-subtitle">מידע מפורט על התקדמות ובחינות</div>
                <?php endif; ?>
            </div>
            
            <div class="enhanced-course-grid">
                <?php foreach ($courses as $course): ?>
                    <?php $this->render_course_card($course, $current_user_id, $atts); ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($debug_mode): ?>
            <div class="debug-info" style="background: #f0f0f0; padding: 15px; margin-top: 20px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">
                <h4>Debug Information:</h4>
                <?php echo $debug_output; ?>
            </div>
        <?php endif; ?>
        
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get user courses with filtering
     */
    private function get_user_courses($user_id, $atts) {
        // Use the most reliable method to get user courses
        $course_ids = learndash_user_get_enrolled_courses($user_id);
        $user_courses = array();
        
        foreach ($course_ids as $course_id) {
            $course = get_post($course_id);
            if ($course && $course->post_type === 'sfwd-courses' && $course->post_status === 'publish') {
                $user_courses[] = $course;
            }
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Enhanced Course Info Debug - User ID: {$user_id}");
            error_log("Enhanced Course Info Debug - Total courses found: " . count($user_courses));
            if (!empty($user_courses)) {
                foreach ($user_courses as $course) {
                    error_log("Enhanced Course Info Debug - Course: {$course->ID} - {$course->post_title}");
                }
            }
        }
        
        // Check if user came from promo code and should be filtered
        $is_promo_student = $this->is_promo_code_student($user_id);
        $should_filter = $atts['filter_promo_students'] === 'true' && $is_promo_student;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Enhanced Course Info Debug - Is promo student: " . ($is_promo_student ? 'YES' : 'NO'));
            error_log("Enhanced Course Info Debug - Should filter: " . ($should_filter ? 'YES' : 'NO'));
            error_log("Enhanced Course Info Debug - Filter setting: " . $atts['filter_promo_students']);
        }
        
        if ($should_filter) {
            // Only show target course (898) for promo code students
            $target_course_id = intval($atts['target_course_id']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Enhanced Course Info Debug - Filtering to target course: {$target_course_id}");
            }
            
            // Filter to only show the target course
            $filtered_courses = array();
            foreach ($user_courses as $course) {
                if ($course->ID == $target_course_id) {
                    $filtered_courses[] = $course;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Enhanced Course Info Debug - Found target course: {$course->ID}");
                    }
                    break;
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Enhanced Course Info Debug - Filtered courses count: " . count($filtered_courses));
            }
            
            return $filtered_courses;
        }
        
        // Return all user courses (default behavior for regular students)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Enhanced Course Info Debug - Returning all courses for regular student");
        }
        
        return $user_courses;
    }
    
    /**
     * Check if user is a promo code student (should only see course 898)
     */
    private function is_promo_code_student($user_id) {
        // Method 1: Check if user has promo code access metadata
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        
        // Method 2: Check if user is enrolled ONLY in course 898
        $user_courses = ld_get_mycourses($user_id);
        $course_ids = array();
        foreach ($user_courses as $course) {
            $course_ids[] = $course->ID;
        }
        
        // If user is enrolled only in course 898, they're a promo student
        $only_course_898 = (count($course_ids) == 1 && in_array(898, $course_ids));
        
        // Method 3: Check if user came from promo code redemption (has specific role or metadata)
        $is_promo_role = in_array('student_private', wp_get_current_user()->roles);
        
        $is_promo_student = !empty($promo_access) || $only_course_898 || $is_promo_role;
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Enhanced Course Info Debug - Checking promo access for user {$user_id}");
            error_log("Enhanced Course Info Debug - Promo access data: " . print_r($promo_access, true));
            error_log("Enhanced Course Info Debug - User courses: " . implode(', ', $course_ids));
            error_log("Enhanced Course Info Debug - Only course 898: " . ($only_course_898 ? 'YES' : 'NO'));
            error_log("Enhanced Course Info Debug - Has promo role: " . ($is_promo_role ? 'YES' : 'NO'));
            error_log("Enhanced Course Info Debug - Final promo student result: " . ($is_promo_student ? 'YES' : 'NO'));
        }
        
        return $is_promo_student;
    }
    
    /**
     * Render individual course card
     */
    private function render_course_card($course, $user_id, $atts) {
        $course_id = $course->ID;
        $course_title = get_the_title($course_id);
        $course_url = get_permalink($course_id);
        
        // Debug logging for course card rendering
        error_log("[QUIZ DEBUG] Enhanced Course Info: Rendering course card for course {$course_id}, user {$user_id}");
        error_log("[QUIZ DEBUG] Enhanced Course Info: show_quiz_average = " . $atts['show_quiz_average']);
        
        // Get course progress
        $progress = $this->get_course_progress($course_id, $user_id);
        
        // Get quiz average if enabled
        $quiz_average = null;
        if ($atts['show_quiz_average'] === 'true') {
            error_log("[QUIZ DEBUG] Enhanced Course Info: About to call get_course_quiz_average for course {$course_id}, user {$user_id}");
            $quiz_average = $this->get_course_quiz_average($course_id, $user_id);
            error_log("[QUIZ DEBUG] Enhanced Course Info: Quiz average result: " . print_r($quiz_average, true));
        } else {
            error_log("[QUIZ DEBUG] Enhanced Course Info: Quiz average disabled (show_quiz_average = {$atts['show_quiz_average']})");
        }
        
        ?>
        <div class="enhanced-course-card">
            <div class="course-card-header">
                <h4 class="course-card-title">
                    <a href="<?php echo esc_url($course_url); ?>" class="course-title-link">
                        <?php echo esc_html($course_title); ?>
                    </a>
                </h4>
                <div class="course-card-status">
                    <span class="status-badge status-<?php echo esc_attr($progress['status_class']); ?>">
                        <?php echo esc_html($progress['status_text']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($atts['show_progress'] === 'true'): ?>
            <div class="course-card-progress">
                <div class="progress-info">
                    <span class="progress-text">
                        השלמת <strong><?php echo $progress['completed']; ?></strong> מתוך <strong><?php echo $progress['total']; ?></strong> שלבים
                    </span>
                    <span class="progress-percentage"><?php echo $progress['percentage']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress['percentage']; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($quiz_average !== null): ?>
            <div class="course-card-quiz-average">
                <div class="quiz-average-label">ממוצע בחינות:</div>
                <div class="quiz-average-value <?php echo esc_attr($quiz_average['class']); ?>">
                    <?php echo esc_html($quiz_average['display']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="course-card-actions">
                <a href="<?php echo esc_url($course_url); ?>" class="course-action-button primary">
                    המשך לימוד
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get course progress information
     */
    private function get_course_progress($course_id, $user_id) {
        $progress = learndash_user_get_course_progress($user_id, $course_id);
        
        $total = $progress['total'] ?? 0;
        $completed = $progress['completed'] ?? 0;
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        // Determine status
        if ($percentage == 0) {
            $status_class = 'not-started';
            $status_text = 'לא התחיל';
        } elseif ($percentage == 100) {
            $status_class = 'completed';
            $status_text = 'הושלם';
        } else {
            $status_class = 'in-progress';
            $status_text = 'בתהליך';
        }
        
        return array(
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
            'status_class' => $status_class,
            'status_text' => $status_text
        );
    }
    
    /**
     * Get course quiz average for user using the working logic from teacher dashboard
     */
    private function get_course_quiz_average($course_id, $user_id) {
        global $wpdb;
        
        // Debug logging - ALWAYS enabled for troubleshooting
        error_log("[QUIZ DEBUG] Enhanced Course Info: Getting quiz average for user {$user_id}, course {$course_id}");
        
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
        
        error_log("[QUIZ DEBUG] Enhanced Course Info: Pro Quiz query result: " . print_r($result, true));
        
        $success_rate = 0;
        
        if ($result && $result->total_attempts > 0) {
            // Use completed-only rate if available, otherwise overall rate
            $success_rate = $result->completed_only_rate > 0 ? $result->completed_only_rate : $result->overall_success_rate;
            
            error_log("[QUIZ DEBUG] Enhanced Course Info: Using Pro Quiz data - Overall: {$result->overall_success_rate}%, Completed: {$result->completed_only_rate}%, Final: {$success_rate}%");
        } else {
            // Fallback method: Using learndash_user_activity table
            error_log("[QUIZ DEBUG] Enhanced Course Info: No Pro Quiz data, trying learndash_user_activity fallback");
            
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
            
            error_log("[QUIZ DEBUG] Enhanced Course Info: Found " . count($activities) . " activity records");
            
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
                    
                    error_log("[QUIZ DEBUG] Enhanced Course Info: Activity score: {$score}%");
                }
            }
            
            if ($total_attempts > 0) {
                $success_rate = round($total_score / $total_attempts, 1);
                
                error_log("[QUIZ DEBUG] Enhanced Course Info: Fallback calculation: {$success_rate}% from {$total_attempts} attempts");
            }
        }
        
        // Format the result
        if ($success_rate == 0) {
            error_log("[QUIZ DEBUG] Enhanced Course Info: No quiz data found for user {$user_id}, course {$course_id}");
            
            return array(
                'display' => 'אין נתונים',
                'class' => 'no-data'
            );
        }
        
        // Determine class based on score
        if ($success_rate >= 90) {
            $class = 'excellent';
        } elseif ($success_rate >= 80) {
            $class = 'good';
        } elseif ($success_rate >= 70) {
            $class = 'average';
        } else {
            $class = 'needs-improvement';
        }
        
        error_log("[QUIZ DEBUG] Enhanced Course Info: Final result for user {$user_id}: {$success_rate}% (class: {$class})");
        
        return array(
            'display' => $success_rate . '%',
            'class' => $class
        );
    }
    
    private function get_inline_css() {
        return '
        .enhanced-course-info-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            direction: rtl;
            background: transparent;
        }
        
        .enhanced-course-info-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            color: white;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }
        
        .course-info-title {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .course-info-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-weight: 400;
        }
        
        .enhanced-course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        
        .enhanced-course-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            padding: 32px;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        
        .enhanced-course-card::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .enhanced-course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
        }
        
        .course-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .course-card-title {
            margin: 0;
            flex: 1;
            margin-left: 12px;
        }
        
        .course-title-link {
            color: #2d3748;
            text-decoration: none;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.4;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .course-title-link:hover {
            color: #3182ce;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-not-started {
            background: #fed7d7;
            color: #c53030;
        }
        
        .status-in-progress {
            background: #feebc8;
            color: #dd6b20;
        }
        
        .status-completed {
            background: #c6f6d5;
            color: #38a169;
        }
        
        .course-card-progress {
            margin-bottom: 16px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .progress-text {
            font-size: 14px;
            color: #4a5568;
        }
        
        .progress-percentage {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            transition: width 0.8s ease;
            position: relative;
        }
        
        .progress-fill::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .course-card-quiz-average {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .quiz-average-label {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .quiz-average-value {
            font-size: 16px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .quiz-average-value.excellent {
            background: #c6f6d5;
            color: #38a169;
        }
        
        .quiz-average-value.good {
            background: #bee3f8;
            color: #3182ce;
        }
        
        .quiz-average-value.average {
            background: #feebc8;
            color: #dd6b20;
        }
        
        .quiz-average-value.needs-improvement {
            background: #fed7d7;
            color: #c53030;
        }
        
        .quiz-average-value.no-data {
            background: #e2e8f0;
            color: #718096;
        }
        
        .course-card-actions {
            text-align: center;
        }
        
        .course-action-button {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .course-action-button:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .enhanced-course-info-error,
        .enhanced-course-info-empty {
            text-align: center;
            padding: 40px 20px;
            background: #ffffff;
            border-radius: 16px;
            color: #4a5568;
            font-size: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .enhanced-course-info-empty p {
            margin: 12px 0;
            line-height: 1.6;
        }
        
        .enhanced-course-info-empty p:first-child {
            font-weight: 600;
            color: #2d3748;
        }
        
        @media (max-width: 768px) {
            .enhanced-course-grid {
                grid-template-columns: 1fr;
            }
            
            .course-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .course-card-title {
                margin-left: 0;
                margin-bottom: 8px;
            }
        }
        ';
    }
}

// Initialize the shortcode
new Enhanced_Course_Info_Shortcode();
