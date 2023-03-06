<?php

namespace WebpConverter\Service;

/**
 * Returns size of image downloaded based on server path or URL.
 */
class FileLoader {

	/**
	 * Checks size of file by sending request using active image loader.
	 *
	 * @param string      $url         URL of image.
	 * @param bool        $set_headers Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $ver_param   Additional GET param.
	 *
	 * @return int
	 */
	public function get_file_size_by_url( string $url, bool $set_headers = true, string $ver_param = null ): int {
		$connect = $this->get_curl_connection( $url, $set_headers, $ver_param );
		if ( $connect === null ) {
			return 0;
		}

		$response = curl_exec( $connect );
		$code     = curl_getinfo( $connect, CURLINFO_HTTP_CODE );
		curl_close( $connect );

		return ( $code === 200 )
			? strlen( is_string( $response ) ? $response : '' )
			: 0;
	}

	/**
	 * Checks HTTP status of file by sending request using active image loader.
	 *
	 * @param string      $url         URL of image.
	 * @param bool        $set_headers Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $ver_param   Additional GET param.
	 *
	 * @return int
	 */
	public function get_file_status_by_url( string $url, bool $set_headers = true, string $ver_param = null ): int {
		$connect = $this->get_curl_connection( $url, $set_headers, $ver_param );
		if ( $connect === null ) {
			return 0;
		}

		curl_exec( $connect );
		$code = curl_getinfo( $connect, CURLINFO_HTTP_CODE );
		curl_close( $connect );

		return $code;
	}

	/**
	 * Returns size of file.
	 *
	 * @param string $path Server path of file.
	 *
	 * @return int Size of file.
	 */
	public function get_file_size_by_path( string $path ): int {
		return ( file_exists( $path ) ) ? ( filesize( $path ) ?: 0 ) : 0;
	}

	/**
	 * @param string      $url         URL of image.
	 * @param bool        $set_headers Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $ver_param   Additional GET param.
	 *
	 * @return resource|null
	 */
	private function get_curl_connection( string $url, bool $set_headers, string $ver_param = null ) {
		$headers = ( $set_headers )
			? [ 'Accept: image/webp,image/*' ]
			: [ 'Accept: image/*' ];

		$image_url = $url;
		if ( $ver_param !== null ) {
			$image_url = add_query_arg( 'ver', $ver_param, $image_url );
		}
		$image_url = apply_filters( 'webpc_debug_image_url', $image_url );

		foreach ( wp_get_nocache_headers() as $header_key => $header_value ) {
			$headers[] = sprintf( '%s: %s', $header_key, $header_value );
		}

		$ch = curl_init( $image_url );
		if ( $ch === false ) {
			return null;
		}

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $ch, CURLOPT_USERPWD, sprintf( '%1$s:%2$s', $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		return $ch;
	}
}
