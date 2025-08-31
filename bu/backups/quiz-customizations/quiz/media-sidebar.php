<?php
/**
 * Media Sidebar Template Part
 * 
 * Displays a sidebar with ACF media content for quiz questions.
 * 
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Function to safely render video content
function lilac_render_video_content($video_url) {
    if (!is_string($video_url) || empty($video_url)) {
        return '';
    }
    
    // Check if YouTube URL
    $youtube_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $video_url, $matches) ||
        preg_match('/youtu\.be\/([\w-]+)/', $video_url, $matches)) {
        $youtube_id = $matches[1];
    }
    
    $output = '<div class="media-item-video">';
    
    if ($youtube_id) {
        $output .= '<div class="youtube-container">';
        $output .= '<iframe src="https://www.youtube.com/embed/' . $youtube_id . '" frameborder="0" allowfullscreen></iframe>';
        $output .= '</div>';
    } else {
        $output .= '<a href="' . esc_url($video_url) . '" target="_blank" class="video-button">צפה בסרטון</a>';
    }
    
    $output .= '</div>';
    return $output;
}

// Only display sidebar if we have media items
if (!empty($media_items) && is_array($media_items)): 
?>
<aside class="quiz-media-sidebar">
    <h3>תוכן נוסף לשאלות</h3>
    
    <?php foreach ($media_items as $item): 
        // Skip if item isn't an array
        if (!is_array($item)) continue;
        
        // Get required fields with default values
        $question_id = isset($item['question_id']) ? $item['question_id'] : 0;
        $title = isset($item['title']) ? $item['title'] : '';
        $hint = isset($item['hint']) ? $item['hint'] : '';
        $image = isset($item['image']) ? $item['image'] : null;
        $video = isset($item['video']) ? $item['video'] : null;
    ?>
        <div class="media-item" id="media-question-<?php echo esc_attr($question_id); ?>">
            <div class="media-item-header">
                <h4><?php echo esc_html($title); ?></h4>
            </div>
            
            <div class="media-item-content">
                <?php 
                // Handle image display
                if (is_array($image) && isset($image['url']) && !empty($image['url'])): 
                ?>
                    <div class="media-item-image">
                        <img src="<?php echo esc_url($image['url']); ?>" 
                             alt="<?php echo esc_attr(isset($image['alt']) ? $image['alt'] : ''); ?>">
                    </div>
                <?php 
                endif; 
                
                // Handle video display using our safe rendering function
                if ($video) {
                    echo lilac_render_video_content($video);
                }
                
                // Handle hint display
                if (!empty($hint)): 
                ?>
                    <div class="media-item-hint">
                        <div class="hint-indicator">💡 רמז:</div>
                        <?php echo wp_kses_post($hint); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</aside>
<?php endif; ?>
