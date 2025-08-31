<?php
/**
 * Student Section Hider
 * 
 * Hides course list and quiz progress sections for all students on /my-courses/ page
 * 
 * @package Student_Section_Hider
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Student_Section_Hider {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'hide_student_sections'));
    }
    
    /**
     * Hide sections for all students on my-courses page
     */
    public function hide_student_sections() {
        // Only run on my-courses page
        if (!$this->is_my_courses_page()) {
            return;
        }
        
        // Only run for logged-in users (students)
        if (!is_user_logged_in()) {
            return;
        }
        
        // Don't hide for administrators
        if (current_user_can('administrator')) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Student Section Hider: Starting to hide sections...');
            
            // Hide the course list section
            var courseListSection = $('#ld_course_info_mycourses_list');
            if (courseListSection.length > 0) {
                courseListSection.hide();
                console.log('Student Section Hider: Course list section hidden');
            }
            
            // Hide the course progress details section  
            var courseProgressSection = $('#course_progress_details');
            if (courseProgressSection.length > 0) {
                courseProgressSection.hide();
                console.log('Student Section Hider: Course progress section hidden');
            }
            
            // Hide the original quiz progress section (but NOT our enhanced quiz display)
            var quizProgressSection = $('#quiz_progress_details');
            if (quizProgressSection.length > 0) {
                // Only hide if it doesn't contain our enhanced quiz list
                if (quizProgressSection.find('.quiz-list-display').length === 0) {
                    quizProgressSection.hide();
                    console.log('Student Section Hider: Original quiz progress section hidden');
                } else {
                    // Hide only the original content, but keep our enhanced display
                    quizProgressSection.children().not('.quiz-list-display').hide();
                    console.log('Student Section Hider: Original quiz content hidden, enhanced display preserved');
                }
            }
            
            // PRESERVE our enhanced quiz list display - DO NOT HIDE IT
            // This is the valuable quiz summary we want students to see
            
            // Hide any old enhanced quiz scores container (legacy)
            var enhancedQuizContainer = $('.enhanced-quiz-scores-container');
            if (enhancedQuizContainer.length > 0 && enhancedQuizContainer.find('.quiz-list-display').length === 0) {
                enhancedQuizContainer.hide();
                console.log('Student Section Hider: Legacy enhanced quiz container hidden');
            }
            
            // Hide any section with course registration content
            var courseRegisteredContainer = $('.ld-courseregistered-content-container');
            if (courseRegisteredContainer.length > 0) {
                courseRegisteredContainer.parent().hide();
                console.log('Student Section Hider: Course registered container hidden');
            }
            
            // Hide any section with course progress content
            var courseProgressContainer = $('.ld-course-progress-content-container');
            if (courseProgressContainer.length > 0) {
                courseProgressContainer.parent().hide();
                console.log('Student Section Hider: Course progress container hidden');
            }
            
            // Hide section headers if they exist without content
            $('h4').each(function() {
                var headerText = $(this).text().trim();
                if (headerText === 'נרשמת לקורסים שלהלן' || 
                    headerText === 'פרטי התקדמות הקורס:' ||
                    headerText === 'ביצעת את המבחנים שלהלן:' ||
                    headerText === 'סיכום ביצועי הבחינות') {
                    $(this).hide();
                    console.log('Student Section Hider: Hidden header: ' + headerText);
                }
            });
            
            console.log('Student Section Hider: All sections processed');
        });
        </script>
        
        <style type="text/css">
        /* CSS backup to ensure sections stay hidden */
        body:not(.administrator) #ld_course_info_mycourses_list,
        body:not(.administrator) #course_progress_details {
            display: none !important;
        }
        
        /* Hide original quiz progress content but PRESERVE our enhanced quiz list */
        body:not(.administrator) #quiz_progress_details > *:not(.quiz-list-display) {
            display: none !important;
        }
        
        /* PRESERVE the enhanced quiz list display - this is what we want students to see */
        body:not(.administrator) .quiz-list-display {
            display: block !important;
            visibility: visible !important;
        }
        
        /* Hide legacy enhanced quiz container only if it doesn't contain our quiz list */
        body:not(.administrator) .enhanced-quiz-scores-container:not(:has(.quiz-list-display)) {
            display: none !important;
        }
        
        /* Hide course info container entirely if it only contains hidden sections */
        body:not(.administrator) #ld_course_info {
            display: none !important;
        }
        </style>
        <?php
    }
    
    /**
     * Check if current page is my-courses page
     */
    private function is_my_courses_page() {
        global $post;
        
        // Check by post slug
        if ($post && $post->post_name === 'my-courses') {
            return true;
        }
        
        // Check by URL
        $current_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($current_uri, 'my-courses') !== false) {
            return true;
        }
        
        return false;
    }
}

// Initialize the plugin
new Student_Section_Hider();
