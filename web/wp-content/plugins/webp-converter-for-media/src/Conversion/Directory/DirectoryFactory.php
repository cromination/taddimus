<?php

namespace WebpConverter\Conversion\Directory;

use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\HookableInterface;
use WebpConverter\Plugin\Uninstall\OutputFilesRemover;
use WebpConverter\Settings\Option\OutputFormatsOption;
use WebpConverter\Settings\Option\SupportedDirectoriesOption;

/**
 * Initializes integration for all directories.
 */
class DirectoryFactory implements HookableInterface {

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	/**
	 * Object of directories integration.
	 *
	 * @var DirectoryIntegrator
	 */
	private $directories_integration;

	public function __construct( FormatFactory $format_factory ) {
		$this->format_factory = $format_factory;

		$this->set_integration( new SourceDirectory( 'cache' ) );
		$this->set_integration( new SourceDirectory( 'gallery' ) );
		$this->set_integration( new SourceDirectory( 'plugins' ) );
		$this->set_integration( new SourceDirectory( 'themes' ) );
		$this->set_integration( new UploadsDirectory() );
		$this->set_integration( new UploadsWebpcDirectory() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'init_hooks_after_setup' ], 0 );
		add_action( 'webpc_settings_updated', [ $this, 'remove_unused_output_directories' ], 10, 2 );
		add_action( 'webpc_settings_updated', [ $this, 'remove_unused_output_format' ], 10, 2 );
		$this->directories_integration->init_hooks();
	}

	/**
	 * Loads hooks before other init_hooks_after_setup() functions.
	 *
	 * @return void
	 * @internal
	 */
	public function init_hooks_after_setup() {
		foreach ( apply_filters( 'webpc_source_directories', [] ) as $directory_name ) {
			$this->set_integration( new SourceDirectory( $directory_name ) );
		}
	}

	/**
	 * Sets integration for directory.
	 *
	 * @param DirectoryInterface $directory .
	 *
	 * @return void
	 */
	private function set_integration( DirectoryInterface $directory ) {
		if ( $this->directories_integration === null ) {
			$this->directories_integration = new DirectoryIntegrator( $this->format_factory );
		}
		$this->directories_integration->add_directory( $directory );
	}

	/**
	 * Returns list of source directories.
	 *
	 * @return string[] Types of directories with labels.
	 */
	public function get_directories(): array {
		return $this->directories_integration->get_source_directories();
	}

	/**
	 * @param mixed[] $plugin_settings          .
	 * @param mixed[] $previous_plugin_settings .
	 *
	 * @return void
	 * @internal
	 */
	public function remove_unused_output_directories( array $plugin_settings, array $previous_plugin_settings ) {
		if ( $plugin_settings[ SupportedDirectoriesOption::OPTION_NAME ] === $previous_plugin_settings[ SupportedDirectoriesOption::OPTION_NAME ] ) {
			return;
		}

		$all_dirs = $this->directories_integration->get_output_directories();
		foreach ( $all_dirs as $output_dir => $output_path ) {
			if ( in_array( $output_dir, $plugin_settings[ SupportedDirectoriesOption::OPTION_NAME ] ) ) {
				continue;
			}

			$paths   = OutputFilesRemover::get_paths_from_location( $output_path );
			$paths[] = $output_path;
			OutputFilesRemover::remove_files( $paths );
		}
	}

	/**
	 * @param mixed[] $plugin_settings          .
	 * @param mixed[] $previous_plugin_settings .
	 *
	 * @return void
	 * @internal
	 */
	public function remove_unused_output_format( array $plugin_settings, array $previous_plugin_settings ) {
		if ( ( $plugin_settings[ OutputFormatsOption::OPTION_NAME ] === $previous_plugin_settings[ OutputFormatsOption::OPTION_NAME ] )
			|| in_array( WebpFormat::FORMAT_EXTENSION, $plugin_settings[ OutputFormatsOption::OPTION_NAME ] ) ) {
			return;
		}

		$path  = apply_filters( 'webpc_dir_path', '', 'webp' );
		$paths = OutputFilesRemover::get_paths_from_location( $path );
		OutputFilesRemover::remove_files( $paths, [ WebpFormat::FORMAT_EXTENSION ] );
	}
}
