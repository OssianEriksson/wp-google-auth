<?php
/**
 * Handles plugin settings
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
 * Handles plugin settings
 */
class Settings {

	const DEFAULT_SETTINGS = array(
		'client_id'      => '',
		'client_secret'  => '',
		'email_patterns' => array(),
		'cache_refresh'  => 24,
		'error'          => true,
	);

	/**
	 * Default constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_settings' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'plugin_action_links_wp-google-auth/wp-google-auth.php', array( $this, 'add_settings_action_link' ) );
	}

	/**
	 * Returns setting values
	 *
	 * @param ?string $key Key of requested setting or null for the entire
	 *                     setting array.
	 */
	public function get( ?string $key = null ) {
		$option = get_option( 'wp_google_auth_option' );
		$option = array_merge( self::DEFAULT_SETTINGS, $option ? $option : array() );
		return null === $key ? $option : $option[ $key ];
	}

	/**
	 * Adds plugin settings using the WordPress Settings API
	 */
	public function add_settings(): void {
		register_setting(
			'wp_google_auth_option_group',
			'wp_google_auth_option',
			array(
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'required'   => true,
						'properties' => array(
							'client_id'      => array(
								'type'     => 'string',
								'required' => true,
							),
							'client_secret'  => array(
								'type'     => 'string',
								'required' => true,
							),
							'email_patterns' => array(
								'type'     => 'array',
								'required' => true,
								'items'    => array(
									'regex' => array(
										'type'     => 'string',
										'required' => true,
									),
									'roles' => array(
										'type'     => 'array',
										'required' => true,
										'items'    => array(
											'type' => 'string',
										),
									),
								),
							),
							'cache_refresh'  => array(
								'type'     => 'integer',
								'required' => true,
							),
							'error'          => array(
								'type'     => 'boolean',
								'required' => true,
							),
						),
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::DEFAULT_SETTINGS,
			)
		);
	}

	/**
	 * Validates a Google OAuth client
	 *
	 * Returns an array of detected errors.
	 *
	 * @param string $client_id     Client ID.
	 * @param string $client_secret Client secret.
	 */
	public function validate_oauth_client( string $client_id, string $client_secret ): array {
		$errors = array();

		if ( empty( $client_id ) ) {
			$errors[] = __( 'Client ID cannot be empty.', 'wp-google-auth' );
		} else {
			$oauth    = new OAuth( $this );
			$auth_url = $oauth->get_authorization_url( $client_id );
			if ( empty( $auth_url ) ) {
				$errors[] = __( 'There was an error reading the Google API discovery document, please try again later.', 'wp-google-auth' );
			} else {
				$response = wp_remote_get( $auth_url );
				$code     = wp_remote_retrieve_response_code( $response );
				$url      = $response['http_response']->get_response_object()->url;
				$url_path = wp_parse_url( $url )['path'];
				if ( 200 !== $code || strpos( $url_path, 'error' ) !== false ) {
					$errors[] = sprintf(
						// translators: %1$s: URL.
						__( 'An OpenID error was detected: %1$s', 'wp-google-auth' ),
						$auth_url
					);
				}
			}
		}

		if ( empty( $client_secret ) ) {
			$errors[] = __( 'Client secret cannot be empty.', 'wp-google-auth' );
		}

		return $errors;
	}

	/**
	 * Sanitizes settings and checks for errors
	 *
	 * @param ?array $option Trial value of option to sanitize.
	 */
	public function sanitize_settings( array $option ): array {
		$option['error'] = false;

		if ( $this->validate_oauth_client( $option['client_id'], $option['client_secret'] ) ) {
			$option['error'] = true;
		}

		$cache_refresh           = $option['cache_refresh'];
		$option['cache_refresh'] = max( 0, intval( $cache_refresh ) );

		return $option;
	}

	/**
	 * Adds an admin menu page for plugin settings
	 */
	public function add_settings_page(): void {
		$settings_page = add_options_page(
			__( 'Google Auth', 'wp-google-auth' ),
			__( 'Google Auth', 'wp-google-auth' ),
			'manage_options',
			'wp_google_auth_settings',
			function(): void {
				?>
				<div id="wp-google-auth-settings" class="wrap"></div>
				<?php
			}
		);

		if ( $settings_page ) {
			add_action(
				'load-' . $settings_page,
				function(): void {
					$this->add_settings_help();

					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_page_scripts' ) );
				}
			);
		}
	}

	/**
	 * Enqueues scripts and styles needed on the settings page
	 */
	public function enqueue_settings_page_scripts(): void {
		enqueue_entrypoint_script( 'wp-google-auth-settings', 'settings.tsx' );

		wp_add_inline_script(
			'wp-google-auth-settings',
			'const wpGoogleAuth = ' . wp_json_encode(
				array(
					'roles' => $this->get_available_roles(),
				)
			),
			'before'
		);
	}

	/**
	 * Adds a help dropdown to the current screen
	 */
	public function add_settings_help(): void {
		$screen = get_current_screen();
		$screen->add_help_tab(
			array(
				'title'    => __( 'OAuth Client', 'wp-google-auth' ),
				'id'       => 'wp_google_auth_help_tab_overview',
				'callback' => function(): void {
					?>
					<p>
						<?php
						sprintf(
							// translators: %1$s: Anchor attributes.
							__( 'To perform logins with Google, this plugin needs access to a Google OAuth client id and secret. To create a Google OAuth client as a Google Workspace admin you can follow the instrcutions <a %1$s>here</a>.', 'wp-google-auth' ),
							'href="https://support.google.com/cloud/answer/6158849" target="_blank" rel="noopener noreferrer"'
						);
						?>
					</p>
					<?php
				},
			)
		);
	}

	/**
	 * Filters plugin_actions_links to add a link to the plugin settings page
	 *
	 * @param array $actions An array of plugin action links.
	 */
	public function add_settings_action_link( array $actions ): array {
		$url = add_query_arg(
			'page',
			'wp_google_auth_settings',
			get_admin_url() . 'options-general.php'
		);

		ob_start();
		?>
		<a href="<?php echo esc_attr( $url ); ?>">
			<?php esc_html_e( 'Settings', 'wp-google-auth' ); ?>
		</a>
		<?php
		$actions[] = ob_get_clean();
		return $actions;
	}

	/**
	 * Returns an array of all user roles with the upload_files capability
	 */
	public function get_available_roles(): array {
		$roles = array();
		foreach ( wp_roles()->role_objects as $key => $role ) {
			$roles[] = array(
				'key'  => $key,
				'name' => translate_user_role( ucfirst( $role->name ) ),
			);
		}
		return $roles;
	}

	/**
	 * Registers custom WordPress REST API routes
	 */
	public function rest_api_init(): void {
		register_rest_route(
			'wp-google-auth/v1',
			'/validate/oauth-client',
			array(
				'methods'             => 'GET',
				'args'                => array(
					'type'          => 'string',
					'client_id'     => array(
						'required' => true,
					),
					'client_secret' => array(
						'required' => true,
					),
				),
				'callback'            => function( \WP_REST_Request $request ): array {
					return $this->validate_oauth_client( $request->get_param( 'client_id' ), $request->get_param( 'client_secret' ) );
				},
				'permission_callback' => function(): string {
					return current_user_can( 'manage_options' );
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
