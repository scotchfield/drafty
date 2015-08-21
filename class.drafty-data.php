<?php

class DraftyData {

	/**
	 * The domain for localization.
	 */
	const OPTION = 'drafty';

	/**
	 * Store a collection of shared draft data.
	 *
	 * @param array $keys An array of assigned keys and values for shared drafts
	 */
	public function set_shared_keys( $keys ) {
		update_option( self::OPTION, $keys );
	}

	/**
	 * Retrieve the collection of shared draft data.
	 *
	 * @return array
	 */
	public function get_shared_keys() {
		$shares = get_option( self::OPTION );

		return is_array( $shares ) ? $shares : array();
	}

	/**
	 * Given a user id and post id, retrieve the list of visible shares.
	 *
	 * If user_id is set to -1, ignore the user_id check against existing shares.
	 *
	 * @param int $user_id The current user id, which can be ignored using -1
	 * @param int $post_id The post id against which to check
	 */
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

	/**
	 * Get a share, if it exists, using the key.
	 *
	 * Returns false if the share does not exist, otherwise returns the share array.
	 *
	 * @param string $share_key The key to search for
	 */
	public function get_share_by_key( $share_key ) {
		foreach ( $this->get_shared_keys() as $key => $share ) {
			if ( $share_key == $key ) {
				return $share;
			}
		}

		return false;
	}

	/**
	 * Set (or update) a share by key.
	 *
	 * @param string $share_key The key to set (or update)
	 * @param array $share The value to use for the given key
	 */
	public function set_share_by_key( $share_key, $share ) {
		$shares = $this->get_shared_keys();

		$shares[ $share_key ] = $share;

		$this->set_shared_keys( $shares );
	}

	/**
	 * Return true or false if a share exists for the given post id and key.
	 *
	 * @param int $post_id The post id against which to check
	 * @param string $share_key The key against which to check
	 * @return bool
	 */
	public function share_exists( $post_id, $share_key ) {
		$shares = $this->get_shared_keys();

		foreach ( $shares as $key => $share ) {
			if ( $key == $share_key &&
					$share[ 'post_id' ] == $post_id &&
					$share[ 'expires' ] >= time() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add a new share using the provided information.
	 *
	 * @param int $user_id The user id who is sharing the draft
	 * @param int $post_id The post id to share
	 * @param int $time The duration of time (after now) to share
	 * @return string
	 */
	public function add_share( $user_id, $post_id, $time ) {
		$key = wp_generate_password( 8, false );
		$share = array(
			'user_id' => $user_id,
			'post_id' => $post_id,
			'expires' => time() + $time,
		);

		$this->set_share_by_key( $key, $share );

		return $key;
	}

	/**
	 * Delete a share using the provided information.
	 *
	 * If the share does not exist, delete nothing and return false.
	 *
	 * @param int $user_id The user id who shared the draft, or -1 to ignore
	 * @param string $delete_key The key to search for (and delete, if it exists)
	 * @return bool
	 */
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

	/**
	 * Extend a share using the provided information.
	 *
	 * If the share does not exist, extend nothing and return false.
	 *
	 * @param int $user_id The user id who shared the draft, or -1 to ignore
	 * @param string $extend_key The key to search for (and extend, if it exists)
	 * @param int $time The duration of time to extend the draft by
	 * @return bool
	 */
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
