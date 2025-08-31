<?php
/**
 * Template Name: Test Forms
 *
 * A test page template for testing all form components.
 *
 * @package Hello_Child_Theme
 */

get_header();

the_post();
?>

<div class="lilac-forms-test">
    <div class="lilac-forms-header">
        <h1><?php the_title(); ?></h1>
    </div>
    
    <div class="lilac-forms-container">
        <!-- Student Login Form -->
        <section class="lilac-form-section">
            <h2>Student Login Form</h2>
            <?php echo do_shortcode('[lilac_student_login form_title="התחברות תלמיד" login_button_text="התחבר"]'); ?>
        </section>
        
        <!-- Teacher Login Form -->
        <section class="lilac-form-section">
            <h2>Teacher Login Form</h2>
            <?php echo do_shortcode('[lilac_teacher_login form_title="התחברות מורה" login_button_text="התחבר"]'); ?>
        </section>
        
        <!-- Teacher Lost Password Form -->
        <section class="lilac-form-section">
            <h2>Password Reset Form</h2>
            <?php echo do_shortcode('[lilac_teacher_lostpassword form_title="איפוס סיסמה" button_text="אפס סיסמה"]'); ?>
        </section>
    </div>
</div>

<style>
    .lilac-forms-test {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        direction: rtl;
    }
    
    .lilac-forms-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .lilac-forms-header h1 {
        margin-bottom: 20px;
    }
    
    .lilac-forms-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }
    
    .lilac-form-section {
        background: var(--color-white);
        padding: 30px;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
    }
    
    .lilac-form-section h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--color-gray-900);
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .lilac-forms-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
get_footer();
?>
