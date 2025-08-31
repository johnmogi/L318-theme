<?php
/**
 * Unified Login Form Template
 *
 * @package Hello_Child_Theme
 * @subpackage Login/Templates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lilac-login-container <?php echo esc_attr($atts['form_class']); ?>">
    <h2><?php echo esc_html($atts['form_title']); ?></h2>
    <p class="description"><?php echo esc_html($atts['form_description']); ?></p>
    
    <?php 
    // Display error message if login failed
    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        $default_message = __('שם משתמש או סיסמה שגויים. נסה שנית.', 'hello-child');
        $error_message = isset($_SESSION['lilac_login_error']) ? $_SESSION['lilac_login_error'] : $default_message;
        $allowed_html = array(
            'strong' => array(),
            'em'     => array(),
            'a'      => array('href' => array(), 'title' => array()),
            'br'     => array(),
            'span'   => array('class' => array())
        );
        echo '<div class="login-error">' . wp_kses($error_message, $allowed_html) . '</div>';
        // Clear the error from session
        unset($_SESSION['lilac_login_error']);
    }
    
    // Display success message after registration
    if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
        echo '<div class="login-success">' . __('הרישום הושלם בהצלחה! כעת ניתן להתחבר.', 'hello-child') . '</div>';
    }
    ?>
    
    <form method="post" class="lilac-login-form">
        <div class="form-group">
            <label for="lilac-username"><?php _e('מספר טלפון / אימייל:', 'hello-child'); ?></label>
            <input type="text" name="log" id="lilac-username" required 
                placeholder="<?php echo esc_attr__('הזן מספר טלפון או כתובת מייל', 'hello-child'); ?>" />
        </div>
        
        <div class="form-group">
            <label for="lilac-password"><?php _e('סיסמה:', 'hello-child'); ?></label>
            <input type="password" name="pwd" id="lilac-password" required 
                placeholder="<?php echo esc_attr__('הזן סיסמה', 'hello-child'); ?>" />
        </div>
        
        <div class="form-group remember-me">
            <input type="checkbox" name="rememberme" id="lilac-rememberme" value="forever" />
            <label for="lilac-rememberme"><?php _e('זכור אותי', 'hello-child'); ?></label>
        </div>
        
        <input type="hidden" name="lilac_login_action" value="1" />
        <?php wp_nonce_field('lilac_login_action', 'lilac_login_nonce'); ?>
        
        <div class="form-group submit-group">
            <button type="submit" class="lilac-login-button">
                <?php echo esc_html($atts['login_button_text']); ?>
            </button>
        </div>
        
        <div class="form-group links-group">
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password">
                <?php _e('שכחת סיסמה?', 'hello-child'); ?>
            </a>
            
            <?php 
            // Only show registration links if registration is open
            if (get_option('users_can_register')) {
                $registration_url = get_permalink(get_option('lilac_registration_page', 0)) ?: wp_registration_url();
                echo '<a href="' . esc_url($registration_url) . '" class="register-link">';
                echo '</a>';
            }
            ?>
        </div>
    </form>
</div>
<style>
    .lilac-login-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        direction: rtl;
        text-align: right;
    }

    .lilac-login-container h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
        text-align: center;
    }

    .lilac-login-container .description {
        margin-bottom: 25px;
        color: #7f8c8d;
        text-align: center;
    }

    .lilac-login-form .form-group {
        margin-bottom: 20px;
    }

    .lilac-login-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .lilac-login-form input[type="text"],
    .lilac-login-form input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .lilac-login-form .remember-me {
        display: flex;
        align-items: center;
    }

    .lilac-login-form .remember-me input {
        margin-left: 8px;
    }

    .lilac-login-form .remember-me label {
        margin: 0;
        font-weight: normal;
    }

    .lilac-login-button {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .lilac-login-button:hover {
        background-color: #2980b9;
    }

    .links-group {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
    }

    .links-group a {
        color: #3498db;
        text-decoration: none;
    }

    .links-group a:hover {
        text-decoration: underline;
    }

    .login-error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }

    .login-success {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        border: 1px solid #c3e6cb;
    }
    
    /* Two-column registration links */
    .registration-links {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .registration-links .reg-link {
        flex: 0 0 48%;
        text-align: center;
        background-color: #f8f9fa;
        padding: 15px 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .registration-links .reg-link:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
    }
    
    .registration-links .reg-link a {
        text-decoration: none;
        color: #2c3e50;
        font-weight: 600;
        display: block;
    }
    
    .registration-links .reg-link span {
        display: block;
        font-size: 0.9em;
        color: #6c757d;
        margin-top: 5px;
    }
    
    @media (max-width: 576px) {
        .registration-links .reg-link {
            flex: 0 0 100%;
        }
    }
</style>
