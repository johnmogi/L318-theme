<?php
/**
 * Complete ImportExport tab renderer
 * This file contains a complete implementation of the Import/Export tab
 * to be included in the Registration_Admin class
 */

/**
 * Render import/export tab for teachers and students
 */
function render_importexport_tab() {
    // Check for form submissions
    $message = '';
    $message_type = '';
    
    // Handle teacher import
    if (isset($_POST['teacher_import_submit']) && isset($_FILES['teacher_import_file'])) {
        check_admin_referer('teacher_import_nonce');
        $message = $this->process_teacher_import($_FILES['teacher_import_file']);
        $message_type = (strpos($message, 'Error') !== false) ? 'error' : 'success';
    }
    
    // Handle student import
    if (isset($_POST['student_import_submit']) && isset($_FILES['student_import_file'])) {
        check_admin_referer('student_import_nonce');
        $message = $this->process_student_import($_FILES['student_import_file']);
        $message_type = (strpos($message, 'Error') !== false) ? 'error' : 'success';
    }
    
    // Handle teacher export
    if (isset($_POST['teacher_export_submit'])) {
        check_admin_referer('teacher_export_nonce');
        $this->export_teachers_as_csv();
        // This will exit after download
    }
    
    // Handle student export
    if (isset($_POST['student_export_submit'])) {
        check_admin_referer('student_export_nonce');
        $this->export_students_as_csv();
        // This will exit after download
    }
    
    // Display message if any
    if (!empty($message)) {
        ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
    
    ?>
    <div id="importexport-tab" class="tab-pane">
        <h2><?php _e('Import/Export Data', 'registration-codes'); ?></h2>
        <p><?php _e('Import or export teacher and student data using CSV files.', 'registration-codes'); ?></p>
        
        <!-- Teacher Import/Export -->
        <div class="importexport-section">
            <h3><?php _e('Teacher Data', 'registration-codes'); ?></h3>
            
            <!-- Teacher Import Form -->
            <div class="import-form-container">
                <h4><?php _e('Import Teachers', 'registration-codes'); ?></h4>
                <p><?php _e('Upload a CSV file with teacher data. The CSV should have the following columns:', 'registration-codes'); ?></p>
                <pre>teacher_id_number,first_name,last_name,email,phone,bio,subjects_taught,status</pre>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('teacher_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="teacher-import-file"><?php _e('CSV File', 'registration-codes'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="teacher-import-file" name="teacher_import_file" accept=".csv" required>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="teacher_import_submit" class="button button-primary" value="<?php _e('Import Teachers', 'registration-codes'); ?>">
                    </p>
                </form>
            </div>
            
            <!-- Teacher Export Form -->
            <div class="export-form-container">
                <h4><?php _e('Export Teachers', 'registration-codes'); ?></h4>
                <p><?php _e('Download all teacher data as a CSV file.', 'registration-codes'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('teacher_export_nonce'); ?>
                    <p class="submit">
                        <input type="submit" name="teacher_export_submit" class="button button-secondary" value="<?php _e('Export Teachers', 'registration-codes'); ?>">
                    </p>
                </form>
            </div>
        </div>
        
        <hr>
        
        <!-- Student Import/Export -->
        <div class="importexport-section">
            <h3><?php _e('Student Data', 'registration-codes'); ?></h3>
            
            <!-- Student Import Form -->
            <div class="import-form-container">
                <h4><?php _e('Import Students', 'registration-codes'); ?></h4>
                <p><?php _e('Upload a CSV file with student data. The CSV should have the following columns:', 'registration-codes'); ?></p>
                <pre>student_id_number,first_name,last_name,email,phone,address,city,state,postal_code,country,date_of_birth,status</pre>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('student_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="student-import-file"><?php _e('CSV File', 'registration-codes'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="student-import-file" name="student_import_file" accept=".csv" required>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="student_import_submit" class="button button-primary" value="<?php _e('Import Students', 'registration-codes'); ?>">
                    </p>
                </form>
            </div>
            
            <!-- Student Export Form -->
            <div class="export-form-container">
                <h4><?php _e('Export Students', 'registration-codes'); ?></h4>
                <p><?php _e('Download all student data as a CSV file.', 'registration-codes'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('student_export_nonce'); ?>
                    <p class="submit">
                        <input type="submit" name="student_export_submit" class="button button-secondary" value="<?php _e('Export Students', 'registration-codes'); ?>">
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}
