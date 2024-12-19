<?php

namespace WebpConverter\Action;

use WebpConverter\Conversion\Method\MethodFactory;
use WebpConverter\Conversion\Method\MethodIntegrator;
use WebpConverter\HookableInterface;
use WebpConverter\PluginData;

/**
 * Initializes conversion of all images in list of paths.
 */
class ConvertPathsAction implements HookableInterface {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var MethodFactory
	 */
	private $method_factory;

	public function __construct( PluginData $plugin_data, MethodFactory $method_factory ) {
		$this->plugin_data    = $plugin_data;
		$this->method_factory = $method_factory;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'webpc_convert_paths', [ $this, 'convert_files_by_paths' ], 10, 3 );
	}

	/**
	 * Converts all given images to output formats.
	 *
	 * @param string[] $paths            Server paths of images.
	 * @param bool     $regenerate_force .
	 * @param int|null $quality_level    .
	 *
	 * @return void
	 * @internal
	 */
	public function convert_files_by_paths( array $paths, bool $regenerate_force = false, ?int $quality_level = null ) {
		( new MethodIntegrator( $this->plugin_data, $this->method_factory ) )
			->init_conversion( $this->remove_paths_from_excluded_paths( $paths ), $regenerate_force, false, $quality_level );
	}

	/**
	 * Removes paths of source images from excluded paths.
	 *
	 * @param string[] $source_paths Server paths of images.
	 *
	 * @return string[]
	 */
	private function remove_paths_from_excluded_paths( array $source_paths ): array {
		foreach ( $source_paths as $path_index => $path ) {
			if ( ! apply_filters( 'webpc_supported_source_file', true, basename( $path ), $path ) ) {
				unset( $source_paths[ $path_index ] );
			}
		}
		return $source_paths;
	}
}
