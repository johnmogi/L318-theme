<?php
/**
 * Captcha Class
 *
 * Simple captcha implementation for form protection
 *
 * @package Hello_Child_Theme
 * @subpackage Login
 */

namespace Lilac\Login;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Captcha {
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
     * Generate a simple math captcha
     * 
     * @return array Captcha data including question and answer
     */
    public function generate_math_captcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $answer = $num1 + $num2;
        
        $captcha = array(
            'question' => sprintf(__('כמה זה %d + %d?', 'hello-theme-child'), $num1, $num2),
            'answer' => $answer
        );
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['lilac_captcha_answer'] = $answer;
        
        return $captcha;
    }
    
    /**
     * Display captcha field
     */
    public function display_captcha_field() {
        $captcha = $this->generate_math_captcha();
        
        echo '<p>';
        echo '<label>' . esc_html($captcha['question']) . '</label><br>';
        echo '<input type="text" name="captcha_answer" required>';
        echo '</p>';
    }
}

/**
 * Initialize the captcha functionality
 */
function lilac_captcha() {
    return Captcha::get_instance();
}

// Initialize
add_action('plugins_loaded', 'Lilac\\Login\\lilac_captcha');
