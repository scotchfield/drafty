<?php

class TestDraftyData extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->class = new DraftyData();
	}

	public function tearDown() {
		$this->class->set_shared_keys( array() );

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
	 * @covers DraftyData::get_share_by_key
	 */
	public function test_get_share_by_key_false_if_not_existing() {
		$this->assertFalse( $this->class->get_share_by_key( -1 ) );
	}

	/**
	 * @covers DraftyData::get_share_by_key
	 * @covers DraftyData::set_share_by_key
	 */
	public function test_set_and_get_share_by_key() {
		$key = 'test_key';
		$share = array( 'test' );

		$this->class->set_share_by_key( $key, $share );

		$this->assertEquals( $share, $this->class->get_share_by_key( $key ) );
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

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::get_visible_post_shared_keys
	 */
	public function test_add_share_and_get_visible_post_shared_keys() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$shares = $this->class->get_visible_post_shared_keys( $user_id, $post_id );

		$this->assertCount( 1, $shares );
		$this->assertEquals( $user_id, $shares[ $key ][ 'user_id' ] );
		$this->assertEquals( $post_id, $shares[ $key ][ 'post_id' ] );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::get_visible_post_shared_keys
	 */
	public function test_add_share_and_get_visible_post_shared_keys_invisible() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertEmpty( $this->class->get_visible_post_shared_keys( $user_id + 1, $post_id ) );
	}

	/**
	 * @covers DraftyData::delete_share
	 */
	public function test_delete_share_is_false_with_non_existing_share() {
		$this->assertFalse( $this->class->delete_share( 1, 2 ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::delete_share
	 * @covers DraftyData::share_exists
	 */
	public function test_add_and_delete_share_does_not_exist() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertTrue( $this->class->delete_share( $user_id, $key ) );
		$this->assertFalse( $this->class->share_exists( $post_id, $key ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::delete_share
	 * @covers DraftyData::share_exists
	 */
	public function test_add_and_delete_share_different_user_does_not_work() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertFalse( $this->class->delete_share( $user_id + 1, $key ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );
	}

	/**
	 * @covers DraftyData::extend_share
	 */
	public function test_extend_share_is_false_with_non_existing_share() {
		$this->assertFalse( $this->class->extend_share( 1, 2, 3 ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::extend_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::get_share_by_key
	 */
	public function test_add_and_extend_share_does_not_exist() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$share = $this->class->get_share_by_key( $key );

		$this->assertTrue( $this->class->extend_share( $user_id, $key, $time ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );

		$share_extend = $this->class->get_share_by_key( $key );

		$this->assertGreaterThan( $share[ 'expires' ], $share_extend[ 'expires' ] );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::extend_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::get_share_by_key
	 */
	public function test_add_and_extend_share_different_user_does_not_work() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$share = $this->class->get_share_by_key( $key );

		$this->assertFalse( $this->class->extend_share( $user_id + 1, $key, $time ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );

		$share_extend = $this->class->get_share_by_key( $key );

		$this->assertEquals( $share[ 'expires' ], $share_extend[ 'expires' ] );
	}

}
