<?php
/**
 * Thank You Page Settings
 * 
 * Admin interface for configuring Thank You page alerts for specific courses
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Lilac_Thank_You_Page_Settings {
    
    private static $instance = null;
    private $option_name = 'lilac_thank_you_courses';
    private $option_settings = 'lilac_thank_you_settings';
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add alert to thank you page
        add_action('woocommerce_thankyou', array($this, 'display_course_alert'), 10, 1);
        
        // Register shortcode
        add_shortcode('lilac_course_alert', array($this, 'course_alert_shortcode'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        // Check if LearnDash is active
        if (defined('LEARNDASH_VERSION')) {
            // Add as submenu to LearnDash
            add_submenu_page(
                'learndash-lms',
                __('Thank You Page Settings', 'hello-child'),
                __('Thank You Page Settings', 'hello-child'),
                'manage_options',
                'lilac-thank-you-settings',
                array($this, 'settings_page')
            );
        } else {
            // Add as submenu to WooCommerce if LearnDash isn't available
            add_submenu_page(
                'woocommerce',
                __('Thank You Page Settings', 'hello-child'),
                __('Thank You Page Settings', 'hello-child'),
                'manage_options',
                'lilac-thank-you-settings',
                array($this, 'settings_page')
            );
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register course selection option
        register_setting(
            'lilac_thank_you_settings',
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_courses')
            )
        );
        
        // Register display settings option
        register_setting(
            'lilac_thank_you_settings',
            $this->option_settings,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
        
        // Course selection section
        add_settings_section(
            'lilac_thank_you_section',
            __('Course Selection', 'hello-child'),
            array($this, 'settings_section_callback'),
            'lilac-thank-you-settings'
        );
        
        add_settings_field(
            'lilac_thank_you_courses',
            __('Select Courses', 'hello-child'),
            array($this, 'courses_field_callback'),
            'lilac-thank-you-settings',
            'lilac_thank_you_section'
        );
        
        // Display settings section
        add_settings_section(
            'lilac_thank_you_display_section',
            __('Display Settings', 'hello-child'),
            array($this, 'display_section_callback'),
            'lilac-thank-you-settings'
        );
        
        add_settings_field(
            'lilac_thank_you_message',
            __('Alert Message', 'hello-child'),
            array($this, 'message_field_callback'),
            'lilac-thank-you-settings',
            'lilac_thank_you_display_section'
        );
        
        add_settings_field(
            'lilac_thank_you_rtl',
            __('Text Direction', 'hello-child'),
            array($this, 'rtl_field_callback'),
            'lilac-thank-you-settings',
            'lilac_thank_you_display_section'
        );
        
        add_settings_field(
            'lilac_thank_you_icon_position',
            __('Icon Position', 'hello-child'),
            array($this, 'icon_position_field_callback'),
            'lilac-thank-you-settings',
            'lilac_thank_you_display_section'
        );
        
        add_settings_field(
            'lilac_thank_you_content',
            __('Custom Content', 'hello-child'),
            array($this, 'content_field_callback'),
            'lilac-thank-you-settings',
            'lilac_thank_you_display_section'
        );
    }
    
    /**
     * Course selection section description
     */
    public function settings_section_callback() {
        echo '<p>' . __('Select which courses should trigger an alert on the WooCommerce Thank You page.', 'hello-child') . '</p>';
    }
    
    /**
     * Display settings section description
     */
    public function display_section_callback() {
        echo '<p>' . __('Customize how the thank you page alert appears.', 'hello-child') . '</p>';
    }
    
    /**
     * Course selection field
     */
    public function courses_field_callback() {
        // Get all LearnDash courses
        $courses = $this->get_all_courses();
        
        // Get saved courses
        $selected_courses = get_option($this->option_name, array());
        
        if (empty($courses)) {
            echo '<p>' . __('No courses found. Please create some courses first.', 'hello-child') . '</p>';
            return;
        }
        
        echo '<div style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd;">';
        
        foreach ($courses as $course) {
            $checked = in_array($course->ID, $selected_courses) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 8px;">';
            echo '<input type="checkbox" name="' . esc_attr($this->option_name) . '[]" value="' . esc_attr($course->ID) . '" ' . $checked . '>';
            echo ' ' . esc_html($course->post_title);
            echo '</label>';
        }
        
        echo '</div>';
    }
    
    /**
     * Get all LearnDash courses
     */
    private function get_all_courses() {
        $args = array(
            'post_type' => 'sfwd-courses',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        return get_posts($args);
    }
    
    /**
     * Message text field
     */
    public function message_field_callback() {
        $settings = get_option($this->option_settings, array());
        $message = isset($settings['message']) ? $settings['message'] : __('Your course is now ready! You can access it from your account dashboard.', 'hello-child');
        
        echo '<textarea name="' . esc_attr($this->option_settings) . '[message]" rows="3" cols="50" style="width: 100%; max-width: 500px;">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Use %s as a placeholder for the course name(s).', 'hello-child') . '</p>';
    }
    
    /**
     * RTL support field
     */
    public function rtl_field_callback() {
        $settings = get_option($this->option_settings, array());
        $rtl = isset($settings['rtl']) ? $settings['rtl'] : false;
        
        echo '<label>';
        echo '<input type="checkbox" name="' . esc_attr($this->option_settings) . '[rtl]" value="1" ' . checked($rtl, true, false) . '>';
        echo ' ' . __('Enable RTL (Right-to-Left) text direction', 'hello-child');
        echo '</label>';
    }
    
    /**
     * Icon position field
     */
    public function icon_position_field_callback() {
        $settings = get_option($this->option_settings, array());
        $position = isset($settings['icon_position']) ? $settings['icon_position'] : 'left';
        
        echo '<select name="' . esc_attr($this->option_settings) . '[icon_position]">';
        echo '<option value="left" ' . selected($position, 'left', false) . '>' . __('Left', 'hello-child') . '</option>';
        echo '<option value="right" ' . selected($position, 'right', false) . '>' . __('Right', 'hello-child') . '</option>';
        echo '<option value="none" ' . selected($position, 'none', false) . '>' . __('No Icon', 'hello-child') . '</option>';
        echo '</select>';
    }
    
    /**
     * Custom content field
     */
    public function content_field_callback() {
        $settings = get_option($this->option_settings, array());
        $custom_content = isset($settings['custom_content']) ? $settings['custom_content'] : '';
        
        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="display: block; margin-bottom: 5px;">' . __('Additional HTML Content:', 'hello-child') . '</label>';
        echo '<textarea name="' . esc_attr($this->option_settings) . '[custom_content]" rows="5" cols="50" style="width: 100%; max-width: 500px;">' . esc_textarea($custom_content) . '</textarea>';
        echo '</div>';
        
        echo '<p class="description">' . __('Add custom HTML content to be displayed in the alert box. You can include links, formatting, etc.', 'hello-child') . '</p>';
        echo '<p class="description">' . __('Available placeholders: %course_name%, %course_id%, %account_url%, %course_url%', 'hello-child') . '</p>';
    }
    
    /**
     * Sanitize courses array
     */
    public function sanitize_courses($input) {
        if (!is_array($input)) {
            return array();
        }
        
        return array_map('absint', $input);
    }
    
    /**
     * Sanitize settings array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['message'])) {
            $sanitized['message'] = wp_kses_post($input['message']);
        }
        
        if (isset($input['rtl'])) {
            $sanitized['rtl'] = (bool) $input['rtl'];
        }
        
        if (isset($input['icon_position'])) {
            $sanitized['icon_position'] = in_array($input['icon_position'], array('left', 'right', 'none')) ? $input['icon_position'] : 'left';
        }
        
        if (isset($input['custom_content'])) {
            $sanitized['custom_content'] = wp_kses_post($input['custom_content']);
        }
        
        return $sanitized;
    }
    
    /**
     * Settings page content
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('lilac_thank_you_settings');
                do_settings_sections('lilac-thank-you-settings');
                submit_button(__('Save Settings', 'hello-child'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display course alert on thank you page
     */
    public function display_course_alert($order_id) {
        if (!$order_id) {
            return;
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Get selected courses that should trigger the alert
        $selected_courses = get_option($this->option_name, array());
        if (empty($selected_courses)) {
            return;
        }
        
        // Check if any purchased product is linked to a selected course
        $show_alert = false;
        $course_names = array();
        $course_ids = array();
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            // Get course ID associated with this product
            $course_id = $this->get_course_id_for_product($product_id);
            
            if ($course_id && in_array($course_id, $selected_courses)) {
                $show_alert = true;
                $course = get_post($course_id);
                if ($course) {
                    $course_names[] = $course->post_title;
                    $course_ids[] = $course_id;
                }
            }
        }
        
        // Display the alert if needed
        if ($show_alert) {
            echo $this->generate_course_alert_html($course_names, $course_ids);
            
            // Add automatic redirect to course after 3 seconds
            if (!empty($course_ids)) {
                $first_course_id = $course_ids[0];
                $course_url = get_permalink($first_course_id);
                if ($course_url) {
                    echo '<script>
                        console.log("Lilac: Redirecting to course in 3 seconds...");
                        setTimeout(function() {
                            console.log("Lilac: Redirecting to course: ' . esc_js($course_url) . '");
                            window.location.href = "' . esc_js($course_url) . '";
                        }, 3000);
                    </script>';
                }
            }
        }
    }
    
    /**
     * Generate course alert HTML
     */
    public function generate_course_alert_html($course_names, $course_ids = array(), $atts = array()) {
        $course_list = implode(', ', $course_names);
        $settings = get_option($this->option_settings, array());
        $output = '';
        
        // Override settings with shortcode attributes if provided
        $message = isset($atts['message']) ? $atts['message'] : (isset($settings['message']) ? $settings['message'] : __('Your course (%s) is now ready! You can access it from your account dashboard.', 'hello-child'));
        $rtl = isset($atts['rtl']) ? filter_var($atts['rtl'], FILTER_VALIDATE_BOOLEAN) : (isset($settings['rtl']) && $settings['rtl']);
        $icon_position = isset($atts['icon_position']) ? $atts['icon_position'] : (isset($settings['icon_position']) ? $settings['icon_position'] : 'left');
        $custom_content = isset($atts['content']) ? $atts['content'] : (isset($settings['custom_content']) ? $settings['custom_content'] : '');
        
        // Replace placeholders in message
        $message = sprintf($message, esc_html($course_list));
        
        // Process custom content placeholders
        if (!empty($custom_content)) {
            $account_url = wc_get_page_permalink('myaccount');
            $custom_content = str_replace('%account_url%', $account_url, $custom_content);
            $custom_content = str_replace('%course_name%', esc_html($course_list), $custom_content);
            
            // Replace course-specific placeholders if available
            if (!empty($course_ids) && count($course_ids) === 1) {
                $course_id = $course_ids[0];
                $course_url = get_permalink($course_id);
                $custom_content = str_replace('%course_id%', $course_id, $custom_content);
                $custom_content = str_replace('%course_url%', $course_url, $custom_content);
            }
        }
        
        // Get RTL direction
        $dir = $rtl ? 'rtl' : 'ltr';
        
        // Build the alert HTML
        $alert_classes = 'woocommerce-message lilac-course-alert';
        if ($rtl) {
            $alert_classes .= ' lilac-rtl';
        }
        
        $output .= '<div class="' . esc_attr($alert_classes) . '" role="alert" style="background-color: #f7f6f7; border-' . ($rtl ? 'right' : 'top') . ': 3px solid #8fae1b; padding: 1em 1.618em; margin-bottom: 2.617924em; color: #515151; direction: ' . esc_attr($dir) . ';">';
        
        // Add icon based on position setting
        if ($icon_position !== 'none') {
            $icon_style = 'display: inline-block; margin-' . ($icon_position === 'left' ? 'right' : 'left') . ': 0.5em;';
            $icon_html = '<span class="course-alert-icon" style="' . esc_attr($icon_style) . '">✓</span>';
            
            if ($icon_position === 'right') {
                $output .= '<p style="margin: 0; text-align: ' . ($rtl ? 'left' : 'right') . ';">' . wp_kses_post($message) . ' ' . $icon_html . '</p>';
            } else {
                $output .= '<p style="margin: 0;">' . $icon_html . ' ' . wp_kses_post($message) . '</p>';
            }
        } else {
            $output .= '<p style="margin: 0;">' . wp_kses_post($message) . '</p>';
        }
        
        // Add custom content if configured
        if (!empty($custom_content)) {
            $output .= '<div class="lilac-custom-content" style="margin-top: 10px;">';
            $output .= wp_kses_post($custom_content);
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Add some custom CSS for RTL support
        if ($rtl) {
            $output .= '<style>
                .lilac-rtl { text-align: right; }
                .lilac-rtl.woocommerce-message { border-right: 3px solid #8fae1b; border-top: none; }
            </style>';
        }
        
        return $output;
    }
    
    /**
     * Course alert shortcode handler
     */
    public function course_alert_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => 0,
            'message' => '',
            'rtl' => '',
            'icon_position' => '',
            'content' => '',
        ), $atts, 'lilac_course_alert');
        
        // If no course ID specified, don't show anything
        if (empty($atts['course_id'])) {
            return '';
        }
        
        // Get course information
        $course_id = absint($atts['course_id']);
        $course = get_post($course_id);
        
        if (!$course) {
            return '';
        }
        
        $course_names = array($course->post_title);
        $course_ids = array($course_id);
        
        // Generate and return the alert HTML
        return $this->generate_course_alert_html($course_names, $course_ids, $atts);
    }
    
    /**
     * Get product to course mappings
     * 
     * @return array Array of product to course mappings
     */
    private function get_product_mappings() {
        $mappings = get_option('lilac_product_course_mappings', []);
        return is_array($mappings) ? $mappings : [];
    }
    
    /**
     * Get course ID associated with a product
     */
    private function get_course_id_for_product($product_id) {
        // Check for custom mapping first
        $mappings = $this->get_product_mappings();
        
        foreach ($mappings as $mapping) {
            if (!empty($mapping['product_id']) && $mapping['product_id'] == $product_id && !empty($mapping['course_id'])) {
                return $mapping['course_id'];
            }
        }
        
        // Check for product meta (some plugins store course ID in product meta)
        $course_id = get_post_meta($product_id, '_related_course', true);
        if ($course_id) {
            return $course_id;
        }
        
        // If using LearnDash WooCommerce integration, check their mapping
        if (function_exists('learndash_get_course_id')) {
            $course_id = get_post_meta($product_id, '_related_course_id', true);
            if ($course_id) {
                return $course_id;
            }
        }
        
        return false;
    }
}

// Initialize the plugin
function lilac_thank_you_page_settings_init() {
    // Make sure WooCommerce is active
    if (!class_exists('WooCommerce')) {
        error_log('Lilac Course Alert: WooCommerce is not active');
        return;
    }
    
    // Initialize the settings
    $instance = Lilac_Thank_You_Page_Settings::get_instance();
    
    // Debug: Check if shortcode exists
    if (!shortcode_exists('lilac_course_alert')) {
        error_log('Lilac Course Alert: Shortcode not registered!');
    } else {
        error_log('Lilac Course Alert: Shortcode is registered');
    }
}

add_action('init', 'lilac_thank_you_page_settings_init', 20);
