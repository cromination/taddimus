<?php

namespace WebpConverter\Plugin;

use WebpConverter\HookableInterface;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Plugin\Deactivation\CronReset;
use WebpConverter\Plugin\Deactivation\PluginSettings;
use WebpConverter\PluginInfo;

/**
 * Runs actions after plugin deactivation.
 */
class Deactivation implements HookableInterface {

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
		register_deactivation_hook( $this->plugin_info->get_plugin_file(), [ $this, 'load_deactivation_actions' ] );
	}

	/**
	 * Initializes actions when plugin is deactivated.
	 *
	 * @return void
	 * @internal
	 */
	public function load_deactivation_actions() {
		( new CronReset() )->reset_cron_event();
		( new PluginSettings() )->remove_plugin_settings();

		do_action( LoaderAbstract::ACTION_NAME, false );
	}
}
