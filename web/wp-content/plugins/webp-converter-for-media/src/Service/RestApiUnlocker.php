<?php

namespace WebpConverter\Service;

use WebpConverter\Conversion\Endpoint\CronConversionEndpoint;
use WebpConverter\Conversion\Endpoint\EndpointIntegration;
use WebpConverter\HookableInterface;

/**
 * Supports exceptions for blocked REST API endpoints.
 */
class RestApiUnlocker implements HookableInterface {

	/**
	 * @var string[]
	 */
	private $allowed_routes = [];

	public function __construct() {
		$this->allowed_routes[] = sprintf(
			'/%1$s/%2$s',
			EndpointIntegration::ROUTE_NAMESPACE,
			CronConversionEndpoint::get_route_name()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_filter(
			'dra_allow_rest_api',
			[ $this, 'handle_disable_json_api' ]
		);
		add_filter(
			'disable_wp_rest_api_post_var',
			[ $this, 'handle_disable_wp_rest_api' ]
		);
		add_filter(
			'option_mo_api_authentication_protectedrestapi_route_whitelist',
			[ $this, 'handle_wp_rest_api_authentication' ]
		);
	}

	/**
	 * @param bool|mixed $status .
	 *
	 * @return bool|mixed
	 */
	public function handle_disable_json_api( $status ) {
		if ( ! $this->is_allowed_rest_route() ) {
			return $status;
		}

		return true;
	}

	/**
	 * @param string|mixed $get_param .
	 *
	 * @return string|mixed
	 */
	public function handle_disable_wp_rest_api( $get_param ) {
		if ( ! $this->is_allowed_rest_route() ) {
			return $get_param;
		}

		$_POST[ CronConversionEndpoint::ROUTE_NONCE_HEADER ] = true;
		return CronConversionEndpoint::ROUTE_NONCE_HEADER;
	}

	/**
	 * @param array|mixed $all_routes .
	 *
	 * @return array|mixed
	 */
	public function handle_wp_rest_api_authentication( $all_routes ) {
		if ( ! is_array( $all_routes ) ) {
			return $all_routes;
		}

		foreach ( $all_routes as $route_key => $route_path ) {
			if ( in_array( $route_path, $this->allowed_routes ) ) {
				unset( $all_routes[ $route_key ] );
			}
		}
		return $all_routes;
	}

	private function is_allowed_rest_route(): bool {
		$current_route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] ?? '' );
		return ( in_array( $current_route, $this->allowed_routes ) );
	}
}
