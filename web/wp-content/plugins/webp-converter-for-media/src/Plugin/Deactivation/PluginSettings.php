<?php

namespace WebpConverter\Plugin\Deactivation;

use WebpConverter\Conversion\Cron\CronStatusManager;
use WebpConverter\Error\ErrorDetectorAggregator;
use WebpConverter\Notice\CloudflareNotice;
use WebpConverter\Service\OptionsAccessManager;

/**
 * Removes options saved by plugin.
 */
class PluginSettings {

	/**
	 * Removes options from wp_options table.
	 *
	 * @return void
	 */
	public function remove_plugin_settings() {
		OptionsAccessManager::delete_option( 'webpc_notice_cloudways' );
		OptionsAccessManager::delete_option( CloudflareNotice::NOTICE_OPTION );
		OptionsAccessManager::delete_option( 'webpc_notice_litespeed' );

		delete_site_transient( CronStatusManager::CRON_PATHS_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_PATHS_SKIPPED_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_STATUS_LOCKED_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_REQUEST_ID_TRANSIENT );
		delete_site_transient( CronStatusManager::CRON_REQUEST_RESPONSE_TRANSIENT );
		delete_site_transient( ErrorDetectorAggregator::ERROR_DETECTOR_DATE_TRANSIENT );
	}
}
