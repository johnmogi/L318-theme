Status Update & Implementation Plan
Current Implementation Status
Shortcode Implementation
[lilac_course_alert] is registered and functional
Supports parameters: course_id, message, rtl, icon_position, content
Can be used on any page to display course alerts
Admin Settings
Thank You Page Settings page under LearnDash/WooCommerce menu
Course selection for alerts
Customizable alert messages with placeholders
RTL support and icon positioning
Order Received Page
Injects alerts based on purchased courses
Shows success message with course access information
Time-Limited Course Access Implementation
Let's implement this in phases:

Phase 1: Basic Time-Based Access
Database Structure
Store access expiration per user per course
Track purchase/access dates
Core Functionality
Set expiration on purchase
Check access before course viewing
Handle access expiration
Implementation Steps
First, let's create a new class for handling course access time limits:
php
CopyInsert
// includes/class-lilac-course-access.php
class Lilac_Course_Access {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into WooCommerce purchase
        add_action('woocommerce_payment_complete', array($this, 'set_course_access_on_purchase'), 10, 1);
        
        // Check course access
        add_filter('learndash_lesson_visible', array($this, 'check_course_access'), 10, 3);
    }

    public function set_course_access_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $course_id = $this->get_course_for_product($product_id);
            
            if ($course_id) {
                $access_period = $this->get_access_period($product_id);
                $this->grant_access($user_id, $course_id, $access_period);
            }
        }
    }

    private function get_access_period($product_id) {
        // Get period from product meta or default to 1 month
        return get_post_meta($product_id, '_course_access_period', true) ?: '1 month';
    }

    private function grant_access($user_id, $course_id, $period) {
        $expiry_date = strtotime("+{$period}", current_time('timestamp'));
        update_user_meta($user_id, "_course_expiry_{$course_id}", $expiry_date);
    }

    public function check_course_access($is_visible, $lesson_id, $user_id) {
        $course_id = learndash_get_course_id($lesson_id);
        $expiry = get_user_meta($user_id, "_course_expiry_{$course_id}", true);
        
        if ($expiry && $expiry < current_time('timestamp')) {
            return false; // Access expired
        }
        
        return $is_visible;
    }
}
Add to functions.php:
php
CopyInsert
// Initialize course access
function lilac_init_course_access() {
    require_once get_stylesheet_directory() . '/includes/class-lilac-course-access.php';
    Lilac_Course_Access::get_instance();
}
add_action('init', 'lilac_init_course_access');
Add Access Period Field to Products:
php
CopyInsert
// Add custom field to product data
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_text_input(array(
        'id' => '_course_access_period',
        'label' => __('Course Access Period', 'hello-child'),
        'description' => __('e.g., 1 month, 2 weeks, 1 year', 'hello-child'),
        'desc_tip' => true,
        'placeholder' => '1 month'
    ));
});

// Save custom field
add_action('woocommerce_process_product_meta', function($post_id) {
    $access_period = $_POST['_course_access_period'] ?? '';
    update_post_meta($post_id, '_course_access_period', sanitize_text_field($access_period));
});
Next Steps
Testing:
Create a test product with a short access period (e.g., 1 minute)
Purchase the course
Verify access is revoked after the period
Enhancements:
Add admin interface for managing access
Send expiration notifications
Add user dashboard widget showing expiration dates
UI Improvements:
Show time remaining on course pages
Add renewal options
Grace periods for expired access
Would you like me to implement any of these features next, or would you like to test the basic implementation first