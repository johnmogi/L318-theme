<?php
/**
 * Custom Single Quiz Template with Media Sidebar
 * 
 * This template provides a dedicated sidebar for ACF media content alongside LearnDash quizzes.
 * Only displays the sidebar if the LearnDash option is enabled.
 *
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

get_header();

// Initialize variables
$quiz_id = get_the_ID();
$show_sidebar = false;
$media_items = [];

// Check if rich sidebar should be displayed (check LearnDash settings)
$show_sidebar = get_post_meta($quiz_id, 'enable_rich_sidebar', true) === 'yes';

// Allow theme to override this setting
$show_sidebar = apply_filters('lilac_quiz_show_media_sidebar', $show_sidebar, $quiz_id);

// Only process media items if sidebar is enabled
if ($show_sidebar && function_exists('learndash_get_quiz_questions')) {
    $questions = learndash_get_quiz_questions($quiz_id);
    
    // Debug: Print ACF fields for the first question
    if (current_user_can('administrator') && !empty($questions)) {
        $first_question_id = key($questions);
        if ($first_question_id) {
            $debug_fields = get_fields($first_question_id);
            echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px; border: 1px solid #ddd; direction: ltr; text-align: left; z-index: 9999; position: relative;">';
            echo '<h3>Debug: ACF Fields for Question ID ' . esc_html($first_question_id) . '</h3>';
            echo '<pre style="max-height: 300px; overflow: auto;">';
            print_r($debug_fields);
            echo '</pre>';
            echo '</div>';
        }
    }
    
    if (!empty($questions) && is_array($questions)) {
        foreach ($questions as $question_id => $question) {
            if (function_exists('get_fields')) {
                $acf_fields = get_fields($question_id);
                
                if (!empty($acf_fields) && is_array($acf_fields)) {
                    $item = [
                        'question_id' => $question_id,
                        'title' => get_the_title($question_id),
                        'hint' => isset($acf_fields['add_hint']) ? $acf_fields['add_hint'] : '',
                        'image' => null,
                        'video' => null
                    ];
                    
                    // Check for image (handle both URL and array formats)
                    if (!empty($acf_fields['rich_media'])) {
                        if (is_array($acf_fields['rich_media']) && !empty($acf_fields['rich_media']['url'])) {
                            $item['image'] = $acf_fields['rich_media'];
                        } elseif (is_string($acf_fields['rich_media'])) {
                            $item['image'] = array('url' => $acf_fields['rich_media']);
                        } elseif (is_numeric($acf_fields['rich_media'])) {
                            // Handle case where it's an attachment ID
                            $image_url = wp_get_attachment_url($acf_fields['rich_media']);
                            if ($image_url) {
                                $item['image'] = array('url' => $image_url);
                            }
                        }
                    }
                    
                    // Check for video (try various field names)
                    if (!empty($acf_fields['video_url'])) {
                        $item['video'] = $acf_fields['video_url'];
                    } elseif (!empty($acf_fields['video_question'])) {
                        $item['video'] = $acf_fields['video_question'];
                    }
                    
                    // Only add if we have media
                    if (!empty($item['hint']) || !empty($item['image']) || !empty($item['video'])) {
                        $media_items[] = $item;
                    }
                }
            }
        }
    }
}

// Determine if we're showing a sidebar layout or standard layout
$has_media_content = $show_sidebar && !empty($media_items);
$container_class = $has_media_content ? 'quiz-with-sidebar-container' : 'quiz-standard-container';
?>

<?php if ($has_media_content): // Only include the styles if we have sidebar content ?>
<style>
    .quiz-with-sidebar-container {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 30px;
    }
    
    .quiz-main-content {
        flex: 1;
        min-width: 65%;
    }
    
    .quiz-media-sidebar {
        width: 30%;
        position: sticky;
        top: 30px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .media-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .media-item-header {
        background: #f5f5f5;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }
    
    .media-item-content {
        padding: 15px;
    }
    
    .media-item-image img {
        max-width: 100%;
        height: auto;
    }
    
    .media-item-hint {
        margin-top: 15px;
        padding: 10px 15px;
        background: #f0f8ff;
        border-left: 3px solid #2a7ae2;
    }
    
    .youtube-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .youtube-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    @media (max-width: 768px) {
        .quiz-with-sidebar-container {
            flex-direction: column;
        }
        
        .quiz-main-content,
        .quiz-media-sidebar {
            width: 100%;
        }
    }
</style>
<?php endif; ?>

<div id="primary" class="content-area">
    <div class="<?php echo esc_attr($container_class); ?>">
        <main class="quiz-main-content">
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </main>
        
        <?php if ($has_media_content): ?>
        <aside class="quiz-media-sidebar">
            <h3>תוכן נוסף לשאלות</h3>
            
            <?php foreach ($media_items as $item): ?>
                <div class="media-item" id="media-question-<?php echo esc_attr($item['question_id']); ?>">
                    <div class="media-item-header">
                        <h4><?php echo esc_html($item['title']); ?></h4>
                        <?php if (current_user_can('administrator')): ?>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                Question ID: <?php echo esc_html($item['question_id']); ?>
                                <pre style="display: none; font-size: 10px; max-height: 200px; overflow: auto;" class="acf-debug"><?php 
                                    echo esc_html(print_r(get_fields($item['question_id']), true)); 
                                ?></pre>
                                <a href="#" class="toggle-debug" style="margin-left: 10px;">[Toggle Debug]</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="media-item-content">
                        <?php 
                        // Initialize variables
                        $image = is_array($item['image'] ?? null) ? $item['image'] : [];
                        $video = $item['video'] ?? '';
                        $hint = $item['hint'] ?? '';
                        
                        // Handle video content (takes priority over image)
                        if (!empty($video) && is_string($video)) {
                            echo '<div class="media-item-video">';
                            
                            if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $video, $matches) ||
                                preg_match('/youtu\.be\/([\w-]+)/', $video, $matches)) {
                                $youtube_id = $matches[1];
                                echo '<div class="youtube-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">';
                                echo '<iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" ';
                                echo 'src="https://www.youtube.com/embed/' . esc_attr($youtube_id) . '?rel=0" ';
                                echo 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ';
                                echo 'allowfullscreen></iframe>';
                                echo '</div>';
                            } else {
                                echo '<a href="' . esc_url($video) . '" target="_blank" class="video-button">צפה בסרטון</a>';
                            }
                            
                            echo '</div>';
                        }
                        // Handle image display (only if no video)
                        elseif (!empty($image)) {
                            $image_url = is_array($image) ? ($image['url'] ?? '') : $image;
                            if (!empty($image_url)) {
                                $image_alt = (is_array($image) && !empty($image['alt'])) ? esc_attr($image['alt']) : '';
                                echo '<div class="media-item-image" style="text-align: center; margin: 10px 0;">';
                                echo '<img src="' . esc_url($image_url) . '" alt="' . $image_alt . '" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">';
                                echo '</div>';
                            }
                        }
                        
                        // Display hint if available
                        if (!empty($hint)) {
                            echo '<div class="media-item-hint">';
                            echo '<div><strong>💡 רמז:</strong></div>';
                            echo wp_kses_post($hint);
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </aside>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>

<?php if (current_user_can('administrator')): ?>
<script>
jQuery(document).ready(function($) {
    $('.toggle-debug').on('click', function(e) {
        e.preventDefault();
        $(this).siblings('.acf-debug').toggle();
    });
});
</script>
<?php endif; ?>
