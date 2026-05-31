<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Loader;
use SPC\Services\Bypass_Resolver;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Purge_Queue;
use SPC\Services\Settings_Store;
use SPC\Services\Varnish;
use SPC\Utils\Helpers;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Central cache orchestrator. Handles response headers, purge queue, scheduled purging,
 * content-change triggers, AJAX purge endpoints, and the cache-buster redirect.
 *
 * Third-party plugin integrations live in {@see Third_Party_Integrations}.
 * .htaccess / nginx rules live in {@see \SPC\Utils\Htaccess_Writer}.
 * Preloader kickoff lives in {@see Preloader_Process}.
 */
class Cache_Controller implements Module_Interface {
	private bool $skip_cache             = false;
	private bool $purge_all_already_done = false;

	public function init() {
		// Purge cache cronjob
		add_action( 'swcfpc_cache_purge_cron', [ Purge_Queue::class, 'process_queue' ] );
		add_filter( 'cron_schedules', [ Purge_Queue::class, 'register_cron_schedule' ] );
		add_action( 'shutdown', [ Purge_Queue::class, 'maybe_start_cron' ], PHP_INT_MAX );

		// AJAX purges
		add_action( 'wp_ajax_swcfpc_purge_whole_cache', [ $this, 'ajax_purge_whole_cache' ] );
		add_action( 'wp_ajax_swcfpc_purge_everything', [ $this, 'ajax_purge_everything' ] );
		add_action( 'wp_ajax_swcfpc_purge_single_post_cache', [ $this, 'ajax_purge_single_post_cache' ] );

		// Response headers
		add_action( 'init', [ $this, 'setup_response_headers_backend' ], 0 );
		add_action( 'send_headers', [ $this, 'bypass_cache_on_init' ], PHP_INT_MAX );
		add_action( 'template_redirect', [ $this, 'apply_cache' ], PHP_INT_MAX );

		// Scheduled purge / preloader over HTTP
		add_action( 'init', [ $this, 'cronjob_purge_cache' ] );
		add_action( 'init', [ $this, 'cronjob_preloader' ] );

		// Bypass REST
		if ( Settings_Store::get_instance()->get( Constants::SETTING_BYPASS_WP_JSON_REST, 0 ) > 0 ) {
			add_filter( 'rest_send_nocache_headers', '__return_true' );
		}
	}

	/**
	 * @return void
	 */
	public function setup_response_headers_backend() {
		if ( is_admin() ) {
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();

			if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
				add_filter(
					'nocache_headers',
					static function () {
						return [ 'X-WP-CF-Super-Cache' => 'disabled' ];
					},
					PHP_INT_MAX
				);
			} else {
				add_filter(
					'nocache_headers',
					static function () {
						return [
							'Cache-Control'       => 'private, no-cache, must-revalidate, max-age=0',
							'X-WP-CF-Super-Cache-Cache-Control' => 'private, no-cache, must-revalidate, max-age=0',
							'X-WP-CF-Super-Cache' => 'no-cache',
							'Pragma'              => 'no-cache',
							'Expires'             => gmdate( 'D, d M Y H:i:s \G\M\T', time() ),
						];
					},
					PHP_INT_MAX
				);
			}

			return;
		}

		if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();

			add_filter(
				'nocache_headers',
				static function () {
					return [ 'X-WP-CF-Super-Cache' => 'disabled' ];
				},
				PHP_INT_MAX
			);

			return;
		}

		if ( Bypass_Resolver::is_url_to_bypass() || Bypass_Resolver::can_i_bypass_cache() ) {
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();

			add_filter(
				'nocache_headers',
				static function () {
					return [
						'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
						'X-WP-CF-Super-Cache-Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
						'X-WP-CF-Super-Cache' => 'no-cache',
						'Pragma'              => 'no-cache',
						'Expires'             => gmdate( 'D, d M Y H:i:s \G\M\T', time() ),
					];
				},
				PHP_INT_MAX
			);

			return;
		}

		Loader::get()->fallback_cache()->fallback_cache_enable();
		Loader::get()->html_cache()->cache_current_page();

		$cache_control = Settings_Store::get_instance()->get_cache_control_value();
		add_filter(
			'nocache_headers',
			static function () use ( $cache_control ) {
				return [
					'Cache-Control'                     => $cache_control,
					'X-WP-CF-Super-Cache-Cache-Control' => $cache_control,
					'X-WP-CF-Super-Cache-Active'        => '1',
					'X-WP-CF-Super-Cache'               => 'cache',
				];
			},
			PHP_INT_MAX
		);
	}

	/**
	 * @return void
	 */
	public function bypass_cache_on_init() {
		if ( is_admin() ) {
			return;
		}

		if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
			header( 'X-WP-SPC-Disk-Cache: DISABLED' );
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();
			return;
		}

		if ( $this->skip_cache ) {
			return;
		}

		header_remove( 'Pragma' );
		header_remove( 'Expires' );
		header_remove( 'Cache-Control' );

		if ( Bypass_Resolver::is_url_to_bypass() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s \G\M\T', time() ) );
			header( 'X-WP-SPC-Disk-Cache: BYPASS' );
			header( 'X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			$this->skip_cache = true;
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();
			return;
		}

		Loader::get()->fallback_cache()->fallback_cache_enable();
		Loader::get()->html_cache()->cache_current_page();
	}

	/**
	 * @return void
	 */
	public function apply_cache() {
		if ( is_admin() ) {
			return;
		}

		$settings = Settings_Store::get_instance();

		if ( ! $settings->is_cache_enabled() ) {
			header( 'X-WP-SPC-Disk-Cache: DISABLED' );
			header( 'X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();
			return;
		}

		if ( $this->skip_cache ) {
			return;
		}

		if ( Bypass_Resolver::can_i_bypass_cache() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: no-cache' );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s \G\M\T', time() ) );
			header( 'X-WP-SPC-Disk-Cache: BYPASS' );
			header( 'X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			Loader::get()->fallback_cache()->fallback_cache_disable();
			Loader::get()->html_cache()->do_not_cache_current_page();
			return;
		}

		if ( $settings->get( Constants::SETTING_STRIP_RESPONSE_COOKIES, 0 ) > 0 ) {
			header_remove( 'Set-Cookie' );
		}

		header_remove( 'Pragma' );
		header_remove( 'Expires' );
		header_remove( 'Cache-Control' );
		header( 'Cache-Control: ' . $settings->get_cache_control_value() );

		$status = $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE, 0 ) > 0 ? 'HIT' : 'DISABLED';
		if ( Helpers::has_cache_bypass_reason_header() ) {
			$status = 'BYPASS';
		}

		header( 'X-WP-SPC-Disk-Cache: ' . $status );
		header( 'X-WP-CF-Super-Cache-Active: 1' );
		header( 'X-WP-CF-Super-Cache-Cache-Control: ' . $settings->get_cache_control_value() );

		Loader::get()->fallback_cache()->fallback_cache_enable();
		Loader::get()->html_cache()->cache_current_page();
	}

	/**
	 * Static facade — preserves the per-request `$purge_all_already_done` dedup by routing
	 * through the singleton instance. Prefer calling this from outside Cache_Controller
	 * instead of chaining through `Loader::get()->cache_controller()`.
	 *
	 * @return bool
	 */
	public static function purge_all( bool $disable_preloader = false, bool $queue_mode = true, bool $force_purge_everything = false ) {
		return Loader::get()->cache_controller()->purge_all_internal( $disable_preloader, $queue_mode, $force_purge_everything );
	}

	/**
	 * @internal Use {@see self::purge_all()} from outside this class.
	 *
	 * @return bool
	 */
	public function purge_all_internal( bool $disable_preloader = false, bool $queue_mode = true, bool $force_purge_everything = false ) {
		$error    = '';
		$settings = Settings_Store::get_instance();

		if ( $queue_mode && $settings->get( Constants::SETTING_DISABLE_PURGING_QUEUE, 0 ) == 0 ) {
			Purge_Queue::write( [], true );

			return true;
		}

		if ( $this->purge_all_already_done ) {
			return true;
		}

		$cached_html_pages       = null;
		$cached_html_pages_count = 0;

		if ( $force_purge_everything == false && $settings->get( Constants::SETTING_PURGE_ONLY_HTML, 0 ) > 0 ) {
			$timestamp         = time();
			$cached_html_pages = Loader::get()->html_cache()->get_cached_urls_by_timestamp( $timestamp );

			if ( is_array( $cached_html_pages ) ) {
				$cached_html_pages_count = count( $cached_html_pages );

				if ( $cached_html_pages_count > 0 ) {
					Loader::get()->html_cache()->delete_cached_urls_by_timestamp( $timestamp );

					if ( ! ( new Cloudflare_Integration() )->purge_cache_urls( $cached_html_pages, $error ) ) {
						Logger::log( 'cache_controller::purge_all', "Unable to purge some URLs from Cloudflare due to error: {$error}" );
						return false;
					}
				} else {
					Logger::log( 'cache_controller::purge_all', 'There are no HTML pages to purge' );
				}
			}
		} else {
			$cloudflare = new Cloudflare_Integration();
			if ( $cloudflare->is_enabled() && ! $cloudflare->purge_cache( $error ) ) {
				Logger::log( 'cache_controller::purge_all', "Unable to purge the whole Cloudflare cache due to error: {$error}" );
				return false;
			}

			if ( $settings->get( Constants::SETTING_PURGE_ONLY_HTML, 0 ) > 0 ) {
				Loader::get()->html_cache()->delete_all_cached_urls();
			}
		}

		if ( $settings->get( Constants::SETTING_VARNISH_SUPPORT, 0 ) > 0 && $settings->get( Constants::SETTING_VARNISH_AUTO_PURGE, 0 ) > 0 ) {
			( new Varnish() )->purge_whole_cache( $error );
		}

		if ( $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE, 0 ) > 0 && $settings->get( Constants::SETTING_FALLBACK_CACHE_AUTO_PURGE, 0 ) > 0 ) {
			Loader::get()->fallback_cache()->fallback_cache_purge_all();
		}

		if ( $settings->get( Constants::SETTING_OPCACHE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			self::purge_opcache();
		}

		if ( $settings->get( Constants::SETTING_OBJECT_CACHE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			self::purge_object_cache();
		}

		if ( $settings->get( Third_Party::SETTING_WPENGINE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_wpengine_cache();
		}

		if ( $settings->get( Third_Party::SETTING_SPINUPWP_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_spinupwp_cache();
		}

		if ( $settings->get( Third_Party::SETTING_KINSTA_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_kinsta_cache();
		}

		if ( $settings->get( Third_Party::SETTING_SITEGROUND_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_siteground_cache();
		}

		if ( $settings->get( Constants::SETTING_PURGE_ONLY_HTML, 0 ) == 0 || $force_purge_everything == true ) {
			Logger::log( 'cache_controller::purge_all', 'Purged whole cache' );
		} else {
			if ( ! is_array( $cached_html_pages ) || ! $cached_html_pages_count ) {
				Logger::log( 'cache_controller::purge_all', 'There are no HTML pages to purge' );
			} else {
				if ( $settings->is_cloudflare_connected() ) {
					Logger::log( 'cache_controller::purge_all', "Purged only {$cached_html_pages_count} HTML pages from Cloudflare" );
				} else {
					Logger::log( 'cache_controller::purge_all', "Purged only {$cached_html_pages_count} HTML pages" );
				}
				Logger::log( 'cache_controller::purge_all', 'Pages purged ' . print_r( $cached_html_pages, true ), true );
			}
		}

		if ( $disable_preloader === false && $settings->get( Constants::SETTING_ENABLE_PRELOADER, 1 ) > 0 && $settings->get( Constants::SETTING_PRELOADER_START_ON_PURGE, 0 ) > 0 ) {
			Preloader_Process::start_for_all_urls();
		}

		do_action( 'swcfpc_purge_all' );

		if ( Speculative_Loading::is_legacy_engine() ) {
			Speculative_Loading::bump_prefetch_timestamp();
		}

		$this->purge_all_already_done = true;

		return true;
	}

	/**
	 * Static facade — see {@see self::purge_all()}.
	 *
	 * @param array<int, string> $urls
	 * @return bool
	 */
	public static function purge_urls( $urls, bool $queue_mode = true ) {
		return Loader::get()->cache_controller()->purge_urls_internal( $urls, $queue_mode );
	}

	/**
	 * @internal Use {@see self::purge_urls()} from outside this class.
	 *
	 * @param array<int, string> $urls
	 * @return bool
	 */
	public function purge_urls_internal( $urls, bool $queue_mode = true ) {
		if ( ! is_array( $urls ) ) {
			return false;
		}

		$error    = '';
		$settings = Settings_Store::get_instance();

		foreach ( $urls as $array_index => $single_url ) {
			if ( Helpers::is_external_link( $single_url ) || substr( strtolower( $single_url ), 0, 4 ) !== 'http' ) {
				unset( $urls[ $array_index ] );
			}
		}

		if ( $queue_mode && (int) $settings->get( Constants::SETTING_DISABLE_PURGING_QUEUE, 0 ) === 0 ) {
			Purge_Queue::write( $urls );

			return true;
		}

		$count_urls = count( $urls );

		$cloudflare = new Cloudflare_Integration();
		if ( $cloudflare->is_enabled() && ! $cloudflare->purge_cache_urls( $urls, $error ) ) {
			Logger::log( 'cache_controller::purge_urls', "Unable to purge some URLs from Cloudflare due to error: {$error}" );
			return false;
		}

		if ( $settings->get( Constants::SETTING_VARNISH_SUPPORT, 0 ) > 0 && $settings->get( Constants::SETTING_VARNISH_AUTO_PURGE, 0 ) > 0 ) {
			( new Varnish() )->purge_urls( $urls );
		}

		if ( $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE, 0 ) > 0 && $settings->get( Constants::SETTING_FALLBACK_CACHE_AUTO_PURGE, 0 ) > 0 ) {
			Loader::get()->fallback_cache()->fallback_cache_purge_urls( $urls );
		}

		if ( $settings->get( Constants::SETTING_PURGE_ONLY_HTML, 0 ) > 0 ) {
			Loader::get()->html_cache()->delete_cached_urls_by_urls_list( $urls );
		}

		if ( $settings->get( Constants::SETTING_OPCACHE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			self::purge_opcache();
		}

		if ( $settings->get( Constants::SETTING_OBJECT_CACHE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			self::purge_object_cache();
		}

		if ( $settings->get( Third_Party::SETTING_WPENGINE_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_wpengine_cache();
		}

		if ( $settings->get( Third_Party::SETTING_SPINUPWP_PURGE_ON_FLUSH, 0 ) > 0 ) {
			if ( $count_urls > 1 ) {
				Third_Party_Integrations::purge_spinupwp_cache();
			} else {
				Third_Party_Integrations::purge_spinupwp_cache_single_url( $urls[0] );
			}
		}

		if ( $settings->get( Third_Party::SETTING_KINSTA_PURGE_ON_FLUSH, 0 ) > 0 ) {
			if ( $count_urls > 1 ) {
				Third_Party_Integrations::purge_kinsta_cache();
			} else {
				Third_Party_Integrations::purge_kinsta_cache_single_url( $urls[0] );
			}
		}

		if ( $settings->get( Third_Party::SETTING_SITEGROUND_PURGE_ON_FLUSH, 0 ) > 0 ) {
			Third_Party_Integrations::purge_siteground_cache();
		}

		if ( $settings->get( Constants::SETTING_ENABLE_PRELOADER, 1 ) > 0 && $settings->get( Constants::SETTING_PRELOADER_START_ON_PURGE, 0 ) > 0 ) {
			Preloader_Process::start_for_urls( $urls );
		}

		$log_message  = 'Purged cache for ';
		$log_message .= implode( ', ', array_slice( $urls, 0, 3 ) );

		if ( $count_urls > 3 ) {
			$remaining    = $count_urls - 3;
			$log_message .= sprintf( ' + %d other%s', $remaining, $remaining > 1 ? 's' : '' );
		}

		Logger::log( 'cache_controller::purge_urls', $log_message );

		do_action( 'swcfpc_purge_urls', $urls );

		if ( Speculative_Loading::is_legacy_engine() ) {
			Speculative_Loading::bump_prefetch_timestamp();
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function purge_object_cache() {
		if ( ! function_exists( 'wp_cache_flush' ) ) {
			return false;
		}

		wp_cache_flush();

		Logger::log( 'cache_controller::purge_object_cache', 'Purge object cache' );

		return true;
	}

	/**
	 * @return bool
	 */
	public static function purge_opcache() {
		if ( ! extension_loaded( 'opcache' ) || ! function_exists( 'opcache_get_status' ) ) {
			return false;
		}

		$opcache_status = opcache_get_status();

		if ( ! $opcache_status || ! isset( $opcache_status['opcache_enabled'] ) || $opcache_status['opcache_enabled'] === false ) {
			return false;
		}

		if ( ! opcache_reset() ) {
			return false;
		}

		foreach ( $opcache_status['scripts'] as $key => $data ) {
			opcache_invalidate( $data['full_path'], true );
		}

		Logger::log( 'cache_controller::purge_opcache', 'Purge OPcache cache' );

		return true;
	}

	/**
	 * @return void
	 */
	public function cronjob_purge_cache() {
		$settings = Settings_Store::get_instance();

		if ( ! $settings->is_cache_enabled() || ! isset( $_GET['swcfpc-purge-all'] ) ) {
			return;
		}

		if ( ! isset( $_GET['swcfpc-sec-key'] ) || $_GET['swcfpc-sec-key'] !== $settings->get( Constants::SETTING_PURGE_URL_SECRET_KEY, wp_generate_password( 20, false, false ) ) ) {
			return;
		}

		$this->purge_all_internal( false, false );
		Logger::log( 'cache_controller::cronjob_purge_cache', 'Cache purging complete' );

		if ( ! headers_sent() ) {
			nocache_headers();
		}

		die( 'Cache purged' );
	}

	/**
	 * @return void
	 */
	public function cronjob_preloader() {
		$settings = Settings_Store::get_instance();

		if ( ! isset( $_GET['swcfpc-preloader'] ) || ! isset( $_GET['swcfpc-sec-key'] ) ) {
			return;
		}

		if ( $_GET['swcfpc-sec-key'] !== $settings->get( Constants::SETTING_PRELOAD_CRONJOB_SECRET, wp_generate_password( 20, false, false ) ) ) {
			return;
		}

		if ( $settings->get( Constants::SETTING_ENABLE_PRELOADER, 1 ) == 0 ) {
			return;
		}

		Preloader_Process::start_for_all_urls();
		Logger::log( 'cache_controller::cronjob_preloader', 'Preloader started' );

		if ( ! headers_sent() ) {
			nocache_headers();
		}

		die( 'Preloader started' );
	}

	/**
	 * @return void
	 */
	public function ajax_purge_everything() {
		check_ajax_referer( 'spc-ajax-nonce', 'security' );

		$return_array = [ 'status' => 'ok' ];

		if ( ! Helpers::can_current_user_purge_cache() ) {
			$return_array['status'] = 'error';
			$return_array['error']  = __( 'Permission denied', 'wp-cloudflare-page-cache' );
			die( wp_json_encode( $return_array ) );
		}

		Logger::log( 'cache_controller::ajax_purge_whole_cache', 'Purge whole cache' );
		$this->purge_all_internal( false, false, true );

		$return_array['success_msg'] = __( 'Cache purged. The change may take up to 30 seconds to fully propagate.', 'wp-cloudflare-page-cache' );

		die( wp_json_encode( $return_array ) );
	}

	/**
	 * @return void
	 */
	public function ajax_purge_whole_cache() {
		check_ajax_referer( 'spc-ajax-nonce', 'security' );

		$return_array = [ 'status' => 'ok' ];

		if ( ! Helpers::can_current_user_purge_cache() ) {
			$return_array['status'] = 'error';
			$return_array['error']  = __( 'Permission denied', 'wp-cloudflare-page-cache' );
			die( wp_json_encode( $return_array ) );
		}

		Logger::log( 'cache_controller::ajax_purge_whole_cache', 'Purge whole cache' );
		$this->purge_all_internal( false, false );

		$return_array['success_msg'] = __( 'Cache purged. The change may take up to 30 seconds to fully propagate.', 'wp-cloudflare-page-cache' );

		die( wp_json_encode( $return_array ) );
	}

	/**
	 * @return void
	 */
	public function ajax_purge_single_post_cache() {
		check_ajax_referer( 'spc-ajax-nonce', 'security' );

		$return_array = [ 'status' => 'ok' ];

		$data = stripslashes( $_POST['data'] );
		$data = json_decode( $data, true );

		if ( ! Helpers::can_current_user_purge_cache() ) {
			$return_array['status'] = 'error';
			$return_array['error']  = __( 'Permission denied', 'wp-cloudflare-page-cache' );
			die( wp_json_encode( $return_array ) );
		}

		$post_id = (int) $data['post_id'];

		$urls = Cache_Invalidation_Hooks::get_post_related_links( $post_id );

		if ( ! $this->purge_urls_internal( $urls, false ) ) {
			$return_array['status'] = 'error';
			$return_array['error']  = __( 'An error occurred while cleaning the cache. Please check log file for further details.', 'wp-cloudflare-page-cache' );
			die( wp_json_encode( $return_array ) );
		}

		Logger::log( 'cache_controller::ajax_purge_single_post_cache', "Purge Cloudflare cache for only post id {$post_id} and related contents" );

		$return_array['success_msg'] = __( 'Cache purged. The change may take up to 30 seconds to fully propagate.', 'wp-cloudflare-page-cache' );

		die( wp_json_encode( $return_array ) );
	}
}
