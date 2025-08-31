<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

global $wpdb;
echo "Database prefix: {$wpdb->prefix}\n";
echo "Checking if tables exist:\n";

$tables = [
    $wpdb->prefix . 'registration_codes',
    $wpdb->prefix . 'edc_school_teachers',
    $wpdb->prefix . 'edc_school_students'
];

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    echo $table . ": " . ($exists ? "Exists" : "Missing") . "\n";
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        echo "  - Records: {$count}\n";
    }
}

// Show table create statements if they don't exist
echo "\nCreating missing tables if needed:\n";
$teacher_table = $wpdb->prefix . 'edc_school_teachers';
if (!$wpdb->get_var("SHOW TABLES LIKE '{$teacher_table}'")) {
    $sql = "CREATE TABLE IF NOT EXISTS {$teacher_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT(20) UNSIGNED NOT NULL,
        teacher_id_number VARCHAR(50) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        bio TEXT NULL,
        subjects_taught TEXT NULL,
        status ENUM('active','inactive','on_leave') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY teacher_id_number (teacher_id_number),
        UNIQUE KEY wp_user_id (wp_user_id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $result = $wpdb->query($sql);
    echo "Teacher table creation: " . ($result !== false ? "Success" : "Failed") . "\n";
    if ($result === false) {
        echo "Error: " . $wpdb->last_error . "\n";
    }
}

$student_table = $wpdb->prefix . 'edc_school_students';
if (!$wpdb->get_var("SHOW TABLES LIKE '{$student_table}'")) {
    $sql = "CREATE TABLE IF NOT EXISTS {$student_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_user_id BIGINT(20) UNSIGNED NOT NULL,
        student_id_number VARCHAR(50) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        address TEXT NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        postal_code VARCHAR(20) NULL,
        country VARCHAR(100) NULL,
        date_of_birth DATE NULL,
        status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY student_id_number (student_id_number),
        UNIQUE KEY wp_user_id (wp_user_id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $result = $wpdb->query($sql);
    echo "Student table creation: " . ($result !== false ? "Success" : "Failed") . "\n";
    if ($result === false) {
        echo "Error: " . $wpdb->last_error . "\n";
    }
}
?>
