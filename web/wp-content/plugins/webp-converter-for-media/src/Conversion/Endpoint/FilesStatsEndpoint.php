<?php

namespace WebpConverter\Conversion\Endpoint;

use WebpConverter\Conversion\FilesTreeFinder;
use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\OutputFormatsOption;

/**
 * Generated tree of files that can be optimized.
 */
class FilesStatsEndpoint extends EndpointAbstract {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	public function __construct( PluginData $plugin_data, FormatFactory $format_factory ) {
		$this->plugin_data    = $plugin_data;
		$this->format_factory = $format_factory;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_route_name(): string {
		return 'images-stats';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_http_methods(): string {
		return \WP_REST_Server::READABLE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_route_response( \WP_REST_Request $request ) {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$allowed_formats = $plugin_settings[ OutputFormatsOption::OPTION_NAME ];
		if ( ! in_array( AvifFormat::FORMAT_EXTENSION, $allowed_formats ) ) {
			$allowed_formats[] = AvifFormat::FORMAT_EXTENSION;
		}

		$stats_data = ( new FilesTreeFinder( $this->plugin_data, $this->format_factory ) )
			->get_tree( $allowed_formats );

		return new \WP_REST_Response(
			[
				'value_webp_all'       => ( ( $stats_data['files_converted'][ WebpFormat::FORMAT_EXTENSION ] ?? 0 ) + ( $stats_data['files_unconverted'][ WebpFormat::FORMAT_EXTENSION ] ?? 0 ) ),
				'value_webp_converted' => $stats_data['files_converted'][ WebpFormat::FORMAT_EXTENSION ] ?? 0,
				'value_avif_all'       => ( ( $stats_data['files_converted'][ AvifFormat::FORMAT_EXTENSION ] ?? 0 ) + ( $stats_data['files_unconverted'][ AvifFormat::FORMAT_EXTENSION ] ?? 0 ) ),
				'value_avif_converted' => $stats_data['files_converted'][ AvifFormat::FORMAT_EXTENSION ] ?? 0,
				'tree'                 => $stats_data['files_tree'],
			],
			200
		);
	}
}
