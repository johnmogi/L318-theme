<div class="wrap ccr-admin-page">
    <h1><?php _e('Teacher Dashboard', 'hello-child'); ?></h1>
    
    <div class="ccr-admin-tabs">
        <a href="?page=registration-codes" class="nav-tab"><?php _e('Manage Codes', 'hello-child'); ?></a>
        <a href="?page=teacher-dashboard" class="nav-tab nav-tab-active"><?php _e('Teacher Dashboard', 'hello-child'); ?></a>
    </div>
    
    <div class="ccr-admin-container">
        <div class="ccr-admin-section ccr-generate-codes">
            <h2><?php _e('Generate New Codes for Students', 'hello-child'); ?></h2>
            
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
                    <label for="ccr-group-name"><?php _e('Class/Group Name:', 'hello-child'); ?></label>
                    <input type="text" id="ccr-group-name" name="group" list="ccr-groups" placeholder="<?php _e('e.g., Class 10A 2025', 'hello-child'); ?>">
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
        
        <div class="ccr-admin-section ccr-student-list">
            <h2><?php _e('Student Management', 'hello-child'); ?></h2>
            
            <div class="ccr-filters">
                <form method="get" class="ccr-filter-form">
                    <input type="hidden" name="page" value="teacher-dashboard">
                    
                    <div class="ccr-filter-row">
                        <label for="ccr-filter-group"><?php _e('Class/Group:', 'hello-child'); ?></label>
                        <select id="ccr-filter-group" name="group">
                            <option value=""><?php _e('All Classes', 'hello-child'); ?></option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group); ?>" <?php selected($filter_group, $group); ?>><?php echo esc_html($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="button"><?php _e('Filter', 'hello-child'); ?></button>
                        <a href="?page=teacher-dashboard" class="button"><?php _e('Reset', 'hello-child'); ?></a>
                    </div>
                </form>
            </div>
            
            <div class="ccr-export-section">
                <h3><?php _e('Export Student Codes', 'hello-child'); ?></h3>
                <form id="ccr-export-form" class="ccr-form">
                    <div class="ccr-form-row">
                        <label for="ccr-export-group"><?php _e('Class/Group:', 'hello-child'); ?></label>
                        <select id="ccr-export-group" name="group">
                            <option value=""><?php _e('All Classes', 'hello-child'); ?></option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo esc_attr($group); ?>"><?php echo esc_html($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ccr-form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Export Student Codes', 'hello-child'); ?></button>
                        <span class="spinner"></span>
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
                            <th><?php _e('Class/Group', 'hello-child'); ?></th>
                            <th><?php _e('Created At', 'hello-child'); ?></th>
                            <th><?php _e('Status', 'hello-child'); ?></th>
                            <th><?php _e('Student', 'hello-child'); ?></th>
                            <th><?php _e('Registered At', 'hello-child'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $code): ?>
                            <?php
                            $used_by = !empty($code->used_by) ? get_userdata($code->used_by) : null;
                            $used_by_name = $used_by ? $used_by->display_name : '';
                            
                            $status_class = $code->status === 'active' ? 'ccr-status-active' : 'ccr-status-used';
                            ?>
                            <tr>
                                <td><?php echo esc_html($code->code); ?></td>
                                <td><?php echo esc_html($code->group_name); ?></td>
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
    </div>
</div>
