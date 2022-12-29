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
						return false;
					}

					return $this->endpoint_object->is_valid_request( $header_value );
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
		if ( ! defined( 'WEBPC_DOING_CONVERSION' ) ) {
			define( 'WEBPC_DOING_CONVERSION', true );
		}

		if ( ! defined( 'WP_ADMIN' ) ) {
			/* Disable URLs replacement by Hide My WP (wpWave) plugin */
			define( 'WP_ADMIN', true );
		}
		/* Disable URLs replacement by Hide My WP (WPPlugins) plugin */
		add_filter( 'hmwp_start_buffer', '__return_false' );

		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'Converter for Media' );

		return $this->endpoint_object->get_route_response( $request );
	}
}
