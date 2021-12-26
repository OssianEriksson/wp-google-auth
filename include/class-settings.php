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
	 * Registers hooks for displaying settings
	 */
	public function register_hooks(): void {
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
	 * Remove options created to hold settings
	 */
	public function remove_options() {
		delete_option( 'wp_google_auth_option' );
	}

	/**
	 * Adds plugin settings
	 */
	public function add_settings(): void {
		register_setting(
			'wp_google_auth_option_group',
			'wp_google_auth_option'
		);

		add_settings_section(
			'wp_google_auth_settings_section_general',
			__( 'Login rules', 'wp_google_auth' ),
			function(): void {
				?>
				<p><?php esc_html_e( 'Decide which Google Workspace accounds can log in to this WordPress site', 'wp_google_auth' ); ?><p>
				<?php
			},
			'wp_google_auth_option_group'
		);
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
					<form method="post" action="options.php">
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
}
