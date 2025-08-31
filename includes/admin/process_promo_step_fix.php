<?php
/**
 * Process promo code step
 */
private function process_promo_step() {
    // Back button
    if (isset($_POST['back'])) {
        $this->current_step = 2;
        return;
    }
    
    // Validate and process promo code generation
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 10;
    $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
    
    // Check if create_students was actually checked AND student data was submitted
    // This is to prevent validation errors when the student form wasn't displayed
    $create_students = !empty($_POST['create_students']);
    $students = isset($_POST['students']) ? $_POST['students'] : array();
    
    error_log('WIZARD CLASS: Process promo step - create_students: ' . ($create_students ? 'true' : 'false'));
    error_log('WIZARD CLASS: Process promo step - students data submitted: ' . (!empty($students) ? 'true' : 'false'));
    
    // Validate quantity
    if ($quantity < 1 || $quantity > 1000) {
        $this->errors[] = __('Quantity must be between 1 and 1000.', 'hello-theme-child');
        return;
    }
    
    // Skip student validation if checkbox is unchecked or no student data submitted
    if (!$create_students || empty($students)) {
        error_log('WIZARD CLASS: Skipping student validation - checkbox unchecked or no student data');
        // Generate promo codes only
        $promo_codes = $this->generate_promo_codes($quantity, $prefix);
        
        // Store form data without students
        $this->form_data['promo_codes'] = $promo_codes;
        $this->form_data['quantity'] = $quantity;
        $this->form_data['prefix'] = $prefix;
        $this->form_data['created_students'] = array();
        $this->form_data['create_students'] = false;
        return;
    }
    
    // Validate student data if checkbox was checked AND data submitted
    error_log('WIZARD CLASS: Validating student data');
    foreach ($students as $index => $student) {
        $student_num = $index + 1;
        
        // Required fields
        $required_fields = array(
            'first_name' => __('First Name', 'hello-theme-child'),
            'last_name' => __('Last Name', 'hello-theme-child'),
            'username' => __('Phone Number', 'hello-theme-child'),
            'password' => __('Student ID', 'hello-theme-child')
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty(trim($student[$field]))) {
                $this->errors[] = sprintf(__('Student #%d: %s is required', 'hello-theme-child'), $student_num, $label);
            }
        }
        
        // Validate phone number format
        if (!empty($student['username']) && !preg_match('/^\d{9,15}$/', $student['username'])) {
            $this->errors[] = sprintf(__('Student #%d: Please enter a valid phone number (9-15 digits)', 'hello-theme-child'), $student_num);
        }
        
        // Check if username already exists
        if (!empty($student['username']) && username_exists($student['username'])) {
            $this->errors[] = sprintf(__('Student #%d: The username %s is already taken', 'hello-theme-child'), $student_num, $student['username']);
        }
    }
    
    // If we have errors, stop processing
    if (!empty($this->errors)) {
        return;
    }
    
    // Generate promo codes
    $promo_codes = $this->generate_promo_codes($quantity, $prefix);
    $created_students = array();
    
    // Create student accounts
    foreach ($students as $index => $student_data) {
        $student_data = array_map('sanitize_text_field', $student_data);
        $promo_code = isset($promo_codes[$index]) ? $promo_codes[$index] : '';
        
        // Create user data array
        $user_data = array(
            'user_login'    => $student_data['username'],
            'user_email'    => !empty($student_data['email']) ? $student_data['email'] : $student_data['username'] . '@example.com',
            'first_name'    => $student_data['first_name'],
            'last_name'     => $student_data['last_name'],
            'user_pass'     => $student_data['password'],
            'role'          => 'subscriber',
            'display_name'  => $student_data['first_name'] . ' ' . $student_data['last_name']
        );
        
        // Insert user
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            $this->errors[] = sprintf(
                __('Error creating student %s: %s', 'hello-theme-child'),
                $student_data['first_name'] . ' ' . $student_data['last_name'],
                $user_id->get_error_message()
            );
            continue;
        }
        
        // Add user meta
        update_user_meta($user_id, 'phone_number', $student_data['username']);
        update_user_meta($user_id, 'student_id', $student_data['password']);
        
        // Store promo code association with student
        if (!empty($promo_code)) {
            update_user_meta($user_id, '_promo_code', $promo_code);
            
            // Also update the promo code CPT entry to link to this student
            $existing = get_page_by_title($promo_code, OBJECT, 'ld_promo_code');
            if ($existing) {
                error_log('WIZARD CLASS: Linking student ' . $user_id . ' with promo code: ' . $promo_code);
                update_post_meta($existing->ID, '_ld_promo_code_student_id', $user_id);
            }
        }
        
        // Enroll student in the selected group if available
        if (!empty($this->form_data['class_id'])) {
            $group_id = $this->form_data['class_id'];
            
            error_log('WIZARD CLASS: Enrolling student ' . $user_id . ' in group ' . $group_id);
            
            // Use all available LearnDash functions to ensure enrollment
            if (function_exists('ld_update_group_access')) {
                error_log('WIZARD CLASS: Using ld_update_group_access');
                ld_update_group_access($user_id, $group_id);
            }
            
            // Add to group users - main LearnDash function
            if (function_exists('learndash_set_group_users')) {
                error_log('WIZARD CLASS: Using learndash_set_group_users');
                // Get existing users
                $existing_users = learndash_get_groups_user_ids($group_id);
                if (!is_array($existing_users)) {
                    $existing_users = array();
                }
                // Add new user if not already in the group
                if (!in_array($user_id, $existing_users)) {
                    $existing_users[] = $user_id;
                    learndash_set_group_users($group_id, $existing_users);
                }
            }
            
            // Direct database update for older LearnDash versions
            global $wpdb;
            // Check if the user_group entry exists
            $table_name = $wpdb->prefix . 'learndash_user_group';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT user_id FROM $table_name WHERE user_id = %d AND group_id = %d",
                    $user_id, $group_id
                ));
                
                if (!$existing) {
                    error_log('WIZARD CLASS: Direct DB insert to learndash_user_group');
                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id' => $user_id,
                            'group_id' => $group_id
                        ),
                        array('%d', '%d')
                    );
                }
            }
            
            // Add user as group member via post meta as fallback
            $group_member_ids = (array) get_post_meta($group_id, '_groups_members', true);
            if (!in_array($user_id, $group_member_ids)) {
                $group_member_ids[] = $user_id;
                update_post_meta($group_id, '_groups_members', $group_member_ids);
                error_log('WIZARD CLASS: Updated _groups_members meta with ' . count($group_member_ids) . ' members');
            }
        }
        
        // Store created student data
        $created_students[] = array(
            'id' => $user_id,
            'name' => $student_data['first_name'] . ' ' . $student_data['last_name'],
            'username' => $student_data['username'],
            'password' => $student_data['password'],
            'promo_code' => $promo_code
        );
        
        // Log the creation
        error_log(sprintf(
            'TEACHER_WIZARD: Created student %s (ID: %d) with username %s',
            $student_data['first_name'] . ' ' . $student_data['last_name'],
            $user_id,
            $student_data['username']
        ));
    }
    
    // Store form data
    $this->form_data['promo_codes'] = $promo_codes;
    $this->form_data['quantity'] = $quantity;
    $this->form_data['prefix'] = $prefix;
    $this->form_data['created_students'] = $created_students;
    $this->form_data['create_students'] = $create_students;
}
