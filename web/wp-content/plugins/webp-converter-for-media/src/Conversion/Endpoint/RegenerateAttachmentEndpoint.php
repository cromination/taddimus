<?php

namespace WebpConverter\Conversion\Endpoint;

/**
 * Supports endpoint for converting all sub-sizes of attachment.
 */
class RegenerateAttachmentEndpoint extends EndpointAbstract {

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_name(): string {
		return 'regenerate-attachment';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_http_methods(): string {
		return \WP_REST_Server::CREATABLE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_args(): array {
		return array_merge(
			parent::get_route_args(),
			[
				'post_id'       => [
					'description'       => 'Attachment ID',
					'required'          => true,
					'validate_callback' => function ( $value ) {
						return is_int( $value );
					},
				],
				'quality_level' => [
					'description'       => 'Conversion strategy',
					'required'          => true,
					'validate_callback' => function ( $value ) {
						return in_array(
							$value,
							array_merge(
								apply_filters( 'webpc_option_quality_levels', [ 75, 80, 85, 90, 95 ] ),
								[ 0 ]
							)
						);
					},
				],
			]
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_response( \WP_REST_Request $request ) {
		$params = $request->get_params();

		return new \WP_REST_Response(
			[
				'output_html' => $this->convert_images( $params['post_id'], $params['quality_level'] ),
			],
			200
		);
	}

	/**
	 * Initializes image conversion to output formats.
	 *
	 * @param int $post_id       .
	 * @param int $quality_level .
	 *
	 * @return string|false Status of conversion.
	 */
	public function convert_images( int $post_id, int $quality_level ) {
		do_action( 'webpc_convert_attachment', $post_id, true, $quality_level );

		return apply_filters( 'webpc_attachment_stats', '', $post_id, $quality_level );
	}
}
