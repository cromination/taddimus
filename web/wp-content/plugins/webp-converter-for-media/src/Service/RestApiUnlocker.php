<?php

namespace WebpConverter\Service;

use WebpConverter\Conversion\Endpoint\EndpointIntegration;
use WebpConverter\HookableInterface;

/**
 * Supports exceptions for blocked REST API endpoints.
 */
class RestApiUnlocker implements HookableInterface {

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_filter( 'rest_authentication_errors', [ $this, 'clear_authentication_error' ], 9999 );
		add_filter(
			'option_mo_api_authentication_protectedrestapi_route_whitelist',
			[ $this, 'handle_wp_rest_api_authentication_plugin' ]
		);
		add_filter( 'jwt_auth_whitelist', [ $this, 'handle_jwt_auth_plugin' ] );
	}

	/**
	 * @param \WP_Error|null|true $result .
	 *
	 * @return \WP_Error|null|true
	 * @internal
	 */
	public function clear_authentication_error( $result ) {
		$current_route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] ?? '' );
		if ( strpos( $current_route, '/' . EndpointIntegration::ROUTE_NAMESPACE . '/' ) === 0 ) {
			return true;
		}

		return $result;
	}

	/**
	 * @param array|mixed $all_routes .
	 *
	 * @return array|mixed
	 * @internal
	 */
	public function handle_wp_rest_api_authentication_plugin( $all_routes ) {
		if ( ! is_array( $all_routes ) ) {
			return $all_routes;
		}

		foreach ( $all_routes as $route_key => $route_path ) {
			if ( strpos( $route_path, '/' . EndpointIntegration::ROUTE_NAMESPACE . '/' ) === 0 ) {
				unset( $all_routes[ $route_key ] );
			}
		}
		return $all_routes;
	}

	/**
	 * @param array|mixed $white_routes .
	 *
	 * @return array|mixed
	 * @internal
	 */
	public function handle_jwt_auth_plugin( $white_routes ) {
		if ( ! is_array( $white_routes ) ) {
			return $white_routes;
		}

		$all_routes[] = '/wp-json/' . EndpointIntegration::ROUTE_NAMESPACE . '/*';
		return $all_routes;
	}
}
