<?php

namespace WebpConverter\Service;

/**
 * Manages generation of server paths.
 */
class PathsGenerator {

	/**
	 * Returns path to root directory of WordPress installation.
	 */
	public static function get_wordpress_root_path(): string {
		return apply_filters(
			'webpc_site_root',
			preg_replace( '/(\/|\\\\)/', DIRECTORY_SEPARATOR, dirname( wp_upload_dir()['basedir'], 2 ) )
		);
	}
}
