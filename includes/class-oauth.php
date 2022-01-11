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
	 * Google OAuth authorization token
	 *
	 * @var ?array
	 */
	private $auth_token = null;

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

		return add_query_arg(
			array(
				'client_id'     => $client_id ?? $this->settings->get( 'client_id' ),
				'response_type' => 'code',
				'scope'         => 'openid email profile',
				'redirect_uri'  => $this->get_redirect_uri(),
				'state'         => $this->get_state(),
				'nonce'         => $this->generate_random_key(),
				'prompt'        => 'select_account',
			),
			$urls['authorization_endpoint']
		);
	}

	/**
	 * Fetches Google authorization token
	 *
	 * @param string  $code          The authorization code that is returned
	 *                               from the authorization request.
	 * @param ?string $client_id     OAuth Client ID. Defaults to value
	 *                               provided by settings.
	 * @param ?string $client_secret OAuth Client secret. Defaults to value
	 *                               provided by settings.
	 */
	public function fetch_auth_token( string $code, ?string $client_id = null, ?string $client_secret = null ): ?array {
		$urls = $this->endpoints->get();
		if ( ! $urls ) {
			return null;
		}

		$response = wp_remote_post(
			$urls['token_endpoint'],
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $client_id ?? $this->settings->get( 'client_id' ),
					'client_secret' => $client_secret ?? $this->settings->get( 'client_secret' ),
					'redirect_uri'  => $this->get_redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$this->auth_token = json_decode( wp_remote_retrieve_body( $response ), true );

		return $this->auth_token;
	}

	/**
	 * Fetches user info from Google
	 *
	 * @param ?array $auth_token Google OAuth access token.
	 *
	 * @throws Exception If not auth token is available.
	 */
	public function fetch_user_info( ?array $auth_token = null ): ?array {
		$urls = $this->endpoints->get();
		if ( ! $urls ) {
			return null;
		}

		$auth_token ??= $this->auth_token;
		if ( ! $auth_token ) {
			throw new Exception( 'Auth token must be provided, or fetch_auth_token() must be called before fetch_user_info()' );
		}

		$response = wp_remote_get(
			$urls['userinfo_endpoint'],
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $auth_token['access_token'],
				),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Checks anti-forgery state token match
	 *
	 * @param string $state Token to validate.
	 */
	public function validate_state( string $state ): bool {
		return $state === $this->get_state();
	}

	/**
	 * Returns a valid redirect URI for the Google OAuth client
	 */
	public static function get_redirect_uri(): string {
		return site_url( '?wp_google_auth_openid' );
	}

	/**
	 * Returns a 128 bit random lower case key string
	 */
	private function generate_random_key(): string {
		return bin2hex( random_bytes( 128 / 8 ) );
	}

	/**
	 * Gets or generates the OAuth state parameter
	 */
	private function get_state(): string {
		if ( isset( $_COOKIE['wp_google_auth_oauth_state'] ) ) {
			$state = sanitize_key( $_COOKIE['wp_google_auth_oauth_state'] );
		} else {
			$state = $this->generate_random_key();
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
