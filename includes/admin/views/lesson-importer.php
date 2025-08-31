<?php
/**
 * Lesson Importer Admin View
 *
 * @var array $courses Array of course objects
 */

// Process import results
$imported = isset($_GET['imported']) ? (int)$_GET['imported'] : 0;
$created = isset($_GET['created']) ? (int)$_GET['created'] : 0;
$updated = isset($_GET['updated']) ? (int)$_GET['updated'] : 0;
$skipped = isset($_GET['skipped']) ? (int)$_GET['skipped'] : 0;
$errors = isset($_GET['errors']) ? explode('|', urldecode($_GET['errors'])) : [];
?>
<div class="wrap ccr-admin-page">
    <h1><?php _e('LearnDash Lesson & Topic Importer', 'hello-child'); ?></h1>
    
    <?php if (isset($_GET['imported'])): ?>
        <div class="notice notice-<?php echo $skipped > 0 ? 'warning' : 'success'; ?> is-dismissible">
            <p>
                <?php 
                if ($imported > 0) {
                    echo sprintf(_n(
                        'Successfully imported %d item.",
                        'Successfully imported %d items.',
                        $imported,
                        'hello-child'
                    ), $imported);
                    
                    if ($created > 0 || $updated > 0) {
                        $details = [];
                        if ($created > 0) {
                            $details[] = sprintf(_n('%d created', '%d created', $created, 'hello-child'), $created);
                        }
                        if ($updated > 0) {
                            $details[] = sprintf(_n('%d updated', '%d updated', $updated, 'hello-child'), $updated);
                        }
                        echo ' (' . implode(', ', $details) . ')';
                    }
                }
                
                if ($skipped > 0) {
                    echo '<br>' . sprintf(_n(
                        '%d item was skipped.',
                        '%d items were skipped.',
                        $skipped,
                        'hello-child'
                    ), $skipped);
                }
                ?>
            </p>
            
            <?php if (!empty($errors)): ?>
                <ul style="margin: 0.5em 0 0 1.5em; list-style-type: disc;">
                    <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                    <?php if (count($errors) > 10): ?>
                        <li><?php echo sprintf(__('... and %d more errors', 'hello-child'), count($errors) - 10); ?></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Import Lessons & Topics', 'hello-child'); ?></h2>
        <p><?php _e('Upload a CSV file containing your lessons and topics to import them into LearnDash.', 'hello-child'); ?></p>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ccr_import_lessons">
            <?php wp_nonce_field('ccr_import_lessons_nonce', 'ccr_import_lessons_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_file"><?php _e('CSV File', 'hello-child'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        <p class="description">
                            <?php _e('Upload a properly formatted CSV file. ', 'hello-child'); ?>
                            <a href="<?php echo admin_url('admin-post.php?action=ccr_download_lesson_template'); ?>">
                                <?php _e('Download template', 'hello-child'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="import_mode"><?php _e('Import Mode', 'hello-child'); ?></label>
                    </th>
                    <td>
                        <select name="import_mode" id="import_mode">
                            <option value="create"><?php _e('Create new items only', 'hello-child'); ?></option>
                            <option value="update"><?php _e('Update existing and create new', 'hello-child'); ?></option>
                            <option value="replace"><?php _e('Delete all existing and import new', 'hello-child'); ?></option>
                        </select>
                        <p class="description">
                            <?php _e('Choose how to handle existing content during import.', 'hello-child'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Import', 'hello-child'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2><?php _e('CSV Format', 'hello-child'); ?></h2>
        <p><?php _e('Your CSV file should follow this format:', 'hello-child'); ?></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Column', 'hello-child'); ?></th>
                    <th><?php _e('Description', 'hello-child'); ?></th>
                    <th><?php _e('Required', 'hello-child'); ?></th>
                    <th><?php _e('Example', 'hello-child'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>course_id</td>
                    <td><?php _e('ID of the course', 'hello-child'); ?></td>
                    <td><?php _e('Yes', 'hello-child'); ?></td>
                    <td>123</td>
                </tr>
                <tr>
                    <td>course_title</td>
                    <td><?php _e('Title of the course (for reference)', 'hello-child'); ?></td>
                    <td><?php _e('No', 'hello-child'); ?></td>
                    <td>Introduction to Driving</td>
                </tr>
                <tr>
                    <td>lesson_id</td>
                    <td><?php _e('ID of the lesson (leave empty for topics)', 'hello-child'); ?></td>
                    <td><?php _e('For lessons', 'hello-child'); ?></td>
                    <td>456</td>
                </tr>
                <tr>
                    <td>lesson_title</td>
                    <td><?php _e('Title of the lesson', 'hello-child'); ?></td>
                    <td><?php _e('For lessons', 'hello-child'); ?></td>
                    <td>Basic Rules of the Road</td>
                </tr>
                <tr>
                    <td>lesson_content</td>
                    <td><?php _e('Content of the lesson (HTML allowed)', 'hello-child'); ?></td>
                    <td><?php _e('No', 'hello-child'); ?></td>
                    <td>&lt;p&gt;Learn the basic rules...&lt;/p&gt;</td>
                </tr>
                <tr>
                    <td>topic_id</td>
                    <td><?php _e('ID of the topic (leave empty for lessons)', 'hello-child'); ?></td>
                    <td><?php _e('For topics', 'hello-child'); ?></td>
                    <td>789</td>
                </tr>
                <tr>
                    <td>topic_title</td>
                    <td><?php _e('Title of the topic', 'hello-child'); ?></td>
                    <td><?php _e('For topics', 'hello-child'); ?></td>
                    <td>Speed Limits</td>
                </tr>
                <tr>
                    <td>topic_content</td>
                    <td><?php _e('Content of the topic (HTML allowed)', 'hello-child'); ?></td>
                    <td><?php _e('No', 'hello-child'); ?></td>
                    <td>&lt;p&gt;Speed limits vary by area...&lt;/p&gt;</td>
                </tr>
                <tr>
                    <td>lesson_order / topic_order</td>
                    <td><?php _e('Order in which items appear', 'hello-child'); ?></td>
                    <td><?php _e('No', 'hello-child'); ?></td>
                    <td>1, 2, 3, ...</td>
                </tr>
                <tr>
                    <td>video_enabled</td>
                    <td><?php _e('Whether to show video (1 or 0)', 'hello-child'); ?></td>
                    <td><?php _e('No', 'hello-child'); ?></td>
                    <td>1</td>
                </tr>
                <tr>
                    <td>video_url</td>
                    <td><?php _e('URL of the video to embed', 'hello-child'); ?></td>
                    <td><?php _e('If video_enabled=1', 'hello-child'); ?></td>
                    <td>https://example.com/video.mp4</td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php _e('Example CSV', 'hello-child'); ?></h3>
        <pre>course_id,course_title,lesson_id,lesson_title,lesson_content,lesson_order,topic_id,topic_title,topic_content,topic_order,video_enabled,video_url
1,Driving Course,101,Road Rules,"Learn the basic rules of the road",1,,,,,,
1,Driving Course,101,Road Rules,,,,201,Speed Limits,"Understand speed limits in different areas",1,1,https://example.com/speed-limits.mp4
1,Driving Course,101,Road Rules,,,,202,Traffic Signs,"Learn common traffic signs",2,1,https://example.com/traffic-signs.mp4
1,Driving Course,102,Vehicle Control,"Learn to control your vehicle",2,,,,,,
1,Driving Course,102,Vehicle Control,,,,203,Steering,"Proper steering techniques",1,0,</pre>
    </div>
</div>

<style>
.ccr-admin-page .card {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #ccd0d4;
}
.ccr-admin-page .form-table th {
    width: 200px;
}
.ccr-admin-page pre {
    background: #f5f5f5;
    padding: 15px;
    overflow: auto;
    max-width: 100%;
}
</style>
