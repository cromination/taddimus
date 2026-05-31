<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Third-party plugin integrations and host-platform cache hooks.
 *
 * Listens for flush events from other plugins (W3TC, WP Rocket, LiteSpeed, etc.)
 * and platforms (WP Engine, Kinsta, SpinupWP, SiteGround) and delegates to
 * Cache_Controller for the actual purge.
 */
class Third_Party_Integrations implements Module_Interface {

	public function init(): void {
		$settings = Settings_Store::get_instance();

		// W3TC
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_DBCACHE, 0 ) > 0 ) {
			add_action( 'w3tc_flush_dbcache', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_ALL, 0 ) > 0 ) {
			add_action( 'w3tc_flush_all', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_FRAGMENTCACHE, 0 ) > 0 ) {
			add_action( 'w3tc_flush_fragmentcache', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_OBJECTCACHE, 0 ) > 0 ) {
			add_action( 'w3tc_flush_objectcache', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_POSTS, 0 ) > 0 ) {
			add_action( 'w3tc_flush_posts', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
			add_action( 'w3tc_flush_post', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_W3TC_PURGE_ON_FLUSH_MINIFY, 0 ) > 0 ) {
			add_action( 'w3tc_flush_minify', [ $this, 'w3tc_hooks' ], PHP_INT_MAX );
		}

		// WP-Optimize
		if ( $settings->get( Third_Party::SETTING_WP_OPTIMIZE_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'wpo_cache_flush', [ $this, 'wpo_hooks' ], PHP_INT_MAX );
		}

		// WP Performance
		if ( $settings->get( Third_Party::SETTING_WP_PERFORMANCE_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'wpp-after-cache-delete', [ $this, 'wp_performance_hooks' ], PHP_INT_MAX );
		}

		// WP Rocket
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_POST_FLUSH, 0 ) > 0 ) {
			add_action( 'after_rocket_clean_post', [ $this, 'wp_rocket_after_rocket_clean_post_hook' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_DOMAIN_FLUSH, 0 ) > 0 ) {
			add_action( 'after_rocket_clean_domain', [ $this, 'wp_rocket_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_CACHE_DIR_FLUSH, 0 ) > 0 ) {
			add_action( 'rocket_after_automatic_cache_purge_dir', [ $this, 'wp_rocket_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_CLEAN_FILES, 0 ) > 0 ) {
			add_action( 'after_rocket_clean_files', [ $this, 'wp_rocket_selective_url_purge_hooks' ], PHP_INT_MAX, 1 );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_CLEAN_CACHE_BUSTING, 0 ) > 0 ) {
			add_action( 'after_rocket_clean_cache_busting', [ $this, 'wp_rocket_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_CLEAN_MINIFY, 0 ) > 0 ) {
			add_action( 'after_rocket_clean_minify', [ $this, 'wp_rocket_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_CCSS_GENERATION_COMPLETE, 0 ) > 0 ) {
			add_action( 'rocket_critical_css_generation_process_complete', [ $this, 'wp_rocket_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_PURGE_ON_RUCSS_JOB_COMPLETE, 0 ) > 0 ) {
			add_action( 'rocket_rucss_complete_job_status', [ $this, 'wp_rocket_selective_url_purge_hooks' ], PHP_INT_MAX, 1 );
		}
		if ( $settings->get( Third_Party::SETTING_WP_ROCKET_DISABLE_CACHE, 0 ) > 0 ) {
			add_action( 'admin_init', [ $this, 'wp_rocket_disable_page_cache' ], PHP_INT_MAX );
		}

		// LiteSpeed
		if ( $settings->get( Third_Party::SETTING_LITESPEED_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'litespeed_purged_all', [ $this, 'litespeed_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_LITESPEED_PURGE_ON_CCSS_FLUSH, 0 ) > 0 ) {
			add_action( 'litespeed_purged_all_ccss', [ $this, 'litespeed_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_LITESPEED_PURGE_ON_CSSJS_FLUSH, 0 ) > 0 ) {
			add_action( 'litespeed_purged_all_cssjs', [ $this, 'litespeed_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_LITESPEED_PURGE_ON_OBJECT_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'litespeed_purged_all_object', [ $this, 'litespeed_hooks' ], PHP_INT_MAX );
		}
		if ( $settings->get( Third_Party::SETTING_LITESPEED_PURGE_ON_SINGLE_POST_FLUSH, 0 ) > 0 ) {
			add_action( 'litespeed_api_purge_post', [ $this, 'litespeed_single_post_hooks' ], PHP_INT_MAX, 1 );
		}

		// Hummingbird
		if ( $settings->get( Third_Party::SETTING_HUMMINGBIRD_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'wphb_clear_cache_url', [ $this, 'hummingbird_hooks' ], PHP_INT_MAX );
		}

		// WooCommerce
		if ( $settings->get( Third_Party::SETTING_WOO_AUTO_PURGE_PRODUCT_PAGE, 0 ) > 0 ) {
			add_action( 'woocommerce_updated_product_stock', [ $this, 'woocommerce_purge_product_page_on_stock_change' ], PHP_INT_MAX, 1 );
		}
		if ( $settings->get( Third_Party::SETTING_WOO_AUTO_PURGE_SCHEDULED_SALES, 0 ) > 0 ) {
			add_action( 'wc_after_products_starting_sales', [ $this, 'woocommerce_purge_scheduled_sales' ], PHP_INT_MAX );
			add_action( 'wc_after_products_ending_sales', [ $this, 'woocommerce_purge_scheduled_sales' ], PHP_INT_MAX );
		}

		// Swift Performance Lite/Pro
		if ( $settings->get( Third_Party::SETTING_SPL_PURGE_ON_FLUSH_ALL, 0 ) > 0 ) {
			add_action( 'swift_performance_after_clear_all_cache', [ $this, 'spl_purge_all' ], PHP_INT_MAX );
			add_action( 'swift_performance_after_clear_expired_cache', [ $this, 'spl_purge_all' ], PHP_INT_MAX );
			add_action( 'swift_performance_after_clear_post_cache', [ $this, 'spl_purge_single_post' ], PHP_INT_MAX );
		}

		// EDD
		if ( $settings->get( Third_Party::SETTING_EDD_AUTO_PURGE_PAYMENT_ADD, 0 ) > 0 ) {
			add_action( 'edd_built_order', [ $this, 'edd_purge_cache_on_payment_add' ], PHP_INT_MAX );
		}

		// Nginx Helper
		if ( $settings->get( Third_Party::SETTING_NGINX_HELPER_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'rt_nginx_helper_after_fastcgi_purge_all', [ $this, 'nginx_helper_purge_all_hooks' ], PHP_INT_MAX );
			add_action( 'rt_nginx_helper_fastcgi_purge_url_base', [ $this, 'nginx_helper_purge_single_url_hooks' ], PHP_INT_MAX, 1 );
		}

		// YASR
		if ( $settings->get( Third_Party::SETTING_YASR_PURGE_ON_RATING, 0 ) > 0 ) {
			add_action( 'yasr_action_on_overall_rating', [ $this, 'yasr_hooks' ], PHP_INT_MAX, 1 );
			add_action( 'yasr_action_on_visitor_vote', [ $this, 'yasr_hooks' ], PHP_INT_MAX, 1 );
			add_action( 'yasr_action_on_visitor_multiset_vote', [ $this, 'yasr_hooks' ], PHP_INT_MAX, 1 );
		}

		// WP Asset Clean Up
		if ( $settings->get( Third_Party::SETTING_WP_ACU_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'wpacu_clear_cache_after', [ $this, 'wpacu_hooks' ], PHP_INT_MAX );
		}

		// Flying Press
		if ( $settings->get( Third_Party::SETTING_FLYPRESS_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'flying_press_purge_pages:after', [ $this, 'flying_press_hook' ], PHP_INT_MAX );
			add_action( 'flying_press_purge_everything:after', [ $this, 'flying_press_hook' ], PHP_INT_MAX );
		}

		// Autoptimize
		if ( $settings->get( Third_Party::SETTING_AUTOPTIMIZE_PURGE_ON_CACHE_FLUSH, 0 ) > 0 ) {
			add_action( 'autoptimize_action_cachepurged', [ $this, 'autoptimize_hooks' ], PHP_INT_MAX );
		}

		// Plugin upgrader
		if ( $settings->get( Constants::SETTING_FALLBACK_CACHE_PURGE_ON_UPGRADER_COMPLETE, 0 ) > 0 ) {
			add_action( 'upgrader_process_complete', [ $this, 'purge_on_plugin_update' ], PHP_INT_MAX );
		}
	}

	// -----------------------------------------------------------------------
	// Host-platform detection + purge — static facades called from elsewhere.
	// -----------------------------------------------------------------------

	public static function can_wpengine_cache_be_purged(): bool {
		return class_exists( 'WpeCommon' );
	}

	public static function can_spinupwp_cache_be_purged(): bool {
		return function_exists( 'spinupwp_purge_site' ) && function_exists( 'spinupwp_purge_url' );
	}

	public static function can_kinsta_cache_be_purged(): bool {
		global $kinsta_cache;

		return isset( $kinsta_cache ) && class_exists( '\\Kinsta\\CDN_Enabler' );
	}

	public static function is_siteground_supercacher_enabled(): bool {
		$sg_version = self::get_siteground_supercacher_version();

		if ( $sg_version === false ) {
			return false;
		}

		if ( ! version_compare( $sg_version, '5.0' ) < 0 ) {
			global $sg_cachepress_environment;

			return isset( $sg_cachepress_environment ) && $sg_cachepress_environment instanceof \SG_CachePress_Environment && $sg_cachepress_environment->cache_is_enabled();
		}

		return (bool) get_option( 'siteground_optimizer_enable_cache', 0 );
	}

	public static function purge_wpengine_cache(): void {
		if ( ! class_exists( 'WpeCommon' ) ) {
			return;
		}

		\WpeCommon::purge_memcached();
		Logger::log( 'third_party_integrations::purge_wpengine_cache', 'Purge WP Engine memcached cache' );

		\WpeCommon::purge_varnish_cache();
		Logger::log( 'third_party_integrations::purge_wpengine_cache', 'Purge WP Engine varnish cache' );
	}

	public static function purge_spinupwp_cache(): void {
		if ( ! function_exists( 'spinupwp_purge_site' ) ) {
			return;
		}

		spinupwp_purge_site();
		Logger::log( 'third_party_integrations::purge_spinupwp_cache', 'Purge whole SpinupWP' );
	}

	public static function purge_spinupwp_cache_single_url( string $url ): void {
		if ( ! function_exists( 'spinupwp_purge_url' ) ) {
			return;
		}

		spinupwp_purge_url( $url );
		Logger::log( 'third_party_integrations::purge_spinupwp_cache_single_url', "Purge SpinupWP cache for the URL {$url}" );
	}

	public static function purge_kinsta_cache(): bool {
		global $kinsta_cache;

		if ( self::can_kinsta_cache_be_purged() && ! empty( $kinsta_cache->kinsta_cache_purge ) ) {
			$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
			Logger::log( 'third_party_integrations::purge_kinsta_cache', 'Purge whole Kinsta cache' );

			return true;
		}

		return false;
	}

	public static function purge_kinsta_cache_single_url( string $url ): bool {
		if ( ! self::can_kinsta_cache_be_purged() ) {
			return false;
		}

		$url = trailingslashit( $url ) . 'kinsta-clear-cache/';

		wp_remote_get(
			$url,
			[
				'blocking' => false,
				'timeout'  => 0.01,
			]
		);

		return true;
	}

	public static function purge_siteground_cache(): void {
		if ( ! self::is_siteground_supercacher_enabled() ) {
			return;
		}

		if ( ! version_compare( self::get_siteground_supercacher_version(), '5.0' ) < 0 ) {
			\SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
		} else {
			global $sg_cachepress_supercacher;

			if ( isset( $sg_cachepress_supercacher ) && $sg_cachepress_supercacher instanceof \SG_CachePress_Supercacher ) {
				$sg_cachepress_supercacher->purge_cache();
			}
		}

		Logger::log( 'third_party_integrations::purge_siteground_cache', 'Purge whole Siteground cache' );
	}

	/**
	 * @return string|false
	 */
	private static function get_siteground_supercacher_version() {
		static $version;

		if ( isset( $version ) ) {
			return $version;
		}

		if ( file_exists( WP_PLUGIN_DIR . '/sg-cachepress/sg-cachepress.php' ) ) {
			$sg_optimizer = get_file_data( WP_PLUGIN_DIR . '/sg-cachepress/sg-cachepress.php', [ 'Version' => 'Version' ] );
			$version      = $sg_optimizer['Version'];

			return $version;
		}

		return false;
	}

	// -----------------------------------------------------------------------
	// Hook callbacks — thin wrappers that delegate to Cache_Controller.
	// -----------------------------------------------------------------------

	public function wp_rocket_disable_page_cache(): void {
		if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		add_filter( 'rocket_generate_advanced_cache_file', '__return_false', PHP_INT_MAX );
		add_filter( 'rocket_cache_mandatory_cookies', '__return_empty_array', PHP_INT_MAX );
		add_filter( 'rocket_set_wp_cache_constant', '__return_false', PHP_INT_MAX );
		add_filter( 'rocket_disable_htaccess', '__return_false', PHP_INT_MAX );
		add_filter( 'rocket_display_input_varnish_auto_purge', '__return_false', PHP_INT_MAX );
		add_filter( 'do_rocket_generate_caching_files', '__return_false', PHP_INT_MAX );
	}

	public function w3tc_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		if ( $current_action === 'w3tc_flush_minify' ) {
			Cache_Controller::purge_all( false, true, true );
		} else {
			Cache_Controller::purge_all();
		}

		Logger::log( 'third_party_integrations::w3tc_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function wpo_hooks(): void {
		if ( did_action( 'wpo_cache_flush' ) !== 1 ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::wpo_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function wp_performance_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::wp_performance_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function wp_rocket_hooks(): void {
		global $pagenow;
		if ( $pagenow === 'nav-menus.php' ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		if ( $current_action === 'after_rocket_clean_minify' ) {
			Cache_Controller::purge_all( false, true, true );
		} else {
			Cache_Controller::purge_all();
		}

		Logger::log( 'third_party_integrations::wp_rocket_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	/**
	 * @param mixed $post WP_Post when WP Rocket passes one, possibly other types.
	 */
	public function wp_rocket_after_rocket_clean_post_hook( $post ): void {
		static $done = [];

		if ( ! is_object( $post ) ) {
			Logger::log( 'third_party_integrations::wp_rocket_after_rocket_clean_post_hook', 'Unable to Purge cache. Valid post object not received' );
			return;
		}

		if ( isset( $done[ $post->ID ] ) ) {
			return;
		}

		$current_action  = function_exists( 'current_action' ) ? current_action() : '';
		$purged_post_url = get_permalink( $post->ID );

		Cache_Controller::purge_urls( [ $purged_post_url ] );

		Logger::log( 'third_party_integrations::wp_rocket_after_rocket_clean_post_hook', "Purge cache for only URL {$purged_post_url} - Fired action: {$current_action}" );

		$done[ $post->ID ] = true;
	}

	/**
	 * @param string|array<int, string> $url_to_purge
	 */
	public function wp_rocket_selective_url_purge_hooks( $url_to_purge ): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';
		$url_to_purge   = is_array( $url_to_purge ) ? $url_to_purge : [ $url_to_purge ];

		Cache_Controller::purge_urls( $url_to_purge );

		$urls_purged = wp_json_encode( $url_to_purge );
		Logger::log( 'third_party_integrations::wp_rocket_selective_url_purge_hooks', "Purge cache for only URL {$urls_purged} - Fired action: {$current_action}" );
	}

	public function litespeed_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		if ( $current_action === 'litespeed_purged_all_cssjs' || $current_action === 'litespeed_purged_all' ) {
			Cache_Controller::purge_all( false, true, true );
		} else {
			Cache_Controller::purge_all();
		}

		Logger::log( 'third_party_integrations::litespeed_hooks', "Purge Cloudflare cache (fired action: {$current_action})" );
	}

	/**
	 * @param mixed $post_id
	 */
	public function litespeed_single_post_hooks( $post_id ): void {
		static $done = [];
		$post_id     = (int) $post_id;

		if ( $post_id <= 0 ) {
			return;
		}

		if ( isset( $done[ $post_id ] ) ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );

		Logger::log( 'third_party_integrations::litespeed_single_post_hooks', "Purge cache for only post {$post_id} - Fired action: {$current_action}" );

		$done[ $post_id ] = true;
	}

	public function hummingbird_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::hummingbird_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function nginx_helper_purge_all_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::nginx_helper_purge_all_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	/**
	 * @param mixed $url_to_purge
	 */
	public function nginx_helper_purge_single_url_hooks( $url_to_purge ): void {
		if ( Settings_Store::get_instance()->get( Third_Party::SETTING_NGINX_HELPER_PURGE_ON_CACHE_FLUSH, 0 ) == 0 ) {
			return;
		}

		$url_to_purge = is_scalar( $url_to_purge ) ? (string) $url_to_purge : '';

		if ( '' === $url_to_purge ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_urls( [ $url_to_purge ] );

		Logger::log( 'third_party_integrations::nginx_helper_purge_single_url_hooks', "Purge cache for only URL {$url_to_purge} - Fired action: {$current_action}" );
	}

	/**
	 * @param int|array{post_id:int} $post_id
	 */
	public function yasr_hooks( $post_id ): void {
		static $done = [];

		$post_id = is_array( $post_id ) ? $post_id['post_id'] : $post_id;

		if ( isset( $done[ $post_id ] ) ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );

		Logger::log( 'third_party_integrations::yasr_hooks', "Purge cache for only post {$post_id} - Fired action: {$current_action}" );

		$done[ $post_id ] = true;
	}

	public function spl_purge_all(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		if ( $current_action === 'swift_performance_after_clear_all_cache' ) {
			Cache_Controller::purge_all( false, true, true );
		} else {
			Cache_Controller::purge_all();
		}

		Logger::log( 'third_party_integrations::spl_purge_all', "Purge whole cache (fired action: {$current_action})" );
	}

	/**
	 * @param mixed $post_id
	 */
	public function spl_purge_single_post( $post_id ): void {
		static $done = [];
		$post_id     = (int) $post_id;

		if ( $post_id <= 0 ) {
			return;
		}

		if ( isset( $done[ $post_id ] ) ) {
			return;
		}

		if ( Settings_Store::get_instance()->get( Third_Party::SETTING_SPL_PURGE_ON_FLUSH_SINGLE_POST, 0 ) == 0 ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );

		Logger::log( 'third_party_integrations::spl_purge_single_post', "Purge cache for only post {$post_id} - Fired action: {$current_action}" );

		$done[ $post_id ] = true;
	}

	public function wpacu_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::wpacu_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function flying_press_hook(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all( false, true, true );
		Logger::log( 'third_party_integrations::flying_press_hook', "Purge whole cache (fired action: {$current_action})" );
	}

	public function autoptimize_hooks(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::autoptimize_hooks', "Purge whole cache (fired action: {$current_action})" );
	}

	public function purge_on_plugin_update(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all( false, true, true );
		Logger::log( 'third_party_integrations::purge_on_plugin_update', "Purge whole cache (fired action: {$current_action})" );
	}

	public function edd_purge_cache_on_payment_add(): void {
		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'third_party_integrations::edd_purge_cache_on_payment_add', "Purge whole cache (fired action: {$current_action})" );
	}

	/**
	 * @param mixed $product_id
	 */
	public function woocommerce_purge_product_page_on_stock_change( $product_id ): void {
		$product_id = (int) $product_id;

		if ( $product_id <= 0 ) {
			return;
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$urls = [];

		if ( function_exists( 'wc_get_page_id' ) ) {
			$urls[] = get_permalink( wc_get_page_id( 'shop' ) );
		}

		foreach ( wc_get_product_cat_ids( $product_id ) as $category_id ) {
			$urls[] = get_category_link( $category_id );
		}

		$urls = array_merge( $urls, Cache_Invalidation_Hooks::get_post_related_links( $product_id ) );
		$urls = array_unique( $urls );

		Cache_Controller::purge_urls( $urls );
		Logger::log( 'third_party_integrations::woocommerce_purge_product_page_on_stock_change', 'Purge product pages and categories for WooCommerce order' );
	}

	/**
	 * @param array<int, int>|mixed $product_id_list
	 */
	public function woocommerce_purge_scheduled_sales( $product_id_list ): void {
		if ( ! is_array( $product_id_list ) || count( $product_id_list ) === 0 ) {
			return;
		}

		$urls = [];
		foreach ( $product_id_list as $product_id ) {
			$single_url = get_permalink( $product_id );

			if ( $single_url !== false ) {
				$urls[] = $single_url;
			}
		}

		if ( count( $urls ) > 0 ) {
			Cache_Controller::purge_urls( $urls );
		}
	}
}
