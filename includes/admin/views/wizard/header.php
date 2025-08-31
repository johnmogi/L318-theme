<?php if (!defined('ABSPATH')) exit; ?>
<style>
/* Wizard Steps - Inline Styles */
.wizard-steps {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center;
    margin: 20px auto 40px !important;
    padding: 20px 0 !important;
    list-style: none !important;
    position: relative;
    counter-reset: step;
    max-width: 800px;
    width: 100%;
    box-sizing: border-box;
}

.wizard-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e0e0e0;
    z-index: 1;
}

.wizard-steps .step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
    padding: 0 10px;
}

.wizard-steps .step::before {
    content: counter(step);
    counter-increment: step;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin: 0 auto 8px;
    border-radius: 50%;
    background: #f0f0f0;
    color: #999;
    font-weight: bold;
    border: 2px solid #e0e0e0;
    position: relative;
    z-index: 2;
}

.wizard-steps .step.active::before {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.wizard-steps .step.completed::before {
    content: '✓';
    background: #46b450;
    color: white;
    border-color: #46b450;
}

.wizard-steps .step-title {
    display: block;
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.wizard-steps .step.active .step-title {
    color: #0073aa;
    font-weight: 600;
}

.wizard-steps .step.completed .step-title {
    color: #46b450;
}

/* RTL support */
.rtl .wizard-steps {
    direction: ltr;
}

.rtl .wizard-steps .step {
    direction: rtl;
}

/* Select2 Styling */
.select2-container {
    width: 100% !important;
    max-width: 500px;
    margin-bottom: 15px;
}

.select2-container--default .select2-selection--single {
    border: 1px solid #8c8f94;
    border-radius: 4px;
    height: 36px;
    padding: 0 8px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 34px;
    padding-right: 30px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px;
    right: 6px;
    left: auto;
}

/* RTL support for Select2 */
.rtl .select2-container--default .select2-selection--single .select2-selection__arrow {
    left: 6px;
    right: auto;
}

.rtl .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding-right: 8px;
    padding-left: 30px;
}

/* Dropdown styling */
.select2-dropdown {
    border-color: #8c8f94;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.select2-results__option {
    padding: 8px 12px;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0073aa;
}

/* Form field styling */
.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-field .description {
    font-style: italic;
    color: #666;
    margin-top: 5px;
    font-size: 12px;
}
</style>

<div class="wrap teacher-class-wizard" dir="rtl">
    <h1><?php _e('Teacher/Class Management Wizard', 'hello-theme-child'); ?></h1>
    <p>Debug: Testing CSS</p>
    
    <div class="wizard-steps">
        <div class="step <?php 
            echo $this->current_step > 1 ? 'completed' : ''; 
            echo $this->current_step == 1 ? 'active' : ''; 
        ?>" data-step="1">
            <span class="step-title"><?php _e('Teacher', 'hello-theme-child'); ?></span>
        </div>
        <div class="step <?php 
            echo $this->current_step > 2 ? 'completed' : ''; 
            echo $this->current_step == 2 ? 'active' : ''; 
        ?>" data-step="2">
            <span class="step-title"><?php _e('Class', 'hello-theme-child'); ?></span>
        </div>
        <div class="step <?php 
            echo $this->current_step > 3 ? 'completed' : ''; 
            echo $this->current_step == 3 ? 'active' : ''; 
        ?>" data-step="3">
            <span class="step-title"><?php _e('Promo Codes', 'hello-theme-child'); ?></span>
        </div>
    </div>
    
    <form method="post" action="" class="wizard-form">
        <?php wp_nonce_field('teacher_class_wizard', 'teacher_class_wizard_nonce'); ?>
        <input type="hidden" name="step" value="<?php echo esc_attr($this->current_step); ?>">
