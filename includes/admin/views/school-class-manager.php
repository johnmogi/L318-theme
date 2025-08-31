<?php
/**
 * School class manager admin view
 *
 * @package Hello_Theme_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Check user permissions
$is_admin = current_user_can( 'manage_options' );
$is_teacher = in_array( 'school_teacher', wp_get_current_user()->roles );

if ( !$is_admin && !$is_teacher ) {
    return;
}
?>

<div class="wrap school-class-manager">
    <h1 class="wp-heading-inline">
        <?php echo $is_admin ? esc_html__( 'School Class Manager', 'hello-theme-child' ) : esc_html__( 'My Classes', 'hello-theme-child' ); ?>
    </h1>

    <hr class="wp-header-end">

    <div class="school-class-manager-container">
        <div class="school-class-manager-sidebar">
            <?php if ( $is_admin && !empty( $teachers ) ) : ?>
                <div class="teacher-selection">
                    <h2><?php esc_html_e( 'Select Teacher', 'hello-theme-child' ); ?></h2>
                    <select id="teacher-selector">
                        <?php foreach ( $teachers as $teacher ) : ?>
                            <option value="<?php echo esc_attr( $teacher->ID ); ?>" <?php selected( $teacher->ID, $selected_teacher_id ); ?>>
                                <?php echo esc_html( $teacher->display_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="class-selection">
                <h2><?php esc_html_e( 'Classes', 'hello-theme-child' ); ?></h2>
                <div id="class-list">
                    <?php if ( !empty( $classes ) ) : ?>
                        <select id="class-selector">
                            <?php foreach ( $classes as $class_id => $class_name ) : ?>
                                <option value="<?php echo esc_attr( $class_id ); ?>" <?php selected( $class_id, $selected_class_id ); ?>>
                                    <?php echo esc_html( $class_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <p class="no-classes"><?php esc_html_e( 'No classes found.', 'hello-theme-child' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="school-class-manager-content">
            <!-- Class Statistics -->
            <div class="school-class-statistics">
                <h2><?php esc_html_e( 'Class Statistics', 'hello-theme-child' ); ?></h2>
                <div class="statistics-cards">
                    <div class="stat-card">
                        <div class="stat-title"><?php esc_html_e( 'Total Students', 'hello-theme-child' ); ?></div>
                        <div class="stat-value" id="stat-total-students"><?php echo esc_html( $statistics['total_students'] ); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title"><?php esc_html_e( 'Avg. Course Completion', 'hello-theme-child' ); ?></div>
                        <div class="stat-value" id="stat-course-completion">
                            <?php echo esc_html( $statistics['course_completion'] ); ?>%
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title"><?php esc_html_e( 'Avg. Quiz Score', 'hello-theme-child' ); ?></div>
                        <div class="stat-value" id="stat-avg-score">
                            <?php echo esc_html( $statistics['average_quiz_score'] ); ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Progress Table -->
            <?php if ( !empty( $statistics['courses'] ) ) : ?>
                <div class="course-progress-section">
                    <h2><?php esc_html_e( 'Course Progress', 'hello-theme-child' ); ?></h2>
                    <table class="wp-list-table widefat fixed striped course-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Course', 'hello-theme-child' ); ?></th>
                                <th><?php esc_html_e( 'Avg. Completion', 'hello-theme-child' ); ?></th>
                                <th><?php esc_html_e( 'Avg. Quiz Score', 'hello-theme-child' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $statistics['courses'] as $course_id => $course_data ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $course_data['title'] ); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo esc_attr( $course_data['completion'] ); ?>%;"></div>
                                            <div class="progress-text"><?php echo esc_html( $course_data['completion'] ); ?>%</div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( $course_data['avg_score'] ); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Students Table -->
            <div class="students-section">
                <h2><?php esc_html_e( 'Students', 'hello-theme-child' ); ?></h2>
                <?php if ( !empty( $students ) ) : ?>
                    <table class="wp-list-table widefat fixed striped students-table" id="students-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Name', 'hello-theme-child' ); ?></th>
                                <th><?php esc_html_e( 'Email', 'hello-theme-child' ); ?></th>
                                <th><?php esc_html_e( 'Username', 'hello-theme-child' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $students as $student ) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $student->ID ) ); ?>">
                                            <?php echo esc_html( $student->display_name ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( $student->user_email ); ?></td>
                                    <td><?php echo esc_html( $student->user_login ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e( 'No students found for this class.', 'hello-theme-child' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
