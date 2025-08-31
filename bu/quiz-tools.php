<?php
/**
 * Quiz Tools Integration
 * 
 * Loads specialized LearnDash quiz tools for export and answer management.
 */

// Load the Quiz Exporter class
require_once get_stylesheet_directory() . '/includes/admin/class-quiz-exporter.php';

// Load the Quiz Answer Importer class
require_once get_stylesheet_directory() . '/includes/admin/class-quiz-answer-importer.php';

/**
 * Add LearnDash quiz tools initialization
 */
function ccr_initialize_quiz_tools() {
    if (is_admin()) {
        // Check if LearnDash is active
        if (defined('LEARNDASH_VERSION')) {
            // Add debug information to LearnDash quiz pages
            add_action('admin_footer', 'ccr_add_quiz_debug_info');
        }
    }
}
add_action('plugins_loaded', 'ccr_initialize_quiz_tools');

/**
 * Add debug information to quiz pages for troubleshooting
 */
function ccr_add_quiz_debug_info() {
    $screen = get_current_screen();
    
    // Only on quiz edit pages
    if (is_object($screen) && $screen->post_type === 'sfwd-quiz' && $screen->base === 'post') {
        global $post;
        $quiz_id = $post->ID;
        
        $pro_quiz_id = get_post_meta($quiz_id, 'quiz_pro_id', true);
        if (empty($pro_quiz_id)) {
            // Try alternative meta keys
            $alt_keys = ['_quiz_pro', 'quiz_pro', 'quiz_pro_primary_id'];
            foreach ($alt_keys as $key) {
                $alt_id = get_post_meta($quiz_id, $key, true);
                if (!empty($alt_id)) {
                    $pro_quiz_id = $alt_id;
                    break;
                }
            }
        }
        
        // Get question count
        $questions = get_post_meta($quiz_id, 'quiz_questions', true);
        $question_count = is_array($questions) ? count($questions) : 0;
        
        ?>
        <div class="postbox" style="margin-top: 20px;">
            <h2 class="hndle"><span>Quiz Debug Information</span></h2>
            <div class="inside">
                <p><strong>Quiz ID:</strong> <?php echo esc_html($quiz_id); ?></p>
                <p><strong>ProQuiz ID:</strong> <?php echo esc_html($pro_quiz_id); ?></p>
                <p><strong>Question Count:</strong> <?php echo esc_html($question_count); ?></p>
                
                <?php if (!empty($pro_quiz_id)) : ?>
                <p><a href="<?php echo esc_url(admin_url('admin-post.php?action=export_single_quiz&export_single_quiz_nonce=' . wp_create_nonce('export_single_quiz_nonce') . '&quiz_id=' . $quiz_id)); ?>" class="button">Export This Quiz</a></p>
                <?php endif; ?>
                
                <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-quiz&page=quiz-export-tool')); ?>" class="button">Export Tools</a></p>
                <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-quiz&page=quiz-answer-importer')); ?>" class="button">Import Answers</a></p>
            </div>
        </div>
        <?php
    }
}
