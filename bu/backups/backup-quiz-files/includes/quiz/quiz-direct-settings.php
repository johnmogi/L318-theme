<?php
/**
 * Quiz Direct Settings Toggle
 * 
 * Provides direct URL access to toggle quiz settings without using admin forms
 * This helps avoid issues with admin-post.php form submissions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Initialize the direct settings toggle functionality
 */
function lilac_init_quiz_direct_settings() {
    // Only run in admin
    if (!is_admin()) return;
    
    // Only for users who can manage options
    if (!current_user_can('manage_options')) return;
    
    // Listen for our direct toggle action
    add_action('admin_init', 'lilac_quiz_direct_settings_handler');
    
    // Add the quick toggle links to the quiz extensions page
    add_action('admin_footer', 'lilac_quiz_direct_settings_add_links');
}

/**
 * Handle direct settings toggle requests
 */
function lilac_quiz_direct_settings_handler() {
    // Check if this is our toggle request
    if (!isset($_GET['lilac_quiz_setting']) || !isset($_GET['quiz_id']) || !isset($_GET['value'])) {
        return;
    }
    
    // For debugging
    error_log('Lilac Quiz Direct Settings: Attempting to update ' . $_GET['lilac_quiz_setting'] . ' for quiz ' . $_GET['quiz_id'] . ' to ' . $_GET['value']);
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'lilac_toggle_quiz_setting')) {
        wp_die('Security check failed');
    }
    
    // Get parameters
    $setting = sanitize_key($_GET['lilac_quiz_setting']);
    $quiz_id = intval($_GET['quiz_id']);
    $value = ($_GET['value'] === '1');
    
    // Allowed settings
    $allowed_settings = array(
        'disable_hints', 
        'force_hint_mode', 
        'rich_sidebar', 
        'disable_topbar'
    );
    
    // Validate setting
    if (!in_array($setting, $allowed_settings)) {
        wp_die('Invalid setting');
    }
    
    // Get current settings
    $settings = get_option('ccr_quiz_extensions_settings', array());
    
    // Make sure quiz settings exist
    if (!isset($settings[$quiz_id])) {
        $settings[$quiz_id] = array();
    }
    
    // Update setting
    $settings[$quiz_id][$setting] = $value;
    
    // Save settings
    update_option('ccr_quiz_extensions_settings', $settings);
    
    // Redirect back to quiz extensions page
    wp_redirect(admin_url('admin.php?page=quiz-extensions&updated=1'));
    exit;
}

/**
 * Add direct toggle links to the quiz extensions page
 */
function lilac_quiz_direct_settings_add_links() {
    // Only run on the quiz extensions page
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'toplevel_page_quiz-extensions') {
        return;
    }
    
    // Get current settings
    $settings = get_option('ccr_quiz_extensions_settings', array());
    
    // Add JavaScript to create toggle links
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add a helpful message at the top of the page
        if ($('.notice-success').length > 0) {
            $('.notice-success').after('<div class="notice notice-info is-dismissible"><p><strong>Quick Tip:</strong> Having trouble with the checkboxes? Try using the direct action buttons below each setting instead.</p></div>');
        }
        
        // Add direct action buttons for all settings
        $('.widefat.fixed.striped tr').each(function() {
            var $row = $(this);
            var quizTitle = $row.find('td').first().text();
            var quizId = $row.find('input[type="checkbox"]').first().attr('name');
            
            if (!quizId) return;
            
            // Extract quiz ID from the name attribute (e.g., quiz_extensions[1234][setting])
            quizId = quizId.match(/quiz_extensions\[(\d+)\]/);
            if (!quizId || !quizId[1]) return;
            
            quizId = quizId[1];
            
            // Create nonce once for all links
            var nonceUrl = '<?php echo wp_create_nonce("lilac_toggle_quiz_setting"); ?>';
            var baseUrl = '<?php echo admin_url("admin.php?page=quiz-extensions"); ?>';
            
            // Add toggle links for all settings
            addToggleLinks('disable_hints', 'Disable Hints', 'Enable Hints');
            addToggleLinks('force_hint_mode', 'Enable Force Hint', 'Disable Force Hint');
            addToggleLinks('disable_topbar', 'Hide Top Bar', 'Show Top Bar');
            addToggleLinks('rich_sidebar', 'Enable Sidebar', 'Disable Sidebar');
            
            // Helper function to add toggle links for a setting
            function addToggleLinks(setting, enableText, disableText) {
                var $cell = $row.find('input[name="quiz_extensions[' + quizId + '][' + setting + ']"]').closest('td');
                var isChecked = $row.find('input[name="quiz_extensions[' + quizId + '][' + setting + ']"]').is(':checked');
                
                var enableLink = baseUrl + '&lilac_quiz_setting=' + setting + '&quiz_id=' + quizId + '&value=1&_wpnonce=' + nonceUrl;
                var disableLink = baseUrl + '&lilac_quiz_setting=' + setting + '&quiz_id=' + quizId + '&value=0&_wpnonce=' + nonceUrl;
                
                // Add the links with clear labels
                $cell.append('<div class="quick-actions" style="margin-top:4px;font-size:11px;">' + 
                    (!isChecked ? '<a href="' + enableLink + '" class="button button-small" style="margin-right:4px;background:#e7f5f9;">' + enableText + '</a>' : '') +
                    (isChecked ? '<a href="' + disableLink + '" class="button button-small" style="margin-right:4px;background:#f9f5e7;">' + disableText + '</a>' : '') +
                '</div>');
            }
        });
        
        // Add help text at the bottom of the table
        $('.widefat.fixed.striped').after('<p class="description" style="margin-top:8px;"><em>Note: If you encounter issues with the checkboxes not saving, use the direct action buttons instead.</em></p>');
    });
    </script>
    <?php
}

// Initialize
lilac_init_quiz_direct_settings();
