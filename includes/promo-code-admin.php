<?php
/**
 * Promo Code System - Admin Interface
 * 
 * Provides admin functionality to generate and manage promo codes
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add the promo code admin menu
 */
function lilac_add_promo_code_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=sfwd-courses',
        'קודי פרומו', // Page title
        'קודי פרומו', // Menu title
        'manage_options',
        'lilac-promo-codes',
        'lilac_promo_code_admin_page'
    );
}
add_action('admin_menu', 'lilac_add_promo_code_admin_menu');

/**
 * Display the promo code admin page
 */
function lilac_promo_code_admin_page() {
    // Process form submissions
    lilac_process_promo_code_actions();
    
    ?>
    <div class="wrap">
        <h1>ניהול קודי פרומו</h1>
        
        <h2>צור קוד פרומו חדש</h2>
        <form method="post">
            <?php wp_nonce_field('lilac_create_promo_code', 'lilac_promo_code_nonce'); ?>
            <input type="hidden" name="action" value="create_promo_code">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="code">קוד (או השאר ריק לייצור אוטומטי)</label></th>
                    <td><input type="text" id="code" name="code" class="regular-text" placeholder="השאר ריק לייצור אוטומטי"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="course_id">קורס</label></th>
                    <td>
                        <select id="course_id" name="course_id" required>
                            <option value="">בחר קורס</option>
                            <?php
                            // Get all LearnDash courses
                            $courses = get_posts([
                                'post_type' => 'sfwd-courses',
                                'numberposts' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                            ]);
                            
                            foreach ($courses as $course) {
                                echo '<option value="' . $course->ID . '">' . esc_html($course->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="description">תיאור</label></th>
                    <td><input type="text" id="description" name="description" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_uses">מספר שימושים מקסימלי</label></th>
                    <td>
                        <input type="number" id="max_uses" name="max_uses" value="1" min="1" class="small-text">
                        <p class="description">כמה פעמים ניתן להשתמש בקוד זה (1 לשימוש חד-פעמי)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="expiry_date">תאריך תפוגה</label></th>
                    <td>
                        <input type="date" id="expiry_date" name="expiry_date">
                        <p class="description">השאר ריק לקוד ללא תאריך תפוגה</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="צור קוד פרומו">
            </p>
        </form>
        
        <hr>
        
        <h2>קודי פרומו קיימים</h2>
        <?php lilac_display_promo_codes_table(); ?>
    </div>
    <?php
}

/**
 * Display the table of existing promo codes
 */
function lilac_display_promo_codes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_promo_codes';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo '<div class="notice notice-error"><p>טבלת קודי הפרומו לא קיימת. אנא רענן את העמוד.</p></div>';
        return;
    }
    
    // Get all promo codes
    $promo_codes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    // Display the table
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>קוד</th>
                <th>קורס</th>
                <th>תיאור</th>
                <th>שימושים</th>
                <th>תאריך תפוגה</th>
                <th>סטטוס</th>
                <th>נוצר ב</th>
                <th>פעולות</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($promo_codes)) : ?>
                <tr>
                    <td colspan="8">אין קודי פרומו.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($promo_codes as $code) : ?>
                    <tr>
                        <td><?php echo esc_html($code->code); ?></td>
                        <td>
                            <?php 
                            $course_title = get_the_title($code->course_id);
                            echo $course_title ? esc_html($course_title) : '<em>קורס לא קיים</em>';
                            ?>
                        </td>
                        <td><?php echo esc_html($code->description); ?></td>
                        <td><?php echo $code->used_count . ' / ' . $code->max_uses; ?></td>
                        <td>
                            <?php 
                            echo !empty($code->expiry_date) ? date_i18n(get_option('date_format'), strtotime($code->expiry_date)) : 'אין תפוגה';
                            ?>
                        </td>
                        <td>
                            <?php
                            $status = '';
                            $now = current_time('timestamp');
                            
                            if ($code->is_active == 0) {
                                $status = '<span class="promo-code-inactive">לא פעיל</span>';
                            } elseif (!empty($code->expiry_date) && strtotime($code->expiry_date) < $now) {
                                $status = '<span class="promo-code-expired">פג תוקף</span>';
                            } elseif ($code->used_count >= $code->max_uses) {
                                $status = '<span class="promo-code-used">נוצל במלואו</span>';
                            } else {
                                $status = '<span class="promo-code-active">פעיל</span>';
                            }
                            
                            echo $status;
                            ?>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($code->created_at)); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('lilac_delete_promo_code', 'lilac_promo_code_nonce'); ?>
                                <input type="hidden" name="action" value="delete_promo_code">
                                <input type="hidden" name="code_id" value="<?php echo $code->id; ?>">
                                <button type="submit" class="button button-small" onclick="return confirm('האם אתה בטוח שברצונך למחוק את הקוד הזה?');">מחק</button>
                            </form>
                            
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('lilac_toggle_promo_code', 'lilac_promo_code_nonce'); ?>
                                <input type="hidden" name="action" value="toggle_promo_code">
                                <input type="hidden" name="code_id" value="<?php echo $code->id; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $code->is_active ? '0' : '1'; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $code->is_active ? 'בטל' : 'הפעל'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <style>
        .promo-code-active { color: green; font-weight: bold; }
        .promo-code-inactive { color: gray; }
        .promo-code-expired { color: orange; }
        .promo-code-used { color: red; }
    </style>
    <?php
}

/**
 * Process promo code admin actions
 */
function lilac_process_promo_code_actions() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'create_promo_code':
            if (!isset($_POST['lilac_promo_code_nonce']) || !wp_verify_nonce($_POST['lilac_promo_code_nonce'], 'lilac_create_promo_code')) {
                wp_die('אבטחה נכשלה');
            }
            
            if (!current_user_can('manage_options')) {
                wp_die('אין לך הרשאה לבצע פעולה זו');
            }
            
            $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
            $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
            $max_uses = isset($_POST['max_uses']) ? intval($_POST['max_uses']) : 1;
            $expiry_date = isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;
            
            if (empty($code)) {
                // Generate a random code if none provided
                $code = lilac_generate_random_code();
            }
            
            if (empty($course_id)) {
                add_settings_error('lilac_promo_codes', 'no-course', 'אנא בחר קורס', 'error');
                return;
            }
            
            // Create the promo code
            $result = lilac_create_promo_code($code, $course_id, $description, $max_uses, $expiry_date);
            
            if ($result === true) {
                add_settings_error('lilac_promo_codes', 'code-created', 'קוד הפרומו נוצר בהצלחה: ' . $code, 'success');
            } else {
                add_settings_error('lilac_promo_codes', 'create-error', 'שגיאה: ' . $result, 'error');
            }
            break;
            
        case 'delete_promo_code':
            if (!isset($_POST['lilac_promo_code_nonce']) || !wp_verify_nonce($_POST['lilac_promo_code_nonce'], 'lilac_delete_promo_code')) {
                wp_die('אבטחה נכשלה');
            }
            
            if (!current_user_can('manage_options')) {
                wp_die('אין לך הרשאה לבצע פעולה זו');
            }
            
            $code_id = isset($_POST['code_id']) ? intval($_POST['code_id']) : 0;
            
            if (empty($code_id)) {
                add_settings_error('lilac_promo_codes', 'no-id', 'מזהה קוד לא תקין', 'error');
                return;
            }
            
            // Delete the promo code
            $result = lilac_delete_promo_code($code_id);
            
            if ($result === true) {
                add_settings_error('lilac_promo_codes', 'code-deleted', 'קוד הפרומו נמחק בהצלחה', 'success');
            } else {
                add_settings_error('lilac_promo_codes', 'delete-error', 'שגיאה: ' . $result, 'error');
            }
            break;
            
        case 'toggle_promo_code':
            if (!isset($_POST['lilac_promo_code_nonce']) || !wp_verify_nonce($_POST['lilac_promo_code_nonce'], 'lilac_toggle_promo_code')) {
                wp_die('אבטחה נכשלה');
            }
            
            if (!current_user_can('manage_options')) {
                wp_die('אין לך הרשאה לבצע פעולה זו');
            }
            
            $code_id = isset($_POST['code_id']) ? intval($_POST['code_id']) : 0;
            $new_status = isset($_POST['new_status']) ? intval($_POST['new_status']) : 0;
            
            if (empty($code_id)) {
                add_settings_error('lilac_promo_codes', 'no-id', 'מזהה קוד לא תקין', 'error');
                return;
            }
            
            // Toggle the promo code status
            $result = lilac_toggle_promo_code($code_id, $new_status);
            
            if ($result === true) {
                $status_text = $new_status ? 'הופעל' : 'בוטל';
                add_settings_error('lilac_promo_codes', 'code-toggled', 'סטטוס קוד הפרומו ' . $status_text . ' בהצלחה', 'success');
            } else {
                add_settings_error('lilac_promo_codes', 'toggle-error', 'שגיאה: ' . $result, 'error');
            }
            break;
    }
}

/**
 * Generate a random promo code
 */
function lilac_generate_random_code($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * Create a new promo code
 * 
 * @param string $code The promo code
 * @param int $course_id The course ID
 * @param string $description The code description
 * @param int $max_uses Maximum number of uses
 * @param string|null $expiry_date Expiry date (Y-m-d format)
 * @return true|string True on success, error message on failure
 */
function lilac_create_promo_code($code, $course_id, $description = '', $max_uses = 1, $expiry_date = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_promo_codes';
    
    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return 'טבלת קודי הפרומו לא קיימת';
    }
    
    // Check if the code already exists
    $existing_code = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE code = %s", $code));
    if ($existing_code) {
        return 'קוד פרומו זה כבר קיים';
    }
    
    // Check if the course exists
    if (!get_post($course_id) || get_post_type($course_id) !== 'sfwd-courses') {
        return 'הקורס שנבחר אינו קיים';
    }
    
    // Format expiry date for MySQL
    $expiry_date_sql = null;
    if (!empty($expiry_date)) {
        $expiry_date_obj = new DateTime($expiry_date);
        $expiry_date_sql = $expiry_date_obj->format('Y-m-d H:i:s');
    }
    
    // Insert the promo code
    $result = $wpdb->insert(
        $table_name,
        [
            'code' => $code,
            'course_id' => $course_id,
            'description' => $description,
            'max_uses' => $max_uses,
            'used_count' => 0,
            'expiry_date' => $expiry_date_sql,
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ],
        [
            '%s', '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%d'
        ]
    );
    
    if ($result === false) {
        return 'שגיאה בהוספת קוד הפרומו לבסיס הנתונים: ' . $wpdb->last_error;
    }
    
    return true;
}

/**
 * Delete a promo code
 * 
 * @param int $code_id The promo code ID
 * @return true|string True on success, error message on failure
 */
function lilac_delete_promo_code($code_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_promo_codes';
    
    // Delete the promo code
    $result = $wpdb->delete(
        $table_name,
        ['id' => $code_id],
        ['%d']
    );
    
    if ($result === false) {
        return 'שגיאה במחיקת קוד הפרומו: ' . $wpdb->last_error;
    }
    
    // Also delete any usage records
    $usage_table = $wpdb->prefix . 'lilac_promo_code_usage';
    if ($wpdb->get_var("SHOW TABLES LIKE '$usage_table'") == $usage_table) {
        $wpdb->delete(
            $usage_table,
            ['code_id' => $code_id],
            ['%d']
        );
    }
    
    return true;
}

/**
 * Toggle a promo code's active status
 * 
 * @param int $code_id The promo code ID
 * @param int $new_status The new status (1 for active, 0 for inactive)
 * @return true|string True on success, error message on failure
 */
function lilac_toggle_promo_code($code_id, $new_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lilac_promo_codes';
    
    // Update the promo code status
    $result = $wpdb->update(
        $table_name,
        ['is_active' => $new_status ? 1 : 0],
        ['id' => $code_id],
        ['%d'],
        ['%d']
    );
    
    if ($result === false) {
        return 'שגיאה בעדכון סטטוס קוד הפרומו: ' . $wpdb->last_error;
    }
    
    return true;
}
