<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Settings_Store;
use SPC\Services\Varnish;
use SPC\Utils\Cache_Tester;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class WP_CLI extends \WP_CLI_Command implements Module_Interface {
	public function init(): void {
		\WP_CLI::add_command( 'cfcache', $this );
	}

	/**
	 * Show current Super Page Cache version
	 *
	 * @when after_wp_load
	 */
	public function version(): void {
		\WP_CLI::line( sprintf( 'Super Page Cache %s', get_option( 'swcfpc_version', false ) ) );
	}

	/**
	 * Show the current plugin status.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format: table, json, or yaml.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param list<string>         $_args     Positional arguments.
	 * @param array<string, mixed> $assoc_args Named arguments.
	 *
	 * @when after_wp_load
	 */
	public function status( array $_args, array $assoc_args ): void {
		$settings       = Settings_Store::get_instance();
		$cloudflare     = new Cloudflare_Integration();
		$format         = $assoc_args['format'] ?? 'table';
		$preloader_lock = (int) get_option( 'swcfpc_preloader_lock', 0 );
		$lock_age       = $preloader_lock > 0 ? time() - $preloader_lock : 0;
		$zone_list      = $settings->get( Constants::ZONE_ID_LIST );
		$override_count = count(
			array_filter(
				$settings->get_all_with_source( true ),
				static function ( $setting ) {
					return Settings_Store::CONFIG_SOURCE_CONST === $setting['source'];
				}
			)
		);

		$status = [
			'plugin_version'                        => SWCFPC_VERSION,
			'cloudflare_connected'                  => $settings->is_cloudflare_connected(),
			'cloudflare_api_enabled'                => $cloudflare->is_enabled(),
			'cloudflare_auth_mode'                  => $this->get_auth_mode_label( (int) $settings->get( Constants::SETTING_AUTH_MODE ) ),
			'cloudflare_zone_selected'              => '' !== (string) $settings->get_cloudflare_zone_id(),
			'cloudflare_zone_candidates'            => is_array( $zone_list ) ? count( $zone_list ) : 0,
			'cache_rule_configured'                 => $cloudflare->has_cache_rule(),
			'page_cache_enabled'                    => $settings->is_cache_enabled(),
			'fallback_cache_enabled'                => (bool) $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE ),
			'preloader_enabled'                     => (bool) $settings->get( Constants::SETTING_ENABLE_PRELOADER ),
			'preloader_running'                     => $preloader_lock > 0 && $lock_age <= 15 * MINUTE_IN_SECONDS,
			'preloader_lock_age'                    => $preloader_lock > 0 ? $lock_age : '',
			'logs_enabled'                          => (bool) $settings->get( Constants::SETTING_LOG_ENABLED ),
			'invalid_encryption_state'              => $settings->has_invalid_encryption_state(),
			'cloudflare_credentials_need_attention' => $settings->has_unreadable_active_cloudflare_credentials(),
			'wp_config_overrides'                   => $override_count,
		];

		$this->format_assoc_output( $status, $format );
	}

	/**
	 * Run a reliable plugin health check.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format: table, json, or yaml.
	 * ---
	 * default: table
	 * ---
	 *
	 * [--run-cache-test]
	 * : Include best-effort runtime cache tests.
	 *
	 * @param list<string>         $_args     Positional arguments.
	 * @param array<string, mixed> $assoc_args Named arguments.
	 *
	 * @when after_wp_load
	 */
	public function doctor( array $_args, array $assoc_args ): void {
		$settings                      = Settings_Store::get_instance();
		$cloudflare                    = new Cloudflare_Integration();
		$format                        = $assoc_args['format'] ?? 'table';
		$run_cache_test                = \WP_CLI\Utils\get_flag_value( $assoc_args, 'run-cache-test', false );
		$zone_selected                 = '' !== (string) $settings->get_cloudflare_zone_id();
		$auth_mode                     = (int) $settings->get( Constants::SETTING_AUTH_MODE );
		$has_credentials               = (
			( SWCFPC_AUTH_MODE_API_KEY === $auth_mode && '' !== (string) $settings->get( Constants::SETTING_CF_EMAIL ) && '' !== (string) $settings->get( Constants::SETTING_CF_API_KEY ) ) ||
			( SWCFPC_AUTH_MODE_API_TOKEN === $auth_mode && '' !== (string) $settings->get( Constants::SETTING_CF_API_TOKEN ) )
		);
		$actionable_invalid_encryption = $settings->has_unreadable_active_cloudflare_credentials();

		$checks = [
			[
				'check'   => 'encryption_state',
				'status'  => $actionable_invalid_encryption ? 'fail' : 'pass',
				'type'    => 'config',
				'details' => $actionable_invalid_encryption
					? 'The active Cloudflare credential is no longer readable.'
					: 'The active Cloudflare credential is readable.',
			],
			[
				'check'   => 'cloudflare_credentials',
				'status'  => $has_credentials ? 'pass' : 'warn',
				'type'    => 'config',
				'details' => $has_credentials ? 'Cloudflare credentials are configured.' : 'Cloudflare credentials are not fully configured.',
			],
			[
				'check'   => 'cloudflare_zone',
				'status'  => $zone_selected ? 'pass' : 'warn',
				'type'    => 'config',
				'details' => $zone_selected ? 'A Cloudflare zone is selected.' : 'No Cloudflare zone is selected.',
			],
			[
				'check'   => 'cache_rule',
				'status'  => $cloudflare->has_cache_rule() ? 'pass' : 'warn',
				'type'    => 'config',
				'details' => $cloudflare->has_cache_rule() ? 'A Cloudflare cache rule is configured.' : 'No Cloudflare cache rule is configured.',
			],
			[
				'check'   => 'page_cache',
				'status'  => $settings->is_cache_enabled() ? 'pass' : 'warn',
				'type'    => 'config',
				'details' => $settings->is_cache_enabled() ? 'Page cache is enabled.' : 'Page cache is disabled.',
			],
			[
				'check'   => 'logging',
				'status'  => $settings->get( Constants::SETTING_LOG_ENABLED ) ? 'pass' : 'info',
				'type'    => 'config',
				'details' => $settings->get( Constants::SETTING_LOG_ENABLED ) ? 'Logging is enabled.' : 'Logging is disabled.',
			],
			[
				'check'   => 'preloader',
				'status'  => $settings->get( Constants::SETTING_ENABLE_PRELOADER ) ? 'pass' : 'info',
				'type'    => 'config',
				'details' => $settings->get( Constants::SETTING_ENABLE_PRELOADER ) ? 'Preloader is enabled.' : 'Preloader is disabled.',
			],
		];

		if ( $run_cache_test ) {
			$test_results = ( new Cache_Tester() )->cli_test();

			$checks[] = [
				'check'   => 'cache_test_overall',
				'status'  => 'success' === $test_results['overall_status'] ? 'pass' : 'warn',
				'type'    => 'best_effort',
				'details' => $test_results['message'] ?? 'Runtime cache test completed.',
			];
		}

		$this->format_items_output( $checks, [ 'check', 'status', 'type', 'details' ], $format );

		foreach ( $checks as $check ) {
			if ( 'fail' === $check['status'] ) {
				\WP_CLI::halt( 4 );
			}
		}
	}

	/**
	 * Purge whole caches
	 *
	 * @when after_wp_load
	 */
	public function purge_cache(): void {
		if ( Cache_Controller::purge_all() ) {
			\WP_CLI::success( __( 'Cache purged successfully.', 'wp-cloudflare-page-cache' ) );
		} else {
			\WP_CLI::error( __( 'An error occurred while purging the cache', 'wp-cloudflare-page-cache' ) );
		}
	}

	/**
	 * Purge the whole Cloudflare cache only
	 *
	 * @when after_wp_load
	 */
	public function purge_cf_cache(): void {
		$error = '';

		if ( ! ( new Cloudflare_Integration() )->purge_cache( $error ) ) {
			\WP_CLI::error( $error );
		}

		\WP_CLI::success( __( 'Cache purged successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Purge the whole Varnish cache only
	 *
	 * @when after_wp_load
	 */
	public function purge_varnish_cache(): void {
		$error = '';

		$varnish_handler = new Varnish();

		if ( ! $varnish_handler->purge_whole_cache( $error ) ) {
			\WP_CLI::error( $error );
		}

		\WP_CLI::success( __( 'Cache purged successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Purge the whole OPcache cache only
	 *
	 * @when after_wp_load
	 */
	public function purge_opcache_cache(): void {
		Cache_Controller::purge_opcache();

		\WP_CLI::success( __( 'Cache purged successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Purge the whole Fallback cache only
	 *
	 * @when after_wp_load
	 */
	public function purge_fallback_cache(): void {
		global $sw_cloudflare_pagecache;

		$sw_cloudflare_pagecache->get_core_loader()->fallback_cache()->fallback_cache_purge_all();

		\WP_CLI::success( __( 'Cache purged successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Enable Cloudflare page cache
	 *
	 * @when after_wp_load
	 */
	public function enable_cf_cache(): void {
		$error = '';

		if ( ! ( new Cloudflare_Integration() )->enable_page_cache( $error ) ) {
			\WP_CLI::error( $error );
		}

		\WP_CLI::success( __( 'Cache enabled successfully', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Disable Cloudflare page cache
	 *
	 * @when after_wp_load
	 */
	public function disable_cf_cache(): void {
		$error = '';

		if ( ! ( new Cloudflare_Integration() )->disable_page_cache( $error ) ) {
			\WP_CLI::error( $error );
		}

		\WP_CLI::success( __( 'Cache disabled successfully', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Test Cloudflare page cache
	 *
	 * @when after_wp_load
	 */
	public function test_cf_cache(): void {
		$error_dynamic = '';
		$error_static  = '';

		$url_static_resource  = SWCFPC_PLUGIN_URL . 'assets/testcache.html';
		$url_dynamic_resource = home_url();

		$return_array['static_resource_url']  = $url_static_resource;
		$return_array['dynamic_resource_url'] = $url_dynamic_resource;

		$headers_dyamic_resource = $this->page_cache_test( $url_dynamic_resource, $error_dynamic );

		if ( ! $headers_dyamic_resource ) {

			$headers_static_resource = $this->page_cache_test( $url_static_resource, $error_static, true );
			$error                   = '';

			// Error on both dynamic and static test
			if ( ! $headers_static_resource ) {

				$error .= __( 'Page caching does not appear to be working for either dynamic or static pages.', 'wp-cloudflare-page-cache' );
				$error .= '<br/><br/>';
				// translators: %1$s is the dynamic resource URL, %2$s is the error message.
				$error .= sprintf( __( 'Error on dynamic page (%1$s): %2$s', 'wp-cloudflare-page-cache' ), $url_dynamic_resource, $error_dynamic );
				$error .= '<br/><br/>';
				// translators: %1$s is the static resource URL, %2$s is the error message.
				$error .= sprintf( __( 'Error on static resource (%1$s): %2$s', 'wp-cloudflare-page-cache' ), $url_static_resource, $error_static );

			} else {
				// Error on dynamic test only
				// translators: %s is the static resource URL.
				$error .= sprintf( __( 'Page caching is working for the static page (%s) but does not appear to be working for dynamic pages.', 'wp-cloudflare-page-cache' ), $url_static_resource );
				$error .= '<br/><br/>';
				// translators: %1$s is the dynamic resource URL, %2$s is the error message.
				$error .= sprintf( __( 'Error on dynamic page (%1$s): %2$s', 'wp-cloudflare-page-cache' ), $url_dynamic_resource, $error_dynamic );

			}

			\WP_CLI::error( $error );

		}

		\WP_CLI::success( __( 'Page caching is working properly', 'wp-cloudflare-page-cache' ) );
	}

	private function page_cache_test( string $url, string &$error, bool $test_static = false ): bool {
		$args = [
			'timeout'    => defined( 'SWCFPC_CURL_TIMEOUT' ) ? SWCFPC_CURL_TIMEOUT : 10,
			'sslverify'  => false,
			'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
			'headers'    => [
				'Accept' => 'text/html',
			],
		];

		Logger::log( 'cloudflare::page_cache_test', "Start test to {$url} with headers " . print_r( $args, true ) );

		$response = wp_remote_get( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			$error = __( 'Connection error: ', 'wp-cloudflare-page-cache' ) . $response->get_error_message();
			Logger::log( 'cloudflare::page_cache_test', "Error wp_remote_get: {$error}" );

			return false;
		}

		$headers = wp_remote_retrieve_headers( $response );

		Logger::log( 'cloudflare::page_cache_test', 'Response Headers: ' . var_export( $headers, true ) );

		if ( ! $test_static && ! isset( $headers['X-WP-CF-Super-Cache'] ) ) {
			$error = __( 'Plugin not detected on your home page. Disable any other caching systems and retry the test.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( ! $test_static && $headers['X-WP-CF-Super-Cache'] == 'no-cache' ) {
			$error = __( 'The cache is not enabled on your home page. It\'s not possible to verify if the page caching is working properly.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( ! isset( $headers['CF-Cache-Status'] ) ) {
			$error = __( 'It seems your website is not behind Cloudflare. If you\'ve recently enabled the cache or this is your first test, wait about 30 seconds and try again — changes take a few seconds to propagate. If the error persists, contact support for a detailed check.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( ! isset( $headers['Cache-Control'] ) ) {
			$error = __( 'Unable to find the Cache-Control response header.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( ! $test_static && ! isset( $headers['X-WP-CF-Super-Cache-Cache-Control'] ) ) {
			$error = __( 'Unable to find the X-WP-CF-Super-Cache-Cache-Control response header.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( strcasecmp( $headers['Cache-Control'], '{resp:x-wp-cf-super-cache-cache-control}' ) == 0 ) {
			$error = __( 'Invalid Cache-Control response header. If you are using LiteSpeed Server, please disable the option <strong>Overwrite the cache-control header for WordPress\'s pages using web server rules</strong>, purge the cache and retry.', 'wp-cloudflare-page-cache' );

			return false;
		}

		if ( strcasecmp( $headers['CF-Cache-Status'], 'HIT' ) == 0 || strcasecmp( $headers['CF-Cache-Status'], 'MISS' ) == 0 || strcasecmp( $headers['CF-Cache-Status'], 'EXPIRED' ) == 0 ) {
			return true;
		}

		$is_swr_enabled = $this->should_allow_swr_cache_status( $headers );

		if ( strcasecmp( $headers['CF-Cache-Status'], 'REVALIDATED' ) == 0 ) {
			if ( $is_swr_enabled ) {
				return true;
			}

			// translators: %s is the CF-Cache-Status header value.
			$error = sprintf( __( 'Cache status: %s — The cached resource is stale and was revalidated using conditional headers.', 'wp-cloudflare-page-cache' ), $headers['CF-Cache-Status'] );

			return false;
		}

		if ( strcasecmp( $headers['CF-Cache-Status'], 'UPDATING' ) == 0 ) {
			if ( $is_swr_enabled ) {
				return true;
			}

			// translators: %s is the CF-Cache-Status header value.
			$error = sprintf( __( 'Cache status: %s — The cached resource has expired and is being refreshed. This status is common for high-traffic resources.', 'wp-cloudflare-page-cache' ), $headers['CF-Cache-Status'] );

			return false;
		}

		if ( strcasecmp( $headers['CF-Cache-Status'], 'BYPASS' ) == 0 ) {
			// translators: %s is the CF-Cache-Status header value.
			$error = sprintf( __( 'Cache status: %s - Cloudflare has been instructed to not cache this asset. It has been served directly from the origin.', 'wp-cloudflare-page-cache' ), $headers['CF-Cache-Status'] );

			return false;
		}

		if ( strcasecmp( $headers['CF-Cache-Status'], 'DYNAMIC' ) == 0 ) {

			$cookies = wp_remote_retrieve_cookies( $response );

			if ( ! empty( $cookies ) && count( $cookies ) > 1 ) {
				// translators: %s is the CF-Cache-Status header value.
				$error = sprintf( __( 'Cache status: %s - The resource was not cached by default and your current Cloudflare caching configuration doesn\'t instruct Cloudflare to cache the resource. Try to enable the <strong>Strip response cookies on pages that should be cached</strong> option and retry.', 'wp-cloudflare-page-cache' ), $headers['CF-Cache-Status'] );
			} else {
				// translators: %s is the CF-Cache-Status header value.
				$error = sprintf( __( 'Cache status: %s - The resource was not cached by default and your current Cloudflare caching configuration doesn\'t instruct Cloudflare to cache the resource.  Instead, the resource was requested from the origin web server.', 'wp-cloudflare-page-cache' ), $headers['CF-Cache-Status'] );
			}

			return false;
		}

		$error = __( 'Undefined error', 'wp-cloudflare-page-cache' );

		return false;
	}

	/**
	 * Format associative data for CLI output.
	 *
	 * @param array<string, mixed> $data   Data.
	 * @param string              $format Output format.
	 *
	 * @return void
	 */
	private function format_assoc_output( array $data, string $format ): void {
		if ( in_array( $format, [ 'json', 'yaml' ], true ) ) {
			\WP_CLI::print_value( $data, [ 'format' => $format ] );
			return;
		}

		$items = [];
		foreach ( $data as $key => $value ) {
			$items[] = [
				'key'   => $key,
				'value' => $this->normalize_cli_value( $value ),
			];
		}

		\WP_CLI\Utils\format_items( 'table', $items, [ 'key', 'value' ] );
	}

	/**
	 * Format list data for CLI output.
	 *
	 * @param array<int, array<string, mixed>> $items  Items.
	 * @param list<string>                    $fields Fields.
	 * @param string                          $format Output format.
	 *
	 * @return void
	 */
	private function format_items_output( array $items, array $fields, string $format ): void {
		if ( in_array( $format, [ 'json', 'yaml' ], true ) ) {
			\WP_CLI::print_value( $items, [ 'format' => $format ] );
			return;
		}

		\WP_CLI\Utils\format_items( 'table', $items, $fields );
	}

	/**
	 * Normalize values for table output.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	private function normalize_cli_value( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( null === $value ) {
			return '';
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		$encoded = wp_json_encode( $value );

		return false !== $encoded ? $encoded : '';
	}

	/**
	 * Get the auth mode label.
	 *
	 * @param int $auth_mode Auth mode.
	 *
	 * @return string
	 */
	private function get_auth_mode_label( int $auth_mode ): string {
		return SWCFPC_AUTH_MODE_API_KEY === $auth_mode ? 'api_key' : 'api_token';
	}

	/**
	 * @param array<string, mixed>|\WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers
	 */
	private function should_allow_swr_cache_status( $headers ): bool {
		return Settings_Store::get_instance()->response_has_swr_directive( $headers );
	}
}
