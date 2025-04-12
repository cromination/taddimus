<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\SettingsIncorrectNotice;
use WebpConverter\PluginData;
use WebpConverter\Settings\Option\ConversionMethodOption;
use WebpConverter\Settings\Option\ImagesQualityOption;
use WebpConverter\Settings\Option\LoaderTypeOption;
use WebpConverter\Settings\Option\OutputFormatsOption;
use WebpConverter\Settings\Option\SupportedDirectoriesOption;
use WebpConverter\Settings\Option\SupportedExtensionsOption;
use WebpConverter\Settings\Page\AdvancedSettingsPage;
use WebpConverter\Settings\Page\GeneralSettingsPage;

/**
 * Checks for configuration errors about incorrectly saved plugin settings.
 */
class SettingsIncorrectDetector implements DetectorInterface {

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

		if ( ! $plugin_settings[ ImagesQualityOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				ImagesQualityOption::get_label(),
				GeneralSettingsPage::get_label()
			);
		} elseif ( ! $plugin_settings[ OutputFormatsOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				OutputFormatsOption::get_label(),
				GeneralSettingsPage::get_label()
			);
		} elseif ( ! $plugin_settings[ SupportedDirectoriesOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				SupportedDirectoriesOption::get_label(),
				GeneralSettingsPage::get_label()
			);
		} elseif ( ! $plugin_settings[ SupportedExtensionsOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				SupportedExtensionsOption::get_label(),
				AdvancedSettingsPage::get_label()
			);
		} elseif ( ! $plugin_settings[ ConversionMethodOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				ConversionMethodOption::get_label(),
				AdvancedSettingsPage::get_label()
			);
		} elseif ( ! $plugin_settings[ LoaderTypeOption::OPTION_NAME ] ) {
			return new SettingsIncorrectNotice(
				LoaderTypeOption::get_label(),
				AdvancedSettingsPage::get_label()
			);
		}

		return null;
	}
}
