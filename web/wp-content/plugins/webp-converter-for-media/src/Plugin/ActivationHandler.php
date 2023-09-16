<?php

namespace WebpConverter\Plugin;

use WebpConverter\HookableInterface;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Plugin\Activation\OutputDirectoryGenerator;
use WebpConverter\Plugin\Activation\PluginSettingsManager;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Repository\TokenRepository;

/**
 * Runs actions after plugin activation.
 */
class ActivationHandler implements HookableInterface {

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		TokenRepository $token_repository
	) {
		$this->plugin_info      = $plugin_info;
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
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

		$default_settings = new PluginSettingsManager( $this->plugin_info, $this->plugin_data, $this->token_repository );
		$default_settings->add_default_plugin_settings();
		$default_settings->add_default_notices_values();
		$default_settings->add_default_stats_values();

		do_action( LoaderAbstract::ACTION_NAME, true );
	}
}
