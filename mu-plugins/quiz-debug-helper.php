<?php
/**
 * Quiz Debug Helper
 * Temporary script to debug quiz average calculation issues
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add debug shortcode
add_shortcode('quiz_debug', 'quiz_debug_shortcode');

function quiz_debug_shortcode($atts) {
    if (!current_user_can('administrator')) {
        return 'Access denied';
    }
    
    $atts = shortcode_atts(array(
        'user_id' => get_current_user_id(),
        'course_id' => 898
    ), $atts);
    
    $user_id = intval($atts['user_id']);
    $course_id = intval($atts['course_id']);
    
    global $wpdb;
    
    $output = "<div style='background: #f9f9f9; padding: 20px; margin: 20px 0; border: 1px solid #ddd; font-family: monospace;'>";
    $output .= "<h3>Quiz Debug Information</h3>";
    $output .= "<p><strong>User ID:</strong> {$user_id}</p>";
    $output .= "<p><strong>Course ID:</strong> {$course_id}</p>";
    
    // Check Pro Quiz statistic ref table
    $quiz_refs = $wpdb->get_results($wpdb->prepare("
        SELECT ref.*, p.post_title, p.post_parent 
        FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref ref
        LEFT JOIN {$wpdb->posts} p ON ref.quiz_post_id = p.ID
        WHERE ref.user_id = %d
        ORDER BY ref.create_time DESC
        LIMIT 10
    ", $user_id));
    
    $output .= "<h4>Pro Quiz Statistics (learndash_pro_quiz_statistic_ref):</h4>";
    if ($quiz_refs) {
        $output .= "<p>Found " . count($quiz_refs) . " quiz attempts:</p>";
        $output .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $output .= "<tr><th>Quiz ID</th><th>Quiz Title</th><th>Course Parent</th><th>Create Time</th></tr>";
        foreach ($quiz_refs as $ref) {
            $output .= "<tr>";
            $output .= "<td>{$ref->quiz_post_id}</td>";
            $output .= "<td>" . esc_html($ref->post_title) . "</td>";
            $output .= "<td>{$ref->post_parent}</td>";
            $output .= "<td>{$ref->create_time}</td>";
            $output .= "</tr>";
        }
        $output .= "</table>";
    } else {
        $output .= "<p><strong>No quiz attempts found in pro_quiz_statistic_ref table</strong></p>";
    }
    
    // Check LearnDash user activity
    $activities = $wpdb->get_results($wpdb->prepare("
        SELECT ua.*, p.post_title, p.post_parent
        FROM {$wpdb->prefix}learndash_user_activity ua
        LEFT JOIN {$wpdb->posts} p ON ua.post_id = p.ID
        WHERE ua.user_id = %d 
        AND ua.activity_type = 'quiz'
        ORDER BY ua.activity_completed DESC
        LIMIT 10
    ", $user_id));
    
    $output .= "<h4>LearnDash User Activity (quiz type):</h4>";
    if ($activities) {
        $output .= "<p>Found " . count($activities) . " quiz activities:</p>";
        $output .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $output .= "<tr><th>Quiz ID</th><th>Quiz Title</th><th>Course Parent</th><th>Status</th><th>Completed</th></tr>";
        foreach ($activities as $activity) {
            $output .= "<tr>";
            $output .= "<td>{$activity->post_id}</td>";
            $output .= "<td>" . esc_html($activity->post_title) . "</td>";
            $output .= "<td>{$activity->post_parent}</td>";
            $output .= "<td>{$activity->activity_status}</td>";
            $output .= "<td>" . date('Y-m-d H:i:s', $activity->activity_completed) . "</td>";
            $output .= "</tr>";
        }
        $output .= "</table>";
    } else {
        $output .= "<p><strong>No quiz activities found in learndash_user_activity table</strong></p>";
    }
    
    // Check course quizzes
    $course_quizzes = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, post_status
        FROM {$wpdb->posts}
        WHERE post_type = 'sfwd-quiz'
        AND post_parent = %d
        ORDER BY post_date DESC
    ", $course_id));
    
    $output .= "<h4>Quizzes in Course {$course_id}:</h4>";
    if ($course_quizzes) {
        $output .= "<p>Found " . count($course_quizzes) . " quizzes in this course:</p>";
        $output .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $output .= "<tr><th>Quiz ID</th><th>Quiz Title</th><th>Status</th></tr>";
        foreach ($course_quizzes as $quiz) {
            $output .= "<tr>";
            $output .= "<td>{$quiz->ID}</td>";
            $output .= "<td>" . esc_html($quiz->post_title) . "</td>";
            $output .= "<td>{$quiz->post_status}</td>";
            $output .= "</tr>";
        }
        $output .= "</table>";
    } else {
        $output .= "<p><strong>No quizzes found in course {$course_id}</strong></p>";
    }
    
    // Test the actual quiz average function
    $output .= "<h4>Testing Quiz Average Calculation:</h4>";
    if (class_exists('Enhanced_Course_Info_Shortcode')) {
        $reflection = new ReflectionClass('Enhanced_Course_Info_Shortcode');
        if ($reflection->hasMethod('get_course_quiz_average')) {
            $method = $reflection->getMethod('get_course_quiz_average');
            $method->setAccessible(true);
            $instance = new Enhanced_Course_Info_Shortcode();
            $result = $method->invoke($instance, $course_id, $user_id);
            $output .= "<p><strong>Quiz Average Result:</strong> " . print_r($result, true) . "</p>";
        } else {
            $output .= "<p><strong>Method get_course_quiz_average not found</strong></p>";
        }
    } else {
        $output .= "<p><strong>Enhanced_Course_Info_Shortcode class not found</strong></p>";
    }
    
    $output .= "</div>";
    
    return $output;
}
