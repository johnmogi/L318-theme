<?php
/**
 * LearnDash Quiz Debug Panel for Administrators
 * 
 * Provides useful debugging information for admins to diagnose ACF field mapping issues.
 * 
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Only display for administrators
if (!current_user_can('administrator')) {
    return;
}

?>
<div class="ld-debug-panel">
    <div class="ld-debug-header">
        <h3>Quiz Template Debug Panel</h3>
        <p>This information is only visible to administrators.</p>
    </div>
    
    <div class="ld-debug-content">
        <div class="debug-section">
            <h4>Quiz Information</h4>
            <table class="debug-table">
                <tr>
                    <th>Quiz ID:</th>
                    <td><?php echo esc_html($quiz_id); ?></td>
                </tr>
                <tr>
                    <th>Questions Found:</th>
                    <td><?php echo is_array($questions) ? count($questions) : '0'; ?></td>
                </tr>
                <tr>
                    <th>Media Items:</th>
                    <td><?php echo is_array($media_items) ? count($media_items) : '0'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="debug-section">
            <h4>ACF Field Mapping</h4>
            <?php if (!empty($questions) && is_array($questions)): ?>
                <table class="debug-table">
                    <tr>
                        <th>Question ID</th>
                        <th>Question Title</th>
                        <th>Fields Found</th>
                    </tr>
                    <?php foreach ($questions as $question_id => $question): 
                        $acf_fields = function_exists('get_fields') ? get_fields($question_id) : array();
                        $field_names = is_array($acf_fields) ? array_keys($acf_fields) : array();
                        $field_list = !empty($field_names) ? implode(', ', $field_names) : 'None';
                    ?>
                    <tr>
                        <td><?php echo esc_html($question_id); ?></td>
                        <td><?php echo esc_html(get_the_title($question_id)); ?></td>
                        <td><?php echo esc_html($field_list); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No questions found for this quiz.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .ld-debug-panel {
        margin: 20px 0;
        padding: 15px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    
    .ld-debug-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .ld-debug-header h3 {
        margin: 0 0 10px;
        color: #23282d;
    }
    
    .ld-debug-content .debug-section {
        margin-bottom: 20px;
    }
    
    .ld-debug-content h4 {
        margin: 0 0 10px;
        color: #23282d;
    }
    
    .debug-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .debug-table th,
    .debug-table td {
        padding: 8px;
        text-align: left;
        border: 1px solid #ddd;
    }
    
    .debug-table th {
        background-color: #f1f1f1;
        font-weight: bold;
    }
    
    /* RTL Support */
    html[dir="rtl"] .debug-table th,
    html[dir="rtl"] .debug-table td {
        text-align: right;
    }
</style>
