<?php

namespace WebpConverter\Conversion;

use WebpConverter\Conversion\Method\RemoteMethod;
use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\StatsManager;
use WebpConverter\Settings\Option\ConversionMethodOption;
use WebpConverter\Settings\Option\OutputFormatsOption;
use WebpConverter\Settings\Option\SupportedDirectoriesOption;

/**
 * Finds paths of images to be converted.
 */
class PathsFinder {

	const PATHS_PER_REQUEST_LOCAL         = 10;
	const PATHS_PER_REQUEST_REMOTE_SMALL  = 1;
	const PATHS_PER_REQUEST_REMOTE_MEDIUM = 2;
	const PATHS_PER_REQUEST_REMOTE_LARGE  = 3;
	const PATHS_PER_REQUEST_REMOTE_MAX    = 5;

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
	 * Returns list of chunked server paths of source images to be converted.
	 *
	 * @param bool $skip_converted Skip converted images?
	 *
	 * @return string[][] Array of arrays with server paths.
	 */
	public function get_paths_by_chunks( bool $skip_converted = false ): array {
		$paths       = $this->get_paths( $skip_converted );
		$paths_count = count( $paths );

		$this->stats_manager->set_regeneration_images_count( $paths_count );
		return array_chunk( $paths, $this->get_paths_chunk_size( $paths_count ) );
	}

	/**
	 * Returns list of server paths of source images to be converted.
	 *
	 * @param bool          $skip_converted         Skip converted images?
	 * @param string[]|null $allowed_output_formats List of extensions or use selected in plugin settings.
	 *
	 * @return string[] Server paths of source images.
	 */
	public function get_paths( bool $skip_converted = false, array $allowed_output_formats = null ): array {
		$settings = $this->plugin_data->get_plugin_settings();
		$dirs     = array_filter(
			array_map(
				function ( $dir_name ) {
					return apply_filters( 'webpc_dir_path', '', $dir_name );
				},
				$settings[ SupportedDirectoriesOption::OPTION_NAME ]
			)
		);

		$list = [];
		foreach ( $dirs as $dir_path ) {
			$paths = apply_filters( 'webpc_dir_files', [], $dir_path, $skip_converted, $allowed_output_formats );
			$list  = array_merge( $list, $paths );
		}

		rsort( $list );
		return $list;
	}

	private function get_paths_chunk_size( int $paths_count ): int {
		$settings = $this->plugin_data->get_plugin_settings();
		if ( $settings[ ConversionMethodOption::OPTION_NAME ] !== RemoteMethod::METHOD_NAME ) {
			return self::PATHS_PER_REQUEST_LOCAL;
		}

		$output_formats       = count( $settings[ OutputFormatsOption::OPTION_NAME ] ) ?: 1;
		$images_count         = $paths_count * $output_formats;
		$images_limit         = $this->token_repository->get_token()->get_images_limit();
		$images_to_conversion = min( $images_count, $images_limit );

		if ( $images_to_conversion <= 10000 ) {
			return self::PATHS_PER_REQUEST_REMOTE_SMALL;
		} elseif ( $images_to_conversion <= 25000 ) {
			return self::PATHS_PER_REQUEST_REMOTE_MEDIUM;
		} elseif ( $images_to_conversion <= 120000 ) {
			return self::PATHS_PER_REQUEST_REMOTE_LARGE;
		} else {
			return self::PATHS_PER_REQUEST_REMOTE_MAX;
		}
	}
}
