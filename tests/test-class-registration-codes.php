<?php
/**
 * Tests for CCR_Registration_Codes core methods.
 */
class Test_CCR_Registration_Codes extends WP_UnitTestCase {
    /** @var CCR_Registration_Codes */
    protected static $registration_codes;

    public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
        self::$registration_codes = new CCR_Registration_Codes();
        self::$registration_codes->create_table();
    }

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'registration_codes' );
    }

    public function test_generate_unique_code_format() {
        $code = self::$registration_codes->generate_unique_code();
        $this->assertStringStartsWith( 'LCH', $code );
        $this->assertEquals( 9, strlen( $code ) );
    }

    public function test_generate_codes_inserts_count() {
        $codes = self::$registration_codes->generate_codes( 5, 'test_group' );
        $this->assertCount( 5, $codes );
        global $wpdb;
        $count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'registration_codes' );
        $this->assertEquals( 5, intval( $count ) );
    }

    public function test_code_exists_functionality() {
        $codes = self::$registration_codes->generate_codes( 1, 'group' );
        $this->assertTrue( self::$registration_codes->code_exists( $codes[0] ) );
        $this->assertFalse( self::$registration_codes->code_exists( 'INVALID_CODE' ) );
    }

    public function test_get_groups_and_count_codes() {
        self::$registration_codes->generate_codes( 3, 'g1' );
        self::$registration_codes->generate_codes( 2, 'g2' );
        $groups = self::$registration_codes->get_groups();
        $this->assertEquals( array( 'g1', 'g2' ), $groups );
        $this->assertEquals( 3, self::$registration_codes->count_codes( 'g1' ) );
        $this->assertEquals( 2, self::$registration_codes->count_codes( 'g2' ) );
    }

    public function test_get_codes_pagination() {
        self::$registration_codes->generate_codes( 30, 'pag_group' );
        $page1 = self::$registration_codes->get_codes( 0, 10, 'pag_group' );
        $this->assertCount( 10, $page1 );
        $page2 = self::$registration_codes->get_codes( 10, 10, 'pag_group' );
        $this->assertCount( 10, $page2 );
        $this->assertNotEquals( $page1[0], $page2[0] );
    }

    public function test_teacher_groups_and_codes() {
        $teacher1 = $this->factory->user->create( array( 'role' => 'author' ) );
        $teacher2 = $this->factory->user->create( array( 'role' => 'author' ) );
        wp_set_current_user( $teacher1 );
        self::$registration_codes->generate_codes( 2, 'tg1' );
        wp_set_current_user( $teacher2 );
        self::$registration_codes->generate_codes( 3, 'tg2' );
        $groups1 = self::$registration_codes->get_teacher_groups( $teacher1 );
        $this->assertEquals( array( 'tg1' ), $groups1 );
        $this->assertEquals( 2, self::$registration_codes->count_teacher_codes( $teacher1 ) );
        $codes1 = self::$registration_codes->get_teacher_codes( $teacher1, 0, 10 );
        $this->assertCount( 2, $codes1 );
    }
}
