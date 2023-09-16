<?php

namespace WebpConverter\Plugin\Activation;

use WebpConverter\Notice\NoticeIntegrator;
use WebpConverter\Notice\ThanksNotice;
use WebpConverter\Notice\UpgradeNotice;
use WebpConverter\Notice\WelcomeNotice;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\StatsManager;
use WebpConverter\Settings\SettingsManager;

/**
 * Adds default options for plugin settings.
 */
class PluginSettingsManager {

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

	/**
	 * @var StatsManager
	 */
	private $stats_manager;

	public function __construct(
		PluginInfo $plugin_info,
		PluginData $plugin_data,
		TokenRepository $token_repository,
		StatsManager $stats_manager = null
	) {
		$this->plugin_info      = $plugin_info;
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
		$this->stats_manager    = $stats_manager ?: new StatsManager();
	}

	/**
	 * @return void
	 */
	public function add_default_plugin_settings() {
		( new SettingsManager( $this->plugin_data, $this->token_repository ) )->save_settings( null );
	}

	/**
	 * @return void
	 */
	public function add_default_notices_values() {
		NoticeIntegrator::set_default_value( WelcomeNotice::NOTICE_OPTION, WelcomeNotice::get_default_value() );
		NoticeIntegrator::set_default_value( ThanksNotice::NOTICE_OPTION, ThanksNotice::get_default_value() );
		NoticeIntegrator::set_default_value( UpgradeNotice::NOTICE_OPTION, UpgradeNotice::get_default_value() );
	}

	/**
	 * @return void
	 */
	public function add_default_stats_values() {
		$this->stats_manager->set_plugin_installation_date();
		$this->stats_manager->set_plugin_first_version( $this->plugin_info->get_plugin_version() );
	}
}
