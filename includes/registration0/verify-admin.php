<?php
/**
 * Verify Registration Admin Functionality
 * This script checks if the Registration_Admin class is loading correctly
 * and verifies the database tables for teachers and students
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

echo "=== Registration Admin Verification ===\n\n";

// Check if Registration_Admin class exists
if (class_exists('Registration_Admin')) {
    echo "✓ Registration_Admin class found\n";
    
    // Get instance
    $admin = Registration_Admin::get_instance();
    if ($admin) {
        echo "✓ Registration_Admin instance created\n";
    } else {
        echo "✗ Failed to create Registration_Admin instance\n";
    }
} else {
    echo "✗ Registration_Admin class not found\n";
}

// Check database tables
global $wpdb;
echo "\n=== Database Tables ===\n";

$tables = [
    'wp_edc_school_registration_codes' => 'Registration Codes',
    'wp_edc_school_teachers' => 'Teachers',
    'wp_edc_school_students' => 'Students'
];

foreach ($tables as $table => $description) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $count = $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0;
    
    if ($table_exists) {
        echo "✓ {$description} table found with {$count} records\n";
    } else {
        echo "✗ {$description} table not found\n";
    }
}

// List the tabs available in the admin UI
echo "\n=== Admin Tabs ===\n";
$tabs = [
    'generate' => 'Generate',
    'manage' => 'Manage',
    'test' => 'Test',
    'testflow' => 'Test Flow',
    'importexport' => 'Import/Export'
];

echo "The following tabs should be available in the admin UI:\n";
foreach ($tabs as $slug => $name) {
    echo "- {$name}\n";
}

echo "\n=== Import/Export Sample Data ===\n";
echo "Sample data files found:\n";

$sample_files = [
    '../../../sample-data/teachers-sample.csv' => 'Teachers',
    '../../../sample-data/students-sample.csv' => 'Students'
];

foreach ($sample_files as $file => $type) {
    if (file_exists($file)) {
        $rows = count(file($file)) - 1; // Subtract header row
        echo "✓ {$type} sample file found with {$rows} sample records\n";
    } else {
        echo "✗ {$type} sample file not found at {$file}\n";
    }
}

echo "\n=== Next Steps ===\n";
echo "1. Go to WordPress admin and navigate to Registration Codes menu\n";
echo "2. Click on the Import/Export tab\n";
echo "3. Import teachers-sample.csv and students-sample.csv using the provided forms\n";
echo "4. Verify that the data appears in the database tables\n";
echo "5. Test export functionality to verify that the exported CSV files contain the expected data\n";
