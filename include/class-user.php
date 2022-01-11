<?php
/**
 * Class for logging
 *
 * WP Google Auth
 * Copyright (C) 2021  Ossian Eriksson
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package ftek/wp-google-auth
 */

namespace Ftek\WPGoogleAuth;

/**
 * User utils and hooks.
 */
class User {

	/**
	 * User constructor
	 */
	public function __construct() {
		add_filter(
			'get_avatar',
			array( $this, 'get_avatar' ),
			9,
			5
		);
	}

	/**
	 * Filter for the get_avatar hook
	 *
	 * @param string $avatar HTML for the user's avatar.
	 * @param mixed  $id_or_email The avatar to retrieve.
	 * @param int    $size Square avatar width and height in pixels to retrieve.
	 * @param string $default URL for the default image or a default type.
	 * @param string $alt Alternative text to use in the avatar image tag.
	 */
	public function get_avatar( string $avatar, $id_or_email, int $size, string $default, string $alt ): string {
		if ( ! is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( ! $user ) {
				return $avatar;
			}
			$user_id = $user->ID;
		} else {
			$user_id = $id_or_email;
		}

		$meta = $this->get_meta_fields( $id_or_email );
		if ( empty( $meta['picture'] ) ) {
			return $avatar;
		}

		ob_start();
		?>
		<img class="avatar avatar-<?php echo esc_attr( $size ); ?> photo" loading="lazy" src="<?php echo esc_attr( $meta['picture'] ); ?>" width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve a custom meta fields for a user
	 *
	 * @param int $user_id User ID.
	 */
	public static function get_meta_fields( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wp_google_auth', true );
		return array_merge(
			array(
				'picture' => '',
			),
			empty( $meta ) ? array() : $meta
		);
	}

	/**
	 * Update a custom meta fields for a user
	 *
	 * @param int   $user_id User ID.
	 * @param array $meta   Metadata fields.
	 */
	public static function update_meta_fields( int $user_id, array $meta ): void {
		update_user_meta( $user_id, 'wp_google_auth', $meta );
	}
}
