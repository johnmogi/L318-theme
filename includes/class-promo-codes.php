<?php
/**
 * Promo Codes functionality for LearnDash
 *
 * @package Hello_Theme_Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Hello_Theme_Child_Promo_Codes {
    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_ld_promo_code', array($this, 'save_meta_boxes'), 10, 2);
        add_shortcode('promo_code_registration', array($this, 'registration_shortcode'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('wp_ajax_validate_promo_code', array($this, 'validate_promo_code_ajax'));
        add_action('wp_ajax_nopriv_validate_promo_code', array($this, 'validate_promo_code_ajax'));
        add_action('template_redirect', array($this, 'process_registration'));
        add_action('admin_init', array($this, 'add_capabilities'));
    }

    /**
     * Register Promo Code post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Promo Codes', 'Post type general name', 'hello-theme-child'),
            'singular_name'         => _x('Promo Code', 'Post type singular name', 'hello-theme-child'),
            'menu_name'             => _x('Promo Codes', 'Admin Menu text', 'hello-theme-child'),
            'name_admin_bar'        => _x('Promo Code', 'Add New on Toolbar', 'hello-theme-child'),
            'add_new'               => __('Add New', 'hello-theme-child'),
            'add_new_item'          => __('Add New Promo Code', 'hello-theme-child'),
            'new_item'              => __('New Promo Code', 'hello-theme-child'),
            'edit_item'             => __('Edit Promo Code', 'hello-theme-child'),
            'view_item'             => __('View Promo Code', 'hello-theme-child'),
            'all_items'             => __('All Promo Codes', 'hello-theme-child'),
            'search_items'          => __('Search Promo Codes', 'hello-theme-child'),
            'not_found'             => __('No promo codes found.', 'hello-theme-child'),
            'not_found_in_trash'    => __('No promo codes found in Trash.', 'hello-theme-child'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'learndash-lms',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'ld_promo_code',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
            'show_in_rest'       => false,
            'capabilities'       => array(
                'edit_post'          => 'edit_ld_promo_code',
                'read_post'          => 'read_ld_promo_code',
                'delete_post'        => 'delete_ld_promo_code',
                'edit_posts'         => 'edit_ld_promo_codes',
                'edit_others_posts'  => 'edit_others_ld_promo_codes',
                'publish_posts'      => 'publish_ld_promo_codes',
                'read_private_posts' => 'read_private_ld_promo_codes',
                'create_posts'       => 'edit_ld_promo_codes',
                'delete_posts'       => 'delete_ld_promo_codes',
                'delete_private_posts'   => 'delete_private_ld_promo_codes',
                'delete_published_posts' => 'delete_published_ld_promo_codes',
                'delete_others_posts'    => 'delete_others_ld_promo_codes',
                'edit_private_posts'     => 'edit_private_ld_promo_codes',
                'edit_published_posts'   => 'edit_published_ld_promo_codes',
            ),
            'map_meta_cap'       => true,
        );


        register_post_type('ld_promo_code', $args);
    }


    /**
     * Plugin activation
     */
    public function activate() {
        // Make sure we have the post type registered
        $this->register_post_type();
        
        // Add capabilities to roles
        $this->add_capabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Add capabilities to roles
     */
    public function add_capabilities() {
        // Get the administrator role
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Add all capabilities to administrator
            $capabilities = array(
                'edit_ld_promo_code',
                'read_ld_promo_code',
                'delete_ld_promo_code',
                'edit_ld_promo_codes',
                'edit_others_ld_promo_codes',
                'publish_ld_promo_codes',
                'read_private_ld_promo_codes',
                'delete_ld_promo_codes',
                'delete_private_ld_promo_codes',
                'delete_published_ld_promo_codes',
                'delete_others_ld_promo_codes',
                'edit_private_ld_promo_codes',
                'edit_published_ld_promo_codes',
            );
            
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Get the school_teacher role if it exists
        $teacher_role = get_role('school_teacher');
        
        if ($teacher_role) {
            // Add limited capabilities to teachers
            $teacher_capabilities = array(
                'edit_ld_promo_codes',
                'publish_ld_promo_codes',
                'read_private_ld_promo_codes',
                'edit_published_ld_promo_codes',
                'delete_published_ld_promo_codes',
            );
            
            foreach ($teacher_capabilities as $cap) {
                $teacher_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'promo_code_details',
            __('Promo Code Details', 'hello-theme-child'),
            array($this, 'render_meta_box'),
            'ld_promo_code',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('save_promo_code_data', 'promo_code_nonce');

        // Get saved values
        $code = get_post_meta($post->ID, '_promo_code', true);
        $usage_limit = get_post_meta($post->ID, '_usage_limit', true);
        $usage_count = get_post_meta($post->ID, '_usage_count', true) ?: 0;
        $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
        $courses = get_post_meta($post->ID, '_courses', true) ?: array();
        $groups = get_post_meta($post->ID, '_groups', true) ?: array();

        // Generate a random code if not set
        if (empty($code)) {
            $code = strtoupper(wp_generate_password(8, false, false));
        }

        ?>
        <div class="promo-code-fields">
            <div class="form-field">
                <label for="promo_code"><?php _e('Promo Code', 'hello-theme-child'); ?></label>
                <input type="text" id="promo_code" name="promo_code" value="<?php echo esc_attr($code); ?>" class="regular-text">
                <p class="description"><?php _e('The code users will enter to register.', 'hello-theme-child'); ?></p>
            </div>

            <div class="form-field">
                <label for="usage_limit"><?php _e('Usage Limit', 'hello-theme-child'); ?></label>
                <input type="number" id="usage_limit" name="usage_limit" value="<?php echo esc_attr($usage_limit); ?>" min="1" class="small-text">
                <span class="usage-count">
                    <?php printf(__('Used %d times', 'hello-theme-child'), $usage_count); ?>
                </span>
                <p class="description"><?php _e('Maximum number of times this code can be used. Leave empty for unlimited uses.', 'hello-theme-child'); ?></p>
            </div>

            <div class="form-field">
                <label for="expiry_date"><?php _e('Expiry Date', 'hello-theme-child'); ?></label>
                <input type="date" id="expiry_date" name="expiry_date" value="<?php echo esc_attr($expiry_date); ?>">
                <p class="description"><?php _e('The date when this code expires. Leave empty for no expiration.', 'hello-theme-child'); ?></p>
            </div>

            <?php if (class_exists('SFWD_LMS')) : ?>
                <div class="form-field">
                    <label><?php _e('Assigned Courses', 'hello-theme-child'); ?></label>
                    <div class="ld-course-selection">
                        <?php
                        $ld_courses = get_posts(array(
                            'post_type' => 'sfwd-courses',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                        ));

                        if (!empty($ld_courses)) :
                            foreach ($ld_courses as $course) :
                                $checked = in_array($course->ID, (array)$courses) ? ' checked' : '';
                                ?>
                                <label class="selectit">
                                    <input type="checkbox" name="courses[]" value="<?php echo $course->ID; ?>"<?php echo $checked; ?>>
                                    <?php echo esc_html($course->post_title); ?>
                                </label><br>
                                <?php
                            endforeach;
                        else :
                            _e('No courses found.', 'hello-theme-child');
                        endif;
                        ?>
                    </div>
                </div>

                <div class="form-field">
                    <label><?php _e('Assigned Groups', 'hello-theme-child'); ?></label>
                    <div class="ld-group-selection">
                        <?php
                        $ld_groups = get_posts(array(
                            'post_type' => 'groups',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                        ));

                        if (!empty($ld_groups)) :
                            foreach ($ld_groups as $group) :
                                $checked = in_array($group->ID, (array)$groups) ? ' checked' : '';
                                ?>
                                <label class="selectit">
                                    <input type="checkbox" name="groups[]" value="<?php echo $group->ID; ?>"<?php echo $checked; ?>>
                                    <?php echo esc_html($group->post_title); ?>
                                </label><br>
                                <?php
                            endforeach;
                        else :
                            _e('No groups found.', 'hello-theme-child');
                        endif;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .promo-code-fields .form-field {
                margin-bottom: 1.5em;
            }
            .promo-code-fields label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .promo-code-fields .description {
                color: #646970;
                font-style: italic;
                margin-top: 4px;
            }
            .promo-code-fields .usage-count {
                margin-left: 10px;
                color: #2271b1;
                font-weight: 600;
            }
            .ld-course-selection,
            .ld-group-selection {
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 10px;
                background: #fff;
            }
            .selectit {
                display: block;
                margin: 5px 0;
            }
        </style>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['promo_code_nonce']) || !wp_verify_nonce($_POST['promo_code_nonce'], 'save_promo_code_data')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save promo code data
        if (isset($_POST['promo_code'])) {
            update_post_meta($post_id, '_promo_code', sanitize_text_field($_POST['promo_code']));
        }

        if (isset($_POST['usage_limit'])) {
            $usage_limit = !empty($_POST['usage_limit']) ? absint($_POST['usage_limit']) : '';
            update_post_meta($post_id, '_usage_limit', $usage_limit);
        }

        if (isset($_POST['expiry_date'])) {
            update_post_meta($post_id, '_expiry_date', sanitize_text_field($_POST['expiry_date']));
        }

        // Save courses and groups
        $courses = isset($_POST['courses']) ? array_map('intval', $_POST['courses']) : array();
        $groups = isset($_POST['groups']) ? array_map('intval', $_POST['groups']) : array();

        update_post_meta($post_id, '_courses', $courses);
        update_post_meta($post_id, '_groups', $groups);
    }

    /**
     * Registration shortcode
     */
    public function registration_shortcode($atts) {
        // Enqueue scripts and styles
        wp_enqueue_style('promo-code-registration');
        wp_enqueue_script('promo-code-registration');

        // Start output buffering
        ob_start();
        ?>
        <div class="promo-code-registration">
            <form id="promo-code-form" method="post" novalidate>
                <?php wp_nonce_field('promo_code_registration', 'promo_code_nonce'); ?>
                
                <div class="form-field promo-code-field">
                    <label for="promo_code"><?php _e('Promo Code', 'hello-theme-child'); ?> <span class="required">*</span></label>
                    <input type="text" id="promo_code" name="promo_code" required>
                    <div class="promo-code-actions">
                        <button type="button" id="validate-promo-code" class="button"><?php _e('Validate', 'hello-theme-child'); ?></button>
                        <span id="promo-code-status"></span>
                    </div>
                </div>

                <div id="registration-fields" style="display: none;">
                    <h3><?php _e('Your Information', 'hello-theme-child'); ?></h3>
                    
                    <div class="form-field">
                        <label for="first_name"><?php _e('First Name', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-field">
                        <label for="last_name"><?php _e('Last Name', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-field">
                        <label for="email"><?php _e('Email Address', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-field">
                        <label for="username"><?php _e('Username', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-field">
                        <label for="password"><?php _e('Password', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-field">
                        <label for="confirm_password"><?php _e('Confirm Password', 'hello-theme-child'); ?> <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Register', 'hello-theme-child'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Validate promo code via AJAX
     */
    public function validate_promo_code_ajax() {
        check_ajax_referer('promo_code_validation', 'security');

        if (!isset($_POST['code']) || empty($_POST['code'])) {
            wp_send_json_error(array('message' => __('Please enter a promo code.', 'hello-theme-child')));
        }

        $code = sanitize_text_field($_POST['code']);
        $promo_code = $this->get_promo_code($code);

        if (is_wp_error($promo_code)) {
            wp_send_json_error(array('message' => $promo_code->get_error_message()));
        }

        // If we got here, the code is valid
        wp_send_json_success(array(
            'message' => __('Promo code is valid! Please fill in your details below.', 'hello-theme-child'),
            'code' => $promo_code
        ));
    }

    /**
     * Get promo code data
     */
    private function get_promo_code($code) {
        if (empty($code)) {
            return new WP_Error('empty_code', __('Please enter a promo code.', 'hello-theme-child'));
        }

        // Find promo code
        $promo_codes = get_posts(array(
            'post_type' => 'ld_promo_code',
            'meta_key' => '_promo_code',
            'meta_value' => $code,
            'posts_per_page' => 1,
        ));

        if (empty($promo_codes)) {
            return new WP_Error('invalid_code', __('Invalid promo code. Please try again.', 'hello-theme-child'));
        }

        $promo_code = $promo_codes[0];
        $promo_code_id = $promo_code->ID;

        // Check if code has expired
        $expiry_date = get_post_meta($promo_code_id, '_expiry_date', true);
        if (!empty($expiry_date) && strtotime($expiry_date) < current_time('timestamp')) {
            return new WP_Error('expired_code', __('This promo code has expired.', 'hello-theme-child'));
        }

        // Check usage limit
        $usage_limit = get_post_meta($promo_code_id, '_usage_limit', true);
        $usage_count = (int) get_post_meta($promo_code_id, '_usage_count', true);

        if (!empty($usage_limit) && $usage_count >= $usage_limit) {
            return new WP_Error('usage_limit_reached', __('This promo code has reached its usage limit.', 'hello-theme-child'));
        }

        return array(
            'id' => $promo_code_id,
            'code' => $code,
            'title' => $promo_code->post_title,
            'usage_limit' => $usage_limit,
            'usage_count' => $usage_count,
            'expiry_date' => $expiry_date,
            'courses' => get_post_meta($promo_code_id, '_courses', true),
            'groups' => get_post_meta($promo_code_id, '_groups', true),
        );
    }

    /**
     * Process registration form
     */
    public function process_registration() {
        if (!isset($_POST['promo_code_nonce']) || !wp_verify_nonce($_POST['promo_code_nonce'], 'promo_code_registration')) {
            return;
        }

        // Validate required fields
        $required_fields = array(
            'promo_code' => __('Promo Code', 'hello-theme-child'),
            'first_name' => __('First Name', 'hello-theme-child'),
            'last_name' => __('Last Name', 'hello-theme-child'),
            'email' => __('Email Address', 'hello-theme-child'),
            'username' => __('Username', 'hello-theme-child'),
            'password' => __('Password', 'hello-theme-child'),
            'confirm_password' => __('Confirm Password', 'hello-theme-child'),
        );

        $errors = array();

        foreach ($required_fields as $field => $name) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('%s is a required field.', 'hello-theme-child'), $name);
            }
        }

        // Check if passwords match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = __('Passwords do not match.', 'hello-theme-child');
        }

        // Check if username exists
        if (username_exists($_POST['username'])) {
            $errors[] = __('Username already exists. Please choose another one.', 'hello-theme-child');
        }

        // Check if email exists
        if (email_exists($_POST['email'])) {
            $errors[] = __('An account with this email already exists. Please log in instead.', 'hello-theme-child');
        }

        // Validate promo code
        $promo_code = $this->get_promo_code($_POST['promo_code']);
        if (is_wp_error($promo_code)) {
            $errors[] = $promo_code->get_error_message();
        }

        // If there are errors, display them
        if (!empty($errors)) {
            wp_die(implode('<br>', $errors), __('Registration Error', 'hello-theme-child'), array('back_link' => true));
        }

        // Create new user
        $user_data = array(
            'user_login' => sanitize_user($_POST['username']),
            'user_email' => sanitize_email($_POST['email']),
            'user_pass' => $_POST['password'],
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'role' => 'subscriber',
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_die($user_id->get_error_message(), __('Registration Error', 'hello-theme-child'), array('back_link' => true));
        }

        // Update promo code usage
        $usage_count = (int) get_post_meta($promo_code['id'], '_usage_count', true);
        update_post_meta($promo_code['id'], '_usage_count', $usage_count + 1);

        // Assign courses and groups if LearnDash is active
        if (class_exists('SFWD_LMS')) {
            // Assign courses
            if (!empty($promo_code['courses'])) {
                foreach ($promo_code['courses'] as $course_id) {
                    ld_update_course_access($user_id, $course_id);
                }
            }

            // Assign groups
            if (!empty($promo_code['groups'])) {
                foreach ($promo_code['groups'] as $group_id) {
                    ld_update_group_access($user_id, $group_id);
                }
            }
        }

        // Log the user in
        $user = get_user_by('id', $user_id);
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $user->user_login, $user);

        // Redirect to my-account or custom URL
        $redirect_url = apply_filters('promo_code_redirect_after_registration', home_url('/my-account/'));
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        global $post_type;

        if ('ld_promo_code' === $post_type) {
            wp_enqueue_style('promo-code-admin', get_stylesheet_directory_uri() . '/assets/css/promo-code-admin.css', array(), '1.0.0');
            wp_enqueue_script('promo-code-admin', get_stylesheet_directory_uri() . '/assets/js/promo-code-admin.js', array('jquery'), '1.0.0', true);
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        if (!is_admin()) {
            // Register styles
            wp_register_style('promo-code-registration', get_stylesheet_directory_uri() . '/assets/css/promo-code-registration.css', array(), '1.0.0');
            
            // Register scripts
            wp_register_script('promo-code-registration', get_stylesheet_directory_uri() . '/assets/js/promo-code-registration.js', array('jquery'), '1.0.0', true);
            
            // Localize script
            wp_localize_script('promo-code-registration', 'promoCodeData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('promo_code_validation'),
                'messages' => array(
                    'validating' => __('Validating...', 'hello-theme-child'),
                    'error' => __('An error occurred. Please try again.', 'hello-theme-child'),
                )
            ));
        }
    }
}

// Initialize the plugin
function hello_theme_child_promo_codes_init() {
    return Hello_Theme_Child_Promo_Codes::instance();
}

// Start the plugin
add_action('plugins_loaded', 'hello_theme_child_promo_codes_init');
