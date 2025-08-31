<?php
/**
 * Template Name: Course Progress Test Page
 *
 * A test page to demonstrate the course progress tracking system.
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="entry-content">
            <h1>מערכת מעקב התקדמות קורס</h1>
            
            <section class="demo-section">
                <h2>מידע פעילות נוכחית</h2>
                
                <div class="demo-row">
                    <div class="demo-col">
                        <?php echo do_shortcode('[lilac_session_counter label="מספר ביקור במערכת:"]'); ?>
                    </div>
                    <div class="demo-col">
                        <?php echo do_shortcode('[lilac_activity_timer label="זמן פעילות:" format="formatted" live="true"]'); ?>
                    </div>
                </div>
            </section>
            
            <section class="demo-section">
                <h2>מעקב התקדמות קורס</h2>
                
                <?php 
                // Demo Course IDs (simulated data)
                $demo_courses = array(
                    array('id' => 101, 'name' => 'מבוא לתיאוריה בנהיגה', 'progress' => 75),
                    array('id' => 102, 'name' => 'חוקי תנועה מתקדמים', 'progress' => 30),
                    array('id' => 103, 'name' => 'תרגול מבחן מסכם', 'progress' => 10),
                );
                
                foreach ($demo_courses as $course) :
                    ?>
                    <div class="course-progress-demo">
                        <h3><?php echo esc_html($course['name']); ?></h3>
                        <div class="demo-row">
                            <div class="demo-col">
                                <?php echo do_shortcode('[lilac_course_progress course_id="' . $course['id'] . '" label="התקדמות:"]'); ?>
                            </div>
                            <div class="demo-col">
                                <?php echo do_shortcode('[lilac_course_views course_id="' . $course['id'] . '" label="צפיות:"]'); ?>
                            </div>
                            <div class="demo-actions">
                                <button class="update-progress-btn lilac-course-progress-update" 
                                        data-course-id="<?php echo esc_attr($course['id']); ?>" 
                                        data-progress="<?php echo esc_attr($course['progress'] + 10 > 100 ? 100 : $course['progress'] + 10); ?>">
                                    הוסף התקדמות +10%
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="demo-buttons">
                    <button id="reset-all-progress" class="reset-btn">איפוס התקדמות בכל הקורסים</button>
                </div>
            </section>
            
            <section class="demo-section">
                <h2>שימוש בשילוב עם מערכת ההודעות (Toast)</h2>
                
                <div class="demo-row">
                    <div class="demo-col">
                        <button id="show-success-toast" class="toast-btn">הצגת הודעת הצלחה</button>
                    </div>
                    <div class="demo-col">
                        <button id="show-progress-toast" class="toast-btn">הצגת הודעת התקדמות</button>
                    </div>
                </div>
                
                <h3>תיעוד שימוש בקוד</h3>
                <pre><code>// עדכון התקדמות קורס:
jQuery(document).ready(function($) {
    // עדכון התקדמות דרך JavaScript
    window.LilacProgress.updateProgress(101, 75);
    
    // הצגת הודעת Toast
    window.LilacToast.showToast({
        type: 'success',
        title: 'התקדמות עודכנה',
        message: 'התקדמות הקורס עודכנה בהצלחה',
        position: 'top-right',
        autoClose: 3
    });
});</code></pre>
            </section>
            
            <section class="demo-section">
                <h2>קודי קיצור זמינים</h2>
                
                <div class="shortcode-docs">
                    <h3>קוד קיצור מספר ביקורים</h3>
                    <pre><code>[lilac_session_counter label="מספר ביקור במערכת:" class="custom-class"]</code></pre>
                    
                    <h3>קוד קיצור התקדמות קורס</h3>
                    <pre><code>[lilac_course_progress course_id="101" label="התקדמות:" 
    show_percentage="true" show_bar="true" bar_color="#3498db"]</code></pre>
                    
                    <h3>קוד קיצור ספירת צפיות</h3>
                    <pre><code>[lilac_course_views course_id="101" label="צפיות:" icon="true"]</code></pre>
                    
                    <h3>קוד קיצור טיימר פעילות</h3>
                    <pre><code>[lilac_activity_timer label="זמן פעילות:" format="formatted" live="true"]</code></pre>
                </div>
            </section>
            
            <script>
            jQuery(document).ready(function($) {
                // Handle Reset All Progress button
                $('#reset-all-progress').on('click', function() {
                    // Reset progress for each demo course
                    <?php foreach ($demo_courses as $course) : ?>
                    window.LilacProgress.updateProgress(<?php echo esc_js($course['id']); ?>, 0);
                    <?php endforeach; ?>
                    
                    // Show toast message
                    if (typeof window.LilacToast !== 'undefined') {
                        window.LilacToast.showToast({
                            type: 'info',
                            title: 'איפוס התקדמות',
                            message: 'התקדמות כל הקורסים אופסה בהצלחה',
                            position: 'top-right',
                            autoClose: 3
                        });
                    }
                });
                
                // Handle Success Toast button
                $('#show-success-toast').on('click', function() {
                    if (typeof window.LilacToast !== 'undefined') {
                        window.LilacToast.showToast({
                            type: 'success',
                            title: 'הצלחה',
                            message: 'פעולה הושלמה בהצלחה',
                            position: 'top-right',
                            autoClose: 3
                        });
                    }
                });
                
                // Handle Progress Toast button
                $('#show-progress-toast').on('click', function() {
                    if (typeof window.LilacToast !== 'undefined') {
                        window.LilacToast.showToast({
                            type: 'info',
                            title: 'התקדמות',
                            message: 'התקדמותך נשמרה. עברת ל-75% מהקורס',
                            position: 'top-center',
                            autoClose: 5
                        });
                    }
                });
            });
            </script>
            
            <style>
            .demo-section {
                margin: 40px 0;
                padding: 20px;
                border: 1px solid #eee;
                border-radius: 8px;
                background-color: #fff;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            
            .demo-row {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin: 15px 0;
            }
            
            .demo-col {
                flex: 1;
                min-width: 250px;
            }
            
            .course-progress-demo {
                margin: 30px 0;
                padding: 20px;
                border-radius: 8px;
                background-color: #f9f9f9;
            }
            
            .course-progress-demo h3 {
                margin-top: 0;
                color: #333;
            }
            
            .demo-actions {
                margin-top: 15px;
            }
            
            .update-progress-btn,
            .toast-btn,
            .reset-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                background-color: #3498db;
                color: white;
                font-weight: bold;
                cursor: pointer;
                transition: background-color 0.3s;
                direction: rtl;
            }
            
            .update-progress-btn:hover,
            .toast-btn:hover {
                background-color: #2980b9;
            }
            
            .reset-btn {
                background-color: #e74c3c;
            }
            
            .reset-btn:hover {
                background-color: #c0392b;
            }
            
            .shortcode-docs {
                margin-top: 20px;
            }
            
            .shortcode-docs h3 {
                margin-top: 25px;
                margin-bottom: 10px;
                color: #333;
            }
            
            .shortcode-docs pre {
                background-color: #f5f5f5;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                direction: ltr;
                text-align: left;
            }
            
            pre code {
                font-family: Consolas, Monaco, 'Andale Mono', monospace;
                font-size: 14px;
                color: #333;
                line-height: 1.5;
            }
            </style>
        </div>
    </main>
</div>

<?php
get_footer();
