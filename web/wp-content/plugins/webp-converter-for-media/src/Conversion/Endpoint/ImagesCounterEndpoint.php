<?php

namespace WebpConverter\Conversion\Endpoint;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\PathsFinder;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;

/**
 * Calculates the number of all images to be converted.
 */
class ImagesCounterEndpoint extends EndpointAbstract {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository
	) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_name(): string {
		return 'images-counter';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_response( \WP_REST_Request $request ) {
		$images_count = ( new PathsFinder( $this->plugin_data, $this->token_repository ) )
			->get_paths_count( [ AvifFormat::FORMAT_EXTENSION, WebpFormat::FORMAT_EXTENSION ] );

		return new \WP_REST_Response(
			[
				'value_output' => sprintf(
				/* translators: %1$s: images count */
					__( '%1$s for AVIF and %2$s for WebP', 'webp-converter-for-media' ),
					number_format( $images_count[ AvifFormat::FORMAT_EXTENSION ] ?? 0, 0, '', ' ' ),
					number_format( $images_count[ WebpFormat::FORMAT_EXTENSION ] ?? 0, 0, '', ' ' )
				),
			],
			200
		);
	}
}
