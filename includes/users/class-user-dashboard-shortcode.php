<?php
/**
 * User Dashboard Shortcode
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class User_Dashboard_Shortcode {
    /**
     * Default shortcode attributes
     *
     * @var array
     */
    private $defaults = array(
        'is_course' => '4236', // Course ID to apply specific settings
        'vehicle_type' => 'default', // Options: default, private, truck, motorcycle
        'show_practice' => 'true',
        'show_real_test' => 'true',
        'show_teacher_quizzes' => 'false',
        'show_study_materials' => 'true',
        'show_topic_tests' => 'true',
        'show_stats' => 'false',
        'practice_url' => '#',
        'real_test_url' => '/quizzes/מבחן-אמת-כמו-בתאוריה/',
        'study_materials_url' => '/חומרי-לימוד-לפי-נושאים/',
        'topic_tests_url' => '/מבחנים-לפי-נושאים-3/',
        'account_url' => '#',
        'stats_url' => '#',
        'welcome_text' => 'שלום, %s!', // %s will be replaced with user's name
        'track_name' => 'חינוך תעבורתי',
        'show_logout' => 'true',
        'teacher_quiz_limit' => '5'
    );

    /**
     * Vehicle type labels
     *
     * @var array
     */
    private $vehicle_types = array(
        'default' => 'שינוי נושא לימוד',
        'private' => 'רכב פרטי',
        'truck' => 'משאית',
        'motorcycle' => 'אפנוע או קורקינט'
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('user_dashboard', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        global $post;
        
        // Check if we're on a page with the shortcode or on a single course page
        $should_enqueue = false;
        
        if (is_singular() && $post) {
            $should_enqueue = has_shortcode($post->post_content, 'user_dashboard') || 
                            (function_exists('sfwd_lms_has_access') && 'sfwd-courses' === $post->post_type);
        }
        
        if ($should_enqueue) {
            wp_enqueue_style(
                'user-dashboard-style',
                get_stylesheet_directory_uri() . '/assets/css/user-dashboard.css',
                array(),
                filemtime(get_stylesheet_directory() . '/assets/css/user-dashboard.css')
            );
        }
    }

    /**
     * Get current user's full name
     */
    private function get_user_full_name() {
        $current_user = wp_get_current_user();
        $name = trim($current_user->first_name . ' ' . $current_user->last_name);
        return !empty($name) ? $name : $current_user->display_name;
    }

    /**
     * Get vehicle type text
     *
     * @param string $type Vehicle type key
     * @return string Vehicle type label
     */
    private function get_vehicle_type_text($type) {
        return isset($this->vehicle_types[$type]) ? $this->vehicle_types[$type] : $this->vehicle_types['default'];
    }

    /**
     * Get current date in format dd/mm/yyyy
     */
    private function get_current_date() {
        return date('d/m/Y');
    }

    /**
     * Get the teacher ID assigned to current student
     *
     * @return int|false Teacher ID or false if not found
     */
    private function get_student_teacher_id() {
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user_id = get_current_user_id();
        global $wpdb;
        
        // Try to get teacher from school_teacher_students table
        $teacher_id = $wpdb->get_var($wpdb->prepare(
            "SELECT teacher_id 
             FROM {$wpdb->prefix}school_teacher_students 
             WHERE student_id = %d 
             LIMIT 1",
            $current_user_id
        ));

        // If no direct teacher-student relationship, try to get from class
        if (!$teacher_id) {
            $teacher_id = $wpdb->get_var($wpdb->prepare(
                "SELECT sc.teacher_id 
                 FROM {$wpdb->prefix}school_classes sc
                 JOIN {$wpdb->prefix}school_students ss ON sc.id = ss.class_id
                 WHERE ss.wp_user_id = %d
                 LIMIT 1",
                $current_user_id
            ));
        }

        return $teacher_id ? (int)$teacher_id : false;
    }

    /**
     * Check if user should see teacher quiz link
     * Only for course 898, תעבורתי courses, or school group students
     *
     * @return bool
     */
    private function should_show_teacher_quiz() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        
        // Check if user is enrolled in course 898
        if (sfwd_lms_has_access(898, $user_id)) {
            return true;
        }
        
        // Check if user is enrolled in any course with "תעבורתי" in the title
        $user_courses = learndash_user_get_enrolled_courses($user_id);
        foreach ($user_courses as $course_id) {
            $course = get_post($course_id);
            if ($course && strpos($course->post_title, 'תעבורתי') !== false) {
                return true;
            }
        }
        
        // Check if user is part of a school class (has teacher assignment)
        if ($this->get_student_teacher_id()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get user's primary course info for dynamic display
     *
     * @return array Course info with title, URL, and ID
     */
    private function get_user_primary_course_info() {
        if (!is_user_logged_in()) {
            return array(
                'id' => 0,
                'title' => 'חינוך תעבורתי',
                'url' => '#'
            ); // Default fallback
        }

        $user_id = get_current_user_id();
        
        // Get user's enrolled courses
        $user_courses = learndash_user_get_enrolled_courses($user_id);
        
        if (empty($user_courses)) {
            return array(
                'id' => 0,
                'title' => 'חינוך תעבורתי',
                'url' => '#'
            ); // Default fallback
        }
        
        // Priority order: תעבורתי courses first, then course 898, then first active course
        $priority_course = null;
        $course_898 = null;
        $first_active = null;
        
        foreach ($user_courses as $course_id) {
            $course = get_post($course_id);
            if (!$course) continue;
            
            // Check if user has active access to this course
            if (!sfwd_lms_has_access($course_id, $user_id)) {
                continue;
            }
            
            $course_info = array(
                'id' => $course_id,
                'title' => $course->post_title,
                'url' => get_permalink($course_id)
            );
            
            // Priority 1: Courses with "תעבורתי" in title
            if (strpos($course->post_title, 'תעבורתי') !== false) {
                $priority_course = $course_info;
                break;
            }
            
            // Priority 2: Course 898
            if ($course_id == 898) {
                $course_898 = $course_info;
            }
            
            // Priority 3: First active course
            if (!$first_active) {
                $first_active = $course_info;
            }
        }
        
        // Return in priority order
        if ($priority_course) {
            return $priority_course;
        } elseif ($course_898) {
            return $course_898;
        } elseif ($first_active) {
            return $first_active;
        }
        
        return array(
            'id' => 0,
            'title' => 'חינוך תעבורתי',
            'url' => '#'
        ); // Final fallback
    }

    /**
     * Apply course-specific defaults based on is_course parameter
     *
     * @param array $atts Shortcode attributes
     * @return array Modified attributes with course-specific defaults
     */
    private function apply_course_defaults($atts) {
        // If is_course is specified, apply course-specific defaults
        if (!empty($atts['is_course'])) {
            $course_id = intval($atts['is_course']);
            
            // Course 4236 defaults
            if ($course_id === 4236) {
                // Only apply defaults if values weren't explicitly set in shortcode
                if ($atts['track_name'] === $this->defaults['track_name']) {
                    $atts['track_name'] = 'מקוון';
                }
                if ($atts['study_materials_url'] === $this->defaults['study_materials_url']) {
                    $atts['study_materials_url'] = 'חומרי-לימוד-לפי-נושאים/';
                }
                if ($atts['topic_tests_url'] === $this->defaults['topic_tests_url']) {
                    $atts['topic_tests_url'] = 'מבחנים-לפי-נושאים-3/';
                }
                if ($atts['real_test_url'] === $this->defaults['real_test_url']) {
                    $atts['real_test_url'] = '/quizzes/מבחן-אמת-כמו-בתאוריה/';
                }
                if ($atts['show_stats'] === $this->defaults['show_stats']) {
                    $atts['show_stats'] = 'false';
                }
            }
            
            // Add more courses here as needed
            // elseif ($course_id === 898) {
            //     // Course 898 defaults
            // }
        }
        
        return $atts;
    }

    /**
     * Get user's primary course name for backward compatibility
     *
     * @return string
     */
    private function get_user_primary_course_name() {
        $course_info = $this->get_user_primary_course_info();
        return $course_info['title'];
    }

    /**
     * Apply course-specific configurations to shortcode attributes
     *
     * @param array $atts Shortcode attributes
     * @param int $course_id Course ID to get configuration for
     * @return array Modified attributes with course-specific values
     */
    private function apply_course_config($atts, $course_id) {
        // Check if we have a specific configuration for this course
        if (isset($this->course_configs[$course_id])) {
            $course_config = $this->course_configs[$course_id];
            
            // Apply course-specific parameters, but only if they weren't explicitly set in shortcode
            foreach ($course_config as $key => $value) {
                // Only override if the current value is the default value
                if (isset($this->defaults[$key]) && $atts[$key] === $this->defaults[$key]) {
                    $atts[$key] = $value;
                }
            }
        }
        
        return $atts;
    }

    /**
     * Get quizzes created by a specific teacher
     *
     * @param int $teacher_id Teacher user ID
     * @param int $limit Number of quizzes to retrieve
     * @return array Quiz information
     */
    private function get_teacher_quizzes($teacher_id, $limit = 5) {
        $args = array(
            'post_type'      => 'sfwd-quiz',
            'posts_per_page' => intval($limit),
            'author'         => $teacher_id,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $quizzes = get_posts($args);
        
        // Add quiz metadata
        foreach ($quizzes as &$quiz) {
            $quiz->quiz_url = get_permalink($quiz->ID);
            $quiz->quiz_date = get_the_date('d/m/Y', $quiz->ID);
        }
        
        return $quizzes;
    }

    /**
     * Get teacher's name
     *
     * @param int $teacher_id Teacher user ID
     * @return string Teacher's display name
     */
    private function get_teacher_name($teacher_id) {
        $teacher = get_userdata($teacher_id);
        return $teacher ? $teacher->display_name : '';
    }

    /**
     * Get user's course access status using user meta (following documentation standards)
     *
     * @return array Course access information
     */
    /**
     * Check if user has access to a specific course
     *
     * @param int $course_id Course ID to check
     * @return bool Whether the user has access
     */
    private function user_has_course_access($course_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        return sfwd_lms_has_access($course_id, $user_id);
    }

    /**
     * Get user's course access status using user meta (following documentation standards)
     *
     * @return array Course access information
     */
    private function get_user_course_access() {
        if (!is_user_logged_in()) {
            return array(
                'has_active' => false,
                'has_expired' => false,
                'active_courses' => array(),
                'expired_courses' => array(),
                'expiring_soon' => array()
            );
        }

        $user_id = get_current_user_id();
        $current_time = current_time('timestamp');
        
        // Get all user meta keys that match course expiry pattern
        $all_meta = get_user_meta($user_id);
        $access_info = array(
            'has_active' => false,
            'has_expired' => false,
            'active_courses' => array(),
            'expired_courses' => array(),
            'expiring_soon' => array()
        );

        foreach ($all_meta as $meta_key => $meta_values) {
            // Look for course expiry meta keys: course_{courseId}_access_expires
            if (preg_match('/^course_(\d+)_access_expires$/', $meta_key, $matches)) {
                $course_id = intval($matches[1]);
                $expires_timestamp = intval($meta_values[0]);
                
                // Skip if no expiry set (0 = permanent access)
                if ($expires_timestamp <= 0) {
                    // For permanent access, add to active courses
                    $course = get_post($course_id);
                    if ($course && sfwd_lms_has_access($course_id, $user_id)) {
                        $course_info = array(
                            'id' => $course_id,
                            'title' => $course->post_title,
                            'url' => get_permalink($course_id),
                            'expires' => 0,
                            'expires_formatted' => 'גישה קבועה',
                            'product_id' => 0,
                            'days_remaining' => 999999
                        );
                        $access_info['has_active'] = true;
                        $access_info['active_courses'][] = $course_info;
                    }
                    continue;
                }
                
                $course = get_post($course_id);
                if (!$course) continue;
                
                $course_info = array(
                    'id' => $course_id,
                    'title' => $course->post_title,
                    'url' => get_permalink($course_id),
                    'expires' => $expires_timestamp,
                    'expires_formatted' => date_i18n('d/m/Y', $expires_timestamp),
                    'product_id' => 0,
                    'days_remaining' => max(0, ceil(($expires_timestamp - $current_time) / DAY_IN_SECONDS))
                );

                // Try to find associated product ID for renewal
                $order_id_key = "course_{$course_id}_order_id";
                if (isset($all_meta[$order_id_key])) {
                    $order_id = intval($all_meta[$order_id_key][0]);
                    if (function_exists('wc_get_order')) {
                        $order = wc_get_order($order_id);
                        if ($order) {
                            foreach ($order->get_items() as $item) {
                                $product_id = $item->get_product_id();
                                $product_courses = get_post_meta($product_id, '_learndash_courses', true);
                                if (is_array($product_courses) && in_array($course_id, $product_courses)) {
                                    $course_info['product_id'] = $product_id;
                                    break;
                                }
                            }
                        }
                    }
                }

                // Categorize based on expiration status
                if ($expires_timestamp > $current_time) {
                    $access_info['has_active'] = true;
                    $access_info['active_courses'][] = $course_info;
                    
                    // Check if expiring within 7 days
                    if ($course_info['days_remaining'] <= 7 && $course_info['days_remaining'] > 0) {
                        $access_info['expiring_soon'][] = $course_info;
                    }
                } else {
                    $access_info['has_expired'] = true;
                    $access_info['expired_courses'][] = $course_info;
                }
            }
        }

        return $access_info;
    }

    /**
     * Render course access status section
     *
     * @param array $access_info Course access information
     * @return string HTML output
     */
    private function render_course_access_status($access_info) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        // Debug: Always show the section to check if data is being retrieved
        // if (empty($access_info['active_courses']) && empty($access_info['expired_courses'])) {
        //     return '';
        // }

        // Check if we're currently viewing a course page
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $is_on_course_page = false;
        
        // Get first active course for comparison
        $quick_course = null;
        if (!empty($access_info['active_courses'])) {
            $quick_course = $access_info['active_courses'][0];
        } else {
            // Fallback to enrolled courses
            $user_courses = learndash_user_get_enrolled_courses(get_current_user_id());
            if (!empty($user_courses)) {
                foreach ($user_courses as $course_id) {
                    if (sfwd_lms_has_access($course_id, get_current_user_id())) {
                        $course = get_post($course_id);
                        if ($course) {
                            $quick_course = array(
                                'title' => $course->post_title,
                                'url' => get_permalink($course_id),
                                'id' => $course_id
                            );
                            break;
                        }
                    }
                }
            }
        }
        
        // Check if current page is the course page
        if ($quick_course && isset($quick_course['id'])) {
            global $post;
            $is_on_course_page = ($post && $post->ID == $quick_course['id']);
        }

        ob_start();
        ?>
        <div class="course-access-details">
            <h4>מצב גישה לקורסים</h4>
            
            <!-- Course access details -->
            
            
            <?php if (!empty($access_info['expiring_soon'])) : ?>
                <div class="access-notice expiring-notice">
                    <h4>⚠️ גישה פגה בקרוב</h4>
                    <?php foreach ($access_info['expiring_soon'] as $course) : ?>
                        <div class="course-expiry-item">
                            <strong><?php echo esc_html($course['title']); ?></strong>
                            <span class="expiry-text">פג תוקף בעוד <?php echo $course['days_remaining']; ?> ימים (<?php echo esc_html($course['expires_formatted']); ?>)</span>
                            <?php if ($course['product_id']) : 
                                $product = wc_get_product($course['product_id']);
                                if ($product) : ?>
                                    <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="renew-button">חדש מנוי</a>
                                <?php endif;
                            endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($access_info['expired_courses'])) : ?>
                <div class="access-notice expired-notice">
                    <h4>🚫 גישה פגה</h4>
                    <p>הגישה שלך לקורסים הבאים פגה. חדש את המנוי כדי להמשיך ללמוד:</p>
                    <?php foreach ($access_info['expired_courses'] as $course) : ?>
                        <div class="course-expiry-item expired">
                            <div class="expired-course-header">
                                <strong><?php echo esc_html($course['title']); ?></strong>
                                <span class="expiry-text">פג תוקף ב-<?php echo esc_html($course['expires_formatted']); ?></span>
                            </div>
                            
                            <!-- Inline Purchase Incentive Box -->
                            <div class="purchase-incentive-box">
                                <div class="incentive-content">
                                    <div class="incentive-icon">🎯</div>
                                    <div class="incentive-text">
                                        <h5>חזור ללמוד עכשיו!</h5>
                                        <p>חדש את הגישה שלך והמשך ללמוד מהמקום שבו הפסקת</p>
                                    </div>
                                </div>
                                
                                <?php if ($course['product_id']) : 
                                    $product = wc_get_product($course['product_id']);
                                    if ($product) : ?>
                                        <div class="incentive-actions">
                                            <div class="price-display">
                                                <span class="price"><?php echo $product->get_price_html(); ?></span>
                                            </div>
                                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="incentive-button">
                                                🛒 חדש מנוי עכשיו
                                            </a>
                                        </div>
                                    <?php else : ?>
                                        <div class="incentive-actions">
                                            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="incentive-button">
                                                🛒 עבור לחנות
                                            </a>
                                        </div>
                                    <?php endif;
                                else : ?>
                                    <div class="incentive-actions">
                                        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="incentive-button">
                                            🛒 עבור לחנות
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="incentive-benefits">
                                    <ul>
                                        <li>✅ גישה מיידית לכל החומרים</li>
                                        <li>✅ מבחני תרגול ללא הגבלה</li>
                                        <li>✅ תמיכה טכנית מלאה</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($access_info['active_courses'])) : ?>
                <div class="access-notice active-notice">
                    <h4>✅ גישה פעילה</h4>
                    <?php foreach ($access_info['active_courses'] as $course) : ?>
                        <?php if (!in_array($course, $access_info['expiring_soon'])) : ?>
                            <div class="course-expiry-item active">
                                <strong><?php echo esc_html($course['title']); ?></strong>
                                <span class="expiry-text">תוקף עד <?php echo esc_html($course['expires_formatted']); ?></span>
                                <a href="<?php echo esc_url($course['url']); ?>" class="continue-button">המשך ללמוד</a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <!-- Fallback: Check for enrolled courses without expiration data -->
                <?php 
                $user_courses = learndash_user_get_enrolled_courses(get_current_user_id());
                if (!empty($user_courses)) : ?>
                    <div class="access-notice active-notice">
                        <h4>✅ גישה פעילה</h4>
                        <?php foreach ($user_courses as $course_id) : 
                            if (sfwd_lms_has_access($course_id, get_current_user_id())) :
                                $course = get_post($course_id);
                                if ($course) : ?>
                                    <div class="course-expiry-item active">
                                        <strong><?php echo esc_html($course->post_title); ?></strong>
                                        <span class="expiry-text">גישה פעילה</span>
                                        <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="continue-button">המשך ללמוד</a>
                                    </div>
                                <?php endif;
                            endif;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <style>
        .course-access-details {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .course-access-details h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Render dashboard HTML
     */
    public function render_dashboard($atts) {
        // Only show to logged in users
        if (!is_user_logged_in()) {
            return '<div class="user-dashboard-login-notice">יש להתחבר למערכת כדי לצפות בלוח הבקרה.</div>';
        }

        // Parse attributes with defaults
        $atts = shortcode_atts($this->defaults, $atts, 'user_dashboard');
        
        // Apply course-specific defaults if is_course is specified
        $atts = $this->apply_course_defaults($atts);
        
        // Get dynamic course info
        $primary_course_info = $this->get_user_primary_course_info();
        
        // Get vehicle type text
        $vehicle_text = $this->get_vehicle_type_text($atts['vehicle_type']);
        
        // Always use dynamic course name unless explicitly overridden in shortcode
        if ($atts['track_name'] === $this->defaults['track_name']) {
            $atts['track_name'] = $primary_course_info['title'];
        }
        
        // Prepare welcome text
        $welcome_text = sprintf($atts['welcome_text'], $this->get_user_full_name());

        // Get course access information
        $access_info = $this->get_user_course_access();

        // Check if we're on a single course page
        $is_single_course_page = is_singular('sfwd-courses');
        
        ob_start();
        ?>
        <div class="user-dashboard-container">
            <!-- Course Access Status Section - Full Width at Top -->
            <div class="course-access-status-wrapper">
                <?php echo $this->render_course_access_status($access_info); ?>
            </div>
            
            <!-- Collapsible Dashboard Section -->
            <div class="dashboard-main-section">
                <!-- Collapsible header -->
                <div class="column-header" style="cursor: pointer;">
                    <h3>לוח בקרה</h3>
                    <span class="collapse-indicator" id="dashboard-indicator">▼</span>
                </div>
                
                <!-- Dashboard content (collapsible) -->
                <div class="dashboard-content" id="dashboard-content">
                    <!-- Dashboard Layout -->
                    <div class="dashboard-columns <?php echo $this->user_has_course_access(898) ? 'has-questions-column' : 'no-questions-column'; ?>">
                        <!-- Practice Tests Column -->
                        <?php if ($atts['show_practice'] === 'true' || $atts['show_real_test'] === 'true' || $atts['show_teacher_quizzes'] === 'true') : ?>
                        <div class="dashboard-column test-column">
                            <div class="column-header">
                                <h3>מבחנים כדוגמת מבחן התיאוריה</h3>
                            </div>
                            <div class="button-group">
                                <?php if ($atts['show_practice'] === 'true') : ?>
                                <a href="<?php echo esc_url(home_url('quizzes/מבחן-תרגול-להמחשה/')); ?>" class="dashboard-button practice-button">
                                    <span class="button-text">מבחני תרגול</span>
                                    <span class="button-icon">📝</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($atts['show_real_test'] === 'true') : ?>
                                <a href="<?php echo esc_url(home_url('/courses/פרטי/lessons/פרק-01-תורת-החינוך-התעברותי-פרק-מבוא/quizzes/מבחן-אמת-כמו-בתאוריה/')); ?>" class="dashboard-button real-test-button">
                                    <span class="button-text">מבחני אמת – כמו בתיאוריה</span>
                                    <span class="button-icon">📋</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($atts['show_teacher_quizzes'] === 'true' && $this->should_show_teacher_quiz()) : ?>
                                    <?php 
                                    $teacher_id = $this->get_student_teacher_id();
                                    if ($teacher_id) {
                                        $teacher_quizzes = $this->get_teacher_quizzes($teacher_id, 1); // Get only the latest quiz
                                        if (!empty($teacher_quizzes)) {
                                            $latest_quiz = $teacher_quizzes[0];
                                            $quiz_url = $latest_quiz->quiz_url;
                                        } else {
                                            // Default URL if no quizzes found - you can change this
                                            $quiz_url = home_url('/quizzes/');
                                        }
                                    } else {
                                        // Default URL if no teacher assigned - you can change this
                                        $quiz_url = home_url('/quizzes/');
                                    }
                                    ?>
                                    <a href="<?php echo esc_url($quiz_url); ?>" class="dashboard-button teacher-quiz-button">
                                        <span class="button-text">מבחן מורה</span>
                                        <span class="button-icon">🎓</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Right Column - Questions by Topic -->
                        <?php if ($this->user_has_course_access(898) && ($atts['show_study_materials'] === 'true' || $atts['show_topic_tests'] === 'true')) : ?>
                        <div class="dashboard-column questions-column">
                            <div class="column-header">
                                <h3>שאלות מהמאגר לפי נושאים</h3>
                            </div>
                            <div class="button-group">
                                <?php if ($atts['show_study_materials'] === 'true') : ?>
                                <a href="<?php echo esc_url(home_url($atts['study_materials_url'])); ?>" class="dashboard-button study-materials-button">
                                    <span class="button-text">חומר לימוד לפי נושאים</span>
                                    <span class="button-icon">📚</span>
                                </a>
                                <?php endif; ?>
                                <?php if ($atts['show_topic_tests'] === 'true') : ?>
                                <a href="<?php echo esc_url(home_url($atts['topic_tests_url'])); ?>" class="dashboard-button topic-tests-button">
                                    <span class="button-text">מבחנים לפי נושאים</span>
                                    <span class="button-icon">📝</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div> <!-- End dashboard-columns -->
                </div> <!-- End dashboard-content -->
            </div> <!-- End dashboard-main-section -->

            <!-- Footer (only show when not on LearnDash pages) -->
            <?php if (!is_singular('sfwd-courses') && !is_singular('sfwd-lessons') && !is_singular('sfwd-topic') && !is_singular('sfwd-quiz')) : ?>
            <div class="dashboard-footer">
                <p>בהצלחה בלימוד ובתרגול!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        /* Dashboard Column Layouts */
        .dashboard-columns {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 0 -10px;
        }
        
        /* Default 3-column layout */
        .dashboard-columns.has-questions-column > .dashboard-column {
            flex: 1;
            min-width: 250px;
            padding: 0 10px;
        }
        
        /* 2-column layout when questions column is hidden */
        .dashboard-columns.no-questions-column > .dashboard-column {
            flex: 1 1 calc(50% - 40px);
            min-width: 300px;
            padding: 0 10px;
        }
        
        /* Adjust column widths for 2-column layout */
        .dashboard-columns.has-questions-column > .dashboard-column {
            flex: 1 1 calc(50% - 20px);
        }
        
        .dashboard-columns.no-questions-column > .dashboard-column {
            flex: 1 1 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .dashboard-columns > .dashboard-column {
                flex: 1 1 100% !important;
                max-width: 100%;
            }
            
            .dashboard-columns .user-panel {
                flex: 1 1 100% !important;
            }
        }
        
        /* Purchase Incentive Box Styles */
        .purchase-incentive-box {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
            border: 2px solid #4a90e2;
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.15);
            transition: all 0.3s ease;
        }
        
        .purchase-incentive-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.25);
        }
        
        .incentive-content {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .incentive-icon {
            font-size: 2.5em;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .incentive-text h5 {
            color: #2c5aa0;
            font-size: 1.3em;
            margin: 0 0 8px 0;
            font-weight: bold;
        }
        
        .incentive-text p {
            color: #555;
            margin: 0;
            font-size: 0.95em;
        }
        
        .incentive-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .price-display {
            background: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .price-display .price {
            font-size: 1.2em;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .incentive-button {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white !important;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(74, 144, 226, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .incentive-button:hover {
            background: linear-gradient(135deg, #357abd 0%, #2c5aa0 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.4);
            text-decoration: none;
            color: white !important;
        }
        
        .incentive-benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            padding: 15px;
        }
        
        .incentive-benefits li {
            padding: 5px 0;
            color: #2c5aa0;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .expired-course-header {
            margin-bottom: 10px;
        }
        
        .expired-course-header strong {
            display: block;
            color: #e74c3c;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        
        .expired-course-header .expiry-text {
            color: #888;
            font-size: 0.9em;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .incentive-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .incentive-button {
                text-align: center;
                justify-content: center;
            }
            
            .incentive-content {
                flex-direction: column;
                text-align: center;
            }
        }
        </style>
        
        <script>
        function toggleDashboard() {
            const content = document.getElementById('dashboard-content');
            const indicator = document.getElementById('dashboard-indicator');
            
            if (content.style.display === 'none') {
                // Expand
                content.style.display = 'block';
                indicator.textContent = '▼';
            } else {
                // Collapse
                content.style.display = 'none';
                indicator.textContent = '▶';
            }
        }
        </script>
        
        <style>
        /* AGGRESSIVE Course Access Status - Full Width at Top */
        .user-dashboard-container {
            width: 100% !important;
            max-width: none !important;
            display: block !important;
        }
        
        .course-access-status-wrapper {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 20px 0 !important;
            padding: 0 !important;
            background: #f8f9fa !important;
            border-radius: 8px !important;
            border: 1px solid #dee2e6 !important;
            display: block !important;
            clear: both !important;
            float: none !important;
            box-sizing: border-box !important;
        }
        
        .course-access-status-wrapper .course-access-details {
            width: 100% !important;
            margin: 0 !important;
            padding: 15px !important;
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            display: block !important;
            box-sizing: border-box !important;
        }
        
        .course-access-status-wrapper .course-access-details h4 {
            margin: 0 0 15px 0 !important;
            color: #333 !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        
        .dashboard-main-section {
            width: 100% !important;
            margin-top: 0 !important;
            clear: both !important;
            display: block !important;
        }
        
        .dashboard-main-section .column-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 10px !important;
            background: #f8f9fa !important;
            border-radius: 5px 5px 0 0 !important;
            border: 1px solid #dee2e6 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .dashboard-main-section .column-header:hover {
            background: #e9ecef;
        }
        
        .collapse-indicator {
            font-size: 14px;
            font-weight: bold;
            transition: transform 0.2s ease;
        }
        
        .dashboard-content {
            display: initial !important;
            border: 1px solid #dee2e6 !important;
            border-top: none !important;
            border-radius: 0 0 5px 5px !important;
            padding: 15px !important;
            background: white !important;
            width: 100% !important;
            box-sizing: border-box !important;
            display: block !important;
        }
        
        /* Override Elementor grid display that breaks layout */
        .user-dashboard-container .dashboard-content {
            display: block !important;
        }
        
        .dashboard-columns {
            display: flex;
            gap: 20px;
            margin-top: 0;
            width: 100%;
            flex-wrap: nowrap;
        }
        
        .dashboard-column {
            flex: 1;
            min-width: 0;
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #dee2e6;
            word-wrap: break-word;
            overflow-wrap: break-word;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .dashboard-column .column-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        .dashboard-column .column-header h3 {
            margin: 0;
            color: #333;
            font-size: 16px;
            font-weight: bold;
        }
        
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .dashboard-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .dashboard-button:hover {
            background: #e9ecef;
            color: #333;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .user-panel {
            background: #f8f9fa;
        }
        
        .user-greeting h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        
        .user-meta {
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .meta-icon {
            font-size: 16px;
        }
        
        .meta-text {
            color: #666;
            text-decoration: none;
        }
        
        .course-link:hover {
            color: #6c757d;
        }
        
        .user-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .user-action-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .user-action-link:hover {
            background: #e9ecef;
            color: #333;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .quick-access-buttons {
            margin: 0 0 15px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .quick-course-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s ease;
        }
        
        .quick-course-button:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
        
        /* AGGRESSIVE 3-column layout */
        .dashboard-columns {
            display: flex !important;
            flex-direction: row !important;
            gap: 20px !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            flex-wrap: nowrap !important;
            justify-content: space-between !important;
            align-items: stretch !important;
            box-sizing: border-box !important;
        }
        
        .dashboard-column {
            flex: 1 1 33.333% !important;
            min-width: 280px !important;
            max-width: 33.333% !important;
            background: #ffffff !important;
            border-radius: 8px !important;
            padding: 20px !important;
            border: 1px solid #dee2e6 !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            box-sizing: border-box !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
            margin: 0 !important;
            float: none !important;
            position: relative !important;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-column {
                min-width: 220px !important;
            }
        }
        
        @media (max-width: 900px) {
            .dashboard-columns {
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            .dashboard-column {
                max-width: 100% !important;
                min-width: auto !important;
                flex: 1 1 100% !important;
            }
            
            .course-access-status-wrapper {
                margin-bottom: 15px !important;
            }
        }
        
        /* Override any theme styles that might interfere */
        .user-dashboard-container * {
            box-sizing: border-box !important;
        }
        
        /* Override Elementor LearnDash lesson interference */
        .learndash-wrapper .ld-item-list .ld-item-list-item a.ld-item-name {
            color: inherit !important;
        }
        
        .learndash-wrapper .ld-item-list .ld-item-list-item a.ld-item-name:hover {
            color: #2c3391 !important;
        }
        
        /* Force dashboard layout over any grid systems */
        .user-dashboard-container,
        .user-dashboard-container .dashboard-content,
        .user-dashboard-container .dashboard-columns {
            display: block !important;
        }
        
        .user-dashboard-container .dashboard-columns {
            display: flex !important;
        }
        
        /* Force clear any floats */
        .course-access-status-wrapper:after,
        .dashboard-main-section:after,
        .dashboard-content:after {
            content: "" !important;
            display: table !important;
            clear: both !important;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // AGGRESSIVE LINK FIX - BY ANY MEANS NECESSARY
            var attempts = 0;
            var maxAttempts = 25; // 5 seconds * 5 attempts per second = 25 total
            
            function forceUpdateRealTestLinks() {
                attempts++;
                var updated = false;
                
                // Method 1: Find by text content
                $('a:contains("מבחני אמת")').each(function() {
                    var $link = $(this);
                    var currentHref = $link.attr('href');
                    var targetHref = '/quizzes/מבחן-אמת-כמו-בתאוריה/';
                    
                    if (currentHref !== targetHref) {
                        $link.attr('href', targetHref);
                        updated = true;
                        console.log('Updated link via text content:', currentHref, '->', targetHref);
                    }
                });
                
                // Method 2: Find by partial href match
                $('a[href*="מבחן"]').each(function() {
                    var $link = $(this);
                    var currentHref = $link.attr('href');
                    var targetHref = '/quizzes/מבחן-אמת-כמו-בתאוריה/';
                    
                    if (currentHref !== targetHref && ($link.text().includes('אמת') || $link.text().includes('מבחן'))) {
                        $link.attr('href', targetHref);
                        updated = true;
                        console.log('Updated link via href match:', currentHref, '->', targetHref);
                    }
                });
                
                // Method 3: Find by class or data attributes
                $('.real-test-link, [data-test-type="real"]').each(function() {
                    var $link = $(this);
                    var targetHref = '/quizzes/מבחן-אמת-כמו-בתאוריה/';
                    $link.attr('href', targetHref);
                    updated = true;
                    console.log('Updated link via class/data attribute');
                });
                
                // Method 4: Nuclear option - find any link with quiz-related text
                $('a').each(function() {
                    var $link = $(this);
                    var linkText = $link.text().trim();
                    var currentHref = $link.attr('href');
                    
                    if ((linkText.includes('מבחני אמת') || linkText.includes('מבחן אמת')) && 
                        currentHref !== '/quizzes/מבחן-אמת-כמו-בתאוריה/') {
                        $link.attr('href', '/quizzes/מבחן-אמת-כמו-בתאוריה/');
                        updated = true;
                        console.log('NUCLEAR: Updated link:', linkText, currentHref, '-> /quizzes/מבחן-אמת-כמו-בתאוריה/');
                    }
                });
                
                // Continue trying if we haven't found links yet and haven't exceeded max attempts
                if (!updated && attempts < maxAttempts) {
                    setTimeout(forceUpdateRealTestLinks, 200); // Try every 200ms
                } else if (updated) {
                    console.log('✅ Real test links successfully updated after', attempts, 'attempts');
                } else {
                    console.log('⚠️ Max attempts reached, no real test links found to update');
                }
            }
            
            // Start immediately
            forceUpdateRealTestLinks();
            
            // Also run on DOM changes (for dynamic content)
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var shouldCheck = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            shouldCheck = true;
                        }
                    });
                    if (shouldCheck) {
                        setTimeout(forceUpdateRealTestLinks, 100);
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize the shortcode
new User_Dashboard_Shortcode();