<?php

namespace WebpConverter\Conversion\Endpoint;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Conversion\PathsFinder;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\StatsManager;

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

	/**
	 * @var StatsManager
	 */
	private $stats_manager;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		StatsManager $stats_manager = null
	) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->stats_manager    = $stats_manager ?: new StatsManager();
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
		$images_count = count(
			( new PathsFinder( $this->plugin_data, $this->token_repository ) )
				->get_paths( true, [ AvifFormat::FORMAT_EXTENSION, WebpFormat::FORMAT_EXTENSION ] )
		);
		$this->stats_manager->set_calculation_images_count( $images_count );

		return new \WP_REST_Response(
			[
				'value_output' => sprintf(
				/* translators: %1$s: images count */
					__( '%1$s for AVIF and %1$s for WebP', 'webp-converter-for-media' ),
					number_format( $images_count, 0, '', ' ' )
				),
			],
			200
		);
	}
}
