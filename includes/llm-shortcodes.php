<?php
/**
 * LLM LearnDash Shortcodes
 * 
 * Available shortcodes:
 * - [llm_early_topics] - Displays LearnDash topics with various parameters
 * - [ld_quiz_stats] - Displays user quiz statistics
 */

/**
 * Get LearnDash quiz statistics for the current user
 * 
 * @param array $user_quizzes Array of user quiz attempts
 * @return array Statistics including averages, counts, etc.
 */
function get_ld_quiz_statistics($user_quizzes) {
    if (empty($user_quizzes) || !is_array($user_quizzes)) {
        return false;
    }

    $stats = array(
        'total_quizzes' => 0,
        'passed' => 0,
        'failed' => 0,
        'total_score' => 0,
        'total_points' => 0,
        'points_earned' => 0,
        'last_attempt' => 0,
        'quizzes_by_status' => array(
            'completed' => 0,
            'incomplete' => 0
        ),
        'quizzes_by_type' => array()
    );

    foreach ($user_quizzes as $quiz_attempt) {
        $stats['total_quizzes']++;
        
        // Track pass/fail
        if (isset($quiz_attempt['pass'])) {
            if ($quiz_attempt['pass'] == 1) {
                $stats['passed']++;
            } else {
                $stats['failed']++;
            }
        }
        
        // Track scores
        if (isset($quiz_attempt['percentage'])) {
            $stats['total_score'] += $quiz_attempt['percentage'];
        }
        
        // Track points if available
        if (isset($quiz_attempt['points_total']) && $quiz_attempt['points_total'] > 0) {
            $stats['points_earned'] += $quiz_attempt['points_earned'];
            $stats['total_points'] += $quiz_attempt['points_total'];
        }
        
        // Track last attempt
        if (isset($quiz_attempt['time']) && $quiz_attempt['time'] > $stats['last_attempt']) {
            $stats['last_attempt'] = $quiz_attempt['time'];
        }
        
        // Track status
        $status = isset($quiz_attempt['completed']) ? 'completed' : 'incomplete';
        $stats['quizzes_by_status'][$status]++;
        
        // Track quiz types if available
        if (isset($quiz_attempt['quiz'])) {
            $quiz_type = get_post_meta($quiz_attempt['quiz'], 'quiz_type', true);
            if (empty($quiz_type)) {
                $quiz_type = 'general';
            }
            if (!isset($stats['quizzes_by_type'][$quiz_type])) {
                $stats['quizzes_by_type'][$quiz_type] = 0;
            }
            $stats['quizzes_by_type'][$quiz_type]++;
        }
    }
    
    // Calculate averages
    if ($stats['total_quizzes'] > 0) {
        $stats['average_score'] = round($stats['total_score'] / $stats['total_quizzes'], 2);
        $stats['pass_rate'] = round(($stats['passed'] / $stats['total_quizzes']) * 100, 2);
        if ($stats['total_points'] > 0) {
            $stats['points_percentage'] = round(($stats['points_earned'] / $stats['total_points']) * 100, 2);
        }
    }
    
    // Format last attempt date
    if ($stats['last_attempt'] > 0) {
        $stats['last_attempt_formatted'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $stats['last_attempt']);
    } else {
        $stats['last_attempt_formatted'] = __('No attempts yet', 'hello-theme-child');
    }
    
    return $stats;
}

/**
 * LearnDash Quiz Statistics Shortcode
 * 
 * Shortcode: [ld_quiz_stats show_average="true" show_pass_rate="true" show_last_attempt="true" show_points="true"]
 */
function ld_quiz_stats_shortcode($atts) {
    // Only show to logged in users
    if (!is_user_logged_in()) {
        return '<div class="ld-quiz-stats-login-notice">' . __('Please log in to view your quiz statistics.', 'hello-theme-child') . '</div>';
    }
    
    // Default attributes
    $atts = shortcode_atts(
        array(
            'show_average' => 'true',
            'show_pass_rate' => 'true',
            'show_last_attempt' => 'true',
            'show_points' => 'true',
            'show_quiz_types' => 'true',
            'user_id' => get_current_user_id(),
            'quiz_id' => '', // Optional: filter by specific quiz
            'course_id' => '' // Optional: filter by course
        ),
        $atts,
        'ld_quiz_stats'
    );
    
    // Get user quiz attempts
    $user_quizzes = get_user_meta($atts['user_id'], '_sfwd-quizzes', true);
    
    // Filter by quiz ID if specified
    if (!empty($atts['quiz_id'])) {
        $user_quizzes = array_filter($user_quizzes, function($quiz) use ($atts) {
            return isset($quiz['quiz']) && $quiz['quiz'] == $atts['quiz_id'];
        });
    }
    
    // Filter by course ID if specified
    if (!empty($atts['course_id'])) {
        $user_quizzes = array_filter($user_quizzes, function($quiz) use ($atts) {
            return isset($quiz['course']) && $quiz['course'] == $atts['course_id'];
        });
    }
    
    // Get statistics
    $stats = get_ld_quiz_statistics($user_quizzes);
    
    if (!$stats) {
        return '<div class="ld-quiz-stats-no-data">' . __('No quiz data available.', 'hello-theme-child') . '</div>';
    }
    
    // Start output buffering
    ob_start();
    ?>
    <div class="ld-quiz-stats-container">
        <h3 class="ld-quiz-stats-title"><?php _e('Your Quiz Statistics', 'hello-theme-child'); ?></h3>
        
        <div class="ld-quiz-stats-grid">
            <?php if ($atts['show_average'] === 'true' && isset($stats['average_score'])): ?>
            <div class="ld-quiz-stat">
                <span class="ld-quiz-stat-label"><?php _e('Average Score', 'hello-theme-child'); ?>:</span>
                <span class="ld-quiz-stat-value"><?php echo esc_html($stats['average_score']); ?>%</span>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_pass_rate'] === 'true' && isset($stats['pass_rate'])): ?>
            <div class="ld-quiz-stat">
                <span class="ld-quiz-stat-label"><?php _e('Pass Rate', 'hello-theme-child'); ?>:</span>
                <span class="ld-quiz-stat-value"><?php echo esc_html($stats['pass_rate']); ?>%</span>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_last_attempt'] === 'true' && !empty($stats['last_attempt_formatted'])): ?>
            <div class="ld-quiz-stat">
                <span class="ld-quiz-stat-label"><?php _e('Last Attempt', 'hello-theme-child'); ?>:</span>
                <span class="ld-quiz-stat-value"><?php echo esc_html($stats['last_attempt_formatted']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_points'] === 'true' && isset($stats['points_percentage'])): ?>
            <div class="ld-quiz-stat">
                <span class="ld-quiz-stat-label"><?php _e('Points', 'hello-theme-child'); ?>:</span>
                <span class="ld-quiz-stat-value">
                    <?php 
                    echo sprintf(
                        __('%s of %s (%s%%)', 'hello-theme-child'),
                        number_format_i18n($stats['points_earned']),
                        number_format_i18n($stats['total_points']),
                        $stats['points_percentage']
                    );
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($atts['show_quiz_types'] === 'true' && !empty($stats['quizzes_by_type'])): ?>
        <div class="ld-quiz-types">
            <h4><?php _e('Quizzes by Type', 'hello-theme-child'); ?></h4>
            <ul class="ld-quiz-types-list">
                <?php foreach ($stats['quizzes_by_type'] as $type => $count): ?>
                <li>
                    <span class="ld-quiz-type-name"><?php echo esc_html(ucfirst($type)); ?>:</span>
                    <span class="ld-quiz-type-count"><?php echo esc_html($count); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php
    
    // Add inline styles if not already added
    if (!wp_style_is('ld-quiz-stats-styles', 'enqueued')) {
        wp_enqueue_style('ld-quiz-stats-styles', false);
        wp_add_inline_style('ld-quiz-stats-styles', '
            .ld-quiz-stats-container {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .ld-quiz-stats-title {
                margin-top: 0;
                color: #1a1a1a;
                border-bottom: 2px solid #e0e0e0;
                padding-bottom: 10px;
            }
            .ld-quiz-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            .ld-quiz-stat {
                background: white;
                padding: 15px;
                border-radius: 6px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .ld-quiz-stat-label {
                display: block;
                font-size: 0.9em;
                color: #666;
                margin-bottom: 5px;
            }
            .ld-quiz-stat-value {
                font-size: 1.2em;
                font-weight: 600;
                color: #0073aa;
            }
            .ld-quiz-types h4 {
                margin-bottom: 10px;
                color: #1a1a1a;
            }
            .ld-quiz-types-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .ld-quiz-types-list li {
                background: white;
                padding: 8px 15px;
                border-radius: 20px;
                font-size: 0.9em;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .ld-quiz-type-name {
                margin-right: 5px;
            }
            .ld-quiz-type-count {
                background: #0073aa;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8em;
                line-height: 1;
            }
        ');
    }
    
    return ob_get_clean();
}
add_shortcode('ld_quiz_stats', 'ld_quiz_stats_shortcode');

/**
 * LLM Early Topics Shortcode
 * Displays a list of LearnDash topics based on various parameters
 * 
 * Shortcode: [llm_early_topics acf_field="field_name" category_id="123" show_lesson="true"]
 */
function llm_early_topics_shortcode($atts) {
    // Default attributes
    $atts = shortcode_atts(
        array(
            'acf_field'    => '',
            'category_id'  => '',
            'show_lesson'  => 'true',
            'category'     => '', // Alternative to category_id
            'match_course' => 'false',
            'debug'        => 'false', // Add debug mode
            'columns'      => '1',     // Number of columns (1-4)
            'columns_tablet' => '',    // Optional: different columns for tablet
            'columns_mobile' => ''     // Optional: different columns for mobile
        ),
        $atts,
        'llm_early_topics'
    );
    
    // Sanitize column values
    $atts['columns'] = max(1, min(4, intval($atts['columns'])));
    $atts['columns_tablet'] = !empty($atts['columns_tablet']) ? max(1, min(4, intval($atts['columns_tablet']))) : '';
    $atts['columns_mobile'] = !empty($atts['columns_mobile']) ? max(1, min(4, intval($atts['columns_mobile']))) : '';

    // Start output buffering
    ob_start();

    // Debug: Shortcode Attributes
    if ($atts['debug'] === 'true') {
        echo '<!-- Debug: Shortcode Attributes -->';
        echo '<pre>';
        print_r($atts);
        echo '</pre>';
    }

    // Build query args for topics
    $args = array(
        'post_type'      => 'sfwd-topic',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );

    // Handle course matching if enabled
    if ($atts['match_course'] === 'true' && is_singular('sfwd-courses')) {
        $course_id = get_the_ID();
        $args['meta_query'] = array(
            array(
                'key'     => 'course_id',
                'value'   => $course_id,
                'compare' => '='
            )
        );
    }

    // Handle category filtering
    $tax_queries = array();
    $debug_info = array();
    
    // Set the taxonomy to use (default to LearnDash topic category)
    $taxonomy = !empty($atts['taxonomy']) ? $atts['taxonomy'] : 'ld_topic_category';
    $debug_info[] = 'Using taxonomy: ' . $taxonomy;

    if (!empty($atts['category_id'])) {
        $category_ids = array_map('intval', explode(',', $atts['category_id']));
        
        // Debug: Check if categories exist and get their names
        $category_terms = array();
        $valid_category_ids = array();
        
        foreach ($category_ids as $cat_id) {
            $term = get_term($cat_id, $taxonomy);
            if ($term && !is_wp_error($term)) {
                $category_terms[] = $term->name . ' (ID: ' . $cat_id . ')';
                $valid_category_ids[] = $cat_id;
            } else {
                $debug_info[] = 'Category ID ' . $cat_id . ' does not exist in taxonomy: ' . $taxonomy;
            }
        }
        
        if (!empty($valid_category_ids)) {
            $tax_queries[] = array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $valid_category_ids,
                'operator' => 'IN'
            );
            
            // Debug: Check if categories have any posts
            foreach ($valid_category_ids as $cat_id) {
                $term = get_term($cat_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $debug_info[] = 'Category "' . $term->name . '" (ID: ' . $cat_id . ') has ' . $term->count . ' terms';
                }
            }
        }
        
        $debug_info[] = 'Querying ' . $taxonomy . ' categories: ' . implode(', ', $category_terms);
    } elseif (!empty($atts['category'])) {
        $categories = array_map('trim', explode(',', $atts['category']));
        $tax_queries[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'name',
            'terms'    => $categories,
            'operator' => 'IN'
        );
        $debug_info[] = 'Querying ' . $taxonomy . ' category names: ' . implode(', ', $categories);
    }

    // Add taxonomy queries if any exist
    if (!empty($tax_queries)) {
        if (count($tax_queries) > 1) {
            $tax_queries['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_queries;
    }

    if ($atts['debug'] === 'true') {
        echo '<!-- Debug: WP_Query Args -->';
        echo '<pre>' . print_r($args, true) . '</pre>';
        
        // Output debug info
        if (!empty($debug_info)) {
            echo '<!-- Debug: Category Information -->';
            echo '<pre>' . implode("\n", $debug_info) . '</pre>';
        }
        
        // Check if there are any topics at all in the system
        $all_topics = new WP_Query(array(
            'post_type' => 'sfwd-topic',
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        $debug_info[] = 'Total topics in system: ' . $all_topics->found_posts;
        
        // Check if there are any topics in the categories we're querying
        if (!empty($valid_category_ids)) {
            $cat_topics = new WP_Query(array(
                'post_type' => 'sfwd-topic',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $valid_category_ids
                    )
                )
            ));
            $debug_info[] = 'Topics in specified ' . $taxonomy . ' categories: ' . $cat_topics->found_posts;
        }
        
        // Output all debug info
        echo '<pre>' . implode("\n", $debug_info) . '</pre>';
    }

    
    // Get topics
    $topics = new WP_Query($args);
    
    if ($atts['debug'] === 'true') {
        echo '<!-- Debug: Found ' . $topics->found_posts . ' topics -->';
        if ($topics->have_posts()) {
            echo '<!-- Debug: First topic ID: ' . $topics->posts[0]->ID . ' -->';
        }
    }

    if ($topics->have_posts()) {
        $wrapper_classes = array('llm-early-topics');
        $wrapper_classes[] = 'llm-columns-' . $atts['columns'];
        if ($atts['columns_tablet']) $wrapper_classes[] = 'llm-columns-tablet-' . $atts['columns_tablet'];
        if ($atts['columns_mobile']) $wrapper_classes[] = 'llm-columns-mobile-' . $atts['columns_mobile'];
        
        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';
        
        while ($topics->have_posts()) {
            $topics->the_post();
            $topic_id = get_the_ID();
            $lesson_id = learndash_get_lesson_id($topic_id);
            
            // Skip if we shouldn't show the lesson and this is a lesson
            if ($atts['show_lesson'] === 'false' && $lesson_id) {
                continue;
            }
            
            // Check ACF field if specified
            if (!empty($atts['acf_field']) && function_exists('get_field')) {
                $acf_value = get_field($atts['acf_field'], $topic_id);
                if ($atts['debug'] === 'true') {
                    echo '<!-- Debug: ACF Field Check for topic ' . $topic_id . ' -->';
                    echo '<pre>Field: ' . $atts['acf_field'] . ' Value: ' . print_r($acf_value, true) . '</pre>';
                }
                if (empty($acf_value) && $atts['acf_field'] !== 'field_name') {
                    continue;
                }
            }
            
            // Display the topic
            echo '<div class="llm-topic-item">';
            echo '<h4><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>';
            
            // Show lesson title if enabled and available
            if ($atts['show_lesson'] !== 'false' && $lesson_id) {
                echo '<div class="llm-lesson-title">' . get_the_title($lesson_id) . '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Reset post data
        wp_reset_postdata();
    } else {
        echo '<p>No topics found.</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('llm_early_topics', 'llm_early_topics_shortcode');

// Add some basic styles
function llm_early_topics_styles() {
    ?>
    <style>
        .llm-early-topics {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 20px 0;
            background: #66f2c2;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Default 1 column */
        .llm-early-topics {
            grid-template-columns: repeat(1, 1fr);
        }
        
        /* 2 columns */
        .llm-columns-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        /* 3 columns */
        .llm-columns-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        /* 4 columns */
        .llm-columns-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        /* Tablet responsive */
        @media (max-width: 1024px) {
            .llm-columns-tablet-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .llm-columns-tablet-3 {
                grid-template-columns: repeat(3, 1fr) !important;
            }
            .llm-columns-tablet-1 {
                grid-template-columns: repeat(1, 1fr) !important;
            }
        }
        
        /* Mobile responsive */
        @media (max-width: 767px) {
            .llm-columns-mobile-1 {
                grid-template-columns: repeat(1, 1fr) !important;
            }
            .llm-columns-mobile-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            /* Default mobile: stack to 1 column if not specified */
            .llm-early-topics:not(.llm-columns-mobile-1):not(.llm-columns-mobile-2) {
                grid-template-columns: 1fr !important;
            }
        }
        
        .llm-topic-item {
            width: 100%;
            background: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 12px 14px;
            box-sizing: border-box;
            text-align: center;
            color: #111111;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .llm-topic-item:hover {
            transform: translateY(-1px);
        }
        
        .llm-topic-item h4 {
            margin: 0;
            text-align: center;
            font-size: 1.1em;
        }

        .llm-topic-item h4 a {
            display: block;
            width: 100%;
            color: #111111;
            text-decoration: none;
            font-weight: bold;
            transition: none;
        }

        .llm-topic-item h4 a:hover {
            color: #111111;
            text-decoration: none;
        }
        
        .llm-lesson-title {
            font-size: 0.85em;
            color: #666666;
            font-style: italic;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
            padding: 0 4px;
        }
    </style>
    <?php
}
add_action('wp_footer', 'llm_early_topics_styles');
