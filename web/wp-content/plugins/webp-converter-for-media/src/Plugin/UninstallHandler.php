<?php

namespace WebpConverter\Plugin;

use WebpConverter\HookableInterface;
use WebpConverter\Plugin\Uninstall\DebugFilesRemover;
use WebpConverter\Plugin\Uninstall\OutputFilesRemover;
use WebpConverter\Plugin\Uninstall\PluginSettingsManager;
use WebpConverter\PluginInfo;

/**
 * Runs actions before plugin uninstallation.
 */
class UninstallHandler implements HookableInterface {

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	public function __construct( PluginInfo $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		register_uninstall_hook( $this->plugin_info->get_plugin_file(), [ self::class, 'load_uninstall_actions' ] );
	}

	/**
	 * Initializes actions when plugin is uninstalled.
	 *
	 * @return void
	 * @internal
	 */
	public static function load_uninstall_actions() {
		PluginSettingsManager::remove_plugin_settings();
		OutputFilesRemover::remove_webp_files();
		DebugFilesRemover::remove_debug_files();
	}
}
