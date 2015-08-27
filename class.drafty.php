<?php

class Drafty {

	/**
	 * The domain for localization.
	 */
	const DOMAIN = 'drafty';

	/**
	 * The URL key for notifying a user about a status update.
	 */
	const NOTIFY = 'drafty_notify';

	/**
	 * Retain the shared posts stored in the option.
	 */
	private $shared_posts = array();

	/**
	 * Pass an argument in the post redirect to notify the user of an update or error.
	 */
	public $notify;

	/**
	 * Keep data separate from the main class.
	 */
	public $drafty_share;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		$this->drafty_share = new DraftyData();
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );
		add_filter( 'admin_notices', array( $this, 'admin_notices' ) );

		add_filter( 'posts_results', array( $this, 'posts_results' ) );
		add_filter( 'the_posts', array( $this, 'the_posts' ) );

		add_filter( 'redirect_post_location', array( $this, 'notify_redirect' ) );

		$this->admin_page_init();
	}

	public function add_admin_pages() {
		add_menu_page(
			esc_html__( 'Drafty', self::DOMAIN ),
			esc_html__( 'Drafty', self::DOMAIN ),
			'edit_posts',
			self::DOMAIN,
			array( $this, 'admin_page' )
		);

		add_action( 'admin_print_styles-post.php', array( $this, 'admin_page_styles' ) );
		add_action( 'admin_print_scripts-post.php', array( $this, 'admin_page_scripts' ) );
	}

	public function admin_page_init() {
		wp_register_style( 'drafty_style', plugins_url( 'css/drafty.css', __FILE__ ) );
		wp_register_script( 'drafty_script', plugins_url( 'js/drafty.js', __FILE__ ) );
	}

	public function admin_page_styles() {
		wp_enqueue_style( 'drafty_style' );
	}

	public function admin_page_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'drafty_script' );
	}

	public function notify_redirect( $location, $post_id ) {
		if ( isset( $this->notify ) ) {
			return add_query_arg( self::NOTIFY, $this->notify, $location );
		}

		return $location;
	}

	public function can_post_status_share( $post_status ) {
		return in_array( $post_status, array( 'draft', 'future', 'pending' ) );
	}

	/**
	 * Initialize meta box.
	 */
	public function add_meta_boxes() {
		$post = get_post();

		if ( empty( $post ) || ! isset( $post->post_status ) ) {
			return false;
		}

		if ( $this->can_post_status_share( $post->post_status ) ) {
			add_meta_box(
				self::DOMAIN . 'meta-box',
				__( 'Share a Drafty Draft', self::DOMAIN ),
				array( $this, 'generate_meta_box' ),
				'post',
				'normal'
			);

			return true;
		}

		return false;
	}

	public function get_user_id_or_admin() {
		return current_user_can( 'edit_others_posts' ) ? -1 : get_current_user_id();
	}

	/**
	 * Show HTML for the zone details stored in post meta.
	 */
	public function generate_meta_box( $post ) {
		if ( ! isset( $post->ID ) ) {
			return false;
		}

		echo wp_nonce_field( 'drafty_action' . $post->ID, 'drafty_action' );

		$post_shares = $this->drafty_share->get_visible_post_shares( $this->get_user_id_or_admin(), $post->ID );

		if ( ! empty( $post_shares ) ) {
?>
<table class="drafty">
	<tr>
		<th><?php _e( 'Link', self::DOMAIN ); ?></th>
		<th><?php _e( 'Expires After', self::DOMAIN ); ?></th>
		<th colspan="2"><?php _e( 'Actions', self::DOMAIN ); ?></th>
	</tr>
<?php
			foreach ( $post_shares as $key => $share ) {

				$url = get_bloginfo( 'url' ) . '/?p=' . $share[ 'post_id' ] . '&drafty='. $key;
?>
	<tr>
		<td class="left" id="td-<?php echo esc_attr( $key ); ?>">
			<span class="clipboard" data-key="<?php echo esc_attr( $key ); ?>">📋</span>
			<a id="url-<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $url ); ?></a>
			<input type="text" class="copy" id="copy-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $url ); ?>" \>
		</td>
		<td><?php echo esc_html( $this->get_time_difference_string( $share[ 'expires' ] ) ); ?></td>
		<td>
			<div id="extend-form-<?php echo esc_attr( $key ); ?>" class="extend-form">
				<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>" />
				<button type="submit" name="drafty_extend" value="<?php echo esc_attr( $key ); ?>">
					<?php esc_html_e( 'Extend', self::DOMAIN ); ?>
				</button>
				<?php esc_html_e( 'by', self::DOMAIN );?>
				<?php echo $this->template_select( $key ); ?>
				<a class="extend-cancel" data-key="<?php echo esc_attr( $key ); ?>">
					<?php esc_html_e( 'Cancel', self::DOMAIN ); ?>
				</a>
			</div>
			<a class="extend" id="extend-<?php echo esc_attr( $key ); ?>" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Extend', self::DOMAIN ); ?></a>
		</td>
		<td>
			<input id="delete-<?php echo esc_attr( $key ); ?>" type="submit" name="drafty_delete" class="drafty_delete" value="<?php echo esc_attr( $key ); ?>" />
			<a class="delete" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Delete', self::DOMAIN ); ?></a>
		</td>
	</tr>
<?php
			}
?>
</table>
<?php
		}
?>
<p class="drafty">
	<input type="submit" class="button" name="drafty_create"
		value="<?php esc_attr_e( 'Share it', self::DOMAIN ); ?>" />
	<?php esc_html_e( 'for', self::DOMAIN ); ?>
	<?php echo $this->template_select(); ?>
</p>
<?php
	}

	public function template_select( $key = '' ) {
?>
		<input name="drafty_amount<?php echo esc_attr( $key ); ?>" type="text" value="2" size="4" />
		<select name="drafty_measure<?php echo esc_attr( $key ); ?>">
			<option value="s"><?php esc_html_e( 'seconds', self::DOMAIN ); ?></option>
			<option value="m"><?php esc_html_e( 'minutes', self::DOMAIN ); ?></option>
			<option value="h" selected="selected"><?php esc_html_e( 'hours', self::DOMAIN ); ?></option>
			<option value="d"><?php esc_html_e( 'days', self::DOMAIN ); ?></option>
		</select>
<?php
	}

	/**
	 * Extract the updates from $_POST and save in post meta.
	 */
	public function save_post_meta( $post_id ) {
		if ( ! isset( $_POST[ 'drafty_action' ] ) || ! wp_verify_nonce( $_POST[ 'drafty_action' ], 'drafty_action' . $post_id ) ) {
			$this->notify = 7;

			return false;
		}

		if ( isset( $_POST[ 'drafty_create' ] ) &&
				isset( $_POST[ 'drafty_amount' ] ) &&
				isset( $_POST[ 'drafty_measure' ] ) ) {

			$post = get_post( $post_id );
			if ( ! $this->can_post_status_share( $post->post_status ) ) {
				$this->notify = 2;

				return false;
			}

			$user_id = get_current_user_id();
			$time = $this->calculate_seconds( intval( $_POST[ 'drafty_amount' ] ), $_POST[ 'drafty_measure' ] );

			$this->notify = 1;

			return $this->drafty_share->add_share( $user_id, $post_id, $time );

		} else if ( isset( $_POST[ 'drafty_delete' ] ) ) {

			$result = $this->drafty_share->delete_share( $this->get_user_id_or_admin(), $post_id, $_POST[ 'drafty_delete' ] );

			if ( $result ) {
				$this->notify = 3;
			} else {
				$this->notify = 4;
			}

			return $result;

		} else if ( isset( $_POST[ 'drafty_extend' ] ) &&
				isset( $_POST[ 'drafty_amount' ] ) &&
				isset( $_POST[ 'drafty_measure' ] ) ) {

			$key = $_POST[ 'drafty_extend' ];
			$amount = $_POST[ 'drafty_amount' . $key ];
			$measure = $_POST[ 'drafty_measure' . $key ];

			$time = $this->calculate_seconds( $amount, $measure );

			$result = $this->drafty_share->extend_share( $this->get_user_id_or_admin(), $post_id, $key, $time );

			if ( $result ) {
				$this->notify = 5;
			} else {
				$this->notify = 6;
			}

			return $result;

		}

		return false;
	}

	public function admin_notices() {
		if ( ! isset( $_GET[ self::NOTIFY ] ) ) {
			return false;
		}

		$messages = array(
			1 => array( 'updated', __( 'Your draft was created!', self::DOMAIN ) ),
			2 => array( 'error', __( 'Your draft could not be created!', self::DOMAIN ) ),
			3 => array( 'updated', __( 'Your draft was deleted!', self::DOMAIN ) ),
			4 => array( 'error', __( 'Your draft could not be deleted!', self::DOMAIN ) ),
			5 => array( 'updated', __( 'Your draft was extended!', self::DOMAIN ) ),
			6 => array( 'error', __( 'Your draft could not be extended!', self::DOMAIN ) ),
			7 => array( 'error', __( 'Could not update the Drafty settings!', self::DOMAIN ) ),
		);

		$notice = intval( $_GET[ self::NOTIFY ] );

		if ( isset( $messages[ $notice ] ) ) {
			$message = $messages[ $notice ];
			echo '<div class="' . esc_attr( $message[ 0 ] ) . '">' . esc_html__( $message[ 1 ] ) . '</div>';

			return true;
		}

		return false;
	}

	public function calculate_seconds( $amount, $measure ) {
		$amount = intval( $amount ) > 0 ? intval( $amount ) : 0;

		$multiples = array( 's' => 1, 'm' => 60, 'h' => 3600, 'd' => 24 * 3600 );

		if ( isset( $multiples[ $measure ] ) ) {
			$amount = $amount * $multiples[ $measure ];
		}

		return $amount;
	}

	public function get_time_difference_string( $time ) {
		$now = time();
		$st = '';

		if ( $time - $now > 0 ) {
			$st = sprintf( __( 'In %s.', self::DOMAIN ), human_time_diff( $time, $now ) );
		} else {
			$st = __( 'Expired.', self::DOMAIN );
		}

		return $st;
	}

	public function can_view( $post_id ) {
		if ( ! isset( $_GET[ 'drafty' ] ) ) {
			return false;
		}

		return $this->drafty_share->share_exists( $post_id, $_GET[ 'drafty' ] );
	}

	public function posts_results( $posts ) {
		$this->shared_posts = array();

		foreach ( $posts as $post_key => $post ) {
			$post_status = get_post_status( $post );
			if ( $this->can_post_status_share( $post_status ) && $this->can_view( $post->ID ) ) {
				$this->shared_posts[ $post_key ] = $post;
			}
		}

		return $posts;
	}

	public function the_posts( $posts ) {
		foreach ( $this->shared_posts as $post_key => $post ) {
			$posts[ $post_key ] = $post;
		}

		return $posts;
	}

	public function admin_page() {
?>
<h1><?php _e( 'Drafty', self::DOMAIN ); ?></h1>
<?php
	}

}
