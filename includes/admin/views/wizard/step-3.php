<div class="wizard-step-content">
    <h2><?php _e('Step 3: Generate Promo Codes', 'hello-theme-child'); ?></h2>
    
    <div class="form-section">
        <div class="form-field">
            <label for="quantity"><?php _e('Number of Codes', 'hello-theme-child'); ?> *</label>
            <input type="number" name="quantity" id="quantity" min="1" max="1000" value="10" class="small-text" required>
            <p class="description"><?php _e('Enter the number of promo codes to generate (max 1000)', 'hello-theme-child'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="prefix"><?php _e('Code Prefix', 'hello-theme-child'); ?></label>
            <input type="text" name="prefix" id="prefix" value="STU" maxlength="5" class="small-text">
            <p class="description"><?php _e('Optional prefix for the codes (max 5 characters)', 'hello-theme-child'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="expiry_date"><?php _e('Expiry Date', 'hello-theme-child'); ?></label>
            <input type="date" name="expiry_date" id="expiry_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
            <p class="description"><?php _e('Date when these codes will expire', 'hello-theme-child'); ?></p>
        </div>
        
        <?php /* Generate Promo Codes button removed as it's not needed in this section */ ?>
        
        <div class="form-field">
            <label>
                <input type="checkbox" name="create_students" id="create_students" value="1">
                <?php _e('Create student accounts now', 'hello-theme-child'); ?>
            </label>
            <p class="description"><?php _e('If checked, you can enter student details for each code', 'hello-theme-child'); ?></p>
        </div>
        
        <div id="student-fields-container" style="display: none; margin-top: 20px;">
            <h3><?php _e('Student Information', 'hello-theme-child'); ?></h3>
            <div id="student-fields-template" class="student-fields">
                <div class="student-field-group">
                    <h4><?php _e('Student #1', 'hello-theme-child'); ?></h4>
                    <div class="form-field">
                        <label><?php _e('First Name', 'hello-theme-child'); ?> *</label>
                        <input type="text" name="students[0][first_name]" class="regular-text" data-required="true">
                    </div>
                    <div class="form-field">
                        <label><?php _e('Last Name', 'hello-theme-child'); ?> *</label>
                        <input type="text" name="students[0][last_name]" class="regular-text" data-required="true">
                    </div>
                    <div class="form-field">
                        <label><?php _e('Phone Number (Username)', 'hello-theme-child'); ?> *</label>
                        <input type="tel" name="students[0][username]" class="regular-text" data-required="true"
                               pattern="[0-9]{9,15}" title="<?php esc_attr_e('Please enter a valid phone number (digits only, 9-15 characters)', 'hello-theme-child'); ?>">
                        <p class="description"><?php _e('This will be used as the student\'s username', 'hello-theme-child'); ?></p>
                    </div>
                    <div class="form-field">
                        <label><?php _e('Student ID (Password)', 'hello-theme-child'); ?> *</label>
                        <input type="text" name="students[0][password]" class="regular-text" data-required="true">
                        <p class="description"><?php _e('This will be used as the student\'s password', 'hello-theme-child'); ?></p>
                    </div>
                    <div class="form-field">
                        <label><?php _e('Email (Optional)', 'hello-theme-child'); ?></label>
                        <input type="email" name="students[0][email]" class="regular-text">
                        <p class="description"><?php _e('Optional email address for password recovery', 'hello-theme-child'); ?></p>
                    </div>
                </div>
            </div>
            <div id="student-fields-container"></div>
            <button type="button" id="add-student-field" class="button">
                <?php _e('+ Add Another Student', 'hello-theme-child'); ?>
            </button>
        </div>
    </div>
    
    <?php if (!empty($this->form_data['promo_codes'])) : ?>
        <div class="promo-codes-generated">
            <h3><?php _e('Generated Promo Codes', 'hello-theme-child'); ?></h3>
            <div class="promo-codes-list">
                <?php foreach ($this->form_data['promo_codes'] as $code) : ?>
                    <div class="promo-code-item">
                        <code><?php echo esc_html($code); ?></code>
                        <button type="button" class="copy-code button button-small" data-code="<?php echo esc_attr($code); ?>">
                            <?php _e('Copy', 'hello-theme-child'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="download-codes" class="button button-primary">
                <?php _e('Download as CSV', 'hello-theme-child'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
