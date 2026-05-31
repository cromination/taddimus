<?php

namespace SPC\Services;

use SPC\Constants;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class Varnish {

	/**
	 * @var string
	 */
	private $hostname = 'localhost';

	/**
	 * @var int
	 */
	private $port = 6081;

	/**
	 * @var string
	 */
	private $single_purge_method = 'PURGE';

	/**
	 * @var string
	 */
	private $whole_purge_method = 'PURGE';

	/**
	 * @var string
	 */
	private $provider = '';

	public function __construct() {
		$settings = Settings_Store::get_instance();

		$this->hostname            = $settings->get( Constants::SETTING_VARNISH_HOSTNAME, 'localhost' );
		$this->port                = $settings->get( Constants::SETTING_VARNISH_PORT, 6081 );
		$this->single_purge_method = $settings->get( Constants::SETTING_VARNISH_PURGE_METHOD, 'PURGE' );
		$this->whole_purge_method  = $settings->get( Constants::SETTING_VARNISH_PURGE_ALL_METHOD, 'PURGE' );

		if ( $settings->get( Constants::SETTING_VARNISH_ON_CLOWDWAYS, 0 ) > 0 ) {
			$this->provider = 'cloudways';
		}
	}


	/**
	 * @param array<int, string> $urls
	 * @return void
	 */
	public function purge_urls( $urls ) {

		$error = '';

		if ( is_array( $urls ) && count( $urls ) > 0 ) {

			foreach ( $urls as $single_url ) {
				$this->purge_single_url_cache( $single_url, $error );
			}
		}
	}


	/**
	 * @param string $url
	 * @param string $error
	 * @param bool   $purge_all
	 * @return bool
	 */
	public function purge_single_url_cache( $url, &$error, $purge_all = false ) {

		if ( $this->hostname == null || $this->port == null ) {
			Logger::log( 'varnish::purge_single_url_cache', 'Invalid hostname or port' );
			$error = __( 'Invalid hostname or port', 'wp-cloudflare-page-cache' );
			return false;
		}

		// Varnish purge request on Cloudways
		if ( $this->provider == 'cloudways' ) {

			$this->single_purge_method = 'URLPURGE';
			$this->whole_purge_method  = 'PURGE';

		}

		$parse_url = $purge_all ? parse_url( site_url() ) : parse_url( $url );

		// Determine the schema
		$schema = 'http://';
		if ( isset( $parse_url['scheme'] ) ) {
			$schema = "{$parse_url['scheme']}://";
		}

		if ( $purge_all ) {

			if ( $this->provider == 'cloudways' ) {
				$final_url = sprintf( '%s%s%s', $schema, $this->hostname, '/.*' );
			} else {
				$final_url = sprintf( '%s%s:%d%s', $schema, $this->hostname, $this->port, '/*' );
			}
		} else {

			// Determine the path
			$path = '';
			if ( isset( $parse_url['path'] ) ) {
				$path = $parse_url['path'];
			}

			if ( $this->provider == 'cloudways' ) {
				$final_url = sprintf( '%s%s%s', $schema, $this->hostname, $path );
			} else {
				$final_url = sprintf( '%s%s:%d%s', $schema, $this->hostname, $this->port, $path );
			}

			if ( ! empty( $parse_url['query'] ) ) {
				$final_url .= "?{$parse_url['query']}";
			}
		}

		$request_args = [
			'method'    => $purge_all ? $this->whole_purge_method : $this->single_purge_method,
			'headers'   => [
				'Host'       => $parse_url['host'],
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			],
			'sslverify' => false,
		];

		Logger::log( 'varnish::purge_single_url_cache', "Send purging request to {$final_url}" );
		Logger::log( 'varnish::purge_single_url_cache', 'Request args ' . print_r( $request_args, true ), true );

		// Send purge request to Varnish
		$response = wp_remote_request( $final_url, $request_args );

		Logger::log( 'varnish::purge_single_url_cache', 'Response: ' . print_r( $response, true ), true );

		if ( is_wp_error( $response ) || (int) $response['response']['code'] !== 200 ) {

			if ( $schema === 'https://' ) {
				$schema = 'http://';
			} else {
				$schema = 'https://';
			}

			if ( is_wp_error( $response ) ) {
				Logger::log( 'varnish::purge_single_url_cache', 'Error: ' . $response->get_error_message() . " - Retry using {$schema}" );
			} else {
				Logger::log( 'varnish::purge_single_url_cache', "Response code {$response['response']['code']} - Retry using {$schema}" );
			}

			if ( $purge_all ) {

				if ( $this->provider == 'cloudways' ) {
					$final_url = sprintf( '%s%s%s', $schema, $this->hostname, '/.*' );
				} else {
					$final_url = sprintf( '%s%s:%d%s', $schema, $this->hostname, $this->port, '/*' );
				}
			} else {

				if ( $this->provider == 'cloudways' ) {
					$final_url = sprintf( '%s%s%s', $schema, $this->hostname, $path );
				} else {
					$final_url = sprintf( '%s%s:%d%s', $schema, $this->hostname, $this->port, $path );
				}

				if ( ! empty( $parse_url['query'] ) ) {
					$final_url .= "?{$parse_url['query']}";
				}
			}

			Logger::log( 'varnish::purge_single_url_cache', "Send purging request to {$final_url}" );
			Logger::log( 'varnish::purge_single_url_cache', 'Request args ' . print_r( $request_args, true ), true );

			// Send new purge request to Varnish
			$response = wp_remote_request( $final_url, $request_args );

			Logger::log( 'varnish::purge_single_url_cache', 'Response: ' . print_r( $response, true ), true );

			if ( is_wp_error( $response ) ) {
				$error = $response->get_error_message();
				return false;
			}
		}

		Logger::log( 'varnish::purge_single_url_cache', "Cache purged for URL {$url}" );

		return true;
	}


	/**
	 * @param string $error
	 * @return bool
	 */
	public function purge_whole_cache( &$error ) {

		$error = '';

		return $this->purge_single_url_cache( '', $error, true );
	}

	/**
	 * @return array<string, string>
	 */
	public function purge_varnish_cache() {
		$return_array = [ 'status' => 'ok' ];
		$error        = '';

		if ( ! $this->purge_whole_cache( $error ) ) {
			$return_array['status'] = 'error';
			$return_array['error']  = $error;
			return $return_array;
		}

		Logger::log( 'varnish::ajax_purge_whole_varnish_cache', 'Purge whole Varnish cache' );

		$return_array['success_msg'] = __( 'Varnish cache purged successfully.', 'wp-cloudflare-page-cache' );

		return $return_array;
	}
}
