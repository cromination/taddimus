<?php

namespace WebpConverter\Service;

use WebpConverter\PluginData;
use WebpConverter\Settings\Option\CloudflareApiTokenOption;
use WebpConverter\Settings\Option\CloudflareZoneIdOption;

/**
 * Manages the cache configuration for Cloudflare CDN.
 */
class CloudflareConfigurator {

	const API_CACHE_CONFIG_URL        = 'https://api.cloudflare.com/client/v4/zones/%s/cache/variants';
	const API_CACHE_PURGE_URL         = 'https://api.cloudflare.com/client/v4/zones/%s/purge_cache';
	const REQUEST_CACHE_CONFIG_OPTION = 'webpc_cloudflare_cache_config';
	const REQUEST_CACHE_PURGE_OPTION  = 'webpc_cloudflare_cache_purge';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	public function set_cache_config(): bool {
		$response_code = $this->send_request(
			self::API_CACHE_CONFIG_URL,
			'PATCH',
			[
				'value' => [
					'jpeg' => [ 'image/jpeg', 'image/webp', 'image/avif' ],
					'jpg'  => [ 'image/jpeg', 'image/webp', 'image/avif' ],
					'png'  => [ 'image/png', 'image/webp', 'image/avif' ],
					'gif'  => [ 'image/gif', 'image/webp', 'image/avif' ],
					'webp' => [ 'image/webp', 'image/avif' ],
				],
			]
		);
		if ( $response_code === null ) {
			return false;
		}

		OptionsAccessManager::update_option(
			self::REQUEST_CACHE_CONFIG_OPTION,
			( ( $response_code === 200 ) ? 'yes' : $response_code )
		);

		return ( $response_code === 200 );
	}

	public function purge_cache(): bool {
		$response_code = $this->send_request(
			self::API_CACHE_PURGE_URL,
			'POST',
			[
				'purge_everything' => true,
			]
		);
		if ( $response_code === null ) {
			return false;
		}

		OptionsAccessManager::update_option(
			self::REQUEST_CACHE_PURGE_OPTION,
			( ( $response_code === 200 ) ? 'yes' : $response_code )
		);

		return ( $response_code === 200 );
	}

	/**
	 * @param string  $api_url        .
	 * @param string  $request_method .
	 * @param mixed[] $request_data   .
	 *
	 * @return int|null
	 */
	private function send_request( string $api_url, string $request_method, array $request_data ) {
		$plugin_setting = $this->plugin_data->get_plugin_settings();
		if ( ! $plugin_setting[ CloudflareZoneIdOption::OPTION_NAME ] || ! $plugin_setting[ CloudflareApiTokenOption::OPTION_NAME ] ) {
			return null;
		}

		$connect = curl_init( sprintf( $api_url, $plugin_setting[ CloudflareZoneIdOption::OPTION_NAME ] ) );
		if ( ! $connect ) {
			return null;
		}

		curl_setopt( $connect, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $connect, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $connect, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $connect, CURLOPT_CUSTOMREQUEST, $request_method ?: 'POST' );
		curl_setopt( $connect, CURLOPT_POSTFIELDS, json_encode( $request_data ) ?: '' );
		curl_setopt(
			$connect,
			CURLOPT_HTTPHEADER,
			[
				'Content-Type: application/json',
				'Authorization: Bearer ' . $plugin_setting[ CloudflareApiTokenOption::OPTION_NAME ],
			]
		);

		curl_exec( $connect );
		$request_info = curl_getinfo( $connect );
		curl_close( $connect );

		return $request_info['http_code'];
	}
}
