<?php
/**
 * Registration Wizard
 * Handles the multi-step registration code generation wizard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure the main registration codes class is loaded
if (!class_exists('Registration_Codes')) {
    return;
}

class Registration_Wizard {
    private static $instance = null;
    private $version = '1.0.0';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register admin menu with lower priority to ensure main menu exists
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register AJAX handler
        add_action('wp_ajax_save_wizard_step', array($this, 'ajax_save_step'));
        
        // Add settings link
        add_filter('plugin_action_links', array($this, 'add_settings_link'), 10, 2);
    }
    
    public function add_admin_menu() {
        // Debug: Check if we're in admin
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== Registration Wizard: add_admin_menu called ===');
            error_log('Current user can manage_options: ' . (current_user_can('manage_options') ? 'yes' : 'no'));
        }
        
        // Get the parent menu slug from the Registration_Codes class
        $parent_slug = 'registration-codes';
        
        // Try to find the parent menu by its title
        global $menu, $submenu;
        $found = false;
        
        if (!empty($menu)) {
            foreach ($menu as $item) {
                if (isset($item[0]) && (stripos($item[0], 'קודי הרשמה') !== false || 
                                       stripos($item[0], 'Registration Codes') !== false)) {
                    $parent_slug = $item[2];
                    $found = true;
                    break;
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Parent menu slug to use: ' . $parent_slug);
            error_log('Found parent menu: ' . ($found ? 'yes' : 'no'));
        }
        
        // If we didn't find the parent menu, don't add the submenu
        if (!$found) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Parent menu not found. Not adding wizard submenu.');
            }
            return;
        }
        
        // Add as submenu under the existing registration codes menu
        $hook = add_submenu_page(
            $parent_slug, // Parent slug
            'אשף יצירת כיתה וקודי הרשמה', // Page title
            'אשף יצירת קודים', // Menu title
            'manage_options', // Capability
            'registration-wizard', // Menu slug
            array($this, 'render_wizard_page'), // Callback function
            30 // Position
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Registration Wizard: Menu added with hook: ' . ($hook ? $hook : 'failed'));
            if (!$hook) {
                error_log('Failed to add submenu. Parent slug: ' . $parent_slug);
                error_log('Available menus: ' . print_r($menu, true));
                error_log('Available submenus: ' . print_r($submenu, true));
            } else {
                error_log('Successfully added wizard submenu');
            }
        }
    }
    
    public function enqueue_scripts($hook) {
        if ('registration-codes_page_registration-wizard' !== $hook) {
            return;
        }
        
        // Basic styles
        wp_enqueue_style(
            'registration-wizard-css',
            get_stylesheet_directory_uri() . '/includes/registration/css/wizard.css',
            array(),
            $this->version
        );
        
        // Main wizard script
        wp_enqueue_script(
            'registration-wizard-js',
            get_stylesheet_directory_uri() . '/includes/registration/js/registration-wizard.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script(
            'registration-wizard-js',
            'registrationWizard',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('registration_wizard_nonce')
            )
        );
    }
    
    public function render_wizard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('אין לך הרשאות מתאימות');
        }
        
        // Simple HTML for testing
        ?>
        <div class="wrap registration-wizard">
            <h1>אשף יצירת כיתה וקודי הרשמה</h1>
            
            <div class="wizard-steps">
                <div class="step active" data-step="1">
                    <h2>שלב 1: פרטי המורה</h2>
                    <div class="step-content">
                        <p>פרטי המורה ימופיעו כאן</p>
                        <button class="button button-primary next-step" data-next="2">הבא</button>
                    </div>
                </div>
                
                <div class="step" data-step="2">
                    <h2>שלב 2: פרטי הכיתה</h2>
                    <div class="step-content">
                        <p>פרטי הכיתה יופיעו כאן</p>
                        <button class="button prev-step" data-prev="1">חזור</button>
                        <button class="button button-primary next-step" data-next="3">הבא</button>
                    </div>
                </div>
                
                <div class="step" data-step="3">
                    <h2>שלב 3: יצירת קודים</h2>
                    <div class="step-content">
                        <p>יצירת קודי הרשמה תופיע כאן</p>
                        <button class="button prev-step" data-prev="2">חזור</button>
                        <button class="button button-primary" id="generate-codes">צור קודים</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add settings link on plugin page
     */
    public function add_settings_link($links, $file) {
        if (strpos($file, 'registration-codes.php') !== false) {
            $settings_link = '<a href="' . admin_url('admin.php?page=registration-wizard') . '">' . __('Wizard', 'registration-codes') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }
}

// Initialize the wizard after the main registration codes plugin
function init_registration_wizard() {
    // Make sure the registration codes class is loaded first
    if (class_exists('Registration_Codes')) {
        // Initialize the wizard
        Registration_Wizard::get_instance();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>אשף הקודים דורש את תוסף קודי הרישום להיות פעיל.</p></div>';
        });
    }
}
// Initialize after plugins are loaded
add_action('plugins_loaded', 'init_registration_wizard', 20);
