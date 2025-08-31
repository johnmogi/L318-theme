<?php
/**
 * Quiz Average Debug Tool
 * 
 * This mu-plugin adds a debug shortcode to test quiz average calculations
 * Usage: [quiz_debug] or [quiz_debug student_id="123"]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Quiz_Average_Debug {
    
    public function __construct() {
        add_shortcode('quiz_debug', array($this, 'render_debug'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function enqueue_styles() {
        if (is_admin()) return;
        
        wp_add_inline_style('wp-block-library', '
            .quiz-debug-container {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                font-family: monospace;
            }
            .quiz-debug-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            .quiz-debug-table th,
            .quiz-debug-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            .quiz-debug-table th {
                background-color: #f2f2f2;
            }
            .quiz-debug-success { color: green; }
            .quiz-debug-error { color: red; }
            .quiz-debug-info { color: blue; }
        ');
    }
    
    public function render_debug($atts) {
        // Check if user has permission
        if (!current_user_can('manage_options') && !current_user_can('wdm_instructor')) {
            return '<p>אין לך הרשאה לצפות בכלי הדיבוג</p>';
        }
        
        $atts = shortcode_atts(array(
            'student_id' => '',
            'show_tables' => 'true',
            'show_students' => 'true'
        ), $atts);
        
        global $wpdb;
        
        ob_start();
        ?>
        <div class="quiz-debug-container">
            <h3>🔍 כלי דיבוג ממוצע בחינות</h3>
            
            <?php if ($atts['show_tables'] === 'true'): ?>
            <h4>בדיקת טבלאות מסד נתונים</h4>
            <?php
            $tables_to_check = [
                $wpdb->prefix . 'learndash_pro_quiz_statistic' => 'נתוני בחינות מפורטים',
                $wpdb->prefix . 'learndash_pro_quiz_statistic_ref' => 'הפניות לבחינות',
                $wpdb->prefix . 'learndash_user_activity' => 'פעילות משתמשים',
                $wpdb->prefix . 'school_students' => 'תלמידים',
                $wpdb->prefix . 'school_classes' => 'כיתות'
            ];
            
            echo '<table class="quiz-debug-table">';
            echo '<tr><th>טבלה</th><th>תיאור</th><th>סטטוס</th><th>מספר רשומות</th></tr>';
            
            foreach ($tables_to_check as $table => $description) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
                if ($exists) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    echo "<tr><td>$table</td><td>$description</td><td class='quiz-debug-success'>✅ קיימת</td><td>$count</td></tr>";
                } else {
                    echo "<tr><td>$table</td><td>$description</td><td class='quiz-debug-error'>❌ לא קיימת</td><td>-</td></tr>";
                }
            }
            echo '</table>';
            ?>
            <?php endif; ?>
            
            <?php if ($atts['show_students'] === 'true'): ?>
            <h4>תלמידים עם נתוני בחינות</h4>
            <?php
            // Check for students with quiz data in pro_quiz_statistic tables
            $students_pro_quiz = $wpdb->get_results("
                SELECT DISTINCT u.ID, u.display_name, u.user_email,
                       COUNT(DISTINCT ref.statistic_ref_id) as quiz_attempts,
                       COUNT(DISTINCT ref.quiz_post_id) as unique_quizzes
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->prefix}learndash_pro_quiz_statistic_ref ref ON u.ID = ref.user_id
                GROUP BY u.ID
                ORDER BY quiz_attempts DESC
                LIMIT 10
            ");
            
            if (!empty($students_pro_quiz)) {
                echo '<p class="quiz-debug-success">✅ נמצאו תלמידים עם נתוני בחינות בטבלאות pro_quiz_statistic:</p>';
                echo '<table class="quiz-debug-table">';
                echo '<tr><th>ID</th><th>שם</th><th>אימייל</th><th>ניסיונות</th><th>בחינות ייחודיות</th><th>בדיקה</th></tr>';
                
                foreach ($students_pro_quiz as $student) {
                    echo '<tr>';
                    echo "<td>{$student->ID}</td>";
                    echo "<td>{$student->display_name}</td>";
                    echo "<td>{$student->user_email}</td>";
                    echo "<td>{$student->quiz_attempts}</td>";
                    echo "<td>{$student->unique_quizzes}</td>";
                    echo "<td><a href='?quiz_debug_student={$student->ID}'>בדוק חישוב</a></td>";
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="quiz-debug-error">❌ לא נמצאו תלמידים עם נתוני בחינות בטבלאות pro_quiz_statistic</p>';
                
                // Try learndash_user_activity
                $students_activity = $wpdb->get_results("
                    SELECT DISTINCT u.ID, u.display_name, u.user_email,
                           COUNT(ua.activity_id) as activity_count
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->prefix}learndash_user_activity ua ON u.ID = ua.user_id
                    WHERE ua.activity_type = 'quiz' AND ua.activity_status = 1
                    GROUP BY u.ID
                    ORDER BY activity_count DESC
                    LIMIT 10
                ");
                
                if (!empty($students_activity)) {
                    echo '<p class="quiz-debug-info">ℹ️ נמצאו תלמידים עם פעילות בחינות בטבלת learndash_user_activity:</p>';
                    echo '<table class="quiz-debug-table">';
                    echo '<tr><th>ID</th><th>שם</th><th>אימייל</th><th>פעילויות</th><th>בדיקה</th></tr>';
                    
                    foreach ($students_activity as $student) {
                        echo '<tr>';
                        echo "<td>{$student->ID}</td>";
                        echo "<td>{$student->display_name}</td>";
                        echo "<td>{$student->user_email}</td>";
                        echo "<td>{$student->activity_count}</td>";
                        echo "<td><a href='?quiz_debug_student={$student->ID}'>בדוק חישוב</a></td>";
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p class="quiz-debug-error">❌ לא נמצאו תלמידים עם פעילות בחינות גם בטבלת learndash_user_activity</p>';
                }
            }
            ?>
            <?php endif; ?>
            
            <?php
            // Test specific student if requested
            $test_student_id = isset($_GET['quiz_debug_student']) ? intval($_GET['quiz_debug_student']) : '';
            if (!empty($atts['student_id'])) {
                $test_student_id = intval($atts['student_id']);
            }
            
            if ($test_student_id > 0) {
                $this->test_student_calculation($test_student_id);
            }
            ?>
            
            <h4>הוראות שימוש</h4>
            <ul>
                <li>השתמש ב-<code>[quiz_debug]</code> לבדיקה כללית</li>
                <li>השתמש ב-<code>[quiz_debug student_id="123"]</code> לבדיקת תלמיד ספציפי</li>
                <li>השתמש ב-<code>[quiz_debug show_tables="false"]</code> להסתרת בדיקת טבלאות</li>
            </ul>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    private function test_student_calculation($student_id) {
        global $wpdb;
        
        $student = get_user_by('ID', $student_id);
        if (!$student) {
            echo "<p class='quiz-debug-error'>❌ תלמיד עם ID $student_id לא נמצא</p>";
            return;
        }
        
        echo "<h4>בדיקת חישוב עבור: {$student->display_name} (ID: $student_id)</h4>";
        
        // Test the Simple Teacher Dashboard calculation
        if (class_exists('Simple_Teacher_Dashboard')) {
            $dashboard = new Simple_Teacher_Dashboard();
            
            // Use reflection to access private method
            try {
                $reflection = new ReflectionClass($dashboard);
                $method = $reflection->getMethod('get_student_quiz_stats');
                $method->setAccessible(true);
                
                $result = $method->invoke($dashboard, $student_id);
                
                echo '<h5>תוצאת חישוב Simple Teacher Dashboard:</h5>';
                echo '<table class="quiz-debug-table">';
                echo '<tr><th>מדד</th><th>ערך</th></tr>';
                echo "<tr><td>סה\"כ ניסיונות</td><td>{$result['total_attempts']}</td></tr>";
                echo "<tr><td>בחינות ייחודיות</td><td>{$result['unique_quizzes']}</td></tr>";
                echo "<tr><td>אחוז הצלחה כללי</td><td>{$result['overall_success_rate']}%</td></tr>";
                echo "<tr><td>אחוז הצלחה בחינות שהושלמו</td><td>{$result['completed_only_rate']}%</td></tr>";
                echo '</table>';
                
                if ($result['total_attempts'] == 0) {
                    echo "<p class='quiz-debug-error'>❌ לא נמצאו נתוני בחינות עבור תלמיד זה</p>";
                } else {
                    echo "<p class='quiz-debug-success'>✅ נמצאו נתוני בחינות עבור תלמיד זה</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='quiz-debug-error'>❌ שגיאה בגישה למתודת החישוב: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='quiz-debug-error'>❌ מחלקת Simple_Teacher_Dashboard לא נמצאה</p>";
        }
        
        // Show raw data
        echo '<h5>נתונים גולמיים מהמסד נתונים:</h5>';
        
        // Pro quiz data
        $pro_quiz_data = $wpdb->get_results($wpdb->prepare("
            SELECT ref.quiz_post_id, ref.create_time,
                   SUM(stat.points) as earned_points,
                   COUNT(stat.statistic_id) as total_questions
            FROM {$wpdb->prefix}learndash_pro_quiz_statistic_ref ref
            LEFT JOIN {$wpdb->prefix}learndash_pro_quiz_statistic stat ON ref.statistic_ref_id = stat.statistic_ref_id
            WHERE ref.user_id = %d
            GROUP BY ref.statistic_ref_id
            ORDER BY ref.create_time DESC
            LIMIT 5
        ", $student_id));
        
        if (!empty($pro_quiz_data)) {
            echo '<h6>נתוני Pro Quiz Statistic (5 אחרונים):</h6>';
            echo '<table class="quiz-debug-table">';
            echo '<tr><th>ID בחינה</th><th>תאריך</th><th>נקודות שהושגו</th><th>סה"כ שאלות</th><th>אחוז</th></tr>';
            
            foreach ($pro_quiz_data as $quiz) {
                $percentage = $quiz->total_questions > 0 ? round(($quiz->earned_points / $quiz->total_questions) * 100, 1) : 0;
                echo '<tr>';
                echo "<td>{$quiz->quiz_post_id}</td>";
                echo "<td>{$quiz->create_time}</td>";
                echo "<td>{$quiz->earned_points}</td>";
                echo "<td>{$quiz->total_questions}</td>";
                echo "<td>{$percentage}%</td>";
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo "<p class='quiz-debug-info'>ℹ️ לא נמצאו נתונים בטבלאות pro_quiz_statistic</p>";
            
            // Try activity data
            $activity_data = $wpdb->get_results($wpdb->prepare("
                SELECT post_id, activity_meta, activity_updated, activity_status
                FROM {$wpdb->prefix}learndash_user_activity
                WHERE user_id = %d AND activity_type = 'quiz'
                ORDER BY activity_updated DESC
                LIMIT 5
            ", $student_id));
            
            if (!empty($activity_data)) {
                echo '<h6>נתוני User Activity (5 אחרונים):</h6>';
                echo '<table class="quiz-debug-table">';
                echo '<tr><th>ID בחינה</th><th>סטטוס</th><th>תאריך</th><th>אחוז</th></tr>';
                
                foreach ($activity_data as $activity) {
                    $meta = maybe_unserialize($activity->activity_meta);
                    $percentage = isset($meta['percentage']) ? $meta['percentage'] : 'N/A';
                    echo '<tr>';
                    echo "<td>{$activity->post_id}</td>";
                    echo "<td>{$activity->activity_status}</td>";
                    echo "<td>{$activity->activity_updated}</td>";
                    echo "<td>{$percentage}%</td>";
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo "<p class='quiz-debug-error'>❌ לא נמצאו נתונים גם בטבלת user_activity</p>";
            }
        }
    }
}

// Initialize the debug tool
new Quiz_Average_Debug();
?>
