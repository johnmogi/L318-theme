<?php
/**
 * Teacher Class Manager View
 *
 * @package Hello_Theme_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get current user info
$current_user = wp_get_current_user();
$teacher_id = $current_user->ID;
?>

<div class="wrap school-class-manager teacher-view">
    <h1><?php esc_html_e('My Classes', 'hello-theme-child'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php esc_html_e('Welcome to your class management dashboard', 'hello-theme-child'); ?></p>
    </div>
    
    <!-- Classes Section -->
    <div class="classes-section">
        <h2><?php esc_html_e('My Classes', 'hello-theme-child'); ?></h2>
        
        <?php if (empty($classes)) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('You have no assigned classes.', 'hello-theme-child'); ?></p>
            </div>
        <?php else : ?>
            <div class="class-cards">
                <?php foreach ($classes as $class) : ?>
                    <div class="class-card <?php echo ($selected_class_id == $class['id']) ? 'active' : ''; ?>">
                        <h3><?php echo esc_html($class['name']); ?></h3>
                        <div class="class-meta">
                            <div class="student-count">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo esc_html($class['student_count']); ?> 
                                <?php esc_html_e('Students', 'hello-theme-child'); ?>
                            </div>
                        </div>
                        <div class="class-actions">
                            <a href="?page=school-classes-teacher&class_id=<?php echo esc_attr($class['id']); ?>" class="button button-primary">
                                <?php esc_html_e('View Class', 'hello-theme-child'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Students Section (if class is selected) -->
    <?php if ($selected_class_id) : ?>
        <div class="students-section">
            <h2>
                <?php 
                $class_name = '';
                foreach ($classes as $class) {
                    if ($class['id'] == $selected_class_id) {
                        $class_name = $class['name'];
                        break;
                    }
                }
                echo sprintf(__('Students in %s', 'hello-theme-child'), esc_html($class_name));
                ?>
            </h2>
            
            <?php 
            $students = [];
            if (method_exists($this, 'get_class_students')) {
                $students = $this->get_class_students($selected_class_id);
            }
            ?>
            
            <?php if (empty($students)) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('No students found in this class.', 'hello-theme-child'); ?></p>
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
                            <th><?php esc_html_e('Actions', 'hello-theme-child'); ?></th>
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
                                <td>
                                    <a href="#" class="button button-small view-student" data-student-id="<?php echo esc_attr($student['id']); ?>">
                                        <?php esc_html_e('View Progress', 'hello-theme-child'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Add link to create new students via wizard -->
    <div class="teacher-actions">
        <a href="<?php echo admin_url('admin.php?page=teacher-class-wizard'); ?>" class="button button-primary">
            <?php esc_html_e('Student Management Wizard', 'hello-theme-child'); ?>
        </a>
    </div>
</div>

<!-- Add some CSS to style the class cards -->
<style>
.class-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin: 20px 0;
}

.class-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    width: calc(33.333% - 20px);
    min-width: 250px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    transition: all .2s ease-in-out;
}

.class-card:hover, .class-card.active {
    border-color: #007cba;
    box-shadow: 0 0 0 1px #007cba;
}

.class-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.class-meta {
    margin-bottom: 15px;
}

.student-count {
    display: flex;
    align-items: center;
    color: #606a73;
}

.student-count .dashicons {
    margin-right: 5px;
}

.teacher-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
</style>
