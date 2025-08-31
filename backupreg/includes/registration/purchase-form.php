<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option 1: Book Purchase Registration Form
 */
add_shortcode('purchase_form', 'purchase_form_render');
function purchase_form_render( $atts ) {
    // Accept optional group_id and course_id via shortcode attributes
    $atts = shortcode_atts(
        array(
            'group_id'  => '',
            'course_id' => '',
        ),
        $atts,
        'purchase_form'
    );
    ob_start();
    echo '<p>שימו לב! ניתן להירשם למנוי רק לאחר קבלת הספרים. קוד ההטבה נמצא בתוך הספר.</p>';
    echo '<form method="post">';
    // Inject group and course IDs if provided
    if ( ! empty( $atts['group_id'] ) ) {
        echo '<input type="hidden" name="group_id" value="' . esc_attr( $atts['group_id'] ) . '">';
    }
    if ( ! empty( $atts['course_id'] ) ) {
        echo '<input type="hidden" name="course_id" value="' . esc_attr( $atts['course_id'] ) . '">';
    }
    echo '<p><label>שם פרטי:</label><br><input type="text" name="first_name" required></p>';
    echo '<p><label>שם משפחה:</label><br><input type="text" name="last_name" required></p>';
    echo '<p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p>';
    echo '<p><label>וודא טלפון נייד:</label><br><input type="text" name="phone_confirm" required></p>';
    echo '<p><label>משלוח:</label><br><select name="delivery">';
    echo '<option value="pickup">איסוף עצמי</option><option value="shipping">משלוח</option>';
    echo '</select></p>';
    echo '<div id="shipping_address" style="display:none;">';
    echo '<p><label>עיר:</label><br><input type="text" name="city"></p>';
    echo '<p><label>רחוב:</label><br><input type="text" name="street"></p>';
    echo '<p><label>טלפון לשליח:</label><br><input type="text" name="delivery_phone"></p>';
    echo '</div>';
    echo '<p><label>אמצעי תשלום:</label><br><select name="payment_method">';
    echo '<option value="bit">ביט</option><option value="credit">אשראי</option>';
    echo '</select></p>';
    echo '<p><button type="submit" name="purchase_submit">המשך לתשלום</button></p>';
    echo '</form>';
    return ob_get_clean();
}

add_action('init', 'purchase_form_process');
function purchase_form_process() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_submit']) ) {
        // Use posted data array
        $data    = $_POST;
        $user_id = reg_helpers_create_user_and_assign_roles( $data, 'book_holder' );
        if ( ! is_wp_error($user_id) ) {
            // Auto-login the new user
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
            // Enroll in specified group or fallback
            if ( ! empty( $data['group_id'] ) ) {
                ld_update_group_access( $user_id, intval( $data['group_id'] ) );
            } else {
                reg_helpers_enroll_in_learndash( $user_id, ['book_holder'] );
            }
            // Enroll in specified course if provided
            if ( ! empty( $data['course_id'] ) ) {
                ld_update_course_access( $user_id, intval( $data['course_id'] ), false );
            }
            // TODO: Redirect to payment gateway or confirmation page
        }
    }
}
