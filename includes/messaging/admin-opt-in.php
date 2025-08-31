<?php
/**
 * Admin/Teacher Opt-in Interface for Toast Messages
 * Allows privileged users to choose whether they want to see toast notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add opt-in setting to user profile page
 */
function lilac_add_message_opt_in_field($user) {
    // Only show for privileged users
    $privileged_roles = array('administrator', 'teacher', 'instructor', 'group_leader', 'course_author', 'editor', 'author');
    $user_roles = $user->roles;
    
    $is_privileged = false;
    foreach ($privileged_roles as $role) {
        if (in_array($role, $user_roles)) {
            $is_privileged = true;
            break;
        }
    }
    
    // Also check capabilities
    $has_privileged_caps = user_can($user, 'manage_options') || 
                          user_can($user, 'edit_courses') || 
                          user_can($user, 'edit_lessons') ||
                          user_can($user, 'edit_others_posts');
    
    if (!$is_privileged && !$has_privileged_caps) {
        return;
    }
    
    $opt_in = get_user_meta($user->ID, 'lilac_message_opt_in', true);
    ?>
    <h3>הגדרות הודעות אתר</h3>
    <table class="form-table">
        <tr>
            <th><label for="lilac_message_opt_in">הצג הודעות קופצות באתר</label></th>
            <td>
                <input type="checkbox" name="lilac_message_opt_in" id="lilac_message_opt_in" value="1" <?php checked($opt_in, '1'); ?> />
                <p class="description">
                    כמשתמש מורשה, אתה לא רואה הודעות קופצות כברירת מחדל. סמן כאן כדי לראות הודעות ברכה, הודעות בונוס וכו'.
                    <br>
                    <strong>כולל:</strong>
                    <ul style="margin-top: 5px;">
                        <li>• הודעות ברכה למבקרים חדשים</li>
                        <li>• הודעות בונוס לאחר רכישה</li>
                        <li>• התראות מערכת כלליות</li>
                        <li>• הודעות פעילות וזמן חיבור</li>
                    </ul>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save the opt-in setting
 */
function lilac_save_message_opt_in_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    $opt_in = isset($_POST['lilac_message_opt_in']) ? '1' : '';
    update_user_meta($user_id, 'lilac_message_opt_in', $opt_in);
    
    // Also update localStorage flag for JavaScript systems
    if ($opt_in) {
        // Set a flag that JavaScript can read
        update_user_meta($user_id, 'lilac_message_opt_in_js', 'true');
    } else {
        delete_user_meta($user_id, 'lilac_message_opt_in_js');
    }
}

/**
 * Add JavaScript to set localStorage flag for current user
 */
function lilac_add_opt_in_js() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $opt_in = get_user_meta($current_user->ID, 'lilac_message_opt_in', true);
    
    ?>
    <script type="text/javascript">
        // Set localStorage flag for JavaScript message systems
        <?php if ($opt_in): ?>
        localStorage.setItem('lilac_message_opt_in', 'true');
        <?php else: ?>
        localStorage.removeItem('lilac_message_opt_in');
        <?php endif; ?>
        
        // Also set global variable for immediate access
        window.lilacMessageOptIn = <?php echo $opt_in ? 'true' : 'false'; ?>;
    </script>
    <?php
}

/**
 * Add admin menu for message opt-in management
 */
function lilac_add_message_opt_in_admin_menu() {
    // Only show for users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    add_options_page(
        __('הגדרות הודעות אתר', 'textdomain'),
        __('הודעות אתר', 'textdomain'),
        'manage_options',
        'lilac-message-opt-in',
        'lilac_message_opt_in_admin_page'
    );
}

/**
 * Admin page for managing message opt-in settings
 */
function lilac_message_opt_in_admin_page() {
    // Handle bulk actions
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_enable') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'lilac_bulk_opt_in')) {
            wp_die(__('Security check failed', 'textdomain'));
        }
        
        $privileged_roles = array('administrator', 'teacher', 'instructor', 'group_leader', 'course_author', 'editor', 'author');
        $users = get_users(array('role__in' => $privileged_roles));
        
        $count = 0;
        foreach ($users as $user) {
            update_user_meta($user->ID, 'lilac_message_opt_in', '1');
            $count++;
        }
        
        echo '<div class="notice notice-success"><p>' . sprintf(__('הופעלו הודעות עבור %d משתמשים מורשים.', 'textdomain'), $count) . '</p></div>';
    }
    
    // Get privileged users and their opt-in status
    $privileged_roles = array('administrator', 'teacher', 'instructor', 'group_leader', 'course_author', 'editor', 'author');
    $users = get_users(array('role__in' => $privileged_roles));
    
    ?>
    <div class="wrap">
        <h1><?php _e('הגדרות הודעות אתר - משתמשים מורשים', 'textdomain'); ?></h1>
        
        <p><?php _e('משתמשים מורשים (מנהלים, מורים, עורכים) לא רואים הודעות קופצות כברירת מחדל. כאן ניתן לנהל את ההגדרות שלהם.', 'textdomain'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('lilac_bulk_opt_in'); ?>
            <input type="hidden" name="action" value="bulk_enable">
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('הפעל הודעות לכל המשתמשים המורשים', 'textdomain'); ?>">
            </p>
        </form>
        
        <h2><?php _e('סטטוס משתמשים', 'textdomain'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('משתמש', 'textdomain'); ?></th>
                    <th><?php _e('תפקיד', 'textdomain'); ?></th>
                    <th><?php _e('הודעות מופעלות', 'textdomain'); ?></th>
                    <th><?php _e('פעולות', 'textdomain'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $opt_in = get_user_meta($user->ID, 'lilac_message_opt_in', true); ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong><br>
                            <small><?php echo esc_html($user->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                        <td>
                            <?php if ($opt_in): ?>
                                <span style="color: green;">✓ <?php _e('כן', 'textdomain'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('לא', 'textdomain'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button button-small">
                                <?php _e('ערוך פרופיל', 'textdomain'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Hook into WordPress
add_action('show_user_profile', 'lilac_add_message_opt_in_field');
add_action('edit_user_profile', 'lilac_add_message_opt_in_field');
add_action('personal_options_update', 'lilac_save_message_opt_in_field');
add_action('edit_user_profile_update', 'lilac_save_message_opt_in_field');
add_action('wp_footer', 'lilac_add_opt_in_js');
add_action('admin_menu', 'lilac_add_message_opt_in_admin_menu');
