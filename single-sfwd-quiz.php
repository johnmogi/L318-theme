<?php
/**
 * Single Quiz Template - Clean Version
 * Preserves enforce hint and rich media sidebar functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get quiz ID and settings
$quiz_id = get_the_ID();
$enforce_hint = get_post_meta($quiz_id, 'lilac_quiz_enforce_hint', true);
$has_sidebar = get_post_meta($quiz_id, 'lilac_quiz_enable_sidebar', true);

// Add body classes for styling and functionality
add_filter('body_class', function($classes) use ($enforce_hint, $has_sidebar) {
    if ($enforce_hint === '1' || $enforce_hint === 'yes') {
        $classes[] = 'lilac-quiz-hint-enforced';
    }
    if ($has_sidebar === '1' || $has_sidebar === 'yes') {
        $classes[] = 'lilac-quiz-has-sidebar';
    }
    return $classes;
});

// Enqueue necessary scripts and styles
add_action('wp_enqueue_scripts', function() {
    // Load LearnDash assets
    if (function_exists('learndash_asset_enqueue_scripts')) {
        learndash_asset_enqueue_scripts();
    }
    
    // Enqueue quiz-specific styles
    wp_enqueue_style(
        'lilac-quiz-styles',
        get_stylesheet_directory_uri() . '/assets/css/quiz.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/css/quiz.css')
    );
    
    // Enqueue quiz functionality script
    wp_enqueue_script(
        'lilac-quiz-main',
        get_stylesheet_directory_uri() . '/assets/js/quiz.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/assets/js/quiz.js'),
        true
    );
    
    // Get quiz meta data
    $quiz_id = get_the_ID();
    $enforce_hint = get_post_meta($quiz_id, '_lilac_quiz_enforce_hint', true);
    $has_sidebar = get_post_meta($quiz_id, '_lilac_quiz_toggle_sidebar', true);
    
    // Localize script with quiz data
    wp_localize_script('lilac-quiz-main', 'lilacQuizData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'quizId' => $quiz_id,
        'enforceHint' => ($enforce_hint === '1' || $enforce_hint === 'yes'),
        'hasSidebar' => ($has_sidebar === '1' || $has_sidebar === 'yes')
    ));
});

// Get header
get_header();
?>

<main id="primary" class="site-main">
    <div class="quiz-container <?php echo ($has_sidebar ? 'quiz-with-sidebar' : ''); ?>">
        <div class="quiz-content">
            <?php
            if (have_posts()) {
                while (have_posts()) {
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('sfwd-quiz'); ?>>
                        <header class="entry-header">
                            <h1 class="entry-title"><?php the_title(); ?></h1>
                        </header>
                        <div class="entry-content">
                            <div id="quiz-content-wrapper">
                                <?php echo do_shortcode('[ld_quiz quiz_id="' . $quiz_id . '"]'); ?>
                            </div>
                        </div>
                    </article>
                    <?php
                }
            }
            ?>
        </div><!-- .quiz-content -->
        
        <?php if ($has_sidebar) : ?>
            <aside id="quiz-context" class="ld-quiz-sidebar">
                <div id="question-media">
                    <div class="media-content question-media-image">
                        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/placeholder-quiz.jpg'); ?>" 
                             alt="" 
                             class="fallback-image"
                             style="display: none;">
                    </div>
                    <div class="media-error" style="display: none;">
                        <p>Media could not be loaded</p>
                    </div>
                </div>
                <?php if ($enforce_hint) : ?>
                    <div class="quiz-hint-container" style="display: none;">
                        <h3>Need a hint?</h3>
                        <div class="hint-content"></div>
                    </div>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </div><!-- .quiz-container -->
</main>

<?php
// Output the buffered content
$output = ob_get_clean();
echo apply_filters('lilac_quiz_content', $output);

get_footer();
?>
