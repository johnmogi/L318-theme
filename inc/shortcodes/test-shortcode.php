<?php
/**
 * Test Shortcode
 * 
 * Shortcode: [lilac_test_shortcode]
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function lilac_test_shortcode($atts = []) {
    return '<div style="background: #f0f0f0; border: 1px solid #ccc; padding: 20px; margin: 10px 0;">
        <h3>Test Shortcode Working!</h3>
        <p>This is a test shortcode to verify shortcode functionality.</p>
        <p>Current time: ' . current_time('mysql') . '</p>
    </div>';
}
add_shortcode('lilac_test_shortcode', 'lilac_test_shortcode');
