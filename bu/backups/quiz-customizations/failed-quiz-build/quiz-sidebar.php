<?php
/**
 * LearnDash Quiz Sidebar Functions
 * 
 * Functions for generating and displaying the media sidebar for LearnDash quizzes
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Check if a quiz has a sidebar enabled
 * 
 * @param int $quiz_id The quiz post ID
 * @return bool Whether the sidebar is enabled
 */
function lilac_quiz_has_sidebar($quiz_id) {
    return get_post_meta($quiz_id, 'enable_rich_sidebar', true) === 'yes';
}

/**
 * Get media content for quiz questions
 *
 * @param int $quiz_id The quiz post ID
 * @return array Array of media items by question
 */
function lilac_get_quiz_media_content($quiz_id) {
    $media_items = array();
    $questions = array();
    
    // Check if ACF is active
    if (!function_exists('get_field')) {
        return $media_items;
    }
    
    // First check if LearnDash function is available
    if (function_exists('learndash_get_quiz_questions')) {
        $questions = learndash_get_quiz_questions($quiz_id);
    }
    
    // Collect media items for each question
    if (!empty($questions) && is_array($questions)) {
        foreach ($questions as $question_id => $question) {
            $content = array(
                'question_id' => $question_id,
                'title' => get_the_title($question_id),
                'has_media' => false,
                'media_type' => '',
                'media_url' => '',
                'media_alt' => '',
                'hint' => ''
            );
            
            // Get ACF fields for this question
            $fields = get_fields($question_id);
            
            if (!empty($fields)) {
                // Check for rich media (image)
                if (!empty($fields['rich_media']) && is_array($fields['rich_media'])) {
                    $content['has_media'] = true;
                    $content['media_type'] = 'image';
                    $content['media_url'] = $fields['rich_media']['url'];
                    $content['media_alt'] = isset($fields['rich_media']['alt']) ? $fields['rich_media']['alt'] : $content['title'];
                }
                
                // Check for video
                if (!empty($fields['video_url'])) {
                    $content['has_media'] = true;
                    $content['media_type'] = 'video';
                    $content['media_url'] = $fields['video_url'];
                }
                
                // Check for hint
                if (!empty($fields['add_hint'])) {
                    $content['hint'] = $fields['add_hint'];
                }
                
                // Only add if it has media or hint
                if ($content['has_media'] || !empty($content['hint'])) {
                    $media_items[$question_id] = $content;
                }
            }
        }
    }
    
    return $media_items;
}

/**
 * Generate HTML for a video embed (YouTube or other)
 *
 * @param string $url Video URL
 * @return string HTML for video embed
 */
function lilac_generate_video_embed($url) {
    $output = '';
    
    // Check for YouTube
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches) || 
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        
        $video_id = $matches[1];
        $output = '<div class="quiz-sidebar-video-wrapper">';
        $output .= '<iframe width="100%" height="200" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
        $output .= '</div>';
    } else {
        // Not YouTube, offer link
        $output = '<a href="' . esc_url($url) . '" target="_blank" class="quiz-sidebar-video-link">';
        $output .= '<span class="dashicons dashicons-video-alt3"></span> ' . esc_html__('צפה בסרטון', 'lilac');
        $output .= '</a>';
    }
    
    return $output;
}

/**
 * Display the quiz sidebar
 *
 * @param int $quiz_id The quiz post ID
 * @return void
 */
function lilac_display_quiz_sidebar($quiz_id) {
    if (!lilac_quiz_has_sidebar($quiz_id)) {
        return;
    }
    
    $media_items = lilac_get_quiz_media_content($quiz_id);
    
    if (empty($media_items)) {
        return;
    }
    
    // Begin sidebar output
    ?>
    <div class="lilac-quiz-sidebar" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
        <div class="lilac-quiz-sidebar-inner">
            <h3 class="lilac-quiz-sidebar-title"><?php esc_html_e('תוכן נוסף', 'lilac'); ?></h3>
            
            <div class="lilac-quiz-sidebar-items">
                <?php foreach ($media_items as $item_id => $item): ?>
                <div class="lilac-quiz-sidebar-item" data-question-id="<?php echo esc_attr($item_id); ?>">
                    <h4 class="lilac-quiz-sidebar-item-title"><?php echo esc_html($item['title']); ?></h4>
                    
                    <?php if ($item['has_media']): ?>
                        <?php if ($item['media_type'] === 'image'): ?>
                        <div class="lilac-quiz-sidebar-image">
                            <img src="<?php echo esc_url($item['media_url']); ?>" 
                                 alt="<?php echo esc_attr($item['media_alt']); ?>">
                        </div>
                        <?php elseif ($item['media_type'] === 'video'): ?>
                        <div class="lilac-quiz-sidebar-video">
                            <?php echo lilac_generate_video_embed($item['media_url']); ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['hint'])): ?>
                    <div class="lilac-quiz-sidebar-hint">
                        <h5><span class="lilac-hint-icon">💡</span> רמז:</h5>
                        <div class="lilac-quiz-sidebar-hint-content">
                            <?php echo wp_kses_post($item['hint']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Generate CSS for the quiz sidebar
 *
 * @return string CSS for the quiz sidebar
 */
function lilac_get_quiz_sidebar_styles() {
    ob_start();
    ?>
    <style>
        /* Quiz Sidebar Styles */
        .lilac-quiz-layout {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            position: relative;
        }
        
        .lilac-quiz-main {
            flex: 1;
            min-width: 65%;
        }
        
        .lilac-quiz-sidebar {
            width: 30%;
            position: sticky;
            top: 30px;
            align-self: flex-start;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
            padding: 0;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .lilac-quiz-sidebar-inner {
            padding: 20px;
        }
        
        .lilac-quiz-sidebar-title {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            color: #333;
        }
        
        .lilac-quiz-sidebar-item {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #e9ecef;
        }
        
        .lilac-quiz-sidebar-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .lilac-quiz-sidebar-item-title {
            font-size: 1.1rem;
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
        }
        
        .lilac-quiz-sidebar-image img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lilac-quiz-sidebar-video {
            margin-bottom: 15px;
        }
        
        .lilac-quiz-sidebar-video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }
        
        .lilac-quiz-sidebar-video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 4px;
        }
        
        .lilac-quiz-sidebar-video-link {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .lilac-quiz-sidebar-video-link:hover {
            background-color: #0069d9;
            color: white;
        }
        
        .lilac-quiz-sidebar-hint {
            background-color: #f0f8ff;
            border-right: 3px solid #007bff;
            padding: 12px 15px;
            border-radius: 4px;
        }
        
        .lilac-quiz-sidebar-hint h5 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1rem;
            color: #495057;
        }
        
        .lilac-hint-icon {
            margin-left: 5px;
            font-size: 1.2rem;
        }
        
        .lilac-quiz-sidebar-hint-content {
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* RTL support */
        html[dir="rtl"] .lilac-quiz-sidebar-hint {
            border-right: 3px solid #007bff;
            border-left: none;
        }
        
        /* Active Question Highlighting */
        .lilac-quiz-sidebar-item.active {
            background-color: #e8f4fd;
            border-radius: 4px;
            padding: 15px;
            margin: -15px -15px 10px -15px;
            transition: background-color 0.3s ease;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .lilac-quiz-layout {
                flex-direction: column;
            }
            
            .lilac-quiz-sidebar {
                width: 100%;
                position: static;
                margin-top: 30px;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue the JavaScript for the quiz sidebar interaction
 */
function lilac_enqueue_quiz_sidebar_script() {
    // Make sure we're on a quiz page
    if (!is_singular('sfwd-quiz')) {
        return;
    }
    
    wp_enqueue_script(
        'lilac-quiz-sidebar', 
        get_stylesheet_directory_uri() . '/includes/quiz/quiz-sidebar.js', 
        array('jquery'), 
        '1.0.0', 
        true
    );
}
add_action('wp_enqueue_scripts', 'lilac_enqueue_quiz_sidebar_script');

/**
 * Generate the content for the quiz sidebar
 *
 * @return string HTML content for the quiz sidebar
 */
function lilac_get_quiz_sidebar_content() {
    $quiz_id = get_the_ID();
    $media_items = lilac_get_quiz_media_content($quiz_id);
    $output = '';
    
    if (empty($media_items)) {
        return '<div class="lilac-quiz-sidebar-empty">'. __('No media content available for this quiz yet.', 'hello-theme-child') .'</div>';
    }
    
    // Add data attributes for JavaScript targeting
    $output .= '<div class="lilac-quiz-sidebar-items" data-quiz-id="' . esc_attr($quiz_id) . '">';
    
    foreach ($media_items as $question_id => $item) {
        $output .= '<div id="quiz-sidebar-item-' . esc_attr($question_id) . '" class="lilac-quiz-sidebar-item" data-question-id="' . esc_attr($question_id) . '">';
        
        // Add item title
        $output .= '<h4 class="lilac-quiz-sidebar-item-title">' . esc_html($item['title']) . '</h4>';
        
        // Add media content if available
        if ($item['has_media']) {
            if ($item['media_type'] === 'image') {
                $output .= '<div class="lilac-quiz-sidebar-image">';
                $output .= '<img src="' . esc_url($item['media_url']) . '" alt="' . esc_attr($item['media_alt']) . '" />';
                $output .= '</div>';
            } elseif ($item['media_type'] === 'video') {
                $output .= '<div class="lilac-quiz-sidebar-video">';
                $output .= lilac_generate_video_embed($item['media_url']);
                $output .= '</div>';
            }
        }
        
        // Add hint if available
        if (!empty($item['hint'])) {
            $output .= '<div class="lilac-quiz-sidebar-hint">';
            $output .= '<h5><span class="lilac-hint-icon">💡</span> ' . __('Hint', 'hello-theme-child') . '</h5>';
            $output .= '<div class="lilac-quiz-sidebar-hint-content">' . wpautop($item['hint']) . '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>'; // End sidebar item
    }
    
    $output .= '</div>'; // End sidebar items container
    
    return $output;
}
