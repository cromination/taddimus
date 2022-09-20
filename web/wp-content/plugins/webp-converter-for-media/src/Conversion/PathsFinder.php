<?php

namespace WebpConverter\Conversion;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\WebpFormat;
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
	const PATHS_PER_REQUEST_REMOTE_SMALL  = 3;
	const PATHS_PER_REQUEST_REMOTE_MEDIUM = 5;
	const PATHS_PER_REQUEST_REMOTE_LARGE  = 10;

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

	/**
	 * @var OutputPath
	 */
	private $output_path;

	public function __construct(
		PluginData $plugin_data,
		TokenRepository $token_repository,
		StatsManager $stats_manager = null,
		OutputPath $output_path = null
	) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->stats_manager    = $stats_manager ?: new StatsManager();
		$this->output_path      = $output_path ?: new OutputPath();
	}

	/**
	 * Returns list of chunked server paths of source images to be converted.
	 *
	 * @param bool          $skip_converted         Skip converted images?
	 * @param string[]|null $allowed_output_formats List of extensions or use selected in plugin settings.
	 *
	 * @return string[][] Array of arrays with server paths.
	 */
	public function get_paths_by_chunks( bool $skip_converted = false, array $allowed_output_formats = null ): array {
		$allowed_output_formats = $allowed_output_formats
			?: $this->plugin_data->get_plugin_settings()[ OutputFormatsOption::OPTION_NAME ];

		$paths       = $this->get_paths( $skip_converted, $allowed_output_formats );
		$paths_count = count( $paths );

		$this->stats_manager->set_regeneration_images( $paths_count );

		return array_chunk( $paths, $this->get_paths_chunk_size( $paths_count ) );
	}

	/**
	 * @param bool          $skip_converted         Skip converted images?
	 * @param string[]|null $allowed_output_formats List of extensions or use selected in plugin settings.
	 *
	 * @return string[] Server paths of source images to be converted.
	 */
	public function get_paths( bool $skip_converted = false, array $allowed_output_formats = null ): array {
		$allowed_output_formats = $allowed_output_formats
			?: $this->plugin_data->get_plugin_settings()[ OutputFormatsOption::OPTION_NAME ];

		$paths = $this->find_source_paths( true );
		if ( ! $skip_converted ) {
			return $paths;
		}

		return $this->skip_converted_paths( $paths, $allowed_output_formats );
	}

	/**
	 * @param string[] $allowed_output_formats List of extensions.
	 *
	 * @return int[] Number of images to be converted to given output formats.
	 */
	public function get_paths_count( array $allowed_output_formats ): array {
		$source_paths = $this->find_source_paths( true );
		$values       = [];
		foreach ( $allowed_output_formats as $output_format ) {
			$values[ $output_format ]          = 0;
			$values[ 'all_' . $output_format ] = 0;
			foreach ( $source_paths as $source_path ) {
				$output_path = $this->output_path->get_path( $source_path, false, $output_format );
				if ( $output_path === null ) {
					continue;
				}

				$values[ 'all_' . $output_format ]++;
				if ( ! $this->is_converted_file( $source_path, $output_path ) ) {
					$values[ $output_format ]++;
				}
			}
		}

		$this->stats_manager->set_images_webp_all( $values[ 'all_' . WebpFormat::FORMAT_EXTENSION ] ?? 0 );
		$this->stats_manager->set_images_webp_unconverted( $values[ WebpFormat::FORMAT_EXTENSION ] ?? 0 );
		$this->stats_manager->set_images_avif_all( $values[ 'all_' . AvifFormat::FORMAT_EXTENSION ] ?? 0 );
		$this->stats_manager->set_images_avif_unconverted( $values[ AvifFormat::FORMAT_EXTENSION ] ?? 0 );

		return $values;
	}

	/**
	 * @param string[]      $source_paths           Server paths of source images.
	 * @param string[]|null $allowed_output_formats List of extensions or use selected in plugin settings.
	 * @param bool          $force_convert_modified Force re-conversion of images modified after previous conversion.
	 *
	 * @return string[] Server paths of source images.
	 */
	public function skip_converted_paths( array $source_paths, array $allowed_output_formats = null, bool $force_convert_modified = false ): array {
		$allowed_output_formats = $allowed_output_formats
			?: $this->plugin_data->get_plugin_settings()[ OutputFormatsOption::OPTION_NAME ];

		foreach ( $source_paths as $path_index => $source_path ) {
			$is_converted = true;
			foreach ( $allowed_output_formats as $output_format ) {
				$output_path = $this->output_path->get_path( $source_path, false, $output_format );

				if ( $output_path && ! $this->is_converted_file( $source_path, $output_path, $force_convert_modified ) ) {
					$is_converted = false;
					break;
				}
			}
			if ( $is_converted ) {
				unset( $source_paths[ $path_index ] );
			}
		}

		return $source_paths;
	}

	/**
	 * Returns list of server paths of source images to be converted.
	 *
	 * @param bool $skip_converted Skip converted images?
	 *
	 * @return string[] Server paths of source images.
	 */
	private function find_source_paths( bool $skip_converted = false ): array {
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
			$paths = apply_filters( 'webpc_dir_files', [], $dir_path, $skip_converted );
			$list  = array_merge( $list, $paths );
		}

		rsort( $list );
		return $list;
	}

	private function is_converted_file( string $source_path, string $output_path, bool $force_convert_modified = false ): bool {
		if ( file_exists( $output_path ) ) {
			return ( $force_convert_modified ) ? ( filemtime( $source_path ) <= filemtime( $output_path ) ) : true;
		}

		return ( file_exists( $output_path . '.' . SkipLarger::DELETED_FILE_EXTENSION )
			|| file_exists( $output_path . '.' . SkipCrashed::CRASHED_FILE_EXTENSION ) );
	}

	/**
	 * @param int $paths_count .
	 *
	 * @return int<1, max>
	 */
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
		} elseif ( $images_to_conversion <= 120000 ) {
			return self::PATHS_PER_REQUEST_REMOTE_MEDIUM;
		} else {
			return self::PATHS_PER_REQUEST_REMOTE_LARGE;
		}
	}
}
