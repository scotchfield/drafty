<?php

class TestDrafty extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->class = new Drafty();
	}

	public function tearDown() {
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

		$this->assertGreaterThan(
			0, has_action( 'admin_print_styles-post.php', array( $this->class, 'admin_page_styles' ) )
		);
		$this->assertGreaterThan(
			0, has_action( 'admin_print_scripts-post.php', array( $this->class, 'admin_page_scripts' ) )
		);
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
	 * @covers Drafty::notify_redirect
	 */
	public function test_notify_redirect_unchanged() {
		$url = 'http://example.com';

		$this->assertEquals( $url, $this->class->notify_redirect( $url, 1 ) );
	}

	/**
	 * @covers Drafty::notify_redirect
	 */
	public function test_notify_redirect_set_notify() {
		$url = 'http://example.com';
		$notify = 1;

		$this->class->notify = $notify;
		$this->assertEquals(
			$url . '?' . Drafty::NOTIFY . '=' . $notify,
			$this->class->notify_redirect( $url, 1 )
		);
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
	 * @covers Drafty::template_select
	 */
	public function test_template_select_key() {
		$key = 'test';

		ob_start();
		$this->class->template_select( $key );
		$content = ob_get_clean();

		$this->assertContains( 'drafty_amount' . $key, $content );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_fail_bad_nonce() {
		$this->assertFalse( $this->class->save_post_meta( -1 ) );
		$this->assertNotNull( $this->class->notify );
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
	 * @covers Drafty::get_user_id_or_admin
	 */
	public function test_get_user_id_or_admin_with_administrator() {
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$old_user_id = get_current_user_id();
		wp_set_current_user( $user->ID );

		$this->assertEquals( -1, $this->class->get_user_id_or_admin() );

		wp_set_current_user( $old_user_id );
	}

	/**
	 * @covers Drafty::get_user_id_or_admin
	 */
	public function test_get_user_id_or_admin_with_subscriber() {
		$user = new WP_User( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$old_user_id = get_current_user_id();
		wp_set_current_user( $user->ID );

		$this->assertEquals( $user->ID, $this->class->get_user_id_or_admin() );

		wp_set_current_user( $old_user_id );
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
	 * @covers Drafty::generate_meta_box
	 */
	public function test_generate_meta_box_simple_shared_draft() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$key = $this->class->drafty_share->add_share( get_current_user_id(), $post_id, 100 );

		ob_start();
		$this->assertNull( $this->class->generate_meta_box( get_post( $post_id ) ) );
		$content = ob_get_clean();

		$this->assertContains( $key, $content );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_create_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_create' ] );
		unset( $_POST[ 'drafty_action' ] );
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

		unset( $_POST[ 'drafty_measure' ] );
		unset( $_POST[ 'drafty_amount' ] );
		unset( $_POST[ 'drafty_create' ] );
		unset( $_POST[ 'drafty_action' ] );
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

		$this->assertNotFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_measure' ] );
		unset( $_POST[ 'drafty_amount' ] );
		unset( $_POST[ 'drafty_create' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_delete_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_delete' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_delete' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_delete_valid_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$key = $this->class->drafty_share->add_share( get_current_user_id(), $post_id, 100 );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_delete' ] = $key;

		$this->assertNotFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_delete' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_extend_bad_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_extend' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_extend' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_extend_bad_post_with_data() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_extend' ] = true;
		$_POST[ 'drafty_amount' ] = true;
		$_POST[ 'drafty_measure' ] = true;

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_measure' ] );
		unset( $_POST[ 'drafty_amount' ] );
		unset( $_POST[ 'drafty_extend' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_extend_good_post() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$key = $this->class->drafty_share->add_share( get_current_user_id(), $post_id, 100 );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_extend' ] = $key;
		$_POST[ 'drafty_amount' . $key ] = 100;
		$_POST[ 'drafty_measure' . $key ] = 'm';

		$this->assertNotFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_measure' ] );
		unset( $_POST[ 'drafty_amount' ] );
		unset( $_POST[ 'drafty_extend' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::save_post_meta
	 */
	public function test_save_post_meta_extend_bad_post_existing_values() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$key = 'test';

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_extend' ] = $key;
		$_POST[ 'drafty_amount' . $key ] = 100;
		$_POST[ 'drafty_measure' . $key ] = 'm';

		$this->assertFalse( $this->class->save_post_meta( $post_id ) );

		unset( $_POST[ 'drafty_measure' ] );
		unset( $_POST[ 'drafty_amount' ] );
		unset( $_POST[ 'drafty_extend' ] );
		unset( $_POST[ 'drafty_action' ] );
	}

	/**
	 * @covers Drafty::admin_notices
	 */
	public function test_admin_notices_false_no_get() {
		$this->assertFalse( $this->class->admin_notices() );
	}

	/**
	 * @covers Drafty::admin_notices
	 */
	public function test_admin_notices_false_invalid_get() {
		$_GET[ 'drafty_notify' ] = -1;

		$this->assertFalse( $this->class->admin_notices() );

		unset( $_GET[ 'drafty_notify' ] );
	}

	/**
	 * @covers Drafty::admin_notices
	 */
	public function test_admin_notices_true_created() {
		$_GET[ 'drafty_notify' ] = 1;

		ob_start();
		$this->assertTrue( $this->class->admin_notices() );
		$content = ob_get_clean();

		$this->assertContains( 'was created', $content );

		unset( $_GET[ 'drafty_notify' ] );
	}

	/**
	 * @covers Drafty::calculate_seconds
	 */
	public function test_calculate_seconds() {
		$this->assertEquals( 1, $this->class->calculate_seconds( 1, 's' ) );
		$this->assertEquals( 60, $this->class->calculate_seconds( 1, 'm' ) );
		$this->assertEquals( 60 * 60, $this->class->calculate_seconds( 1, 'h' ) );
		$this->assertEquals( 60 * 60 * 24, $this->class->calculate_seconds( 1, 'd' ) );

		$this->assertEquals( 10 * 1, $this->class->calculate_seconds( 10, 's' ) );
		$this->assertEquals( 10 * 60, $this->class->calculate_seconds( 10, 'm' ) );
		$this->assertEquals( 10 * 60 * 60, $this->class->calculate_seconds( 10, 'h' ) );
		$this->assertEquals( 10 * 60 * 60 * 24, $this->class->calculate_seconds( 10, 'd' ) );
	}

	/**
	 * @covers Drafty::get_time_difference_string
	 */
	public function test_get_time_difference_string() {
		$this->assertEquals(
			__( 'Expired.', Drafty::DOMAIN ),
			$this->class->get_time_difference_string( 0 )
		);

		$this->assertNotEquals(
			__( 'Expired.', Drafty::DOMAIN ),
			$this->class->get_time_difference_string( time() + 100 )
		);
	}

	/**
	 * @covers Drafty::can_view
	 */
	public function test_can_view_false_no_get_key() {
		$this->assertFalse( $this->class->can_view( 1 ) );
	}

	/**
	 * @covers Drafty::can_view
	 */
	public function test_can_view() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;
		$_POST[ 'drafty_amount' ] = 100;
		$_POST[ 'drafty_measure' ] = 's';

		$key = $this->class->save_post_meta( $post_id );

		$_GET[ 'drafty' ] = $key;
		$this->assertTrue( $this->class->can_view( $post_id ) );
	}

	/**
	 * @covers Drafty::can_view
	 */
	public function test_can_view_false_expired() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );

		$_POST[ 'drafty_action' ] = wp_create_nonce( 'drafty_action' . $post_id );
		$_POST[ 'drafty_create' ] = true;
		$_POST[ 'drafty_amount' ] = 100;
		$_POST[ 'drafty_measure' ] = 's';

		$key = $this->class->save_post_meta( $post_id );

		$share = array(
			'user_id' => 1,
			'post_id' => $post_id,
			'expires' => 0,
		);

		$this->class->drafty_share->set_share_expires( $post_id, $key, 0 );

		$_GET[ 'drafty' ] = $key;
		$this->assertFalse( $this->class->can_view( $post_id ) );
	}

	/**
	 * @covers Drafty::posts_results
	 */
	public function test_posts_results_empty() {
		$posts = array();

		$this->assertEquals( $posts, $this->class->posts_results( $posts ) );
	}

	/**
	 * @covers Drafty::the_posts
	 */
	public function test_the_posts_empty() {
		$posts = array();

		$this->assertEquals( $posts, $this->class->the_posts( $posts ) );
	}

	/**
	 * @covers Drafty::posts_results
	 * @covers Drafty::the_posts
	 */
	public function test_posts_show_shared() {
		$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$key = $this->class->drafty_share->add_share( get_current_user_id(), $post_id, 100 );
		$_GET[ 'drafty' ] = $key;

		$posts = array( get_post( $post_id ) );

		$this->class->posts_results( $posts );

		$this->assertEquals( $posts, $this->class->the_posts( array() ) );

		unset( $_GET[ 'drafty' ] );
	}

}
