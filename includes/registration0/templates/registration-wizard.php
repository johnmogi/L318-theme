<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user info
$current_user = wp_get_current_user();
$is_super_admin = current_user_can('manage_network');

// Get available teachers
$teachers = get_users(array(
    'role__in' => array('administrator', 'teacher'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Get existing classes
global $wpdb;
$classes = $wpdb->get_col("SELECT DISTINCT group_name FROM {$wpdb->prefix}registration_codes WHERE group_name != '' ORDER BY group_name");

// Default values
$default_expiry_date = date('d/m/Y', strtotime('+1 year'));
?>

<div class="wrap registration-wizard">
    <h1>אשף יצירת כיתה וקודי הרשמה</h1>
    
    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-title">בחירת מורה</span>
        </div>
        <div class="wizard-step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-title">בחירת כיתה</span>
        </div>
        <div class="wizard-step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-title">יצירת קודים</span>
        </div>
    </div>
    
    <form id="registration-wizard-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('registration_wizard_action', 'registration_wizard_nonce'); ?>
        
        <!-- Step 1: Teacher Selection -->
        <div class="wizard-pane active" data-step="1">
            <div class="card">
                <h2>שלב 1 — בחר/י מורה</h2>
                
                <div class="form-group">
                    <label for="existing-teacher">מורה קיים:</label>
                    <select id="existing-teacher" name="existing_teacher" class="regular-text">
                        <option value="">בחר מורה</option>
                        <?php foreach ($teachers as $teacher) : ?>
                            <option value="<?php echo esc_attr($teacher->ID); ?>">
                                <?php echo esc_html($teacher->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="or-divider">או</div>
                
                <div class="form-group">
                    <label>צור מורה חדש:</label>
                    <div class="form-row">
                        <div class="form-col">
                            <input type="text" id="new-teacher-name" name="new_teacher_name" class="regular-text" placeholder="שם מלא">
                        </div>
                        <div class="form-col">
                            <input type="email" id="new-teacher-email" name="new_teacher_email" class="regular-text" placeholder="אימייל">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button button-primary next-step" data-next="2">הבא &raquo;</button>
            </div>
        </div>
        
        <!-- Step 2: Class Selection -->
        <div class="wizard-pane" data-step="2">
            <div class="card">
                <h2>שלב 2 — בחר/י כיתה</h2>
                
                <div class="form-group">
                    <label for="existing-class">כיתה קיימת:</label>
                    <select id="existing-class" name="existing_class" class="regular-text">
                        <option value="">בחר כיתה</option>
                        <?php foreach ($classes as $class) : ?>
                            <option value="<?php echo esc_attr($class); ?>"><?php echo esc_html($class); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="or-divider">או</div>
                
                <div class="form-group">
                    <label for="new-class">צור כיתה חדשה:</label>
                    <input type="text" id="new-class" name="new_class" class="regular-text" placeholder="שם הכיתה">
                </div>
                
                <?php if ($is_super_admin) : ?>
                <div class="form-group">
                    <label>שיוך אוטומטי:</label>
                    <div class="auto-assignment">
                        <p>תכנית "חינוך תעבורתי" › כיתה י'</p>
                        <p class="description">* רק סופר-אדמין רשאי לשנות שיוך זה</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button previous-step" data-prev="1">&laquo; הקודם</button>
                <button type="button" class="button button-primary next-step" data-next="3">הבא &raquo;</button>
            </div>
        </div>
        
        <!-- Step 3: Code Generation -->
        <div class="wizard-pane" data-step="3">
            <div class="card">
                <h2>שלב 3 — צור קודי הרשמה</h2>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="code-count">מספר קודים:</label>
                        <input type="number" id="code-count" name="code_count" min="1" max="1000" value="10" class="small-text">
                        <span class="description">(מקס׳ 1000)</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="code-prefix">קידומת קוד:</label>
                    <input type="text" id="code-prefix" name="code_prefix" class="regular-text" placeholder="PROMO" value="PROMO">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="code-length">אורך קוד:</label>
                        <input type="number" id="code-length" name="code_length" min="8" max="32" value="8" class="small-text">
                    </div>
                    
                    <div class="form-col">
                        <label for="max-uses">מקס׳ שימושים:</label>
                        <input type="number" id="max-uses" name="max_uses" min="1" value="1" class="small-text" readonly>
                        <span class="description">(קבוע = שימוש יחיד)</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="expiry-date">תאריך תפוגה:</label>
                    <input type="text" id="expiry-date" name="expiry_date" class="regular-text datepicker" 
                           value="<?php echo esc_attr($default_expiry_date); ?>">
                    <span class="description">(ברירת-מחדל: סוף שנה נוכחית)</span>
                </div>
                
                <?php if ($is_super_admin) : ?>
                <div class="form-group">
                    <label for="learndash-course">LearnDash Course:</label>
                    <select id="learndash-course" name="learndash_course" class="regular-text">
                        <option value="">-- בחר קורס --</option>
                        <?php 
                        // Get LearnDash courses if plugin is active
                        if (function_exists('ld_course_list')) {
                            $courses = get_posts(array(
                                'post_type' => 'sfwd-courses',
                                'numberposts' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ));
                            
                            foreach ($courses as $course) {
                                echo '<option value="' . esc_attr($course->ID) . '">' . 
                                     esc_html($course->post_title) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p class="description">(סופר-אדמין בלבד יכול לשנות)</p>
                </div>
                <?php endif; ?>
                
                <!-- Student Information -->
                <div class="student-info">
                    <h3>מידע תלמיד (אופציונלי)</h3>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="first-name">שם פרטי:</label>
                            <input type="text" id="first-name" name="first_name" class="regular-text">
                        </div>
                        
                        <div class="form-col">
                            <label for="last-name">שם משפחה:</label>
                            <input type="text" id="last-name" name="last_name" class="regular-text">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="school-name">שם בי״ס:</label>
                            <input type="text" id="school-name" name="school_name" class="regular-text">
                        </div>
                        
                        <div class="form-col">
                            <label for="school-city">עיר בי״ס:</label>
                            <input type="text" id="school-city" name="school_city" class="regular-text">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="school-code">קוד בי״ס:</label>
                            <input type="text" id="school-code" name="school_code" class="regular-text">
                        </div>
                        
                        <div class="form-col">
                            <label for="mobile">טלפון נייד:</label>
                            <div class="input-with-note">
                                <input type="tel" id="mobile" name="mobile" class="regular-text" placeholder="05________" pattern="05\d{8}">
                                <span class="input-note">← שם המשתמש</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="password">סיסמה:</label>
                            <div class="input-with-note">
                                <input type="text" id="password" name="password" class="regular-text" autocomplete="new-password">
                                <span class="input-note">← תעודת הזהות (ריק = סיסמה אקראית)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Excel Import -->
                <div class="excel-import">
                    <h3>ייבוא קבוצתי (Excel)</h3>
                    <div class="upload-area" id="excel-dropzone">
                        <p>גרור/י קובץ Excel לכאן או לחצו לבחירה</p>
                        <p class="description">פורמט: first | last | mobile | email | class</p>
                        <input type="file" id="excel-file" name="excel_file" accept=".xlsx, .xls, .csv" style="display: none;">
                    </div>
                    <button type="button" id="browse-excel" class="button">בחר קובץ</button>
                    <div id="excel-preview"></div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button previous-step" data-prev="2">&laquo; הקודם</button>
                <button type="submit" name="generate_codes" class="button button-primary">צור קודים</button>
                <span class="spinner"></span>
            </div>
        </div>
    </form>
    
    <div id="wizard-results" class="card" style="display: none;">
        <h3>תוצאות יצירת הקודים</h3>
        <div id="wizard-results-content"></div>
    </div>
</div>
