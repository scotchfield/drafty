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

}