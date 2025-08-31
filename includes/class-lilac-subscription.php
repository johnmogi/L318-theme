<?php
/**
 * Lilac Subscription Management
 * 
 * Handles subscription toggles on the order received page
 */

if (!defined('ABSPATH')) {
    exit;
}

class Lilac_Subscription {
    private $access_duration_days = 30; // Default 30-day access

    public function __construct() {
        // Add subscription section to order received page
        add_action('woocommerce_order_details_after_order_table', [$this, 'add_subscription_section'], 10, 1);
        
        // Register shortcode
        add_shortcode('lilac_course_subscription', [$this, 'subscription_shortcode']);
        
        // Handle AJAX subscription updates
        add_action('wp_ajax_update_subscription', [$this, 'update_subscription']);
        add_action('wp_ajax_nopriv_update_subscription', [$this, 'update_subscription']);
        
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add subscription column to orders list
        add_filter('manage_edit-shop_order_columns', [$this, 'add_subscription_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'display_subscription_column'], 10, 2);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Add course meta box for subscription settings
        add_action('add_meta_boxes', [$this, 'add_course_meta_box']);
        add_action('save_post_sfwd-courses', [$this, 'save_course_meta']);
        
        // Set default access duration (30 days)
        $this->access_duration_days = 30;
    }
    
    /**
     * Shortcode to display the subscription section
     */
    public function subscription_shortcode($atts) {
        // Only show on order received page if no order ID is provided
        if (!is_wc_endpoint_url('order-received') && empty($atts['order_id'])) {
            return '';
        }
        
        $order_id = !empty($atts['order_id']) ? intval($atts['order_id']) : false;
        
        if (!$order_id && is_wc_endpoint_url('order-received')) {
            global $wp;
            $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;
        }
        
        if (!$order_id) {
            return '';
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return '';
        }
        
        // Start output buffering
        ob_start();
        
        // Output the subscription section
        $this->add_subscription_section($order);
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }
        
        wp_enqueue_script(
            'lilac-subscription',
            get_stylesheet_directory_uri() . '/js/subscription.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/js/subscription.js'),
            true
        );
        
        wp_localize_script('lilac-subscription', 'lilacSubscription', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac-subscription-nonce'),
            'i18n' => [
                'updating' => __('Updating...', 'lilac'),
                'error' => __('An error occurred. Please try again.', 'lilac')
            ]
        ]);
    }

    public function add_subscription_section($order) {
        if (!is_wc_endpoint_url('order-received')) {
            return;
        }

        $order_id = $order->get_id();
        $courses = $this->get_courses_from_order($order);
        
        if (empty($courses)) {
            return; // No courses in this order
        }
        
        // Get subscription status for each course
        $course_data = [];
        $has_manual_activation_courses = false;
        
        foreach ($courses as $course_id) {
            // Make sure course_id is a valid integer
            $course_id = intval($course_id);
            if ($course_id <= 0) {
                continue;
            }
            
            // Check if this course requires manual activation
            $requires_manual_activation = get_post_meta($course_id, '_lilac_requires_manual_activation', true) === 'yes';
            
            if ($requires_manual_activation) {
                $has_manual_activation_courses = true;
                $course_data[$course_id] = [
                    'title' => get_the_title($course_id),
                    'is_subscribed' => $this->has_active_subscription($order_id, $course_id),
                    'expiry_date' => $this->get_subscription_expiry($order_id, $course_id),
                    'can_toggle' => $this->can_toggle_subscription($course_id),
                    'requires_manual_activation' => true
                ];
            }
        }
        
        // If no courses require manual activation, don't show the subscription box
        if (!$has_manual_activation_courses) {
            return;
        }
        
        ?>
        <div class="lilac-subscription-box">
            <h3><?php _e('Course Access', 'lilac'); ?></h3>
            
            <div class="course-access-list">
                <?php foreach ($course_data as $course_id => $data): ?>
                    <div class="course-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px;">
                            <?php echo esc_html($data['title']); ?>
                        </h4>
                        
                        <?php if ($data['can_toggle']): ?>
                            <div class="subscription-toggle" style="margin: 10px 0;">
                                <label class="switch" style="display: inline-block; vertical-align: middle; margin-right: 10px;">
                                    <input type="checkbox" 
                                           class="course-subscription-toggle"
                                           data-order-id="<?php echo esc_attr($order_id); ?>"
                                           data-course-id="<?php echo esc_attr($course_id); ?>"
                                           <?php echo $data['is_subscribed'] ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="subscription-status" style="font-weight: 600;">
                                    <?php echo $data['is_subscribed'] ? 
                                        __('Access Active', 'lilac') : 
                                        __('Click to Activate Access', 'lilac'); ?>
                                </span>
                                
                                <?php if ($data['is_subscribed'] && $data['expiry_date']): ?>
                                    <div class="subscription-expiry" style="margin-top: 10px; padding: 8px; background: #e6f7ee; border-radius: 4px; display: inline-block;">
                                        <span style="color: #0e6245; font-size: 0.9em;">
                                            <?php 
                                            printf(
                                                __('Expires: %s', 'lilac'), 
                                                '<strong>' . $data['expiry_date'] . '</strong>'
                                            ); 
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="access-info" style="padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                <p style="margin: 0; color: #50575e; font-size: 0.9em;">
                                    <?php _e('Access is automatically granted for this course.', 'lilac'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="subscription-info" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <p style="margin: 0; color: #50575e; font-size: 0.9em;">
                    <?php 
                    printf(
                        __('Activated courses will be accessible for %d days from activation.', 'lilac'),
                        $this->access_duration_days
                    );
                    ?>
                </p>
            </div>
            
            <div id="subscription-message" style="margin-top: 15px; min-height: 20px;"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#lilac-subscription-toggle').on('change', function() {
                const isChecked = $(this).is(':checked');
                const orderId = $(this).data('order-id');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_subscription',
                        order_id: orderId,
                        subscribe: isChecked ? 'yes' : 'no',
                        security: '<?php echo wp_create_nonce('lilac-subscription'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#lilac-subscription-message')
                                .text('<?php _e('Preferences updated!', 'lilac'); ?>')
                                .fadeIn()
                                .delay(2000)
                                .fadeOut();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function update_subscription() {
        check_ajax_referer('lilac-subscription-nonce', 'security');

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
        $subscribed = isset($_POST['subscribed']) && $_POST['subscribed'] === 'true';

        if (!$order_id) {
            wp_send_json_error(['message' => __('Invalid order ID', 'lilac')]);
        }

        // Verify order belongs to current user
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found', 'lilac')]);
        }

        // For logged-out users, we'll use a cookie
        $user_id = get_current_user_id();
        $cookie_key = 'lilac_subscription_' . $order_id . ($course_id ? '_' . $course_id : '');
        
        try {
            if ($subscribed) {
                // Grant course access for specific course or all courses
                $courses = $course_id ? [$course_id] : [];
                $this->grant_course_access($order, $courses);
                
                // Set cookie for guest users
                if (!$user_id) {
                    setcookie($cookie_key, '1', time() + (10 * 365 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
                }
                
                $message = $course_id ? 
                    __('Course access granted!', 'lilac') : 
                    __('Course access granted! You can now access your courses.', 'lilac');
                
                // Get updated expiry date for the response
                $expiry_meta_key = $course_id ? "_lilac_access_expires_{$course_id}" : '_lilac_access_expires';
                $expiry_date = get_post_meta($order_id, $expiry_meta_key, true);
                $formatted_expiry = $expiry_date ? date_i18n(get_option('date_format'), strtotime($expiry_date)) : '';
                
                wp_send_json_success([
                    'message' => $message,
                    'expiry_date' => $formatted_expiry,
                    'is_subscribed' => true
                ]);
                
            } else {
                // Revoke course access for specific course or all courses
                $courses = $course_id ? [$course_id] : [];
                $this->revoke_course_access($order, $courses);
                
                // Remove cookie for guest users
                if (!$user_id) {
                    setcookie($cookie_key, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
                }
                
                $message = $course_id ? 
                    __('Course access has been revoked.', 'lilac') : 
                    __('Course access has been revoked for all courses.', 'lilac');
                
                wp_send_json_success([
                    'message' => $message,
                    'is_subscribed' => false
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('An error occurred while updating your subscription. Please try again.', 'lilac'),
                'debug' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Grant course access for specific courses in an order
     */
    private function grant_course_access($order, $course_ids = []) {
        $order_id = $order->get_id();
        $user_id = $order->get_user_id();
        $courses = empty($course_ids) ? $this->get_courses_from_order($order) : $course_ids;
        
        if (empty($courses)) {
            return false;
        }
        
        $expiry_date = date('Y-m-d H:i:s', strtotime('+' . $this->access_duration_days . ' days'));
        
        // Enroll user in courses if logged in
        if ($user_id && function_exists('ld_update_course_access')) {
            foreach ($courses as $course_id) {
                // Only update if this course requires activation
                if ($this->can_toggle_subscription($course_id)) {
                    $meta_key = "_lilac_subscribed_{$course_id}";
                    $expiry_key = "_lilac_access_expires_{$course_id}";
                    
                    update_post_meta($order_id, $meta_key, 'yes');
                    update_post_meta($order_id, $expiry_key, $expiry_date);
                    
                    // Enroll user in the course
                    ld_update_course_access($user_id, $course_id);
                } else {
                    // For courses that don't require activation, grant access immediately
                    ld_update_course_access($user_id, $course_id);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Revoke course access for specific courses in an order
     */
    private function revoke_course_access($order, $course_ids = []) {
        $order_id = $order->get_id();
        $user_id = $order->get_user_id();
        $courses = empty($course_ids) ? $this->get_courses_from_order($order) : $course_ids;
        
        if (empty($courses)) {
            return false;
        }
        
        // Remove course access if user is logged in
        if ($user_id && function_exists('ld_update_course_access')) {
            foreach ($courses as $course_id) {
                // Only update if this course requires activation
                if ($this->can_toggle_subscription($course_id)) {
                    $meta_key = "_lilac_subscribed_{$course_id}";
                    update_post_meta($order_id, $meta_key, 'no');
                    
                    // Remove course access
                    ld_update_course_access($user_id, $course_id, true);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if a course requires subscription toggle
     */
    private function can_toggle_subscription($course_id) {
        // Check if this course requires manual activation
        $requires_activation = get_post_meta($course_id, '_lilac_requires_activation', true);
        return $requires_activation === 'yes';
    }
    
    /**
     * Check if subscription is active for a course in an order
     */
    private function has_active_subscription($order_id, $course_id = null) {
        $meta_key = $course_id ? "_lilac_subscribed_{$course_id}" : '_lilac_subscribed';
        $is_subscribed = get_post_meta($order_id, $meta_key, true) === 'yes';
        
        if ($is_subscribed) {
            $expiry_meta_key = $course_id ? "_lilac_access_expires_{$course_id}" : '_lilac_access_expires';
            $expiry_date = get_post_meta($order_id, $expiry_meta_key, true);
            
            // Check if subscription has expired
            if ($expiry_date && strtotime($expiry_date) < current_time('timestamp')) {
                update_post_meta($order_id, $meta_key, 'no');
                return false;
            }
        }
        
        return $is_subscribed;
    }
    
    /**
     * Get subscription expiry date for a course in an order
     */
    private function get_subscription_expiry($order_id, $course_id = null) {
        $expiry_meta_key = $course_id ? "_lilac_access_expires_{$course_id}" : '_lilac_access_expires';
        $expiry_date = get_post_meta($order_id, $expiry_meta_key, true);
        return $expiry_date ? date_i18n(get_option('date_format'), strtotime($expiry_date)) : '';
    }
    
    /**
     * Get courses from order
     */
    private function get_courses_from_order($order) {
        $courses = [];
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $linked_course = get_post_meta($product_id, '_related_course', true);
            
            if ($linked_course) {
                $courses[] = $linked_course;
            }
        }
        
        return array_filter($courses);
    }
    
    /**
     * Check course access on page load
     */
    public function check_course_access() {
        if (!is_singular('sfwd-courses') && !is_singular('sfwd-lessons') && !is_singular('sfwd-topic')) {
            return;
        }
        
        $post_id = get_queried_object_id();
        $user_id = get_current_user_id();
        $has_access = true;
        
        // For course pages
        if (get_post_type($post_id) === 'sfwd-courses') {
            $course_id = $post_id;
        } 
        // For lessons and topics, get the parent course
        else {
            $course_id = get_post_meta($post_id, 'course_id', true);
        }
        
        if (!$course_id) return;
        
        // Check if user has active subscription for this course
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => 'completed',
            'meta_query' => [
                [
                    'key' => '_lilac_subscribed',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($orders as $order) {
            $order_courses = $this->get_courses_from_order($order);
            if (in_array($course_id, $order_courses)) {
                // Check if subscription is still active
                $expiry_date = get_post_meta($order->get_id(), '_lilac_access_expires', true);
                if (!$expiry_date || strtotime($expiry_date) > current_time('timestamp')) {
                    return; // User has active access
                }
            }
        }
        
        // If we got here, user doesn't have access
        if (!is_user_logged_in() && isset($_COOKIE['lilac_subscription_' . $order_id])) {
            return; // Guest with valid cookie
        }
        
        // Redirect to account page or show access denied
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        } else {
            wp_redirect(home_url('/my-account/'));
            exit;
        }
    }
    
    public function add_subscription_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'order_status') {
                $new_columns['subscription'] = __('Access', 'lilac');
            }
        }
        
        return $new_columns;
    }
    
    public function display_subscription_column($column, $order_id) {
        if ($column === 'subscription') {
            $order = wc_get_order($order_id);
            if (!$order) return;
            
            $is_subscribed = $this->has_active_subscription($order_id);
            $expiry_date = get_post_meta($order_id, '_lilac_access_expires', true);
            $courses = $this->get_courses_from_order($order);
            
            echo '<span class="subscription-status ' . ($is_subscribed ? 'yes' : 'no') . '">';
            echo $is_subscribed ? '✓ Active' : '✗ Inactive';
            
            if ($is_subscribed && $expiry_date) {
                echo '<br><small>Expires: ' . date_i18n(get_option('date_format'), strtotime($expiry_date)) . '</small>';
            }
            
            if (!empty($courses)) {
                echo '<div class="course-names" style="margin-top:5px;font-size:0.9em;">';
                foreach ($courses as $course_id) {
                    echo '<div>' . get_the_title($course_id) . '</div>';
                }
                echo '</div>';
            }
            
            echo '</span>';
        }
    }
    
    /**
     * Enqueue admin styles
     */
    public function admin_enqueue_scripts($hook) {
        global $post;
        
        // Load on order edit screen
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-shop_order') {
            $css = '
            .column-subscription_status { width: 120px; }
            .subscription-status {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                line-height: 1;
                font-weight: 600;
            }
            .subscription-active {
                background: #e6f7ee;
                color: #0e6245;
            }
            .subscription-inactive {
                background: #f8e8e8;
                color: #8a1f11;
            }';
            
            wp_add_inline_style('woocommerce_admin_styles', $css);
        }
        
        // Load on course edit screen
        if (($hook === 'post.php' || $hook === 'post-new.php') && $screen->post_type === 'sfwd-courses') {
            $css = '
            #lilac_course_subscription .misc-pub-section {
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            #lilac_course_subscription .misc-pub-section:last-child {
                border-bottom: none;
            }
            #lilac_course_subscription label {
                display: flex;
                align-items: center;
                cursor: pointer;
            }
            #lilac_course_subscription input[type="checkbox"] {
                margin-right: 8px;
            }
            #lilac_course_subscription .description {
                display: block;
                margin-top: 8px;
                color: #666;
                font-style: italic;
                font-size: 12px;
                line-height: 1.4;
            }';
            
            wp_add_inline_style('wp-admin', $css);
        }
    }
    
    /**
     * Add meta box to course edit screen
     */
    public function add_course_meta_box() {
        // Remove the default LearnDash meta box to avoid confusion
        remove_meta_box('learndash-course-access-settings', 'sfwd-courses', 'side');
        
        add_meta_box(
            'lilac_course_subscription',
            __('Course Access Settings', 'lilac'),
            [$this, 'render_course_meta_box'],
            'sfwd-courses',
            'side',
            'default'
        );
    }
    
    /**
     * Render course meta box content
     */
    public function render_course_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('lilac_course_meta_save', 'lilac_course_meta_nonce');
        
        // Get current value (default to 'yes' for new courses)
        $requires_activation = get_post_meta($post->ID, '_lilac_requires_activation', true);
        $is_checked = $requires_activation === '' || $requires_activation === 'yes';
        ?>
        <div class="misc-pub-section">
            <label>
                <input type="checkbox" 
                       name="lilac_requires_activation" 
                       value="yes" 
                       <?php checked($is_checked, true); ?>>
                <?php _e('Require manual activation', 'lilac'); ?>
            </label>
            <p class="description">
                <?php _e('If checked, students must manually activate access to this course after purchase.', 'lilac'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save course meta box data
     */
    public function save_course_meta($post_id) {
        // Check if nonce is set and valid
        if (!isset($_POST['lilac_course_meta_nonce']) || 
            !wp_verify_nonce($_POST['lilac_course_meta_nonce'], 'lilac_course_meta_save')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save the checkbox value
        $requires_activation = isset($_POST['lilac_requires_activation']) ? 'yes' : 'no';
        update_post_meta($post_id, '_lilac_requires_activation', $requires_activation);
        
        // Clear any cached data
        wp_cache_delete('course_requires_activation_' . $post_id, 'lilac');
    }
}

// Initialize the subscription functionality
new Lilac_Subscription();
