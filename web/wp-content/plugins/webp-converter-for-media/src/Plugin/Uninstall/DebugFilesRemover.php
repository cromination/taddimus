<?php

namespace WebpConverter\Plugin\Uninstall;

use WebpConverter\Error\Detector\RewritesErrorsDetector;

/**
 * Removes files needed for debugging from /uploads directory.
 */
class DebugFilesRemover {

	/**
	 * Removes files used for debugging from /uploads directory.
	 */
	public static function remove_debug_files(): void {
		$uploads_dir = apply_filters( 'webpc_dir_path', '', 'uploads' );

		if ( is_writable( $uploads_dir . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG ) ) {
			unlink( $uploads_dir . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG );
		}
		if ( is_writable( $uploads_dir . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG2 ) ) {
			unlink( $uploads_dir . RewritesErrorsDetector::PATH_OUTPUT_FILE_PNG2 );
		}
	}
}
