<?php

namespace WebpConverter\Conversion\Directory;

/**
 * Supports data about /uploads directory.
 */
class UploadsDirectory extends DirectoryAbstract {

	const DIRECTORY_TYPE = 'uploads';
	const DIRECTORY_PATH = '%s/uploads';

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return self::DIRECTORY_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_relative_path(): string {
		if ( defined( 'UPLOADS' ) ) {
			return trim( UPLOADS, '/\\' );
		}

		$upload_path = trim( get_option( 'upload_path' ) ?: '' );
		if ( $upload_path === '' ) {
			return sprintf( self::DIRECTORY_PATH, basename( WP_CONTENT_DIR ) );
		}

		if ( strpos( $upload_path, ABSPATH ) === 0 ) {
			return trim( substr( $upload_path, strlen( ABSPATH ) ), '/\\' );
		} elseif ( path_is_absolute( $upload_path ) ) {
			return sprintf( self::DIRECTORY_PATH, basename( WP_CONTENT_DIR ) );
		}

		return trim( $upload_path, '/\\' );
	}
}
