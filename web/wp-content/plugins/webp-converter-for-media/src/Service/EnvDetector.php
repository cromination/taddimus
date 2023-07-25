<?php

namespace WebpConverter\Service;

/**
 * Allows to detect specific server environments.
 */
class EnvDetector {

	public static function is_cdn_bunny(): bool {
		if ( ! class_exists( '\BunnyCDN' ) || ! is_callable( '\BunnyCDN::getOptions' ) ) {
			return false;
		}

		$options = \BunnyCDN::getOptions();
		return ( ( $options['site_url'] ?? '' ) && ( $options['cdn_domain_name'] ?? '' ) );
	}
}
