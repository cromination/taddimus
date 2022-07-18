<?php
/**
 * Information about plugin options displayed in server configuration widget.
 *
 * @package WebP Converter for Media
 */

use WebpConverter\Conversion\Cron\CronStatusManager;
use WebpConverter\Notice\CloudflareNotice;
use WebpConverter\Notice\CloudwaysNotice;
use WebpConverter\Notice\LitespeedNotice;
use WebpConverter\Service\OptionsAccessManager;

?>
<h4>Options</h4>
<table>
	<tbody>
	<tr>
		<td class="e"><?php echo esc_html( CloudwaysNotice::NOTICE_OPTION ); ?></td>
		<td class="v">
			<?php echo esc_html( OptionsAccessManager::get_option( CloudwaysNotice::NOTICE_OPTION ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( CloudflareNotice::NOTICE_OPTION ); ?></td>
		<td class="v">
			<?php echo esc_html( OptionsAccessManager::get_option( CloudflareNotice::NOTICE_OPTION ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( LitespeedNotice::NOTICE_OPTION ); ?></td>
		<td class="v">
			<?php echo esc_html( OptionsAccessManager::get_option( LitespeedNotice::NOTICE_OPTION ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( CronStatusManager::CRON_PATHS_TRANSIENT ); ?></td>
		<td class="v">
			<?php echo count( get_site_transient( CronStatusManager::CRON_PATHS_TRANSIENT ) ?: [] ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( CronStatusManager::CRON_PATHS_SKIPPED_TRANSIENT ); ?></td>
		<td class="v">
			<?php echo esc_html( get_site_transient( CronStatusManager::CRON_PATHS_SKIPPED_TRANSIENT ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( CronStatusManager::CRON_STATUS_LOCKED_TRANSIENT ); ?></td>
		<td class="v">
			<?php echo esc_html( get_site_transient( CronStatusManager::CRON_STATUS_LOCKED_TRANSIENT ) ?: '-' ); ?>
		</td>
	</tr>
	<tr>
		<td class="e"><?php echo esc_html( CronStatusManager::CRON_REQUEST_ID_TRANSIENT ); ?></td>
		<td class="v">
			<?php echo esc_html( get_site_transient( CronStatusManager::CRON_REQUEST_ID_TRANSIENT ) ?: '-' ); ?>
		</td>
	</tr>
	</tbody>
</table>
