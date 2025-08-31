<?php
namespace Lilac\Messaging\Admin;

/**
 * TeacherImport class for CSV importing of school_teacher users
 */
class TeacherImport {
    /**
     * Import log table name
     */
    private $log_table_name;

    /**
     * Generated teachers data
     */
    private $generated_teachers = [];

    /**
     * Init class
     */
    public static function get_instance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Constructor - add hooks
     */
    private function __construct() {
        global $wpdb;
        $this->log_table_name = $wpdb->prefix . 'teacher_import_logs';
        
        add_action('admin_menu', [$this, 'add_import_page']);
        add_action('admin_post_export_teachers', [$this, 'export_teachers']);
        
        // Create log table if needed
        $this->maybe_create_log_table();
    }

    /**
     * Create the log table if it doesn't exist
     */
    private function maybe_create_log_table() {
        global $wpdb;
        
        $table_name = $this->log_table_name;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                import_date datetime NOT NULL,
                imported int(11) NOT NULL DEFAULT 0,
                skipped int(11) NOT NULL DEFAULT 0,
                total int(11) NOT NULL DEFAULT 0,
                import_data longtext,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Add admin submenu page
     */
    public function add_import_page() {
        add_submenu_page(
            'registration-codes', // Parent page of Registration Codes
            'Import Teachers',
            'Import Teachers',
            'manage_options',
            'import-teachers',
            [$this, 'render_import_page']
        );
    }

    /**
     * Render import page
     */
    public function render_import_page() {
        echo '<div class="wrap">';
        echo '<h1>Import Teachers</h1>';
        
        // Process import if submitted
        if (!empty($_FILES['teacher_csv']) && !empty($_FILES['teacher_csv']['tmp_name'])) {
            $this->process_import($_FILES['teacher_csv']['tmp_name']);
        }
        
        // Show import form
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<table class="form-table">';
        echo '<tr>
                <th scope="row">CSV File</th>
                <td>
                    <input type="file" name="teacher_csv" accept=".csv" required>
                    <p class="description">Format: phone,email,first_name,last_name</p>
                    <p><a href="' . get_stylesheet_directory_uri() . '/dev/teacher.csv" download>Download template</a></p>
                </td>
              </tr>';
        echo '<tr>
                <th scope="row">Password Options</th>
                <td>
                    <label><input type="radio" name="password_option" value="generate" checked> Generate random passwords</label><br>
                    <label><input type="radio" name="password_option" value="same"> Use same password for all</label>
                    <input type="text" name="default_password" placeholder="Enter password if using same for all">
                </td>
              </tr>';
        echo '<tr>
                <th scope="row">After Import</th>
                <td>
                    <label><input type="checkbox" name="send_email" value="1"> Send welcome emails with login credentials</label>
                </td>
              </tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Import Teachers"></p>';
        echo '</form>';
        
        // Show previous imports
        $this->show_import_logs();
        
        echo '</div>';
    }

    /**
     * Process CSV import
     */
    private function process_import($file) {
        $users_created = 0;
        $errors = [];
        $this->generated_teachers = [];
        
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            echo '<div class="notice notice-info"><p>Import results:</p><ul>';
            
            // Password option settings
            $password_option = isset($_POST['password_option']) ? sanitize_text_field($_POST['password_option']) : 'generate';
            $default_password = isset($_POST['default_password']) ? $_POST['default_password'] : '';
            
            // If same password for all but none provided, generate one
            if ($password_option === 'same' && empty($default_password)) {
                $default_password = wp_generate_password(12, false);
            }
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Parse CSV row (phone, email, first_name, last_name)
                $phone = !empty($data[0]) ? preg_replace('/[^0-9]/', '', $data[0]) : '';
                $email = !empty($data[1]) ? $data[1] : $phone . '@school.edu';
                $first_name = !empty($data[2]) ? $data[2] : '';
                $last_name = !empty($data[3]) ? $data[3] : '';
                
                if (empty($phone)) {
                    $errors[] = 'Skipped row - missing phone number';
                    continue;
                }
                
                // Generate password based on option
                $password = ($password_option === 'same') ? $default_password : wp_generate_password(12, false);
                
                // Create user
                $user_id = wp_insert_user([
                    'user_login' => $phone,
                    'user_pass' => $password,
                    'user_email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'role' => 'school_teacher'
                ]);
                
                if (is_wp_error($user_id)) {
                    $errors[] = 'Error creating user with phone ' . $phone . ': ' . $user_id->get_error_message();
                } else {
                    // Store original phone format
                    update_user_meta($user_id, 'phone', $data[0]);
                    
                    // Store for export
                    $this->generated_teachers[] = [
                        'id' => $user_id,
                        'phone' => $phone,
                        'email' => $email,
                        'name' => trim($first_name . ' ' . $last_name),
                        'password' => $password
                    ];
                    
                    $users_created++;
                    echo '<li>Created teacher: ' . esc_html(trim($first_name . ' ' . $last_name)) . ' (' . esc_html($phone) . ')</li>';
                    
                    // Send welcome email if enabled
                    if (isset($_POST['send_email']) && $_POST['send_email'] == '1') {
                        $this->send_welcome_email($email, $phone, $password, $first_name);
                    }
                }
            }
            
            fclose($handle);
            
            // Log the import
            $this->log_import($users_created, count($errors), $users_created + count($errors));
            
            echo '</ul>';
            echo '<p>Created ' . $users_created . ' teachers.</p>';
            
            if (!empty($errors)) {
                echo '<p>Errors:</p><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            
            // Show export option if teachers were created
            if (!empty($this->generated_teachers)) {
                echo '<div class="teacher-export">';
                echo '<h3>Teacher Login Credentials</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>Name</th><th>Phone</th><th>Email</th><th>Password</th>';
                echo '</tr></thead><tbody>';
                
                foreach ($this->generated_teachers as $teacher) {
                    echo '<tr>';
                    echo '<td>' . esc_html($teacher['name']) . '</td>';
                    echo '<td>' . esc_html($teacher['phone']) . '</td>';
                    echo '<td>' . esc_html($teacher['email']) . '</td>';
                    echo '<td>' . esc_html($teacher['password']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // Export button
                echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
                echo '<input type="hidden" name="action" value="export_teachers">';
                echo '<input type="hidden" name="import_id" value="' . $this->get_last_import_id() . '">';
                echo '<p><button type="submit" class="button">Export to CSV</button></p>';
                echo '</form>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Log the import
     */
    private function log_import($imported, $skipped, $total) {
        global $wpdb;
        
        $wpdb->insert(
            $this->log_table_name,
            [
                'import_date' => current_time('mysql'),
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => $total,
                'import_data' => json_encode($this->generated_teachers)
            ],
            ['%s', '%d', '%d', '%d', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get the last import ID
     */
    private function get_last_import_id() {
        global $wpdb;
        return $wpdb->get_var("SELECT id FROM {$this->log_table_name} ORDER BY id DESC LIMIT 1");
    }
    
    /**
     * Show import logs
     */
    private function show_import_logs() {
        global $wpdb;
        
        $logs = $wpdb->get_results("SELECT * FROM {$this->log_table_name} ORDER BY import_date DESC LIMIT 10");
        
        if (empty($logs)) {
            return;
        }
        
        echo '<h3>Recent Imports</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Date</th><th>Imported</th><th>Skipped</th><th>Total</th><th>Actions</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->import_date)) . '</td>';
            echo '<td>' . $log->imported . '</td>';
            echo '<td>' . $log->skipped . '</td>';
            echo '<td>' . $log->total . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin-post.php?action=export_teachers&import_id=' . $log->id) . '" class="button button-small">Export</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Send welcome email
     */
    private function send_welcome_email($email, $username, $password, $name) {
        $subject = 'Welcome to ' . get_bloginfo('name') . ' as a Teacher';
        
        $message = "Hello " . ($name ?: 'Teacher') . ",\n\n";
        $message .= "Your account has been created at " . get_bloginfo('name') . ".\n\n";
        $message .= "Username: " . $username . "\n";
        $message .= "Password: " . $password . "\n\n";
        $message .= "You can log in at: " . wp_login_url() . "\n\n";
        $message .= "Thank you,\n";
        $message .= get_bloginfo('name');
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Export teachers to CSV
     */
    public function export_teachers() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        $import_id = isset($_REQUEST['import_id']) ? intval($_REQUEST['import_id']) : 0;
        
        if ($import_id > 0) {
            $import_data = $wpdb->get_var($wpdb->prepare(
                "SELECT import_data FROM {$this->log_table_name} WHERE id = %d",
                $import_id
            ));
        } else {
            $import_data = json_encode($this->generated_teachers);
        }
        
        $teachers = json_decode($import_data, true);
        
        if (empty($teachers)) {
            wp_redirect(admin_url('admin.php?page=import-teachers&error=no_data'));
            exit;
        }
        
        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=teachers_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add header row
        fputcsv($output, ['Name', 'Phone', 'Email', 'Password']);
        
        // Add data rows
        foreach ($teachers as $teacher) {
            fputcsv($output, [
                $teacher['name'],
                $teacher['phone'],
                $teacher['email'],
                $teacher['password']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Initialize the class
TeacherImport::get_instance();
