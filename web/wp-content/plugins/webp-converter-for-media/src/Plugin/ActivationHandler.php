<?php

namespace WebpConverter\Plugin;

use WebpConverter\HookableInterface;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Plugin\Activation\OutputDirectoryGenerator;
use WebpConverter\Plugin\Activation\PluginSettingsManager;
use WebpConverter\PluginInfo;

/**
 * Runs actions after plugin activation.
 */
class ActivationHandler implements HookableInterface {

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
		register_activation_hook( $this->plugin_info->get_plugin_file(), [ $this, 'load_activation_actions' ] );
	}

	/**
	 * Initializes actions when plugin is activated.
	 *
	 * @return void
	 * @internal
	 */
	public function load_activation_actions() {
		( new OutputDirectoryGenerator() )->create_directory_for_uploads_webp();

		$default_settings = new PluginSettingsManager( $this->plugin_info );
		$default_settings->add_default_notices_values();
		$default_settings->add_default_stats_values();

		do_action( LoaderAbstract::ACTION_NAME, true );
	}
}
