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
 * Loggin utils.
 */
class Logger {

	/**
	 * Logs an error if WP_DEBUG is true
	 *
	 * @param string $message Value to log.
	 */
	public static function error( string $message ) : void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
            // phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( $message );
            // phpcs:enable
		}
	}
}
