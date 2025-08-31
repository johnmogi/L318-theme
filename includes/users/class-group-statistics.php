<?php
/**
 * Group Statistics Manager
 * 
 * Handles statistics and reporting for groups/classes
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Group_Statistics {
    /**
     * Get statistics for a specific group
     * 
     * @param string $group_id Group ID
     * @return array Group statistics
     */
    public static function get_group_stats($group_id) {
        $stats = array(
            'total_students' => 0,
            'average_score' => 0,
            'completion_rate' => 0,
            'top_performers' => array(),
            'recent_activity' => array()
        );
        
        // Get all users in this group
        $user_ids = Group_Field_Manager::get_users_by_group($group_id);
        $stats['total_students'] = count($user_ids);
        
        if (empty($user_ids)) {
            return $stats;
        }
        
        // Calculate average score
        $total_score = 0;
        $scores = array();
        
        foreach ($user_ids as $user_id) {
            $user_score = self::get_user_average_score($user_id);
            $scores[] = $user_score;
            $total_score += $user_score;
            
            // Track top performers
            $stats['top_performers'][$user_id] = $user_score;
        }
        
        // Sort top performers
        arsort($stats['top_performers']);
        $stats['top_performers'] = array_slice($stats['top_performers'], 0, 5, true);
        
        // Calculate averages
        $stats['average_score'] = $total_score / count($user_ids);
        
        // Get recent activity (last 5 activities)
        $stats['recent_activity'] = self::get_recent_group_activity($group_id, 5);
        
        return $stats;
    }
    
    /**
     * Get user's average score across all quizzes
     */
    private static function get_user_average_score($user_id) {
        if (!function_exists('learndash_get_user_course_progress')) {
            return 0;
        }
        
        $user_courses = learndash_user_get_enrolled_courses($user_id, array(), true);
        $total_score = 0;
        $quiz_count = 0;
        
        foreach ($user_courses as $course_id) {
            $quizzes = learndash_get_course_quiz_list($course_id, $user_id);
            
            if (!empty($quizzes)) {
                foreach ($quizzes as $quiz) {
                    $score = self::get_quiz_score($user_id, $quiz['post']->ID);
                    if ($score !== false) {
                        $total_score += $score;
                        $quiz_count++;
                    }
                }
            }
        }
        
        return $quiz_count > 0 ? round($total_score / $quiz_count, 2) : 0;
    }
    
    /**
     * Get user's score for a specific quiz
     */
    private static function get_quiz_score($user_id, $quiz_id) {
        $quiz_attempts = learndash_get_user_quiz_attempt($user_id, $quiz_id);
        
        if (empty($quiz_attempts)) {
            return false;
        }
        
        // Get the most recent attempt
        $latest_attempt = end($quiz_attempts);
        
        if (isset($latest_attempt['score'])) {
            return floatval($latest_attempt['score']);
        }
        
        return false;
    }
    
    /**
     * Get recent activity for a group
     */
    private static function get_recent_group_activity($group_id, $limit = 5) {
        global $wpdb;
        
        $user_ids = Group_Field_Manager::get_users_by_group($group_id);
        
        if (empty($user_ids)) {
            return array();
        }
        
        $user_ids_placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->usermeta} 
            WHERE user_id IN ($user_ids_placeholders) 
            AND (meta_key LIKE 'completed_%' OR meta_key LIKE 'course_completed_%')
            ORDER BY umeta_id DESC
            LIMIT %d",
            array_merge($user_ids, array($limit))
        );
        
        $activities = $wpdb->get_results($query);
        
        // Format activities
        $formatted = array();
        foreach ($activities as $activity) {
            $user = get_user_by('id', $activity->user_id);
            $post_id = str_replace(array('completed_', 'course_completed_'), '', $activity->meta_key);
            $post = get_post($post_id);
            
            if ($user && $post) {
                $formatted[] = array(
                    'user_id' => $user->ID,
                    'user_name' => $user->display_name,
                    'post_id' => $post_id,
                    'post_title' => $post->post_title,
                    'post_type' => $post->post_type,
                    'timestamp' => $activity->meta_value,
                    'activity_type' => strpos($activity->meta_key, 'course_') !== false ? 'course_completed' : 'quiz_completed'
                );
            }
        }
        
        return $formatted;
    }
    
    /**
     * Render group statistics dashboard
     */
    public static function render_group_dashboard($group_id) {
        $stats = self::get_group_stats($group_id);
        $group_name = self::get_group_name($group_id);
        
        ob_start();
        ?>
        <div class="group-statistics-dashboard">
            <h2><?php echo esc_html(sprintf(__('Class Statistics: %s', 'hello-theme-child'), $group_name)); ?></h2>
            
            <div class="stats-overview">
                <div class="stat-box">
                    <h3><?php _e('Total Students', 'hello-theme-child'); ?></h3>
                    <div class="stat-value"><?php echo esc_html($stats['total_students']); ?></div>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('Average Score', 'hello-theme-child'); ?></h3>
                    <div class="stat-value"><?php echo esc_html($stats['average_score']); ?>%</div>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('Completion Rate', 'hello-theme-child'); ?></h3>
                    <div class="stat-value"><?php echo esc_html($stats['completion_rate']); ?>%</div>
                </div>
            </div>
            
            <div class="top-performers">
                <h3><?php _e('Top Performers', 'hello-theme-child'); ?></h3>
                <ul>
                    <?php foreach ($stats['top_performers'] as $user_id => $score) : 
                        $user = get_user_by('id', $user_id);
                        if ($user) : ?>
                            <li>
                                <span class="user-name"><?php echo esc_html($user->display_name); ?></span>
                                <span class="user-score"><?php echo esc_html($score); ?>%</span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="recent-activity">
                <h3><?php _e('Recent Activity', 'hello-theme-child'); ?></h3>
                <ul>
                    <?php foreach ($stats['recent_activity'] as $activity) : ?>
                        <li>
                            <span class="activity-meta">
                                <?php 
                                printf(
                                    __('%1$s completed %2$s: %3$s', 'hello-theme-child'),
                                    esc_html($activity['user_name']),
                                    $activity['activity_type'] === 'course_completed' ? __('course', 'hello-theme-child') : __('quiz', 'hello-theme-child'),
                                    esc_html($activity['post_title'])
                                );
                                ?>
                            </span>
                            <span class="activity-time">
                                <?php echo human_time_diff($activity['timestamp'], current_time('timestamp')) . ' ' . __('ago', 'hello-theme-child'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get group name by ID
     */
    private static function get_group_name($group_id) {
        $groups = Group_Field_Manager::get_available_groups();
        return isset($groups[$group_id]) ? $groups[$group_id] : __('Unknown Group', 'hello-theme-child');
    }
}
