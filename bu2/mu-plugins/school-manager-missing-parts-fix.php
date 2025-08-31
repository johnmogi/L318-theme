<?php
/**
 * Plugin Name: School Manager Missing Parts Fix
 * Description: Fixes missing database tables, incomplete AJAX handlers, and missing connections
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class School_Manager_Missing_Parts_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'fix_missing_parts'));
        add_action('wp_ajax_complete_promo_validation', array($this, 'complete_promo_validation'));
        add_action('wp_ajax_nopriv_complete_promo_validation', array($this, 'complete_promo_validation'));
        add_action('admin_init', array($this, 'fix_admin_errors'));
    }
    
    /**
     * Fix missing database tables and connections
     */
    public function fix_missing_parts() {
        global $wpdb;
        
        // Create missing student-classes relationship table
        $this->create_student_classes_table();
        
        // Fix missing teacher-student connections
        $this->fix_teacher_student_connections();
        
        // Fix missing group leader assignments
        $this->fix_group_leader_assignments();
        
        // Log completion
        error_log('School Manager Missing Parts Fix: All missing parts addressed');
    }
    
    /**
     * Create missing student-classes relationship table
     */
    private function create_student_classes_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'school_student_classes';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                student_id int(11) NOT NULL,
                class_id int(11) NOT NULL,
                wp_user_id int(11) DEFAULT NULL,
                enrollment_date datetime DEFAULT CURRENT_TIMESTAMP,
                status varchar(20) DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_student_class (student_id, class_id),
                KEY idx_student_id (student_id),
                KEY idx_class_id (class_id),
                KEY idx_wp_user_id (wp_user_id),
                KEY idx_status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log("Created missing student-classes table: $table_name");
        }
    }
    
    /**
     * Fix missing teacher-student connections
     */
    private function fix_teacher_student_connections() {
        global $wpdb;
        
        $teachers_table = $wpdb->prefix . 'school_teachers';
        $students_table = $wpdb->prefix . 'school_students';
        $classes_table = $wpdb->prefix . 'school_classes';
        $teacher_students_table = $wpdb->prefix . 'school_teacher_students';
        
        // Check if all required tables exist
        $tables_exist = true;
        foreach ([$teachers_table, $students_table, $classes_table] as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $tables_exist = false;
                break;
            }
        }
        
        if (!$tables_exist) {
            error_log('School Manager Missing Parts Fix: Required tables missing, skipping connection fix');
            return;
        }
        
        // Get all students without teacher connections
        $unconnected_students = $wpdb->get_results("
            SELECT s.id as student_id, s.wp_user_id, c.teacher_id, c.id as class_id
            FROM $students_table s
            JOIN $classes_table c ON s.class_id = c.id
            LEFT JOIN $teacher_students_table ts ON s.id = ts.student_id
            WHERE ts.id IS NULL
        ");
        
        $fixed_connections = 0;
        foreach ($unconnected_students as $student) {
            $result = $wpdb->insert(
                $teacher_students_table,
                array(
                    'teacher_id' => $student->teacher_id,
                    'student_id' => $student->student_id,
                    'class_id' => $student->class_id,
                    'wp_user_id' => $student->wp_user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%s')
            );
            
            if ($result) {
                $fixed_connections++;
            }
        }
        
        if ($fixed_connections > 0) {
            error_log("Fixed $fixed_connections missing teacher-student connections");
        }
    }
    
    /**
     * Fix missing group leader assignments
     */
    private function fix_group_leader_assignments() {
        global $wpdb;
        
        $classes_table = $wpdb->prefix . 'school_classes';
        
        // Check if classes table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$classes_table'") != $classes_table) {
            return;
        }
        
        // Get all classes with LearnDash groups but missing group leaders
        $classes_with_groups = $wpdb->get_results("
            SELECT c.id, c.teacher_id, c.group_id, c.name
            FROM $classes_table c
            WHERE c.group_id IS NOT NULL AND c.group_id > 0
        ");
        
        $fixed_leaders = 0;
        foreach ($classes_with_groups as $class) {
            // Check if teacher is already a group leader
            $is_leader = learndash_is_group_leader_user($class->teacher_id);
            $group_leaders = learndash_get_groups_administrators($class->group_id);
            
            if (!in_array($class->teacher_id, $group_leaders)) {
                // Add teacher as group leader
                learndash_set_groups_administrators($class->group_id, array($class->teacher_id));
                $fixed_leaders++;
                
                error_log("Added teacher {$class->teacher_id} as leader for group {$class->group_id} (class: {$class->name})");
            }
        }
        
        if ($fixed_leaders > 0) {
            error_log("Fixed $fixed_leaders missing group leader assignments");
        }
    }
    
    /**
     * Complete the missing promo code validation AJAX handler
     */
    public function complete_promo_validation() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'school_manager_ajax')) {
            wp_send_json_error(array('message' => 'אימות אבטחה נכשל'));
            return;
        }
        
        // Get promo code
        $promo_code = sanitize_text_field($_POST['promo_code'] ?? '');
        
        if (empty($promo_code)) {
            wp_send_json_error(array('message' => 'קוד פרומו נדרש'));
            return;
        }
        
        global $wpdb;
        $promo_table = $wpdb->prefix . 'school_promo_codes';
        
        // Check if promo code exists and is valid
        $promo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $promo_table WHERE code = %s AND status = 'active' AND (expiry_date IS NULL OR expiry_date > NOW())",
            $promo_code
        ));
        
        if (!$promo) {
            wp_send_json_error(array('message' => 'קוד פרומו לא תקין או פג תוקף'));
            return;
        }
        
        // Check usage limit
        if ($promo->usage_limit > 0 && $promo->used_count >= $promo->usage_limit) {
            wp_send_json_error(array('message' => 'קוד פרומו הגיע למגבלת השימוש'));
            return;
        }
        
        // Get class information
        $classes_table = $wpdb->prefix . 'school_classes';
        $class = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $classes_table WHERE id = %d",
            $promo->class_id
        ));
        
        if (!$class) {
            wp_send_json_error(array('message' => 'כיתה לא נמצאה'));
            return;
        }
        
        // Return validation success with class info
        wp_send_json_success(array(
            'message' => 'קוד פרומו תקין',
            'promo_code' => $promo_code,
            'class_name' => $class->name,
            'class_id' => $class->id,
            'teacher_id' => $class->teacher_id,
            'course_id' => $promo->course_id ?? null
        ));
    }
    
    /**
     * Fix admin page errors
     */
    public function fix_admin_errors() {
        // Fix missing list table class error
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-teachers') {
            $list_table_file = SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-teachers-list-table.php';
            
            if (!file_exists($list_table_file)) {
                $this->create_missing_list_table_class();
            }
        }
    }
    
    /**
     * Create missing teachers list table class
     */
    private function create_missing_list_table_class() {
        $list_table_file = SCHOOL_MANAGER_LITE_PATH . 'includes/admin/class-teachers-list-table.php';
        $list_table_dir = dirname($list_table_file);
        
        // Create directory if it doesn't exist
        if (!is_dir($list_table_dir)) {
            wp_mkdir_p($list_table_dir);
        }
        
        // Create the missing list table class
        $list_table_content = '<?php
/**
 * Teachers List Table Class
 */

if (!defined("ABSPATH")) {
    exit;
}

if (!class_exists("WP_List_Table")) {
    require_once ABSPATH . "wp-admin/includes/class-wp-list-table.php";
}

class School_Manager_Lite_Teachers_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            "singular" => "teacher",
            "plural" => "teachers",
            "ajax" => false
        ));
    }
    
    public function prepare_items() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $teachers_table = $wpdb->prefix . "school_teachers";
        
        // Get teachers
        $teachers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $teachers_table ORDER BY name LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $teachers_table");
        
        $this->items = $teachers;
        
        $this->set_pagination_args(array(
            "total_items" => $total_items,
            "per_page" => $per_page,
            "total_pages" => ceil($total_items / $per_page)
        ));
    }
    
    public function get_columns() {
        return array(
            "name" => __("Name", "school-manager-lite"),
            "email" => __("Email", "school-manager-lite"),
            "phone" => __("Phone", "school-manager-lite"),
            "actions" => __("Actions", "school-manager-lite")
        );
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case "name":
            case "email":
            case "phone":
                return $item->$column_name;
            case "actions":
                return sprintf(
                    \'<a href="#" class="button">%s</a>\',
                    __("Edit", "school-manager-lite")
                );
            default:
                return "";
        }
    }
    
    public function process_bulk_action() {
        // Handle bulk actions if needed
    }
    
    public function search_box($text, $input_id) {
        if (empty($_REQUEST["s"]) && !$this->has_items()) {
            return;
        }
        
        $input_id = $input_id . "-search-input";
        
        if (!empty($_REQUEST["orderby"])) {
            echo \'<input type="hidden" name="orderby" value="\' . esc_attr($_REQUEST["orderby"]) . \'" />\';
        }
        if (!empty($_REQUEST["order"])) {
            echo \'<input type="hidden" name="order" value="\' . esc_attr($_REQUEST["order"]) . \'" />\';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, "", "", false, array("id" => "search-submit")); ?>
        </p>
        <?php
    }
}';
        
        file_put_contents($list_table_file, $list_table_content);
        error_log("Created missing teachers list table class: $list_table_file");
    }
}

// Initialize the fix
School_Manager_Missing_Parts_Fix::instance();
