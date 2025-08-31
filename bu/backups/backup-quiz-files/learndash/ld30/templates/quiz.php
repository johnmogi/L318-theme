<?php
/**
 * Custom LearnDash Quiz Template with Media Sidebar
 *
 * This template extends the default LearnDash quiz template to include a
 * custom media sidebar for displaying additional content related to quiz questions.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @package LilacChildTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! isset( $quiz_post ) ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) ) {
	return;
}

// Custom code to check for media sidebar
$quiz_id = $quiz_post->ID;
$show_sidebar = false;
$media_items = [];

// Check if rich sidebar should be displayed (check LearnDash settings)
$show_sidebar = get_post_meta($quiz_id, 'enable_rich_sidebar', true) === 'yes';

// Allow theme to override this setting
$show_sidebar = apply_filters('lilac_quiz_show_media_sidebar', $show_sidebar, $quiz_id);

// Only process media items if sidebar is enabled
if ($show_sidebar && function_exists('learndash_get_quiz_questions')) {
    $questions = learndash_get_quiz_questions($quiz_id);
    
    if (!empty($questions) && is_array($questions)) {
        foreach ($questions as $question_id => $question) {
            if (function_exists('get_fields')) {
                // Get all ACF fields for the question
                $acf_fields = get_fields($question_id);
                
                if (!empty($acf_fields) && is_array($acf_fields)) {
                    // Create media item with defaults
                    $item = [
                        'question_id' => $question_id,
                        'title' => get_the_title($question_id),
                        'hint' => isset($acf_fields['add_hint']) ? $acf_fields['add_hint'] : '',
                        'has_image' => false,
                        'image_url' => '',
                        'image_alt' => '',
                        'has_video' => false,
                        'video_url' => ''
                    ];
                    
                    // Process image - simplified approach to avoid type issues
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
                    
                    // Process video - simplified approach
                    if (!empty($acf_fields['video_url']) && is_string($acf_fields['video_url'])) {
                        $item['has_video'] = true;
                        $item['video_url'] = $acf_fields['video_url'];
                    } 
                    elseif (!empty($acf_fields['video_question']) && is_string($acf_fields['video_question'])) {
                        $item['has_video'] = true;
                        $item['video_url'] = $acf_fields['video_question'];
                    }
                    
                    // Only add if we have media to display
                    if (!empty($item['hint']) || $item['has_image'] || $item['has_video']) {
                        $media_items[] = $item;
                    }
                }
            }
        }
    }
}

// Container class based on sidebar status
$has_media_content = $show_sidebar && !empty($media_items);

// Include modular styles - only if we're showing sidebar
if ($has_media_content && file_exists(get_stylesheet_directory() . '/includes/quiz/quiz-sidebar-styles.php')) {
    include_once(get_stylesheet_directory() . '/includes/quiz/quiz-sidebar-styles.php');
}

// Create wrapper classes
$wrapper_class = 'learndash-wrapper';
// Handle the wrapper class safely
ob_start();
if (function_exists('learndash_the_wrapper_class')) {
    learndash_the_wrapper_class();
}
$wrapper_class_output = ob_get_clean();
if (!empty($wrapper_class_output)) {
    $wrapper_class = trim($wrapper_class_output);
}
// Add our custom class
if ($has_media_content) {
    $wrapper_class .= ' quiz-with-sidebar-wrapper';
}
?>

<div class="<?php echo esc_attr($wrapper_class); ?>">
<?php
    /**
     * Fires before the quiz content starts.
     *
     * @since 3.0.0
     *
     * @param int $quiz_id Quiz ID.
     * @param int $course_id Course ID.
     * @param int $user_id   User ID.
     */
    do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );

    if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
        $shown_content_key = 'learndash-shortcode-wrap-ld_infobar-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
        if ( false === strstr( $content, $shown_content_key ) ) {
            $shortcode_out = do_shortcode( '[ld_infobar course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
            if ( ! empty( $shortcode_out ) ) {
                echo $shortcode_out;
            }
        }
    } else {
        learndash_get_template_part(
            'modules/infobar.php',
            array(
                'context'   => 'quiz',
                'course_id' => $course_id,
                'user_id'   => $user_id,
                'post'      => $quiz_post,
            ),
            true
        );
    }

    if ( ! empty( $lesson_progression_enabled ) ) :
        $last_incomplete_step = learndash_is_quiz_accessable( $user_id, $quiz_post, true, $course_id );
        if ( ! empty( $user_id ) ) {
            if ( learndash_user_progress_is_step_complete( $user_id, $course_id, $quiz_post->ID ) ) {
                $show_content = true;
            } else {
                if ( $bypass_course_limits_admin_users ) {
                    // Properly handle filter removal with correct number of arguments
                    remove_filter( 'learndash_content', 'lesson_visible_after', 1 );
                    $previous_lesson_completed = true;
                } else {
                    $previous_step_post_id = learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $quiz_post->ID );
                    if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
                        $previous_lesson_completed = false;

                        $last_incomplete_step = get_post( $previous_step_post_id );
                    } else {
                        $previous_step_post_id     = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $quiz_post->ID );
                        $previous_lesson_completed = true;
                        if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
                            $previous_lesson_completed = false;

                            $last_incomplete_step = get_post( $previous_step_post_id );
                        }
                    }

                    /**
                     * Filter to override previous step completed.
                     *
                     * @param bool $previous_lesson_completed True if previous step completed.
                     * @param int  $step_id                   Step Post ID.
                     * @param int  $user_id                   User ID.
                     */
                    $previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $quiz_post->ID, $user_id );
                }

                $show_content = $previous_lesson_completed;
            }

            // Case of sample quizzes.
            $show_content = $show_content || learndash_is_sample( $quiz_post );

            if (
                $last_incomplete_step
                && $last_incomplete_step instanceof WP_Post
                && (
                    ! learndash_is_sample( $quiz_post )
                    || (bool) $has_access
                )
            ) {
                $sub_context = '';
                if ( 'on' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_enabled' ) ) {
                    if ( ! empty( learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_url' ) ) ) {
                        if ( 'BEFORE' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_shown' ) ) {
                            if ( ! learndash_video_complete_for_step( $last_incomplete_step->ID, $course_id, $user_id ) ) {
                                $sub_context = 'video_progression';
                            }
                        }
                    }
                }

                /**
                 * Fires before the quiz progression.
                 *
                 * @since 3.0.0
                 *
                 * @param int $quiz_id   Quiz ID.
                 * @param int $course_id Course ID.
                 * @param int $user_id   User ID.
                 */
                do_action( 'learndash-quiz-progression-before', $quiz_post->ID, $course_id, $user_id );

                learndash_get_template_part(
                        'modules/messages/lesson-progression.php',
                        array(
                            'previous_item' => $last_incomplete_step,
                            'course_id'     => $course_id,
                            'user_id'       => $user_id,
                            'context'       => 'quiz',
                            'sub_context'   => $sub_context,
                        ),
                        true
                    );

                /**
                 * Fires after the quiz progress.
                 *
                 * @since 3.0.0
                 *
                 * @param int $quiz_id   Quiz ID.
                 * @param int $course_id Course ID.
                 * @param int $user_id   User ID.
                 */
                do_action( 'learndash-quiz-progression-after', $quiz_post->ID, $course_id, $user_id );
            }
        } else {
            $show_content = true;
        }
    endif;

    if ( $show_content ) :
        // Custom wrapper for sidebar layout
        if ($has_media_content): ?>
        <div class="quiz-with-sidebar-container">
            <div class="quiz-main-content">
        <?php endif; ?>

        <?php
        /**
         * Content and/or tabs
         */
        learndash_get_template_part(
            'modules/tabs.php',
            array(
                'course_id' => $course_id,
                'post_id'   => $quiz_post->ID,
                'user_id'   => $user_id,
                'content'   => $content,
                'materials' => $materials,
                'context'   => 'quiz',
            ),
            true
        );

        if ( $attempts_left ) :

            /**
             * Fires before the actual quiz content (not WP_Editor content).
             *
             * @since 3.0.0
             *
             * @param int $quiz_id   Quiz ID.
             * @param int $course_id Course ID.
             * @param int $user_id   User ID.
             */
            do_action( 'learndash-quiz-actual-content-before', $quiz_post->ID, $course_id, $user_id );

            echo '<div class="ld-quiz-content">';

            if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
                $shown_content_key = 'learndash-shortcode-wrap-ld_quiz-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
                if ( false === strstr( $content, $shown_content_key ) ) {
                    $shortcode_out = do_shortcode( '[ld_quiz quiz_id="' . $quiz_post->ID . '" course_id="' . $course_id . '" user_id="' . $user_id . '"]' );
                    if ( ! empty( $shortcode_out ) ) {
                        echo $shortcode_out;
                    }
                }
            } else {
                echo $quiz_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs quiz content
            }

            echo '</div>';

            /**
             * Fires after the actual quiz content (not WP_Editor content).
             *
             * @since 3.0.0
             *
             * @param int $quiz_id   Quiz ID.
             * @param int $course_id Course ID.
             * @param int $user_id   User ID.
             */
            do_action( 'learndash-quiz-actual-content-after', $quiz_post->ID, $course_id, $user_id );

        else :

            /**
             * Display an alert
             */

            /**
             * Fires before the quiz attempts alert.
             *
             * @since 3.0.0
             *
             * @param int $quiz_id   Quiz ID.
             * @param int $course_id Course ID.
             * @param int $user_id   User ID.
             */
            do_action( 'learndash-quiz-attempts-alert-before', $quiz_post->ID, $course_id, $user_id );

            learndash_get_template_part(
                'modules/alert.php',
                array(
                    'type'    => 'warning',
                    'icon'    => 'alert',
                    'message' => $attempts_message,
                ),
                true
            );

            /**
             * Fires after the quiz attempts alert.
             *
             * @since 3.0.0
             *
             * @param int $quiz_id   Quiz ID.
             * @param int $course_id Course ID.
             * @param int $user_id   User ID.
             */
            do_action( 'learndash-quiz-attempts-alert-after', $quiz_post->ID, $course_id, $user_id );

        endif;

        // Close main content if we have a sidebar
        if ($has_media_content): ?>
            </div><!-- .quiz-main-content -->
            
            <aside class="quiz-media-sidebar">
                <h3>תוכן נוסף לשאלות</h3>
                
                <?php foreach ($media_items as $item): ?>
                    <div class="media-item" id="media-question-<?php echo esc_attr($item['question_id']); ?>">
                        <div class="media-item-header">
                            <h4><?php echo esc_html($item['title']); ?></h4>
                        </div>
                        
                        <div class="media-item-content">
                            <?php if ($item['has_image']): ?>
                                <div class="media-item-image">
                                    <img src="<?php echo esc_url($item['image_url']); ?>" 
                                         alt="<?php echo esc_attr($item['image_alt']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($item['has_video']): ?>
                                <div class="media-item-video">
                                    <?php
                                    $video_url = $item['video_url'];
                                    $youtube_id = '';
                                    
                                    // Check for YouTube URLs
                                    if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $video_url, $matches) ||
                                        preg_match('/youtu\.be\/([\w-]+)/', $video_url, $matches)) {
                                        
                                        $youtube_id = $matches[1];
                                        echo '<div class="youtube-container">';
                                        echo '<iframe src="https://www.youtube.com/embed/' . $youtube_id . '" frameborder="0" allowfullscreen></iframe>';
                                        echo '</div>';
                                    } else {
                                        echo '<a href="' . esc_url($video_url) . '" target="_blank" class="video-button">צפה בסרטון</a>';
                                    }
                                    ?>
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
        </div><!-- .quiz-with-sidebar-container -->
        <?php endif; ?>

    <?php endif; ?>

    <?php
    /**
     * Fires after the quiz content.
     *
     * @since 3.0.0
     *
     * @param int $quiz_id   Quiz ID.
     * @param int $course_id Course ID.
     * @param int $user_id   User ID.
     */
    do_action( 'learndash-quiz-after', $quiz_post->ID, $course_id, $user_id );
    ?>

</div> <!--/.learndash-wrapper-->
