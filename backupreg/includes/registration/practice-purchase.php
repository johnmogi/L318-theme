<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option 4: Practice-Only Purchase Registration Form
 */
add_shortcode('practice_purchase', 'practice_purchase_render');
function practice_purchase_render() {
    ob_start();
    echo '<form method="post">';
    echo '<p><label>שם פרטי:</label><br><input type="text" name="first_name" required></p>';
    echo '<p><label>שם משפחה:</label><br><input type="text" name="last_name" required></p>';
    echo '<p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p>';
    echo '<p><label>וודא טלפון:</label><br><input type="text" name="phone_confirm" required></p>';
    echo '<p><label>מסלול תרגול:</label><br><select name="track"><option value="quizzes">חידונים בלבד</option><option value="practice_tests">מבחנים לתרגול</option></select></p>';
    echo '<p><label>תקופת מנוי:</label><br><select name="period"><option value="1">1 חודש</option><option value="12">12 חודשים</option></select></p>';
    echo '<p><label>אמצעי תשלום:</label><br><select name="payment_method"><option value="bit">ביט</option><option value="credit">אשראי</option></select></p>';
    echo '<p><button type="submit" name="practice_purchase_submit">המשך לתשלום</button></p>';
    echo '</form>';
    return ob_get_clean();
}

add_action('init', 'practice_purchase_process');
function practice_purchase_process() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['practice_purchase_submit']) ) {
        $data = $_POST;
        $user_id = reg_helpers_create_user_and_assign_roles($data, 'practice_only');
        if ( ! is_wp_error($user_id) ) {
            reg_helpers_enroll_in_learndash($user_id, ['practice_only']);
            // TODO: Redirect to payment gateway or confirmation
        }
    }
}
