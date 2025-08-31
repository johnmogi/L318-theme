<?php
/**
 * Test Promo Code Fix
 */

// Register test shortcode
function test_hebrew_shortcode() {
    ob_start();
    ?>
    <div style="direction: rtl; text-align: right; border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h2>טקסט בדיקה בעברית</h2>
        <p>זהו טקסט בדיקה בעברית</p>
        <p>האם הטקסט מוצג כראוי?</p>
        
        <form method="post" style="margin-top: 20px;">
            <input type="text" 
                   name="test_hebrew" 
                   placeholder="הקלד כאן בעברית" 
                   style="width: 100%; padding: 10px; direction: rtl; text-align: right;">
            <input type="submit" 
                   name="test_submit" 
                   value="שלח" 
                   style="margin-top: 10px; padding: 5px 15px;">
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('test_hebrew', 'test_hebrew_shortcode');
