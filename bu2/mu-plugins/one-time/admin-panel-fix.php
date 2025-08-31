<?php
/**
 * One-time admin panel fix
 * 
 * This plugin will:
 * 1. Fix admin panel visibility
 * 2. Ensure testihrt_admin has proper admin capabilities
 * 3. Remove any conflicting instructor roles
 */

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Panel_Fix {
    private static $instance = null;
    private static $completed = false;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Only run once
        if (self::$completed) {
            return;
        }
        
        // Add admin styles
        add_action('admin_head', array($this, 'add_admin_styles'));
        
        // Fix testihrt_admin capabilities
        add_action('admin_init', array($this, 'fix_testihrt_admin'));
        
        // Mark as completed
        self::$completed = true;
    }
    
    /**
     * Add styles to fix admin panel visibility
     */
    public function add_admin_styles() {
        ?>
        <style>
            .welcome-panel-content, 
            .welcome-panel-content h2,
            .welcome-panel-content h3,
            .welcome-panel-content p,
            .welcome-panel-content li,
            .welcome-panel-content .about-description {
                color: #fff !important;
            }
        </style>
        <?php
    }
    
    /**
     * Fix testihrt_admin capabilities
     */
    public function fix_testihrt_admin() {
        // Only run once
        if (get_option('admin_panel_fix_completed')) {
            return;
        }
        
        $user = get_user_by('login', 'testihrt_admin');
        if ($user) {
            // Remove conflicting roles
            $user->remove_role('wdm_instructor');
            $user->remove_role('school_teacher');
            
            // Add admin role
            $user->add_role('administrator');
            
            // Ensure proper capabilities
            $capabilities = array(
                'read' => true,
                'edit_posts' => true,
                'edit_pages' => true,
                'edit_users' => true,
                'edit_files' => true,
                'manage_options' => true,
                'administrator' => true
            );
            
            foreach ($capabilities as $cap => $grant) {
                $user->add_cap($cap, $grant);
            }
            
            // Log the change
            error_log('Admin Panel Fix: Updated testihrt_admin capabilities');
            
            // Mark as completed
            update_option('admin_panel_fix_completed', true);
            
            // Show admin notice
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success">
                    <p>Successfully fixed admin panel visibility and restored testihrt_admin capabilities.</p>
                </div>
                <?php
            });
        }
    }
}

// Initialize
Admin_Panel_Fix::instance();
