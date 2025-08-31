<?php
/**
 * LearnDash Quiz Functions
 * 
 * Modular functions for processing and displaying LearnDash quizzes
 * Includes features for sidebar, force hint mode, and disabling top bar
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

/**
 * Output meta tags for quiz settings
 * This function adds meta tags that can be read by our JS to control quiz behavior
 */
function lilac_quiz_settings_meta_tags() {
    // Only run on single quiz pages
    if (!is_singular('sfwd-quiz')) {
        return;
    }
    
    $quiz_id = get_the_ID();
    
    // Get quiz settings from options table - we're using CCR_Quiz_Extensions::OPTION_KEY which is 'ccr_quiz_extensions_settings'
    $quiz_settings = get_option('ccr_quiz_extensions_settings', array());
    $force_hint_mode = isset($quiz_settings[$quiz_id]['force_hint_mode']) ? (bool)$quiz_settings[$quiz_id]['force_hint_mode'] : false;
    $show_hint = isset($quiz_settings[$quiz_id]['show_hint']) ? (bool)$quiz_settings[$quiz_id]['show_hint'] : false;
    $rich_sidebar = isset($quiz_settings[$quiz_id]['rich_sidebar']) ? (bool)$quiz_settings[$quiz_id]['rich_sidebar'] : false;
    $disable_topbar = isset($quiz_settings[$quiz_id]['disable_topbar']) ? (bool)$quiz_settings[$quiz_id]['disable_topbar'] : false;
    $disable_hints = isset($quiz_settings[$quiz_id]['disable_hints']) ? (bool)$quiz_settings[$quiz_id]['disable_hints'] : false;
    $post_meta_force_hint = get_post_meta($quiz_id, 'force_hint_mode', true) === 'yes';
    $post_meta_rich_sidebar = get_post_meta($quiz_id, 'enable_rich_sidebar', true) === 'yes';
    $post_meta_disable_hints = get_post_meta($quiz_id, 'disable_hints', true) === 'yes';
    
    // If either location has the setting enabled, use that
    $final_force_hint = $force_hint_mode || $post_meta_force_hint;
    $final_rich_sidebar = $rich_sidebar || $post_meta_rich_sidebar;
    $final_disable_hints = $disable_hints || $post_meta_disable_hints;
    
    // Resolve conflicts: if hint disabling is enabled, force hint mode should be disabled
    if ($final_disable_hints && $final_force_hint) {
        $final_force_hint = false;
        error_log('Lilac Quiz: Conflict detected - Force Hint Mode disabled because Disable Hints is enabled for quiz ' . $quiz_id);
    }
    
    // Output meta tags for JavaScript
    echo "<!-- Lilac Quiz Settings -->\n";
    
    // Force hint mode (next button hidden until hint viewed + correct answer)
    if ($final_force_hint) {
        echo '<meta name="lilac-quiz-force-hint" content="true">'."\n";
    } else {
        echo '<meta name="lilac-quiz-force-hint" content="false">'."\n";
    }
    
    // Show hint button
    if ($show_hint) {
        echo '<meta name="lilac-quiz-show-hint" content="true">'."\n";
    }
    
    // Rich sidebar with ACF content
    if ($final_rich_sidebar) {
        echo '<meta name="lilac-quiz-rich-sidebar" content="true">'."\n";
    }
    
    // Disable top bar navigation
    if ($disable_topbar) {
        echo '<meta name="lilac-quiz-disable-topbar" content="true">'."\n";
    }
    
    // Also output post meta settings for debugging
    if ($post_meta_force_hint) {
        echo '<meta name="lilac-quiz-force-hint-postmeta" content="true">'."\n";
    }
    
    // Disable hints meta tag (for disabling the hint functionality entirely)
    if ($final_disable_hints) {
        echo '<meta name="lilac-quiz-disable-hints" content="true">'."\n";
    } else {
        echo '<meta name="lilac-quiz-disable-hints" content="false">'."\n";
    }
    
    // Add debug information about hints status
    echo '<!-- Hint Status: ' . ($final_disable_hints ? 'Disabled' : 'Enabled') . ' -->'."
";
    
    if ($post_meta_rich_sidebar) {
        echo '<meta name="lilac-quiz-rich-sidebar-postmeta" content="true">'."\n";
    }
    
    // Add quiz ID for tracking
    echo '<meta name="lilac-quiz-id" content="'.$quiz_id.'">'."\n";
    
    // Debug: output all quiz settings
    echo "<!-- Quiz Extension Settings Debug\n";
    echo "Quiz ID: " . $quiz_id . "\n";
    echo "Force Hint Mode (option): " . ($force_hint_mode ? 'true' : 'false') . "\n";
    echo "Force Hint Mode (post meta): " . ($post_meta_force_hint ? 'true' : 'false') . "\n";
    echo "Force Hint Mode (final): " . ($final_force_hint ? 'true' : 'false') . "\n";
    echo "Rich Sidebar (option): " . ($rich_sidebar ? 'true' : 'false') . "\n";
    echo "Rich Sidebar (post meta): " . ($post_meta_rich_sidebar ? 'true' : 'false') . "\n";
    echo "Rich Sidebar (final): " . ($final_rich_sidebar ? 'true' : 'false') . "\n";
    echo "-->";
    
    // Add data attribute to the quiz container via JavaScript
    echo '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function() {
        var quizContainer = document.querySelector(".wpProQuiz_quiz");
        if (quizContainer) {
            ' . ($final_force_hint ? 'quizContainer.setAttribute("data-force-hint-mode", "true");' : '') . '
            ' . ($final_rich_sidebar ? 'quizContainer.setAttribute("data-rich-sidebar", "true");' : '') . '
            ' . ($final_disable_hints ? 'quizContainer.setAttribute("data-disable-hints", "true");' : '') . '
            console.log("Lilac Quiz Settings applied: Force Hint Mode = ' . ($final_force_hint ? 'true' : 'false') . ', Rich Sidebar = ' . ($final_rich_sidebar ? 'true' : 'false') . ', Disable Hints = ' . ($final_disable_hints ? 'true' : 'false') . '");
        }
    });</script>';
}

// Add debug message to the admin footer to confirm function execution
function lilac_debug_quiz_settings() {
    if (!is_admin() && is_singular('sfwd-quiz')) {
        echo '<!-- Quiz Debug: lilac_quiz_settings_meta_tags was called -->\n';
    }
}
// add_action('wp_footer', 'lilac_debug_quiz_settings');

// Add our meta tags function to wp_head
add_action('wp_head', 'lilac_quiz_settings_meta_tags', 5); // Lower number = higher priority
