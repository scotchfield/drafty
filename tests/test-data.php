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
	 * @covers DraftyData::get_post_shares
	 * @covers DraftyData::get_visible_post_shares
	 */
	public function test_get_visible_post_shares_empty_invalid_post() {
		$this->assertEmpty( $this->class->get_visible_post_shares( -1, -1 ) );
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
	 * @covers DraftyData::set_post_shares
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
	 * @covers DraftyData::get_visible_post_shares
	 * @covers DraftyData::set_post_shares
	 */
	public function test_add_share_and_get_visible_post_shares() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$shares = $this->class->get_visible_post_shares( $user_id, $post_id );

		$this->assertCount( 1, $shares );
		$this->assertEquals( $user_id, $shares[ $key ][ 'user_id' ] );
		$this->assertEquals( $post_id, $shares[ $key ][ 'post_id' ] );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::get_visible_post_shares
	 * @covers DraftyData::set_post_shares
	 */
	public function test_add_share_and_get_visible_post_shares_invisible() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertEmpty( $this->class->get_visible_post_shares( $user_id + 1, $post_id ) );
	}

	/**
	 * @covers DraftyData::delete_share
	 * @covers DraftyData::set_post_shares
	 */
	public function test_delete_share_is_false_with_non_existing_share() {
		$this->assertFalse( $this->class->delete_share( 1, 2, 3 ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::delete_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::set_post_shares
	 */
	public function test_add_and_delete_share_does_not_exist() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertTrue( $this->class->delete_share( $user_id, $post_id, $key ) );
		$this->assertFalse( $this->class->share_exists( $post_id, $key ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::delete_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::set_post_shares
	 */
	public function test_add_and_delete_share_different_user_does_not_work() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$this->assertFalse( $this->class->delete_share( $user_id + 1, $post_id, $key ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );
	}

	/**
	 * @covers DraftyData::extend_share
	 */
	public function test_extend_share_is_false_with_non_existing_share() {
		$this->assertFalse( $this->class->extend_share( 1, 2, 3, 4 ) );
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::extend_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::get_visible_post_shares
	 */
	public function test_add_and_extend_share_does_not_exist() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$shares = $this->class->get_visible_post_shares( $user_id, $post_id );

		$this->assertTrue( $this->class->extend_share( $user_id, $post_id, $key, $time ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );

		$shares_extend = $this->class->get_visible_post_shares( $user_id, $post_id );

		$this->assertGreaterThan(
			$shares[ $key ][ 'expires' ],
			$shares_extend[ $key ][ 'expires' ]
		);
	}

	/**
	 * @covers DraftyData::add_share
	 * @covers DraftyData::extend_share
	 * @covers DraftyData::share_exists
	 * @covers DraftyData::get_visible_post_shares
	 */
	public function test_add_and_extend_share_different_user_does_not_work() {
		$user_id = 1;
		$post_id = 2;
		$time = 100;

		$key = $this->class->add_share( $user_id, $post_id, $time );

		$shares = $this->class->get_visible_post_shares( $user_id, $post_id );

		$this->assertFalse( $this->class->extend_share( $user_id + 1, $post_id, $key, $time ) );
		$this->assertTrue( $this->class->share_exists( $post_id, $key ) );

		$shares_extend = $this->class->get_visible_post_shares( $user_id, $post_id );

		$this->assertEquals(
			$shares[ $key ][ 'expires' ],
			$shares_extend[ $key ][ 'expires' ]
		);
	}

}
