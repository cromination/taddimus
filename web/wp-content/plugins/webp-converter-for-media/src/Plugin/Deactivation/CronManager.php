<?php

namespace WebpConverter\Plugin\Deactivation;

use WebpConverter\Conversion\Cron\CronEventGenerator;

/**
 * Removes cron event that starts converting all images.
 */
class CronManager {

	/**
	 * Resets cron event to regenerate all images.
	 */
	public function reset_cron_event(): void {
		wp_clear_scheduled_hook( CronEventGenerator::CRON_PATHS_ACTION );
	}
}
