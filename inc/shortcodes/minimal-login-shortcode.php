<?php
/**
 * Minimal Login Shortcode
 *
 * Implements a minimal login form shortcode.
 *
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the minimal login shortcode.
 */
function lilac_register_minimal_login_shortcode() {
    add_shortcode('lilac_minimal_login', 'lilac_minimal_login_shortcode');
}
// Register this both at init and directly to ensure it works
add_action('init', 'lilac_register_minimal_login_shortcode');
// Direct registration as fallback
add_shortcode('lilac_minimal_login', 'lilac_minimal_login_shortcode');

/**
 * Shortcode callback function for the minimal login form.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the minimal login form.
 */
function lilac_minimal_login_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(
        array(
            'title' => 'התחברות', // Default title
            'login_button_text' => 'התחבר',
            'show_remember_me' => 'yes',
            'show_forgot_password' => 'yes'
        ),
        $atts,
        'lilac_minimal_login'
    );
    
    // Start output buffering
    ob_start();
    
    // Display error message if login failed
    $login_error = '';
    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        $error_message = isset($_SESSION['lilac_login_error']) ? $_SESSION['lilac_login_error'] : __('שם משתמש או סיסמה שגויים.', 'hello-child');
        $login_error = '<div class="login-error">' . esc_html($error_message) . '</div>';
        // Clear the error from session
        unset($_SESSION['lilac_login_error']);
    }
    
    // Display success message after registration
    $success_message = '';
    if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
        $success_message = '<div class="login-success">' . __('הרישום הושלם בהצלחה! כעת ניתן להתחבר.', 'hello-child') . '</div>';
    }
    
    ?>
    <div class="lilac-minimal-login-container">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        
        <?php echo $login_error; ?>
        <?php echo $success_message; ?>
        
        <form method="post" class="lilac-minimal-login-form">
            <div class="form-group">
                <input type="text" name="log" id="lilac-minimal-username" required 
                    placeholder="<?php echo esc_attr__('טלפון / אימייל', 'hello-child'); ?>" />
            </div>
            
            <div class="form-group">
                <input type="password" name="pwd" id="lilac-minimal-password" required 
                    placeholder="<?php echo esc_attr__('סיסמה', 'hello-child'); ?>" />
            </div>
            
            <?php if ($atts['show_remember_me'] === 'yes') : ?>
            <div class="form-group remember-me">
                <input type="checkbox" name="rememberme" id="lilac-minimal-rememberme" value="forever" />
                <label for="lilac-minimal-rememberme"><?php _e('זכור אותי', 'hello-child'); ?></label>
            </div>
            <?php endif; ?>
            
            <!-- Hidden field for login processing -->
            <input type="hidden" name="lilac_login_action" value="1" />
            <!-- Hidden field to ensure teachers are redirected to admin dashboard -->
            <input type="hidden" name="teacher_redirect" value="admin" />
            <?php wp_nonce_field('lilac_login_action', 'lilac_login_nonce'); ?>
            
            <div class="form-group submit-group">
                <button type="submit" class="lilac-minimal-login-button">
                    <?php echo esc_html($atts['login_button_text']); ?>
                </button>
            </div>
            
            <?php if ($atts['show_forgot_password'] === 'yes') : ?>
            <div class="form-group links-group">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password">
                    <?php _e('שכחת סיסמה?', 'hello-child'); ?>
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <style>
        .lilac-minimal-login-container {
            max-width: 320px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            direction: rtl;
        }

        .lilac-minimal-login-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            text-align: center;
            font-size: 18px;
            color: #2c3e50;
        }

        .lilac-minimal-login-form .form-group {
            margin-bottom: 15px;
        }

        .lilac-minimal-login-form input[type="text"],
        .lilac-minimal-login-form input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .lilac-minimal-login-form .remember-me {
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .lilac-minimal-login-form .remember-me input {
            margin-left: 8px;
        }

        .lilac-minimal-login-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .lilac-minimal-login-button:hover {
            background-color: #2980b9;
        }

        .links-group {
            text-align: center;
            margin-top: 10px;
        }

        .links-group a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .links-group a:hover {
            text-decoration: underline;
        }

        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }

        .login-success {
            background-color: #d4edda;
            color: #155724;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }
    </style>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
