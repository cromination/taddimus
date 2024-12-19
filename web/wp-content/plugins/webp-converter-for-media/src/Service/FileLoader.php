<?php

namespace WebpConverter\Service;

/**
 * Returns size of image downloaded based on server path or URL.
 */
class FileLoader {

	const GLOBAL_LOGS_VARIABLE = 'webpc_logs';

	/**
	 * Checks size of file by sending request using active image loader.
	 *
	 * @param string      $url           URL of image.
	 * @param bool        $set_headers   Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $ver_param     Additional GET param.
	 * @param string|null $debug_context .
	 *
	 * @return int
	 */
	public function get_file_size_by_url( string $url, bool $set_headers = true, ?string $ver_param = null, ?string $debug_context = null ): int {
		$request_url     = $this->get_curl_url( $url, $ver_param );
		$request_headers = $this->get_curl_headers( $set_headers );
		$connect         = $this->get_curl_connection( $request_url, $request_headers );
		if ( $connect === null ) {
			return 0;
		}

		$response = curl_exec( $connect );
		if ( ! is_string( $response ) ) {
			$response = '';
		}

		$http_code  = curl_getinfo( $connect, CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $connect );
		curl_close( $connect );

		if ( $debug_context !== null ) {
			$this->log_request( $debug_context, $request_url, $set_headers, $http_code, $curl_error, strlen( $response ) );
		}

		return ( $http_code === 200 ) ? strlen( $response ) : 0;
	}

	/**
	 * Checks HTTP status of file by sending request using active image loader.
	 *
	 * @param string      $url           URL of image.
	 * @param bool        $set_headers   Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $ver_param     Additional GET param.
	 * @param string|null $debug_context .
	 *
	 * @return int
	 */
	public function get_file_status_by_url( string $url, bool $set_headers = true, ?string $ver_param = null, ?string $debug_context = null ): int {
		$request_url     = $this->get_curl_url( $url, $ver_param );
		$request_headers = $this->get_curl_headers( $set_headers );
		$connect         = $this->get_curl_connection( $request_url, $request_headers );
		if ( $connect === null ) {
			return 0;
		}

		curl_exec( $connect );
		$http_code  = curl_getinfo( $connect, CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $connect );
		curl_close( $connect );

		if ( $debug_context !== null ) {
			$this->log_request( $debug_context, $request_url, $set_headers, $http_code, $curl_error, null );
		}

		return $http_code;
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
	 * @param string      $url       URL of image.
	 * @param string|null $ver_param Additional GET param.
	 *
	 * @return string
	 */
	private function get_curl_url( string $url, ?string $ver_param = null ): string {
		$image_url = $url;
		if ( $ver_param !== null ) {
			$image_url = add_query_arg( 'ver', $ver_param, $image_url );
		}
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wccp-pro/preventer-index.php' ) ) {
			$image_url = add_query_arg( 'wccp_pro_watermark_pass', '', $image_url );
		}

		return apply_filters( 'webpc_debug_image_url', $image_url );
	}

	/**
	 * @param bool $set_headers Whether to send headers to confirm that browser supports WebP?
	 *
	 * @return string[]
	 */
	private function get_curl_headers( bool $set_headers ): array {
		$headers = ( $set_headers )
			? [ 'Accept: image/webp,image/*' ]
			: [ 'Accept: image/*' ];

		foreach ( wp_get_nocache_headers() as $header_key => $header_value ) {
			$headers[] = sprintf( '%s: %s', $header_key, $header_value );
		}
		return $headers;
	}

	/**
	 * @param string   $url     .
	 * @param string[] $headers .
	 *
	 * @return resource|null
	 */
	private function get_curl_connection( string $url, array $headers ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return null;
		}

		$ch = curl_init( $url );
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
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_REFERER, PathsGenerator::get_site_url() );

		return $ch;
	}

	/**
	 * @param string      $debug_context   .
	 * @param string      $url             .
	 * @param bool        $is_webp_request .
	 * @param int         $response_code   .
	 * @param string|null $curl_error      .
	 * @param int|null    $response_length .
	 *
	 * @return void
	 */
	private function log_request(
		string $debug_context,
		string $url,
		bool $is_webp_request,
		int $response_code,
		?string $curl_error = null,
		?int $response_length = null
	) {
		if ( ! isset( $GLOBALS[ self::GLOBAL_LOGS_VARIABLE ] ) ) {
			$GLOBALS[ self::GLOBAL_LOGS_VARIABLE ] = [];
		}

		$GLOBALS[ self::GLOBAL_LOGS_VARIABLE ][] = [
			'context'    => $debug_context,
			'url'        => $url,
			'is_webp'    => $is_webp_request,
			'http_code'  => $response_code,
			'response'   => $response_length,
			'curl_error' => ( $curl_error === '' ) ? null : $curl_error,
		];
	}
}
