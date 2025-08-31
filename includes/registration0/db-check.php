<?php
// Define WP_DEBUG to get more info
define('WP_DEBUG', true);

// Get absolute path to WordPress load
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';
echo "Looking for wp-load.php at: $wp_load_path\n";

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    
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
            
            // Show sample record
            if ($count > 0) {
                $record = $wpdb->get_row("SELECT * FROM {$table} LIMIT 1", ARRAY_A);
                echo "  - Sample record:\n";
                print_r($record);
            }
        }
    }
} else {
    echo "ERROR: WordPress load file not found at: $wp_load_path\n";
    
    // Look for wp-config.php to help locate WordPress
    $possible_paths = [
        dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))),
        dirname(dirname(dirname(dirname(dirname(__FILE__))))),
        dirname(dirname(dirname(dirname(__FILE__)))),
        dirname(dirname(dirname(__FILE__))),
        dirname(dirname(__FILE__))
    ];
    
    foreach ($possible_paths as $path) {
        $config_file = $path . '/wp-config.php';
        if (file_exists($config_file)) {
            echo "Found wp-config.php at: $config_file\n";
            break;
        }
    }
}
?>
