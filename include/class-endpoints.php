<?php
/**
 * Class for keeping track of Google OAuth endpoints
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
 * Google OAuth endpoints cache.
 */
class Endpoints {

	private const DISCOVERY_DOCUMENT_URL = 'https://accounts.google.com/.well-known/openid-configuration';

	/**
	 *  Array of endpoint URLs
	 *
	 * @var ?array $endpoints
	 */
	private $endpoints = null;

	/**
	 * Local settings reference
	 *
	 * @var Settings $settings
	 */
	private $settings;

	/**
	 * Endpoints constructor
	 *
	 * @param Settings $settings Settings reference.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns an array of endpoint URLs
	 *
	 * @param ?int $cache_refresh Cache is updated if this number of seconds
	 *                            has passed since the last update. Defaults to
	 *                            value provided by settings.
	 */
	public function get( ?int $cache_refresh = null ): ?array {
		$cache_refresh ??= 3600 * $this->settings->get( 'cache_refresh' );

		$this->clean();
		if ( ! $this->endpoints ) {
			$json = get_option( 'wp_google_auth_option_endpoints' );
			if ( $json ) {
				$this->endpoints = json_decode( $json, true );

				$since_last_updated = time() - $this->endpoints['last_updated'];
				if ( $since_last_updated >= $cache_refresh ) {
					$this->endpoints = null;
				}
			}

			if ( ! $this->endpoints ) {
				$this->endpoints = $this->fetch_endpoints(
					array(
						'authorization_endpoint',
						'token_endpoint',
						'userinfo_endpoint',
					)
				);

				update_option(
					'wp_google_auth_option_endpoints',
					wp_json_encode( $this->endpoints )
				);
			}
		}
		return $this->endpoints;
	}

	/**
	 * Fetches a new array of endpoints from the discovery document
	 *
	 * @param array $keys Keys of endpoints to look for.
	 */
	private function fetch_endpoints( array $keys ): ?array {
		$response = wp_remote_get( self::DISCOVERY_DOCUMENT_URL );
		$code     = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			Logger::error( 'Unexpected status (' . $code . ') when fetching discovery document fetched from ' . self::DISCOVERY_DOCUMENT_URL );
			return null;
		}

		$document = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $document ) {
			Logger::error( 'Error while parsing discovery document fetched from ' . self::DISCOVERY_DOCUMENT_URL );
			return null;
		}

		$document = array_intersect_key( $document, array_flip( $keys ) );
		if ( count( $keys ) !== count( $document ) ) {
			Logger::error( 'Missing one of (' . implode( ', ', $keys ) . ') in discovery document fetched from ' . self::DISCOVERY_DOCUMENT_URL );
			return null;
		}

		$document['last_updated'] = time();

		return $document;
	}

	/**
	 * Removes persistant data
	 */
	public static function clean(): void {
		delete_option( 'wp_google_auth_option_endpoints' );
	}
}
