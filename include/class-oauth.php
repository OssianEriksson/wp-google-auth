<?php
/**
 * Class for Google OAuth authentication management
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
 * Google OAuth authentication state.
 */
class OAuth {

	private const DISCOVERY_DOCUMENT_URL = 'https://accounts.google.com/.well-known/openid-configuration';

	/**
	 * Google OAuth endpoints
	 *
	 * @var Endpoints
	 */
	private $endpoints;

	/**
	 * Local settings reference
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * OAuth constructor
	 *
	 * @param Settings $settings Settings reference.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		$this->endpoints = new Endpoints( $settings );
	}

	/**
	 * Returns Google authorization URL
	 *
	 * @param ?string $client_id OAuth Client ID. Defaults to value provided by
	 *                           settings.
	 */
	public function get_authorization_url( ?string $client_id = null ): string {
		$urls = $this->endpoints->get();
		if ( ! $urls ) {
			return '';
		}

		$client_id ??= $this->settings->get( 'client_id' );

		return $urls['authorization_endpoint']
				. '?client_id=' . $client_id
				. '&response_type=code'
				. '&scope=openid email profile'
				. '&redirect_uri=' . site_url( '?wp_google_auth_openid' )
				. '&state=' . $this->get_state()
				. '&nonce=' . bin2hex( random_bytes( 128 / 8 ) )
				. '&prompt=select_account';
	}

	/**
	 * Gets or generates the OAuth state parameter
	 */
	private function get_state(): string {
		if ( isset( $_COOKIE['wp_google_auth_oauth_state'] ) ) {
			$state = sanitize_key( wp_unslash( $_COOKIE['wp_google_auth_oauth_state'] ) );
		} else {
			$state = bin2hex( random_bytes( 128 / 8 ) );
			setcookie(
				'wp_google_auth_oauth_state',
				$state,
				array(
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		return $state;
	}
}
