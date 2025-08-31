<div class="wrap ccr-admin-page">
    <h1><?php _e('Registration Codes Management', 'hello-child'); ?></h1>
    
    <div class="ccr-admin-tabs">
        <a href="?page=registration-codes" class="nav-tab nav-tab-active"><?php _e('Manage Codes', 'hello-child'); ?></a>
        <a href="?page=teacher-dashboard" class="nav-tab"><?php _e('Teacher Dashboard', 'hello-child'); ?></a>
        <a href="?page=registration-codes&tab=import-logs" class="nav-tab"><?php _e('Import Logs', 'hello-child'); ?></a>
    </div>
    
    <div class="ccr-admin-container">
        <div class="ccr-admin-section ccr-generate-codes">
            <h2><?php _e('Generate New Codes', 'hello-child'); ?></h2>
            
            <form id="ccr-generate-form" class="ccr-form">
                <div class="ccr-form-row">
                    <label for="ccr-code-count"><?php _e('Number of Codes:', 'hello-child'); ?></label>
                    <select id="ccr-code-count" name="count">
                        <option value="10">10</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="custom"><?php _e('Custom', 'hello-child'); ?></option>
                    </select>
                    <input type="number" id="ccr-custom-count" name="custom_count" min="1" max="1000" value="10" style="display:none;">
                </div>
                
                <div class="ccr-form-row">
                    <label for="ccr-group-name"><?php _e('Group Name:', 'hello-child'); ?></label>
                    <input type="text" id="ccr-group-name" name="group" list="ccr-groups" placeholder="<?php _e('e.g., School Name or Class', 'hello-child'); ?>">
                    <datalist id="ccr-groups">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="ccr-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Generate Codes', 'hello-child'); ?></button>
                    <span class="spinner"></span>
                </div>
            </form>
            
            <div id="ccr-generate-results" style="display:none;">
                <h3><?php _e('Generated Codes', 'hello-child'); ?></h3>
                <div class="ccr-results-info"></div>
                <textarea id="ccr-generated-codes" readonly rows="10"></textarea>
                <div class="ccr-results-actions">
                    <button id="ccr-copy-codes" class="button"><?php _e('Copy to Clipboard', 'hello-child'); ?></button>
                    <button id="ccr-download-codes" class="button"><?php _e('Download CSV', 'hello-child'); ?></button>
                </div>
            </div>
        </div>
        
        <div class="ccr-admin-section ccr-import-export">
            <h2><?php _e('Import/Export Codes', 'hello-child'); ?></h2>
            
            <div class="ccr-tabs">
                <div class="ccr-tab-nav">
                    <button class="ccr-tab-btn active" data-tab="export"><?php _e('Export', 'hello-child'); ?></button>
                    <button class="ccr-tab-btn" data-tab="import"><?php _e('Import', 'hello-child'); ?></button>
                </div>
                
                <div class="ccr-tab-content active" id="ccr-tab-export">
                    <form id="ccr-export-form" class="ccr-form">
                        <div class="ccr-form-row">
                            <label for="ccr-export-group"><?php _e('Filter by Group:', 'hello-child'); ?></label>
                            <select id="ccr-export-group" name="group">
                                <option value=""><?php _e('All Groups', 'hello-child'); ?></option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo esc_attr($group); ?>"><?php echo esc_html($group); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="ccr-form-row">
                            <label for="ccr-export-status"><?php _e('Filter by Status:', 'hello-child'); ?></label>
                            <select id="ccr-export-status" name="status">
                                <option value=""><?php _e('All Statuses', 'hello-child'); ?></option>
                                <option value="active"><?php _e('Active', 'hello-child'); ?></option>
                                <option value="used"><?php _e('Used', 'hello-child'); ?></option>
                            </select>
                        </div>
                        
                        <div class="ccr-form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Export Codes', 'hello-child'); ?></button>
                            <span class="spinner"></span>
                        </div>
                    </form>
                </div>
                
                <div class="ccr-tab-content" id="ccr-tab-import">
                    <form id="ccr-import-form" class="ccr-form" enctype="multipart/form-data">
                        <div class="ccr-form-row">
                            <label for="ccr-import-file"><?php _e('CSV File:', 'hello-child'); ?></label>
                            <input type="file" id="ccr-import-file" name="import_file" accept=".csv">
                            <p class="description"><?php _e('CSV format: Code,Group (optional)', 'hello-child'); ?></p>
                        </div>
                        
                        <div class="ccr-form-row">
                            <label for="ccr-import-group"><?php _e('Default Group Name:', 'hello-child'); ?></label>
                            <input type="text" id="ccr-import-group" name="group" placeholder="<?php _e('Used if not specified in CSV', 'hello-child'); ?>">
                        </div>
                        
                        <div class="ccr-form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Import Codes', 'hello-child'); ?></button>
                            <span class="spinner"></span>
                        </div>
                    </form>
                    
                    <div id="ccr-import-results" style="display:none;">
                        <div class="ccr-results-info"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="ccr-admin-section ccr-codes-list">
        <h2><?php _e('Existing Codes', 'hello-child'); ?></h2>
        
        <div class="ccr-filters">
            <form method="get" class="ccr-filter-form">
                <input type="hidden" name="page" value="registration-codes">
                
                <div class="ccr-filter-row">
                    <label for="ccr-filter-group"><?php _e('Group:', 'hello-child'); ?></label>
                    <select id="ccr-filter-group" name="group">
                        <option value=""><?php _e('All Groups', 'hello-child'); ?></option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group); ?>" <?php selected($filter_group, $group); ?>><?php echo esc_html($group); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="ccr-filter-status"><?php _e('Status:', 'hello-child'); ?></label>
                    <select id="ccr-filter-status" name="status">
                        <option value=""><?php _e('All Statuses', 'hello-child'); ?></option>
                        <option value="active" <?php selected($filter_status, 'active'); ?>><?php _e('Active', 'hello-child'); ?></option>
                        <option value="used" <?php selected($filter_status, 'used'); ?>><?php _e('Used', 'hello-child'); ?></option>
                    </select>
                    
                    <button type="submit" class="button"><?php _e('Filter', 'hello-child'); ?></button>
                    <a href="?page=registration-codes" class="button"><?php _e('Reset', 'hello-child'); ?></a>
                </div>
            </form>
        </div>
        
        <?php if (empty($codes)): ?>
            <div class="ccr-no-results">
                <p><?php _e('No registration codes found.', 'hello-child'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Code', 'hello-child'); ?></th>
                        <th><?php _e('Group', 'hello-child'); ?></th>
                        <th><?php _e('Created By', 'hello-child'); ?></th>
                        <th><?php _e('Created At', 'hello-child'); ?></th>
                        <th><?php _e('Status', 'hello-child'); ?></th>
                        <th><?php _e('Used By', 'hello-child'); ?></th>
                        <th><?php _e('Used At', 'hello-child'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codes as $code): ?>
                        <?php
                        $created_by = get_userdata($code->created_by);
                        $created_by_name = $created_by ? $created_by->display_name : 'Unknown';
                        
                        $used_by = !empty($code->used_by) ? get_userdata($code->used_by) : null;
                        $used_by_name = $used_by ? $used_by->display_name : '';
                        
                        $status_class = $code->status === 'active' ? 'ccr-status-active' : 'ccr-status-used';
                        ?>
                        <tr>
                            <td><?php echo esc_html($code->code); ?></td>
                            <td><?php echo esc_html($code->group_name); ?></td>
                            <td><?php echo esc_html($created_by_name); ?></td>
                            <td><?php echo esc_html($code->created_at); ?></td>
                            <td><span class="ccr-status <?php echo $status_class; ?>"><?php echo esc_html($code->status); ?></span></td>
                            <td><?php echo esc_html($used_by_name); ?></td>
                            <td><?php echo esc_html($code->used_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(_n('%s item', '%s items', $total_codes, 'hello-child'), number_format_i18n($total_codes)); ?>
                        </span>
                        
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if (current_user_can('manage_options') && !empty($groups)): ?>
    <div class="ccr-admin-section ccr-group-management">
        <h2><?php _e('Group Management', 'hello-child'); ?></h2>
        
        <div class="ccr-group-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Group Name', 'hello-child'); ?></th>
                        <th><?php _e('Total Codes', 'hello-child'); ?></th>
                        <th><?php _e('Active Codes', 'hello-child'); ?></th>
                        <th><?php _e('Used Codes', 'hello-child'); ?></th>
                        <th><?php _e('Actions', 'hello-child'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    global $wpdb;
                    foreach ($groups as $group): 
                        // Count total codes in the group
                        $total_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}registration_codes WHERE group_name = %s",
                            $group
                        ));
                        
                        // Count active codes in the group
                        $active_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}registration_codes WHERE group_name = %s AND status = 'active'",
                            $group
                        ));
                        
                        // Count used codes in the group
                        $used_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}registration_codes WHERE group_name = %s AND status = 'used'",
                            $group
                        ));
                    ?>
                        <tr>
                            <td><?php echo esc_html($group); ?></td>
                            <td><?php echo esc_html($total_count); ?></td>
                            <td><?php echo esc_html($active_count); ?></td>
                            <td><?php echo esc_html($used_count); ?></td>
                            <td>
                                <button class="button button-small ccr-delete-group" data-group="<?php echo esc_attr($group); ?>"><?php _e('Delete Group', 'hello-child'); ?></button>
                                <a href="?page=registration-codes&group=<?php echo urlencode($group); ?>" class="button button-small"><?php _e('View Codes', 'hello-child'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Group deletion confirmation modal -->
        <div id="ccr-delete-group-modal" class="ccr-modal" style="display:none;">
            <div class="ccr-modal-content">
                <span class="ccr-modal-close">&times;</span>
                <h3><?php _e('Delete Group', 'hello-child'); ?></h3>
                <p class="ccr-warning"><?php _e('Are you sure you want to delete this group? This will permanently delete all registration codes in the group.', 'hello-child'); ?></p>
                <p><strong><?php _e('Group', 'hello-child'); ?>:</strong> <span id="ccr-delete-group-name"></span></p>
                <div class="ccr-modal-actions">
                    <button id="ccr-confirm-delete-group" class="button button-primary button-large"><?php _e('Yes, Delete Group', 'hello-child'); ?></button>
                    <button class="button button-large ccr-modal-cancel"><?php _e('Cancel', 'hello-child'); ?></button>
                    <span class="spinner"></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
