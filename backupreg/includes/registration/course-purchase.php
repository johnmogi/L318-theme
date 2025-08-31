<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option 2: Course Purchase Registration Form
 */
add_shortcode('course_registration', 'course_purchase_render');
// Alias for legacy Elementor shortcode
add_shortcode('course-purchase', 'course_purchase_render');
function course_purchase_render( $atts ) {
    $atts = shortcode_atts(
        array(
            'course_id' => '1292',
            'group_id'  => '',
        ),
        $atts,
        'course_registration'
    );
    ob_start();
    echo '<form method="post">';
    echo '<input type="hidden" name="course_id" value="' . esc_attr( $atts['course_id'] ) . '">';
    if ( ! empty( $atts['group_id'] ) ) {
        echo '<input type="hidden" name="group_id" value="' . esc_attr( $atts['group_id'] ) . '">';
    }
    echo '<p><label>קוד קופון (אופציונלי):</label><br><input type="text" name="coupon_code"></p>';
    echo '<p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p>';
    echo '<p><label>וידוא טלפון:</label><br><input type="text" name="phone_confirm" required></p>';
    echo '<p><label>תעודת זהות:</label><br><input type="text" name="id_number" required></p>';
    echo '<p><label>וידוא תעודת זהות:</label><br><input type="text" name="id_number_confirm" required></p>';
    echo '<p><label>תקופת מנוי:</label><br><select name="period"><option value="1">1 חודש</option><option value="12">12 חודשים</option></select></p>';
    echo '<p><label>אמצעי תשלום:</label><br><select name="payment_method"><option value="bit">ביט</option><option value="credit">אשראי</option><option value="demo">דמו חינם</option></select></p>';
    echo '<p><button type="submit" name="course_purchase_submit">המשך לתשלום</button></p>';
    echo '</form>';
    return ob_get_clean();
}

add_action('init', 'course_purchase_process');
function course_purchase_process() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['course_purchase_submit'] ) ) {
        $data    = $_POST;
        $user_id = reg_helpers_create_user_and_assign_roles( $data, 'student_school' );
        if ( ! is_wp_error( $user_id ) ) {
            // Auto-login the new user
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
            // Enroll group: override if set, else default via role
            if ( ! empty( $data['group_id'] ) ) {
                ld_update_group_access( $user_id, intval( $data['group_id'] ) );
            }
            // Enroll in the selected course
            if ( ! empty( $data['course_id'] ) ) {
                ld_update_course_access( $user_id, intval( $data['course_id'] ), false );
            }
            // TODO: Redirect to payment gateway or confirmation page
        }
    }
}
