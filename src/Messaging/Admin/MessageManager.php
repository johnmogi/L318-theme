<?php
/**
 * Message Management Interface
 *
 * Provides CRUD functionality for custom toast messages.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging/Admin
 */

namespace Lilac\Messaging\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MessageManager {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Option name for storing message data
     */
    private $option_name = 'lilac_custom_messages';

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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submit'));
        
        // Add AJAX handlers
        add_action('wp_ajax_lilac_toggle_message_status', array($this, 'ajax_toggle_message_status'));
        add_action('wp_ajax_lilac_delete_message', array($this, 'ajax_delete_message'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lilac-messaging',
            __('Message Management', 'hello-child'),
            __('Message Management', 'hello-child'),
            'manage_options',
            'lilac-message-manager',
            array($this, 'admin_page_display')
        );
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submit() {
        if (!isset($_POST['lilac_message_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['lilac_message_nonce']) || !wp_verify_nonce($_POST['lilac_message_nonce'], 'lilac_message_manager')) {
            wp_die(__('Security check failed.', 'hello-child'));
        }
        
        $action = sanitize_text_field($_POST['lilac_message_action']);
        
        switch ($action) {
            case 'add':
                $this->add_message();
                break;
                
            case 'edit':
                $this->edit_message();
                break;
                
            case 'duplicate':
                $this->duplicate_message();
                break;
        }
    }

    /**
     * Add a new message
     */
    private function add_message() {
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Create new message
        $message = $this->prepare_message_data();
        
        // Add to messages array
        $messages[] = $message;
        
        // Save messages
        update_option($this->option_name, $messages);
        
        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg(array('page' => 'lilac-message-manager', 'success' => 'added'), admin_url('admin.php')));
        exit;
    }

    /**
     * Edit an existing message
     */
    private function edit_message() {
        // Get message ID
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            return;
        }
        
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Find message index
        $index = false;
        foreach ($messages as $key => $message) {
            if (isset($message['id']) && $message['id'] === $message_id) {
                $index = $key;
                break;
            }
        }
        
        if ($index === false) {
            return;
        }
        
        // Update message
        $messages[$index] = $this->prepare_message_data($message_id);
        
        // Save messages
        update_option($this->option_name, $messages);
        
        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg(array('page' => 'lilac-message-manager', 'success' => 'updated'), admin_url('admin.php')));
        exit;
    }

    /**
     * Duplicate an existing message
     */
    private function duplicate_message() {
        // Get message ID
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            return;
        }
        
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Find message to duplicate
        $duplicate = null;
        foreach ($messages as $message) {
            if (isset($message['id']) && $message['id'] === $message_id) {
                $duplicate = $message;
                break;
            }
        }
        
        if ($duplicate === null) {
            return;
        }
        
        // Create new message based on duplicate
        $duplicate['id'] = uniqid();
        $duplicate['title'] .= ' ' . __('(Copy)', 'hello-child');
        $duplicate['active'] = false;
        
        // Add to messages array
        $messages[] = $duplicate;
        
        // Save messages
        update_option($this->option_name, $messages);
        
        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg(array('page' => 'lilac-message-manager', 'success' => 'duplicated'), admin_url('admin.php')));
        exit;
    }

    /**
     * Toggle message status via AJAX
     */
    public function ajax_toggle_message_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lilac_message_ajax')) {
            wp_send_json_error(__('Security check failed.', 'hello-child'));
        }
        
        // Get message ID
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            wp_send_json_error(__('Missing message ID.', 'hello-child'));
        }
        
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Find message index
        $index = false;
        foreach ($messages as $key => $message) {
            if (isset($message['id']) && $message['id'] === $message_id) {
                $index = $key;
                break;
            }
        }
        
        if ($index === false) {
            wp_send_json_error(__('Message not found.', 'hello-child'));
        }
        
        // Toggle status
        $messages[$index]['active'] = !$messages[$index]['active'];
        
        // Save messages
        update_option($this->option_name, $messages);
        
        wp_send_json_success(array(
            'active' => $messages[$index]['active'],
            'status_text' => $messages[$index]['active'] ? __('Active', 'hello-child') : __('Inactive', 'hello-child')
        ));
    }

    /**
     * Delete message via AJAX
     */
    public function ajax_delete_message() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lilac_message_ajax')) {
            wp_send_json_error(__('Security check failed.', 'hello-child'));
        }
        
        // Get message ID
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            wp_send_json_error(__('Missing message ID.', 'hello-child'));
        }
        
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Find message index
        $index = false;
        foreach ($messages as $key => $message) {
            if (isset($message['id']) && $message['id'] === $message_id) {
                $index = $key;
                break;
            }
        }
        
        if ($index === false) {
            wp_send_json_error(__('Message not found.', 'hello-child'));
        }
        
        // Remove message
        array_splice($messages, $index, 1);
        
        // Save messages
        update_option($this->option_name, $messages);
        
        wp_send_json_success();
    }

    /**
     * Prepare message data from form submission
     */
    private function prepare_message_data($message_id = '') {
        // If no message ID, generate one
        if (empty($message_id)) {
            $message_id = uniqid();
        }
        
        // Post types
        $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array();
        $post_types = array_map('sanitize_text_field', $post_types);
        
        return array(
            'id' => $message_id,
            'title' => sanitize_text_field($_POST['message_title']),
            'content' => wp_kses_post($_POST['message_content']),
            'type' => sanitize_text_field($_POST['message_type']),
            'auto_close' => absint($_POST['auto_close']),
            'display_conditions' => array(
                'homepage' => isset($_POST['display_homepage']),
                'once_per_session' => isset($_POST['display_once_per_session']),
                'specific_urls' => sanitize_textarea_field($_POST['specific_urls']),
                'post_types' => $post_types
            ),
            'active' => isset($_POST['is_active'])
        );
    }

    /**
     * Display the admin page
     */
    public function admin_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get existing messages
        $messages = get_option($this->option_name, array());
        
        // Get available post types
        $post_types = get_post_types(array('public' => true), 'objects');
        
        // Handle edit request
        $edit_mode = false;
        $edit_message = null;
        
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['message_id'])) {
            $message_id = sanitize_text_field($_GET['message_id']);
            
            foreach ($messages as $message) {
                if (isset($message['id']) && $message['id'] === $message_id) {
                    $edit_mode = true;
                    $edit_message = $message;
                    break;
                }
            }
        }
        
        // Display success message
        if (isset($_GET['success'])) {
            $success_message = '';
            
            switch ($_GET['success']) {
                case 'added':
                    $success_message = __('Message added successfully!', 'hello-child');
                    break;
                    
                case 'updated':
                    $success_message = __('Message updated successfully!', 'hello-child');
                    break;
                    
                case 'duplicated':
                    $success_message = __('Message duplicated successfully!', 'hello-child');
                    break;
            }
            
            if (!empty($success_message)) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-messaging')); ?>" class="nav-tab"><?php _e('Welcome Message', 'hello-child'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-message-manager')); ?>" class="nav-tab nav-tab-active"><?php _e('Message Management', 'hello-child'); ?></a>
            </h2>
            
            <div id="col-container" class="wp-clearfix">
                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2><?php echo $edit_mode ? __('Edit Message', 'hello-child') : __('Add New Message', 'hello-child'); ?></h2>
                            
                            <form method="post" action="">
                                <?php wp_nonce_field('lilac_message_manager', 'lilac_message_nonce'); ?>
                                <input type="hidden" name="lilac_message_action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                                
                                <?php if ($edit_mode): ?>
                                <input type="hidden" name="message_id" value="<?php echo esc_attr($edit_message['id']); ?>">
                                <?php endif; ?>
                                
                                <div class="form-field">
                                    <label for="message_title"><?php _e('Message Title', 'hello-child'); ?></label>
                                    <input type="text" id="message_title" name="message_title" value="<?php echo $edit_mode ? esc_attr($edit_message['title']) : ''; ?>" required>
                                </div>
                                
                                <div class="form-field">
                                    <label for="message_content"><?php _e('Message Content', 'hello-child'); ?></label>
                                    <textarea id="message_content" name="message_content" rows="5" required><?php echo $edit_mode ? esc_textarea($edit_message['content']) : ''; ?></textarea>
                                    <p class="description"><?php _e('Supports HTML and placeholders: {user_name}, {visit_count}, {session_time}, {course_progress}', 'hello-child'); ?></p>
                                    
                                    <div class="lilac-placeholders-info" style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-left: 4px solid #0073aa;">
                                        <h4 style="margin-top:0"><?php _e('Available Placeholders:', 'hello-child'); ?></h4>
                                        <ul>
                                            <li><code>{user_name}</code> - <?php _e('Current user\'s display name', 'hello-child'); ?></li>
                                            <li><code>{visit_count}</code> - <?php _e('Number of site visits in current session', 'hello-child'); ?></li>
                                            <li><code>{session_time}</code> - <?php _e('Active time on site (formatted as hours:minutes)', 'hello-child'); ?></li>
                                            <li><code>{course_progress}</code> - <?php _e('Overall course progress percentage', 'hello-child'); ?></li>
                                        </ul>
                                        <p><?php _e('Example: "שלום {user_name}! ביקרת באתר {visit_count} פעמים."', 'hello-child'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="form-field">
                                    <label for="message_type"><?php _e('Message Type', 'hello-child'); ?></label>
                                    <select id="message_type" name="message_type">
                                        <option value="info" <?php selected($edit_mode && $edit_message['type'] === 'info'); ?>><?php _e('Info (Blue)', 'hello-child'); ?></option>
                                        <option value="success" <?php selected($edit_mode && $edit_message['type'] === 'success'); ?>><?php _e('Success (Green)', 'hello-child'); ?></option>
                                        <option value="warning" <?php selected($edit_mode && $edit_message['type'] === 'warning'); ?>><?php _e('Warning (Yellow)', 'hello-child'); ?></option>
                                        <option value="error" <?php selected($edit_mode && $edit_message['type'] === 'error'); ?>><?php _e('Error (Red)', 'hello-child'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-field">
                                    <label for="auto_close"><?php _e('Auto Close (seconds)', 'hello-child'); ?></label>
                                    <input type="number" id="auto_close" name="auto_close" value="<?php echo $edit_mode ? esc_attr($edit_message['auto_close']) : '0'; ?>" min="0" max="60">
                                    <p class="description"><?php _e('Set to 0 to disable auto-close', 'hello-child'); ?></p>
                                </div>
                                
                                <h3><?php _e('Display Conditions', 'hello-child'); ?></h3>
                                
                                <div class="form-field">
                                    <label>
                                        <input type="checkbox" name="display_homepage" value="1" <?php checked($edit_mode && isset($edit_message['display_conditions']['homepage']) && $edit_message['display_conditions']['homepage']); ?>>
                                        <?php _e('Show on Homepage', 'hello-child'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-field">
                                    <label>
                                        <input type="checkbox" name="display_once_per_session" value="1" <?php checked(!$edit_mode || ($edit_mode && isset($edit_message['display_conditions']['once_per_session']) && $edit_message['display_conditions']['once_per_session'])); ?>>
                                        <?php _e('Show Once Per Session', 'hello-child'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-field">
                                    <label for="specific_urls"><?php _e('Show on Specific URLs', 'hello-child'); ?></label>
                                    <textarea id="specific_urls" name="specific_urls" rows="3" placeholder="/sample-page/"><?php echo $edit_mode && isset($edit_message['display_conditions']['specific_urls']) ? esc_textarea($edit_message['display_conditions']['specific_urls']) : ''; ?></textarea>
                                    <p class="description"><?php _e('Enter one URL per line. Leave empty to show on all pages.', 'hello-child'); ?></p>
                                </div>
                                
                                <div class="form-field">
                                    <label><?php _e('Show on Post Types', 'hello-child'); ?></label>
                                    <div style="margin-top: 5px;">
                                        <?php foreach ($post_types as $post_type): ?>
                                        <label style="display: block; margin-bottom: 5px;">
                                            <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked($edit_mode && isset($edit_message['display_conditions']['post_types']) && in_array($post_type->name, $edit_message['display_conditions']['post_types'])); ?>>
                                            <?php echo esc_html($post_type->labels->name); ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="form-field">
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" <?php checked(!$edit_mode || ($edit_mode && isset($edit_message['active']) && $edit_message['active'])); ?>>
                                        <?php _e('Active', 'hello-child'); ?>
                                    </label>
                                </div>
                                
                                <p class="submit">
                                    <input type="submit" class="button button-primary" value="<?php echo $edit_mode ? __('Update Message', 'hello-child') : __('Add Message', 'hello-child'); ?>">
                                    
                                    <?php if ($edit_mode): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-message-manager')); ?>" class="button"><?php _e('Cancel', 'hello-child'); ?></a>
                                    <?php endif; ?>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div id="col-right">
                    <div class="col-wrap">
                        <h2><?php _e('Custom Messages', 'hello-child'); ?></h2>
                        
                        <?php if (empty($messages)): ?>
                            <p><?php _e('No custom messages found. Add your first message using the form.', 'hello-child'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th width="25%"><?php _e('Title', 'hello-child'); ?></th>
                                        <th width="15%"><?php _e('Type', 'hello-child'); ?></th>
                                        <th width="30%"><?php _e('Display Conditions', 'hello-child'); ?></th>
                                        <th width="10%"><?php _e('Status', 'hello-child'); ?></th>
                                        <th width="20%"><?php _e('Actions', 'hello-child'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                    <tr data-id="<?php echo esc_attr($message['id']); ?>">
                                        <td><?php echo esc_html($message['title']); ?></td>
                                        <td><?php echo esc_html(ucfirst($message['type'])); ?></td>
                                        <td>
                                            <?php
                                            $conditions = array();
                                            if (isset($message['display_conditions']['homepage']) && $message['display_conditions']['homepage']) {
                                                $conditions[] = __('Homepage', 'hello-child');
                                            }
                                            if (!empty($message['display_conditions']['post_types'])) {
                                                $conditions[] = sprintf(_n('%s post type', '%s post types', count($message['display_conditions']['post_types']), 'hello-child'), count($message['display_conditions']['post_types']));
                                            }
                                            if (!empty($message['display_conditions']['specific_urls'])) {
                                                $url_count = count(array_filter(explode("\n", $message['display_conditions']['specific_urls'])));
                                                $conditions[] = sprintf(_n('%s URL', '%s URLs', $url_count, 'hello-child'), $url_count);
                                            }
                                            echo !empty($conditions) ? esc_html(implode(', ', $conditions)) : __('All pages', 'hello-child');
                                            ?>
                                        </td>
                                        <td>
                                            <span class="lilac-message-status" style="color: <?php echo isset($message['active']) && $message['active'] ? 'green' : 'gray'; ?>">
                                                <?php echo isset($message['active']) && $message['active'] ? __('Active', 'hello-child') : __('Inactive', 'hello-child'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(add_query_arg(array('page' => 'lilac-message-manager', 'action' => 'edit', 'message_id' => $message['id']), admin_url('admin.php'))); ?>" class="button button-small"><?php _e('Edit', 'hello-child'); ?></a>
                                            
                                            <button class="button button-small lilac-toggle-message" data-id="<?php echo esc_attr($message['id']); ?>" data-active="<?php echo isset($message['active']) && $message['active'] ? '1' : '0'; ?>">
                                                <?php echo isset($message['active']) && $message['active'] ? __('Deactivate', 'hello-child') : __('Activate', 'hello-child'); ?>
                                            </button>
                                            
                                            <form method="post" action="" style="display:inline-block;">
                                                <?php wp_nonce_field('lilac_message_manager', 'lilac_message_nonce'); ?>
                                                <input type="hidden" name="lilac_message_action" value="duplicate">
                                                <input type="hidden" name="message_id" value="<?php echo esc_attr($message['id']); ?>">
                                                <button type="submit" class="button button-small"><?php _e('Duplicate', 'hello-child'); ?></button>
                                            </form>
                                            
                                            <button class="button button-small lilac-delete-message" data-id="<?php echo esc_attr($message['id']); ?>"><?php _e('Delete', 'hello-child'); ?></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <script>
                            jQuery(document).ready(function($) {
                                // Toggle message status
                                $('.lilac-toggle-message').on('click', function() {
                                    var button = $(this);
                                    var messageId = button.data('id');
                                    var isActive = button.data('active') === '1';
                                    
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'lilac_toggle_message_status',
                                            message_id: messageId,
                                            nonce: '<?php echo wp_create_nonce('lilac_message_ajax'); ?>'
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                // Update button text
                                                button.text(isActive ? '<?php _e('Activate', 'hello-child'); ?>' : '<?php _e('Deactivate', 'hello-child'); ?>');
                                                button.data('active', isActive ? '0' : '1');
                                                
                                                // Update status text
                                                var statusText = button.closest('tr').find('.lilac-message-status');
                                                statusText.text(response.data.status_text);
                                                statusText.css('color', response.data.active ? 'green' : 'gray');
                                            } else {
                                                alert(response.data);
                                            }
                                        },
                                        error: function() {
                                            alert('<?php _e('An error occurred. Please try again.', 'hello-child'); ?>');
                                        }
                                    });
                                });
                                
                                // Delete message
                                $('.lilac-delete-message').on('click', function() {
                                    var button = $(this);
                                    var messageId = button.data('id');
                                    
                                    if (confirm('<?php _e('Are you sure you want to delete this message?', 'hello-child'); ?>')) {
                                        $.ajax({
                                            url: ajaxurl,
                                            type: 'POST',
                                            data: {
                                                action: 'lilac_delete_message',
                                                message_id: messageId,
                                                nonce: '<?php echo wp_create_nonce('lilac_message_ajax'); ?>'
                                            },
                                            success: function(response) {
                                                if (response.success) {
                                                    button.closest('tr').remove();
                                                    
                                                    // If no messages left, show empty message
                                                    if ($('table tbody tr').length === 0) {
                                                        $('table').replaceWith('<p><?php _e('No custom messages found. Add your first message using the form.', 'hello-child'); ?></p>');
                                                    }
                                                } else {
                                                    alert(response.data);
                                                }
                                            },
                                            error: function() {
                                                alert('<?php _e('An error occurred. Please try again.', 'hello-child'); ?>');
                                            }
                                        });
                                    }
                                });
                            });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the class
function lilac_message_manager() {
    return MessageManager::get_instance();
}
lilac_message_manager();
