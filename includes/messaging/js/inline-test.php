<?php
/**
 * Inline JavaScript Test
 * Add this to your theme's functions.php to test the alert system directly
 */

// Don't load directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add inline JavaScript test to footer
 */
function lilac_add_inline_toast_test() {
    ?>
    <script type="text/javascript">
    /* Inline Toast Test Script */
    console.log('Inline Toast Test Script Loaded');
    
    // Create global test function that doesn't depend on other scripts
    window.InlineToastTest = function() {
        console.log('Running inline toast test');
        
        // Try accessing LilacToast API
        if (window.LilacToast) {
            console.log('LilacToast API found!');
            window.LilacToast.success('Toast API is working!');
        } else {
            console.log('LilacToast API not found');
            alert('This is a native alert - LilacToast not loaded');
        }
        
        return 'Test completed';
    };
    
    // Add to window load event
    window.addEventListener('load', function() {
        console.log('Window loaded - Test if toast scripts are available:');
        console.log('- LilacToast available:', typeof window.LilacToast !== 'undefined');
        console.log('- alert overridden:', window.alert !== window.originalAlert);
    });
    </script>
    <?php
}
add_action('wp_footer', 'lilac_add_inline_toast_test');
