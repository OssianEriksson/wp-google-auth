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
 * User utils.
 */
class User {

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
	public static function update_meta_fields( int $user_id, array $meta ): array {
		update_user_meta( $user_id, 'wp_google_auth', $meta );
	}
}
