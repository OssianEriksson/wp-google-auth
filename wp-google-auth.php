<?php
/**
 * Main plugin file
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
 * Plugin Name: WP Google Auth
 * Description: WordPress plugin for syncing WordPress users with Google Workspace users.
 * Version: 1.0.0
 * Text Domain: wp-google-auth
 * Domain Path: /languages
 * Author: Ossian Eriksson
 * Author URI: https://github.com/OssianEriksson
 * Licence: GLP-3.0
 *
 * @package ftek/wp-google-auth
 */

namespace Ftek\WPGoogleAuth;

if ( ! defined( 'WPINC' ) ) {
	die;
}

chdir( __DIR__ );

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'init',
	function() {
		load_plugin_textdomain(
			'wp-google-auth',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

$settings = new Settings();
if ( $settings->get( 'error' ) === false ) {
	$user  = new User();
	$login = new Login( $settings, $user );
}

/**
 * Removes persistant data
 */
function clean() {
	Settings::clean();
	Endpoints::clean();
}

register_uninstall_hook( __FILE__, 'clean' );
