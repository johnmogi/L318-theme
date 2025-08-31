<?php
/**
 * LearnDash Quiz Functions
 * 
 * Modular functions for processing and displaying LearnDash quizzes
 *
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Get media items for quiz questions
 * 
 * @param int $quiz_id Quiz post ID
 * @return array Array of media items
 */
function lilac_get_quiz_media_items($quiz_id) {
    $media_items = array();
    
    // Check if rich sidebar should be displayed
    $show_sidebar = get_post_meta($quiz_id, 'enable_rich_sidebar', true) === 'yes';
    
    if ($show_sidebar && function_exists('learndash_get_quiz_questions')) {
        $questions = learndash_get_quiz_questions($quiz_id);
        
        if (!empty($questions) && is_array($questions)) {
            foreach ($questions as $question_id => $question) {
                if (function_exists('get_fields')) {
                    $acf_fields = get_fields($question_id);
                    
                    if (!empty($acf_fields) && is_array($acf_fields)) {
                        $item = array(
                            'question_id' => $question_id,
                            'title' => get_the_title($question_id),
                            'hint' => isset($acf_fields['add_hint']) ? $acf_fields['add_hint'] : '',
                            'has_image' => false,
                            'image_url' => '',
                            'image_alt' => '',
                            'has_video' => false,
                            'video_url' => ''
                        );
                        
                        // Process image
                        if (!empty($acf_fields['rich_media']) && is_array($acf_fields['rich_media']) && 
                            isset($acf_fields['rich_media']['url'])) {
                            $item['has_image'] = true;
                            $item['image_url'] = $acf_fields['rich_media']['url'];
                            $item['image_alt'] = isset($acf_fields['rich_media']['alt']) ? $acf_fields['rich_media']['alt'] : '';
                        } 
                        elseif (!empty($acf_fields['image_question']) && is_array($acf_fields['image_question']) && 
                                 isset($acf_fields['image_question']['url'])) {
                            $item['has_image'] = true;
                            $item['image_url'] = $acf_fields['image_question']['url'];
                            $item['image_alt'] = isset($acf_fields['image_question']['alt']) ? $acf_fields['image_question']['alt'] : '';
                        }
                        
                        // Process video
                        if (!empty($acf_fields['video_url']) && is_string($acf_fields['video_url'])) {
                            $item['has_video'] = true;
                            $item['video_url'] = $acf_fields['video_url'];
                        } 
                        elseif (!empty($acf_fields['video_question']) && is_string($acf_fields['video_question'])) {
                            $item['has_video'] = true;
                            $item['video_url'] = $acf_fields['video_question'];
                        }
                        
                        // Only add if we have media
                        if (!empty($item['hint']) || $item['has_image'] || $item['has_video']) {
                            $media_items[] = $item;
                        }
                    }
                }
            }
        }
    }
    
    return array(
        'show_sidebar' => $show_sidebar,
        'has_media' => !empty($media_items),
        'items' => $media_items
    );
}

/**
 * Generate YouTube embed from URL
 *
 * @param string $url YouTube URL
 * @return string HTML output
 */
function lilac_generate_youtube_embed($url) {
    $youtube_id = '';
    $output = '';
    
    // Check for YouTube URLs
    if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $url, $matches) ||
        preg_match('/youtu\.be\/([\w-]+)/', $url, $matches)) {
        
        $youtube_id = $matches[1];
        $output = '<div class="youtube-container">';
        $output .= '<iframe src="https://www.youtube.com/embed/' . $youtube_id . '" frameborder="0" allowfullscreen></iframe>';
        $output .= '</div>';
    } else {
        $output = '<a href="' . esc_url($url) . '" target="_blank" class="video-button">צפה בסרטון</a>';
    }
    
    return $output;
}

/**
 * Render the media sidebar
 *
 * @param array $media_data Media data from lilac_get_quiz_media_items()
 * @return string HTML output
 */
function lilac_render_media_sidebar($media_data) {
    if (!$media_data['show_sidebar'] || !$media_data['has_media']) {
        return '';
    }
    
    ob_start();
    ?>
    <aside class="quiz-media-sidebar">
        <h3>תוכן נוסף לשאלות</h3>
        
        <?php foreach ($media_data['items'] as $item): ?>
            <div class="media-item" id="media-question-<?php echo esc_html($item['question_id']); ?>">
                <div class="media-item-header">
                    <h4><?php echo esc_html($item['title']); ?></h4>
                </div>
                
                <div class="media-item-content">
                    <?php if ($item['has_image']): ?>
                        <div class="media-item-image">
                            <img src="<?php echo esc_url($item['image_url']); ?>" 
                                 alt="<?php echo esc_html($item['image_alt']); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($item['has_video']): ?>
                        <div class="media-item-video">
                            <?php echo lilac_generate_youtube_embed($item['video_url']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['hint'])): ?>
                        <div class="media-item-hint">
                            <div><strong>💡 רמז:</strong></div>
                            <?php echo wp_kses_post($item['hint']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </aside>
    <?php
    return ob_get_clean();
}

/**
 * Add markup before LearnDash quiz content
 */
function lilac_before_quiz_content() {
    $quiz_id = get_the_ID();
    $media_data = lilac_get_quiz_media_items($quiz_id);
    
    if ($media_data['show_sidebar'] && $media_data['has_media']) {
        echo '<div class="quiz-with-sidebar-container">';
        echo '<div class="quiz-main-content">';
    }
}

/**
 * Add markup after LearnDash quiz content
 */
function lilac_after_quiz_content() {
    $quiz_id = get_the_ID();
    $media_data = lilac_get_quiz_media_items($quiz_id);
    
    if ($media_data['show_sidebar'] && $media_data['has_media']) {
        echo '</div>'; // Close .quiz-main-content
        echo lilac_render_media_sidebar($media_data);
        echo '</div>'; // Close .quiz-with-sidebar-container
    }
}
