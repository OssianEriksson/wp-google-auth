<?php
/**
 * Class managmenent of login page and login procedure
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
 * Login page state.
 */
class Login {

	/**
	 * Local settings reference
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Local user reference
	 *
	 * @var User
	 */
	private $user;

	/**
	 * OAuth helper
	 *
	 * @var OAuth
	 */
	private $oauth;

	/**
	 * OAuth constructor
	 *
	 * @param Settings $settings Settings reference.
	 * @param User     $user     User reference.
	 */
	public function __construct( Settings $settings, User $user ) {
		$this->settings = $settings;
		$this->user     = $user;

		$this->oauth = new OAuth( $settings );

		add_action( 'login_init', array( $this, 'login_init' ) );
		add_action( 'init', array( $this, 'init' ) );

		add_filter(
			'allowed_redirect_hosts',
			array( $this, 'allowed_redirect_hosts' ),
			10,
			2
		);
		add_filter(
			'wp_login_errors',
			array( $this, 'wp_login_errors' )
		);
	}

	/**
	 * Callback for login_init action hook
	 */
	public function login_init(): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$loggedout = isset( $_GET['loggedout'] ) ? sanitize_key( $_GET['loggedout'] ) : null;
        // phpcs:enable
		if ( 'true' === $loggedout ) {
			wp_safe_redirect( home_url() );
			exit;
		}

        // phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['noopenid'] ) || isset( $_POST['wp-submit'] ) ) {
            // phpcs:enable
			return;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : null;
        // phpcs:enable
		if ( ! in_array( $action, array( 'logout', 'lostpassword', 'rp', 'resetpass' ), true ) ) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_sanitize_redirect( wp_unslash( $_REQUEST['redirect_to'] ) ) : null;
            // phpcs:enable

			if ( $redirect_to ) {
				setcookie(
					'wp_google_auth_redirect_to',
					$redirect_to,
					array(
						'path'     => '/',
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
			}

			$authorization_url = $this->oauth->get_authorization_url();
			if ( empty( $authorization_url ) ) {
				return;
			}

			if ( wp_safe_redirect( $authorization_url ) ) {
				exit;
			}
		}
	}

	/**
	 * Redirect to the WordPress login page and display an error
	 *
	 * @param string $error Error key of message to display on login page.
	 */
	public function redirect_to_login_with_error( string $error ): void {
		$redirect = wp_safe_redirect(
			add_query_arg(
				array(
					'noopenid'             => '',
					'wp_google_auth_error' => $error,
				),
				wp_login_url()
			)
		);
		if ( $redirect ) {
			exit;
		}
	}

	/**
	 * Callback for init action hook
	 */
	public function init(): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wp_google_auth_openid'] ) ) {
            // phpcs:enable
			return;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['code'], $_GET['state'] ) ) {
            // phpcs:enable
			$this->redirect_to_login_with_error( 'missing_params' );
			return;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$state = sanitize_key( $_GET['state'] );
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$code = wp_unslash( $_GET['code'] );
        // phpcs:enable

		if ( ! $this->oauth->validate_state( $state ) ) {
			$this->redirect_to_login_with_error( 'state_mismatch' );
			return;
		}

		if ( ! $this->oauth->fetch_auth_token( $code ) ) {
			$this->redirect_to_login_with_error( 'token' );
			return;
		}

		$user_info = $this->oauth->fetch_user_info();
		if ( ! isset( $user_info['email'] ) ) {
			$this->redirect_to_login_with_error( 'user_info' );
			return;
		}

		$regex = '/^' . $this->settings->get( 'email_regex' ) . '$/';
		if ( ! preg_match( $regex, $user_info['email'] ) ) {
			$this->redirect_to_login_with_error( 'access_denied' );
			return;
		}

		$user = $this->update_or_create_user( $user_info );
		if ( ! $user ) {
			$this->redirect_to_login_with_error( 'empty_user' );
			return;
		}

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		$redirect = wp_sanitize_redirect( wp_unslash( $_COOKIE['wp_google_auth_redirect_to'] ?? '' ) );
		$redirect = apply_filters(
			'login_redirect',
			empty( $redirect ) ? admin_url() : $redirect,
			$redirect,
			$user
		);
		$redirect = basename( $redirect ) === 'profile.php' ? admin_url() : $redirect;

		setcookie( 'wp_google_auth_redirect_to', null, -1, '/' );

		if ( wp_safe_redirect( $redirect ) ) {
			exit;
		}
	}

	/**
	 * Creates or updates a user
	 *
	 * @param array $user_info User info from Google's userinfo endpoint.
	 */
	private function update_or_create_user( array $user_info ): ?\WP_User {
		if ( ! isset( $user_info['email'] ) ) {
			return false;
		}

		$user = get_user_by( 'email', $user_info['email'] );
		if ( ! $user ) {
			$user_id = wp_insert_user(
				array_filter(
					array(
						'user_pass'       => wp_generate_password( 24 ),
						'user_login'      => $user_info['email'],
						'user_email'      => $user_info['email'],
						'user_registered' => gmdate( 'Y-m-d H:i:s' ),
					)
				)
			);

			if ( is_wp_error( $user_id ) ) {
				return null;
			}

			$user = get_user_by( 'id', $user_id );
		}

		foreach (
			array(
				'first_name'   => 'given_name',
				'last_name'    => 'family_name',
				'display_name' => 'name',
			) as $meta_key => $user_info_key
		) {
			if ( isset( $user_info[ $user_info_key ] ) ) {
				update_user_meta(
					$user->ID,
					$meta_key,
					$user_info[ $user_info_key ]
				);
			}
		}

		$meta = $this->user->get_meta_fields( $user->ID );
		if ( isset( $user_info['picture'] ) ) {
			$meta['picture']        = $user_info['picture'];
			$meta['is_google_user'] = true;
		}
		$this->user->update_meta_fields( $user->ID, $meta );

		return $user;
	}

	/**
	 * Callback for allowed_redirect_hosts filter hook
	 *
	 * Allows redirects to all domains ending with .google.com
	 *
	 * @param array  $hosts An array of allowed host names.
	 * @param string $host  The host name of the redirect destination; empty
	 *                      string if not set.
	 */
	public function allowed_redirect_hosts( array $hosts, string $host ): array {
		$allowed_suffix = '.google.com';
		if ( substr_compare( $host, $allowed_suffix, -strlen( $allowed_suffix ) ) === 0 ) {
			$hosts[] = $host;
		}
		return $hosts;
	}

	/**
	 * Callback for wp_login_errors filter hook
	 *
	 * Adds errors based on the wp_google_auth_error query parameter
	 *
	 * @param \WP_Error $error WP Error object.
	 */
	public function wp_login_errors( \WP_Error $error ): \WP_Error {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['wp_google_auth_error'] ) ? sanitize_key( $_GET['wp_google_auth_error'] ) : null;
        // phpcs:enable

		if ( $action ) {
            // phpcs:disable WordPress.Security.EscapeOutput.UnsafePrintingFunction
			switch ( $action ) {
				case 'missing_params':
					$message = __( '<strong>Error</strong>: Malformatted response to OpenID authentication request. Please try logging in again later.', 'wp_google_auth' );
					break;
				case 'state_mismatch':
					$message = __( '<strong>Error</strong>: Anti-forgery state token mismatch. Please try logging in again.', 'wp_google_auth' );
					break;
				case 'discovery_doc':
					$message = __( '<strong>Error</strong>: There was an error reading the Google API discovery document. Please try logging in again later.', 'wp_google_auth' );
					break;
				case 'token':
					$message = __( '<strong>Error</strong>: There was an error recieving the Google OAuth access token. Please try logging in again later.', 'wp_google_auth' );
					break;
				case 'user_info':
					$message = __( '<strong>Error</strong>: There was an error fetching user info from Google. Please try logging in again later.', 'wp_google_auth' );
					break;
				case 'access_denied':
					$message = __( '<strong>Error</strong>: Sorry, your account cannot login to this site.', 'wp_google_auth' );
					break;
				default:
					$message = __( '<strong>Error</strong>: OpenID login error. Please try logging in again later.', 'wp_google_auth' );
			}
            // phpcs:enable
			$error->add( 'wp_google_auth_' . $action, $message );

			$error->add(
				'wp_google_auth_noopenid_notice',
				sprintf(
					// translators: %s: Anchor attributes.
					__( 'Since the Google OpenID login failed, you were taken to the default WordPress login page. If you want to attempt another sign in with Google, click <a %s>here</a>.', 'wp_google_auth' ),
					'href="' . wp_login_url() . '"'
				),
				'message'
			);
		}

		return $error;
	}
}
