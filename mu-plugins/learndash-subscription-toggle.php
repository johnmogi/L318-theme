<?php
/**
 * LearnDash Subscription Toggle Shortcode - TEMPORARILY DISABLED
 * 
 * Allows students to pause and resume their LearnDash course subscriptions
 * using native LearnDash course access dates
 */

if (!defined('ABSPATH')) {
    exit;
}

// TEMPORARILY DISABLED DUE TO FATAL ERROR
return;

class LearnDash_Subscription_Toggle {
    
    public function __construct() {
        add_shortcode('school_toggle_access', array($this, 'render_shortcode'));
        add_action('wp_ajax_toggle_course_access', array($this, 'handle_toggle_access'));
        add_action('wp_ajax_nopriv_toggle_course_access', array($this, 'handle_toggle_access'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts for AJAX
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }
    
    /**
     * Render the subscription toggle shortcode
     */
    public function render_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="subscription-toggle-error">יש להתחבר כדי לנהל את המנוי.</div>';
        }
        
        $atts = shortcode_atts(array(
            'duration' => '2w',  // Default pause duration
            'show_status' => 'true',
            'filter_promo_students' => 'true',
            'target_course_id' => '898'
        ), $atts);
        
        $current_user_id = get_current_user_id();
        $is_promo_student = $this->is_promo_code_student($current_user_id);
        
        // Get user's courses
        if ($atts['filter_promo_students'] === 'true' && $is_promo_student) {
            $course_ids = array(intval($atts['target_course_id']));
        } else {
            $course_ids = learndash_user_get_enrolled_courses($current_user_id);
        }
        
        if (empty($course_ids)) {
            return '<div class="subscription-toggle-error">לא נמצאו קורסים פעילים.</div>';
        }
        
        return $this->render_toggle_interface($course_ids, $current_user_id, $atts);
    }
    
    /**
     * Check if user is a promo code student
     */
    private function is_promo_code_student($user_id) {
        $promo_access = get_user_meta($user_id, 'school_promo_course_access', true);
        $course_ids = learndash_user_get_enrolled_courses($user_id);
        $only_course_898 = (count($course_ids) == 1 && in_array(898, $course_ids));
        $current_user = wp_get_current_user();
        $is_promo_role = in_array('student_private', $current_user->roles);
        
        return !empty($promo_access) || $only_course_898 || $is_promo_role;
    }
    
    /**
     * Render the toggle interface
     */
    private function render_toggle_interface($course_ids, $user_id, $atts) {
        $subscription_status = $this->get_subscription_status($course_ids, $user_id);
        $nonce = wp_create_nonce('toggle_course_access_nonce');
        
        ob_start();
        ?>
        <style>
        <?php echo $this->get_inline_css(); ?>
        </style>
        
        <div class="subscription-toggle-container">
            <div class="subscription-status-section">
                <h4 class="status-title">סטטוס המנוי</h4>
                
                <?php if ($atts['show_status'] === 'true'): ?>
                <div class="current-status">
                    <div class="status-indicator <?php echo $subscription_status['is_active'] ? 'active' : 'paused'; ?>">
                        <span class="status-icon"><?php echo $subscription_status['is_active'] ? '✅' : '⏸️'; ?></span>
                        <span class="status-text">
                            <?php echo $subscription_status['is_active'] ? 'המנוי פעיל' : 'המנוי מושהה'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$subscription_status['is_active'] && $subscription_status['resume_date']): ?>
                    <div class="resume-info">
                        המנוי יתחדש ב: <?php echo date('d/m/Y H:i', strtotime($subscription_status['resume_date'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($subscription_status['is_active'] && $subscription_status['expires_date']): ?>
                    <div class="expires-info">
                        המנוי פעיל עד: <?php echo date('d/m/Y H:i', strtotime($subscription_status['expires_date'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="subscription-actions">
                <?php if ($subscription_status['is_active']): ?>
                    <button type="button" class="toggle-button pause-button" 
                            data-action="pause" 
                            data-duration="<?php echo esc_attr($atts['duration']); ?>"
                            data-nonce="<?php echo $nonce; ?>">
                        <span class="button-icon">⏸️</span>
                        <span class="button-text">השהה מנוי</span>
                    </button>
                    <div class="pause-info">
                        המנוי יושהה למשך <?php echo $this->format_duration($atts['duration']); ?>
                    </div>
                <?php else: ?>
                    <button type="button" class="toggle-button resume-button" 
                            data-action="resume"
                            data-nonce="<?php echo $nonce; ?>">
                        <span class="button-icon">▶️</span>
                        <span class="button-text">חדש מנוי</span>
                    </button>
                    <div class="resume-info">
                        המנוי יחזור להיות פעיל מיד
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="toggle-message" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.toggle-button').on('click', function() {
                var button = $(this);
                var action = button.data('action');
                var duration = button.data('duration') || '';
                var nonce = button.data('nonce');
                var messageDiv = $('.toggle-message');
                
                button.prop('disabled', true).find('.button-text').text('מעבד...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'toggle_course_access',
                        toggle_action: action,
                        duration: duration,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            messageDiv.removeClass('error').addClass('success')
                                     .html('<p>' + response.data.message + '</p>')
                                     .show();
                            
                            // Reload page after 2 seconds to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            messageDiv.removeClass('success').addClass('error')
                                     .html('<p>' + response.data.message + '</p>')
                                     .show();
                            button.prop('disabled', false);
                            
                            if (action === 'pause') {
                                button.find('.button-text').text('השהה מנוי');
                            } else {
                                button.find('.button-text').text('חדש מנוי');
                            }
                        }
                    },
                    error: function() {
                        messageDiv.removeClass('success').addClass('error')
                                 .html('<p>אירעה שגיאה. אנא נסה שוב.</p>')
                                 .show();
                        button.prop('disabled', false);
                        
                        if (action === 'pause') {
                            button.find('.button-text').text('השהה מנוי');
                        } else {
                            button.find('.button-text').text('חדש מנוי');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get subscription status for user's courses
     */
    private function get_subscription_status($course_ids, $user_id) {
        $current_time = current_time('timestamp');
        $is_active = true;
        $resume_date = null;
        $expires_date = null;
        
        foreach ($course_ids as $course_id) {
            // Check course access dates
            $access_from = ld_course_access_from($course_id, $user_id);
            $access_until = ld_course_access_until($course_id, $user_id);
            
            // If access_from is in the future, subscription is paused
            if ($access_from && $access_from > $current_time) {
                $is_active = false;
                $resume_date = date('Y-m-d H:i:s', $access_from);
            }
            
            // Get expiration date
            if ($access_until) {
                $expires_date = date('Y-m-d H:i:s', $access_until);
            }
        }
        
        return array(
            'is_active' => $is_active,
            'resume_date' => $resume_date,
            'expires_date' => $expires_date
        );
    }
    
    /**
     * Handle AJAX toggle access request
     */
    public function handle_toggle_access() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'toggle_course_access_nonce')) {
            wp_send_json_error(array('message' => 'בדיקת אבטחה נכשלה.'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'יש להתחבר כדי לבצע פעולה זו.'));
        }
        
        $action = sanitize_text_field($_POST['toggle_action']);
        $duration = sanitize_text_field($_POST['duration']);
        $user_id = get_current_user_id();
        
        // Get user's courses
        $is_promo_student = $this->is_promo_code_student($user_id);
        if ($is_promo_student) {
            $course_ids = array(898);
        } else {
            $course_ids = learndash_user_get_enrolled_courses($user_id);
        }
        
        if (empty($course_ids)) {
            wp_send_json_error(array('message' => 'לא נמצאו קורסים פעילים.'));
        }
        
        $result = false;
        $message = '';
        
        if ($action === 'pause') {
            $result = $this->pause_subscription($course_ids, $user_id, $duration);
            $message = $result ? 'המנוי הושהה בהצלחה.' : 'שגיאה בהשהיית המנוי.';
        } elseif ($action === 'resume') {
            $result = $this->resume_subscription($course_ids, $user_id);
            $message = $result ? 'המנוי חודש בהצלחה.' : 'שגיאה בחידוש המנוי.';
        }
        
        if ($result) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => $message));
        }
    }
    
    /**
     * Pause subscription by setting access_from to future date
     */
    private function pause_subscription($course_ids, $user_id, $duration) {
        $pause_until = $this->calculate_pause_end_date($duration);
        
        foreach ($course_ids as $course_id) {
            // Set access_from to the end of pause period
            ld_course_access_from($course_id, $user_id, $pause_until);
            
            // Log the action
            error_log("School Manager: Paused subscription for user {$user_id}, course {$course_id} until " . date('Y-m-d H:i:s', $pause_until));
        }
        
        return true;
    }
    
    /**
     * Resume subscription by removing access_from restriction
     */
    private function resume_subscription($course_ids, $user_id) {
        foreach ($course_ids as $course_id) {
            // Remove access_from restriction (set to current time or earlier)
            ld_course_access_from($course_id, $user_id, current_time('timestamp') - 3600);
            
            // Log the action
            error_log("School Manager: Resumed subscription for user {$user_id}, course {$course_id}");
        }
        
        return true;
    }
    
    /**
     * Calculate pause end date based on duration string
     */
    private function calculate_pause_end_date($duration) {
        $current_time = current_time('timestamp');
        
        // Parse duration (e.g., "2w", "1m", "3d")
        preg_match('/(\d+)([wmd])/', $duration, $matches);
        
        if (empty($matches)) {
            return $current_time + (2 * WEEK_IN_SECONDS); // Default 2 weeks
        }
        
        $amount = intval($matches[1]);
        $unit = $matches[2];
        
        switch ($unit) {
            case 'w': // weeks
                return $current_time + ($amount * WEEK_IN_SECONDS);
            case 'm': // months
                return $current_time + ($amount * 30 * DAY_IN_SECONDS);
            case 'd': // days
                return $current_time + ($amount * DAY_IN_SECONDS);
            default:
                return $current_time + (2 * WEEK_IN_SECONDS);
        }
    }
    
    /**
     * Format duration for display
     */
    private function format_duration($duration) {
        preg_match('/(\d+)([wmd])/', $duration, $matches);
        
        if (empty($matches)) {
            return 'שבועיים';
        }
        
        $amount = intval($matches[1]);
        $unit = $matches[2];
        
        switch ($unit) {
            case 'w':
                return $amount === 1 ? 'שבוע' : $amount . ' שבועות';
            case 'm':
                return $amount === 1 ? 'חודש' : $amount . ' חודשים';
            case 'd':
                return $amount === 1 ? 'יום' : $amount . ' ימים';
            default:
                return 'שבועיים';
        }
    }
    
    /**
     * Get inline CSS for the shortcode
     */
    private function get_inline_css() {
        return '
        .subscription-toggle-container {
            max-width: 500px;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            direction: rtl;
            text-align: right;
        }
        
        .status-title {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .current-status {
            margin-bottom: 20px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .status-indicator.active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-indicator.paused {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-icon {
            margin-left: 8px;
            font-size: 16px;
        }
        
        .status-text {
            font-weight: 600;
        }
        
        .resume-info, .expires-info {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .subscription-actions {
            margin-bottom: 20px;
        }
        
        .toggle-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        
        .pause-button {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .pause-button:hover {
            background: linear-gradient(135deg, #ee5a52, #dc4c64);
        }
        
        .resume-button {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .resume-button:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
        }
        
        .button-icon {
            margin-left: 8px;
            font-size: 16px;
        }
        
        .pause-info, .resume-info {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            font-style: italic;
        }
        
        .toggle-message {
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .toggle-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .toggle-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .subscription-toggle-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        ';
    }
}

// Initialize the shortcode
new LearnDash_Subscription_Toggle();
