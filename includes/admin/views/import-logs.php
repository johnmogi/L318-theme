<div class="wrap ccr-admin-page">
    <h1><?php _e('Import Logs', 'hello-child'); ?></h1>
    
    <div class="ccr-admin-tabs">
        <a href="?page=registration-codes" class="nav-tab"><?php _e('Manage Codes', 'hello-child'); ?></a>
        <a href="?page=teacher-dashboard" class="nav-tab"><?php _e('Teacher Dashboard', 'hello-child'); ?></a>
        <a href="?page=registration-codes&tab=import-logs" class="nav-tab nav-tab-active"><?php _e('Import Logs', 'hello-child'); ?></a>
    </div>
    
    <div class="ccr-admin-container">
        <div class="ccr-admin-section ccr-import-logs">
            <h2><?php _e('Import History', 'hello-child'); ?></h2>
            
            <?php if (empty($logs)): ?>
                <div class="ccr-no-results">
                    <p><?php _e('No import logs found.', 'hello-child'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'hello-child'); ?></th>
                            <th><?php _e('Group', 'hello-child'); ?></th>
                            <th><?php _e('User', 'hello-child'); ?></th>
                            <th><?php _e('Status', 'hello-child'); ?></th>
                            <th><?php _e('Started', 'hello-child'); ?></th>
                            <th><?php _e('Completed', 'hello-child'); ?></th>
                            <th><?php _e('Imported', 'hello-child'); ?></th>
                            <th><?php _e('Skipped', 'hello-child'); ?></th>
                            <th><?php _e('Total', 'hello-child'); ?></th>
                            <th><?php _e('Actions', 'hello-child'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $user = get_userdata($log->user_id);
                            $user_name = $user ? $user->display_name : 'Unknown';
                            
                            $status_class = $log->status === 'completed' ? 'ccr-status-active' : 'ccr-status-processing';
                            ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td><?php echo esc_html($log->group_name); ?></td>
                                <td><?php echo esc_html($user_name); ?></td>
                                <td><span class="ccr-status <?php echo $status_class; ?>"><?php echo esc_html($log->status); ?></span></td>
                                <td><?php echo esc_html($log->started_at); ?></td>
                                <td><?php echo esc_html($log->completed_at); ?></td>
                                <td><?php echo esc_html($log->imported); ?></td>
                                <td><?php echo esc_html($log->skipped); ?></td>
                                <td><?php echo esc_html($log->total); ?></td>
                                <td>
                                    <?php if (!empty($log->group_name)): ?>
                                    <a href="?page=registration-codes&group=<?php echo urlencode($log->group_name); ?>" class="button button-small"><?php _e('View Codes', 'hello-child'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <span class="displaying-num">
                                <?php printf(_n('%s item', '%s items', $total_logs, 'hello-child'), number_format_i18n($total_logs)); ?>
                            </span>
                            
                            <span class="pagination-links">
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg(array('paged' => '%#%', 'tab' => 'import-logs')),
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
