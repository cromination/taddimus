<?php
/**
 * Loads images in AVIF/WebP format or original images if browser does not support AVIF/WebP.
 *
 * @category WordPress Plugin
 * @package  Converter for Media
 * @author   Mateusz Gbiorczyk
 * @link     https://wordpress.org/plugins/webp-converter-for-media/
 */

/**
 * Loads lighter weight image files in output format instead of original formats.
 */
class PassthruLoader {

	const ALLOWED_URL_PREFIXES = '';
	const MIME_TYPES           = '';

	public function __construct() {
		if ( ( self::ALLOWED_URL_PREFIXES === '' ) || ( self::MIME_TYPES === '' ) ) {
			http_response_code( 404 );
			exit;
		}

		$image_url = filter_input( INPUT_GET, 'src', FILTER_SANITIZE_URL );
		if ( $image_url ) {
			$this->load_validated_image_path( stripslashes( $image_url ) );
		}
	}

	private function load_validated_image_path( string $image_url ): void {
		if ( ( strpos( $image_url, '..' ) !== false ) || ( strpos( $image_url, '\\' ) !== false ) ) {
			return;
		}

		$url_path        = parse_url( $image_url, PHP_URL_PATH ) ?: '';
		$image_extension = strtolower( pathinfo( $url_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $image_extension, [ 'jpg', 'jpeg', 'png', 'gif', 'png2' ] ) ) {
			return;
		}

		$allowed_bases = json_decode( base64_decode( self::ALLOWED_URL_PREFIXES ) ?: '', true ) ?: []; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$is_allowed    = false;
		foreach ( $allowed_bases as $base_url => $server_path ) {
			if ( strpos( $image_url, $base_url ) === 0 ) {
				$is_allowed = true;
				$this->load_converted_image(
					str_replace( $base_url, $server_path, $image_url )
				);
			}
		}

		if ( $is_allowed ) {
			header( 'Location: ' . $image_url );
		}
	}

	private function load_converted_image( string $image_path ) {
		$mime_types    = json_decode( base64_decode( self::MIME_TYPES ) ?: '', true ) ?: []; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$headers       = array_change_key_case(
			array_merge( ( function_exists( 'getallheaders' ) ) ? getallheaders() : [], $_SERVER ),
			CASE_UPPER
		);
		$accept_header = $headers['ACCEPT'] ?? ( $headers['HTTP_ACCEPT'] ?? '' );

		foreach ( $mime_types as $extension => $mime_type ) {
			if ( strpos( $accept_header, $mime_type ) !== false ) {
				$output_image_path      = str_replace( '\\', '/', $image_path . '.' . $extension );
				$real_output_image_path = str_replace( '\\', '/', realpath( $image_path . '.' . $extension ) ?: '' );
				if ( is_readable( $output_image_path ) && ( $output_image_path === $real_output_image_path ) ) {
					header( 'X-Content-Type-Options: nosniff' );
					header( 'Content-Type: ' . $mime_type );
					header( 'Content-Length: ' . filesize( $output_image_path ) );
					readfile( $output_image_path );
					exit;
				}
			}
		}
	}
}

new PassthruLoader();
