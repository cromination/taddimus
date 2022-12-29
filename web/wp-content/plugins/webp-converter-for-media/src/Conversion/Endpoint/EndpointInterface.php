<?php

namespace WebpConverter\Conversion\Endpoint;

/**
 * Interface for class that supports endpoint.
 */
interface EndpointInterface {

	/**
	 * Returns route of endpoint.
	 *
	 * @return string
	 */
	public static function get_route_name(): string;

	/**
	 * Returns methods separated by space.
	 *
	 * @return string
	 */
	public function get_http_methods(): string;

	/**
	 * Returns whether request can be executed.
	 *
	 * @param string $request_nonce .
	 *
	 * @return bool
	 */
	public function is_valid_request( string $request_nonce ): bool;

	/**
	 * Returns list of params for endpoint.
	 *
	 * @return mixed[]
	 */
	public function get_route_args(): array;

	/**
	 * Returns URL of endpoint.
	 *
	 * @return string
	 */
	public static function get_route_url(): string;

	/**
	 * Returns authorization code of endpoint.
	 *
	 * @return string
	 */
	public static function get_route_nonce(): string;

	/**
	 * Returns header name with nonce value.
	 *
	 * @return string
	 */
	public function get_route_nonce_header(): string;

	/**
	 * Returns response to endpoint.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 * @internal
	 */
	public function get_route_response( \WP_REST_Request $request );
}
