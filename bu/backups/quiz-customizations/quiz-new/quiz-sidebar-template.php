<?php
/**
 * Quiz Sidebar Template
 * 
 * Template for the quiz sidebar that appears alongside quizzes.
 * This provides supplementary information, navigation, and help.
 */

defined('ABSPATH') || exit;

// Get the current quiz ID if available
$quiz_id = 0;
$quiz_post_id = 0;

// Try to get quiz data from LearnDash
if (function_exists('learndash_get_course_quiz_list')) {
    $quiz_id = isset($_GET['quiz']) ? intval($_GET['quiz']) : 0;
    
    if ($quiz_id) {
        $quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id($quiz_id);
    } else {
        global $post;
        if ($post && $post->post_type === 'sfwd-quiz') {
            $quiz_post_id = $post->ID;
        }
    }
}

// Get quiz title
$quiz_title = '';
if ($quiz_post_id) {
    $quiz_title = get_the_title($quiz_post_id);
}
?>

<div class="quiz-sidebar">
    <!-- Quiz information section -->
    <div class="quiz-sidebar-info">
        <?php if (!empty($quiz_title)) : ?>
            <h3 class="quiz-title"><?php echo esc_html($quiz_title); ?></h3>
        <?php else : ?>
            <h3 class="quiz-title">מידע על הבוחן</h3>
        <?php endif; ?>
        
        <div class="quiz-details">
            <?php 
            // Get quiz details if available
            if ($quiz_post_id && function_exists('learndash_get_quiz_data')) {
                $quiz_data = learndash_get_quiz_data($quiz_post_id);
                
                if (!empty($quiz_data)) {
                    echo '<ul class="quiz-meta">';
                    
                    // Quiz time limit
                    if (!empty($quiz_data['time_limit'])) {
                        $time_limit = $quiz_data['time_limit'];
                        echo '<li><strong>מגבלת זמן:</strong> ' . esc_html($time_limit) . ' דקות</li>';
                    }
                    
                    // Pass percentage
                    if (isset($quiz_data['pass_percentage'])) {
                        echo '<li><strong>ציון עובר:</strong> ' . esc_html($quiz_data['pass_percentage']) . '%</li>';
                    }
                    
                    // Number of questions
                    if (isset($quiz_data['question_count'])) {
                        echo '<li><strong>שאלות:</strong> ' . esc_html($quiz_data['question_count']) . '</li>';
                    }
                    
                    echo '</ul>';
                }
            }
            ?>
        </div>
    </div>
    
    <!-- Quiz instructions -->
    <div class="quiz-sidebar-instructions">
        <h4>הנחיות לבוחן</h4>
        <ul>
            <li>קרא בעיון את כל השאלות לפני שאתה עונה</li>
            <li>אם אינך בטוח בתשובה, השתמש ברמז</li>
            <li>הרמזים יכולים לעזור לך לפתור את השאלות בצורה טובה יותר</li>
            <li>אל תמהר - קח את הזמן שלך להשלמת הבוחן</li>
        </ul>
    </div>
    
    <!-- Progress tracking -->
    <div class="quiz-sidebar-progress">
        <h4>התקדמות בבוחן</h4>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-text">0% הושלם</div>
        </div>
        
        <!-- Navigation controls -->
        <div class="quiz-navigation-controls">
            <button id="lilac-quiz-prev" class="quiz-nav-button prev-button" disabled>שאלה קודמת</button>
            <button id="lilac-quiz-next" class="quiz-nav-button next-button" disabled>שאלה הבאה</button>
        </div>
    </div>
    
    <!-- Help section -->
    <div class="quiz-sidebar-help">
        <h4>צריך עזרה?</h4>
        <p>זכור שברוב השאלות יש רמזים שיעזרו לך. אם אתה עדיין מתקשה, ניתן לפנות למדריך.</p>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="user-info">
                <p>התחברת כ-<strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quiz statistics (will be populated by JavaScript) -->
    <div class="quiz-sidebar-stats" style="display: none;">
        <h4>סטטיסטיקה בזמן אמת</h4>
        <ul>
            <li><span class="stat-label">שאלות שהושלמו:</span> <span id="completed-questions">0</span></li>
            <li><span class="stat-label">תשובות נכונות:</span> <span id="correct-answers">0</span></li>
            <li><span class="stat-label">רמזים שנצפו:</span> <span id="hints-viewed">0</span></li>
        </ul>
    </div>
</div>

<script>
// Initialize quiz sidebar functionality when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Function to update progress
    function updateQuizProgress() {
        if (!window.LilacQuiz || !window.LilacQuiz.state) return;
        
        const state = window.LilacQuiz.state;
        const quizState = state.quiz;
        
        // Get question count
        const totalQuestions = Object.keys(quizState.questions).length;
        if (totalQuestions === 0) return;
        
        // Count completed questions
        let completedQuestions = 0;
        let correctAnswers = 0;
        let hintsViewed = 0;
        
        for (const questionId in quizState.questions) {
            const question = quizState.questions[questionId];
            
            if (question.isAnswered) {
                completedQuestions++;
            }
            
            if (question.isCorrect) {
                correctAnswers++;
            }
            
            if (question.hintViewed) {
                hintsViewed++;
            }
        }
        
        // Update progress display
        const progressPercent = Math.round((completedQuestions / totalQuestions) * 100);
        const progressFill = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-text');
        
        if (progressFill) {
            progressFill.style.width = progressPercent + '%';
        }
        
        if (progressText) {
            progressText.textContent = progressPercent + '% הושלם';
        }
        
        // Update statistics
        const statsContainer = document.querySelector('.quiz-sidebar-stats');
        const completedEl = document.getElementById('completed-questions');
        const correctEl = document.getElementById('correct-answers');
        const hintsEl = document.getElementById('hints-viewed');
        
        if (statsContainer) {
            statsContainer.style.display = 'block';
        }
        
        if (completedEl) completedEl.textContent = completedQuestions;
        if (correctEl) correctEl.textContent = correctAnswers;
        if (hintsEl) hintsEl.textContent = hintsViewed;
    }
    
    // Hook into LilacQuiz if available
    if (window.LilacQuiz) {
        // Add observer to track state changes
        const originalSetState = window.LilacQuiz.setState;
        window.LilacQuiz.setState = function(newState) {
            const result = originalSetState ? originalSetState(newState) : newState;
            updateQuizProgress();
            return result;
        };
        
        // Initialize navigation controls
        const prevButton = document.getElementById('lilac-quiz-prev');
        const nextButton = document.getElementById('lilac-quiz-next');
        
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                const currentQuestion = document.querySelector('.wpProQuiz_listItem:not([style*="display: none"])');
                if (currentQuestion) {
                    const prevButton = currentQuestion.querySelector('.wpProQuiz_button[name=back]');
                    if (prevButton) {
                        prevButton.click();
                    }
                }
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                const currentQuestion = document.querySelector('.wpProQuiz_listItem:not([style*="display: none"])');
                if (currentQuestion) {
                    const nextBtn = currentQuestion.querySelector('.wpProQuiz_button[name=next]');
                    if (nextBtn && !nextBtn.disabled) {
                        nextBtn.click();
                    }
                }
            });
        }
        
        // Initial update
        setTimeout(updateQuizProgress, 1000);
        
        // Periodic updates
        setInterval(updateQuizProgress, 3000);
    }
});
</script>

<style>
/* Additional sidebar styles */
.quiz-sidebar {
    background-color: #f8f8f8;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    height: fit-content;
    position: sticky;
    top: 30px;
}

.quiz-sidebar h3, 
.quiz-sidebar h4 {
    color: #333;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 10px;
    margin-bottom: 15px;
    text-align: right;
}

.quiz-sidebar-info {
    margin-bottom: 20px;
}

.quiz-meta {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: right;
}

.quiz-meta li {
    margin-bottom: 8px;
    padding-right: 20px;
    position: relative;
}

.quiz-meta li:before {
    content: "•";
    position: absolute;
    right: 0;
    color: #4CAF50;
}

.quiz-sidebar-instructions ul {
    padding-right: 20px;
    margin: 0 0 20px 0;
    text-align: right;
}

.quiz-sidebar-instructions li {
    margin-bottom: 10px;
}

.quiz-sidebar-progress {
    margin-bottom: 20px;
}

.progress-container {
    margin-bottom: 15px;
}

.progress-bar {
    height: 10px;
    background-color: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #4CAF50;
    width: 0;
    transition: width 0.5s ease-in-out;
}

.progress-text {
    margin-top: 5px;
    text-align: center;
    font-size: 14px;
    color: #333;
}

.quiz-navigation-controls {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-top: 15px;
}

.quiz-nav-button {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    background-color: #2196F3;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s;
}

.quiz-nav-button:hover {
    background-color: #0d8bf2;
}

.quiz-nav-button:disabled {
    background-color: #b0bec5;
    cursor: not-allowed;
}

.quiz-sidebar-help {
    background-color: rgba(33, 150, 243, 0.1);
    padding: 15px;
    border-radius: 5px;
    border-right: 4px solid #2196F3;
    margin-bottom: 20px;
    text-align: right;
}

.quiz-sidebar-stats {
    margin-top: 20px;
}

.quiz-sidebar-stats ul {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: right;
}

.quiz-sidebar-stats li {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 5px 0;
    border-bottom: 1px dashed #e0e0e0;
}

.stat-label {
    font-weight: bold;
}

.user-info {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    text-align: right;
}
</style>
