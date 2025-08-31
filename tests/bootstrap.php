<?php
// Bootstrap file for WordPress PHPUnit tests
// Adjust WP_TESTS_DIR environment or define path to WP test lib
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    // Default path where WP tests reside
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the theme files to test
 */
function _load_registration_codes_theme() {
    // Load WordPress theme functions
    require dirname( dirname( __FILE__ ) ) . '/functions.php';
    
    // Load Registration Codes class
    require dirname( dirname( __FILE__ ) ) . '/includes/registration/class-registration-codes.php';
    
    // Load Promo Code functionality
    require dirname( dirname( __FILE__ ) ) . '/includes/promo-code.php';
    
    // Initialize the Registration_Codes instance for testing
    Registration_Codes::get_instance();
}
tests_add_filter( 'muplugins_loaded', '_load_registration_codes_theme' );

require $_tests_dir . '/includes/bootstrap.php';
