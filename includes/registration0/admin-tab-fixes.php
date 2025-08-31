<?php
/**
 * This file contains fixed tab rendering methods for Registration_Admin
 */

/**
 * Fixed render_manage_tab method
 */
function fixed_render_manage_tab() {
    // Get filters from URL
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($current_page - 1) * $per_page;
    
    $group_filter = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
    $status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
    
    // Get all groups for the filter dropdown
    $groups = $this->get_all_groups();
    
    // Get codes with filters
    $codes = $this->service->get_codes([
        'group_name' => $group_filter,
        'is_used' => $status_filter === 'used' ? 1 : ($status_filter === 'active' ? 0 : null),
        'limit' => $per_page,
        'offset' => $offset
    ]);
    
    // Count total codes for pagination
    $total_codes = $this->service->count_codes([
        'group_name' => $group_filter,
        'is_used' => $status_filter === 'used' ? 1 : ($status_filter === 'active' ? 0 : null)
    ]);
    
    $total_pages = ceil($total_codes / $per_page);
    ?>
    <div id="manage-codes-tab" class="tab-pane">
        <h2><?php _e('Manage Registration Codes', 'registration-codes'); ?></h2>
        
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="registration-admin">
                <input type="hidden" name="tab" value="manage">
                
                <div class="alignleft actions">
                    <select name="group">
                        <option value=""><?php _e('All Groups', 'registration-codes'); ?></option>
                        <?php foreach ($groups as $group) : ?>
                            <option value="<?php echo esc_attr($group); ?>" <?php selected($group_filter, $group); ?>>
                                <?php echo esc_html($group); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value=""><?php _e('All Statuses', 'registration-codes'); ?></option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'registration-codes'); ?></option>
                        <option value="used" <?php selected($status_filter, 'used'); ?>><?php _e('Used', 'registration-codes'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'registration-codes'); ?>">
                </div>
            </form>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s code', '%s codes', $total_codes, 'registration-codes'), number_format_i18n($total_codes)); ?>
                </span>
                
                <?php if ($total_pages > 1) : ?>
                    <span class="pagination-links">
                        <?php
                        // First page link
                        if ($current_page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '">&laquo;</a>';
                        } else {
                            echo '<span class="first-page button disabled">&laquo;</span>';
                        }
                        
                        // Previous page link
                        if ($current_page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '">&lsaquo;</a>';
                        } else {
                            echo '<span class="prev-page button disabled">&lsaquo;</span>';
                        }
                        
                        echo '<span class="paging-input">' . $current_page . ' of ' . $total_pages . '</span>';
                        
                        // Next page link
                        if ($current_page < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '">&rsaquo;</a>';
                        } else {
                            echo '<span class="next-page button disabled">&rsaquo;</span>';
                        }
                        
                        // Last page link
                        if ($current_page < $total_pages) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '">&raquo;</a>';
                        } else {
                            echo '<span class="last-page button disabled">&raquo;</span>';
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Code', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Role', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Group', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Course', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Max Uses', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Used Count', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Expiry Date', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Status', 'registration-codes'); ?></th>
                    <th scope="col"><?php _e('Created', 'registration-codes'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($codes)) : ?>
                    <tr>
                        <td colspan="9"><?php _e('No codes found.', 'registration-codes'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($codes as $code) : ?>
                        <tr>
                            <td><?php echo esc_html($code->get_code()); ?></td>
                            <td><?php echo esc_html($code->get_role()); ?></td>
                            <td><?php echo esc_html($code->get_group_name()); ?></td>
                            <td>
                                <?php
                                $course_id = $code->get_course_id();
                                if ($course_id) {
                                    echo esc_html(get_the_title($course_id));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($code->get_max_uses()); ?></td>
                            <td><?php echo esc_html($code->get_used_count()); ?></td>
                            <td>
                                <?php 
                                $expiry_date = $code->get_expiry_date();
                                echo $expiry_date ? esc_html(date_i18n(get_option('date_format'), strtotime($expiry_date))) : '—';
                                ?>
                            </td>
                            <td>
                                <?php if ($code->is_used()) : ?>
                                    <span class="status-used"><?php _e('Used', 'registration-codes'); ?></span>
                                <?php else : ?>
                                    <span class="status-active"><?php _e('Active', 'registration-codes'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($code->get_created_at()))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Fixed render_importexport_tab method
 */
function fixed_render_importexport_tab() {
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
    <div class="registration-importexport-tab">
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
