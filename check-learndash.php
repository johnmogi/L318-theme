<?php
/**
 * Check LearnDash Installation
 * 
 * This script checks if LearnDash is properly installed and active.
 */

// Load WordPress
require_once('wp-load.php');

// Check if LearnDash is active
$is_learndash_active = function_exists('learndash_get_quiz_questions');

// Check for LearnDash tables
function check_learndash_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'learndash_user_activity',
        $wpdb->prefix . 'learndash_user_activity_meta',
        $wpdb->prefix . 'learndash_pro_quiz_question',
        $wpdb->prefix . 'learndash_pro_quiz_statistic_ref',
        $wpdb->prefix . 'learndash_pro_quiz_statistic',
        $wpdb->prefix . 'learndash_pro_quiz_template',
        $wpdb->prefix . 'learndash_pro_quiz_prerequisite',
        $wpdb->prefix . 'learndash_pro_quiz_category',
        $wpdb->prefix . 'learndash_pro_quiz_form',
        $wpdb->prefix . 'learndash_pro_quiz_lock',
        $wpdb->prefix . 'learndash_pro_quiz_master',
        $wpdb->prefix . 'learndash_pro_quiz_prerequisite',
        $wpdb->prefix . 'learndash_pro_quiz_question',
        $wpdb->prefix . 'learndash_pro_quiz_statistic',
        $wpdb->prefix . 'learndash_pro_quiz_statistic_ref',
        $wpdb->prefix . 'learndash_pro_quiz_template',
        $wpdb->prefix . 'learndash_pro_quiz_toplist',
    );
    
    $results = array();
    foreach ($tables as $table) {
        $results[$table] = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
    
    return $results;
}

// Get LearnDash version
$learndash_version = defined('LEARNDASH_VERSION') ? LEARNDASH_VERSION : 'Not found';
$learndash_plugin_data = function_exists('get_plugin_data') ? get_plugin_data(WP_PLUGIN_DIR . '/sfwd-lms/sfwd_lms.php') : array();

// Get active plugins
$active_plugins = get_option('active_plugins');
$active_plugins_list = array();
foreach ($active_plugins as $plugin) {
    $active_plugins_list[] = $plugin;
}

// Output results
echo "=== LearnDash Check ===\n\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "LearnDash Active: " . ($is_learndash_active ? 'Yes' : 'No') . "\n";
echo "LearnDash Version (Constant): $learndash_version\n";

if (!empty($learndash_plugin_data)) {
    echo "LearnDash Version (Plugin): " . $learndash_plugin_data['Version'] . "\n";
}

echo "\n=== Active Plugins ===\n";
foreach ($active_plugins_list as $plugin) {
    echo "- $plugin\n";}

$tables = check_learndash_tables();
echo "\n=== LearnDash Tables ===\n";
foreach ($tables as $table => $exists) {
    echo "- $table: " . ($exists ? 'Exists' : 'Missing') . "\n";}

// Check if we can get quiz questions
if ($is_learndash_active) {
    echo "\n=== Sample Quiz Check ===\n";
    
    // Get a sample quiz
    $quizzes = get_posts(array(
        'post_type' => 'sfwd-quiz',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (!empty($quizzes)) {
        $quiz = $quizzes[0];
        echo "Found quiz: " . $quiz->post_title . " (ID: " . $quiz->ID . ")\n";
        
        // Try to get questions
        $questions = learndash_get_quiz_questions($quiz->ID);
        echo "Number of questions: " . count($questions) . "\n";
        
        if (!empty($questions)) {
            echo "Sample question IDs: " . implode(', ', array_keys($questions)) . "\n";
        }
    } else {
        echo "No published quizzes found.\n";
    }
}

echo "\nCheck complete.\n";
