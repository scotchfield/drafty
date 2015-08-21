<?php

class TestDraftyData extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->class = new DraftyData();
	}

	public function tearDown() {
		unset( $this->class );

		parent::tearDown();
	}

	public function test_new() {
		$this->assertNotNull( $this->class );
	}

	/**
	 * @covers DraftyData::get_shared_keys
	 */
	public function test_get_shared_keys_empty() {
		$this->assertEmpty( $this->class->get_shared_keys() );
	}

	/**
	 * @covers DraftyData::get_shared_keys
	 * @covers DraftyData::set_shared_keys
	 */
	public function test_set_shared_keys_test_array() {
		$test = array( 'this' => 'that' );

		$this->class->set_shared_keys( $test );

		$this->assertEquals( $test, $this->class->get_shared_keys() );
	}

	/**
	 * @covers DraftyData::get_visible_post_shared_keys
	 */
	public function test_get_visible_post_shared_keys_empty_invalid_post() {
		$this->assertEmpty( $this->class->get_visible_post_shared_keys( -1, -1 ) );
	}

	/**
	 * @covers DraftyData::share_exists
	 */
	public function test_share_exists_false_if_not_existing() {
		$this->assertFalse( $this->class->share_exists( -1, -1 ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::share_exists
	 */
	public function test_add_share_and_share_exists() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );
	}

}
