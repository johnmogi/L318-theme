<?php
/**
 * Ultimate Member Integration
 *
 * Integrates the registration codes system with Ultimate Member forms
 * to provide role-based registration flows.
 *
 * @package Hello_Child_Theme
 * @subpackage Integrations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CCR_Ultimate_Member_Integration {

    /**
     * User type mappings for forms and roles
     */
    private $user_type_mappings = array(
        'student_education' => array(
            'name' => 'תלמיד חינוך תעבורתי',
            'register_form_id' => 1128,
            'login_form_id' => 1125,
            'membership_level' => 3, // תלמיד כיתה י'
            'requires_code' => true
        ),
        'student_independent' => array(
            'name' => 'תלמיד עצמאי',
            'register_form_id' => 1124,
            'login_form_id' => 1125,
            'membership_level' => 2, // תלמיד עצמאי
            'requires_code' => false
        ),
        'teacher' => array(
            'name' => 'מורה / רכז בית ספר',
            'register_form_id' => null, // To be created later
            'login_form_id' => 1125,
            'membership_level' => 4, // מורה
            'requires_code' => true  // Teachers need admin-provided codes
        )
    );
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Initialize session for code verification (with priority to run early)
        add_action('init', array($this, 'init_session'), 1);
        
        // Register shortcodes immediately - don't wait for init hook
        $this->register_shortcodes();
        
        // Hook into Ultimate Member
        add_action('um_submit_form_errors_hook__registration', array($this, 'validate_registration'), 10, 1);
        add_action('um_registration_complete', array($this, 'process_registration_complete'), 10, 2);
        
        // Add hidden field to UM form
        add_filter('um_get_form_fields', array($this, 'add_hidden_code_field'), 100, 2);
        
        // Add debug notice in admin to verify integration is loaded
        add_action('admin_notices', array($this, 'integration_loaded_notice'));
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Remove previous shortcode registrations if any
        remove_shortcode('verify_student_code');
        remove_shortcode('student_independent_registration');
        remove_shortcode('ccr_test');
        
        // Add our shortcodes
        add_shortcode('verify_student_code', array($this, 'verify_student_code_shortcode'));
        add_shortcode('student_independent_registration', array($this, 'student_independent_registration_shortcode'));
        add_shortcode('ccr_test', array($this, 'test_shortcode'));
    }
    
    /**
     * Initialize session if not already started
     */
    public function init_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Show notice that integration is loaded (admin only)
     */
    public function integration_loaded_notice() {
        // Only show on our plugin pages
        if (!isset($_GET['page']) || !in_array($_GET['page'], array('registration-codes', 'class-management'))) {
            return;
        }
        
        $test_url = add_query_arg('ccr_test_page', '1', site_url('/testlogin/'));
        
        ?>
        <div class="notice notice-info is-dismissible">
            <p><?php _e('Ultimate Member Integration is active. Shortcodes [verify_student_code] and [student_independent_registration] are available.', 'hello-child'); ?></p>
            <p><?php printf(__('Test shortcodes here: <a href="%s" target="_blank">Test Page</a>', 'hello-child'), esc_url($test_url)); ?></p>
        </div>
        <?php
    }
    
    /**
     * Shortcode for student code verification
     * 
     * @return string HTML output
     */
    public function verify_student_code_shortcode() {
        // Start output buffering to capture all output
        ob_start();
        
        // If code is in session, show success message
        if (isset($_SESSION['verified_registration_code']) && !empty($_SESSION['verified_registration_code'])) {
            $code = $_SESSION['verified_registration_code'];
            echo '<div class="ccr-code-verified">';
            echo '<p class="success">' . sprintf(__('קוד הרשמה %s אומת בהצלחה', 'hello-child'), '<strong>' . esc_html($code) . '</strong>') . '</p>';
            echo '<p>' . __('אנא מלא את טופס ההרשמה מטה', 'hello-child') . '</p>';
            echo '</div>';
            // No early return - let the script handle visibility
        }
        // Process form submission
        else if (isset($_SERVER['REQUEST_METHOD']) && "POST" === $_SERVER['REQUEST_METHOD'] && !empty($_POST['access_code_action'])) {
            $code = isset($_POST['access_code']) ? sanitize_text_field($_POST['access_code']) : '';
            
            // Check if code validate function exists
            if (!function_exists('ccr_validate_code')) {
                echo '<div class="error">';
                echo '<p>' . __('שגיאת מערכת: פונקציית אימות קוד לא נמצאה', 'hello-child') . '</p>';
                echo '</div>';
                return ob_get_clean();
            }
            
            // Validate the code using our database system
            $validation = ccr_validate_code($code);
            
            if ($validation['valid']) {
                // Store in session and show success
                $this->store_verified_code($code, $validation);
                
                echo '<div class="ccr-code-verified">';
                echo '<p class="success">' . sprintf(__('קוד הרשמה %s אומת בהצלחה', 'hello-child'), '<strong>' . esc_html($code) . '</strong>') . '</p>';
                echo '<p>' . __('אנא מלא את טופס ההרשמה מטה', 'hello-child') . '</p>';
                echo '</div>';
            } else {
                // Show error message
                echo '<div class="error">';
                echo '<p>' . esc_html($validation['message']) . '</p>';
                echo '</div>';
                $this->show_code_form();
            }
        } else {
            // Show code form
            $this->show_code_form();
        }
        
        // Add script to handle form visibility - using setTimeout to ensure DOM is fully loaded
        echo '<script>
            jQuery(document).ready(function($) {
                console.log("Code verification script running");
                
                // Use setTimeout to ensure UM forms are fully loaded
                setTimeout(function() {
                    if ($(".ccr-code-verified").length === 0) {
                        console.log("Hiding UM form - no verified code");
                        $(".um-register").hide();
                    } else {
                        console.log("Showing UM form - code verified");
                        $(".um-register").show();
                    }
                }, 500);
            });
        </script>';
        
        // Get the buffered content and return it
        $output = ob_get_clean();
        return $output;
    }
    
    /**
     * Shortcode for independent student registration
     * 
     * @return string HTML output
     */
    public function student_independent_registration_shortcode() {
        // Check if UM shortcode exists
        if (!shortcode_exists('ultimatemember')) {
            return '<p class="error">' . __('שגיאה: פלאגין Ultimate Member לא פעיל', 'hello-child') . '</p>';
        }
        
        // Get form ID from mappings
        $form_id = $this->user_type_mappings['student_independent']['register_form_id'];
        
        // Make sure we have a valid form ID
        if (empty($form_id)) {
            return '<p class="error">' . __('שגיאה: מזהה טופס הרשמה לא מוגדר', 'hello-child') . '</p>';
        }
        
        // Return the Ultimate Member shortcode
        return do_shortcode('[ultimatemember form_id="'.$form_id.'"]');
    }
    
    /**
     * Simple test shortcode to verify shortcode system works
     */
    public function test_shortcode($atts = [], $content = null) {
        $output = '<div style="padding: 10px; background-color: #e0f7fa; border: 1px solid #00acc1;">';
        $output .= '<p>Test shortcode is working!</p>';
        $output .= '<p>Time: ' . current_time('mysql') . '</p>';
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Display the access code input form
     */
    private function show_code_form() {
        echo '<form method="post" class="ccr-code-form">';
        echo '<p><label>' . __('הקלד את קוד הגישה:', 'hello-child') . '</label><br>';
        echo '<input type="text" name="access_code" required></p>';
        echo '<input type="hidden" name="access_code_action" value="1">';
        echo '<p><button type="submit" class="button">' . __('שלח', 'hello-child') . '</button></p>';
        echo '</form>';
    }
    
    /**
     * Store verified code in session
     */
    private function store_verified_code($code, $validation) {
        $_SESSION['verified_registration_code'] = $code;
        $_SESSION['verified_code_data'] = $validation['code'];
    }
    
    /**
     * Add hidden code field to UM form
     */
    public function add_hidden_code_field($fields, $form_id) {
        // Only add to student education form
        if ($form_id == $this->user_type_mappings['student_education']['register_form_id'] && 
            isset($_SESSION['verified_registration_code'])) {
            
            $code = $_SESSION['verified_registration_code'];
            
            $fields['registration_code'] = array(
                'type' => 'hidden',
                'label' => __('קוד הרשמה', 'hello-child'),
                'value' => $code
            );
        }
        
        return $fields;
    }
    
    /**
     * Validate registration submission
     */
    public function validate_registration($args) {
        $form_id = $args['form_id'];
        
        // For student education form - verify code is valid
        if ($form_id == $this->user_type_mappings['student_education']['register_form_id']) {
            $code = isset($_SESSION['verified_registration_code']) ? $_SESSION['verified_registration_code'] : '';
            if (empty($code)) {
                if (function_exists('UM')) {
                    $um = UM();
                    $um->form()->add_error('registration_code', __('קוד הרשמה לא נמצא', 'hello-child'));
                }
                return;
            }
            
            $validation = ccr_validate_code($code);
            if (!$validation['valid']) {
                if (function_exists('UM')) {
                    $um = UM();
                    $um->form()->add_error('registration_code', $validation['message']);
                }
            }
        }
    }
    
    /**
     * Process registration completion
     */
    public function process_registration_complete($user_id, $args) {
        $form_id = $args['form_id'];
        
        // Check if it's a student education registration
        if ($form_id == $this->user_type_mappings['student_education']['register_form_id']) {
            // Mark code as used
            $code = isset($_SESSION['verified_registration_code']) ? $_SESSION['verified_registration_code'] : '';
            if (!empty($code) && function_exists('ccr_mark_code_used')) {
                ccr_mark_code_used($code, $user_id);
                // Clear session
                unset($_SESSION['verified_registration_code']);
                unset($_SESSION['verified_code_data']);
            }
            
            // Assign membership level if PMPro exists
            if (function_exists('pmpro_changeMembershipLevel')) {
                pmpro_changeMembershipLevel($this->user_type_mappings['student_education']['membership_level'], $user_id);
            }
        }
        // Check if it's an independent student registration
        else if ($form_id == $this->user_type_mappings['student_independent']['register_form_id']) {
            // Assign membership level if PMPro exists
            if (function_exists('pmpro_changeMembershipLevel')) {
                pmpro_changeMembershipLevel($this->user_type_mappings['student_independent']['membership_level'], $user_id);
            }
        }
    }
}

// Initialize the integration
add_action('init', function() {
    global $ccr_um_integration;
    $ccr_um_integration = new CCR_Ultimate_Member_Integration();
}, 5); // Higher priority to ensure it loads early

// Add debug information to help troubleshoot shortcodes
add_action('wp_footer', function() {
    if (current_user_can('manage_options') || isset($_GET['debug'])) {
        echo '<!-- Ultimate Member Integration Debug Info -->';
        echo '<!-- Shortcodes registered: verify_student_code, student_independent_registration, ccr_test -->';
        echo '<!-- UM Plugin Active: ' . (class_exists('UM') ? 'Yes' : 'No') . ' -->';
        echo '<!-- PMPro Plugin Active: ' . (function_exists('pmpro_changeMembershipLevel') ? 'Yes' : 'No') . ' -->';
        echo '<!-- Session Active: ' . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . ' -->';
        
        global $shortcode_tags;
        $our_shortcodes = ['verify_student_code', 'student_independent_registration', 'ccr_test'];
        $shortcode_status = [];
        
        foreach ($our_shortcodes as $sc) {
            $shortcode_status[] = $sc . ': ' . (array_key_exists($sc, $shortcode_tags) ? 'Registered' : 'Not Registered');
        }
        
        echo '<!-- Our Shortcodes Status: ' . implode(', ', $shortcode_status) . ' -->';
        
        // Test executing a simple shortcode as a sanity check
        $test_output = do_shortcode('[ccr_test]');
        echo '<!-- Test Shortcode Length: ' . strlen($test_output) . ' characters -->';
    }
});

// Add clean test page with our shortcodes for debugging
add_action('wp_footer', function() {
    if (isset($_GET['ccr_test_page'])) {
        echo '<style>.ccr-test-area {background:#fff; padding:20px; margin:20px; border:2px solid #ddd;}</style>';
        echo '<div class="ccr-test-area">';
        echo '<h2>CCR Shortcode Test</h2>';
        
        echo '<h3>Test Shortcode</h3>';
        echo do_shortcode('[ccr_test]');
        
        echo '<h3>Verify Student Code Shortcode</h3>';
        echo do_shortcode('[verify_student_code]');
        
        echo '<h3>Student Independent Registration</h3>';
        echo do_shortcode('[student_independent_registration]');
        
        echo '</div>';
    }
});
