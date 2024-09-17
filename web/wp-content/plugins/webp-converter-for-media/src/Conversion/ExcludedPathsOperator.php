<?php

namespace WebpConverter\Conversion;

use WebpConverter\Conversion\Directory\UploadsWebpcDirectory;
use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\ExcludedDirectoriesOption;

/**
 * Removes from list of source directory paths those that are excluded.
 */
class ExcludedPathsOperator implements HookableInterface {

	const EXCLUDED_SUB_EXTENSIONS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'bk',
		'bak',
	];

	/**
	 * @var string[]
	 */
	private $excluded_dirs = [
		'.',
		'..',
		'.git',
		'.svn',
		'node_modules',
		'wpmc-trash',
		'__MACOSX',
		UploadsWebpcDirectory::DIRECTORY_NAME,
		'ShortpixelBackups',
		'backup',
		'wio_backup',
	];

	/**
	 * @var string[]
	 */
	private $excluded_paths = [];

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'load_excluded_directories_from_plugin_settings' ] );
		add_filter( 'webpc_supported_source_directory', [ $this, 'skip_excluded_directory' ], 0, 3 );
	}

	/**
	 * @return void
	 * @internal
	 */
	public function load_excluded_directories_from_plugin_settings() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		$saved_dirs      = ( $plugin_settings[ ExcludedDirectoriesOption::OPTION_NAME ] !== '' )
			? explode( ',', $plugin_settings[ ExcludedDirectoriesOption::OPTION_NAME ] )
			: [];

		foreach ( $saved_dirs as $saved_dir ) {
			if ( preg_match( '/(\/|\\\)/', $saved_dir ) ) {
				$this->excluded_paths[] = '/' . str_replace( '\\', '/', $saved_dir ) . '/';
			} else {
				$this->excluded_dirs[] = $saved_dir;
			}
		}
	}

	/**
	 * Returns the status if the given directory path should be converted.
	 *
	 * @param bool   $path_status .
	 * @param string $dirname     .
	 * @param string $server_path .
	 *
	 * @return bool Status if the given path is not excluded.
	 * @internal
	 */
	public function skip_excluded_directory( bool $path_status, string $dirname, string $server_path ): bool {
		if ( in_array( $dirname, $this->excluded_dirs ) ) {
			return false;
		}

		$valid_server_path = str_replace( '\\', '/', $server_path ) . '/';
		foreach ( $this->excluded_paths as $excluded_path ) {
			if ( strpos( $valid_server_path, $excluded_path ) !== false ) {
				return false;
			}
		}

		return $path_status;
	}
}
