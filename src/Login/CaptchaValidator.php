<?php
/**
 * Captcha Validator Class
 *
 * Validates captcha responses
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CaptchaValidator {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        // Nothing to initialize
    }
    
    /**
     * Validate a captcha answer
     * 
     * @param string $answer The provided answer to validate
     * @return bool True if valid, false otherwise
     */
    public function validate($answer) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $expected_answer = isset($_SESSION['lilac_captcha_answer']) ? $_SESSION['lilac_captcha_answer'] : null;
        
        if (empty($expected_answer)) {
            return false;
        }
        
        // Clean the session for security
        unset($_SESSION['lilac_captcha_answer']);
        
        return (int)$answer === (int)$expected_answer;
    }
}

/**
 * Initialize the captcha validator functionality
 */
function lilac_captcha_validator() {
    return CaptchaValidator::get_instance();
}

// Initialize
add_action('plugins_loaded', 'Lilac\\Login\\lilac_captcha_validator');
