<?php
/**
 * Teacher Lost Password Form Template
 *
 * @package Hello_Child_Theme
 * @subpackage Login/Templates
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="lilac-lostpassword-container <?php echo esc_attr($atts['form_class'] ?? ''); ?>">
    <h2><?php echo esc_html($atts['form_title'] ?? __('איפוס סיסמה למורים', 'hello-child')); ?></h2>
    
    <?php if (isset($atts['form_description'])) : ?>
        <p class="description"><?php echo esc_html($atts['form_description']); ?></p>
    <?php endif; ?>
    
    <?php 
    // Display errors or success messages
    if (!empty($message)) : 
        $message_class = strpos($message, 'error') !== false ? 'error' : 'success';
    ?>
        <div class="lilac-message <?php echo esc_attr($message_class); ?>">
            <?php echo wp_kses_post($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="lilac-lostpassword-form">
        <div class="form-group">
            <label for="user_login"><?php _e('אימייל או מספר טלפון:', 'hello-child'); ?></label>
            <input type="text" name="user_login" id="user_login" required 
                placeholder="<?php echo esc_attr__('הזן אימייל או מספר טלפון', 'hello-child'); ?>" />
        </div>
        
        <input type="hidden" name="lilac_lostpassword_action" value="1" />
        <?php wp_nonce_field('lilac_lostpassword_action', 'lilac_lostpassword_nonce'); ?>
        
        <div class="form-group submit-group">
            <button type="submit" class="lilac-button">
                <?php echo esc_html($atts['button_text'] ?? __('אפס סיסמה', 'hello-child')); ?>
            </button>
        </div>
        
        <div class="form-group links-group">
            <a href="<?php echo esc_url($atts['login_url'] ?? wp_login_url()); ?>" class="back-to-login">
                <?php _e('חזור להתחברות', 'hello-child'); ?>
            </a>
        </div>
    </form>
</div>

<style>
    .lilac-lostpassword-container {
        max-width: 500px;
        margin: 30px auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        direction: rtl;
        text-align: right;
    }

    .lilac-lostpassword-container h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
        text-align: center;
    }

    .lilac-lostpassword-container .description {
        margin-bottom: 25px;
        color: #7f8c8d;
        text-align: center;
    }

    .lilac-lostpassword-form .form-group {
        margin-bottom: 20px;
    }

    .lilac-lostpassword-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }

    .lilac-lostpassword-form input[type="text"],
    .lilac-lostpassword-form input[type="email"] {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    .lilac-lostpassword-form input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .lilac-lostpassword-form .submit-group {
        margin-top: 30px;
    }

    .lilac-lostpassword-form .lilac-button {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: #3498db;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .lilac-lostpassword-form .lilac-button:hover {
        background-color: #2980b9;
    }

    .lilac-lostpassword-form .links-group {
        margin-top: 20px;
        text-align: center;
    }

    .lilac-lostpassword-form .back-to-login {
        color: #7f8c8d;
        text-decoration: none;
        transition: color 0.3s;
    }

    .lilac-lostpassword-form .back-to-login:hover {
        color: #3498db;
        text-decoration: underline;
    }

    .lilac-message {
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-size: 14px;
    }

    .lilac-message.error {
        background-color: #fde8e8;
        color: #c53030;
        border: 1px solid #feb2b2;
    }

    .lilac-message.success {
        background-color: #e6fffa;
        color: #2c7a7b;
        border: 1px solid #81e6d9;
    }
</style>
