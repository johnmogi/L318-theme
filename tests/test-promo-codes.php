<?php
/**
 * Class Test_Promo_Codes
 *
 * @package Lilac
 */

class Test_Promo_Codes extends WP_UnitTestCase {
    
    private $registration_codes;
    private $test_codes = [];
    private $test_user_id;
    private $test_course_id;
    
    /**
     * Set up test environment
     */
    public function setUp() {
        parent::setUp();
        
        // Initialize the Registration_Codes instance
        $this->registration_codes = Registration_Codes::get_instance();
        
        // Create a test user
        $this->test_user_id = $this->factory->user->create([
            'role' => 'subscriber',
            'user_login' => 'testuser',
            'user_email' => 'test@example.com'
        ]);
        
        // Create a test LearnDash course if LearnDash is active
        if (function_exists('learndash_get_post_types')) {
            $this->test_course_id = $this->factory->post->create([
                'post_type' => 'sfwd-courses',
                'post_title' => 'Test Course',
                'post_status' => 'publish'
            ]);
        } else {
            $this->test_course_id = 999; // Dummy ID for tests without LearnDash
        }
        
        // Make sure we have a clean database state
        $this->clean_test_codes();
        
        // Generate test codes
        $this->generate_test_codes();
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown() {
        $this->clean_test_codes();
        
        // Delete test user
        if ($this->test_user_id) {
            wp_delete_user($this->test_user_id);
        }
        
        // Delete test course
        if ($this->test_course_id && get_post($this->test_course_id)) {
            wp_delete_post($this->test_course_id, true);
        }
        
        parent::tearDown();
    }
    
    /**
     * Remove all test codes from the database
     */
    private function clean_test_codes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'registration_codes';
        
        foreach ($this->test_codes as $code) {
            $wpdb->delete(
                $table_name,
                ['code' => $code],
                ['%s']
            );
        }
        
        $this->test_codes = [];
    }
    
    /**
     * Generate test codes with different configurations
     */
    private function generate_test_codes() {
        // Standard code
        $standard_code = 'TESTSTD' . rand(1000, 9999);
        $this->test_codes[] = $standard_code;
        $this->registration_codes->add_code($standard_code, 'subscriber', 'test_group');
        
        // Course-specific code
        $course_code = 'TESTCRS' . rand(1000, 9999);
        $this->test_codes[] = $course_code;
        $this->registration_codes->add_code($course_code, 'subscriber', 'test_group', $this->test_course_id);
        
        // Multi-use code (3 uses)
        $multi_code = 'TESTMUL' . rand(1000, 9999);
        $this->test_codes[] = $multi_code;
        $this->registration_codes->add_code($multi_code, 'subscriber', 'test_group', null, 3);
        
        // Expired code
        $expired_code = 'TESTEXP' . rand(1000, 9999);
        $this->test_codes[] = $expired_code;
        $this->registration_codes->add_code(
            $expired_code, 
            'subscriber', 
            'test_group', 
            null, 
            1, 
            date('Y-m-d H:i:s', strtotime('-1 day'))
        );
    }
    
    /**
     * Test basic promo code validation
     */
    public function test_promo_code_validation_basic() {
        // Test empty code
        $result = lilac_validate_promo_code('');
        $this->assertFalse($result['status']);
        $this->assertEquals('empty_code', $result['error_type']);
        
        // Test invalid characters
        $result = lilac_validate_promo_code('INV@LID!');
        $this->assertFalse($result['status']);
        
        // Test nonexistent code
        $result = lilac_validate_promo_code('NONEXISTENT');
        $this->assertFalse($result['status']);
        $this->assertEquals('not_found', $result['error_type']);
    }
    
    /**
     * Test course-specific code validation
     */
    public function test_promo_code_validation_course_specific() {
        // Get course-specific code (second in our test array)
        $course_code = $this->test_codes[1];
        
        // Test with correct course ID
        $result = lilac_validate_promo_code($course_code, $this->test_course_id);
        $this->assertTrue($result['status'], 'Code should be valid for the correct course');
        
        // Test with wrong course ID
        $wrong_course_id = $this->test_course_id + 1;
        $result = lilac_validate_promo_code($course_code, $wrong_course_id);
        $this->assertFalse($result['status'], 'Code should be invalid for wrong course');
        $this->assertEquals('wrong_course', $result['error_type']);
    }
    
    /**
     * Test expired code validation
     */
    public function test_promo_code_validation_expiry() {
        // Get expired code (fourth in our test array)
        $expired_code = $this->test_codes[3];
        
        // Test expired code
        $result = lilac_validate_promo_code($expired_code);
        $this->assertFalse($result['status'], 'Expired code should be invalid');
        $this->assertEquals('expired', $result['error_type']);
    }
    
    /**
     * Test multi-use code validation
     */
    public function test_promo_code_multi_use() {
        // Login as test user
        wp_set_current_user($this->test_user_id);
        
        // Get multi-use code (third in our test array)
        $multi_code = $this->test_codes[2];
        
        // Test first use
        $result = lilac_validate_promo_code($multi_code);
        $this->assertTrue($result['status'], 'First use of multi-use code should be valid');
        
        // Test second use
        $result = lilac_validate_promo_code($multi_code);
        $this->assertTrue($result['status'], 'Second use of multi-use code should be valid');
        
        // Test third use
        $result = lilac_validate_promo_code($multi_code);
        $this->assertTrue($result['status'], 'Third use of multi-use code should be valid');
        
        // Test fourth use (should fail)
        $result = lilac_validate_promo_code($multi_code);
        $this->assertFalse($result['status'], 'Fourth use of multi-use code should be invalid');
        $this->assertEquals('max_uses', $result['error_type']);
    }
    
    /**
     * Test the shortcode functionality
     */
    public function test_promo_code_shortcode() {
        // Login as test user
        wp_set_current_user($this->test_user_id);
        
        // Test basic shortcode output
        $output = lilac_promo_code_display([]);
        $this->assertNotEmpty($output);
        $this->assertContains('lilac-promo-code-form', $output);
        $this->assertContains('wp_nonce_field', $output);
        
        // Test with custom attributes
        $custom_atts = [
            'title' => 'Custom Title',
            'description' => 'Custom Description',
            'button_text' => 'Custom Button',
            'course_id' => $this->test_course_id
        ];
        
        $output = lilac_promo_code_display($custom_atts);
        $this->assertContains('Custom Title', $output);
        $this->assertContains('Custom Description', $output);
        $this->assertContains('Custom Button', $output);
    }
}
