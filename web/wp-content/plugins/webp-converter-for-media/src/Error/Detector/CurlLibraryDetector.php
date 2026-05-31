<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\CurlFunctionDisabledNotice;
use WebpConverter\Error\Notice\NoticeInterface;

/**
 * Validates status of cURL Library.
 */
class CurlLibraryDetector implements DetectorInterface {

	public function get_error(): ?NoticeInterface {
		if ( ! function_exists( 'curl_init' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_init' );
		} elseif ( ! function_exists( 'curl_exec' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_exec' );
		} elseif ( ! function_exists( 'curl_multi_init' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_multi_init' );
		} elseif ( ! function_exists( 'curl_multi_exec' ) ) {
			return new CurlFunctionDisabledNotice( 'curl_multi_exec' );
		}

		return null;
	}
}
