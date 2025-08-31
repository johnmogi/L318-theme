<?php
defined('ABSPATH') || exit;

function ld_verify_quiz_tables() {
    global $wpdb;
    
    if (!current_user_can('manage_options') || 
        get_transient('ld_quiz_tables_verified')) {
        return;
    }

    $table_name = $wpdb->prefix . 'pro_quiz_answer';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql = "CREATE TABLE $table_name (
            answer_id bigint(20) NOT NULL AUTO_INCREMENT,
            question_id bigint(20) NOT NULL,
            answer text NOT NULL,
            correct tinyint(1) NOT NULL DEFAULT 0,
            sort smallint(6) NOT NULL DEFAULT 0,
            PRIMARY KEY  (answer_id),
            KEY question_id (question_id)
        ) " . $wpdb->get_charset_collate() . ";";
        
        dbDelta($sql);
        error_log("[LD_REPAIR] Created missing table: $table_name");
        set_transient('ld_quiz_tables_verified', true, WEEK_IN_SECONDS);
    }
}

add_action('admin_init', 'ld_verify_quiz_tables');
