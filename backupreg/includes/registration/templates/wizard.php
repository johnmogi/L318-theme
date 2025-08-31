<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap registration-wizard">
    <h1>אשף יצירת כיתה וקודי הרשמה</h1>
    
    <div class="wizard-container">
        <!-- Step 1: Teacher -->
        <div class="wizard-step active" data-step="1">
            <h2>שלב 1: פרטי המורה</h2>
            <div class="wizard-content">
                <p>פרטי המורה ימופיעו כאן</p>
            </div>
            <div class="wizard-actions">
                <button type="button" class="button button-primary next-step">הבא</button>
            </div>
        </div>
        
        <!-- Step 2: Class -->
        <div class="wizard-step" data-step="2" style="display: none;">
            <h2>שלב 2: פרטי הכיתה</h2>
            <div class="wizard-content">
                <p>פרטי הכיתה יופיעו כאן</p>
            </div>
            <div class="wizard-actions">
                <button type="button" class="button prev-step">חזור</button>
                <button type="button" class="button button-primary next-step">הבא</button>
            </div>
        </div>
        
        <!-- Step 3: Codes -->
        <div class="wizard-step" data-step="3" style="display: none;">
            <h2>שלב 3: יצירת קודים</h2>
            <div class="wizard-content">
                <p>יצירת קודי הרשמה תופיע כאן</p>
            </div>
            <div class="wizard-actions">
                <button type="button" class="button prev-step">חזור</button>
                <button type="button" class="button button-primary" id="generate-codes">צור קודים</button>
            </div>
        </div>
    </div>
</div>
    
    <form id="registration-wizard-form" method="post">
        <?php wp_nonce_field('registration_wizard_action', 'registration_wizard_nonce'); ?>
        
        <!-- Step 1: Teacher -->
        <div class="wizard-pane active" data-step="1">
            <div class="card">
                <h2>פרטי מורה</h2>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="teacher-type">בחר אפשרות:</label>
                        <select id="teacher-type" name="teacher_type" class="regular-text">
                            <option value="existing">בחירת מורה קיים</option>
                            <option value="new">הוספת מורה חדש</option>
                        </select>
                    </div>
                </div>
                
                <div id="existing-teacher-fields">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="existing-teacher">בחר מורה:</label>
                            <select id="existing-teacher" name="existing_teacher" class="regular-text">
                                <option value="">-- בחר מורה --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo esc_attr($teacher->ID); ?>">
                                        <?php echo esc_html($teacher->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div id="new-teacher-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="teacher-name">שם מלא:</label>
                            <input type="text" id="teacher-name" name="teacher_name" class="regular-text">
                        </div>
                        <div class="form-col">
                            <label for="teacher-email">אימייל:</label>
                            <input type="email" id="teacher-email" name="teacher_email" class="regular-text">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button button-primary next-step" data-next="2">הבא &raquo;</button>
            </div>
        </div>
        
        <!-- Step 2: Class -->
        <div class="wizard-pane" data-step="2">
            <div class="card">
                <h2>פרטי כיתה</h2>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="class-type">בחר אפשרות:</label>
                        <select id="class-type" name="class_type" class="regular-text">
                            <option value="existing">בחירת כיתה קיימת</option>
                            <option value="new">יצירת כיתה חדשה</option>
                        </select>
                    </div>
                </div>
                
                <div id="existing-class-fields">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="existing-class">בחר כיתה:</label>
                            <select id="existing-class" name="existing_class" class="regular-text">
                                <option value="">-- בחר כיתה --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo esc_attr($class); ?>">
                                        <?php echo esc_html($class); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div id="new-class-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="class-name">שם הכיתה:</label>
                            <input type="text" id="class-name" name="class_name" class="regular-text">
                        </div>
                        <div class="form-col">
                            <label for="class-grade">שכבה:</label>
                            <select id="class-grade" name="class_grade" class="regular-text">
                                <option value="">-- בחר שכבה --</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">כיתה <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button prev-step" data-prev="1">&laquo; הקודם</button>
                <button type="button" class="button button-primary next-step" data-next="3">הבא &raquo;</button>
            </div>
        </div>
        
        <!-- Step 3: Students -->
        <div class="wizard-pane" data-step="3">
            <div class="card">
                <h2>ניהול תלמידים</h2>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="student-import-type">הוספת תלמידים:</label>
                        <select id="student-import-type" name="student_import_type" class="regular-text">
                            <option value="manual">הוספה ידנית</option>
                            <option value="excel">ייבוא מקובץ אקסל</option>
                        </select>
                    </div>
                </div>
                
                <div id="manual-student-fields">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="student-first-name">שם פרטי:</label>
                            <input type="text" id="student-first-name" name="student_first_name[]" class="regular-text">
                        </div>
                        <div class="form-col">
                            <label for="student-last-name">שם משפחה:</label>
                            <input type="text" id="student-last-name" name="student_last_name[]" class="regular-text">
                        </div>
                        <div class="form-col">
                            <button type="button" class="button add-student" style="margin-top: 20px;">+ הוסף תלמיד</button>
                        </div>
                    </div>
                    
                    <div id="students-list">
                        <!-- Dynamic student rows will be added here -->
                    </div>
                </div>
                
                <div id="excel-import-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="excel-file">בחר קובץ אקסל:</label>
                            <input type="file" id="excel-file" name="excel_file" accept=".xlsx, .xls, .csv">
                            <p class="description">העלה קובץ אקסל עם עמודות: שם פרטי, שם משפחה, מייל</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wizard-actions">
                <button type="button" class="button prev-step" data-prev="2">&laquo; הקודם</button>
                <button type="submit" name="save_changes" class="button button-primary">שמור שינויים</button>
            </div>
        </div>
        
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
                                <?php echo esc_html($teacher->display_name); ?> (<?php echo esc_html($teacher->user_email); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <p>או צור מורה חדש:</p>
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
                
                <div class="form-group">
                    <label for="new-class">או צור כיתה חדשה:</label>
                    <input type="text" id="new-class" name="new_class" class="regular-text" placeholder="שם הכיתה">
                </div>
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
                    
                    <div class="form-col">
                        <label>תפקיד משתמש:</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="user_role" value="teacher" checked>
                                מורה
                            </label>
                            <label>
                                <input type="radio" name="user_role" value="coordinator">
                                רכז
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="code-prefix">קידומת קוד:</label>
                        <input type="text" id="code-prefix" name="code_prefix" class="regular-text" placeholder="PROMO">
                    </div>
                    
                    <div class="form-col">
                        <label for="code-format">פורמט קוד:</label>
                        <select id="code-format" name="code_format" class="regular-text">
                            <option value="alphanum">אותיות + מספרים</option>
                            <option value="letters">אותיות בלבד</option>
                            <option value="numbers">מספרים בלבד</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="code-length">אורך קוד:</label>
                        <input type="number" id="code-length" name="code_length" min="4" max="32" value="8" class="small-text">
                    </div>
                    
                    <div class="form-col">
                        <label for="max-uses">מקס׳ שימושים:</label>
                        <input type="number" id="max-uses" name="max_uses" min="0" value="1" class="small-text">
                        <span class="description">(0 = ללא הגבלה)</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label for="expiry-date">תאריך תפוגה:</label>
                        <input type="text" id="expiry-date" name="expiry_date" class="regular-text datepicker">
                    </div>
                    
                    <div class="form-col">
                        <label for="learndash-course">קורס LearnDash:</label>
                        <select id="learndash-course" name="learndash_course" class="regular-text">
                            <option value="">-- ללא --</option>
                            <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo esc_attr($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
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
                            <input type="tel" id="mobile" name="mobile" class="regular-text" placeholder="05________">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="password">סיסמה:</label>
                            <input type="text" id="password" name="password" class="regular-text" autocomplete="new-password">
                            <p class="description">(ריק=אקראי)</p>
                        </div>
                    </div>
                </div>
                
                <div class="excel-upload">
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
    
    <div id="wizard-results" class="card" style="display: none;
        <h3>תוצאות יצירת הקודים</h3>
        <div id="wizard-results-content"></div>
    </div>
</div>
