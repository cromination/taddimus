<?php

namespace WebpConverter\Service;

/**
 * Allows to detect specific server environments.
 */
class EnvDetector {

	public static function is_cdn_bunny(): bool {
		if ( ! is_plugin_active( 'bunnycdn/bunnycdn.php' ) ) {
			return false;
		}

		$status = (int) get_option( 'bunnycdn_cdn_status', 0 );
		return ( $status === 1 );
	}
}
