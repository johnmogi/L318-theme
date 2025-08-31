<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Define specific roles with their display names
$roles = array(
    'school_teacher'  => 'מורה / רכז',
    'student_school'  => 'תלמיד חינוך תעבורתי',
    'student_private' => 'תלמיד עצמאי'
);

// Get existing groups for suggestions
global $wpdb;
$groups = $wpdb->get_col("SELECT DISTINCT group_name FROM {$wpdb->prefix}registration_codes WHERE group_name != '' ORDER BY group_name");

// Get available LearnDash courses
$courses = array();
// Check if post type exists first (safer way to check for LearnDash)
if (post_type_exists('sfwd-courses')) {
    // Get all published courses regardless of user permissions
    $course_query_args = array(
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    $course_query = new WP_Query($course_query_args);
    if ($course_query->have_posts()) {
        while ($course_query->have_posts()) {
            $course_query->the_post();
            $course_id = get_the_ID();
            $courses[$course_id] = get_the_title();
        }
        wp_reset_postdata();
    }
    
    // If no courses found, add a debug message
    if (empty($courses)) {
        $courses['debug'] = 'No courses found - please check LearnDash settings';
    }
} else {
    // LearnDash not activated
    $courses['debug'] = 'LearnDash not installed or activated';
}
?>

<div class="registration-codes-generate">
    <div class="notice generated-codes-notice notice-success" style="display: none;">
        <p></p>
    </div>
    
    <div class="card">
        <h2>צור קודי הרשמה חדשים</h2>
        
        <form id="generate-codes-form" method="post" action="">
            <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="code-count">מספר קודים</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="code-count" 
                                   name="code_count" 
                                   class="regular-text" 
                                   min="1" 
                                   max="1000" 
                                   value="10" 
                                   required>
                            <p class="description">
                                הזן את מספר הקודים שברצונך ליצור (מקסימום 1000 בפעם אחת).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-role">תפקיד משתמש</label>
                        </th>
                        <td>
                            <select id="code-role" name="code_role" class="regular-text" required>
                                <?php foreach ($roles as $role => $name) : ?>
                                    <option value="<?php echo esc_attr($role); ?>">
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                בחר את תפקיד המשתמש שיוקצה בעת שימוש בקוד זה.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-group">שם כיתה</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="code-group" 
                                   name="code_group" 
                                   class="regular-text" 
                                   list="group-suggestions"
                                   placeholder="<?php esc_attr_e('לדוגמה: כיתה יא1, כיתה יב3', 'registration-codes'); ?>">
                            <datalist id="group-suggestions">
                                <?php foreach ($groups as $group) : ?>
                                    <option value="<?php echo esc_attr($group); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <p class="description">
                                אופציונלי: קבץ קודים אלה יחד לניהול קל יותר.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-prefix">קידומת קוד</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="code-prefix" 
                                   name="code_prefix" 
                                   class="regular-text" 
                                   maxlength="10"
                                   placeholder="<?php esc_attr_e('e.g., PROMO', 'registration-codes'); ?>">
                            <p class="description">
                                אופציונלי: הוסף קידומת לכל הקודים שנוצרו (מקסימום 10 תווים).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3>מידע תלמיד</h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="first-name"><?php _e('First Name', 'registration-codes'); ?> (<?php _e('שם פרטי', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="first-name" 
                                   name="first_name" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Enter first name', 'registration-codes'); ?>" 
                                   dir="rtl">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="last-name"><?php _e('Last Name', 'registration-codes'); ?> (<?php _e('שם משפחה', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="last-name" 
                                   name="last_name" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Enter last name', 'registration-codes'); ?>" 
                                   dir="rtl">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="school-name"><?php _e('School Name', 'registration-codes'); ?> (<?php _e('שם בית ספר', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="school-name" 
                                   name="school_name" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Enter school name', 'registration-codes'); ?>" 
                                   dir="rtl">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="school-city"><?php _e('School City', 'registration-codes'); ?> (<?php _e('עיר בית ספר', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="school-city" 
                                   name="school_city" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Enter school city', 'registration-codes'); ?>" 
                                   dir="rtl">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="school-code"><?php _e('School Code', 'registration-codes'); ?> (<?php _e('קוד בית ספר', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="school-code" 
                                   name="school_code" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Enter school code', 'registration-codes'); ?>" 
                                   dir="ltr">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mobile-phone"><?php _e('Mobile Phone', 'registration-codes'); ?> (<?php _e('טלפון נייד', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="tel" 
                                   id="mobile-phone" 
                                   name="mobile_phone" 
                                   class="regular-text" 
                                   placeholder="05xxxxxxxx" 
                                   pattern="[0-9]{10}" 
                                   dir="ltr"
                                   required>
                            <p class="description">
                                מספר הפעמים המרבי שכל קוד יכול לשמש (0 ללא הגבלה).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user-password"><?php _e('Password', 'registration-codes'); ?> (<?php _e('סיסמה', 'registration-codes'); ?>)</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="user-password" 
                                   name="user_password" 
                                   class="regular-text" 
                                   placeholder="<?php _e('Leave empty to generate automatically', 'registration-codes'); ?>"
                                   dir="ltr">
                            <p class="description">
                                אם נשאר ריק, סיסמה אקראית תיווצר.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3>הגדרות יצירת קוד</h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-format">פורמט קוד</label>
                        </th>
                        <td>
                            <select id="code-format" name="code_format" class="regular-text">
                                <option value="alphanumeric" selected>אותיות ומספרים (A-Z, 0-9)</option>
                                <option value="letters">אותיות בלבד (A-Z)</option>
                                <option value="numbers">מספרים בלבד (0-9)</option>
                            </select>
                            <p class="description">
                                בחר את הפורמט עבור הקודים שייווצרו.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-length">אורך קוד</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="code-length" 
                                   name="code_length" 
                                   class="small-text" 
                                   min="6" 
                                   max="32" 
                                   value="8">
                            <p class="description">
                                בחר את אורך הקודים שייווצרו (לא כולל קידומת).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-course"><?php _e('LearnDash Course', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <select id="code-course" name="code_course" class="regular-text">
                                <option value="">-- ללא --</option>
                                <?php foreach ($courses as $course_id => $course_name) : ?>
                                    <option value="<?php echo esc_attr($course_id); ?>">
                                        <?php echo esc_html($course_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                אופציונלי: בחר קורס LearnDash לרישום משתמשים כאשר הם משתמשים בקוד זה.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-max-uses">מקסימום שימושים</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="code-max-uses" 
                                   name="code_max_uses" 
                                   class="small-text" 
                                   min="1" 
                                   value="1">
                            <p class="description">
                                מספר הפעמים המרבי שכל קוד יכול לשמש (0 ללא הגבלה).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-expiry">תאריך תפוגה</label>
                        </th>
                        <td>
                            <input type="date" 
                                   id="code-expiry" 
                                   name="code_expiry" 
                                   class="regular-text">
                            <p class="description">
                                אופציונלי: הגדר תאריך תפוגה לקודים אלה (השאר ריק ללא הגבלת זמן).
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" name="generate_codes" class="button button-primary">
                    צור קודים
                </button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
    
    <div id="generated-codes-container" class="card" style="display: none;">
        <h2>קודים שנוצרו</h2>
        
        <div class="generated-codes-actions">
            <button type="button" id="copy-codes" class="button">
                <span class="dashicons dashicons-clipboard"></span>
                העתק ללוח
            </button>
            <button type="button" id="download-csv" class="button">
                <span class="dashicons dashicons-download"></span>
                הורד כ-CSV
            </button>
            <button type="button" id="print-codes" class="button">
                <span class="dashicons dashicons-printer"></span>
                הדפס
            </button>
        </div>
        
        <div id="generated-codes-output" class="code-output">
            <!-- Codes will be displayed here -->
        </div>
        
        <div class="generated-codes-notice notice notice-success" style="display: none;">
            <p></p>
        </div>
    </div>
</div>

<!-- Code Preview Modal -->
<div id="code-preview-modal" class="registration-codes-modal" style="display: none;">
    <div class="registration-codes-modal-content">
        <div class="registration-codes-modal-header">
            <h3>תצוגה מקדימה של קודים שנוצרו</h3>
            <button type="button" class="registration-codes-modal-close">&times;</button>
        </div>
        <div class="registration-codes-modal-body">
            <div class="code-preview-container"></div>
        </div>
        <div class="registration-codes-modal-footer">
            <button type="button" class="button button-primary registration-codes-modal-close">
                סגור
            </button>
        </div>
    </div>
</div>
