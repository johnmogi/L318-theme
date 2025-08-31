<?php
/**
 * Debug Single Quiz Template with Media Sidebar
 * 
 * This is a backup of the debug-enabled quiz template.
 * Contains full debugging information for ACF field mapping and question analysis.
 * 
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Make sure LearnDash is properly initialized
if (function_exists('learndash_setup_course_data')) {
    global $post;
    learndash_setup_course_data($post);
}

/**
 * Process Question Data and Collect ACF Media
 * Get all media content from questions associated with this quiz
 */
$media_items = [];
$quiz_id = get_the_ID();
$questions = [];

// Ensure LearnDash functions exist before calling them
if (function_exists('learndash_get_quiz_questions')) {
    $questions = learndash_get_quiz_questions($quiz_id);
    
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
                    
                    // Check for image (try various field names)
                    if (!empty($acf_fields['rich_media']) && is_array($acf_fields['rich_media']) && !empty($acf_fields['rich_media']['url'])) {
                        $item['image'] = $acf_fields['rich_media'];
                    } elseif (!empty($acf_fields['image_question']) && is_array($acf_fields['image_question']) && !empty($acf_fields['image_question']['url'])) {
                        $item['image'] = $acf_fields['image_question'];
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

// Include CSS styles 
?>
<style>
    .quiz-with-sidebar-container {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .quiz-main-content {
        flex: 1;
        min-width: 65%;
        padding: 0 15px;
    }
    
    .quiz-media-sidebar {
        width: 30%;
        padding: 0 15px;
        position: sticky;
        top: 30px;
        align-self: flex-start;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .media-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
        overflow: hidden;
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
        border-radius: 4px;
    }
    
    .media-item-video {
        margin-top: 10px;
    }
    
    .media-item-hint {
        margin-top: 15px;
        border-left: 3px solid #2a7ae2;
        padding: 10px 15px;
        background: #f0f8ff;
    }
    
    .hint-indicator {
        font-weight: bold;
        margin-bottom: 8px;
    }
    
    .video-button {
        display: inline-block;
        padding: 8px 15px;
        background: #2a7ae2;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        text-align: center;
    }
    
    /* YouTube Embed Responsive Container */
    .youtube-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
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
        border-radius: 4px;
    }
    
    /* RTL Support */
    html[dir="rtl"] .media-item-hint {
        border-left: none;
        border-right: 3px solid #2a7ae2;
    }
    
    @media (max-width: 768px) {
        .quiz-with-sidebar-container {
            flex-direction: column;
        }
        
        .quiz-main-content,
        .quiz-media-sidebar {
            width: 100%;
        }
        
        .quiz-media-sidebar {
            position: static;
            margin-top: 30px;
        }
    }
    
    /* Debug Styling */
    .ld-debug-section {
        margin: 30px 0;
        padding: 20px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: monospace;
        font-size: 13px;
    }
    
    .ld-debug-section h3 {
        margin-top: 0;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
    }
    
    .ld-debug-section pre {
        background: #fff;
        padding: 10px;
        border: 1px solid #eee;
        max-height: 300px;
        overflow: auto;
    }
</style>

<div id="primary" class="content-area">
    <div class="quiz-with-sidebar-container">
        <main class="quiz-main-content">
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>

                    <div class="entry-content">
                        <?php
                        // Include the standard LearnDash quiz content
                        the_content();
                        ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </main>
        
        <?php if (!empty($media_items)): ?>
        <aside class="quiz-media-sidebar">
            <h3>תוכן נוסף לשאלות</h3>
            
            <?php foreach ($media_items as $item): ?>
                <div class="media-item" id="media-question-<?php echo esc_attr($item['question_id']); ?>">
                    <div class="media-item-header">
                        <h4><?php echo esc_html($item['title']); ?></h4>
                    </div>
                    
                    <div class="media-item-content">
                        <?php if (!empty($item['image']) && is_array($item['image']) && !empty($item['image']['url'])): ?>
                            <div class="media-item-image">
                                <img src="<?php echo esc_url($item['image']['url']); ?>" 
                                     alt="<?php echo esc_attr(isset($item['image']['alt']) ? $item['image']['alt'] : ''); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['video'])): ?>
                            <div class="media-item-video">
                                <?php
                                // Handle video content
                                $video_url = $item['video'];
                                $youtube_id = '';
                                
                                if (is_string($video_url) && 
                                    (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $video_url, $matches) ||
                                     preg_match('/youtu\.be\/([\w-]+)/', $video_url, $matches))) {
                                    $youtube_id = $matches[1];
                                    echo '<div class="youtube-container">';
                                    echo '<iframe src="https://www.youtube.com/embed/' . $youtube_id . '" frameborder="0" allowfullscreen></iframe>';
                                    echo '</div>';
                                } elseif (is_string($video_url)) {
                                    echo '<a href="' . esc_url($video_url) . '" target="_blank" class="video-button">צפה בסרטון</a>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['hint'])): ?>
                            <div class="media-item-hint">
                                <div class="hint-indicator">💡 רמז:</div>
                                <?php echo wp_kses_post($item['hint']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </aside>
        <?php endif; ?>
    </div>
</div>

<?php
// Admin-only debug section
if (current_user_can('administrator')): ?>
<div class="ld-debug-section">
    <h3>Media Field Mapping Debug</h3>
    <p>This section helps identify which fields are being used for media content.</p>
    
    <?php foreach ($questions as $question_id => $question): ?>
        <div style="margin-bottom:20px;padding:15px;background:#fff;border:1px solid #eee;">
            <h4><?php echo esc_html(get_the_title($question_id)); ?></h4>
            <?php 
            if (function_exists('get_fields')) {
                $fields = get_fields($question_id);
                if (!empty($fields)) {
                    echo '<ul>';
                    foreach ($fields as $key => $value) {
                        echo '<li><strong>' . esc_html($key) . ':</strong> ';
                        if (is_array($value)) {
                            echo '<pre>' . print_r($value, true) . '</pre>';
                        } else {
                            echo wp_kses_post($value);
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No ACF fields found</p>';
                }
            }
            ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php get_footer(); ?>
