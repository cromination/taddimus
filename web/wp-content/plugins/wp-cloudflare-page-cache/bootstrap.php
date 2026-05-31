<?php
/**
 * Plugin bootstrap file.
 */

use SPC\Constants;
use SPC\Models\Asset_Rules;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;
use SPC\Utils\Logger;

if ( ! class_exists( 'SW_CLOUDFLARE_PAGECACHE' ) ) {
	define( 'SPC_PATH', defined( 'SPC_PRO_PATH' ) ? SPC_PRO_PATH : SPC_FREE_PATH );

	define( 'SWCFPC_PLUGIN_PATH', plugin_dir_path( SPC_PATH ) );
	define( 'SWCFPC_PLUGIN_URL', plugin_dir_url( SPC_PATH ) );
	define( 'SWCFPC_BASEFILE', SPC_PATH );
	define( 'SWCFPC_PRODUCT_SLUG', basename( dirname( SPC_PATH ) ) );
	define( 'SWCFPC_PLUGIN_REVIEWS_URL', 'https://wordpress.org/support/plugin/wp-cloudflare-page-cache/reviews/' );
	define( 'SWCFPC_PLUGIN_FORUM_URL', 'https://wordpress.org/support/plugin/wp-cloudflare-page-cache/' );
	define( 'SWCFPC_AUTH_MODE_API_KEY', 0 );
	define( 'SWCFPC_AUTH_MODE_API_TOKEN', 1 );
	define( 'SWCFPC_VERSION', '5.3.1' );
	if ( ! defined( 'SPC_METRICS_DIR' ) ) {
		$home_url_parts = parse_url( home_url() );
		define( 'SPC_METRICS_DIR', WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$home_url_parts['host']}/metrics" );
	}

	if ( ! defined( 'SWCFPC_PRELOADER_MAX_POST_NUMBER' ) ) {
		define( 'SWCFPC_PRELOADER_MAX_POST_NUMBER', 50 );
	}

	if ( ! defined( 'SWCFPC_CACHE_BUSTER' ) ) {
		define( 'SWCFPC_CACHE_BUSTER', 'swcfpc' );
	}

	if ( ! defined( 'SWCFPC_CURL_TIMEOUT' ) ) {
		define( 'SWCFPC_CURL_TIMEOUT', 10 );
	}

	if ( ! defined( 'SWCFPC_PURGE_CACHE_LOCK_SECONDS' ) ) {
		define( 'SWCFPC_PURGE_CACHE_LOCK_SECONDS', 10 );
	}

	if ( ! defined( 'SWCFPC_HOME_PAGE_SHOWS_POSTS' ) ) {
		define( 'SWCFPC_HOME_PAGE_SHOWS_POSTS', true );
	}

	if ( file_exists( SWCFPC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
		require_once SWCFPC_PLUGIN_PATH . 'vendor/autoload.php';
	}

	add_filter(
		'themesle_sdk_namespace_' . md5( SPC_PATH ),
		function ( $encoded_basefile ) {
			return 'spc';
		}
	);

	class SW_CLOUDFLARE_PAGECACHE {

		/**
		 * The plugin version.
		 */
		private $version = SWCFPC_VERSION;

		/**
		 * The core loader.
		 * @var \SPC\Loader|null $core_loader
		 */
		private $core_loader = null;

		/**
		 * The pro modules.
		 * @var \SPC_Pro\Loader $pro_loader
		 */
		private $pro_loader;

		/**
		 * The redirect option key.
		 */
		public const REDIRECT_KEY = 'swcfpc_dashboard_redirect';

		/**
		 * SW_CLOUDFLARE_PAGECACHE constructor.
		 */
		public function __construct() {
			register_deactivation_hook( SPC_PATH, [ $this, 'deactivate_plugin' ] );

			if ( ! file_exists( Helpers::get_plugin_content_dir() ) ) {
				Helpers::create_plugin_content_dir();
			}

			$this->update_plugin();
			$this->boot_services();
			$this->actions();
		}

		/**
		 * Load the textdomain.
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'wp-cloudflare-page-cache', false, basename( dirname( SPC_PATH ) ) . '/languages/' );
		}

		/**
		 * Boot the Loader + its modules. Idempotent.
		 */
		private function boot_services() {
			if ( $this->core_loader !== null ) {
				return;
			}

			include_once ABSPATH . 'wp-includes/pluggable.php';

			$this->core_loader = new SPC\Loader();

			$this->maybe_load_pro_modules();
			$this->core_loader->load_modules();
		}

		/**
		 * Run actions.
		 */
		private function actions() {
			add_action( 'admin_init', [ $this, 'maybe_deactivate_free' ] );
			add_filter( 'themeisle_sdk_products', [ $this, 'load_sdk' ] );
			add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
			add_filter( SWCFPC_PRODUCT_SLUG . '_sdk_migrations_path', [ $this, 'register_migrations_path' ] );
			add_action( 'themeisle_sdk_update_' . SWCFPC_PRODUCT_SLUG, [ $this, 'on_plugin_upgrade' ] );
		}

		/**
		 * Filter callback that points the SDK migrator at our migrations directory.
		 *
		 * @return string
		 */
		public function register_migrations_path() {
			return SWCFPC_PLUGIN_PATH . 'migrations/';
		}

		/**
		 * Action callback fired by the SDK on a detected version change.
		 *
		 * Captures the previous version into a stable option so SDK migrations
		 * can read it from `should_run()`, and schedules the cache-rule sync /
		 * advanced-cache.php relink that has to run on every upgrade.
		 *
		 * @param string $previous_version Version before this upgrade.
		 *
		 * @return void
		 */
		public function on_plugin_upgrade( $previous_version = '' ) {
			update_option( 'swcfpc_previous_version', (string) $previous_version );

			add_action( 'shutdown', [ $this, 'run_post_upgrade_tasks' ] );
		}

		/**
		 * Tasks that run on every plugin upgrade.
		 *
		 * @return void
		 */
		public function run_post_upgrade_tasks() {
			Logger::log( 'upgrader::post_upgrade', 'Running post-upgrade tasks.' );

			$this->boot_services();

			$cloudflare = new Cloudflare_Integration();
			$cf_error   = '';

			$cloudflare->update_cache_rule_if_diff( $cf_error );

			if ( $cf_error ) {
				Logger::log( 'upgrader::post_upgrade', sprintf( 'Cache rule sync error: %s', $cf_error ), true );
			}

			if ( defined( 'SWCFPC_ADVANCED_CACHE' ) ) {
				$store          = Settings_Store::get_instance();
				$cache_enabled  = $store->get( Constants::SETTING_CF_CACHE_ENABLED );
				$fallback_cache = $store->get( Constants::SETTING_ENABLE_FALLBACK_CACHE );
				$curl_enabled   = $store->get( Constants::SETTING_FALLBACK_CACHE_CURL );

				if ( $cache_enabled > 0 && $fallback_cache > 0 && ! $curl_enabled ) {
					$handler = $this->core_loader->fallback_cache();
					$handler->fallback_cache_advanced_cache_disable();
					$handler->fallback_cache_advanced_cache_enable();
				}
			}

			do_action( 'swcfpc_after_plugin_upgrader_run' );
		}

		/**
		 * Load the SDK.
		 *
		 * @param array $products
		 *
		 * @return array
		 */
		public function load_sdk( $products ) {
			$products[] = SWCFPC_BASEFILE;

			return $products;
		}

		/**
		 * Keep the legacy `swcfpc_version` option in sync with the running plugin version.
		 *
		 * The SDK migrator handles version-gated migrations on its own; this method
		 * exists to keep the option current for the few consumers that still read it
		 * (CLI `version` command, uninstall cleanup) and to handle the free→pro
		 * transition where the version doesn't actually change.
		 *
		 * @return void
		 */
		private function update_plugin() {
			$current_version = get_option( 'swcfpc_version', false );

			if ( get_option( 'cf_will_run_free_to_pro_migrations' ) === 'yes' ) {
				delete_option( 'cf_will_run_free_to_pro_migrations' );
				// Free→pro keeps the same plugin version, so the SDK update action
				// won't fire. Schedule the post-upgrade tasks ourselves.
				add_action( 'shutdown', [ $this, 'run_post_upgrade_tasks' ] );
			}

			if ( version_compare( $current_version, $this->version, '!=' ) ) {
				update_option( 'swcfpc_version', $this->version );
			}
		}

		/**
		 * Deactivate the plugin.
		 *
		 * @hooked register_deactivation_hook()
		 *
		 * @return void
		 */
		public function deactivate_plugin() {
			// Keep settings when upgrading.
			if ( defined( 'SPC_PRO_PATH' ) && defined( 'SPC_FREE_PATH' ) ) {
				return;
			}

			$keep_settings = Settings_Store::get_instance()->get( Constants::SETTING_KEEP_ON_DEACTIVATION );

			if ( ! $keep_settings ) {
				Settings_Store::get_instance()->reset();
				Asset_Rules::remove_database_table();
			}

			Helpers::delete_plugin_content_dir();
		}

		/**
		 * If both free & pro are active, we attempt to deactivate the free version.
		 *
		 * @return void
		 */
		public function maybe_deactivate_free() {
			if ( defined( 'SPC_PRO_PATH' ) && defined( 'SPC_FREE_PATH' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';

				deactivate_plugins( SPC_FREE_PATH );

				add_action(
					'admin_notices',
					function () {
						printf(
							'<div class="notice notice-warning"><p><strong>%s</strong><br>%s</p></div>',
							sprintf(
								/* translators: %s: Name of deactivated plugin */
								__( '%s plugin deactivated.', 'wp-cloudflare-page-cache' ),
								'Super Page Cache for Cloudflare (Free)'
							),
							__( 'The Premium version of Super Page Cache for Cloudflare does not require the Free version.', 'wp-cloudflare-page-cache' )
						);
					}
				);

				update_option( 'cf_will_run_free_to_pro_migrations', 'yes' );
				do_action( 'swcfpc_after_free_deactivation' );
			}
		}

		/**
		 * Get the modules.
		 *
		 * @return array
		 */
		public function get_modules() {
			return $this->core_loader ? $this->core_loader->get_modules() : [];
		}

		/**
		 * Get the modules.
		 *
		 * Legacy function to preserve backward compatibility for old `advanced-cache.php` files.
		 *
		 * @return array
		 *
		 * @deprecated Use get_modules() instead.
		 */
		public function get_objects() {
			return $this->get_modules();
		}

		/**
		 * Check if current user can purge cache.
		 *
		 * @return bool
		 */
		public function can_current_user_purge_cache() {
			return Helpers::can_current_user_purge_cache();
		}

		/**
		 * Maybe load the pro modules.
		 *
		 * @return void
		 */
		private function maybe_load_pro_modules() {
			if (
				! is_file( SWCFPC_PLUGIN_PATH . 'pro/Loader.php' ) ||
				! defined( 'SPC_PRO_PATH' ) ||
				! class_exists( 'SPC_Pro\\Loader' )
			) {
				return;
			}

			$this->pro_loader = new SPC_Pro\Loader();
			$this->pro_loader->init();
		}

		/**
		 * Get the core loader.
		 *
		 * @return SPC\Loader
		 */
		public function get_core_loader() {
			return $this->core_loader;
		}
		/**
		 * Get the plugin version.
		 *
		 * @return string
		 */
		public function get_plugin_version() {
			return $this->version;
		}
	}

	// Activate this plugin as last plugin
	add_action(
		'plugins_loaded',
		function () {
			global $sw_cloudflare_pagecache;

			if ( ! isset( $sw_cloudflare_pagecache ) || empty( $sw_cloudflare_pagecache ) ) {
				$sw_cloudflare_pagecache = new SW_CLOUDFLARE_PAGECACHE();
			}
		},
		PHP_INT_MAX
	);

	/**
	 * Register the activation hook.
	 *
	 * @return void
	 */
	register_activation_hook(
		SPC_PATH,
		function () {
			update_option( SW_CLOUDFLARE_PAGECACHE::REDIRECT_KEY, true );
		}
	);
}
