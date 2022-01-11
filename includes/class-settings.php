<?php
/**
 * Class for plugin settings management
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
 * Plugin settings state
 */
class Settings {

	/**
	 * Settings constructor
	 */
	public function __construct() {
		add_action(
			'admin_init',
			array( $this, 'add_settings' )
		);
		add_action(
			'admin_menu',
			array( $this, 'add_settings_page' )
		);
	}

	/**
	 * Retrieves a setting value
	 *
	 * @param string $name Name of setting to fetch.
	 */
	public function get( string $name ) {
		return array_merge(
			array(
				'client_id'     => '',
				'client_secret' => '',
				'email_regex'   => '.*',
				'cache_refresh' => 24,
				'error'         => true,
			),
			get_option( 'wp_google_auth_option', array() )
		)[ $name ];
	}

	/**
	 * Adds plugin settings
	 */
	public function add_settings(): void {
		register_setting(
			'wp_google_auth_option_group',
			'wp_google_auth_option',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'wp_google_auth_settings_section_credentials',
			__( 'OAuth2 Credentials', 'wp_google_auth' ),
			function(): void {
				?>
				<p><?php esc_html_e( 'Enter Google OAuth2 credentials aquired from Google Workspace.', 'wp_google_auth' ); ?><p>
				<p>
					<?php
					printf(
						// translators: %1$s: Anchor attributes. %2$s: URI.
						wp_kses_post( __( 'To get your Google API client, <a %1$s>follow these instructions</a>. Make sure to enter %2$s as an allowed redirect URI.', 'wp_google_auth' ) ),
						'href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid" target="_blank" rel="noopener noreferrer"',
						'<code>' . esc_html( OAuth::get_redirect_uri() ) . '</code>'
					)
					?>
				</p>
				<?php
			},
			'wp_google_auth_option_group'
		);

		add_settings_field(
			'wp_google_auth_settings_field_client_id',
			__( 'Client ID', 'wp_google_auth' ),
			function(): void {
				$setting     = 'client_id';
				$placeholder = '000000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com';
				$value       = $this->get( $setting );
				?>
				<input type="password" autocomplete="new-password" name="wp_google_auth_option[<?php echo esc_attr( $setting ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="large-text">
				<?php
			},
			'wp_google_auth_option_group',
			'wp_google_auth_settings_section_credentials'
		);

		add_settings_field(
			'wp_google_auth_settings_field_client_secret',
			__( 'Client secret', 'wp_google_auth' ),
			function(): void {
				$setting     = 'client_secret';
				$placeholder = 'XXXXXX-XXXXXXXXXXXXXX-XXXXXXXXXXXXX';
				$value       = $this->get( $setting );
				?>
				<input type="password" autocomplete="new-password" name="wp_google_auth_option[<?php echo esc_attr( $setting ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="large-text">
				<?php
			},
			'wp_google_auth_option_group',
			'wp_google_auth_settings_section_credentials'
		);

		add_settings_section(
			'wp_google_auth_settings_section_rules',
			__( 'Login Rules', 'wp_google_auth' ),
			function(): void {
				?>
				<p><?php esc_html_e( 'Decide which Google Workspace accounts can log in to this WordPress site.', 'wp_google_auth' ); ?><p>
				<?php
			},
			'wp_google_auth_option_group'
		);

		add_settings_field(
			'wp_google_auth_settings_field_email_regex',
			__( 'Email whitelist regex', 'wp_google_auth' ),
			function(): void {
				$setting     = 'email_regex';
				$placeholder = '(accounting|hr)\.\w+@mydomain\.com';
				$value       = $this->get( $setting );
				?>
				<input type="text" name="wp_google_auth_option[<?php echo esc_attr( $setting ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="large-text">
				<?php
			},
			'wp_google_auth_option_group',
			'wp_google_auth_settings_section_rules'
		);

		add_settings_section(
			'wp_google_auth_settings_section_misc',
			__( 'Miscellaneous', 'wp_google_auth' ),
			function(): void { },
			'wp_google_auth_option_group'
		);

		add_settings_field(
			'wp_google_auth_settings_field_cache_refresh',
			__( 'Cache refresh interval', 'wp_google_auth' ),
			function(): void {
				$setting = 'cache_refresh';
				$value   = $this->get( $setting );
				?>
				<input type="number" name="wp_google_auth_option[<?php echo esc_attr( $setting ); ?>]" value="<?php echo esc_attr( $value ); ?>" min="0" class="small-text" >
				hours
				<?php
			},
			'wp_google_auth_option_group',
			'wp_google_auth_settings_section_misc'
		);
	}

	/**
	 * Sanitizes settings and checks for errors
	 *
	 * @param ?array $option Trial value of option to sanitize.
	 */
	public function sanitize_settings( array $option ): array {
		$option['error'] = false;

		$oauth    = new OAuth( $this );
		$auth_url = $oauth->get_authorization_url( $option['client_id'] );
		if ( empty( $auth_url ) ) {
			add_settings_error(
				'wp_google_auth_option',
				'wp_google_auth_discovery_doc',
				__( 'There was an error reading the Google API discovery document, please try again later.', 'wp_google_auth' ),
				'error'
			);
			$option['error'] = true;
		} else {
			$response = wp_remote_get( $auth_url );
			$code     = wp_remote_retrieve_response_code( $response );
			$url      = $response['http_response']->get_response_object()->url;
			$url_path = wp_parse_url( $url )['path'];
			if ( 200 !== $code || strpos( $url_path, 'error' ) !== false ) {
				add_settings_error(
					'wp_google_auth_option',
					'wp_google_auth_client_id',
					sprintf(
						// translators: %s: Anchor attributes.
						__( 'An OpenID error was detected, click <a %s>here</a> to view the problematic response from Google.', 'wp_google_auth' ),
						'href="' . esc_attr( $auth_url ) . '" target="_blank" rel="noopener noreferrer"'
					),
					'error'
				);
				$option['error'] = true;
			}
		}

		if ( empty( $option['client_secret'] ) ) {
			add_settings_error(
				'wp_google_auth_option',
				'wp_google_auth_client_secret',
				__( 'Client secret cannot be empty.', 'wp_google_auth' ),
				'error'
			);
			$option['error'] = true;
		}

		$cache_refresh           = $option['cache_refresh'];
		$option['cache_refresh'] = max( 0, intval( $cache_refresh ) );
		if ( intval( $cache_refresh ) !== $option['cache_refresh'] ) {
			add_settings_error(
				'wp_google_auth_option',
				'wp_google_auth_cache_refresh',
				__( 'Cache refresh interval was changed to a non-negative integer.', 'wp_google_auth' ),
				'updated'
			);
		}

		if ( ! $option['error'] ) {
			add_settings_error(
				'wp_google_auth_option',
				'wp_google_auth_success',
				__( 'Settings saved. Please test out the login functionality to verify that everything is working as expected.', 'wp_google_auth' ),
				'success'
			);
		}

		return $option;
	}

	/**
	 * Adds a settings page for the plugin
	 */
	public function add_settings_page(): void {
		$options_page = add_options_page(
			__( 'Google Auth', 'wp_google_auth' ),
			__( 'Google Auth', 'wp_google_auth' ),
			'manage_options',
			'wp_google_auth',
			function(): void {
				?>
				<div class="wrap">
					<h1><?php esc_html_e( 'Google Authentication Settings', 'wp_google_auth' ); ?></h1>
					<form method="post" autocomplete="off" action="options.php">
						<?php
						settings_fields( 'wp_google_auth_option_group' );
						do_settings_sections( 'wp_google_auth_option_group' );
						submit_button();
						?>
					</form>
				</div>
				<?php
			}
		);

		add_action( 'load-' . $options_page, array( $this, 'add_settings_help' ) );
	}

	/**
	 * Adds a help dropdown menu to the current screen
	 */
	public function add_settings_help(): void {
		$screen = get_current_screen();

		$screen->add_help_tab(
			array(
				'title'    => __( 'Overview', 'wp_google_auth' ),
				'id'       => 'wp_google_auth_help_tab_overview',
				'callback' => function(): void {
					?>
					<p><?php esc_html_e( 'The fields on this screen determine the setup of the Google Workspace integration for authenticating users.', 'wp_google_auth' ); ?></p>
					<?php
				},
			)
		);
	}

	/**
	 * Removes persistant data
	 */
	public static function clean(): void {
		delete_option( 'wp_google_auth_option' );
	}
}
