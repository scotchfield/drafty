<?php

class DraftyData {

	/**
	 * The domain for localization.
	 */
	const OPTION = 'drafty';

	public function set_shared_keys( $keys ) {
		update_option( self::OPTION, $keys );
	}

	public function get_shared_keys() {
		$shares = get_option( self::OPTION );

		return is_array( $shares ) ? $shares : array();
	}

	public function get_visible_post_shared_keys( $user_id, $post_id ) {
		$keys = array();

		foreach ( $this->get_shared_keys() as $key => $share ) {
			$user_can = in_array( $user_id, array( -1, $share[ 'user_id' ] ) );

			if ( ! $user_can ) {
				continue;
			}

			if ( $share[ 'post_id' ] == $post_id ) {
				$keys[ $key ] = $share;
			}
		}

		return $keys;
	}

	public function share_exists( $post_id, $share_key ) {
		$shares = $this->get_shared_keys();

		foreach ( $shares as $key => $share ) {
			if ( $key == $share_key && $share[ 'post_id' ] == $post_id ) {
				return true;
			}
		}

		return false;
	}

	public function add_share( $user_id, $post_id, $time ) {
		$shares = $this->get_shared_keys();
		$key = wp_generate_password( 8, false );

		$shares[$key] = array(
			'user_id' => $user_id,
			'post_id' => $post_id,
			'expires' => time() + $time,
		);

		$this->set_shared_keys( $shares );

		return $key;
	}

	public function delete_share( $user_id, $delete_key ) {
		$return = false;
		$shares = $this->get_shared_keys();

		foreach ( $shares as $key => $share ) {
			$user_can = in_array( $user_id, array( -1, $share[ 'user_id' ] ) );

			if ( $user_can && $key == $delete_key ) {
				unset( $shares[ $key ] );
				$return = true;
			}
		}

		$this->set_shared_keys( $shares );

		return $return;
	}

	public function extend_share( $user_id, $extend_key, $time ) {
		$return = false;
		$shares = $this->get_shared_keys();

		foreach ( $shares as $key => $share ) {
			$user_can = in_array( $user_id, array( -1, $share[ 'user_id' ] ) );

			if ( $user_can && $key == $extend_key ) {
				$now = time();

				if ( $share[ 'expires' ] < $now ) {
					$share[ 'expires' ] = $now;
				}

				$shares[ $key ][ 'expires' ] += $time;
				$return = true;
			}
		}

		$this->set_shared_keys( $shares );

		return $return;
	}

}
