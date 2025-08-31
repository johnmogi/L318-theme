<?php
/**
 * Login Page Shortcode
 *
 * Implements a standalone shortcode for displaying the unified login page.
 *
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the login page shortcode.
 */
function lilac_register_login_page_shortcode() {
    add_shortcode('lilac_login_page', 'lilac_login_page_shortcode');
}
add_action('init', 'lilac_register_login_page_shortcode');

/**
 * Shortcode callback function for the login page.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output for the login page.
 */
function lilac_login_page_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(
        array(
            'show_title' => 'yes', // Whether to show the page title
            'title' => 'התחברות לאתר', // Default title if no page title is available
            'description' => '', // Custom description
            'login_form_title' => 'התחברות לאתר',
            'login_form_description' => 'התחבר באמצעות מספר הטלפון/אימייל והסיסמה שלך',
            'registration_title' => 'לא רשום עדיין?',
            'registration_description' => 'בחר את סוג החשבון המתאים לך:',
        ),
        $atts,
        'lilac_login_page'
    );
    
    // Start output buffering
    ob_start();
    
    // Get URLs for registration pages
    $school_register_url = get_permalink(get_option('lilac_school_register_page', 0)) ?: '#';
    $private_register_url = get_permalink(get_option('lilac_private_register_page', 0)) ?: '#';
    
    // Check if access code form should be displayed
    $show_code_form = get_option('lilac_enable_access_code', false);
    ?>
    <div class="lilac-login-page">
        <?php if ($atts['show_title'] === 'yes') : ?>
            <div class="lilac-page-header">
                <h1><?php echo esc_html($atts['title']); ?></h1>
                <?php if (!empty($atts['description'])) : ?>
                    <div class="lilac-page-content">
                        <?php echo wp_kses_post($atts['description']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="lilac-login-forms-container">
            <div class="lilac-login-forms-row">
                <!-- Access Code Form (if enabled) -->
                <?php if ($show_code_form) : ?>
                    <div class="lilac-access-code-form">
                        <h2>כניסה עם קוד גישה</h2>
                        <p class="description">אם קיבלת קוד גישה מיוחד, הזן אותו כאן</p>
                        <?php echo do_shortcode('[code_registration]'); ?>
                    </div>
                    <hr class="lilac-divider">
                <?php endif; ?>
                
                <!-- Unified Login Form -->
                <div class="lilac-login-form-col">
                    <div class="lilac-login-form-wrapper">
                        <?php echo do_shortcode(sprintf(
                            '[lilac_login form_title="%s" form_description="%s"]',
                            esc_attr($atts['login_form_title']),
                            esc_attr($atts['login_form_description'])
                        )); ?>
                    </div>
                </div>
                
                <!-- Registration Links -->
                <div class="lilac-login-form-col">
                    <div class="lilac-registration-links-wrapper">
                        <h2><?php echo esc_html($atts['registration_title']); ?></h2>
                        <!-- <p class="description"><php echo esc_html($atts['registration_description']); ?></p> -->
                        
                        <div class="registration-links">
                            <!-- <div class="reg-link school">
                                    <a href="<?php echo+ esc_url($school_register_url); ?>">
                                    הרשמה לחינוך התעבורתי
                                    <span>לתלמידי כיתה י'</span>
                                </a>
                            </div> -->
                            <div class="reg-link private">
                                <a href="<?php echo esc_url($private_register_url); ?>">
                                    הרשמה ללקוחות פרטיים
                                    <span>לכל המעוניינים</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .lilac-login-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .lilac-page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .lilac-page-header h1 {
            margin-bottom: 20px;
        }
        
        .lilac-page-content {
            margin-bottom: 30px;
        }
        
        .lilac-login-forms-container {
            display: flex;
            justify-content: center;
            direction: rtl;
        }
        
        .lilac-login-forms-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            width: 100%;
        }
        
        .lilac-login-form-col {
            flex: 0 0 calc(50% - 15px);
            max-width: calc(50% - 15px);
        }
        
        .lilac-access-code-form {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .lilac-divider {
            width: 100%;
            border: 0;
            border-top: 1px solid #e9ecef;
            margin: 30px 0;
        }
        
        .lilac-registration-links-wrapper {
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .lilac-registration-links-wrapper h2 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .registration-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .registration-links .reg-link {
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .registration-links .reg-link.school {
            background-color: #e3f2fd;
        }
        
        .registration-links .reg-link.private {
            background-color: #e8f5e9;
        }
        
        .registration-links .reg-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .registration-links .reg-link a {
            display: block;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 16px;
        }
        
        .registration-links .reg-link span {
            display: block;
            color: #7f8c8d;
            font-size: 14px;
            font-weight: normal;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .lilac-login-forms-row {
                flex-direction: column;
            }
            
            .lilac-login-form-col {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 30px;
            }
        }
    </style>
    <?php
    // Return the buffered content
    return ob_get_clean();
}
