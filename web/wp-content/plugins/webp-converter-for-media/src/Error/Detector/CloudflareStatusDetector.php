<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\CloudflareSettingsIncorrectNotice;
use WebpConverter\PluginData;
use WebpConverter\Service\CloudflareConfigurator;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Option\CloudflareZoneIdOption;

/**
 * Validates Cloudflare configuration.
 */
class CloudflareStatusDetector implements ErrorDetector {

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
	public function get_error() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();

		if ( $plugin_settings[ CloudflareZoneIdOption::OPTION_NAME ]
			&& ( OptionsAccessManager::get_option( CloudflareConfigurator::REQUEST_CACHE_PURGE_OPTION, 'yes' ) !== 'yes' ) ) {
			return new CloudflareSettingsIncorrectNotice();
		}

		return null;
	}
}
