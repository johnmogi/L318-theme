<?php
/**
 * Admin page template for Registration Codes
 * 
 * @package Registration_Codes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get instance of the registration codes class
$registration_codes = Registration_Codes::get_instance();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manage';
if (!in_array($current_tab, array('manage', 'generate', 'import'))) {
    $current_tab = 'manage';
}

// Clean up any duplicate notices
remove_all_actions('admin_notices');
?>
<div class="wrap registration-codes">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    // Display admin notices
    settings_errors('registration_codes_messages');
    
    // Define main tabs in Hebrew
    $tabs = array(
        'manage' => 'ניהול קודים',
        'generate' => 'צור קודים',
        'import' => 'ייבוא/ייצוא',
    );
    ?>
    
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab => $name) : ?>
            <a href="?page=registration-codes&tab=<?php echo esc_attr($tab); ?>" 
               class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>"
               data-tab="<?php echo esc_attr($tab); ?>">
                <?php echo esc_html($name); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="registration-codes-content">
        <?php 
        // Include only the active tab content
        $template_path = __DIR__ . '/' . $current_tab . '-codes.php';
        if ('import' === $current_tab) {
            $template_path = __DIR__ . '/import-export.php';
        }
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' . 
                 sprintf(
                     __('Template file not found for tab: %s', 'registration-codes'),
                     '<code>' . esc_html($current_tab) . '</code>'
                 ) . 
                 '</p></div>';
        }
        ?>
    </div>
</div>
