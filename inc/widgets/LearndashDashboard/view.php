<div class="ld-dashboard-wrapper">
    <div class="ld-dashboard-columns">
        <div class="ld-box">
            <h4>מבחנים כדוגמאות מבחן התיאוריה</h4>
            <a href="<?php echo esc_url(home_url('/practice-tests')); ?>" class="ld-button red">מבחני תרגול</a>
            <a href="<?php echo esc_url(home_url('/theory-tests')); ?>" class="ld-button purple">מבחני אמת – כמו בתיאוריה</a>
        </div>
        <div class="ld-box">
            <h4>שאלות ממאגר לפי נושאים</h4>
            <a href="<?php echo esc_url(home_url('/study-materials')); ?>" class="ld-button turquoise">חומר לימוד לפי נושאים</a>
            <a href="<?php echo esc_url(home_url('/topic-tests')); ?>" class="ld-button light-blue">מבחנים לפי נושאים</a>
        </div>
        <div class="ld-profile-box">
            <h2>שלום, <?php echo esc_html($display_name); ?>!</h2>
            <p><?php echo esc_html($current_date); ?></p>
            <p><strong>הינך במסלול לימוד חינוך תעבורתי</strong></p>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/account')); ?>">ערוך חשבון (שינוי נושא לימוד)</a></li>
                <li><a href="<?php echo esc_url(home_url('/statistics')); ?>">סטטיסטיקות לימוד</a></li>
                <li><a href="<?php echo esc_url(home_url('/support')); ?>">תמיכה</a></li>
            </ul>
        </div>
    </div>
    <p class="ld-footer-note">בהצלחה בלימוד ובהתרגשות!</p>
</div>
