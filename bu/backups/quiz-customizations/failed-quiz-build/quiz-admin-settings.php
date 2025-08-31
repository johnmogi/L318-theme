<?php
/**
 * Quiz Extensions Admin Settings
 * 
 * Adds a settings page for LearnDash quiz extensions including Force Hint Mode
 * Features can be enabled for individual quizzes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Lilac_Quiz_Extensions {
    
    private static $instance = null;
    private $option_name = 'lilac_quiz_extensions_settings';
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add admin menu item
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue scripts with the settings
        add_action('wp_enqueue_scripts', array($this, 'enqueue_quiz_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=sfwd-quiz',
            'Quiz Extensions',
            'Quiz Extensions',
            'manage_options',
            'quiz-extensions',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'lilac_quiz_extensions_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $new_input = array();
        
        if (isset($input['quiz_extensions']) && is_array($input['quiz_extensions'])) {
            foreach ($input['quiz_extensions'] as $quiz_id => $options) {
                $quiz_id = absint($quiz_id);
                if ($quiz_id > 0) {
                    $new_input['quiz_extensions'][$quiz_id] = array(
                        'force_hint_mode' => isset($options['force_hint_mode']) ? 1 : 0,
                        'require_correct' => isset($options['require_correct']) ? 1 : 0,
                        'auto_show_hint' => isset($options['auto_show_hint']) ? 1 : 0,
                        'show_hint' => isset($options['show_hint']) ? 1 : 0,
                        'disable_topbar' => isset($options['disable_topbar']) ? 1 : 0,
                        'rich_sidebar' => isset($options['rich_sidebar']) ? 1 : 0
                    );
                }
            }
        }
        
        return $new_input;
    }
    
    /**
     * Get all quizzes
     */
    private function get_all_quizzes() {
        $args = array(
            'post_type' => 'sfwd-quiz',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Save settings if submitted
        if (isset($_POST['submit']) && isset($_POST['quiz_extensions'])) {
            check_admin_referer('lilac_quiz_extensions_nonce');
            $options = get_option($this->option_name, array());
            $options['quiz_extensions'] = $_POST['quiz_extensions'];
            update_option($this->option_name, $options);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        
        // Get current settings
        $options = get_option($this->option_name, array());
        $quizzes = $this->get_all_quizzes();
        ?>
        <div class="wrap">
            <h1>Quiz Extensions Settings</h1>
            
            <p>Configure extensions for each LearnDash quiz:</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('lilac_quiz_extensions_nonce'); ?>
                
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Quiz Title</th>
                            <th>Type</th>
                            <th>Force Hint Mode</th>
                            <th>Require Correct Answer</th>
                            <th>Auto-Show Hint</th>
                            <th>Show Hint</th>
                            <th>Disable Top Bar</th>
                            <th>Rich Sidebar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz) : 
                            $quiz_id = $quiz->ID;
                            $quiz_settings = isset($options['quiz_extensions'][$quiz_id]) 
                                ? $options['quiz_extensions'][$quiz_id] 
                                : array();
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($quiz->post_title); ?>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo get_edit_post_link($quiz_id); ?>">
                                                Edit
                                            </a>
                                        </span>
                                        |
                                        <span class="view">
                                            <a href="<?php echo get_permalink($quiz_id); ?>">
                                                View
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>LearnDash</td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][force_hint_mode]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['force_hint_mode']) ? $quiz_settings['force_hint_mode'] : 0, 1); ?>>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][require_correct]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['require_correct']) ? $quiz_settings['require_correct'] : 0, 1); ?>>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][auto_show_hint]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['auto_show_hint']) ? $quiz_settings['auto_show_hint'] : 0, 1); ?>>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][show_hint]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['show_hint']) ? $quiz_settings['show_hint'] : 0, 1); ?>>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][disable_topbar]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['disable_topbar']) ? $quiz_settings['disable_topbar'] : 0, 1); ?>>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           name="quiz_extensions[<?php echo $quiz_id; ?>][rich_sidebar]" 
                                           value="1" 
                                           <?php checked(isset($quiz_settings['rich_sidebar']) ? $quiz_settings['rich_sidebar'] : 0, 1); ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php submit_button(); ?>
                
                <h3>Feature Descriptions</h3>
                <table class="form-table">
                    <tr>
                        <th>Force Hint Mode</th>
                        <td>Hides correct/incorrect feedback and forces users to view hints to understand answers.</td>
                    </tr>
                    <tr>
                        <th>Require Correct Answer</th>
                        <td>Next button only works after user selects the correct answer.</td>
                    </tr>
                    <tr>
                        <th>Auto-Show Hint</th>
                        <td>Automatically shows hint after answering instead of requiring a click.</td>
                    </tr>
                    <tr>
                        <th>Show Hint</th>
                        <td>Enables the hint button for this quiz.</td>
                    </tr>
                    <tr>
                        <th>Disable Top Bar</th>
                        <td>Hides/disables the quiz top navigation bar.</td>
                    </tr>
                    <tr>
                        <th>Rich Sidebar</th>
                        <td>Enables the rich media sidebar for displaying additional content.</td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue quiz settings
     */
    public function enqueue_quiz_settings() {
        // Only on quiz pages
        if (!is_singular('sfwd-quiz')) {
            return;
        }
        
        $quiz_id = get_the_ID();
        $options = get_option($this->option_name, array());
        $quiz_settings = isset($options['quiz_extensions'][$quiz_id]) 
            ? $options['quiz_extensions'][$quiz_id] 
            : array();
        
        // Create settings object for this specific quiz
        $settings = array(
            'forceHintMode' => isset($quiz_settings['force_hint_mode']) ? (bool)$quiz_settings['force_hint_mode'] : false,
            'requireCorrectForNext' => isset($quiz_settings['require_correct']) ? (bool)$quiz_settings['require_correct'] : false,
            'autoShowHint' => isset($quiz_settings['auto_show_hint']) ? (bool)$quiz_settings['auto_show_hint'] : false,
            'showHint' => isset($quiz_settings['show_hint']) ? (bool)$quiz_settings['show_hint'] : true,
            'disableTopbar' => isset($quiz_settings['disable_topbar']) ? (bool)$quiz_settings['disable_topbar'] : false,
            'richSidebar' => isset($quiz_settings['rich_sidebar']) ? (bool)$quiz_settings['rich_sidebar'] : false,
            'quizId' => $quiz_id
        );
        
        // Add inline script
        wp_add_inline_script('jquery', 'window.lilacQuizSettings = ' . json_encode($settings) . ';', 'before');
    }
}

// Initialize
Lilac_Quiz_Extensions::get_instance();
