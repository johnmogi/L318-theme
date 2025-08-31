<?php
/**
 * Quiz Diagnostic Admin Page
 * Creates an admin page to diagnose quiz average calculation issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Quiz_Diagnostic_Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('wp_ajax_test_quiz_calculation', array($this, 'ajax_test_quiz_calculation'));
    }
    
    public function add_admin_page() {
        add_management_page(
            'Quiz Diagnostic',
            'Quiz Diagnostic', 
            'manage_options',
            'quiz-diagnostic',
            array($this, 'admin_page_content')
        );
    }
    
    public function admin_page_content() {
        global $wpdb;
        
        echo '<div class="wrap">';
        echo '<h1>Quiz Average Calculation Diagnostic</h1>';
        
        // Check if Simple Teacher Dashboard is active
        if (!class_exists('Simple_Teacher_Dashboard')) {
            echo '<div class="notice notice-error"><p>Simple Teacher Dashboard plugin is not active!</p></div>';
            echo '</div>';
            return;
        }
        
        // Database table checks
        echo '<h2>1. Database Table Status</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Table Name</th><th>Exists</th><th>Record Count</th></tr></thead>';
        echo '<tbody>';
        
        $tables_to_check = array(
            'learndash_pro_quiz_statistic_ref',
            'learndash_pro_quiz_statistic', 
            'learndash_user_activity',
            'school_students',
            'school_classes',
            'school_teacher_students'
        );
        
        foreach ($tables_to_check as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name") : 0;
            
            echo '<tr>';
            echo '<td>' . esc_html($table) . '</td>';
            echo '<td>' . ($exists ? '✅ Yes' : '❌ No') . '</td>';
            echo '<td>' . number_format($count) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Quiz data analysis
        echo '<h2>2. Quiz Data Analysis</h2>';
        
        // Check for quiz posts
        $quiz_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sfwd-quiz' AND post_status = 'publish'");
        echo '<p><strong>Published Quizzes:</strong> ' . number_format($quiz_count) . '</p>';
        
        // Check for quiz attempts in pro_quiz tables
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}learndash_pro_quiz_statistic_ref'")) {
            $quiz_attempts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref");
            echo '<p><strong>Quiz Attempts (Pro Quiz):</strong> ' . number_format($quiz_attempts) . '</p>';
            
            // Show recent attempts
            $recent_attempts = $wpdb->get_results("
                SELECT user_id, quiz_post_id, create_time 
                FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref 
                ORDER BY create_time DESC 
                LIMIT 10
            ");
            
            if ($recent_attempts) {
                echo '<h3>Recent Quiz Attempts (Pro Quiz)</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>User ID</th><th>Quiz Post ID</th><th>Date</th><th>User Name</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($recent_attempts as $attempt) {
                    $user = get_user_by('id', $attempt->user_id);
                    $user_name = $user ? $user->display_name : 'Unknown';
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($attempt->user_id) . '</td>';
                    echo '<td>' . esc_html($attempt->quiz_post_id) . '</td>';
                    echo '<td>' . esc_html($attempt->create_time) . '</td>';
                    echo '<td>' . esc_html($user_name) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
        }
        
        // Check for quiz activity in user_activity
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}learndash_user_activity'")) {
            $activity_count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}learndash_user_activity 
                WHERE activity_type = 'quiz'
            ");
            echo '<p><strong>Quiz Activities (User Activity):</strong> ' . number_format($activity_count) . '</p>';
            
            // Show recent quiz activities
            $recent_activities = $wpdb->get_results("
                SELECT user_id, post_id, activity_updated, activity_status
                FROM {$wpdb->prefix}learndash_user_activity 
                WHERE activity_type = 'quiz'
                ORDER BY activity_updated DESC 
                LIMIT 10
            ");
            
            if ($recent_activities) {
                echo '<h3>Recent Quiz Activities (User Activity)</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>User ID</th><th>Quiz Post ID</th><th>Date</th><th>Status</th><th>User Name</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($recent_activities as $activity) {
                    $user = get_user_by('id', $activity->user_id);
                    $user_name = $user ? $user->display_name : 'Unknown';
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($activity->user_id) . '</td>';
                    echo '<td>' . esc_html($activity->post_id) . '</td>';
                    echo '<td>' . esc_html($activity->activity_updated) . '</td>';
                    echo '<td>' . ($activity->activity_status ? 'Completed' : 'In Progress') . '</td>';
                    echo '<td>' . esc_html($user_name) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
        }
        
        // Student-Teacher relationships
        echo '<h2>3. Student-Teacher Relationships</h2>';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}school_students'")) {
            $student_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}school_students");
            echo '<p><strong>Total Students:</strong> ' . number_format($student_count) . '</p>';
            
            // Show recent students
            $recent_students = $wpdb->get_results("
                SELECT s.id, s.name, s.class_id, c.name as class_name, c.teacher_id
                FROM {$wpdb->prefix}school_students s
                LEFT JOIN {$wpdb->prefix}school_classes c ON s.class_id = c.id
                ORDER BY s.id DESC
                LIMIT 10
            ");
            
            if ($recent_students) {
                echo '<h3>Recent Students</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Teacher ID</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($recent_students as $student) {
                    echo '<tr>';
                    echo '<td>' . esc_html($student->id) . '</td>';
                    echo '<td>' . esc_html($student->name) . '</td>';
                    echo '<td>' . esc_html($student->class_name) . '</td>';
                    echo '<td>' . esc_html($student->teacher_id) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
        }
        
        // Test calculation for specific students
        echo '<h2>4. Test Quiz Calculation</h2>';
        echo '<p>Test the Simple Teacher Dashboard quiz calculation method for specific students:</p>';
        
        // Get some student IDs to test
        $test_students = $wpdb->get_results("
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}learndash_user_activity 
            WHERE activity_type = 'quiz' 
            LIMIT 5
        ");
        
        if (!$test_students) {
            // Try to get from pro_quiz if user_activity is empty
            $test_students = $wpdb->get_results("
                SELECT DISTINCT user_id 
                FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref 
                LIMIT 5
            ");
        }
        
        if ($test_students) {
            echo '<div id="quiz-test-results">';
            foreach ($test_students as $student) {
                $user = get_user_by('id', $student->user_id);
                $user_name = $user ? $user->display_name : 'Unknown';
                
                echo '<h4>Testing Student: ' . esc_html($user_name) . ' (ID: ' . esc_html($student->user_id) . ')</h4>';
                echo '<button class="button" onclick="testQuizCalculation(' . esc_js($student->user_id) . ')">Test Calculation</button>';
                echo '<div id="result-' . esc_attr($student->user_id) . '" style="margin: 10px 0; padding: 10px; background: #f9f9f9; display: none;"></div>';
            }
            echo '</div>';
        } else {
            echo '<p><strong>No students with quiz data found!</strong> This is likely the root cause of the empty averages.</p>';
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Recommendation:</strong> Have a test student take a quiz to generate data, then re-run this diagnostic.';
            echo '</p></div>';
        }
        
        // JavaScript for AJAX testing
        ?>
        <script>
        function testQuizCalculation(studentId) {
            var resultDiv = document.getElementById('result-' + studentId);
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing...';
            
            jQuery.post(ajaxurl, {
                action: 'test_quiz_calculation',
                student_id: studentId,
                nonce: '<?php echo wp_create_nonce('quiz_test_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    resultDiv.innerHTML = '<pre>' + response.data + '</pre>';
                } else {
                    resultDiv.innerHTML = '<span style="color: red;">Error: ' + response.data + '</span>';
                }
            });
        }
        </script>
        <?php
        
        echo '</div>'; // Close wrap
    }
    
    public function ajax_test_quiz_calculation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'quiz_test_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $student_id = intval($_POST['student_id']);
        
        if (!$student_id) {
            wp_send_json_error('Invalid student ID');
            return;
        }
        
        // Test the Simple Teacher Dashboard calculation
        if (class_exists('Simple_Teacher_Dashboard')) {
            $dashboard = new Simple_Teacher_Dashboard();
            
            // Use reflection to access the private method
            $reflection = new ReflectionClass($dashboard);
            $method = $reflection->getMethod('get_student_quiz_stats');
            $method->setAccessible(true);
            
            try {
                $result = $method->invoke($dashboard, $student_id);
                
                $output = "Quiz Statistics for Student ID: $student_id\n";
                $output .= "=====================================\n";
                $output .= "Total Attempts: " . $result['total_attempts'] . "\n";
                $output .= "Unique Quizzes: " . $result['unique_quizzes'] . "\n";
                $output .= "Overall Success Rate: " . $result['overall_success_rate'] . "%\n";
                $output .= "Completed Only Rate: " . $result['completed_only_rate'] . "%\n\n";
                
                // Additional debugging info
                global $wpdb;
                
                // Check pro_quiz data
                $pro_quiz_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref 
                    WHERE user_id = %d
                ", $student_id));
                
                $output .= "Raw Data Analysis:\n";
                $output .= "Pro Quiz Records: $pro_quiz_count\n";
                
                // Check user activity data
                $activity_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}learndash_user_activity 
                    WHERE user_id = %d AND activity_type = 'quiz'
                ", $student_id));
                
                $output .= "User Activity Records: $activity_count\n";
                
                wp_send_json_success($output);
                
            } catch (Exception $e) {
                wp_send_json_error('Error testing calculation: ' . $e->getMessage());
            }
        } else {
            wp_send_json_error('Simple Teacher Dashboard class not found');
        }
    }
}

// Initialize the admin page
new Quiz_Diagnostic_Admin_Page();
