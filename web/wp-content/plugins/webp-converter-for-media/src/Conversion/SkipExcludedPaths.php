<?php

namespace WebpConverter\Conversion;

use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\ExcludedDirectoriesOption;

/**
 * Removes from list of source directory paths those that are excluded.
 */
class SkipExcludedPaths implements HookableInterface {

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
	];

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
		$plugin_settings     = $this->plugin_data->get_plugin_settings();
		$saved_dirs          = $plugin_settings[ ExcludedDirectoriesOption::OPTION_NAME ];
		$this->excluded_dirs = array_merge(
			$this->excluded_dirs,
			( $saved_dirs !== '' ) ? explode( ',', $saved_dirs ) : []
		);

		add_filter( 'webpc_supported_source_directory', [ $this, 'skip_excluded_directory' ], 0, 3 );
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

		return $path_status;
	}
}
