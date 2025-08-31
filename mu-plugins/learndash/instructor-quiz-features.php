<?php
/**
 * Plugin Name: Instructor Quiz Features Extension
 * Description: Extends the Lilac Quiz Sidebar features to instructor-created quizzes
 * Version: 1.0
 * Author: Lilac Support
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Instructor_Quiz_Features {
    /**
     * Constructor
     */
    public function __construct() {
        // Only run if the main Lilac Quiz Sidebar plugin is active
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the extension
     */
    public function init() {
        // Check if Lilac Quiz Sidebar class exists
        if (!class_exists('Lilac_Quiz_Sidebar')) {
            return;
        }
        
        // Add metabox for instructor-created quizzes
        add_action('add_meta_boxes', array($this, 'add_instructor_quiz_metabox'));
        
        // Save metabox data for instructor quizzes
        add_action('save_post_sfwd-quiz', array($this, 'save_instructor_quiz_metabox'));
        
        // Add admin columns for instructor view
        add_filter('manage_sfwd-quiz_posts_columns', array($this, 'add_instructor_admin_columns'));
        add_action('manage_sfwd-quiz_posts_custom_column', array($this, 'render_instructor_admin_columns'), 10, 2);
        
        // Ensure instructor-created quizzes get the same frontend behavior
        add_filter('body_class', array($this, 'add_instructor_quiz_body_classes'));
        
        // Enqueue scripts for instructor quizzes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_instructor_quiz_scripts'));
        
        // Add quick edit support for instructors
        add_action('quick_edit_custom_box', array($this, 'add_instructor_quick_edit_fields'), 10, 2);
        add_action('save_post', array($this, 'save_instructor_quick_edit_data'));
        
        // AJAX handler for instructor quiz settings
        add_action('wp_ajax_update_instructor_quiz_settings', array($this, 'ajax_update_instructor_quiz_settings'));
    }
    
    /**
     * Add metabox for instructor-created quizzes
     */
    public function add_instructor_quiz_metabox() {
        // Only add for instructors or if current user can edit others' quizzes
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            return;
        }
        
        add_meta_box(
            'instructor_quiz_features',
            __('Quiz Features (Instructor)', 'instructor-quiz-features'),
            array($this, 'render_instructor_quiz_metabox'),
            'sfwd-quiz',
            'side',
            'high'
        );
    }
    
    /**
     * Render metabox content for instructor quizzes
     *
     * @param WP_Post $post Current post object
     */
    public function render_instructor_quiz_metabox($post) {
        // Add nonce for security
        wp_nonce_field('instructor_quiz_features_save', 'instructor_quiz_features_nonce');
        
        // Get current values (use same meta keys as Lilac Quiz Sidebar)
        $toggle_sidebar = get_post_meta($post->ID, '_ld_quiz_toggle_sidebar', true);
        $enforce_hint = get_post_meta($post->ID, '_ld_quiz_enforce_hint', true);
        
        ?>
        <div class="instructor-quiz-features-metabox">
            <p>
                <label for="instructor_quiz_toggle_sidebar">
                    <input type="checkbox" id="instructor_quiz_toggle_sidebar" name="instructor_quiz_toggle_sidebar" 
                        value="1" <?php checked($toggle_sidebar, '1'); ?> />
                    <strong><?php _e('Rich Media Sidebar', 'instructor-quiz-features'); ?></strong>
                </label>
            </p>
            <p class="description">
                <?php _e('Enable rich media sidebar with images and videos from ACF fields attached to each answer.', 'instructor-quiz-features'); ?>
            </p>
            
            <hr style="margin: 15px 0;" />
            
            <p>
                <label for="instructor_quiz_enforce_hint">
                    <input type="checkbox" id="instructor_quiz_enforce_hint" name="instructor_quiz_enforce_hint" 
                        value="1" <?php checked($enforce_hint, '1'); ?> />
                    <strong><?php _e('Forced Hint', 'instructor-quiz-features'); ?></strong>
                </label>
            </p>
            <p class="description">
                <?php _e('Force users to view hints after incorrect answers before they can proceed to the next question.', 'instructor-quiz-features'); ?>
            </p>
            
            <hr style="margin: 15px 0;" />
            
            <p class="description">
                <strong><?php _e('Note:', 'instructor-quiz-features'); ?></strong>
                <?php _e('These features work the same as the main Lilac Quiz Sidebar plugin. Rich sidebar requires ACF fields on quiz questions.', 'instructor-quiz-features'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save metabox data for instructor quizzes
     *
     * @param int $post_id Post ID
     */
    public function save_instructor_quiz_metabox($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['instructor_quiz_features_nonce']) || 
            !wp_verify_nonce($_POST['instructor_quiz_features_nonce'], 'instructor_quiz_features_save')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Only process if user is instructor or admin
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            return;
        }
        
        // Save sidebar toggle state (use same meta key as Lilac Quiz Sidebar)
        if (isset($_POST['instructor_quiz_toggle_sidebar'])) {
            update_post_meta($post_id, '_ld_quiz_toggle_sidebar', '1');
            error_log('Instructor Quiz Features: Enabled rich sidebar for quiz #' . $post_id);
        } else {
            delete_post_meta($post_id, '_ld_quiz_toggle_sidebar');
            error_log('Instructor Quiz Features: Disabled rich sidebar for quiz #' . $post_id);
        }
        
        // Save enforce hint state (use same meta key as Lilac Quiz Sidebar)
        if (isset($_POST['instructor_quiz_enforce_hint'])) {
            update_post_meta($post_id, '_ld_quiz_enforce_hint', '1');
            error_log('Instructor Quiz Features: Enabled forced hint for quiz #' . $post_id);
        } else {
            delete_post_meta($post_id, '_ld_quiz_enforce_hint');
            error_log('Instructor Quiz Features: Disabled forced hint for quiz #' . $post_id);
        }
    }
    
    /**
     * Add admin columns for instructor view
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_instructor_admin_columns($columns) {
        // Only add for instructors
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            return $columns;
        }
        
        // Add columns after title
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['instructor_rich_sidebar'] = __('Rich Sidebar', 'instructor-quiz-features');
                $new_columns['instructor_forced_hint'] = __('Forced Hint', 'instructor-quiz-features');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render admin column content for instructor view
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_instructor_admin_columns($column, $post_id) {
        if ($column === 'instructor_rich_sidebar') {
            $has_sidebar = get_post_meta($post_id, '_ld_quiz_toggle_sidebar', true);
            echo $has_sidebar ? '<span style="color: green;">✓ ' . __('Enabled', 'instructor-quiz-features') . '</span>' : 
                               '<span style="color: #999;">✗ ' . __('Disabled', 'instructor-quiz-features') . '</span>';
        } else if ($column === 'instructor_forced_hint') {
            $enforce_hint = get_post_meta($post_id, '_ld_quiz_enforce_hint', true);
            echo $enforce_hint ? '<span style="color: green;">✓ ' . __('Enabled', 'instructor-quiz-features') . '</span>' : 
                               '<span style="color: #999;">✗ ' . __('Disabled', 'instructor-quiz-features') . '</span>';
        }
    }
    
    /**
     * Add quiz-specific body classes for instructor quizzes
     *
     * @param array $classes Current body classes
     * @return array Modified body classes
     */
    public function add_instructor_quiz_body_classes($classes) {
        if (!is_singular('sfwd-quiz')) {
            return $classes;
        }

        $quiz_id = get_queried_object_id();
        $has_sidebar = get_post_meta($quiz_id, '_ld_quiz_toggle_sidebar', true);
        $enforce_hint = get_post_meta($quiz_id, '_ld_quiz_enforce_hint', true);

        // Add richSidebar class if sidebar is enabled
        if ($has_sidebar === '1' || $has_sidebar === 'yes' || $has_sidebar === true) {
            $classes[] = 'richSidebar';
            $classes[] = 'instructor-rich-sidebar';
        }

        // Add enforceHint class if enforce hint is enabled
        if ($enforce_hint === '1' || $enforce_hint === 'yes' || $enforce_hint === true) {
            $classes[] = 'enforceHint';
            $classes[] = 'quiz-enforce-hint';
            $classes[] = 'instructor-enforce-hint';
        }

        return $classes;
    }
    
    /**
     * Enqueue scripts and styles for instructor quizzes
     */
    public function enqueue_instructor_quiz_scripts() {
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        $has_sidebar = get_post_meta($quiz_id, '_ld_quiz_toggle_sidebar', true);
        $enforce_hint = get_post_meta($quiz_id, '_ld_quiz_enforce_hint', true);
        
        // If either feature is enabled, ensure the main plugin's assets are loaded
        if ($has_sidebar === '1' || $enforce_hint === '1') {
            // Check if main plugin assets are already enqueued
            if (!wp_script_is('lilac-quiz-sidebar-js', 'enqueued')) {
                // Try to load the main plugin's assets
                $this->load_main_plugin_assets($quiz_id, $has_sidebar, $enforce_hint);
            }
            
            // Add our own enhancement script
            wp_enqueue_script(
                'instructor-quiz-features-js',
                plugin_dir_url(__FILE__) . '../plugins/lilac-quiz-sidebar/assets/js/quiz-sidebar-injection.js',
                array('jquery'),
                '1.0',
                true
            );
            
            // Localize script with quiz settings
            wp_localize_script('instructor-quiz-features-js', 'instructorQuizFeatures', array(
                'quizId' => $quiz_id,
                'hasSidebar' => $has_sidebar === '1',
                'enforceHint' => $enforce_hint === '1',
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('instructor_quiz_features_nonce')
            ));
        }
    }
    
    /**
     * Load main plugin assets if they're not already loaded
     */
    private function load_main_plugin_assets($quiz_id, $has_sidebar, $enforce_hint) {
        // Load CSS from main plugin
        $css_files = array(
            'quiz-sidebar.css',
            'quiz-navigation.css',
            'quiz-answer-reselection.css'
        );
        
        foreach ($css_files as $css_file) {
            $css_path = WP_CONTENT_DIR . '/plugins/lilac-quiz-sidebar/assets/css/' . $css_file;
            $css_url = content_url('/plugins/lilac-quiz-sidebar/assets/css/' . $css_file);
            
            if (file_exists($css_path)) {
                wp_enqueue_style(
                    'instructor-' . str_replace('.css', '', $css_file),
                    $css_url,
                    array(),
                    filemtime($css_path)
                );
            }
        }
        
        // Load JS from main plugin
        $js_files = array(
            'quiz-sidebar-injection.js',
            'quiz-answer-reselection-fixed.js',
            'quiz-navigation-control.js'
        );
        
        foreach ($js_files as $js_file) {
            $js_path = WP_CONTENT_DIR . '/plugins/lilac-quiz-sidebar/assets/js/' . $js_file;
            $js_url = content_url('/plugins/lilac-quiz-sidebar/assets/js/' . $js_file);
            
            if (file_exists($js_path)) {
                wp_enqueue_script(
                    'instructor-' . str_replace('.js', '', $js_file),
                    $js_url,
                    array('jquery'),
                    filemtime($js_path),
                    true
                );
            }
        }
        
        // Add the same localization as main plugin
        wp_localize_script('instructor-quiz-sidebar-injection', 'lilacQuizSidebar', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_quiz_sidebar_nonce'),
            'quizId' => $quiz_id,
            'hasSidebar' => $has_sidebar === '1',
            'enforceHint' => $enforce_hint === '1'
        ));
    }
    
    /**
     * Add fields to quick edit interface for instructors
     *
     * @param string $column_name Name of the column
     * @param string $post_type Post type
     */
    public function add_instructor_quick_edit_fields($column_name, $post_type) {
        if ($post_type !== 'sfwd-quiz' || !current_user_can('wdm_instructor')) {
            return;
        }
        
        if ($column_name === 'instructor_rich_sidebar') {
            ?>        
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label class="inline-edit-group">
                        <span class="title"><?php _e('Rich Sidebar', 'instructor-quiz-features'); ?></span>
                        <input type="checkbox" name="_ld_quiz_toggle_sidebar" value="1" />
                        <span class="checkbox-title"><?php _e('Enable', 'instructor-quiz-features'); ?></span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
        
        if ($column_name === 'instructor_forced_hint') {
            ?>        
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label class="inline-edit-group">
                        <span class="title"><?php _e('Forced Hint', 'instructor-quiz-features'); ?></span>
                        <input type="checkbox" name="_ld_quiz_enforce_hint" value="1" />
                        <span class="checkbox-title"><?php _e('Enable', 'instructor-quiz-features'); ?></span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Save quick edit data for instructor quizzes
     *
     * @param int $post_id Post ID
     */
    public function save_instructor_quick_edit_data($post_id) {
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            return;
        }
        
        if (get_post_type($post_id) !== 'sfwd-quiz') {
            return;
        }
        
        // Save sidebar setting
        if (isset($_POST['_ld_quiz_toggle_sidebar'])) {
            update_post_meta($post_id, '_ld_quiz_toggle_sidebar', '1');
        } else {
            delete_post_meta($post_id, '_ld_quiz_toggle_sidebar');
        }
        
        // Save enforce hint setting
        if (isset($_POST['_ld_quiz_enforce_hint'])) {
            update_post_meta($post_id, '_ld_quiz_enforce_hint', '1');
        } else {
            delete_post_meta($post_id, '_ld_quiz_enforce_hint');
        }
    }
    
    /**
     * AJAX handler for updating instructor quiz settings
     */
    public function ajax_update_instructor_quiz_settings() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'instructor_quiz_features_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('wdm_instructor') && !current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (isset($_POST['toggle_sidebar'])) {
            if ($_POST['toggle_sidebar'] === '1') {
                update_post_meta($post_id, '_ld_quiz_toggle_sidebar', '1');
            } else {
                delete_post_meta($post_id, '_ld_quiz_toggle_sidebar');
            }
        }
        
        if (isset($_POST['enforce_hint'])) {
            if ($_POST['enforce_hint'] === '1') {
                update_post_meta($post_id, '_ld_quiz_enforce_hint', '1');
            } else {
                delete_post_meta($post_id, '_ld_quiz_enforce_hint');
            }
        }
        
        wp_send_json_success('Settings updated successfully');
    }
}

// Initialize the extension
new Instructor_Quiz_Features();
