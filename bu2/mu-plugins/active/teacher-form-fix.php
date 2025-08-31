<?php
/**
 * Plugin Name: Teacher Form Localization Fix
 * Description: Comprehensive fix for teacher form Hebrew localization and missing functionality
 * Version: 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teacher_Form_Localization_Fix {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook early to ensure translations are loaded
        add_action('init', array($this, 'load_translations'), 1);
        add_action('admin_init', array($this, 'handle_teacher_form'));
        add_action('admin_head', array($this, 'add_comprehensive_localization'));
        add_filter('gettext', array($this, 'translate_school_manager_strings'), 20, 3);
        add_filter('gettext_with_context', array($this, 'translate_school_manager_strings_with_context'), 20, 4);
    }
    
    /**
     * Load translations early
     */
    public function load_translations() {
        // Force load the school-manager-lite textdomain
        $plugin_dir = WP_PLUGIN_DIR . '/school-manager-lite';
        $language_dir = $plugin_dir . '/languages';
        
        // Try multiple methods to load translations
        load_plugin_textdomain('school-manager-lite', false, 'school-manager-lite/languages/');
        load_textdomain('school-manager-lite', $language_dir . '/school-manager-lite-he_IL.mo');
        
        // Set locale if not already set
        if (get_locale() !== 'he_IL') {
            add_filter('locale', function($locale) {
                return 'he_IL';
            });
        }
    }
    
    /**
     * Translate school manager strings
     */
    public function translate_school_manager_strings($translated, $original, $domain) {
        if ($domain !== 'school-manager-lite') {
            return $translated;
        }
        
        $translations = array(
            'Teachers' => 'מורים',
            'Add New' => 'הוסף חדש',
            'Add Teacher' => 'הוסף מורה',
            'First Name' => 'שם פרטי',
            'Last Name' => 'שם משפחה',
            'Email' => 'אימייל',
            'Phone' => 'טלפון',
            'Cancel' => 'בטל',
            'Search Teachers' => 'חפש מורים',
            'Name' => 'שם',
            'Actions' => 'פעולות',
            'Edit' => 'עריכה',
            'Delete' => 'מחיקה',
            'No teachers found.' => 'לא נמצאו מורים.',
            'Teacher added successfully!' => 'המורה נוסף בהצלחה!',
            'Error creating teacher:' => 'שגיאה ביצירת המורה:'
        );
        
        return isset($translations[$original]) ? $translations[$original] : $translated;
    }
    
    /**
     * Translate school manager strings with context
     */
    public function translate_school_manager_strings_with_context($translated, $original, $context, $domain) {
        if ($domain !== 'school-manager-lite') {
            return $translated;
        }
        
        // Handle context-specific translations if needed
        return $this->translate_school_manager_strings($translated, $original, $domain);
    }
    
    /**
     * Handle teacher form submission
     */
    public function handle_teacher_form() {
        if (isset($_POST['add_teacher']) && check_admin_referer('school_manager_add_teacher', 'school_manager_nonce')) {
            // Get form data
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['email']);
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            
            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($email)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>שגיאה: כל השדות הנדרשים חייבים להיות מלאים.</p></div>';
                });
                return;
            }
            
            // Check if email already exists
            if (email_exists($email)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>שגיאה: כתובת האימייל כבר קיימת במערכת.</p></div>';
                });
                return;
            }
            
            // Create username from first and last name
            $username = strtolower($first_name . '_' . $last_name);
            $username = sanitize_user($username);
            
            // Ensure username is unique
            $original_username = $username;
            $counter = 1;
            while (username_exists($username)) {
                $username = $original_username . '_' . $counter;
                $counter++;
            }
            
            // Generate secure password
            $password = wp_generate_password(12, false);
            
            // Create user
            $user_data = array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
                'role' => 'wdm_instructor'
            );
            
            $user_id = wp_insert_user($user_data);
            
            if (!is_wp_error($user_id)) {
                // Get user object
                $user = get_user_by('id', $user_id);
                
                // Add phone to user meta
                if (!empty($phone)) {
                    update_user_meta($user_id, 'phone', $phone);
                }
                
                // Add comprehensive capabilities
                $capabilities = array(
                    'read' => true,
                    'school_teacher' => true,
                    'manage_school_students' => true,
                    'access_teacher_dashboard' => true,
                    'edit_sfwd-quizzes' => true,
                    'edit_others_sfwd-quizzes' => true,
                    'publish_sfwd-quizzes' => true,
                    'read_private_sfwd-quizzes' => true,
                    'delete_sfwd-quizzes' => true,
                    'delete_others_sfwd-quizzes' => true,
                    'edit_groups' => true,
                    'edit_others_groups' => true,
                    'read_groups' => true,
                    'delete_groups' => true,
                    'group_leader' => true,
                    'manage_categories' => true,
                    'upload_files' => true
                );
                
                foreach ($capabilities as $cap => $grant) {
                    $user->add_cap($cap, $grant);
                }
                
                // Add to school database if exists
                global $wpdb;
                $teachers_table = $wpdb->prefix . 'school_teachers';
                if ($wpdb->get_var("SHOW TABLES LIKE '$teachers_table'") == $teachers_table) {
                    $wpdb->insert(
                        $teachers_table,
                        array(
                            'wp_user_id' => $user_id,
                            'name' => $first_name . ' ' . $last_name,
                            'email' => $email,
                            'phone' => $phone,
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%s')
                    );
                }
                
                // Show success message with password
                add_action('admin_notices', function() use ($user, $password) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <h3>מורה חדש נוסף בהצלחה!</h3>
                        <p><strong>שם משתמש:</strong> <?php echo esc_html($user->user_login); ?></p>
                        <p><strong>אימייל:</strong> <?php echo esc_html($user->user_email); ?></p>
                        <p><strong>סיסמה זמנית:</strong> <code><?php echo esc_html($password); ?></code></p>
                        <p><em>יש להעביר את פרטי ההתחברות למורה ולבקש ממנו לשנות את הסיסמה בכניסה הראשונה.</em></p>
                    </div>
                    <?php
                });
                
                // Log the action
                error_log("Teacher created successfully: ID {$user_id}, Username: {$username}, Email: {$email}");
                
            } else {
                // Show error message
                add_action('admin_notices', function() use ($user_id) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><strong>שגיאה ביצירת המורה:</strong> <?php echo esc_html($user_id->get_error_message()); ?></p>
                    </div>
                    <?php
                });
                
                // Log the error
                error_log("Error creating teacher: " . $user_id->get_error_message());
            }
        }
    }
    
    /**
     * Add comprehensive localization and form enhancements
     */
    public function add_comprehensive_localization() {
        // Only run on the school-manager-teachers admin page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'school-manager_page_school-manager-teachers') {
            return;
        }
        
        ?>
        <style>
        .school-manager-admin {
            direction: rtl;
            text-align: right;
        }
        .school-manager-admin .form-table th {
            text-align: right;
            padding-right: 0;
            padding-left: 20px;
        }
        .school-manager-admin .postbox-header h2 {
            font-family: 'Arial', sans-serif;
        }
        .notice h3 {
            margin-top: 0;
        }
        .notice code {
            background: #f1f1f1;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Teacher Form Localization: Starting translation...');
            
            // Wait a bit for the DOM to be fully loaded
            setTimeout(function() {
                // Translate page title
                const pageTitle = document.querySelector('.wp-heading-inline');
                if (pageTitle && pageTitle.textContent.trim() === 'Teachers') {
                    pageTitle.textContent = 'מורים';
                    console.log('Translated page title');
                }
                
                // Translate "Add New" button
                const addNewBtn = document.querySelector('#add-teacher-toggle');
                if (addNewBtn && addNewBtn.textContent.trim() === 'Add New') {
                    addNewBtn.textContent = 'הוסף חדש';
                    console.log('Translated Add New button');
                }
                
                // Translate form labels
                const translations = {
                    'First Name': 'שם פרטי',
                    'Last Name': 'שם משפחה',
                    'Email': 'אימייל',
                    'Phone': 'טלפון'
                };
                
                Object.keys(translations).forEach(function(english) {
                    const label = document.querySelector('label[for="' + english.toLowerCase().replace(' ', '_') + '"]');
                    if (label && label.textContent.trim() === english) {
                        label.textContent = translations[english];
                        console.log('Translated label: ' + english + ' -> ' + translations[english]);
                    }
                });
                
                // Translate form buttons
                const addTeacherBtn = document.querySelector('input[name="add_teacher"]');
                if (addTeacherBtn && addTeacherBtn.value === 'Add Teacher') {
                    addTeacherBtn.value = 'הוסף מורה';
                    console.log('Translated Add Teacher button');
                }
                
                const cancelBtn = document.querySelector('button#cancel-add-teacher');
                if (cancelBtn && cancelBtn.textContent.trim() === 'Cancel') {
                    cancelBtn.textContent = 'בטל';
                    console.log('Translated Cancel button');
                }
                
                // Translate postbox header
                const postboxHeader = document.querySelector('.postbox-header h2');
                if (postboxHeader && postboxHeader.textContent.trim() === 'Add Teacher') {
                    postboxHeader.textContent = 'הוספת מורה חדש';
                    console.log('Translated postbox header');
                }
                
                // Translate search box placeholder
                const searchBox = document.querySelector('input[name="s"]');
                if (searchBox) {
                    searchBox.placeholder = 'חפש מורים...';
                    console.log('Translated search placeholder');
                }
                
                // Translate table headers
                const tableHeaders = document.querySelectorAll('th');
                tableHeaders.forEach(function(th) {
                    const text = th.textContent.trim();
                    if (text === 'Name') th.textContent = 'שם';
                    else if (text === 'Email') th.textContent = 'אימייל';
                    else if (text === 'Phone') th.textContent = 'טלפון';
                    else if (text === 'Actions') th.textContent = 'פעולות';
                });
                
                console.log('Teacher Form Localization: Translation completed');
            }, 100);
        });
        </script>
        <?php
    }
}

// Initialize the fix
Teacher_Form_Localization_Fix::instance();
