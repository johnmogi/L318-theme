<?php
/**
 * Template Name: Toast Test Page
 *
 * A test page to demonstrate the toast messaging system.
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="entry-content">
            <h1>Toast Messaging System Demo</h1>
            
            <h2>Current Session Statistics</h2>
            <p><?php echo do_shortcode('[lilac_session_counter label="מספר ביקור במערכת:"]'); ?></p>
            
            <h2>Toast Message Types</h2>
            
            <div class="toast-examples">
                <h3>Default Info Message</h3>
                <?php echo do_shortcode('[lilac_message]שלום עולם! זוהי הודעת ברירת מחדל.[/lilac_message]'); ?>
                
                <h3>Success Message</h3>
                <?php echo do_shortcode('[lilac_message type="success" title="פעולה הושלמה בהצלחה"]המשימה הושלמה בהצלחה![/lilac_message]'); ?>
                
                <h3>Warning Message</h3>
                <?php echo do_shortcode('[lilac_message type="warning" title="אזהרה" auto_close="5"]שים לב! הודעה זו תיסגר אוטומטית אחרי 5 שניות.[/lilac_message]'); ?>
            </div>
            
            <h2>Dynamic Toast Creation</h2>
            <p>You can also create toasts programmatically using JavaScript:</p>
            
            <div class="toast-actions">
                <button id="create-info-toast" class="button">הצג הודעת מידע</button>
                <button id="create-success-toast" class="button">הצג הודעת הצלחה</button>
                <button id="create-warning-toast" class="button">הצג אזהרה</button>
                <button id="create-error-toast" class="button">הצג שגיאה</button>
            </div>
            
            <h2>Using Session Counters</h2>
            <p>The session counter can be used to track user engagement in courses, time spent on pages, or progress through multi-step forms.</p>
            
            <p>Current value: <span id="dynamic-counter"><?php echo isset($_SESSION['lilac_session_counter']) ? $_SESSION['lilac_session_counter'] : 0; ?></span></p>
            
            <script>
            jQuery(document).ready(function($) {
                // Create toast buttons
                $('#create-info-toast').on('click', function() {
                    window.LilacToast.showToast({
                        type: 'info',
                        title: 'הודעת מידע',
                        message: 'זוהי הודעת מידע שנוצרה דינמית.',
                        position: 'top-right',
                        autoClose: 3
                    });
                });
                
                $('#create-success-toast').on('click', function() {
                    window.LilacToast.showToast({
                        type: 'success',
                        title: 'הצלחה',
                        message: 'המשימה הושלמה בהצלחה!',
                        position: 'top-center',
                        autoClose: 3
                    });
                });
                
                $('#create-warning-toast').on('click', function() {
                    window.LilacToast.showToast({
                        type: 'warning',
                        title: 'אזהרה',
                        message: 'שים לב להודעה זו.',
                        position: 'bottom-right',
                        autoClose: 3
                    });
                });
                
                $('#create-error-toast').on('click', function() {
                    window.LilacToast.showToast({
                        type: 'error',
                        title: 'שגיאה',
                        message: 'אירעה שגיאה בביצוע הפעולה.',
                        position: 'bottom-center',
                        autoClose: 3
                    });
                });
            });
            </script>
            
            <style>
            .toast-examples {
                margin: 20px 0;
            }
            .toast-examples h3 {
                margin-top: 20px;
            }
            .toast-actions {
                margin: 20px 0;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .toast-actions button {
                padding: 8px 16px;
                cursor: pointer;
            }
            </style>
        </div>
    </main>
</div>

<?php
get_footer();
