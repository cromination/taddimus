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
		$root_dir = self::get_root_directory();

		return apply_filters(
			'webpc_site_root',
			preg_replace( '/(\/|\\\\)/', DIRECTORY_SEPARATOR, $root_dir )
		);
	}

	/**
	 * Returns path to DOCUMENT_ROOT, by default: "%{DOCUMENT_ROOT}/".
	 */
	public static function get_rewrite_root(): string {
		$root_document      = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_document_root(), '\/' ) );
		$root_document_real = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_real_document_root(), '\/' ) );
		$root_wordpress     = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_wordpress_root_path(), '\/' ) );

		$root_path   = trim( str_replace( $root_document_real ?: '', '', $root_wordpress ?: '' ), '\/' );
		$root_suffix = str_replace( '//', '/', sprintf( '/%s/', $root_path ) );

		return apply_filters(
			'webpc_htaccess_rewrite_root',
			( $root_document !== $root_document_real ) ? ( $root_wordpress . '/' ) : ( '%{DOCUMENT_ROOT}' . $root_suffix )
		);
	}

	/**
	 * Returns prefix used before "wp-content/uploads-webpc/", by default: "/".
	 */
	public static function get_rewrite_path(): string {
		$root_document_real = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_real_document_root(), '\/' ) );
		$root_wordpress     = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_wordpress_root_path(), '\/' ) );

		$root_path = trim( str_replace( $root_document_real ?: '', '', $root_wordpress ?: '' ), '\/' );

		return apply_filters(
			'webpc_htaccess_rewrite_path',
			str_replace( '//', '/', sprintf( '/%s/', $root_path ) )
		);
	}

	public static function get_site_url(): string {
		return apply_filters( 'webpc_site_url', ( defined( 'WP_HOME' ) ) ? WP_HOME : get_site_url() );
	}

	private static function get_root_directory(): string {
		return ( defined( 'WP_CONTENT_DIR' ) ) ? dirname( WP_CONTENT_DIR ) : ABSPATH;
	}

	private static function get_document_root(): string {
		return $_SERVER['DOCUMENT_ROOT']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}

	private static function get_real_document_root(): string {
		return realpath( $_SERVER['DOCUMENT_ROOT'] ) ?: ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
}
