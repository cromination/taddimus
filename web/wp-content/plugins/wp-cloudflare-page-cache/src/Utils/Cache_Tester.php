<?php

namespace SPC\Utils;

use SPC\Constants;
use SPC\Loader;
use SPC\Services\Settings_Store;

class Cache_Tester {
	/**
	 * Run the combined Cloudflare + disk-cache test against browser-captured
	 * Cloudflare response headers and return the API response.
	 *
	 * @param array<string, mixed> $cloudflare_headers Headers map captured by the
	 *                                                  browser from the verify-pass
	 *                                                  response. Header names are
	 *                                                  case-insensitive.
	 * @return array<string, mixed>
	 */
	public function run( array $cloudflare_headers ) {
		$settings_store        = Settings_Store::get_instance();
		$test_url              = self::get_test_url();
		$is_disk_cache_enabled = $settings_store->get( Constants::SETTING_ENABLE_FALLBACK_CACHE );
		$is_cloudflare_enabled = $settings_store->is_cloudflare_connected() && (int) $settings_store->get( Constants::ENABLE_CACHE_RULE ) === 1;

		$results = [
			'overall_status' => 'success',
			'cloudflare'     => $this->test_cloudflare_cache( $cloudflare_headers, $is_cloudflare_enabled ),
			'disk_cache'     => $this->test_disk_cache( $test_url, $is_disk_cache_enabled ),
			'test_url'       => $test_url,
			'configuration'  => [
				'cloudflare_enabled' => $is_cloudflare_enabled,
				'disk_cache_enabled' => $is_disk_cache_enabled,
			],
		];

		$has_errors = $results['cloudflare']['status'] === 'error' || $results['disk_cache']['status'] === 'error';

		if ( ! $is_cloudflare_enabled && ! $is_disk_cache_enabled ) {
			$results['overall_status'] = 'error';
			$results['message']        = __( 'No caching method is enabled.', 'wp-cloudflare-page-cache' );
		} elseif ( $has_errors ) {
			$results['overall_status'] = 'error';
		}

		return $results;
	}

	/**
	 * Run the cache test from PHP by issuing a HEAD request to the test page.
	 *
	 * This is intended for server-side callers such as WP-CLI that cannot use
	 * browser-captured response headers.
	 *
	 * @return array<string, mixed>
	 */
	public function cli_test() {
		$response = wp_remote_head(
			esc_url_raw( self::get_test_url() ),
			[
				'timeout'    => defined( 'SWCFPC_CURL_TIMEOUT' ) ? SWCFPC_CURL_TIMEOUT : 10,
				'sslverify'  => false,
				'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
				'headers'    => [
					'Accept' => 'text/html',
				],
			]
		);

		$headers = [];

		if ( ! is_wp_error( $response ) ) {
			$headers = self::normalize_response_headers( wp_remote_retrieve_headers( $response ) );
		}

		$results = $this->run( $headers );

		if ( is_wp_error( $response ) && 'disabled' !== $results['cloudflare']['status'] ) {
			$results['overall_status']        = 'error';
			$results['cloudflare']['status']  = 'error';
			$results['cloudflare']['message'] = __( 'Cloudflare integration has an issue.', 'wp-cloudflare-page-cache' );
			$results['cloudflare']['errors']  = [
				__( 'Connection error: ', 'wp-cloudflare-page-cache' ) . $response->get_error_message(),
			];
		}

		return $results;
	}

	/**
	 * Default test URL — the plugin's static asset.
	 *
	 * @return string
	 */
	private static function get_test_url() {
		return SWCFPC_PLUGIN_URL . 'assets/testcache.html';
	}

	/**
	 * Convert WordPress HTTP headers to a plain array.
	 *
	 * @param \Traversable<string, mixed>|array<string, mixed>|mixed $headers
	 * @return array<string, mixed>
	 */
	private static function normalize_response_headers( $headers ) {
		if ( is_array( $headers ) ) {
			return $headers;
		}

		if ( $headers instanceof \Traversable ) {
			return iterator_to_array( $headers );
		}

		return [];
	}

	/**
	 * Build the Cloudflare section of the response from the captured headers.
	 *
	 * @param array<string, mixed> $cloudflare_headers
	 * @param bool                 $is_cloudflare_enabled
	 * @return array
	 */
	private function test_cloudflare_cache( array $cloudflare_headers, $is_cloudflare_enabled ) {
		if ( ! $is_cloudflare_enabled ) {
			return [
				'status'      => 'disabled',
				'message'     => __( 'Cloudflare Cache Rule is not enabled.', 'wp-cloudflare-page-cache' ),
				'errors'      => [],
				'action_text' => __( 'Open Cache Rule settings', 'wp-cloudflare-page-cache' ),
				'action_link' => admin_url( 'admin.php?page=super-page-cache-settings#cloudflare' ),
			];
		}

		$check = self::classify_cloudflare_headers( $cloudflare_headers );

		if ( ! $check['ok'] ) {
			return [
				'status'               => 'error',
				'message'              => __( 'Cloudflare integration has an issue.', 'wp-cloudflare-page-cache' ),
				'errors'               => $check['errors'],
				'troubleshooting_note' => __( 'Verify page caching manually by opening the site in an incognito window — Cloudflare sometimes bypasses the cache for cURL requests. Reload the page two or three times. If the response header shows cf-cache-status: HIT, page caching is working correctly.', 'wp-cloudflare-page-cache' ),
				'action_text'          => __( 'View troubleshooting guide', 'wp-cloudflare-page-cache' ),
				'action_link'          => 'https://docs.themeisle.com/super-page-cache/troubleshooting-cloudflare-integration-has-an-issue',
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Cloudflare Page Caching is working properly.', 'wp-cloudflare-page-cache' ),
			'errors'  => [],
		];
	}

	/**
	 * Classify a Cloudflare-fronted response from its headers map.
	 *
	 * Pure function: no I/O, no instance state, no globals. Header lookup is
	 * case-insensitive.
	 *
	 * @param array<string, mixed> $headers Response headers map.
	 * @return array{ok: bool, errors: string[]}
	 */
	private static function classify_cloudflare_headers( array $headers ) {
		$normalized = [];
		foreach ( $headers as $key => $value ) {
			$normalized[ strtolower( (string) $key ) ] = $value;
		}

		$errors = [];
		$status = isset( $normalized['cf-cache-status'] ) ? sanitize_text_field( (string) $normalized['cf-cache-status'] ) : '';

		// +-------- Invalid Cache Status --------+
		if ( '' === $status ) {
			$errors[] = __( 'It seems your website is not behind Cloudflare. If you\'ve recently enabled the cache or this is your first test, wait about 30 seconds and try again — changes take a few seconds to propagate. If the error persists, contact support for a detailed check.', 'wp-cloudflare-page-cache' );
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		$is_swr_enabled = self::should_allow_swr_cache_status( $headers );

		if ( 0 === strcasecmp( $status, 'REVALIDATED' ) ) {
			if ( $is_swr_enabled ) {
				return [
					'ok'     => true,
					'errors' => $errors,
				];
			}

			// translators: %s is the CF-Cache-Status header value.
			$errors[] = sprintf( __( 'Cache status: %s — The cached resource is stale and was revalidated using conditional headers.', 'wp-cloudflare-page-cache' ), $status );
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		if ( 0 === strcasecmp( $status, 'UPDATING' ) ) {
			if ( $is_swr_enabled ) {
				return [
					'ok'     => true,
					'errors' => $errors,
				];
			}

			// translators: %s is the CF-Cache-Status header value.
			$errors[] = sprintf( __( 'Cache status: %s — The cached resource has expired and is being refreshed. This status is common for high-traffic resources.', 'wp-cloudflare-page-cache' ), $status );
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		if ( 0 === strcasecmp( $status, 'BYPASS' ) ) {
			// translators: %s is the CF-Cache-Status header value.
			$errors[] = sprintf( __( 'Cache status: %s - Cloudflare has been instructed to not cache this asset. It has been served directly from the origin.', 'wp-cloudflare-page-cache' ), $status );
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		if ( 0 === strcasecmp( $status, 'DYNAMIC' ) ) {
			// translators: %s is the CF-Cache-Status header value.
			$error    = sprintf( __( 'Cache status: %s - The resource was not cached by default and your current Cloudflare caching configuration doesn\'t instruct Cloudflare to cache the resource.', 'wp-cloudflare-page-cache' ), $status );
			$error   .= ' ' . __( 'Try to enable the <strong>Strip response cookies on pages that should be cached</strong> option and retry.', 'wp-cloudflare-page-cache' );
			$errors[] = $error;
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		// +-------- Cache Control --------+
		$cache_control = isset( $normalized['cache-control'] ) ? (string) $normalized['cache-control'] : '';
		if ( '' !== $cache_control && 0 === strcasecmp( $cache_control, '{resp:x-wp-cf-super-cache-cache-control}' ) ) {
			$errors[] = __( 'Invalid Cache-Control response header. If you are using LiteSpeed Server, please disable the option <strong>Overwrite the cache-control header for WordPress\'s pages using web server rules</strong>, purge the cache and retry.', 'wp-cloudflare-page-cache' );
			return [
				'ok'     => false,
				'errors' => $errors,
			];
		}

		// +-------- Valid Cache Status --------+
		if (
			0 === strcasecmp( $status, 'HIT' ) ||
			0 === strcasecmp( $status, 'MISS' ) ||
			0 === strcasecmp( $status, 'EXPIRED' )
		) {
			return [
				'ok'     => true,
				'errors' => $errors,
			];
		}

		$errors[] = __( 'Undefined error', 'wp-cloudflare-page-cache' );
		return [
			'ok'     => false,
			'errors' => $errors,
		];
	}

	/**
	 * @param \WpOrg\Requests\Utility\CaseInsensitiveDictionary|array<string, mixed> $headers
	 */
	private static function should_allow_swr_cache_status( $headers ): bool {
		return Settings_Store::get_instance()->response_has_swr_directive( $headers );
	}

	/**
	 * Test disk cache by writing the test URL to disk and reading it back.
	 *
	 * Stays server-side: a Cloudflare HIT never reaches the origin's disk cache,
	 * so a browser-side disk-cache test is impossible whenever CF fronts the
	 * origin.
	 *
	 * @param string $test_url
	 * @param bool   $is_disk_cache_enabled
	 * @return array
	 */
	private function test_disk_cache( $test_url, $is_disk_cache_enabled ) {
		if ( ! $is_disk_cache_enabled ) {
			return [
				'status'  => 'disabled',
				'message' => __( 'Disk Page Cache is not enabled.', 'wp-cloudflare-page-cache' ),
				'errors'  => [],
			];
		}

		$fallback_cache = Loader::get()->fallback_cache();

		$fallback_cache->fallback_cache_add_current_url_to_cache( $test_url, true );
		$cache_success = $fallback_cache->fallback_cache_check_cached_page( $test_url );

		if ( ! $cache_success ) {
			return [
				'status'  => 'error',
				'message' => __( 'Disk Page Caching has an issue.', 'wp-cloudflare-page-cache' ),
				'errors'  => [ __( 'Disk Page Cache could not store the cached page.', 'wp-cloudflare-page-cache' ) ],
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Disk Page Caching is functional.', 'wp-cloudflare-page-cache' ),
			'errors'  => [],
		];
	}
}
