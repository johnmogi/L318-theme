<?php
/**
 * Ultra-Basic LearnDash Quiz Template
 * 
 * This template is designed to reliably display LearnDash quizzes without any custom features.
 * When you need quizzes to display, simplicity is best.
 *
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Debug function to output debugging info for LearnDash quizzes
 * Only visible to admins
 */
/**
 * Format and display ACF field value based on field type
 */
function lilac_format_acf_value($field, $post_id) {
    if (!function_exists('get_field')) return 'ACF not active';
    
    $field_value = get_field($field['name'], $post_id);
    $output = '';
    
    // Handle different field types
    if ($field['type'] === 'image' || $field['name'] === 'rich_media') {
        // Handle rich_media field specifically
        if ($field['name'] === 'rich_media') {
            if (is_string($field_value) && !empty($field_value)) {
                // This is a URL string (return_format = 'url')
                $output .= 'Image URL: ' . esc_url($field_value) . '<br>';
                $output .= '<img src="' . esc_url($field_value) . '" style="max-width: 150px; height: auto; display: block; margin: 5px 0;">';
                
                // Try to get the attachment ID from URL to show more details
                $attachment_id = attachment_url_to_postid($field_value);
                if ($attachment_id) {
                    $output .= 'Attachment ID: ' . $attachment_id . '<br>';
                }
            } else {
                $output .= 'No image set';
            }
        } 
        // Handle regular image fields
        elseif (is_array($field_value)) {
            $output .= 'Image ID: ' . $field_value['ID'] . '<br>';
            $output .= 'URL: ' . esc_url($field_value['url']) . '<br>';
            if (isset($field_value['sizes']['thumbnail'])) {
                $output .= '<img src="' . esc_url($field_value['sizes']['thumbnail']) . '" style="max-width: 150px; height: auto; display: block; margin: 5px 0;">';
            }
        } elseif (is_numeric($field_value)) {
            $image_data = wp_get_attachment_image_src($field_value, 'thumbnail');
            if ($image_data) {
                $output .= 'Image ID: ' . $field_value . '<br>';
                $output .= 'URL: ' . esc_url($image_data[0]) . '<br>';
                $output .= '<img src="' . esc_url($image_data[0]) . '" style="max-width: 150px; height: auto; display: block; margin: 5px 0;">';
            } else {
                $output .= 'Invalid image ID';
            }
        } else {
            $output .= 'No image set';
        }
    } 
    // Handle file fields separately
    elseif ($field['type'] === 'file') {
        if (is_array($field_value)) {
            $output .= 'File URL: ' . esc_url($field_value['url']) . '<br>';
            $output .= 'File Name: ' . esc_html($field_value['filename']) . '<br>';
        } elseif (is_string($field_value) && !empty($field_value)) {
            $output .= 'File URL: ' . esc_url($field_value) . '<br>';
        } else {
            $output .= 'No file set';
        }
    } elseif ($field['type'] === 'url' || $field['type'] === 'link') {
        if (is_array($field_value)) {
            $output .= 'URL: ' . esc_url($field_value['url']) . '<br>';
            $output .= 'Title: ' . esc_html($field_value['title']) . '<br>';
            $output .= 'Target: ' . esc_html($field_value['target']);
        } else {
            $output .= $field_value ? '<a href="' . esc_url($field_value) . '" target="_blank">' . esc_url($field_value) . '</a>' : 'Not set';
        }
    } elseif (is_array($field_value) || is_object($field_value)) {
        $output .= '<pre style="max-height: 150px; overflow: auto; margin: 0;">';
        $output .= print_r($field_value, true);
        $output .= '</pre>';
    } else {
        $output .= $field_value ? esc_html($field_value) : 'Not set';
    }
    
    return $output;
}

/**
 * Display ACF fields for a single question
 */
function lilac_display_question_acf_fields($question_id) {
    if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
        return false;
    }
    
    $field_groups = acf_get_field_groups(array('post_id' => $question_id));
    if (empty($field_groups)) {
        return false;
    }
    
    $has_fields = false;
    
    foreach ($field_groups as $group) {
        $fields = acf_get_fields($group['key']);
        if (empty($fields)) {
            continue;
        }
        
        $has_fields = true;
        echo '<div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #eee;">';
        echo '<h5>Field Group: ' . esc_html($group['title']) . ' (ID: ' . $group['ID'] . ')</h5>';
        
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<tr style="background: #e9ecef;">';
        echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Field</th>';
        echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Name</th>';
        echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Value</th>';
        echo '</tr>';
        
        foreach ($fields as $field) {
            echo '<tr style="border: 1px solid #dee2e6;">';
            echo '<td style="padding: 8px; border: 1px solid #dee2e6;">' . esc_html($field['label']) . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #dee2e6; font-family: monospace;">' . esc_html($field['name']) . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #dee2e6;">';
            echo lilac_format_acf_value($field, $question_id);
            echo '</td></tr>';
        }
        
        echo '</table>';
        echo '</div>';
    }
    
    return $has_fields;
}

function lilac_quiz_debug() {
    // Check if debug is enabled via URL parameter and user is admin
    $debug_enabled = isset($_GET['debug']) && $_GET['debug'] === '1';
    if (!current_user_can('administrator') && !$debug_enabled) return;
    
    // Add a style to hide debug by default when using URL parameter
    if ($debug_enabled) {
        echo '<style>#lilac-quiz-debug { display: block !important; }</style>';
    }
    
    $quiz_id = get_the_ID();
    $quiz_pro_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
    
    echo '<div id="lilac-quiz-debug" style="display: none; padding: 20px; margin: 20px; background: #f8f9fa; border: 1px solid #ddd; max-width: 95%; overflow-x: auto;">';  
    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
    echo '<h3 style="margin: 0;">LearnDash Quiz Debug</h3>';
    
    // Add a close button for convenience
    echo '<div>';
    echo '<a href="?debug=1" style="margin-right: 10px;" class="button">Show Debug</a>';
    echo '<a href="?" class="button">Hide Debug</a>';
    echo '</div>';
    echo '</div>'; // Close flex container
    
    // Add debug URL info
    $debug_url = add_query_arg('debug', '1', get_permalink());
    echo '<p><small>Debug URL: <a href="' . esc_url($debug_url) . '">' . esc_url($debug_url) . '</a></small></p>';
    
    // Add ACF Filter Debug Message
    echo '<div style="background:#ffe4e1;color:#222;padding:10px 20px;margin:20px 0;border:2px dashed #e22;max-width:900px;font-size:13px;z-index:9999;">';
    echo '<strong>LearnDash ACF Filter Debug:</strong> If you see this, the filter is loaded.<br>Check your PHP error log for full question/answer/ACF dumps.';
    echo '</div>';
    echo '<p><strong>Template File:</strong> ' . __FILE__ . '</p>';
    echo '<p><strong>Quiz ID:</strong> ' . esc_html($quiz_id) . '</p>';
    echo '<p><strong>Quiz PRO ID:</strong> ' . esc_html($quiz_pro_id) . '</p>';
    echo '<p><strong>LearnDash Version:</strong> ' . (defined('LEARNDASH_VERSION') ? LEARNDASH_VERSION : 'Not detected') . '</p>';
    
    // Check if ACF is active
    $acf_active = function_exists('acf');
    echo '<p><strong>ACF Active:</strong> ' . ($acf_active ? 'Yes' : 'No') . '</p>';
    
    if ($acf_active) {
        // Get ACF version
        $acf_version = function_exists('acf_get_setting') ? acf_get_setting('version') : 'Unknown';
        echo '<p><strong>ACF Version:</strong> ' . esc_html($acf_version) . '</p>';
        
        // Check if ACF fields exist for this quiz
        $quiz_fields = function_exists('get_field_objects') ? get_field_objects($quiz_id) : [];
        echo '<p><strong>Quiz ACF Fields:</strong> ' . (empty($quiz_fields) ? 'None' : count($quiz_fields) . ' fields found') . '</p>';
        
        // Get all questions for this quiz
        $questions = learndash_get_quiz_questions($quiz_id);
        if (!empty($questions)) {
            echo '<div style="margin-top: 20px;">';
            echo '<h4>Questions in this Quiz (' . count($questions) . ')</h4>';
            
            $question_count = 0;
            $max_questions_to_show = 10; // Safety limit
            
            foreach ($questions as $question_id => $question) {
                $question_count++;
                if ($question_count > $max_questions_to_show) {
                    echo '<p>Showing first ' . $max_questions_to_show . ' questions. More questions exist but are not shown.</p>';
                    break;
                }
                
                echo '<div style="margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ddd;">';
                echo '<h5>Question #' . $question_count . ' (ID: ' . $question_id . ')</h5>';
                
                // Get question post
                $question_post = get_post($question_id);
                if ($question_post) {
                    echo '<p><strong>Title:</strong> ' . esc_html($question_post->post_title) . '</p>';
                    echo '<p><strong>Type:</strong> ' . get_post_meta($question_id, 'question_type', true) . '</p>';
                    
                    // Get correct answer ID
                    $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                    $answers = get_post_meta($question_id, 'ld_question_answers', true);
                    $correct_answer_id = '';
                    
                    if (!empty($answers) && is_array($answers)) {
                        foreach ($answers as $answer_id => $answer) {
                            if (isset($answer['correct']) && $answer['correct']) {
                                $correct_answer_id = $answer_id;
                                break;
                            }
                        }
                    }
                    
                    echo '<p><strong>Correct Answer ID:</strong> ' . esc_html($correct_answer_id) . '</p>';
                    echo '<p><strong>Question Pro ID:</strong> ' . esc_html($question_pro_id) . '</p>';
                }
                
                // Show ACF fields for this question
                echo '<div style="margin-top: 10px;">';
                echo '<h6>ACF Fields:</h6>';
                
                $has_acf_fields = lilac_display_question_acf_fields($question_id);
                if (!$has_acf_fields) {
                    echo '<p>No ACF fields found for this question.</p>';
                }
                
                echo '</div>'; // End ACF fields div
                echo '</div>'; // End question div
            }
            
            echo '</div>'; // End questions container
        } else {
            echo '<p>No questions found in this quiz.</p>';
        }
    }
    
    // Check if course is associated
    $course_id = learndash_get_course_id($quiz_id);
    echo '<p><strong>Associated Course ID:</strong> ' . ($course_id ? $course_id : 'None') . '</p>';
    
    // Check quiz shortcode
    $shortcode = '[ld_quiz quiz_id="' . $quiz_id . '"]';
    echo '<p><strong>Quiz Shortcode:</strong> ' . esc_html($shortcode) . '</p>';
    
    echo '</div>';
}

// Add debug info at the end
add_action('wp_footer', 'lilac_quiz_debug');

/**
 * Enqueue quiz performance script
 */
function lilac_enqueue_quiz_scripts() {
    if (is_singular('sfwd-quiz')) {
        // Get the quiz ID
        $quiz_id = get_the_ID();
        $questions = learndash_get_quiz_questions($quiz_id);
        $question_mapping = array();
        
        // Build question ID to post ID mapping
        if (!empty($questions)) {
            foreach ($questions as $question_id => $question) {
                $question_pro_id = get_post_meta($question_id, 'question_pro_id', true);
                if ($question_pro_id) {
                    $question_mapping[$question_pro_id] = $question_id;
                }
            }
        }
        
        // Localize script with question mapping
        wp_localize_script('jquery', 'quizQuestionData', array(
            'questionMapping' => $question_mapping,
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        
        wp_enqueue_script('quiz-performance', get_stylesheet_directory_uri() . '/js/quiz-performance.js', array('jquery'), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'lilac_enqueue_quiz_scripts');

// Get header
get_header();

// Get quiz ID
$quiz_id = get_the_ID();

// We'll handle the media display via JavaScript since the quiz loads dynamically
$show_sidebar = true; // Always show sidebar, but it might be empty
?>

<style>
    .quiz-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    
    .quiz-content-wrapper {
        display: flex;
        flex-direction: row-reverse; /* Reversed to put sidebar on right */
        gap: 20px;
        justify-content: space-between;
    }
    
    .quiz-main-content {
        flex: 1;
        min-width: 0; /* Prevents flex item from overflowing */
    }
    
    .quiz-media-sidebar {
        width: 30%;
        background: #ffffff;
        border: 1px solid #dddddd;
        padding: 15px;
        min-height: 300px;
        position: sticky;
        top: 20px;
        align-self: flex-start;
    }
    
    .quiz-media-sidebar .media-content {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    
    .no-media-message {
        padding: 15px;
        color: #666;
        font-style: italic;
        text-align: center;
    }
    
    .media-loading {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    
    .media-loading:after {
        content: '';
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .entry-content {
        direction: rtl;
    }
    
    /* Force quiz to display at maximum width */
    .learndash-wrapper {
        max-width: 100% !important;
    }
    
    /* Quiz performance enhancements */
    .wpProQuiz_button {
        transition: background-color 0.2s ease;
    }
    
    .wpProQuiz_questionInput:checked + span {
        font-weight: bold;
    }
    
    .wpProQuiz_correct, 
    .wpProQuiz_incorrect {
        opacity: 0;
        animation: fadeIn 0.3s ease forwards;
    }
    
    @keyframes fadeIn {
        to { opacity: 1; }
    }
    
    /* Responsive styles */
    @media (max-width: 992px) {
        .quiz-container {
            flex-direction: column;
        }
        
        .quiz-media-sidebar {
            width: 100%;
            order: -1;
            margin-bottom: 20px;
        }
    }
</style>

<div id="primary" class="content-area">
    <div class="quiz-container">
        <div class="quiz-main-content">
            <?php 
            if (have_posts()) { 
                while (have_posts()) { 
                    the_post(); 
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <h1 class="entry-title"><?php the_title(); ?></h1>
                        </header>

                        <div class="entry-content">
                            <?php 
                            // Display the quiz content
                            the_content();
                            
                            // Fallback for quiz display if needed
                            echo '<div class="learndash-direct-output">';
                            echo do_shortcode('[ld_quiz quiz_id="' . get_the_ID() . '"]');
                            echo '</div>';
                            ?>
                        </div>
                    </article>
                    <?php 
                } // end while
            } // end if
            ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Quiz-related JavaScript can go here if needed
            
            // Handle window resize for responsive videos
            $(window).on('resize', function() {
                var $videoContainer = $('.video-container');
                if ($videoContainer.length) {
                    $videoContainer.height($videoContainer.width() * 0.5625); // 16:9 aspect ratio
                }
            }).trigger('resize');
        });
        </script>
    </div><!-- .quiz-container -->
</div><!-- #primary -->

<?php 
get_footer(); 
?>
