<?php
/**
 * Plugin Name: Admin Menu Translations
 * Description: Force Hebrew translations for School Manager Lite menu items
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Menu_Translations {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_head', array($this, 'add_menu_translations'));
    }
    
    public function add_menu_translations() {
        global $menu;
        
        // Translate main menu item
        if (isset($menu[30][0])) {
            $menu[30][0] = 'ניהול בית ספר';
        }
        
        // Translate submenus
        global $submenu;
        if (isset($submenu['school-manager'])) {
            $translations = array(
                'Dashboard' => 'ממשק ניהול',
                'Teachers' => 'מורים',
                'Classes' => 'כיתות',
                'Students' => 'תלמידים',
                'Promo Codes' => 'קודי הנחה',
                'Generate Codes' => 'יצירת קוד',
                'Import/Export' => 'ייבוא /ייצוא'
            );
            
            foreach ($submenu['school-manager'] as &$item) {
                if (isset($item[0]) && isset($translations[$item[0]])) {
                    $item[0] = $translations[$item[0]];
                }
            }
        }
    }
}

// Initialize
Admin_Menu_Translations::instance();
