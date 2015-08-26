<?php

class DraftyData {

	/**
	 * The metadata key used to store shares.
	 */
	const META_KEY = 'drafty_shares';

	/**
	 * Update a post's collection of shared draft data.
	 *
	 * @param int $post_id The post to update
	 * @param array $shares An array of assigned keys and values for shared drafts
	 */
	private function set_post_shares( $post_id, $shares ) {
		update_post_meta( intval( $post_id ), self::META_KEY, $shares );
	}

	/**
	 * Retrieve the collection of shares for a post.
	 *
	 * @param int $post_id The post to retrieve shares from
	 * @return array
	 */
	private function get_post_shares( $post_id ) {
		$shares = get_post_meta( $post_id, self::META_KEY, true );

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
	public function get_visible_post_shares( $user_id, $post_id ) {
		$keys = array();

		foreach ( $this->get_post_shares( $post_id ) as $key => $share ) {
			$user_can = in_array( $user_id, array( -1, $share[ 'user_id' ] ) );

			if ( ! $user_can ) {
				continue;
			}

			$keys[ $key ] = $share;
		}

		return $keys;
	}

	/**
	 * Return true or false if a share exists for the given post id and key.
	 *
	 * @param int $post_id The post id against which to check
	 * @param string $key The key against which to check
	 * @return bool
	 */
	public function share_exists( $post_id, $key ) {
		$shares = $this->get_post_shares( $post_id );

		if ( isset( $shares[ $key ] ) && $shares[ $key ][ 'expires' ] >= time() ) {
			return true;
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

		$shares = $this->get_post_shares( $post_id );
		$shares[ $key ] = $share;
		$this->set_post_shares( $post_id, $shares );

		return $key;
	}

	/**
	 * Delete a share using the provided information.
	 *
	 * If the share does not exist, delete nothing and return false.
	 *
	 * @param int $user_id The user id who shared the draft, or -1 to ignore
	 * @param string $key The key to search for (and delete, if it exists)
	 * @return bool
	 */
	public function delete_share( $user_id, $post_id, $key ) {
		$shares = $this->get_post_shares( $post_id );

		if ( ! isset( $shares[ $key ] ) ) {
			return false;
		}

		$user_can = in_array( $user_id, array( -1, $shares[ $key ][ 'user_id' ] ) );

		if ( $user_can ) {
			unset( $shares[ $key ] );
			$this->set_post_shares( $post_id, $shares );

			return true;
		}

		return false;
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
	public function extend_share( $user_id, $post_id, $key, $time ) {
		$shares = $this->get_post_shares( $post_id );

		if ( ! isset( $shares[ $key ] ) ) {
			return false;
		}

		$user_can = in_array( $user_id, array( -1, $shares[ $key ][ 'user_id' ] ) );

		if ( $user_can ) {
			$now = time();

			if ( $shares[ $key ][ 'expires' ] < $now ) {
				$shares[ $key ][ 'expires' ] = $now;
			}

			$shares[ $key ][ 'expires' ] += $time;
			$this->set_post_shares( $post_id, $shares );

			return true;
		}

		return false;
	}

	/**
	 * Retrieve the collection of shares for a post.
	 *
	 * @param int $post_id The post to retrieve shares from
	 * @return array
	 */
	public function set_share_expires( $post_id, $key, $expires ) {
		$shares = $this->get_post_shares( $post_id );

		if ( ! isset( $shares[ $key ] ) ) {
			return false;
		}

		$shares[ $key ][ 'expires' ] = intval( $expires );
		$this->set_post_shares( $post_id, $shares );

		return true;
	}

}
