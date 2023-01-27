<?php

namespace WebpConverter\Service;

use WebpConverter\HookableInterface;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\ExtraFeaturesOption;

/**
 * Excludes saving converted images in the backup.
 */
class BackupExcluder implements HookableInterface {

	const OUTPUT_DIRECTORY = 'uploads-webpc';

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
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( in_array( ExtraFeaturesOption::OPTION_VALUE_BACKUP_ENABLED, $plugin_settings[ ExtraFeaturesOption::OPTION_NAME ] ) ) {
			return;
		}

		add_filter( 'ai1wm_exclude_content_from_export', [ $this, 'ai1wm_exclude_content_from_export' ], 10, 1 );
		add_filter( 'updraftplus_exclude_directory', [ $this, 'updraftplus_exclude_directory' ], 10, 2 );
		add_filter( 'backwpup_content_exclude_dirs', [ $this, 'backwpup_content_exclude_dirs' ], 10, 1 );
	}

	/**
	 * @param string[] $exclude_dirs .
	 *
	 * @return string[]
	 * @internal
	 */
	public function ai1wm_exclude_content_from_export( $exclude_dirs ) {
		$exclude_dirs[] = self::OUTPUT_DIRECTORY;
		return $exclude_dirs;
	}

	/**
	 * @param bool   $status    .
	 * @param string $directory .
	 *
	 * @return bool
	 * @internal
	 */
	public function updraftplus_exclude_directory( $status, $directory ) {
		return ( $directory === self::OUTPUT_DIRECTORY ) ? true : $status;
	}

	/**
	 * @param string[] $exclude_dirs .
	 *
	 * @return string[]
	 * @internal
	 */
	public function backwpup_content_exclude_dirs( $exclude_dirs ) {
		$exclude_dirs[] = self::OUTPUT_DIRECTORY;
		return $exclude_dirs;
	}
}
