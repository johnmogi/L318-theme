<?php
/**
 * Group/Class Field Manager
 * 
 * Adds and manages the group/class field for users
 * 
 * @package Hello_Theme_Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Group_Field_Manager {
    /**
     * The meta key for the group field
     */
    const GROUP_FIELD_KEY = 'user_group_class';

    /**
     * Initialize the class
     */
    public function __construct() {
        // Add the group field to user profiles
        add_action('show_user_profile', array($this, 'add_group_field'));
        add_action('edit_user_profile', array($this, 'add_group_field'));
        
        // Save the group field
        add_action('personal_options_update', array($this, 'save_group_field'));
        add_action('edit_user_profile_update', array($this, 'save_group_field'));
        
        // Add column to users list
        add_filter('manage_users_columns', array($this, 'add_group_column'));
        add_filter('manage_users_custom_column', array($this, 'show_group_column_content'), 10, 3);
        
        // Make the column sortable
        add_filter('manage_users_sortable_columns', array($this, 'make_group_column_sortable'));
        
        // Handle sorting
        add_action('pre_get_users', array($this, 'handle_group_sorting'));
    }
    
    /**
     * Add group field to user profile
     */
    public function add_group_field($user) {
        // Get all available groups (you might want to fetch these from your groups table)
        $groups = $this->get_available_groups();
        $user_group = get_user_meta($user->ID, self::GROUP_FIELD_KEY, true);
        ?>
        <h3><?php _e('Class/Group Information', 'hello-theme-child'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="<?php echo esc_attr(self::GROUP_FIELD_KEY); ?>"><?php _e('Class/Group', 'hello-theme-child'); ?></label></th>
                <td>
                    <select name="<?php echo esc_attr(self::GROUP_FIELD_KEY); ?>" id="<?php echo esc_attr(self::GROUP_FIELD_KEY); ?>" class="regular-text">
                        <option value=""><?php _e('-- Select Group --', 'hello-theme-child'); ?></option>
                        <?php foreach ($groups as $group_id => $group_name) : ?>
                            <option value="<?php echo esc_attr($group_id); ?>" <?php selected($user_group, $group_id); ?>>
                                <?php echo esc_html($group_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Select the class/group this user belongs to.', 'hello-theme-child'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save group field
     */
    public function save_group_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST[self::GROUP_FIELD_KEY])) {
            update_user_meta($user_id, self::GROUP_FIELD_KEY, sanitize_text_field($_POST[self::GROUP_FIELD_KEY]));
        }
    }
    
    /**
     * Add group column to users list
     */
    public function add_group_column($columns) {
        $new_columns = array();
        
        // Add the group column right after the username
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'username') {
                $new_columns['user_group'] = __('Class/Group', 'hello-theme-child');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Show group column content
     */
    public function show_group_column_content($value, $column_name, $user_id) {
        if ('user_group' === $column_name) {
            $group_id = get_user_meta($user_id, self::GROUP_FIELD_KEY, true);
            if ($group_id) {
                $groups = $this->get_available_groups();
                return isset($groups[$group_id]) ? esc_html($groups[$group_id]) : __('None', 'hello-theme-child');
            }
            return __('None', 'hello-theme-child');
        }
        return $value;
    }
    
    /**
     * Make group column sortable
     */
    public function make_group_column_sortable($columns) {
        $columns['user_group'] = 'user_group';
        return $columns;
    }
    
    /**
     * Handle sorting by group
     */
    public function handle_group_sorting($query) {
        if (!is_admin()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ('user_group' === $orderby) {
            $query->query_vars['meta_key'] = self::GROUP_FIELD_KEY;
            $query->query_vars['orderby'] = 'meta_value';
        }
    }
    
    /**
     * Get available groups
     * 
     * @return array Array of groups (id => name)
     */
    private function get_available_groups() {
        // This is a placeholder - you should replace this with your actual groups
        // For example, you might get these from a custom post type or taxonomy
        return array(
            'group1' => 'Class A',
            'group2' => 'Class B',
            'group3' => 'Class C',
        );
    }
    
    /**
     * Get users by group
     * 
     * @param string $group_id Group ID
     * @return array Array of user IDs in the group
     */
    public static function get_users_by_group($group_id) {
        $args = array(
            'meta_key' => self::GROUP_FIELD_KEY,
            'meta_value' => $group_id,
            'fields' => 'ID',
        );
        
        return get_users($args);
    }
}

// Initialize the group field manager
function init_group_field_manager() {
    new Group_Field_Manager();
}
add_action('init', 'init_group_field_manager');
