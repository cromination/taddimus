<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\CurlFunctionDisabledNotice;

/**
 * Validates status of cURL Library.
 */
class CurlLibraryDetector implements DetectorInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_error() {
		if ( ! function_exists( 'curl_multi_exec' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_multi_exec' );
		} elseif ( ! function_exists( 'curl_multi_init' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_multi_init' );
		} elseif ( ! function_exists( 'curl_exec' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_exec' );
		} elseif ( ! function_exists( 'curl_init' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_init' );
		}

		return null;
	}
}
