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
		$root_dir = ( ! defined( 'UPLOADS' ) ) ? dirname( WP_CONTENT_DIR ) : ABSPATH;

		return apply_filters(
			'webpc_site_root',
			preg_replace( '/(\/|\\\\)/', DIRECTORY_SEPARATOR, $root_dir )
		);
	}

	/**
	 * Returns path to DOCUMENT_ROOT, by default: "%{DOCUMENT_ROOT}/".
	 */
	public static function get_rewrite_root(): string {
		$root_document      = preg_replace( '/(\/|\\\\)/', '/', rtrim( $_SERVER['DOCUMENT_ROOT'], '\/' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$root_document_real = preg_replace( '/(\/|\\\\)/', '/', rtrim( realpath( $_SERVER['DOCUMENT_ROOT'] ) ?: '', '\/' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$root_wordpress     = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_document_root_path(), '\/' ) );

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
		$root_document_real = preg_replace( '/(\/|\\\\)/', '/', rtrim( realpath( $_SERVER['DOCUMENT_ROOT'] ) ?: '', '\/' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$root_wordpress     = preg_replace( '/(\/|\\\\)/', '/', rtrim( self::get_document_root_path(), '\/' ) );

		$root_path = trim( str_replace( $root_document_real ?: '', '', $root_wordpress ?: '' ), '\/' );

		return apply_filters(
			'webpc_htaccess_rewrite_path',
			str_replace( '//', '/', sprintf( '/%s/', $root_path ) )
		);
	}

	/**
	 * Returns real path for DOCUMENT_ROOT value.
	 */
	private static function get_document_root_path(): string {
		$root_dir      = self::get_wordpress_root_path();
		$document_root = realpath( $_SERVER['DOCUMENT_ROOT'] ) ?: ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( rtrim( $document_root, '\/' ) === rtrim( dirname( $root_dir ), '\/' ) ) {
			$website_url = apply_filters( 'webpc_site_url', ( defined( 'WP_HOME' ) ) ? WP_HOME : get_site_url() );
			if ( preg_match( '/(.*)\/(' . basename( $root_dir ) . ')\/?$/', $website_url ) ) {
				return $root_dir;
			}

			return $document_root;
		}

		return $root_dir;
	}
}
