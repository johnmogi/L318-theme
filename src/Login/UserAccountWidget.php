<?php
/**
 * User Account Widget
 *
 * Provides a shortcode to display user account information and links in the header.
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UserAccountWidget {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register shortcode
        add_shortcode('user_account_widget', array($this, 'render_widget'));
    }
    
    /**
     * Render the user account widget
     * 
     * @param array $atts Shortcode attributes
     * @return string Widget HTML
     */
    public function render_widget($atts = array()) {
        $atts = shortcode_atts(array(
            'show_avatar' => 'true',
            'show_membership' => 'true',
            'show_stats' => 'true',
        ), $atts, 'user_account_widget');
        
        ob_start();
        
        if (is_user_logged_in()) {
            $this->render_logged_in_widget($atts);
        } else {
            $this->render_logged_out_widget($atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render widget for logged in users
     * 
     * @param array $atts Shortcode attributes
     */
    private function render_logged_in_widget($atts) {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        
        // Get user membership info
        $role_label = $this->get_user_role_label($roles);
        $membership_expire = get_user_meta($user->ID, 'membership_expiry', true);
        $membership_expire = !empty($membership_expire) ? date_i18n(get_option('date_format'), strtotime($membership_expire)) : '';
        
        // Get course info
        $course_name = '';
        if (in_array('school_student', $roles)) {
            $course_name = 'תלמיד כיתה י\'';
        } elseif (in_array('student_private', $roles)) {
            $course_name = 'לומד עצמאי';
        }
        
        // Get profile picture or default
        $avatar_url = get_avatar_url($user->ID, array('size' => 60, 'default' => 'mystery'));
        
        ?>
        <div class="lilac-user-widget lilac-user-logged-in">
            <div class="lilac-user-wrap">
                <?php if ($atts['show_avatar'] === 'true') : ?>
                <div class="lilac-profile-pic">
                    <a href="<?php echo esc_url(get_permalink(get_option('lilac_account_page', 0))); ?>">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>">
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="lilac-profile-details">
                    <h3>שלום, <?php echo esc_html($user->display_name); ?>!</h3>
                    <?php if (!empty($role_label)) : ?>
                        <div class="lilac-user-role"><?php echo esc_html($role_label); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($atts['show_membership'] === 'true' && !empty($membership_expire)) : ?>
            <div class="lilac-membership-info">
                <h6>מנוייך בתוקף עד ה-<span class="lilac-expire-date"><?php echo esc_html($membership_expire); ?></span></h6>
            </div>
            <?php endif; ?>
            
            <div class="lilac-profile-links">
                <?php if (!empty($course_name)) : ?>
                <div class="lilac-course">הינך במסלול לימוד <span class="bold"><?php echo esc_html($course_name); ?></span></div>
                <?php endif; ?>
                
                <div class="lilac-edit">
                    <a href="<?php echo esc_url(get_permalink(get_option('lilac_account_page', 0))); ?>">
                        ערוך חשבון
                    </a>
                </div>
                
                <?php if ($atts['show_stats'] === 'true' && function_exists('learndash_get_course_progress')) : ?>
                <div class="lilac-stats">
                    <a href="<?php echo esc_url(get_permalink(get_option('lilac_stats_page', 0))); ?>">
                        סטטיסטיקות לימוד
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="lilac-logout">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                        התנתק
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render widget for logged out users
     * 
     * @param array $atts Shortcode attributes
     */
    private function render_logged_out_widget($atts) {
        // Fix for login button - use direct URL to login page
        $login_url = site_url('/login/');
        
        $school_register_url = site_url('/login/?type=school');
        $private_register_url = site_url('/login/?type=private');
        
        ?>
        <div class="lilac-user-widget lilac-user-logged-out">
            <div class="lilac-login-buttons">
                <a href="<?php echo esc_url($login_url); ?>" class="lilac-login-btn">
                    התחברות
                </a>
                
                <div class="lilac-register-links">
                    <a href="<?php echo esc_url($school_register_url); ?>" class="lilac-register-btn">
                        הרשמה לתלמידי י'
                    </a>
                    <a href="<?php echo esc_url($private_register_url); ?>" class="lilac-register-btn">
                        הרשמה ללקוחות פרטיים
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get user role display label
     * 
     * @param array $roles User roles
     * @return string Role label
     */
    private function get_user_role_label($roles) {
        if (in_array('school_teacher', $roles)) {
            return 'מורה';
        } elseif (in_array('school_student', $roles)) {
            return 'תלמיד כיתה י\'';
        } elseif (in_array('student_private', $roles)) {
            return 'לומד עצמאי';
        } else {
            return '';
        }
    }
}

// Initialize the widget
function lilac_user_account_widget() {
    return UserAccountWidget::get_instance();
}
lilac_user_account_widget();
