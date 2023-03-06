<?php

namespace WebpConverter\Conversion\Endpoint;

use WebpConverter\HookableInterface;

/**
 * Integrates endpoint class by registering REST API route.
 */
class EndpointIntegration implements HookableInterface {

	const ROUTE_NAMESPACE = 'webp-converter/v1';

	/**
	 * Objects of supported REST API endpoints.
	 *
	 * @var EndpointInterface
	 */
	private $endpoint_object;

	public function __construct( EndpointInterface $endpoint_object ) {
		$this->endpoint_object = $endpoint_object;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
	}

	/**
	 * Registers new endpoint in REST API.
	 *
	 * @return void
	 * @internal
	 */
	public function register_rest_route() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			$this->endpoint_object->get_route_name(),
			[
				'methods'             => $this->endpoint_object->get_http_methods(),
				'permission_callback' => function ( \WP_REST_Request $request ) {
					$header_value = $request->get_header( $this->endpoint_object->get_route_nonce_header() );
					if ( $header_value === null ) {
						return new \WP_Error(
							'webpc_rest_token_not_found',
							__( 'Sorry, you do not have permission to do that.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
							[ 'status' => rest_authorization_required_code() ]
						);
					} elseif ( ! $this->endpoint_object->is_valid_request( $header_value ) ) {
						return new \WP_Error(
							'webpc_rest_token_invalid',
							__( 'Sorry, you do not have permission to do that.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
							[ 'status' => rest_authorization_required_code() ]
						);
					}

					return true;
				},
				'callback'            => [ $this, 'get_route_response' ],
				'args'                => $this->endpoint_object->get_route_args(),
			]
		);
	}

	/**
	 * @param \WP_REST_Request $request .
	 *
	 * @return \WP_REST_Response|\WP_Error
	 * @internal
	 */
	public function get_route_response( \WP_REST_Request $request ) {
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'Converter for Media' );

		return $this->endpoint_object->get_route_response( $request );
	}
}
