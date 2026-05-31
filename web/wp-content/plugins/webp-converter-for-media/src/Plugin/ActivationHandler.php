<?php

namespace WebpConverter\Plugin;

use WebpConverter\HookableInterface;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Plugin\Activation\OutputDirectoryGenerator;
use WebpConverter\Plugin\Activation\PluginSettingsManager;
use WebpConverter\PluginInfo;
use WebpConverter\Service\OptionsAccessManager;

/**
 * Runs actions after plugin activation.
 */
class ActivationHandler implements HookableInterface {

	const LATEST_VERSION_OPTION = 'webpc_latest_version';

	private PluginInfo $plugin_info;

	public function __construct( PluginInfo $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks(): void {
		register_activation_hook( $this->plugin_info->get_plugin_file(), [ $this, 'load_activation_actions' ] );
		add_action( 'admin_init', [ $this, 'load_activation_actions_after_upgrade' ] );
	}

	/**
	 * Initializes actions when plugin is activated.
	 *
	 * @internal
	 */
	public function load_activation_actions(): void {
		( new OutputDirectoryGenerator() )->create_directory_for_uploads_webp();

		$default_settings = new PluginSettingsManager( $this->plugin_info );
		$default_settings->add_default_notices_values();
		$default_settings->add_default_stats_values();

		do_action( LoaderAbstract::ACTION_NAME, true );
	}

	/**
	 * @internal
	 */
	public function load_activation_actions_after_upgrade(): void {
		$saved_plugin_version = OptionsAccessManager::get_option( self::LATEST_VERSION_OPTION ) ?: '0.0.0';
		if ( $this->plugin_info->get_plugin_version() === $saved_plugin_version ) {
			return;
		}

		do_action( LoaderAbstract::ACTION_NAME, true );

		OptionsAccessManager::update_option( self::LATEST_VERSION_OPTION, $this->plugin_info->get_plugin_version() );
	}
}
