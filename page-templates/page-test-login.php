<?php
/**
 * Template Name: Test Login Page
 *
 * A test page template that displays the unified login form.
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

get_header();

// Get the content for the page
the_post();
?>

<div class="lilac-test-login-page">
    <div class="lilac-page-header">
        <h1><?php the_title(); ?></h1>
        <div class="lilac-page-content">
            <?php the_content(); ?>
        </div>
    </div>
    
    <div class="lilac-login-forms-container">
        <div class="lilac-login-forms-row">
            <!-- Access Code Form (if enabled) -->
            <?php 
            $show_code_form = get_option('lilac_enable_access_code', false);
            if ($show_code_form) {
                ?>
                <div class="lilac-access-code-form">
                    <h2>כניסה עם קוד גישה</h2>
                    <p class="description">אם קיבלת קוד גישה מיוחד, הזן אותו כאן</p>
                    <?php echo do_shortcode('[code_registration]'); ?>
                </div>
                <hr class="lilac-divider">
                <?php
            }
            ?>
            
            <!-- Unified Login Form -->
            <div class="lilac-login-form-col">
                <div class="lilac-login-form-wrapper">
                    <?php echo do_shortcode('[lilac_login form_title="התחברות לאתר" form_description="התחבר באמצעות מספר הטלפון/אימייל והסיסמה שלך"]'); ?>
                </div>
            </div>
            
            <!-- Registration Links -->
            <div class="lilac-login-form-col">
                <div class="lilac-registration-links-wrapper">
                    <h2>לא רשום עדיין?</h2>
                    <p class="description">בחר את סוג החשבון המתאים לך:</p>
                    
                    <div class="registration-links">
                        <?php 
                        $school_register_url = get_permalink(get_option('lilac_school_register_page', 0)) ?: '#';
                        $private_register_url = get_permalink(get_option('lilac_private_register_page', 0)) ?: '#';
                        ?>
                        <div class="reg-link school">
                            <a href="<?php echo esc_url($school_register_url); ?>">
                                הרשמה לחינוך התעבורתי
                                <span>לתלמידי כיתה י'</span>
                            </a>
                        </div>
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
    .lilac-test-login-page {
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

<?php get_footer(); ?>
