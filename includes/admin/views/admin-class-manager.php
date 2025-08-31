<?php
/**
 * Admin Class Manager View
 *
 * @package Hello_Theme_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>

<div class="wrap school-class-manager admin-view">
    <h1><?php esc_html_e('School Class Manager', 'hello-theme-child'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php esc_html_e('Welcome to School Class Manager - Admin View', 'hello-theme-child'); ?></p>
    </div>
    
    <!-- Teacher Selection Section -->
    <div class="school-class-manager-header">
        <div class="teacher-selection">
            <h2><?php esc_html_e('Select Teacher', 'hello-theme-child'); ?></h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="school-classes-admin">
                <select name="teacher_id" id="teacher-selector">
                    <option value="0"><?php esc_html_e('All Teachers', 'hello-theme-child'); ?></option>
                    <?php 
                    // Debug output
                    if (!empty($teachers)) : 
                        foreach ($teachers as $teacher) : 
                            if (is_object($teacher) && isset($teacher->ID)) : ?>
                                <option value="<?php echo esc_attr($teacher->ID); ?>" <?php selected($selected_teacher_id, $teacher->ID); ?>>
                                    <?php echo esc_html($teacher->display_name); ?>
                                </option>
                    <?php   else : ?>
                                <!-- Invalid teacher data: <?php echo esc_html(print_r($teacher, true)); ?> -->
                    <?php   endif;
                        endforeach;
                    else : ?>
                        <option value="0" disabled><?php esc_html_e('No teachers found', 'hello-theme-child'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filter', 'hello-theme-child'); ?></button>
            </form>
        </div>
    </div>
    
    <!-- Classes Table -->
    <div class="classes-section">
        <h2><?php esc_html_e('Classes', 'hello-theme-child'); ?></h2>
        
        <?php if (empty($classes)) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('No classes found.', 'hello-theme-child'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Class Name', 'hello-theme-child'); ?></th>
                        <th><?php esc_html_e('Teacher', 'hello-theme-child'); ?></th>
                        <th><?php esc_html_e('Students', 'hello-theme-child'); ?></th>
                        <th><?php esc_html_e('Actions', 'hello-theme-child'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class) : ?>
                        <tr>
                            <td><?php echo esc_html($class['name']); ?></td>
                            <td>
                                <?php 
                                if (isset($class['teacher_name'])) {
                                    echo esc_html($class['teacher_name']); 
                                } else {
                                    esc_html_e('N/A', 'hello-theme-child');
                                }
                                ?>
                            </td>
                            <td><?php echo isset($class['student_count']) ? esc_html($class['student_count']) : '0'; ?></td>
                            <td>
                                <a href="?page=school-classes-admin&class_id=<?php echo esc_attr($class['id']); ?>" class="button button-small">
                                    <?php esc_html_e('View Students', 'hello-theme-child'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Students Table (if class is selected) -->
    <?php if ($selected_class_id) : ?>
        <div class="students-section">
            <h2><?php esc_html_e('Students', 'hello-theme-child'); ?></h2>
            
            <?php 
            $students = [];
            if (method_exists($this, 'get_class_students')) {
                $students = $this->get_class_students($selected_class_id);
            }
            ?>
            
            <?php if (empty($students)) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('No students found for this class.', 'hello-theme-child'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'hello-theme-child'); ?></th>
                            <th><?php esc_html_e('Email', 'hello-theme-child'); ?></th>
                            <th><?php esc_html_e('Phone', 'hello-theme-child'); ?></th>
                            <th><?php esc_html_e('Student ID', 'hello-theme-child'); ?></th>
                            <th><?php esc_html_e('Promo Code', 'hello-theme-child'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student) : ?>
                            <tr>
                                <td><?php echo esc_html($student['name']); ?></td>
                                <td><?php echo esc_html($student['email']); ?></td>
                                <td><?php echo esc_html($student['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($student['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($student['promo_code'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
