<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$class_filter = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';

// Get pagination parameters
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get codes with filters
$codes = $registration_codes->get_codes($class_filter, $status_filter, $role_filter, $per_page, $offset);
$total_items = $registration_codes->count_codes($class_filter, $status_filter, $role_filter);

// Get all available roles for the filter dropdown
$available_roles = $wpdb->get_col("SELECT DISTINCT role FROM {$wpdb->prefix}registration_codes WHERE role != '' ORDER BY role");
$total_pages = ceil($total_items / $per_page);

// Get all classes for filter dropdown
$classes = $registration_codes->get_groups(); // TODO: Rename method to get_classes() in future update
?>

<div class="registration-codes-filters">
    <form method="get" action="">
        <input type="hidden" name="page" value="registration-codes" />
        <input type="hidden" name="tab" value="manage" />
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="filter-group" class="screen-reader-text">סנן לפי כיתה</label>
                <select name="group" id="filter-group">
                    <option value="">כל הכיתות</option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo esc_attr($class); ?>" <?php selected($class_filter, $class); ?>>
                            <?php echo esc_html($class); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="filter-status" class="screen-reader-text">סנן לפי סטטוס</label>
                <select name="status" id="filter-status">
                    <option value="">כל הסטטוסים</option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>>פעיל</option>
                    <option value="used" <?php selected($status_filter, 'used'); ?>>בשימוש</option>
                </select>
                
                <label for="filter-role" class="screen-reader-text">סנן לפי מסלול לימוד</label>
                <select name="role" id="filter-role">
                    <option value="">כל המסלולים</option>
                    <?php foreach ($available_roles as $role) : ?>
                        <option value="<?php echo esc_attr($role); ?>" <?php selected($role_filter, $role); ?>>
                            <?php echo esc_html(ucfirst($role)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="סנן">
                
                <?php if (!empty($class_filter) || !empty($status_filter)) : ?>
                    <a href="?page=registration-codes&tab=manage" class="button">
                        איפוס
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php 
                    $items_text = $total_items === 1 ? 'פריט אחד' : '%s פריטים';
                    printf($items_text, number_format_i18n($total_items)); ?>
                </span>
                
                <?php if ($total_pages > 1) : ?>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <form method="post" action="" id="codes-form">
        <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </td>
                    <th scope="col" class="manage-column column-code column-primary">
                        קוד
                    </th>
                    <th scope="col" class="manage-column column-group">
                        כיתה
                    </th>
                    <th scope="col" class="manage-column column-role">
                        מסלול לימוד
                    </th>
                    <th scope="col" class="manage-column column-status">
                        סטטוס
                    </th>
                    <th scope="col" class="manage-column column-created">
                        נוצר בתאריך
                    </th>
                    <th scope="col" class="manage-column column-used">
                        בשימוש
                    </th>
                </tr>
            </thead>
            
            <tbody id="the-list">
                <?php if (!empty($codes)) : ?>
                    <?php foreach ($codes as $code) : 
                        // Handle both array and object access
                        $code_id = is_array($code) ? ($code['id'] ?? 0) : ($code->id ?? 0);
                        $code_value = is_array($code) ? ($code['code'] ?? '') : ($code->code ?? '');
                        $group_name = is_array($code) ? ($code['group_name'] ?? '') : ($code->group_name ?? '');
                        $role = is_array($code) ? ($code['role'] ?? 'subscriber') : ($code->role ?? 'subscriber');
                        $created_at = is_array($code) ? ($code['created_at'] ?? '') : ($code->created_at ?? '');
                        $created_by = is_array($code) ? ($code['created_by'] ?? 0) : ($code->created_by ?? 0);
                        $used_at = is_array($code) ? ($code['used_at'] ?? '') : ($code->used_at ?? '');
                        $used_by = is_array($code) ? ($code['used_by'] ?? 0) : ($code->used_by ?? 0);
                        $is_used = is_array($code) ? (!empty($code['is_used'])) : (!empty($code->is_used));
                        
                        $user = $used_by ? get_user_by('id', $used_by) : null;
                        $status_class = $is_used ? 'status-used' : 'status-active';
                        $status_text = $is_used ? 'בשימוש' : 'פעיל';
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="codes[]" value="<?php echo esc_attr($code_id); ?>" />
                            </th>
                            <td class="code column-code column-primary" data-colname="<?php esc_attr_e('Code', 'registration-codes'); ?>">
                                <strong><?php echo esc_html($code_value); ?></strong>
                                <div class="row-actions">
                                    <span class="copy">
                                        <a href="#" class="copy-code" data-code="<?php echo esc_attr($code_value); ?>">
                                            <?php _e('Copy', 'registration-codes'); ?>
                                        </a> | 
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="delete-code" data-id="<?php echo esc_attr($code_id); ?>">
                                            <?php _e('Delete', 'registration-codes'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="class column-class" data-colname="<?php esc_attr_e('Class', 'registration-codes'); ?>">
                                <?php echo $group_name ? esc_html($group_name) : '—'; ?>
                            </td>
                            <td class="role column-role" data-colname="<?php esc_attr_e('Study', 'registration-codes'); ?>">
                                <?php 
                                if (!empty($role)) {
                                    $roles = wp_roles();
                                    echo isset($roles->role_names[$role]) ? esc_html($roles->role_names[$role]) : esc_html(ucfirst($role));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'registration-codes'); ?>">
                                <span class="status-indicator <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </td>
                            <td class="created column-created" data-colname="<?php esc_attr_e('Created', 'registration-codes'); ?>">
                                <?php 
                                $created_date = !empty($created_at) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at)) : '';
                                echo $created_date ? esc_html($created_date) : '—';
                                ?>
                                <br>
                                <small>
                                    <?php 
                                    $creator = $created_by ? get_user_by('id', $created_by) : null;
                                    echo $creator ? esc_html($creator->display_name) : __('System', 'registration-codes');
                                    ?>
                                </small>
                            </td>
                            <td class="used column-used" data-colname="<?php esc_attr_e('Used', 'registration-codes'); ?>">
                                <?php 
                                if ($is_used && !empty($used_at)) : 
                                    $used_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($used_at));
                                    echo esc_html($used_date);
                                    
                                    if ($used_by && ($user = get_user_by('id', $used_by))) : ?>
                                        <br><small>
                                            <?php 
                                            echo esc_html($user->display_name);
                                            if (!empty($user->user_email)) {
                                                echo ' (' . esc_html($user->user_email) . ')';
                                            }
                                            ?>
                                        </small>
                                    <?php endif;
                                else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">
                            לא נמצאו קודי רישום.
                            <a href="?page=registration-codes&tab=generate">
                                צור קודים חדשים
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-2" />
                    </td>
                    <th scope="col" class="manage-column column-code column-primary">
                        <?php _e('Code', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-class">
                        <?php _e('Class', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-role">
                        <?php _e('Study', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('Status', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-created">
                        <?php _e('Created', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-used">
                        <?php _e('Used', 'registration-codes'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-bottom">
                    <option value="-1">פעולות קבוצתיות</option>
                    <option value="delete">מחק</option>
                    <option value="export">ייצא</option>
                </select>
                <input type="submit" class="button action" value="החל">
            </div>
            
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $total_items, 'registration-codes'), number_format_i18n($total_items)); ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Confirmation modal for delete -->
<div id="delete-confirm-modal" class="registration-codes-modal" style="display: none;">
    <div class="registration-codes-modal-content">
        <div class="registration-codes-modal-header">
            <h3>אישור מחיקה</h3>
            <button type="button" class="registration-codes-modal-close">&times;</button>
        </div>
        <div class="registration-codes-modal-body">
            <p>האם אתה בטוח שברצונך למחוק את הקודים הנבחרים? פעולה זו לא ניתנת לביטול.</p>
        </div>
        <div class="registration-codes-modal-footer">
            <button type="button" class="button registration-codes-modal-cancel">
                ביטול
            </button>
            <button type="button" class="button button-primary registration-codes-modal-confirm" data-action="delete">
                מחק
            </button>
        </div>
    </div>
</div>
