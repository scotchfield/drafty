<?php

class TestDrafty extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->class = new Drafty();
		$this->class->drafty_share->set_shared_keys( null );
	}

	public function tearDown() {
		delete_transient( Drafty::DOMAIN . 'notice' );

		unset( $this->class );

		unset( $_POST );

		parent::tearDown();
	}

	/**
	 * @covers Drafty::__construct
	 */
	public function test_new() {
		$this->assertNotNull( $this->class );
	}

	/**
	 * @covers Drafty::init
	 * @covers Drafty::admin_page_init
	 */
	public function test_init() {
		$this->class->init();

		$this->assertGreaterThan(
			0, has_action( 'admin_menu', array( $this->class, 'add_admin_pages' ) )
		);
		$this->assertGreaterThan(
			0, has_action( 'posts_results', array( $this->class, 'posts_results' ) )
		);
		$this->assertGreaterThan(
			0, has_action( 'the_posts', array( $this->class, 'the_posts' ) )
		);

		$this->assertTrue( wp_style_is( 'drafty_style', 'registered' ) );
		$this->assertTrue( wp_script_is( 'drafty_script', 'registered' ) );
	}

	/**
	 * @covers Drafty::add_admin_pages
	 */
	public function test_add_admin_pages() {
		$this->class->add_admin_pages();

		$this->assertArrayHasKey( Drafty::DOMAIN, $GLOBALS[ 'admin_page_hooks' ] );
	}

	/**
	 * @covers Drafty::add_admin_pages
	 * @covers Drafty::admin_page_styles
	 */
	public function test_enqueue_style() {
		$this->class->add_admin_pages();
		$this->class->admin_page_styles();

		$this->assertTrue( wp_style_is( 'drafty_style', 'enqueued' ) );
	}

	/**
	 * @covers Drafty::add_admin_pages
	 * @covers Drafty::admin_page_scripts
	 */
	public function test_enqueue_script() {
		$this->class->add_admin_pages();
		$this->class->admin_page_scripts();

		$this->assertTrue( wp_script_is( 'drafty_script', 'enqueued' ) );
	}

	/**
	 * @covers Drafty::get_shared_keys
	 */
	public function test_get_shared_keys_empty() {
		$this->assertEmpty( $this->class->drafty_share->get_shared_keys() );
	}

	/**
	 * @covers Drafty::get_shared_keys
	 * @covers Drafty::set_shared_keys
	 */
	public function test_set_shared_keys_test_array() {
		$test = array( 'this' => 'that' );

		$this->class->drafty_share->set_shared_keys( $test );

		$this->assertEquals( $test, $this->class->drafty_share->get_shared_keys() );
	}

	/**
	 * @covers Drafty::get_visible_post_shared_keys
	 */
	public function test_get_visible_post_shared_keys_empty_invalid_post() {
		$this->assertEmpty( $this->class->drafty_share->get_visible_post_shared_keys( -1 ) );
	}

	/**
	 * @covers Drafty::can_post_status_share
	 */
	public function test_can_post_status_share() {
		$expected = array(
			'draft' => true,
			'future' => true,
			'pending' => true,

			'publish' => false,
			'private' => false,
			'trash' => false,
			'auto-draft' => false,
			'inherit' => false,
		);

		foreach ( $expected as $test => $result ) {
			$this->assertEquals( $result, $this->class->can_post_status_share( $test ) );
		}
	}

	/**
	 * @covers Drafty::get_transient_key
	 */
	public function test_get_transient_key() {
		$this->assertNotEmpty( $this->class->get_transient_key() );
	}

	/**
	 * @covers Drafty::set_notice
	 * @covers Drafty::flush_notice
	 */
	public function test_set_and_flush_notice() {
		$notice = array( 'test', 'drafty' );

		$this->class->set_notice( $notice );

		$this->assertEquals( $notice, $this->class->flush_notice() );
	}

	/**
	 * @covers Drafty::save_post_meta
	 * @covers Drafty::set_notice
	 * @covers Drafty::flush_notice
	 */
	public function test_save_post_meta_fail_bad_nonce() {
		$this->class->save_post_meta( -1 );

		$notice = $this->class->flush_notice();

		$this->assertNotEmpty( $notice );
		$this->assertEquals( $notice[ 0 ], 'error' );
	}

	/**
	 * @covers Drafty::add_meta_boxes
	 */
	public function test_add_meta_boxes_empty() {
		$this->assertFalse( $this->class->add_meta_boxes() );
	}

	/**
	 * @covers Drafty::add_meta_boxes
	 */
	public function test_add_meta_boxes_has_post_not_shareable() {
		$post_id = $this->factory->post->create();

		$GLOBALS[ 'post' ] = get_post( $post_id );

		$this->assertFalse( $this->class->add_meta_boxes() );

		unset( $GLOBALS[ 'post' ] );
	}

	/**
	 * @covers Drafty::add_meta_boxes
	 */
	public function test_add_meta_boxes_has_post_shareable() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$GLOBALS[ 'post' ] = get_post( $post_id );

		$this->assertTrue( $this->class->add_meta_boxes() );

		unset( $GLOBALS[ 'post' ] );
	}

	/**
	 * @covers Drafty::generate_meta_box
	 */
	public function test_generate_meta_box_empty() {
		$this->assertFalse( $this->class->generate_meta_box( array() ) );
	}

	/**
	 * @covers Drafty::generate_meta_box
	 */
	public function test_generate_meta_box_simple_draft() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		ob_start();
		$this->assertNull( $this->class->generate_meta_box( get_post( $post_id ) ) );
		ob_end_clean();
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_create_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_create_invalid_post_status() {
		$post_id = $this->factory->post->create();

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;
		$_POST[ 'drafty_amount' ] = 100;
		$_POST[ 'drafty_measure' ] = 's';

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_create_valid_post_status() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;
		$_POST[ 'drafty_amount' ] = 100;
		$_POST[ 'drafty_measure' ] = 's';

		$this->assertTrue( $this->class->save_post_meta( $post_id ) );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_delete_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_delete' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_extend_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_extend' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );
	}

}
