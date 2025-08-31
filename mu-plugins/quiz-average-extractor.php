<?php
/**
 * Quiz Average Extractor for Course 898 (תעבורתי)
 * Piggybacks on existing quiz progress HTML to calculate and display quiz average
 */

class Quiz_Average_Extractor {
    
    public function __construct() {
        // Hook into WordPress footer to add quiz extraction script
        add_action('wp_footer', array($this, 'output_quiz_script'));
        
        // Hook into WordPress head to add CSS styles
        add_action('wp_head', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue CSS styles for the quiz average display
     */
    public function enqueue_styles() {
        if ($this->should_show_quiz_average()) {
            wp_add_inline_style('wp-block-library', $this->get_quiz_average_css());
        }
    }
    
    /**
     * Check if we should show the quiz average (for course 898 students)
     */
    private function should_show_quiz_average() {
        if (!is_user_logged_in()) {
            error_log('Quiz Average Extractor: User not logged in');
            return false;
        }
        
        $user_id = get_current_user_id();
        error_log('Quiz Average Extractor: Checking for user ID: ' . $user_id);
        
        // Check if user is enrolled in course 898 via LearnDash
        $user_courses = ld_get_mycourses($user_id);
        error_log('Quiz Average Extractor: User courses: ' . print_r($user_courses, true));
        if (in_array(898, $user_courses)) {
            error_log('Quiz Average Extractor: User enrolled in course 898 via LearnDash');
            return true;
        }
        
        // Check if user has promo code access to course 898
        $promo_metadata = get_user_meta($user_id, 'school_promo_course_access', true);
        error_log('Quiz Average Extractor: Promo metadata: ' . print_r($promo_metadata, true));
        if (is_array($promo_metadata) && isset($promo_metadata['course_id']) && $promo_metadata['course_id'] == 898) {
            error_log('Quiz Average Extractor: User has promo access to course 898');
            return true;
        }
        
        // Check if we're on the my-courses page (additional safety check)
        global $post;
        $current_uri = $_SERVER['REQUEST_URI'] ?? '';
        error_log('Quiz Average Extractor: Current URI: ' . $current_uri);
        error_log('Quiz Average Extractor: Post name: ' . ($post ? $post->post_name : 'no post'));
        
        if ($post && ($post->post_name === 'my-courses' || strpos($current_uri, 'my-courses') !== false)) {
            error_log('Quiz Average Extractor: On my-courses page - showing quiz average');
            return true;
        }
        
        error_log('Quiz Average Extractor: No conditions met - not showing quiz average');
        return false;
    }
    
    /**
     * Output JavaScript directly to footer
     */
    public function output_quiz_script() {
        if (!$this->should_show_quiz_average()) {
            error_log('Quiz Average Extractor: Should not show quiz average - script not loaded');
            return;
        }
        
        error_log('Quiz Average Extractor: Outputting quiz average script to footer');
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        ?>
        <script type="text/javascript">
        console.log('Quiz Average Extractor: JavaScript loaded via footer');
        jQuery(document).ready(function($) {
            console.log('Quiz Average Extractor: jQuery ready');
            // Wait for the quiz progress section to load
            setTimeout(function() {
                console.log('Quiz Average Extractor: Starting quiz extraction after timeout');
                extractAndDisplayQuizAverage();
            }, 1000);
            
            function extractAndDisplayQuizAverage() {
                console.log('Quiz Average Extractor: extractAndDisplayQuizAverage function called');
                // Find the quiz progress container
                var quizContainer = $('#quiz_progress_details, .ld-quiz-progress-content-container');
                console.log('Quiz Average Extractor: Found quiz containers:', quizContainer.length);
                
                if (quizContainer.length === 0) {
                    console.log('Quiz Average Extractor: Quiz progress container not found');
                    return;
                }
                
                // Hide the original quiz progress section while keeping data accessible
                $('#quiz_progress_details').hide();
                console.log('Quiz Average Extractor: Hidden original quiz progress section');
                
                // Extract detailed quiz data from the HTML
                var quizData = [];
                var quizScores = [];
                
                // Look for quiz score patterns in the HTML with detailed information
                quizContainer.find('p[id^="ld-quiz-"]').each(function() {
                    var $this = $(this);
                    
                    // Extract quiz name from the link text
                    var $link = $this.find('strong a');
                    if ($link.length === 0) return;
                    
                    var quizName = $link.text().trim();
                    if (!quizName) return;
                    
                    // Extract percentage from the span element
                    var $percentageSpan = $this.find('span');
                    var percentageText = $percentageSpan.text().trim();
                    var percentageMatch = percentageText.match(/(\d+)%/);
                    
                    if (!percentageMatch) return;
                    var percentage = parseInt(percentageMatch[1]);
                    
                    // Get quiz URL from the link
                    var quizUrl = $link.attr('href') || '';
                    
                    // For now, we'll use basic data since detailed scoring isn't in this format
                    var correctAnswers = 0;
                    var totalQuestions = 0;
                    var earnedPoints = 0;
                    var maxPoints = 0;
                    var completedDate = '';
                    
                    // Try to extract additional details from the full text if available
                    var fullText = $this.text();
                    var scoreMatch = fullText.match(/ניקוד\s+(\d+)\s+מתוך\s+(\d+)\s+שאלות/);
                    var pointsMatch = fullText.match(/ניקוד:\s+(\d+)\/(\d+)/);
                    var dateMatch = fullText.match(/ב\s+(\d{2}\/\d{2}\/\d{4}\s+\d{1,2}:\d{2})/);
                    
                    if (scoreMatch) {
                        correctAnswers = parseInt(scoreMatch[1]);
                        totalQuestions = parseInt(scoreMatch[2]);
                    }
                    if (pointsMatch) {
                        earnedPoints = parseInt(pointsMatch[1]);
                        maxPoints = parseInt(pointsMatch[2]);
                    }
                    if (dateMatch) {
                        completedDate = dateMatch[1];
                    }
                    
                    var quiz = {
                        name: quizName,
                        percentage: percentage,
                        correctAnswers: correctAnswers,
                        totalQuestions: totalQuestions,
                        earnedPoints: earnedPoints,
                        maxPoints: maxPoints,
                        completedDate: completedDate,
                        url: quizUrl
                    };
                    
                    quizData.push(quiz);
                    quizScores.push(percentage);
                    
                    console.log('Quiz Average Extractor: Found detailed quiz:', quiz);
                });
                
                console.log('Quiz Average Extractor: Total detailed quiz data found:', quizData);
                
                if (quizData.length > 0) {
                    // Calculate average
                    var totalScore = quizScores.reduce(function(sum, score) {
                        return sum + score;
                    }, 0);
                    
                    var average = Math.round(totalScore / quizScores.length * 10) / 10;
                    
                    console.log('Quiz Average Extractor: Calculated average:', average + '%');
                    
                    // Display detailed quiz scores in the enhanced container
                    displayDetailedQuizScores(quizData, average);
                    
                    // Also update any existing "אין נתונים" sections
                    updateExistingQuizAverageSections(average);
                } else {
                    console.log('Quiz Average Extractor: No quiz data found in HTML');
                }
            }
            
            function displayDetailedQuizScores(quizData, average) {
                // Find or create the quiz display container
                var container = $('.quiz-list-display');
                
                if (container.length === 0) {
                    console.log('Quiz Average Extractor: Creating quiz list display container');
                    // Insert the container after the quiz progress section
                    var quizProgressSection = $('#quiz_progress_details');
                    if (quizProgressSection.length > 0) {
                        quizProgressSection.after('<div class="quiz-list-display"></div>');
                        container = $('.quiz-list-display');
                    } else {
                        // Fallback: append to body
                        $('body').append('<div class="quiz-list-display"></div>');
                        container = $('.quiz-list-display');
                    }
                }
                
                // Clear any existing content
                container.empty();
                
                // Group quizzes by performance level
                var groupedQuizzes = {
                    excellent: [],
                    good: [],
                    average: [],
                    needsImprovement: []
                };
                
                quizData.forEach(function(quiz, index) {
                    quiz.originalIndex = index + 1;
                    var performanceClass = getPerformanceClass(quiz.percentage);
                    if (performanceClass === 'excellent') {
                        groupedQuizzes.excellent.push(quiz);
                    } else if (performanceClass === 'good') {
                        groupedQuizzes.good.push(quiz);
                    } else if (performanceClass === 'average') {
                        groupedQuizzes.average.push(quiz);
                    } else {
                        groupedQuizzes.needsImprovement.push(quiz);
                    }
                });
                
                // Build the enhanced quiz list HTML
                var html = '<div class="quiz-list-header">' +
                    '<div class="header-content">' +
                        '<h3 class="quiz-list-title">' +
                            '<span class="title-icon">📊</span>' +
                            'סיכום ביצועי הבחינות' +
                        '</h3>' +
                        '<div class="quiz-summary-stats">' +
                            '<div class="stat-card average-card">' +
                                '<div class="stat-label">ממוצע כללי</div>' +
                                '<div class="stat-value ' + getPerformanceClass(average) + '">' + average + '%</div>' +
                            '</div>' +
                            '<div class="stat-card count-card">' +
                                '<div class="stat-label">סה"כ בחינות</div>' +
                                '<div class="stat-value">' + quizData.length + '</div>' +
                            '</div>' +
                            '<div class="stat-card breakdown-card">' +
                                '<div class="stat-label">פילוח ביצועים</div>' +
                                '<div class="stat-breakdown">' +
                                    '<span class="breakdown-item excellent">🏆 ' + groupedQuizzes.excellent.length + '</span>' +
                                    '<span class="breakdown-item good">👍 ' + groupedQuizzes.good.length + '</span>' +
                                    '<span class="breakdown-item average">📈 ' + groupedQuizzes.average.length + '</span>' +
                                    '<span class="breakdown-item needs-improvement">📚 ' + groupedQuizzes.needsImprovement.length + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                // Add performance groups
                html += '<div class="quiz-list-content">';
                
                // Add each performance group
                var groups = [
                    { key: 'excellent', title: 'ביצועים מעולים (90%+)', icon: '🏆', class: 'excellent' },
                    { key: 'good', title: 'ביצועים טובים (80-89%)', icon: '👍', class: 'good' },
                    { key: 'average', title: 'ביצועים בינוניים (70-79%)', icon: '📈', class: 'average' },
                    { key: 'needsImprovement', title: 'דורש שיפור (מתחת ל-70%)', icon: '📚', class: 'needs-improvement' }
                ];
                
                groups.forEach(function(group) {
                    var groupQuizzes = groupedQuizzes[group.key];
                    if (groupQuizzes.length > 0) {
                        html += '<div class="quiz-group ' + group.class + '">' +
                            '<div class="group-header">' +
                                '<h4 class="group-title">' +
                                    '<span class="group-icon">' + group.icon + '</span>' +
                                    group.title +
                                    '<span class="group-count">(' + groupQuizzes.length + ' בחינות)</span>' +
                                '</h4>' +
                            '</div>' +
                            '<div class="group-quizzes">';
                        
                        // Add quizzes for this group
                        groupQuizzes.forEach(function(quiz) {
                            var performanceClass = getPerformanceClass(quiz.percentage);
                            var statusIcon = getStatusIcon(quiz.percentage);
                            
                            html += '<div class="quiz-item ' + performanceClass + '">' +
                                '<div class="quiz-item-main">' +
                                    '<div class="quiz-info">' +
                                        '<div class="quiz-header">' +
                                            '<span class="quiz-number">' + quiz.originalIndex + '</span>' +
                                            '<h4 class="quiz-title">' + quiz.name + '</h4>' +
                                            '<span class="quiz-status-icon">' + statusIcon + '</span>' +
                                        '</div>' +
                                        '<div class="quiz-details">' +
                                            '<div class="detail-row">' +
                                                '<span class="detail-icon">✅</span>' +
                                                '<span class="detail-text">תשובות נכונות: <strong>' + quiz.correctAnswers + '/' + quiz.totalQuestions + '</strong></span>' +
                                            '</div>' +
                                            '<div class="detail-row">' +
                                                '<span class="detail-icon">🎯</span>' +
                                                '<span class="detail-text">ניקוד: <strong>' + quiz.earnedPoints + '/' + quiz.maxPoints + '</strong></span>' +
                                            '</div>' +
                                            (quiz.completedDate ? 
                                                '<div class="detail-row">' +
                                                    '<span class="detail-icon">📅</span>' +
                                                    '<span class="detail-text">הושלם ב: <strong>' + quiz.completedDate + '</strong></span>' +
                                                '</div>' : '') +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="quiz-score-display">' +
                                        '<div class="score-circle ' + performanceClass + '">' +
                                            '<span class="score-number">' + quiz.percentage + '%</span>' +
                                        '</div>' +
                                        '<div class="performance-label">' + getPerformanceLabel(quiz.percentage) + '</div>' +
                                    '</div>' +
                                '</div>' +
                                (quiz.url ? 
                                    '<div class="quiz-actions">' +
                                        '<a href="' + quiz.url + '" class="quiz-action-btn">' +
                                            '<span class="btn-icon">👁️</span>' +
                                            'צפה בבחינה' +
                                        '</a>' +
                                    '</div>' : '') +
                            '</div>';
                        });
                        
                        // Close the group containers
                        html += '</div></div>'; // Close group-quizzes and quiz-group
                    }
                });
                
                html += '</div>'; // Close quiz-list-content
                
                // Add footer with additional info
                html += '<div class="quiz-list-footer">' +
                    '<div class="performance-legend">' +
                        '<h4>מקרא ביצועים:</h4>' +
                        '<div class="legend-items">' +
                            '<span class="legend-item excellent">🏆 מעולה (90%+)</span>' +
                            '<span class="legend-item good">👍 טוב (80-89%)</span>' +
                            '<span class="legend-item average">📈 בינוני (70-79%)</span>' +
                            '<span class="legend-item needs-improvement">📚 דורש שיפור (מתחת ל-70%)</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                
                // Insert the HTML into the container
                container.html(html);
                
                console.log('Quiz Average Extractor: Beautiful quiz list displayed successfully');
            }
            
            function getStatusIcon(percentage) {
                if (percentage >= 90) return '🏆';
                if (percentage >= 80) return '👍';
                if (percentage >= 70) return '📈';
                return '📚';
            }
            
            function getPerformanceLabel(percentage) {
                if (percentage >= 90) return 'מעולה';
                if (percentage >= 80) return 'טוב';
                if (percentage >= 70) return 'בינוני';
                return 'דורש שיפור';
            }
            
            function updateExistingQuizAverageSections(average) {
                // Find and update any existing "אין נתונים" sections
                $('*:contains("אין נתונים")').each(function() {
                    if ($(this).text().trim() === 'אין נתונים') {
                        $(this).html('<span class="' + getPerformanceClass(average) + '">' + average + '%</span>');
                        console.log('Quiz Average Extractor: Updated "אין נתונים" section with average');
                    }
                });
                
                // Also look for quiz average sections that might be empty
                $('.quiz-average, .course-quiz-average').each(function() {
                    if ($(this).text().trim() === '' || $(this).text().includes('אין נתונים')) {
                        $(this).html('<span class="' + getPerformanceClass(average) + '">' + average + '%</span>');
                        console.log('Quiz Average Extractor: Updated empty quiz average section');
                    }
                });
            }
            
            function getPerformanceClass(percentage) {
                if (percentage >= 90) return 'excellent';
                if (percentage >= 80) return 'good';
                if (percentage >= 70) return 'average';
                return 'needs-improvement';
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get CSS styles for the quiz average display
     */
    private function get_quiz_average_css() {
        return '
        .quiz-list-display {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            direction: rtl;
            font-family: "Assistant", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .quiz-list-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .quiz-list-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .quiz-list-title {
            margin: 0 0 20px 0;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .title-icon {
            font-size: 32px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .quiz-summary-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px 25px;
            text-align: center;
            min-width: 120px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
            display: block;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .quiz-count {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .quiz-list-content {
            padding: 20px;
        }
        
        /* Quiz group styling */
        .quiz-group {
            margin-bottom: 25px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        
        .group-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border-bottom: 3px solid #dee2e6;
        }
        
        .quiz-group.excellent .group-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .quiz-group.good .group-header {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
        }
        
        .quiz-group.average .group-header {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .quiz-group.needs-improvement .group-header {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }
        
        .group-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .group-icon {
            font-size: 20px;
        }
        
        .group-count {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .group-quizzes {
            padding: 0;
        }
        
        .quiz-group .quiz-item {
            border-bottom: 1px solid #f0f0f0;
            border-radius: 0;
            margin: 0;
        }
        
        .quiz-group .quiz-item:last-child {
            border-bottom: none;
        }
        
        .quiz-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            background: #ffffff;
        }
        
        .quiz-item:last-child {
            border-bottom: none;
        }
        
        .quiz-item:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .quiz-item-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        
        .quiz-info {
            flex: 1;
        }
        
        .quiz-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .quiz-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .quiz-title {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            flex: 1;
            line-height: 1.3;
        }
        
        .quiz-status-icon {
            font-size: 24px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .quiz-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #4a5568;
        }
        
        .detail-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        .detail-text {
            flex: 1;
        }
        
        .quiz-score-display {
            text-align: center;
            flex-shrink: 0;
        }
        
        .score-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            border: 4px solid;
            background: white;
        }
        
        .score-number {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .performance-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .quiz-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .quiz-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .quiz-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .btn-icon {
            font-size: 16px;
        }
        
        .quiz-list-footer {
            background: #f7fafc;
            padding: 25px;
            border-top: 1px solid #e2e8f0;
        }
        
        .performance-legend h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #2d3748;
            text-align: center;
        }
        
        .legend-items {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #4a5568;
            background: white;
            padding: 5px 10px;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
        }
        
        /* Performance-based colors */
        .excellent .score-circle {
            border-color: #48bb78;
            background: linear-gradient(135deg, #f0fff4, #c6f6d5);
        }
        
        .excellent .performance-label {
            color: #38a169;
        }
        
        .good .score-circle {
            border-color: #4299e1;
            background: linear-gradient(135deg, #ebf8ff, #bee3f8);
        }
        
        .good .performance-label {
            color: #3182ce;
        }
        
        .average .score-circle {
            border-color: #ed8936;
            background: linear-gradient(135deg, #fffaf0, #feebc8);
        }
        
        .average .performance-label {
            color: #dd6b20;
        }
        
        .needs-improvement .score-circle {
            border-color: #f56565;
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
        }
        
        .needs-improvement .performance-label {
            color: #e53e3e;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .enhanced-quiz-scores-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .enhanced-quiz-scores-header {
                padding: 20px 15px;
            }
            
            .quiz-scores-title {
                font-size: 20px;
            }
            
            .average-value {
                font-size: 24px;
            }
            
            .enhanced-quiz-scores-content {
                padding: 15px;
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .quiz-card {
                padding: 15px;
            }
            
            .quiz-card-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .quiz-name {
                font-size: 15px;
            }
            
            .score-percentage {
                font-size: 20px;
            }
            
            .quiz-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Hide original empty message */
        .enhanced-quiz-scores-empty {
            display: none !important;
        }
        ';
    }
}

// Initialize the quiz average extractor
new Quiz_Average_Extractor();
?>
