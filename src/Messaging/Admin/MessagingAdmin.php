<?php
/**
 * Messaging System Admin Interface
 *
 * Provides an admin interface for configuring the toast messaging system.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging/Admin
 */

namespace Lilac\Messaging\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MessagingAdmin {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Option name for storing message settings
     */
    private $option_name = 'lilac_messaging_settings';

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
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugin page
        add_filter('plugin_action_links_hello-theme-child-master/style.css', array($this, 'add_settings_link'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'מערכת הודעות',
            'הודעות',
            'manage_options',
            'lilac-messaging',
            array($this, 'admin_page_display'),
            'dashicons-megaphone',
            30
        );
        
        // Add submenus
        add_submenu_page(
            'lilac-messaging',
            'הודעות ברכה',
            'הודעות ברכה',
            'manage_options',
            'lilac-messaging',
            array($this, 'admin_page_display')
        );
        
        add_submenu_page(
            'lilac-messaging',
            'הודעות הקשר',
            'הודעות הקשר',
            'manage_options',
            'lilac-messaging-contextual',
            array($this, 'contextual_page_display')
        );
        
        add_submenu_page(
            'lilac-messaging',
            'התראות התקדמות',
            'התראות התקדמות',
            'manage_options',
            'lilac-messaging-progress',
            array($this, 'progress_page_display')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('lilac_messaging_settings', $this->option_name);
        
        // Welcome Message Settings
        add_settings_section(
            'lilac_messaging_welcome_section',
            'הגדרות הודעת ברכה',
            array($this, 'welcome_section_callback'),
            'lilac-messaging'
        );
        
        add_settings_field(
            'welcome_message_enabled',
            'הפעל הודעת ברכה',
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_enabled',
                'field_name' => $this->option_name . '[welcome_message_enabled]'
            )
        );
        
        add_settings_field(
            'welcome_message_title',
            'כותרת הודעת הברכה',
            array($this, 'text_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_title',
                'field_name' => $this->option_name . '[welcome_message_title]'
            )
        );
        
        add_settings_field(
            'welcome_message_content',
            'תוכן הודעת הברכה',
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_content',
                'field_name' => $this->option_name . '[welcome_message_content]',
                'description' => 'תומך ב-HTML. השתמש בזה כדי לברך את המשתמשים באתר שלך.'
            )
        );
        
        add_settings_field(
            'welcome_message_type',
            'סוג הודעה',
            array($this, 'select_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_type',
                'field_name' => $this->option_name . '[welcome_message_type]',
                'options' => array(
                    'info' => 'מידע (כחול)',
                    'success' => 'הצלחה (ירוק)',
                    'warning' => 'אזהרה (צהוב)',
                    'error' => 'שגיאה (אדום)'
                )
            )
        );
        
        add_settings_field(
            'welcome_message_auto_close',
            'סגירה אוטומטית אחרי (אלפיות שנייה)',
            array($this, 'number_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_auto_close',
                'field_name' => $this->option_name . '[welcome_message_auto_close]',
                'description' => 'הגדר ל-0 כדי להשבית סגירה אוטומטית',
                'min' => 0,
                'max' => 60
            )
        );
        
        // Display Conditions
        add_settings_section(
            'lilac_messaging_display_section',
            'תנאי הצגה',
            array($this, 'display_conditions_section_callback'),
            'lilac-messaging'
        );
        
        add_settings_field(
            'welcome_display_homepage',
            'הצג בדף הבית',
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_display_homepage',
                'field_name' => $this->option_name . '[welcome_display_homepage]'
            )
        );
        
        add_settings_field(
            'welcome_display_once_per_session',
            'הצג פעם אחת למשתמש',
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_display_once_per_session',
                'field_name' => $this->option_name . '[welcome_display_once_per_session]'
            )
        );
        
        add_settings_field(
            'welcome_specific_urls',
            'הצג בכתובות ספציפיות',
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_specific_urls',
                'field_name' => $this->option_name . '[welcome_specific_urls]',
                'description' => 'הזן כתובת אחת בכל שורה. השאר ריק כדי להציג בכל הדפים.'
            )
        );
        
        // Lilac Bonus Coupon Section
        add_settings_section(
            'lilac_messaging_coupon_section',
            'מערכת קופון Lilac Bonus',
            array($this, 'coupon_section_callback'),
            'lilac-messaging'
        );
        
        add_settings_field(
            'enable_lilac_bonus',
            'הפעל מערכת קופון Lilac Bonus',
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_coupon_section',
            array(
                'label_for' => 'enable_lilac_bonus',
                'field_name' => $this->option_name . '[enable_lilac_bonus]'
            )
        );
        
        add_settings_field(
            'lilac_bonus_message_general',
            'הודעה כללית (למשתמשים שלא רכשו)',
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_coupon_section',
            array(
                'label_for' => 'lilac_bonus_message_general',
                'field_name' => $this->option_name . '[lilac_bonus_message_general]',
                'description' => 'הודעה המוצגת למשתמשים שלא רכשו תרגול - מזמינה לרכישה במחיר מיוחד'
            )
        );
        
        add_settings_field(
            'lilac_bonus_message_purchaser',
            'הודעה לרוכשי תרגול (עם קוד הנחה)',
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_coupon_section',
            array(
                'label_for' => 'lilac_bonus_message_purchaser',
                'field_name' => $this->option_name . '[lilac_bonus_message_purchaser]',
                'description' => 'הודעה המוצגת לרוכשי תרגול עם קוד הנחה קבוע לקורסים'
            )
        );
        
        add_settings_field(
            'lilac_bonus_coupon_code',
            'קוד הנחה לרוכשי תרגול',
            array($this, 'text_field_callback'),
            'lilac-messaging',
            'lilac_messaging_coupon_section',
            array(
                'label_for' => 'lilac_bonus_coupon_code',
                'field_name' => $this->option_name . '[lilac_bonus_coupon_code]',
                'description' => 'קוד הנחה קבוע שיוצג לרוכשי תרגול'
            )
        );
        
        add_settings_field(
            'lilac_bonus_exclude_courses',
            'אל תציג בדפי קורסים',
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_coupon_section',
            array(
                'label_for' => 'lilac_bonus_exclude_courses',
                'field_name' => $this->option_name . '[lilac_bonus_exclude_courses]'
            )
        );
    }

    /**
     * Section callbacks
     */
    public function welcome_section_callback() {
        echo '<p>הגדר את הודעת הברכה שתוצג למשתמשים.</p>';
    }
    
    public function display_conditions_section_callback() {
        echo '<p>שליטה היכן ומתי מוצגות הודעות.</p>';
    }
    
    public function coupon_section_callback() {
        echo '<p>הגדר את מערכת הקופון Lilac Bonus למשתמשים שונים.</p>';
    }

    /**
     * Field callbacks
     */
    public function text_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function textarea_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function checkbox_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $checked = isset($options[$field_id]) && $options[$field_id] ? 'checked' : '';
        
        echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1" ' . $checked . '>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function select_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '">';
        
        foreach ($args['options'] as $option_value => $option_label) {
            $selected = ($value === $option_value) ? 'selected' : '';
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : 0;
        $min = isset($args['min']) ? 'min="' . intval($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . intval($args['max']) . '"' : '';
        
        echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="small-text" ' . $min . ' ' . $max . '>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Display the admin page
     */
    public function admin_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show status message
        settings_errors('lilac_messaging_settings');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-messaging')); ?>" class="nav-tab nav-tab-active">הודעת ברכה</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-message-manager')); ?>" class="nav-tab">ניהול הודעות</a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lilac_messaging_settings');
                do_settings_sections('lilac-messaging');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display the contextual messages page
     */
    public function contextual_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Enqueue necessary scripts and styles
        wp_enqueue_style('toast-system-css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('toast-message-system');
        
        // Get current settings or defaults
        $settings = get_option($this->option_name . '_contextual', [
            'enable_quiz_exit_warning' => '1',
            'quiz_exit_message' => 'You have not completed this quiz yet. Are you sure you want to leave?',
            'quiz_incomplete_redirect' => '',
            'quiz_incomplete_message' => 'Please complete the quiz to continue.',
            'enable_quiz_completion_redirect' => '0',
            'quiz_completion_redirect' => '',
            'quiz_completion_delay' => '5',
            'quiz_completion_message' => 'Quiz completed successfully! Redirecting you in {seconds} seconds...',
        ]);
        
        // Handle form submission
        if (isset($_POST['lilac_contextual_nonce']) && wp_verify_nonce($_POST['lilac_contextual_nonce'], 'lilac_save_contextual_settings')) {
            $settings = [
                'enable_quiz_exit_warning' => isset($_POST['enable_quiz_exit_warning']) ? '1' : '0',
                'quiz_exit_message' => sanitize_text_field($_POST['quiz_exit_message'] ?? ''),
                'quiz_incomplete_redirect' => esc_url_raw($_POST['quiz_incomplete_redirect'] ?? ''),
                'quiz_incomplete_message' => sanitize_text_field($_POST['quiz_incomplete_message'] ?? ''),
                'enable_quiz_completion_redirect' => isset($_POST['enable_quiz_completion_redirect']) ? '1' : '0',
                'quiz_completion_redirect' => esc_url_raw($_POST['quiz_completion_redirect'] ?? ''),
                'quiz_completion_delay' => absint($_POST['quiz_completion_delay'] ?? 5),
                'quiz_completion_message' => sanitize_text_field($_POST['quiz_completion_message'] ?? ''),
            ];
            
            update_option($this->option_name . '_contextual', $settings);
            add_settings_error(
                'lilac_contextual_messages',
                'lilac_message',
                __('Settings Saved', 'hello-child'),
                'success'
            );
        }
        
        // Display any messages
        settings_errors('lilac_contextual_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>הגדר הודעות הקשר לחידונים ואלמנטים אינטראקטיביים אחרים.</p>
            </div>
            
            <div class="lilac-contextual-admin-container">
                <div class="lilac-contextual-admin-main">
                    <form method="post" action="">
                        <?php wp_nonce_field('lilac_save_contextual_settings', 'lilac_contextual_nonce'); ?>
                        
                        <h2>כוונת יציאה מחידון</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">הפעל התראת יציאה</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_quiz_exit_warning" value="1" <?php checked($settings['enable_quiz_exit_warning'], '1'); ?>>
                                        הצג אזהרה בעת יציאה מחידון שלא הושלם
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_exit_message">הודעת אזהרת יציאה</label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_exit_message" name="quiz_exit_message" value="<?php echo esc_attr($settings['quiz_exit_message']); ?>" class="regular-text">
                                    <p class="description">הודעה שתוצג כאשר המשתמש מנסה לעזוב חידון שלא הושלם</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_incomplete_redirect">הפניה עבור חידון לא הושלם</label>
                                </th>
                                <td>
                                    <input type="url" id="quiz_incomplete_redirect" name="quiz_incomplete_redirect" value="<?php echo esc_attr($settings['quiz_incomplete_redirect']); ?>" class="regular-text">
                                    <p class="description">לאן להפנות משתמשים שמנסים לגשת לתוכן מבלי להשלים חידון (השאר ריק כדי להציג הודעה במקום)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_incomplete_message">הודעת חידון לא הושלם</label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_incomplete_message" name="quiz_incomplete_message" value="<?php echo esc_attr($settings['quiz_incomplete_message']); ?>" class="regular-text">
                                    <p class="description">הודעה שתוצג כאשר המשתמש לא השלים חידון נדרש (אם לא הוגדרה הפניה)</p>
                                </td>
                            </tr>
                        </table>
                        
                        <h2>השלמת חידון</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">הפעל הפניה לאחר השלמה</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_quiz_completion_redirect" value="1" <?php checked($settings['enable_quiz_completion_redirect'], '1'); ?>>
                                        הפנה משתמשים לאחר השלמת חידון
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_redirect">כתובת הפניה לאחר השלמה</label>
                                </th>
                                <td>
                                    <input type="url" id="quiz_completion_redirect" name="quiz_completion_redirect" value="<?php echo esc_attr($settings['quiz_completion_redirect']); ?>" class="regular-text">
                                    <p class="description">לאן להפנות משתמשים לאחר שהם משלימים חידון</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_delay">עיכוב הפניה (שניות)</label>
                                </th>
                                <td>
                                    <input type="number" id="quiz_completion_delay" name="quiz_completion_delay" min="0" max="60" value="<?php echo esc_attr($settings['quiz_completion_delay']); ?>" class="small-text">
                                    <p class="description">כמה זמן להציג את הודעת ההשלמה לפני ההפניה (0 להפניה מיידית)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_message">הודעת השלמה</label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_completion_message" name="quiz_completion_message" value="<?php echo esc_attr($settings['quiz_completion_message']); ?>" class="regular-text">
                                    <p class="description">השתמש ב-{seconds} להצגת ספירה לאחור. לדוגמה: "מפנה תוך {seconds} שניות..."</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Settings', 'hello-child')); ?>
                    </form>
                </div>
                
                <div class="lilac-contextual-admin-sidebar">
                    <div class="lilac-contextual-demo-card">
                        <h3>הערות יישום</h3>
                        <p>כדי להשתמש בתכונות אלה, הוסף את הקוד הבא לתבנית החידון או לערכת העיצוב שלך:</p>
                        
                        <h4>1. כוונת יציאה מחידון</h4>
                        <pre><code>&lt;?php
// Add this to your quiz page template
if (function_exists('lilac_quiz_exit_intent')) {
    lilac_quiz_exit_intent();
}
?&gt;</code></pre>
                        
                        <h4>2. בדיקת השלמת חידון</h4>
                        <pre><code>&lt;?php
// Add this to content that requires quiz completion
if (function_exists('lilac_check_quiz_completion') && !lilac_check_quiz_completion($required_quiz_id)) {
    // Show message or redirect based on settings
    lilac_show_quiz_incomplete_message($required_quiz_id);
    return; // Stop further content rendering
}
?&gt;</code></pre>
                        
                        <h4>3. מטפל בהשלמת חידון</h4>
                        <pre><code>&lt;?php
// Add this when a quiz is completed
if (function_exists('lilac_handle_quiz_completion')) {
    lilac_handle_quiz_completion($quiz_id, $user_id);
}
?&gt;</code></pre>
                    </div>
                </div>
            </div>
            
            <style>
                .lilac-contextual-admin-container {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }
                .lilac-contextual-admin-main {
                    flex: 2;
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-contextual-admin-sidebar {
                    flex: 1;
                }
                .lilac-contextual-demo-card {
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-contextual-demo-card h3 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .lilac-contextual-demo-card h4 {
                    margin: 1.5em 0 0.5em;
                }
                .lilac-contextual-demo-card pre {
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 3px;
                    overflow-x: auto;
                }
                .lilac-contextual-demo-card code {
                    font-family: monospace;
                    font-size: 13px;
                    line-height: 1.5;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Display the progress notifications page
     */
    public function progress_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('toast-system-css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('toast-message-system');
        wp_enqueue_script('toast-session');
        // wp_enqueue_script('toast-test-timer'); // Removed to prevent unwanted test messages
        
        // Localize script for AJAX and translations
        wp_localize_script('toast-message-system', 'lilacToastAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_toast_admin_nonce'),
            'progressSettings' => $settings, // Pass settings to frontend
            'i18n' => [
                'testNotification' => __('Test notification sent!', 'hello-child'),
                'settingsSaved' => __('Settings saved successfully!', 'hello-child'),
                'errorSaving' => __('Error saving settings. Please try again.', 'hello-child')
            ]
        ]);
        
        // Get current settings or defaults
        $settings = get_option($this->option_name . '_progress', [
            'enable_session_warning' => '1',
            'session_timeout' => '30',
            'warning_before' => '5',
            'enable_test_timer' => '1',
            'test_duration' => '60',
            'warning_time' => '10',
            'critical_time' => '2'
        ]);
        
        // Handle form submission
        if (isset($_POST['lilac_progress_nonce']) && wp_verify_nonce($_POST['lilac_progress_nonce'], 'lilac_save_progress_settings')) {
            $settings = [
                'enable_session_warning' => isset($_POST['enable_session_warning']) ? '1' : '0',
                'session_timeout' => absint($_POST['session_timeout'] ?? 30),
                'warning_before' => absint($_POST['warning_before'] ?? 5),
                'enable_test_timer' => isset($_POST['enable_test_timer']) ? '1' : '0',
                'test_duration' => absint($_POST['test_duration'] ?? 60),
                'warning_time' => absint($_POST['warning_time'] ?? 10),
                'critical_time' => absint($_POST['critical_time'] ?? 2)
            ];
            
            update_option($this->option_name . '_progress', $settings);
            add_settings_error(
                'lilac_progress_messages',
                'lilac_message',
                __('Settings Saved', 'hello-child'),
                'success'
            );
        }
        
        // Display any messages
        settings_errors('lilac_progress_messages');
        ?>
thie            
            <div class="lilac-toast-admin-container">
                <div class="lilac-toast-admin-main">
                    <form method="post" action="">
                        <?php wp_nonce_field('lilac_save_progress_settings', 'lilac_progress_nonce'); ?>
                        
                        <h2><?php _e('ניהול  ', 'hello-child'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('הפעל אזהרות  ', 'hello-child'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_session_warning" value="1" <?php checked($settings['enable_session_warning'], '1'); ?>>
                                        הצג אזהרה כאשר עומד להסתיים
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                        <label for="session_timeout">פסק זמן (דקות)</label>
                                </th>
                                <td>
                                    <input type="number" id="session_timeout" name="session_timeout" min="1" max="240" value="<?php echo esc_attr($settings['session_timeout']); ?>" class="small-text">
                                        <p class="description">זמן חוסר פעולה לפני שה  יפוג</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="warning_before">הצג אזהרה לפני (דקות)</label>
                                </th>
                                <td>
                                    <input type="number" id="warning_before" name="warning_before" min="1" max="30" value="<?php echo esc_attr($settings['warning_before']); ?>" class="small-text">
                                    <p class="description">הצג אזהרה מספר דקות לפני שה  יפוג</p>
                                </td>
                            </tr>
                        </table>
                        
                        <h2>הגדרות טיימר מבחן</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">הפעל טיימר מבחן</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_test_timer" value="1" <?php checked($settings['enable_test_timer'], '1'); ?>>
                                        הצג טיימר ספירה לאחור למבחנים
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                        <label for="test_duration">משך המבחן (דקות)</label>
                                </th>
                                <td>
                                    <input type="number" id="test_duration" name="test_duration" min="1" max="240" value="<?php echo esc_attr($settings['test_duration']); ?>" class="small-text">
                                        <p class="description">משך ברירת מחדל של המבחן בדקות</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="warning_time">הצג אזהרה כאשר נותרו (דקות)</label>
                                </th>
                                <td>
                                    <input type="number" id="warning_time" name="warning_time" min="1" max="60" value="<?php echo esc_attr($settings['warning_time']); ?>" class="small-text">
                                    <p class="description">הצג אזהרה כאשר נותרו מספר דקות אלו</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="critical_time">הצג אזהרה קריטית כאשר נותרו (דקות)</label>
                                </th>
                                <td>
                                    <input type="number" id="critical_time" name="critical_time" min="0" max="10" value="<?php echo esc_attr($settings['critical_time']); ?>" class="small-text">
                                    <p class="description">הצג אזהרה קריטית כאשר נותרו מספר דקות אלו</p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Settings', 'hello-child')); ?>
                    </form>
                </div>
                
                <div class="lilac-toast-admin-sidebar">
                    <div class="lilac-toast-demo-card">
                        <h3>בדיקת התראות</h3>
                        <p>בדוק את סוגי ההתראות השונים:</p>
                        <p>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="success">הצלחה</button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="error">שגיאה</button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="warning">אזהרה</button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="info">מידע</button>
                        </p>
                        
                        <h3>בדיקת אזהרת  </h3>
                        <p>תצוגה מקדימה של אזהרת ה :</p>
                        <p>
                            <button type="button" class="button button-primary" id="lilac-test-session-warning">הצג אזהרת  </button>
                        </p>
                        
                        <h3>בדיקת טיימר מבחן</h3>
                        <p>תצוגה מקדימה של טיימר המבחן:</p>
                        <p>
                            <button type="button" class="button button-primary" id="lilac-test-timer">התחל טיימר מבחן</button>
                        </p>
                    </div>
                </div>
            </div>
            
            <style>
                .lilac-toast-admin-container {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }
                .lilac-toast-admin-main {
                    flex: 2;
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-toast-admin-sidebar {
                    flex: 1;
                }
                .lilac-toast-demo-card {
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-toast-demo-card h3 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .lilac-toast-demo-card p {
                    margin: 1em 0;
                }
                .lilac-test-toast {
                    margin-right: 5px;
                    margin-bottom: 5px;
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                // Test toast notifications
                $('.lilac-test-toast').on('click', function() {
                    const type = $(this).data('type');
                    const titles = {
                        'success': 'הצלחה!',
                        'error': 'שגיאה!',
                        'warning': 'אזהרה!',
                        'info': 'מידע!'
                    };
                    const messages = {
                        'success': 'זוהי דוגמה להתראה על הצלחה.',
                        'error': 'זוהי דוגמה להתראת שגיאה.',
                        'warning': 'זוהי דוגמה להתראת אזהרה.',
                        'info': 'זוהי דוגמה להתראת מידע.'
                    };
                    const title = titles[type] || type.charAt(0).toUpperCase() + type.slice(1) + '!';
                    const message = messages[type] || 'זוהי דוגמה להתראה מסוג ' + type + '.';
                    
                    switch(type) {
                        case 'success':
                            window.LilacToast.success(message, title);
                            break;
                        case 'error':
                            window.LilacToast.error(message, title);
                            break;
                        case 'warning':
                            window.LilacToast.warning(message, title);
                            break;
                        case 'info':
                        default:
                            window.LilacToast.info(message, title);
                    }
                });
                
                // Test session warning
                $('#lilac-test-session-warning').on('click', function() {
                    if (typeof window.LilacToast.session !== 'undefined') {
                        // Show a warning that would normally appear 5 minutes before session timeout
                        window.LilacToast.session.showWarning();
                    } else {
                        alert('ניהול   לא נטען. ודא ש-session-toast.js מוטען כראוי.');
                    }
                });
                
                // Test timer functionality removed to prevent unwanted messages
                // Only production quiz timer (quiz-timer.js) should run based on admin settings
            });
            </script>
        </div>
        <?php
    }

    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=lilac-messaging">' . __('Settings', 'hello-child') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the class
function lilac_messaging_admin() {
    return MessagingAdmin::get_instance();
}
lilac_messaging_admin();
