<?php
/**
 * Teacher Login Form Template
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
        $error_message = isset($_SESSION['lilac_login_error']) ? $_SESSION['lilac_login_error'] : __('שם משתמש או סיסמה שגויים. נסה שנית.', 'hello-child');
        echo '<div class="login-error">' . esc_html($error_message) . '</div>';
        // Clear the error from session
        unset($_SESSION['lilac_login_error']);
    }
    ?>
    
    <form method="post" class="lilac-login-form">
        <div class="form-group">
            <label for="lilac-teacher-username"><?php _e('מספר טלפון:', 'hello-child'); ?></label>
            <input type="text" name="log" id="lilac-teacher-username" required 
                placeholder="<?php echo esc_attr__('הזן מספר טלפון', 'hello-child'); ?>" />
        </div>
        
        <div class="form-group">
            <label for="lilac-teacher-password"><?php _e('סיסמה:', 'hello-child'); ?></label>
            <input type="password" name="pwd" id="lilac-teacher-password" required 
                placeholder="<?php echo esc_attr__('הזן סיסמה', 'hello-child'); ?>" />
        </div>
        
        <div class="form-group remember-me">
            <input type="checkbox" name="rememberme" id="lilac-teacher-rememberme" value="forever" />
            <label for="lilac-teacher-rememberme"><?php _e('זכור אותי', 'hello-child'); ?></label>
        </div>
        
        <input type="hidden" name="role_type" value="teacher" />
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
            // Information for teachers on how to get access
            echo '<span class="teacher-info">';
            echo __('למורים בלבד. לקבלת גישה צור קשר עם המנהל.', 'hello-child');
            echo '</span>';
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
        background-color: #27ae60;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .lilac-login-button:hover {
        background-color: #219653;
    }

    .links-group {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .links-group a, .links-group .teacher-info {
        color: #3498db;
        text-decoration: none;
        margin-bottom: 5px;
    }

    .links-group a:hover {
        text-decoration: underline;
    }

    .teacher-info {
        color: #7f8c8d;
        font-style: italic;
    }

    .login-error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }
</style>
