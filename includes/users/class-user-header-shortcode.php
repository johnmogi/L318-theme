<?php
/**
 * User Header Shortcode Class
 * 
 * Handles the user greeting and course access status display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class User_Header_Shortcode {
    
    public function __construct() {
        add_shortcode('user_header', array($this, 'render_shortcode'));
    }
    
    /**
     * Render the user header shortcode
     */
    public function render_shortcode($atts) {
        // Set default attributes
        $atts = shortcode_atts(array(
            'track_name' => 'מקוון',
            'vehicle_type' => 'רכב פרטי',
            'show_logout' => 'true',
            'account_url' => '/my-account/',
            'is_course' => '',
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>אנא התחבר כדי לראות את המידע.</p>';
        }
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Get primary course info
        $primary_course_info = $this->get_primary_course_info($atts['is_course']);
        
        // Generate welcome text
        $welcome_text = $this->generate_welcome_text($current_user, $atts['vehicle_type']);
        
        // Get course access information
        $course_access = $this->get_user_course_access();
        
        ob_start();
        ?>
        <div class="user-header-container">
            <div class="user-header-layout">
                <!-- Left Section - User Greeting (50%) -->
                <div class="user-greeting-section">
                    <div class="user-greeting">
                        <h2><?php echo esc_html($welcome_text); ?></h2>
                        <div class="user-meta">
                            <div class="meta-item date">
                                <span class="meta-icon">📅</span>
                                <span class="meta-text"><?php echo esc_html($this->get_current_date()); ?></span>
                            </div>
                            <div class="meta-item track">
                                <span class="meta-icon">🎯</span>
                                <a href="<?php echo esc_url($primary_course_info['url']); ?>" class="meta-text course-link"><?php echo esc_html($atts['track_name']); ?></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-actions">
                        <?php if (current_user_can('administrator')) : ?>
                        <a href="<?php echo esc_url($atts['account_url']); ?>" class="user-action-link edit-account">
                            <span class="link-icon">✏️</span>
                            <span class="link-text">ערוך חשבון (<?php echo esc_html($atts['vehicle_type']); ?>)</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($atts['show_logout'] === 'true') : ?>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="user-action-link logout">
                            <span class="link-icon">🚪</span>
                            <span class="link-text">התנתק</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Section - Course Access Status (50%) -->
                <?php if ($course_access['has_active'] || $course_access['has_expired']) : ?>
                <div class="course-access-section">
                    <div class="column-header">
                        <h3>מצב גישה לקורסים</h3>
                    </div>
                    
                    <?php if ($course_access['has_active']) : ?>
                    <div class="access-status active-courses">
                        <h4 class="status-title">גישה פעילה</h4>
                        <div class="courses-list">
                            <?php foreach ($course_access['active_courses'] as $course) : ?>
                            <div class="course-item active">
                                <div class="course-info">
                                    <h5 class="course-title"><?php echo esc_html($course['title']); ?></h5>
                                    <div class="course-meta">
                                        <span class="status-badge active">גישה פעילה</span>
                                        <?php if ($course['progress'] > 0) : ?>
                                        <span class="progress-text"><?php echo esc_html($course['progress']); ?>% הושלם</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <a href="<?php echo esc_url($course['url']); ?>" class="continue-button">המשך ללמוד</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($course_access['has_expired']) : ?>
                    <div class="access-status expired-courses">
                        <h4 class="status-title">גישה שפגה</h4>
                        <div class="courses-list">
                            <?php foreach ($course_access['expired_courses'] as $course) : ?>
                            <div class="course-item expired">
                                <div class="course-info">
                                    <h5 class="course-title"><?php echo esc_html($course['title']); ?></h5>
                                    <div class="course-meta">
                                        <span class="status-badge expired">גישה פגה</span>
                                        <span class="expiry-date">פג ב: <?php echo esc_html($course['expired_date']); ?></span>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <a href="<?php echo esc_url($course['renewal_url']); ?>" class="renew-button">חדש גישה</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        /* User Header Styles */
        .user-header-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            font-family: "Assistant", Sans-serif;
        }
        
        .user-header-layout {
            display: flex;
            gap: 0;
        }
        
        .user-greeting-section {
            flex: 1;
            padding: 20px;
            border-left: 1px solid #e0e0e0;
        }
        
        .course-access-section {
            flex: 1;
            padding: 20px;
        }
        
        .user-greeting h2 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .user-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .meta-icon {
            font-size: 16px;
        }
        
        .course-link {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
        }
        
        .course-link:hover {
            text-decoration: underline;
        }
        
        .user-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-action-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .user-action-link:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .user-action-link.logout {
            background: #fff5f5;
            border-color: #fed7d7;
            color: #c53030;
        }
        
        .user-action-link.logout:hover {
            background: #fed7d7;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .user-header-layout {
                flex-direction: column;
            }
            
            .user-greeting-section {
                border-left: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .course-item {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .course-actions {
                margin-right: 0;
                text-align: center;
            }
            
            .user-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-actions {
                flex-direction: column;
            }
        }
        
        .column-header h3 {
            color: #2c3e50;
            margin: 0 0 20px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .access-status {
            margin-bottom: 25px;
        }
        
        .access-status:last-child {
            margin-bottom: 0;
        }
        
        .status-title {
            color: #4a90e2;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .courses-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .course-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #ffffff;
        }
        
        .course-item.active {
            border-color: #4caf50;
            background: #f8fff8;
        }
        
        .course-item.expired {
            border-color: #f44336;
            background: #fff8f8;
        }
        
        .course-info {
            flex: 1;
        }
        
        .course-title {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .course-meta {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #4caf50;
            color: white;
        }
        
        .status-badge.expired {
            background: #f44336;
            color: white;
        }
        
        .progress-text, .expiry-date {
            font-size: 12px;
            color: #666;
        }
        
        .course-actions {
            margin-right: 15px;
        }
        
        .continue-button, .renew-button {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .continue-button {
            background: #4caf50;
            color: white;
        }
        
        .continue-button:hover {
            background: #45a049;
        }
        
        .renew-button {
            background: #ff9800;
            color: white;
        }
        
        .renew-button:hover {
            background: #f57c00;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .course-item {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            
            .course-actions {
                margin-right: 0;
                text-align: center;
            }
            
            .user-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-actions {
                flex-direction: column;
            }
        }
        </style>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get primary course information
     */
    private function get_primary_course_info($course_id = '') {
        if (empty($course_id)) {
            return array(
                'title' => 'קורס ברירת מחדל',
                'url' => home_url('/courses/')
            );
        }
        
        $course = get_post($course_id);
        if ($course) {
            return array(
                'title' => $course->post_title,
                'url' => get_permalink($course_id)
            );
        }
        
        return array(
            'title' => 'קורס לא נמצא',
            'url' => home_url('/courses/')
        );
    }
    
    /**
     * Generate welcome text based on user and vehicle type
     */
    private function generate_welcome_text($user, $vehicle_type) {
        $first_name = $user->first_name ?: $user->display_name;
        $current_hour = (int) current_time('H');
        
        if ($current_hour < 12) {
            $greeting = 'בוקר טוב';
        } elseif ($current_hour < 18) {
            $greeting = 'צהריים טובים';
        } else {
            $greeting = 'ערב טוב';
        }
        
        return sprintf('%s, %s!', $greeting, $first_name);
    }
    
    /**
     * Get current date in Hebrew format
     */
    private function get_current_date() {
        return date_i18n('d/m/Y');
    }
    
    /**
     * Get user's course access status
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
        
        // Get user's enrolled courses
        $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
        
        $active_courses = array();
        $expired_courses = array();
        $expiring_soon = array();
        
        foreach ($enrolled_courses as $course_id) {
            $course = get_post($course_id);
            if (!$course) continue;
            
            $has_access = sfwd_lms_has_access($course_id, $user_id);
            $progress = learndash_course_progress($user_id, $course_id);
            $progress_percentage = isset($progress['percentage']) ? $progress['percentage'] : 0;
            
            $course_data = array(
                'id' => $course_id,
                'title' => $course->post_title,
                'url' => get_permalink($course_id),
                'progress' => $progress_percentage
            );
            
            if ($has_access) {
                // Check if expiring soon (within 30 days)
                $access_expires = get_user_meta($user_id, 'learndash_course_expired_' . $course_id, true);
                if ($access_expires && $access_expires > time() && $access_expires < (time() + (30 * 24 * 60 * 60))) {
                    $course_data['expires_date'] = date_i18n('d/m/Y', $access_expires);
                    $expiring_soon[] = $course_data;
                }
                $active_courses[] = $course_data;
            } else {
                // Check if access has expired
                $access_expires = get_user_meta($user_id, 'learndash_course_expired_' . $course_id, true);
                if ($access_expires && $access_expires < time()) {
                    $course_data['expired_date'] = date_i18n('d/m/Y', $access_expires);
                    $course_data['renewal_url'] = home_url('/renew-course/?course_id=' . $course_id);
                    $expired_courses[] = $course_data;
                }
            }
        }
        
        return array(
            'has_active' => !empty($active_courses),
            'has_expired' => !empty($expired_courses),
            'active_courses' => $active_courses,
            'expired_courses' => $expired_courses,
            'expiring_soon' => $expiring_soon
        );
    }
}

// Initialize the shortcode
new User_Header_Shortcode();
