<?php
/**
 * LearnDash Access Control
 *
 * Automates enrollment for students and assigns group leadership for teachers.
 * Uses LearnDash built-in content protection for courses.
 *
 * @package Hello_Child_Theme
 * @subpackage LearnDash
 */

namespace Lilac\LearnDash;

if (!defined('ABSPATH')) {
    exit;
}

class AccessControl {
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: setup hooks
     */
    private function __construct() {
        // Auto-enroll on login
        add_action('wp_login', array($this, 'auto_enroll_on_login'), 10, 2);
        // Restrict direct course access
        add_action('template_redirect', array($this, 'restrict_course_access'));
    }

    /**
     * Auto-enroll students and assign teachers on login
     */
    public function auto_enroll_on_login($user_login, $user) {
        $roles = (array) $user->roles;
        // Students
        if ((in_array('school_student', $roles) || in_array('student_private', $roles)) && function_exists('ld_update_course_access')) {
            $course_ids = get_option('lilac_default_student_courses', array());
            foreach ($course_ids as $course_id) {
                ld_update_course_access($user->ID, $course_id, true);
            }
        }
        // Teachers
        if (in_array('school_teacher', $roles) && function_exists('learndash_set_group_leader')) {
            $group_ids = get_option('lilac_default_teacher_groups', array());
            foreach ($group_ids as $group_id) {
                learndash_set_group_leader($user->ID, $group_id);
            }
        }
    }

    /**
     * Handle course access for non-enrolled users by showing purchase incentives
     */
    public function restrict_course_access() {
        if (is_singular('sfwd-courses')) {
            global $post;
            $user = wp_get_current_user();
            
            // Check if user has access to this course
            if (function_exists('ld_has_access') && !ld_has_access($user->ID, $post->ID)) {
                // Check if this is an expired course access
                $access_expires = get_user_meta($user->ID, 'course_' . $post->ID . '_access_expires', true);
                $is_expired = !empty($access_expires) && $access_expires < current_time('timestamp');
                
                // Add a body class for no access
                add_filter('body_class', function($classes) use ($is_expired) {
                    $classes[] = 'course-access-expired';
                    if ($is_expired) {
                        $classes[] = 'course-access-really-expired';
                    }
                    return $classes;
                });
                
                // Add the purchase incentive box to the course content
                add_filter('the_content', function($content) use ($post, $is_expired) {
                    if (!is_singular('sfwd-courses') || !in_the_loop() || !is_main_query()) {
                        return $content;
                    }
                    
                    $incentive = $this->get_purchase_incentive_html($post->ID, $is_expired);
                    return $incentive . $content;
                }, 20);
                
                // Clear any redirects that would prevent showing the content
                remove_action('template_redirect', 'learndash_access_redirect', 1);
                
                // Also remove any other LearnDash redirects
                remove_filter('the_content', 'lesson_visible_after', 1, 1);
                remove_filter('the_content', 'lesson_video', 1, 1);
                
                // Disable the default LearnDash content protection
                add_filter('learndash_content_access', '__return_false', 9999);
                
                return; // Don't redirect, we'll show the incentive
            }
        }
    }
    
    /**
     * Get the purchase incentive HTML
     */
    private function get_purchase_incentive_html($course_id, $is_expired = true) {
        $course = get_post($course_id);
        $course_title = get_the_title($course_id);
        $purchase_url = get_permalink(wc_get_page_id('shop')) . '?add-to-cart=' . $course_id;
        $dashboard_url = home_url('/dashboard');
        
        // Get the product ID associated with this course
        $product_id = get_post_meta($course_id, '_related_product', true);
        if (!empty($product_id)) {
            $purchase_url = get_permalink($product_id);
        }
        
        ob_start();
        ?>
        <div class="purchase-incentive-container" style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 30px; margin: 20px 0; text-align: center; direction: rtl; max-width: 800px; margin-left: auto; margin-right: auto;">
            <?php if ($is_expired) : ?>
                <h2 style="color: #2c3e50; margin-bottom: 20px;">❌ גישה לקורס הסתיימה</h2>
                <p style="font-size: 18px; margin-bottom: 25px; color: #495057;">הגישה שלך לקורס "<?php echo esc_html($course_title); ?>" הסתיימה.</p>
            <?php else : ?>
                <h2 style="color: #2c3e50; margin-bottom: 20px;">🔒 אין לך גישה לקורס זה</h2>
                <p style="font-size: 18px; margin-bottom: 25px; color: #495057;">עליך לרכוש גישה כדי לצפות בתוכן הקורס "<?php echo esc_html($course_title); ?>".</p>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
                <a href="<?php echo esc_url($purchase_url); ?>" class="button" style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px; transition: all 0.3s ease;">
                    <?php echo $is_expired ? '🔄 לחדש גישה עכשיו' : '🛒 רכוש גישה עכשיו'; ?>
                </a>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="button" style="background-color: #6c757d; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-size: 16px; transition: all 0.3s ease;">
                    ← חזרה ללוח הבקרה
                </a>
            </div>
            
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; text-align: right;">
                <div class="feature" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
                    <h4 style="color: #2c3e50; margin-top: 0;">🚀 גישה מיידית</h4>
                    <p style="color: #6c757d; margin-bottom: 0;">קבל גישה מיידית לכל חומרי הקורס עם התשלום</p>
                </div>
                <div class="feature" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
                    <h4 style="color: #2c3e50; margin-top: 0;">📚 תרגול בלתי מוגבל</h4>
                    <p style="color: #6c757d; margin-bottom: 0;">תרגל עם מאות שאלות ותרגילים מעודכנים</p>
                </div>
                <div class="feature" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
                    <h4 style="color: #2c3e50; margin-top: 0;">💬 ליווי צמוד</h4>
                    <p style="color: #6c757d; margin-bottom: 0;">צוות התמיכה שלנו זמין לכל שאלה או בעיה</p>
                </div>
            </div>
            
            <?php if ($is_expired) : ?>
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px dashed #dee2e6;">
                    <p style="color: #6c757d; font-size: 14px;">האם אתה מעוניין לחדש את הגישה שלך לקורס זה?</p>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .purchase-incentive-container .button:hover {
                opacity: 0.9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .purchase-incentive-container .feature:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
        </style>
        <?php
        return ob_get_clean();
    }
    }
}

// Initialize AccessControl
function lilac_learndash_accesscontrol() {
    return AccessControl::get_instance();
}
lilac_learndash_accesscontrol();
