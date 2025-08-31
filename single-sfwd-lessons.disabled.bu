<?php
/**
 * Single Lesson Template - Two Column Layout with Video Support
 * Template for displaying single LearnDash lessons
 * Includes proper video content rendering
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="learndash-wrapper">
    <?php while ( have_posts() ) : the_post(); ?>
        
        <!-- Lesson Header with Breadcrumb -->
        <header class="ld-lesson-header">
            <?php
            // Display breadcrumb navigation
            if ( function_exists( 'learndash_get_course_id' ) ) {
                $course_id = learndash_get_course_id( get_the_ID() );
                if ( $course_id ) {
                    $course_title = get_the_title( $course_id );
                    $course_url = get_permalink( $course_id );
                    echo '<nav class="ld-breadcrumbs">';
                    echo '<a href="' . esc_url( $course_url ) . '">' . esc_html( $course_title ) . '</a>';
                    echo ' > <span class="current-lesson">' . get_the_title() . '</span>';
                    echo '</nav>';
                }
            }
            ?>
            
            <h1 class="entry-title"><?php the_title(); ?></h1>
            
            <?php
            // Display lesson progress if available
            if ( function_exists( 'learndash_lesson_progress' ) ) {
                $lesson_progress = learndash_lesson_progress( get_the_ID(), get_current_user_id() );
                if ( ! empty( $lesson_progress ) ) {
                    $progress_percentage = isset( $lesson_progress['percentage'] ) ? $lesson_progress['percentage'] : 0;
                    echo '<div class="ld-progress">';
                    echo '<div class="ld-progress-bar" style="width: ' . $progress_percentage . '%"></div>';
                    echo '</div>';
                    echo '<div class="ld-progress-stats">';
                    echo '<span>התקדמות: ' . $progress_percentage . '%</span>';
                    echo '</div>';
                }
            }
            ?>
        </header>

        <!-- Main Lesson Content -->
        <div class="ld-lesson-content">
            
            <!-- LearnDash Content with Video Support -->
            <div class="learndash_content">
                <?php
                // Force video display - get lesson video settings directly
                $lesson_id = get_the_ID();
                $course_id = learndash_get_course_id($lesson_id);
                
                // Get video URL from lesson settings
                $lesson_settings = learndash_get_setting($lesson_id);
                $video_url = '';
                
                if (!empty($lesson_settings['lesson_video_url'])) {
                    $video_url = $lesson_settings['lesson_video_url'];
                } elseif (!empty($lesson_settings['sfwd-lessons_lesson_video_url'])) {
                    $video_url = $lesson_settings['sfwd-lessons_lesson_video_url'];
                }
                
                // Also check meta fields directly
                if (empty($video_url)) {
                    $video_url = get_post_meta($lesson_id, '_sfwd-lessons', true);
                    if (is_array($video_url) && !empty($video_url['sfwd-lessons_lesson_video_url'])) {
                        $video_url = $video_url['sfwd-lessons_lesson_video_url'];
                    } else {
                        $video_url = get_post_meta($lesson_id, 'lesson_video_url', true);
                    }
                }
                
                // Display video if found
                if (!empty($video_url)) {
                    echo '<div class="ld-video">';
                    echo '<div class="ld-video-container">';
                    
                    // Convert YouTube URL to embed format if needed
                    if (strpos($video_url, 'youtu.be') !== false || strpos($video_url, 'youtube.com') !== false) {
                        // Extract video ID
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches);
                        if (!empty($matches[1])) {
                            $video_id = $matches[1];
                            echo '<iframe width="100%" height="400" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
                        }
                    } else {
                        // For other video URLs, try direct embed
                        echo '<video width="100%" height="400" controls><source src="' . esc_url($video_url) . '"></video>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    echo '<br>';
                }
                
                // Display lesson content
                the_content();
                ?>
            </div>

            <?php
            // Display lesson topics if available
            if ( function_exists( 'learndash_get_topic_list' ) ) {
                $course_id = learndash_get_course_id( get_the_ID() );
                $topics = learndash_get_topic_list( get_the_ID(), $course_id );
                
                if ( ! empty( $topics ) ) {
                    echo '<div class="ld-item-list">';
                    echo '<h3 class="lesson-topics-title">נושאים בשיעור</h3>';
                    
                    foreach ( $topics as $topic ) {
                        // Check if topic is completed using LearnDash function
                        $is_completed = learndash_is_topic_complete( get_current_user_id(), $topic->ID, $course_id );
                        
                        echo '<div class="ld-item-list-item">';
                        echo '<a href="' . get_permalink( $topic->ID ) . '" class="ld-item-name">';
                        echo esc_html( $topic->post_title );
                        echo '</a>';
                        echo '<span class="ld-status ' . ( $is_completed ? 'ld-status-complete' : 'ld-status-incomplete' ) . '">';
                        echo $is_completed ? 'הושלם' : 'לא הושלם';
                        echo '</span>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            }
            ?>

            <?php
            // Lesson navigation buttons
            if ( function_exists( 'learndash_get_course_id' ) ) {
                $course_id = learndash_get_course_id( get_the_ID() );
                if ( $course_id ) {
                    echo '<div class="ld-lesson-navigation">';
                    
                    // Previous lesson link
                    $prev_lesson = learndash_previous_post_link( get_the_ID(), true, '', 'sfwd-lessons' );
                    if ( ! empty( $prev_lesson ) ) {
                        echo '<a href="' . get_permalink( $prev_lesson ) . '" class="ld-button ld-button-secondary">שיעור קודם</a>';
                    }
                    
                    // Back to course link
                    echo '<a href="' . get_permalink( $course_id ) . '" class="ld-button ld-button-outline">חזרה לקורס</a>';
                    
                    // Next lesson link
                    $next_lesson = learndash_next_post_link( get_the_ID(), true, '', 'sfwd-lessons' );
                    if ( ! empty( $next_lesson ) ) {
                        echo '<a href="' . get_permalink( $next_lesson ) . '" class="ld-button ld-button-primary">שיעור הבא</a>';
                    }
                    
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Sidebar content will be added via CSS ::after pseudo-element -->

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
