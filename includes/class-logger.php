<?php
/**
 * Class for logging
 *
 * @package ftek/google-auth
 */

namespace Ftek\GoogleAuth;

/**
 * Loggin utils.
 */
class Logger {

	/**
	 * Name of this logger
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Logger constructor
	 *
	 * @param string $name Name of this logger.
	 */
	public function __construct( string $name ) {
		$this->name = $name;
	}

	/**
	 * Format a message prior to logging
	 *
	 * @param string $message Message to format.
	 */
	private function msg_format( string $message ): string {
		return sprintf( '[%s] %s', $this->name, $message );
	}

	/**
	 * Logs an error if WP_DEBUG is true
	 *
	 * @param string $message Value to log.
	 */
	public function error( string $message ) : void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( $this->msg_format( $message ) );
			// phpcs:enable
		}
	}
}
