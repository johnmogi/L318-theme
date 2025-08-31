<?php
/**
 * Custom User Redirects and Admin
 * Handles login redirects and admin interface customization based on user roles
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Debug: Check if file is loaded
error_log('CUSTOM USER REDIRECTS LOADED - ' . date('Y-m-d H:i:s'));

// Teacher dashboard functionality has been moved to the unified class management dashboard

// Handle login redirects
add_filter('login_redirect', 'custom_login_redirect', 10, 3);
function custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('school_teacher', $user->roles) || in_array('administrator', $user->roles)) {
            return admin_url('admin.php?page=school-manager');
        } else {
            return get_author_posts_url($user->ID);
        }
    }
    return $redirect_to;
}

// Clean up admin for school teachers - run early
add_action('init', 'custom_admin_for_teachers', 1);
function custom_admin_for_teachers() {
    if (is_user_logged_in() && current_user_can('school_teacher')) {
        // Remove admin menu items
        add_action('admin_menu', 'remove_admin_menus', 9999);
        
        // Customize admin bar
        add_action('wp_before_admin_bar_render', 'customize_admin_bar', 9999);
        
        // Custom admin footer
        add_filter('admin_footer_text', 'custom_admin_footer');
        
        // Remove help tabs
        add_action('admin_head', 'remove_help_tabs', 9999);
    }
}

function remove_admin_menus() {
    global $menu, $submenu;
    
    // List of menu items to keep
    $allowed_menus = array(
        'index.php', // Dashboard
        'separator1', // First separator
        'school-manager' // Unified class management dashboard
    );
    
    // First, remove all menus except allowed ones
    if (isset($menu)) {
        foreach ($menu as $menu_key => $menu_item) {
            if (!in_array($menu_item[2], $allowed_menus) && 
                strpos($menu_item[2], 'separator') === false) {
                remove_menu_page($menu_item[2]);
            }
        }
    }
    
    // Clean up submenus for remaining top-level menus
    if (isset($submenu) && is_array($submenu)) {
        foreach ($submenu as $parent_slug => $submenu_items) {
            // Skip our class management menu
            if ($parent_slug === 'school-manager') {
                continue;
            }
            
            // Remove all submenus for other top-level menus
            if (is_array($submenu_items)) {
                foreach ($submenu_items as $submenu_item) {
                    if (isset($submenu_item[2])) {
                        remove_submenu_page($parent_slug, $submenu_item[2]);
                    }
                }
            }
        }
    }
    
    // Remove dashboard widgets
    add_action('wp_dashboard_setup', 'remove_dashboard_widgets', 9999);
}

function remove_dashboard_widgets() {
    global $wp_meta_boxes;
    
    // Remove all dashboard widgets
    if (isset($wp_meta_boxes['dashboard'])) {
        unset($wp_meta_boxes['dashboard']['normal']['core']);
        unset($wp_meta_boxes['dashboard']['side']['core']);
        unset($wp_meta_boxes['dashboard']['normal']['high']);
        unset($wp_meta_boxes['dashboard']['side']['low']);
    }
    
    // Add a custom welcome message
    wp_add_dashboard_widget(
        'welcome_teacher',
        'Welcome to Your Teacher Dashboard',
        'welcome_teacher_widget'
    );
}

function welcome_teacher_widget() {
    echo '<p>Welcome to your teacher dashboard. This is your central hub for managing your courses and students.</p>';
}

function remove_help_tabs() {
    $screen = get_current_screen();
    if ($screen) {
        $screen->remove_help_tabs();
    }
}

function customize_admin_bar() {
    global $wp_admin_bar;
    
    // Remove all admin bar items except for site name and user info
    $wp_admin_bar->remove_menu('wp-logo');
    $wp_admin_bar->remove_menu('about');
    $wp_admin_bar->remove_menu('wporg');
    $wp_admin_bar->remove_menu('documentation');
    $wp_admin_bar->remove_menu('support-forums');
    $wp_admin_bar->remove_menu('feedback');
    $wp_admin_bar->remove_menu('updates');
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('new-content');
    $wp_admin_bar->remove_menu('customize');
    
    // Add a custom dashboard link for teachers/admins
    if (current_user_can('school_teacher') || current_user_can('administrator')) {
        $wp_admin_bar->add_menu(array(
            'id'    => 'dashboard',
            'title' => 'Dashboard',
            'href'  => admin_url('admin.php?page=school-manager')
        ));
    }
}

function custom_admin_footer() {
    return 'School Teacher Dashboard &copy; ' . date('Y') . ' - ' . get_bloginfo('name');
}

// Add custom user roles and capabilities
add_action('after_setup_theme', 'custom_user_roles_capabilities');
function custom_user_roles_capabilities() {
    if (!get_role('school_teacher')) {
        add_role('school_teacher', __('School Teacher'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
        ));
    }
    
    $role = get_role('school_teacher');
    if ($role) {
        $capabilities = array(
            'edit_courses', 'edit_published_courses', 'publish_courses',
            'delete_published_courses', 'edit_others_courses', 'delete_others_courses'
        );
        
        foreach ($capabilities as $cap) {
            $role->add_cap($cap);
        }
    }
}

// Force refresh to ensure all changes take effect
if (is_admin() && current_user_can('school_teacher')) {
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'index.php' || $pagenow === 'profile.php') {
wp_redirect(admin_url('admin.php?page=school-manager'));
            exit;
        }
    });
}
