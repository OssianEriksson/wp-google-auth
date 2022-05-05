<?php
/**
 * Plugin Name:     Template WordPress Plugin
 * Plugin URI:      https://github.com/fysikteknologsektionen/ftek-google-auth
 * Description:     GitHub template for a WordPress plugin
 * Author:          Ossian Eriksson
 * Author URI:      https://github.com/OssianEriksson
 * Text Domain:     ftek-google-auth
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package ftek\ftek-google-auth
 */

namespace Ftek\GoogleAuth;

require_once __DIR__ . '/vendor/autoload.php';


define( __NAMESPACE__ . '\PLUGIN_FILE', __FILE__ );
define( __NAMESPACE__ . '\PLUGIN_ROOT', dirname( PLUGIN_FILE ) );


/**
 * Enqueue an entrypoint script
 *
 * @param string $handle Script and style handle.
 * @param string $src    Name of a file inside src/entrypoints.
 */
function enqueue_entrypoint_script( string $handle, string $src ): void {
	$exploded = explode( '.js', $src );
	if ( empty( $exploded[ count( $exploded ) - 1 ] ) ) {
		array_pop( $exploded );
		$src = implode( '.js', $src );
	}

	$base_path = '/build/' . $src;

	$asset = require PLUGIN_ROOT . $base_path . '.asset.php';
	if ( file_exists( PLUGIN_ROOT . $base_path . '.css' ) ) {
		wp_enqueue_style(
			$handle,
			plugins_url( $base_path . '.css', PLUGIN_FILE ),
			in_array( 'wp-components', $asset['dependencies'], true ) ? array( 'wp-components' ) : array(),
			$asset['version']
		);
	}
	wp_enqueue_script(
		$handle,
		plugins_url( $base_path . '.js', PLUGIN_FILE ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
	wp_set_script_translations(
		$handle,
		'ftek-google-auth',
		PLUGIN_ROOT . '/languages'
	);
}


add_action(
	'init',
	function(): void {
		$plugin_rel_path = plugin_basename( dirname( PLUGIN_FILE ) ) . '/languages';
		load_plugin_textdomain( 'ftek-google-auth', false, $plugin_rel_path );
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

register_uninstall_hook( __FILE__, __NAMESPACE__ . '\clean' );

