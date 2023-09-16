<?php

namespace WebpConverter\Conversion\Endpoint;

/**
 * Abstract class for class that supports image conversion method.
 */
abstract class EndpointAbstract implements EndpointInterface {

	const ROUTE_NONCE_HEADER = 'X-WP-Nonce';

	/**
	 * {@inheritdoc}
	 */
	public function is_valid_request( string $request_nonce ): bool {
		return (bool) wp_verify_nonce( $request_nonce, 'wp_rest' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_args(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_url(): string {
		return get_rest_url(
			null,
			sprintf(
				'%1$s/%2$s',
				EndpointIntegrator::ROUTE_NAMESPACE,
				static::get_route_name()
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_nonce(): string {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_nonce_header(): string {
		return self::ROUTE_NONCE_HEADER;
	}
}
