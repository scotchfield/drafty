<?php

class TestDrafty extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->class = new Drafty();
		$this->class->set_shared_keys( null );
	}

	public function tearDown() {
		unset( $this->class );

		parent::tearDown();
	}

	/**
	 * @covers Drafty::__construct
	 */
	public function test_new() {
		$this->assertNotNull( $this->class );
	}

	/**
	 * @covers Drafty::get_shared_keys
	 */
	public function test_get_shared_keys_empty() {
		$this->assertEmpty( $this->class->get_shared_keys() );
	}

	/**
	 * @covers Drafty::get_shared_keys
	 * @covers Drafty::set_shared_keys
	 */
	public function test_set_shared_keys_test_array() {
		$test = array( 'this' => 'that' );

		$this->class->set_shared_keys( $test );

		$this->assertEquals( $test, $this->class->get_shared_keys() );
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

}
