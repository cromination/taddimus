<?php

namespace WebpConverter\Conversion\Method;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\PluginData;
use WebpConverter\Service\StatsManager;
use WebpConverter\Settings\Option\ConversionMethodOption;
use WebpConverter\Settings\Option\ImagesQualityOption;
use WebpConverter\Settings\Option\OutputFormatsOption;

/**
 * Initializes image conversion using active image conversion method.
 */
class MethodIntegrator {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var MethodFactory
	 */
	private $method_factory;

	/**
	 * @var StatsManager
	 */
	private $stats_manager;

	public function __construct( PluginData $plugin_data, MethodFactory $method_factory, StatsManager $stats_manager = null ) {
		$this->plugin_data    = $plugin_data;
		$this->method_factory = $method_factory;
		$this->stats_manager  = $stats_manager ?: new StatsManager();
	}

	/**
	 * Initializes converting source images using active and set conversion method.
	 *
	 * @param string[] $paths              Server paths for source images.
	 * @param bool     $regenerate_force   .
	 * @param bool     $skip_server_errors .
	 * @param int      $quality_level      .
	 *
	 * @return mixed[]|null Results data of conversion.
	 */
	public function init_conversion( array $paths, bool $regenerate_force, bool $skip_server_errors = false, int $quality_level = null ) {
		if ( ! $skip_server_errors && apply_filters( 'webpc_server_errors', [], true ) ) {
			return null;
		}

		$method = $this->get_method_used();
		if ( $method === null ) {
			return null;
		}

		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( $quality_level !== null ) {
			$plugin_settings[ ImagesQualityOption::OPTION_NAME ] = $quality_level;
		}

		$this->stats_manager->set_images_webp_unconverted();
		$this->stats_manager->set_images_avif_unconverted();

		$method->convert_paths( $paths, $plugin_settings, $regenerate_force );
		return [
			'is_fatal_error' => $method->is_fatal_error(),
			'errors'         => apply_filters( 'webpc_convert_errors', $method->get_errors() ),
			'files'          => [
				'webp_available' => $method->get_files_available( WebpFormat::FORMAT_EXTENSION ),
				'webp_converted' => $method->get_files_converted( WebpFormat::FORMAT_EXTENSION ),
				'avif_available' => $method->get_files_available( AvifFormat::FORMAT_EXTENSION ),
				'avif_converted' => $method->get_files_converted( AvifFormat::FORMAT_EXTENSION ),
			],
			'size'           => [
				'before' => $method->get_size_before(),
				'after'  => $method->get_size_after(),
			],
		];
	}

	/**
	 * Returns active and set conversion method.
	 *
	 * @return MethodInterface|null Object of conversion method.
	 */
	public function get_method_used() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$output_formats  = $plugin_settings[ OutputFormatsOption::OPTION_NAME ] ?? null;
		if ( ! $output_formats ) {
			return null;
		}

		$method_key = $plugin_settings[ ConversionMethodOption::OPTION_NAME ] ?? null;
		$methods    = $this->method_factory->get_methods_objects();
		foreach ( $methods as $method_name => $method ) {
			if ( $method_key === $method_name ) {
				return $method;
			}
		}
		return null;
	}
}
