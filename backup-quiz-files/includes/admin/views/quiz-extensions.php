<?php
/**
 * Quiz Extensions Admin Page View
 *
 * Renders the settings UI for managing quiz enhancements per quiz.
 *
 * Variables available:
 *   $settings (array) - current settings from options
 *   $quizzes  (array) - all quizzes [ [ 'ID' => int, 'title' => string, 'type' => string ], ... ]
 */
?>
<div class="wrap ccr-admin-page">
    <h1><?php _e('Quiz Extensions', 'hello-child'); ?></h1>
    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php _e('Settings saved.', 'hello-child'); ?></p></div>
    <?php endif; ?>
    <form method="post" action="">
        <?php wp_nonce_field('ccr_quiz_extensions_save'); ?>
        <input type="hidden" name="ccr_direct_save" value="1" />
        <h2 class="nav-tab-wrapper">
            <a href="#tab-extensions" class="nav-tab nav-tab-active" data-tab="tab-extensions"><?php _e('Quiz Extensions', 'hello-child'); ?></a>
            <a href="#tab-builder" class="nav-tab" data-tab="tab-builder"><?php _e('Quiz Builder', 'hello-child'); ?></a>
        </h2>
        
        <div class="tab-content" id="tab-extensions" style="display: block;">

        <!--
        =========================================
        TODO: Disable Quiz Top Bar Navigation
        =========================================
        To implement the "disable quiz top bar navigation" feature:
        - Add a new column and toggle in the table below (e.g., "Disable Top Bar Navigation").
        - On the frontend, use the saved settings to inject JavaScript or filter quiz output to hide or disable the navigation bar for the selected quizzes.
        - See documentation in dev/3testImprove.md and dev/3tasklist.md for requirements.
        -->

        <!--
        =========================================
        Developer Notes for Extensibility
        =========================================
        - To add new tabs: Add <a> elements above and conditionally render tab content below.
        - To add new per-quiz options: Add columns to the table, update settings save logic, and consume on the frontend.
        - All settings are stored in the options table as an array keyed by quiz ID.
        -->
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Quiz Title', 'hello-child'); ?></th>
                    <th><?php _e('Type', 'hello-child'); ?></th>
                    <th><?php _e('Show Hint', 'hello-child'); ?></th>
<th><?php _e('Force Hint Mode + Require Correct', 'hello-child'); ?></th>
<th><?php _e('Disable Hints', 'hello-child'); ?></th>
<th><?php _e('Disable Top Bar Navigation', 'hello-child'); ?></th>
<th><?php _e('Rich Sidebar', 'hello-child'); ?></th>
<!-- Future: More features -->
                </tr>
            </thead>
            <tbody>
            <?php foreach ($quizzes as $quiz):
                $quiz_id = $quiz['ID'];
                $quiz_type = $quiz['type'] === 'sfwd-quiz' ? 'LearnDash' : 'WpProQuiz';
                $checked = isset($settings[$quiz_id]['show_hint']) ? (bool)$settings[$quiz_id]['show_hint'] : false;
                $force_hint_mode = isset($settings[$quiz_id]['force_hint_mode']) ? (bool)$settings[$quiz_id]['force_hint_mode'] : false;
                $disable_hints = isset($settings[$quiz_id]['disable_hints']) ? (bool)$settings[$quiz_id]['disable_hints'] : false;
                $disable_topbar = isset($settings[$quiz_id]['disable_topbar']) ? (bool)$settings[$quiz_id]['disable_topbar'] : false;
                $rich_sidebar = isset($settings[$quiz_id]['rich_sidebar']) ? (bool)$settings[$quiz_id]['rich_sidebar'] : false;
            ?>
                <tr>
                    <td><?php echo esc_html($quiz['title']); ?></td>
                    <td><?php echo esc_html($quiz_type); ?></td>
                    <td>
                        <input type="checkbox" name="quiz_extensions[<?php echo $quiz_id; ?>][show_hint]" value="1" <?php checked($checked); ?> />
                    </td>
                    <td>
                        <!-- Force Hint Mode: If checked, the next button will be hidden until correct answer is provided and hint is viewed -->
                        <input type="checkbox" name="quiz_extensions[<?php echo $quiz_id; ?>][force_hint_mode]" value="1" <?php checked($force_hint_mode); ?> />
                    </td>
                    <td>
                        <!-- Disable Hints: If checked, all hint buttons will be completely removed from the quiz -->
                        <input type="checkbox" name="quiz_extensions[<?php echo $quiz_id; ?>][disable_hints]" value="1" <?php checked($disable_hints); ?> />
                    </td>
                    <td>
                        <!-- Disable Top Bar Navigation: If checked, the quiz top navigation bar will be hidden/disabled for this quiz. -->
                        <input type="checkbox" name="quiz_extensions[<?php echo $quiz_id; ?>][disable_topbar]" value="1" <?php checked($disable_topbar); ?> />
                    </td>
                    <td>
                        <input type="checkbox" name="quiz_extensions[<?php echo $quiz_id; ?>][rich_sidebar]" value="1" <?php checked($rich_sidebar); ?> />
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <button type="submit" class="button button-primary"><?php _e('Save Settings', 'hello-child'); ?></button>
        </p>
    </div><!-- End of tab-extensions -->
    
    <div class="tab-content" id="tab-builder" style="display: none;">
        <h3><?php _e('Quiz Builder', 'hello-child'); ?></h3>
        <p class="description">
            <?php _e('Create, import, and export quizzes using CSV files. This tool allows you to manage quizzes in bulk without manually creating each question.', 'hello-child'); ?>
        </p>
        
        <div class="quiz-builder-tools">
            <div class="quiz-export-section">
                <h4><?php _e('Export Quizzes', 'hello-child'); ?></h4>
                <p class="description">
                    <?php _e('Export existing quizzes to a CSV file for backup or editing.', 'hello-child'); ?>
                </p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('ccr_quiz_export'); ?>
                    <input type="hidden" name="action" value="ccr_export_quizzes" />
                    
                    <div class="form-field">
                        <label for="quiz-export-select"><?php _e('Select Quiz to Export:', 'hello-child'); ?></label>
                        <select name="quiz_id" id="quiz-export-select">
                            <option value="all"><?php _e('All Quizzes', 'hello-child'); ?></option>
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?php echo esc_attr($quiz['ID']); ?>"><?php echo esc_html($quiz['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <p>
                        <button type="submit" class="button button-secondary"><?php _e('Export to CSV', 'hello-child'); ?></button>
                    </p>
                </form>
            </div>
            
            <div class="quiz-import-section">
                <h4><?php _e('Import Quizzes', 'hello-child'); ?></h4>
                <p class="description">
                    <?php _e('Import quizzes from a CSV file. You can create a new quiz or update an existing one.', 'hello-child'); ?>
                </p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('ccr_quiz_import'); ?>
                    <input type="hidden" name="action" value="ccr_import_quizzes" />
                    
                    <div class="form-field">
                        <label for="quiz-import-target"><?php _e('Import Target:', 'hello-child'); ?></label>
                        <select name="import_target" id="quiz-import-target">
                            <option value="new"><?php _e('Create New Quiz(es)', 'hello-child'); ?></option>
                            <option value="update"><?php _e('Update Existing Quiz', 'hello-child'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-field existing-quiz-field" style="display: none;">
                        <label for="quiz-update-select"><?php _e('Select Quiz to Update:', 'hello-child'); ?></label>
                        <select name="update_quiz_id" id="quiz-update-select">
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?php echo esc_attr($quiz['ID']); ?>"><?php echo esc_html($quiz['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="quiz-csv-file"><?php _e('Upload CSV File:', 'hello-child'); ?></label>
                        <input type="file" name="quiz_csv_file" id="quiz-csv-file" accept=".csv" required />
                    </div>
                    
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Import CSV', 'hello-child'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="csv-template-section">
            <h4><?php _e('CSV Template & Instructions', 'hello-child'); ?></h4>
            <p class="description">
                <?php _e('Download a template CSV file and follow the instructions below to prepare your quiz data.', 'hello-child'); ?>
            </p>
            
            <p>
                <a href="<?php echo esc_url(admin_url('admin-post.php?action=ccr_download_quiz_template')); ?>" class="button button-secondary">
                    <?php _e('Download CSV Template', 'hello-child'); ?>
                </a>
            </p>
            
            <div class="csv-instructions">
                <h5><?php _e('CSV Format Instructions', 'hello-child'); ?></h5>
                <p><?php _e('Your CSV file should contain the following columns:', 'hello-child'); ?></p>
                <ul>
                    <li><strong>quiz_title</strong> - <?php _e('The title of the quiz (required)', 'hello-child'); ?></li>
                    <li><strong>question_text</strong> - <?php _e('The question text (required)', 'hello-child'); ?></li>
                    <li><strong>question_type</strong> - <?php _e('Type of question: multiple_choice, true_false, essay, etc. (required)', 'hello-child'); ?></li>
                    <li><strong>answer_1</strong> - <?php _e('First answer option (required)', 'hello-child'); ?></li>
                    <li><strong>answer_2</strong> - <?php _e('Second answer option', 'hello-child'); ?></li>
                    <li><strong>answer_3</strong> - <?php _e('Third answer option', 'hello-child'); ?></li>
                    <li><strong>answer_4</strong> - <?php _e('Fourth answer option', 'hello-child'); ?></li>
                    <li><strong>answer_5</strong> - <?php _e('Fifth answer option', 'hello-child'); ?></li>
                    <li><strong>answer_6</strong> - <?php _e('Sixth answer option', 'hello-child'); ?></li>
                    <li><strong>correct_answer</strong> - <?php _e('Correct answer number(s), e.g., "1" or "1,3" for multiple (required)', 'hello-child'); ?></li>
                    <li><strong>question_hint</strong> - <?php _e('Hint to display for this question', 'hello-child'); ?></li>
                    <li><strong>points</strong> - <?php _e('Points for this question (default: 1)', 'hello-child'); ?></li>
                    <li><strong>category</strong> - <?php _e('Question category (optional)', 'hello-child'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Sample CSV Data Table Display -->
        <div class="csv-sample-section">
            <h4><?php _e('Sample CSV Data', 'hello-child'); ?></h4>
            <div class="csv-sample-table-wrap">
                <table class="wp-list-table widefat fixed striped csv-sample-table">
                    <thead>
                        <tr>
                            <th>quiz_title</th>
                            <th>question_text</th>
                            <th>question_type</th>
                            <th>answer_1</th>
                            <th>answer_2</th>
                            <th>answer_3</th>
                            <th>correct_answer</th>
                            <th>question_hint</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>תיאוריה - פרק 01</td>
                            <td>באיזה סוג כלי רכב מותר לבעל רישיון נהיגה מדרגה B לנהוג?</td>
                            <td>multiple_choice</td>
                            <td>רכב נוסעים פרטי</td>
                            <td>רכב משא שמשקלו עד 3,500 ק"ג</td>
                            <td>רכב מסחרי</td>
                            <td>1,2,3</td>
                            <td>רישיון מסוג B מאפשר נהיגה ברכב עד 3,500 ק"ג</td>
                        </tr>
                        <tr>
                            <td>תיאוריה - פרק 01</td>
                            <td>הנהיגה היא משימה מורכבת. נכון או לא נכון?</td>
                            <td>true_false</td>
                            <td>נכון</td>
                            <td>לא נכון</td>
                            <td></td>
                            <td>1</td>
                            <td>נהיגה דורשת ריכוז, מיומנות וידע בחוקי התנועה</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- End of tab-builder -->
    </form>
    
    <p style="margin-top:2em;color:#666;font-size:13px;">
        <?php _e('This page allows you to manage quiz settings and build quizzes using CSV files. Use the tabs above to access different features.', 'hello-child'); ?>
    </p>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab switching functionality
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding tab content
            var tabId = $(this).data('tab');
            $('.tab-content').hide();
            $('#' + tabId).show();
            
            // Update URL without reloading page
            if (history.pushState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tabId.replace('tab-', ''));
                window.history.pushState({path: url.toString()}, '', url.toString());
            }
        });
        
        // Toggle existing quiz field visibility
        $('#quiz-import-target').on('change', function() {
            if ($(this).val() === 'update') {
                $('.existing-quiz-field').show();
            } else {
                $('.existing-quiz-field').hide();
            }
        });
        
        // If there's a hash in the URL, click that tab
        if (window.location.hash) {
            var tabToShow = window.location.hash.substring(1);
            $('.nav-tab[href="#' + tabToShow + '"]').click();
        }
        
        // Handle direct tab parameter in URL
        var urlParams = new URLSearchParams(window.location.search);
        var tabParam = urlParams.get('tab');
        if (tabParam) {
            $('.nav-tab[data-tab="tab-' + tabParam + '"]').click();
        }
    });
    </script>
</div>
