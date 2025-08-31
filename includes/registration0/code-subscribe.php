<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option 3: Subscription via Code
 */
add_shortcode('code_subscription', 'code_subscribe_render');
function code_subscribe_render() {
    ob_start();
    echo '<form method="post">';
    echo '<p><label>קוד מנוי:</label><br><input type="text" name="subscription_code" required></p>';
    echo '<p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p>';
    echo '<p><label>וידוא טלפון:</label><br><input type="text" name="phone_confirm" required></p>';
    echo '<p><label>תעודת זהות:</label><br><input type="text" name="id_number" required></p>';
    echo '<p><label>וידוא תעודת זהות:</label><br><input type="text" name="id_number_confirm" required></p>';
    echo '<p><label>בחירת מסלול:</label><br><select name="track"><option value="standard">סטנדרטי</option><option value="premium">פרימיום</option></select></p>';
    echo '<p><button type="submit" name="code_subscribe_submit">הרשמה</button></p>';
    echo '</form>';
    return ob_get_clean();
}
add_action('init', 'code_subscribe_process');
function code_subscribe_process() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code_subscribe_submit']) ) {
        $data = $_POST;
        // TODO: validate and mark code usage
        $user_id = reg_helpers_create_user_and_assign_roles($data, 'student_private');
        if ( ! is_wp_error($user_id) ) {
            reg_helpers_enroll_in_learndash($user_id, ['student_private']);
            // TODO: redirect to confirmation or dashboard
        }
    }
}
