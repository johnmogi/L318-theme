<?php
/**
 * Course Access Settings
 * 
 * Admin interface for managing WooCommerce course access
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Lilac_Course_Access_Settings {
    
    private static $instance = null;
    private $settings_page = '';
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add debug log
        error_log('Lilac Course Access Settings: Initializing...');
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_link'), 999);
        
        // Debug admin menu
        add_action('admin_menu', function() {
            error_log('Lilac Course Access Settings: Admin menu hook fired');
            
            // Debug: List all admin menu items
            global $menu, $submenu;
            error_log('Admin Menu Items: ' . print_r($menu, true));
            error_log('WooCommerce Submenu Items: ' . print_r(isset($submenu['woocommerce']) ? $submenu['woocommerce'] : 'No WooCommerce menu found', true));
            
            // Debug current user
            $current_user = wp_get_current_user();
            error_log('Current User: ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')');
            error_log('User Capabilities: ' . print_r($current_user->allcaps, true));
            
        }, 999);
    }
    
    /**
     * Add direct link to admin bar
     */
    public function add_admin_bar_link($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $args = array(
            'id'    => 'lilac_course_access',
            'title' => 'Course Access',
            'href'  => admin_url('admin.php?page=lilac-course-access'),
            'meta'  => array(
                'class' => 'lilac-course-access',
                'title' => 'Course Access Settings'
            )
        );
        $wp_admin_bar->add_node($args);
    }
    
    public function add_admin_menu() {
        error_log('Lilac Course Access Settings: Adding admin menu');
        
        // Add main menu as top-level menu
        $this->settings_page = add_menu_page(
            __('Course Access Settings', 'lilac'),  // Page title
            __('Course Access', 'lilac'),           // Menu title
            'manage_options',                       // Capability
            'lilac-course-access',                  // Menu slug
            array($this, 'settings_page'),          // Callback function
            'dashicons-admin-generic',              // Icon
            30                                      // Position (after Dashboard)
        );
        
        // Add submenu item for main settings
        add_submenu_page(
            'lilac-course-access',
            __('Course Access Settings', 'lilac'),
            __('Settings', 'lilac'),
            'manage_options',
            'lilac-course-access',
            array($this, 'settings_page')
        );
        
        // Add debug page as submenu
        add_submenu_page(
            'lilac-course-access',
            __('Course Access Debug', 'lilac'),
            __('Debug', 'lilac'),
            'manage_options',
            'lilac-course-access-debug',
            array($this, 'debug_page')
        );
        
        // Debug: Check current user capabilities
        error_log('Current User ID: ' . get_current_user_id());
        error_log('Current User Capabilities: ' . print_r(wp_get_current_user()->allcaps, true));
        
        error_log('Lilac Course Access Settings: Menu added - ' . ($this->settings_page ? 'Success' : 'Failed'));
    }
    
    public function debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $debug_log = '';
        $debug_file = WP_CONTENT_DIR . '/debug.log';
        
        // Debug information
        $debug_info = array(
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => phpversion(),
            'Theme' => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
            'Active Plugins' => get_option('active_plugins'),
            'Debug File' => $debug_file,
            'File Exists' => file_exists($debug_file) ? 'Yes' : 'No',
            'File Readable' => is_readable($debug_file) ? 'Yes' : 'No',
            'File Size' => file_exists($debug_file) ? size_format(filesize($debug_file)) : 'N/A',
            'Last Modified' => file_exists($debug_file) ? date('Y-m-d H:i:s', filemtime($debug_file)) : 'N/A'
        );
        
        // Get debug log contents
        if (file_exists($debug_file) && is_readable($debug_file)) {
            $debug_log = file_get_contents($debug_file);
            $debug_log = esc_textarea($debug_log);
        } else {
            // Try to create the debug file if it doesn't exist
            if (!file_exists($debug_file)) {
                file_put_contents($debug_file, 'Debug log created on ' . current_time('mysql') . "\n");
                chmod($debug_file, 0666);
                if (file_exists($debug_file)) {
                    $debug_log = 'Debug log file was created at: ' . $debug_file;
                } else {
                    $debug_log = 'Failed to create debug log file. Please check directory permissions for: ' . WP_CONTENT_DIR;
                }
            } else {
                $debug_log = 'Debug log file exists but is not readable. Please check file permissions for: ' . $debug_file;
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Test message to verify the page is loading -->
            <div class="notice notice-success">
                <p>Debug page is loading correctly. If you can see this message, the page is accessible.</p>
            </div>
            
            <h2>System Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <?php foreach ($debug_info as $key => $value): ?>
                <tr>
                    <th style="width: 200px;"><?php echo esc_html($key); ?></th>
                    <td>
                        <?php 
                        if (is_array($value)) {
                            echo '<pre>' . esc_html(print_r($value, true)) . '</pre>';
                        } else {
                            echo esc_html($value);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <h2>WordPress Debug Log</h2>
            <textarea style="width: 100%; height: 500px; font-family: monospace;" readonly><?php 
                echo $debug_log ?: 'Debug log is empty or not readable.'; 
            ?></textarea>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-course-access-debug&refresh=1')); ?>" class="button">
                    <?php _e('Refresh Log', 'lilac'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    public function settings_init() {
        register_setting('lilac_course_access', 'lilac_course_access_mappings');
        
        add_settings_section(
            'lilac_course_access_section',
            __('Course Access Mappings', 'lilac'),
            array($this, 'settings_section_callback'),
            'lilac-course-access'
        );
        
        add_settings_field(
            'mappings',
            __('Product to Course Mappings', 'lilac'),
            array($this, 'mappings_field_callback'),
            'lilac-course-access',
            'lilac_course_access_section'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>' . __('Map WooCommerce products to LearnDash courses and user roles.', 'lilac') . '</p>';
    }
    
    public function mappings_field_callback() {
        $mappings = get_option('lilac_course_access_mappings', array());
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        $courses = $this->get_ld_courses();
        $roles = array(
            'student_private' => 'Private Student',
            'student_school' => 'School Student',
            'school_teacher' => 'School Teacher'
        );
        
        // Add a new empty mapping if none exist
        if (empty($mappings)) {
            $mappings[] = array('product_id' => '', 'course_id' => '', 'role' => '');
        }
        
        echo '<div id="course-access-mappings">';
        
        foreach ($mappings as $index => $mapping) {
            echo '<div class="mapping-row" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">';
            
            // Product select
            echo '<select name="lilac_course_access_mappings[' . $index . '][product_id]" style="margin-right: 10px;">';
            echo '<option value="">' . __('Select Product', 'lilac') . '</option>';
            foreach ($products as $product) {
                $selected = selected($mapping['product_id'], $product->get_id(), false);
                echo '<option value="' . esc_attr($product->get_id()) . '" ' . $selected . '>' . esc_html($product->get_name()) . '</option>';
            }
            echo '</select>';
            
            // Course select
            echo '<select name="lilac_course_access_mappings[' . $index . '][course_id]" style="margin-right: 10px;">';
            echo '<option value="">' . __('Select Course', 'lilac') . '</option>';
            foreach ($courses as $course_id => $course_title) {
                $selected = selected($mapping['course_id'], $course_id, false);
                echo '<option value="' . esc_attr($course_id) . '" ' . $selected . '>' . esc_html($course_title) . '</option>';
            }
            echo '</select>';
            
            // Role select
            echo '<select name="lilac_course_access_mappings[' . $index . '][role]">';
            echo '<option value="">' . __('Select Role', 'lilac') . '</option>';
            foreach ($roles as $role => $label) {
                $selected = selected($mapping['role'], $role, false);
                echo '<option value="' . esc_attr($role) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            
            // Remove button
            echo '<button type="button" class="button remove-mapping" style="margin-left: 10px; color: #a00;">' . __('Remove', 'lilac') . '</button>';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add new mapping button
        echo '<button type="button" id="add-mapping" class="button" style="margin-top: 10px;">' . __('Add Mapping', 'lilac') . '</button>';
        
        // JavaScript for adding/removing mappings
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add new mapping
            $('#add-mapping').on('click', function() {
                var index = $('#course-access-mappings .mapping-row').length;
                var newRow = $('<div class="mapping-row" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;"></div>');
                
                // Product select
                var productSelect = $('<select name="lilac_course_access_mappings[' + index + '][product_id]" style="margin-right: 10px;"></select>');
                productSelect.append($('<option value=""><?php echo esc_js(__('Select Product', 'lilac')); ?></option>'));
                <?php foreach ($products as $product): ?>
                productSelect.append($('<option value="<?php echo $product->get_id(); ?>"><?php echo esc_js($product->get_name()); ?></option>'));
                <?php endforeach; ?>
                
                // Course select
                var courseSelect = $('<select name="lilac_course_access_mappings[' + index + '][course_id]" style="margin-right: 10px;"></select>');
                courseSelect.append($('<option value=""><?php echo esc_js(__('Select Course', 'lilac')); ?></option>'));
                <?php foreach ($courses as $course_id => $course_title): ?>
                courseSelect.append($('<option value="<?php echo $course_id; ?>"><?php echo esc_js($course_title); ?></option>'));
                <?php endforeach; ?>
                
                // Role select
                var roleSelect = $('<select name="lilac_course_access_mappings[' + index + '][role]"></select>');
                roleSelect.append($('<option value=""><?php echo esc_js(__('Select Role', 'lilac')); ?></option>'));
                <?php foreach ($roles as $role => $label): ?>
                roleSelect.append($('<option value="<?php echo $role; ?>"><?php echo esc_js($label); ?></option>'));
                <?php endforeach; ?>
                
                // Remove button
                var removeButton = $('<button type="button" class="button remove-mapping" style="margin-left: 10px; color: #a00;"><?php echo esc_js(__('Remove', 'lilac')); ?></button>');
                
                // Add all elements to the row
                newRow.append(productSelect);
                newRow.append(courseSelect);
                newRow.append(roleSelect);
                newRow.append(removeButton);
                
                // Add row to container
                $('#course-access-mappings').append(newRow);
            });
            
            // Remove mapping
            $(document).on('click', '.remove-mapping', function() {
                $(this).closest('.mapping-row').remove();
            });
        });
        </script>
        <?php
    }
    
    private function get_ld_courses() {
        $courses = array();
        
        if (function_exists('learndash_get_course_list')) {
            $ld_courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($ld_courses as $course) {
                $courses[$course->ID] = $course->post_title;
            }
        }
        
        return $courses;
    }
    
    public function get_connections_table() {
        global $wpdb;
        
        $users = get_users(array(
            'meta_key' => 'lilac_course_access',
            'meta_compare' => 'EXISTS'
        ));
        
        $connections = array();
        
        foreach ($users as $user) {
            $access_data = get_user_meta($user->ID, 'lilac_course_access', true);
            if (!is_array($access_data)) continue;
            
            foreach ($access_data as $access) {
                $product = wc_get_product($access['product_id']);
                $course = get_post($access['course_id']);
                
                if (!$product || !$course) continue;
                
                $connections[] = array(
                    'user' => $user,
                    'product' => $product,
                    'course' => $course,
                    'role' => $access['role'],
                    'access_granted' => $access['access_granted'],
                    'access_expires' => $access['access_expires']
                );
            }
        }
        
        return $connections;
    }
    
    public function settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Save settings if form was submitted
        if (isset($_POST['submit'])) {
            if (isset($_POST['lilac_course_access_mappings'])) {
                $mappings = array();
                foreach ($_POST['lilac_course_access_mappings'] as $mapping) {
                    if (!empty($mapping['product_id']) && !empty($mapping['course_id']) && !empty($mapping['role'])) {
                        $mappings[] = array(
                            'product_id' => intval($mapping['product_id']),
                            'course_id' => intval($mapping['course_id']),
                            'role' => sanitize_text_field($mapping['role'])
                        );
                    }
                }
                update_option('lilac_course_access_mappings', $mappings);
                add_settings_error('lilac_messages', 'lilac_message', __('Settings Saved', 'lilac'), 'updated');
            }
        }
        
        // Show admin notices
        settings_errors('lilac_messages');
        
        // Get all connections
        $connections = $this->get_connections_table();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2><?php _e('Course Access Mappings', 'lilac'); ?></h2>
            <form action="" method="post">
                <?php
                settings_fields('lilac_course_access');
                do_settings_sections('lilac-course-access');
                submit_button('Save Mappings');
                ?>
            </form>
            
            <h2><?php _e('Active Connections', 'lilac'); ?></h2>
            <div class="tablenav top">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php 
                        echo sprintf(_n('%s connection', '%s connections', count($connections), 'lilac'), 
                        number_format_i18n(count($connections))); 
                    ?></span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'lilac'); ?></th>
                        <th><?php _e('Email', 'lilac'); ?></th>
                        <th><?php _e('Product', 'lilac'); ?></th>
                        <th><?php _e('Course', 'lilac'); ?></th>
                        <th><?php _e('Role', 'lilac'); ?></th>
                        <th><?php _e('Access Granted', 'lilac'); ?></th>
                        <th><?php _e('Access Expires', 'lilac'); ?></th>
                        <th><?php _e('Status', 'lilac'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($connections)) : ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">
                                <?php _e('No active connections found.', 'lilac'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($connections as $connection) : 
                            $user = $connection['user'];
                            $product = $connection['product'];
                            $course = $connection['course'];
                            $expired = time() > $connection['access_expires'];
                        ?>
                            <tr class="<?php echo $expired ? 'expired' : ''; ?>">
                                <td>
                                    <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>">
                                        <?php echo esc_html($product->get_name()); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($course->ID)); ?>">
                                        <?php echo esc_html($course->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($connection['role']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), $connection['access_granted']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), $connection['access_expires']); ?></td>
                                <td>
                                    <span class="dashicons dashicons-<?php echo $expired ? 'no' : 'yes'; ?>" 
                                          style="color: <?php echo $expired ? '#dc3232' : '#46b450'; ?>">
                                    </span>
                                    <?php echo $expired ? __('Expired', 'lilac') : __('Active', 'lilac'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <style>
                .wp-list-table td, .wp-list-table th { padding: 8px 10px; }
                .expired { opacity: 0.7; }
                .expired td { color: #a00; }
            </style>
        </div>
        <?php
    }
}

// Check if WooCommerce and LearnDash are active
function lilac_are_required_plugins_active() {
    $woocommerce_active = class_exists('WooCommerce');
    $learndash_active = function_exists('learndash_get_course_list');
    
    if (!$woocommerce_active) {
        error_log('Lilac Course Access: WooCommerce is not active');
    }
    
    if (!$learndash_active) {
        error_log('Lilac Course Access: LearnDash is not active');
    }
    
    return $woocommerce_active && $learndash_active;
}

// Initialize the settings page
function lilac_course_access_settings_init() {
    error_log('Lilac Course Access: Initialization started');
    
    // Only run in admin area
    if (!is_admin()) {
        error_log('Lilac Course Access: Not in admin area');
        return;
    }
    
    // Check if required plugins are active
    if (!lilac_are_required_plugins_active()) {
        error_log('Lilac Course Access: Required plugins are not active');
        return;
    }
    
    error_log('Lilac Course Access: All requirements met, initializing...');
    
    // Initialize the settings page with a high priority to ensure WooCommerce is loaded
    add_action('admin_menu', function() {
        error_log('Lilac Course Access: Running admin_menu hook');
        Lilac_Course_Access_Settings::get_instance();
    }, 99); // Higher priority to ensure WooCommerce menu is loaded
}

// Hook into admin_init instead of init to ensure WooCommerce is loaded
add_action('admin_init', 'lilac_course_access_settings_init');
