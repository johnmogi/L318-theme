<?php
/**
 * Teacher Group Assignment Fix
 * 
 * Fixes the empty teacher group assignment popup in School Manager Lite
 * 
 * @package School_Manager_Lite
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Group_Assignment_Fix {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_teacher_group_assignment_form', array($this, 'ajax_get_teacher_group_assignment_form'));
        add_action('wp_ajax_assign_teacher_to_groups', array($this, 'ajax_assign_teacher_to_groups'));
    }
    
    public function enqueue_scripts($hook) {
        if ('school-manager_page_school-manager-teachers' !== $hook) {
            return;
        }
        
        // Enqueue the script
        wp_enqueue_script(
            'teacher-group-assignment',
            plugins_url('js/teacher-group-assignment.js', __FILE__),
            array('jquery', 'thickbox'),
            '1.0.0',
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('teacher-group-assignment', 'teacherGroupAssignment', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teacher_group_assignment_nonce'),
            'i18n' => array(
                'selectGroups' => __('בחר קבוצות', 'school-manager-lite'),
                'assignGroups' => __('שייך קבוצות', 'school-manager-lite'),
                'loading' => __('טוען...', 'school-manager-lite'),
                'error' => __('אירעה שגיאה. אנא נסה שוב.', 'school-manager-lite'),
                'success' => __('הקבוצות עודכנו בהצלחה!', 'school-manager-lite')
            )
        ));
        
        // Enqueue thickbox
        add_thickbox();
    }
    
    public function ajax_get_teacher_group_assignment_form() {
        check_ajax_referer('teacher_group_assignment_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('edit_teachers')) {
            wp_send_json_error(array('message' => __('אין לך הרשאה לבצע פעולה זו', 'school-manager-lite')));
        }
        
        $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
        
        if (!$teacher_id) {
            wp_send_json_error(array('message' => __('לא נבחר מורה', 'school-manager-lite')));
        }
        
        $teacher = get_user_by('id', $teacher_id);
        if (!$teacher) {
            wp_send_json_error(array('message' => __('המורה לא נמצא במערכת', 'school-manager-lite')));
        }
        
        // Get all LearnDash groups
        $groups = $this->get_learndash_groups();
        
        // Get teacher's current groups
        $teacher_groups = $this->get_teacher_groups($teacher_id);
        
        // Start output buffering
        ob_start();
        ?>
        <div class="teacher-group-assignment" style="padding: 20px; max-width: 600px;">
            <h2><?php echo sprintf(__('הקצאת קבוצות עבור %s', 'school-manager-lite'), esc_html($teacher->display_name)); ?></h2>
            
            <div class="group-selection" style="margin: 20px 0;">
                <p><?php _e('בחר את הקבוצות שברצונך לשייך למורה:', 'school-manager-lite'); ?></p>
                
                <div class="groups-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">
                    <?php if (!empty($groups)) : ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($groups as $group) : 
                                $is_assigned = in_array($group->ID, $teacher_groups);
                            ?>
                                <li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                                    <label style="display: flex; align-items: center;">
                                        <input type="checkbox" 
                                               name="teacher_groups[]" 
                                               value="<?php echo esc_attr($group->ID); ?>"
                                               <?php checked($is_assigned); ?>
                                               style="margin-left: 10px;">
                                        <?php echo esc_html($group->post_title); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php _e('לא נמצאו קבוצות במערכת.', 'school-manager-lite'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 20px; text-align: left;">
                <button type="button" 
                        class="button button-primary save-teacher-groups" 
                        data-teacher-id="<?php echo esc_attr($teacher_id); ?>"
                        style="margin-right: 10px;">
                    <?php _e('שמור שינויים', 'school-manager-lite'); ?>
                </button>
                <button type="button" class="button button-secondary cancel-teacher-groups">
                    <?php _e('ביטול', 'school-manager-lite'); ?>
                </button>
                <span class="spinner" style="float: none; margin-top: 0; display: none;"></span>
            </div>
            
            <div class="response-message" style="margin-top: 15px; display: none;"></div>
        </div>
        <?php
        
        $output = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $output
        ));
    }
    
    public function ajax_assign_teacher_to_groups() {
        check_ajax_referer('teacher_group_assignment_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('edit_teachers')) {
            wp_send_json_error(array('message' => __('אין לך הרשאה לבצע פעולה זו', 'school-manager-lite')));
        }
        
        $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
        $group_ids = isset($_POST['group_ids']) ? array_map('intval', $_POST['group_ids']) : array();
        
        if (!$teacher_id) {
            wp_send_json_error(array('message' => __('לא נבחר מורה', 'school-manager-lite')));
        }
        
        $teacher = get_user_by('id', $teacher_id);
        if (!$teacher) {
            wp_send_json_error(array('message' => __('המורה לא נמצא במערכת', 'school-manager-lite')));
        }
        
        // Update teacher's groups
        $result = $this->update_teacher_groups($teacher_id, $group_ids);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('הקבוצות עודכנו בהצלחה!', 'school-manager-lite')
        ));
    }
    
    private function get_learndash_groups() {
        if (!function_exists('learndash_get_groups')) {
            return array();
        }
        
        $args = array(
            'post_type' => 'groups',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    private function get_teacher_groups($teacher_id) {
        if (!function_exists('learndash_get_administrators_group_ids')) {
            return array();
        }
        
        return learndash_get_administrators_group_ids($teacher_id);
    }
    
    private function update_teacher_groups($teacher_id, $group_ids) {
        if (!function_exists('ld_update_leader_group_access')) {
            return new WP_Error('no_learndash', __('LearnDash לא פעיל או שגירסה לא תומכת', 'school-manager-lite'));
        }
        
        // Get current groups
        $current_groups = $this->get_teacher_groups($teacher_id);
        
        // Groups to add
        $groups_to_add = array_diff($group_ids, $current_groups);
        
        // Groups to remove
        $groups_to_remove = array_diff($current_groups, $group_ids);
        
        // Add new groups
        foreach ($groups_to_add as $group_id) {
            ld_update_leader_group_access($teacher_id, $group_id, false);
        }
        
        // Remove old groups
        foreach ($groups_to_remove as $group_id) {
            ld_update_leader_group_access($teacher_id, $group_id, true);
        }
        
        return true;
    }
}

// Initialize the fix
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Teacher_Group_Assignment_Fix::instance();
    }
});
