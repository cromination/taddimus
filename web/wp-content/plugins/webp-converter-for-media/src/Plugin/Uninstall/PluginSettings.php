<?php

namespace WebpConverter\Plugin\Uninstall;

use WebpConverter\Conversion\Cron\CronStatusManager;
use WebpConverter\Error\ErrorDetectorAggregator;
use WebpConverter\Notice\BlackFridayNotice;
use WebpConverter\Notice\CloudflareNotice;
use WebpConverter\Notice\ThanksNotice;
use WebpConverter\Notice\TokenInactiveNotice;
use WebpConverter\Notice\UpgradeNotice;
use WebpConverter\Notice\WelcomeNotice;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\CloudflareConfigurator;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Service\StatsManager;
use WebpConverter\Service\TokenValidator;
use WebpConverter\Settings\SettingsSave;

/**
 * Removes options saved by plugin.
 */
class PluginSettings {

	/**
	 * Removes options from wp_options table.
	 *
	 * @return void
	 */
	public static function remove_plugin_settings() {
		OptionsAccessManager::delete_option( WelcomeNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( ThanksNotice::NOTICE_OLD_OPTION );
		OptionsAccessManager::delete_option( ThanksNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( CloudflareNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( TokenInactiveNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( UpgradeNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( BlackFridayNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( 'webpc_notice_avif_support' );
		OptionsAccessManager::delete_option( 'webpc_notice_cloudways' );
		OptionsAccessManager::delete_option( 'webpc_notice_litespeed' );

		OptionsAccessManager::delete_option( ErrorDetectorAggregator::ERRORS_CACHE_OPTION );
		OptionsAccessManager::delete_option( SettingsSave::SETTINGS_OPTION );
		OptionsAccessManager::delete_option( TokenRepository::TOKEN_OPTION );
		OptionsAccessManager::delete_option( TokenValidator::REQUEST_INFO_OPTION );
		OptionsAccessManager::delete_option( CloudflareConfigurator::REQUEST_CACHE_CONFIG_OPTION );
		OptionsAccessManager::delete_option( CloudflareConfigurator::REQUEST_CACHE_PURGE_OPTION );
		OptionsAccessManager::delete_option( 'webpc_latest_version' );

		OptionsAccessManager::delete_option( StatsManager::STATS_INSTALLATION_DATE_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_FIRST_VERSION_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_REGENERATION_IMAGES_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_IMAGES_WEBP_ALL_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_IMAGES_WEBP_UNCONVERTED_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_IMAGES_AVIF_ALL_OPTION );
		OptionsAccessManager::delete_option( StatsManager::STATS_IMAGES_AVIF_UNCONVERTED_OPTION );
		OptionsAccessManager::delete_option( 'webpc_stats_calculation_images' );

		delete_site_transient( CronStatusManager::CRON_PATHS_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_PATHS_SKIPPED_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_STATUS_LOCKED_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_REQUEST_ID_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_REQUEST_RESPONSE_TRANSIENT );
		delete_site_transient( ErrorDetectorAggregator::ERROR_DETECTOR_DATE_TRANSIENT );
	}
}
