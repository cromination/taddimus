<?php

namespace WebpConverter\Service;

/**
 * Manages required server configuration.
 */
class ServerConfigurator {

	public function set_memory_limit( int $value = 2 ): void {
		ini_set( 'memory_limit', sprintf( '%sG', $value ) ); // phpcs:ignore
	}

	public function set_execution_time( int $seconds = 120 ): void {
		if ( strpos( ini_get( 'disable_functions' ) ?: '', 'set_time_limit' ) === false ) {
			set_time_limit( $seconds );
		}
	}
}
