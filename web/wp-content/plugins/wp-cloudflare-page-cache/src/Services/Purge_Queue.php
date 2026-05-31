<?php

namespace SPC\Services;

use SPC\Constants;
use SPC\Modules\Cache_Controller;
use SPC\Utils\Helpers;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * File-based purge queue.
 *
 * Coalesces purge requests into a single JSON file (`purge_cache_queue/cache_queue.json`)
 * inside the plugin content dir. A WP-Cron job (`swcfpc_cache_purge_cron`) drains the queue
 * on a configurable interval and forwards URLs / purge-all flag to `Cache_Controller`.
 *
 * The queue is guarded by an option-based lock (`swcfpc_purge_cache_lock`) with a 60s
 * stale-lock timeout to survive crashed workers.
 *
 * Hook registration lives in {@see \SPC\Modules\Cache_Controller::init()} — this class
 * is a stateless service and does not register anything itself.
 */
class Purge_Queue {

	private const LOCK_OPTION       = 'swcfpc_purge_cache_lock';
	private const QUEUE_FILE        = 'cache_queue.json';
	private const CRON_HOOK         = 'swcfpc_cache_purge_cron';
	private const CRON_INTERVAL_KEY = 'swcfpc_purge_cache_cron_interval';

	/**
	 * Resolve (and create if missing) the queue directory inside the plugin content dir.
	 *
	 * @return string
	 */
	public static function init_directory() {
		$cache_path = Helpers::get_plugin_content_dir() . '/purge_cache_queue/';

		if ( ! file_exists( $cache_path ) && wp_mkdir_p( $cache_path ) ) {
			file_put_contents( $cache_path . 'index.php', '<?php // Silence is golden' );
		}

		return $cache_path;
	}

	/**
	 * Append URLs (or set the purge-all flag) to the queue file. Coalesces concurrent writes
	 * via the option-backed lock — once the queue is marked purge_all=true, further URL
	 * additions are dropped.
	 *
	 * @param array<int, string> $urls
	 * @param bool               $purge_all
	 * @return void
	 */
	public static function write( array $urls = [], $purge_all = false ) {
		$wait_deadline = time() + self::get_purge_cache_lock_seconds() + 1;

		while ( ! self::is_writable() ) {
			if ( time() >= $wait_deadline ) {
				Logger::log( 'purge_queue::write', 'Queue file remained locked past the timeout. Skipping queue write to avoid request timeout.' );
				return;
			}

			Logger::log( 'purge_queue::write', 'Queue file not writable. Sleep 1 second' );
			sleep( 1 );
		}

		self::lock();

		$cache_queue_path   = self::init_directory() . self::QUEUE_FILE;
		$swcfpc_cache_queue = [];

		if ( file_exists( $cache_queue_path ) ) {
			$swcfpc_cache_queue = json_decode( file_get_contents( $cache_queue_path ), true );

			if ( ! is_array( $swcfpc_cache_queue ) || ! isset( $swcfpc_cache_queue['purge_all'] ) || ! isset( $swcfpc_cache_queue['urls'] ) ) {
				self::unlock();
				return;
			}

			if ( $swcfpc_cache_queue['purge_all'] ) {
				self::unlock();
				return;
			}

			if ( $swcfpc_cache_queue['purge_all'] === false && $purge_all === true ) {
				$swcfpc_cache_queue['purge_all'] = true;
			} else {
				$swcfpc_cache_queue['urls'] = array_unique( array_merge( $swcfpc_cache_queue['urls'], $urls ) );
			}
		} else {
			Logger::log( 'purge_queue::write', 'queue file not exist' );

			$swcfpc_cache_queue = [
				'purge_all' => $purge_all,
				'urls'      => $urls,
			];
		}

		Logger::log( 'purge_queue::write', 'URLs in purge queue ' . print_r( $swcfpc_cache_queue, true ), true );

		file_put_contents( $cache_queue_path, wp_json_encode( $swcfpc_cache_queue ) );

		self::unlock();
	}

	/**
	 * Register the custom cron interval used by the queue draining job. WP filter callback
	 * for `cron_schedules`.
	 *
	 * @param array<string, mixed> $schedules
	 * @return array<string, mixed>
	 */
	public static function register_cron_schedule( $schedules ) {
		$schedules[ self::CRON_INTERVAL_KEY ] = [
			'interval' => ( defined( 'SWCFPC_PURGE_CACHE_CRON_INTERVAL' ) && \SWCFPC_PURGE_CACHE_CRON_INTERVAL > 0 ) ? \SWCFPC_PURGE_CACHE_CRON_INTERVAL : 10,
			'display'  => esc_html__( 'Super Page Cache - Purge Cache Cron Interval', 'wp-cloudflare-page-cache' ),
		];

		return $schedules;
	}

	/**
	 * Idempotently start (or stop) the WP-Cron job that drains the queue. Runs on `shutdown`
	 * so it sees writes from the same request. Disabled entirely when
	 * `SETTING_DISABLE_PURGING_QUEUE` is on.
	 *
	 * @return void
	 */
	public static function maybe_start_cron() {
		if ( Settings_Store::get_instance()->get( Constants::SETTING_DISABLE_PURGING_QUEUE, 0 ) > 0 ) {
			return;
		}

		$cache_queue_path = self::init_directory() . self::QUEUE_FILE;

		if ( ! file_exists( $cache_queue_path ) ) {
			$timestamp = wp_next_scheduled( self::CRON_HOOK );

			if ( $timestamp !== false ) {
				if ( wp_unschedule_event( $timestamp, self::CRON_HOOK ) ) {
					wp_clear_scheduled_hook( self::CRON_HOOK );
					Logger::log( 'purge_queue::maybe_start_cron', "Purge queue scheduled event stopped successfully - Timestamp {$timestamp}", true );
				} else {
					Logger::log( 'purge_queue::maybe_start_cron', "Unable to stop the purge queue scheduled event - Timestamp {$timestamp}", true );
				}
			}

			return;
		}

		if ( wp_next_scheduled( 'swcfpc_purge_cache_cron' ) || wp_get_schedule( self::CRON_HOOK ) ) {
			return;
		}

		$timestamp = time();

		if ( wp_schedule_event( $timestamp, self::CRON_INTERVAL_KEY, self::CRON_HOOK ) ) {
			Logger::log( 'purge_queue::maybe_start_cron', "Purge queue cronjob started successfully - Timestamp {$timestamp}", true );
			return;
		}

		Logger::log( 'purge_queue::maybe_start_cron', "Unable to start the purge queue scheduled event - Timestamp {$timestamp}", true );
	}

	/**
	 * Drain the queue file, dispatch to Cache_Controller, then delete the file. WP-Cron
	 * callback for `swcfpc_cache_purge_cron`.
	 *
	 * @return void
	 */
	public static function process_queue() {
		$cache_queue_path = self::init_directory() . self::QUEUE_FILE;

		if ( ! file_exists( $cache_queue_path ) ) {
			Logger::log( 'purge_queue::process_queue', 'Queue file does not exists. Exit.' );
			return;
		}

		$wait_deadline = time() + self::get_purge_cache_lock_seconds() + 1;

		while ( ! self::is_writable() ) {
			if ( time() >= $wait_deadline ) {
				Logger::log( 'purge_queue::process_queue', 'Queue file remained locked past the timeout. Skipping queue job to avoid request timeout.' );
				return;
			}

			Logger::log( 'purge_queue::process_queue', 'Queue file not writable. Sleep 1 second' );
			sleep( 1 );
		}

		self::lock();

		$swcfpc_cache_queue = json_decode( file_get_contents( $cache_queue_path ), true );

		if ( isset( $swcfpc_cache_queue['purge_all'] ) && $swcfpc_cache_queue['purge_all'] ) {
			Cache_Controller::purge_all( false, false );
		} elseif ( isset( $swcfpc_cache_queue['urls'] ) && is_array( $swcfpc_cache_queue['urls'] ) && count( $swcfpc_cache_queue['urls'] ) > 0 ) {
			Cache_Controller::purge_urls( $swcfpc_cache_queue['urls'], false );
		}

		@unlink( $cache_queue_path );

		self::unlock();

		Logger::log( 'purge_queue::process_queue', 'Cache purging complete' );
	}

	/**
	 * @return bool
	 */
	private static function is_writable() {
		$purge_cache_lock = get_option( self::LOCK_OPTION, 0 );

		return $purge_cache_lock == 0 || ( time() - $purge_cache_lock ) > self::get_purge_cache_lock_seconds();
	}

	/**
	 * @return void
	 */
	private static function lock() {
		update_option( self::LOCK_OPTION, time() );
	}

	/**
	 * @return void
	 */
	private static function unlock() {
		update_option( self::LOCK_OPTION, 0 );
	}

	/**
	 * @return int
	 */
	private static function get_purge_cache_lock_seconds() {
		// @phpstan-ignore-next-line - constant can be user-defined.
		return defined( 'SWCFPC_PURGE_CACHE_LOCK_SECONDS' ) && SWCFPC_PURGE_CACHE_LOCK_SECONDS > 0 ? SWCFPC_PURGE_CACHE_LOCK_SECONDS : 10;
	}
}
