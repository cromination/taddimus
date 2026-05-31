<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Modules\Module_Interface;
use SPC\Modules\Preloader_Process;
use SPC\Services\Metrics;
use SPC\Services\SDK_Integrations;
use SPC\Services\Settings_Store;
use SPC\Utils\Assets_Handler;
use SPC\Utils\Helpers;
use SPC\Utils\I18n;
use SPC\Services\Notices_Handler;

class Dashboard implements Module_Interface {
	public const PAGE_SLUG               = 'super-page-cache';
	private const SPC_DOCS_ENDPOINT      = 'https://api.themeisle.com/spc/help';
	private const SPC_DOCS_API_CACHE_KEY = 'spc_docs_api_cache';

	/**
	 * The SDK service.
	 *
	 * @var SDK_Integrations
	 */
	private $sdk_service;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->sdk_service = new SDK_Integrations();
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'export_config' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_print_styles', [ $this, 'truncate_menu_items' ], 100 );
		add_action( 'admin_print_styles', [ $this, 'hide_menu_items' ], 100 );
		add_action( 'admin_print_styles', [ $this, 'hide_all_notices' ], 100 );
		add_filter( 'themeisle_sdk_blackfriday_data', [ $this->sdk_service, 'add_black_friday_data' ] );
	}

	/**
	 * Truncate the menu item name so it doesn't break the layout of the WP Admin sidebar.
	 *
	 * @return void
	 */
	public function truncate_menu_items() {
		$props = [
			'white-space'   => 'nowrap',
			'overflow'      => 'hidden',
			'text-overflow' => 'ellipsis',
			'padding-left'  => '0 !important',
			'max-width'     => '100px',
		];

		if ( Helpers::is_spc_admin_page() ) {
			$props['padding-right'] = '0 !important';
		}

		echo '<style>
			#toplevel_page_super-page-cache a[target="_blank"] {
				background-color: green !important;
			}

			#toplevel_page_super-page-cache .tsdk-upg-menu-item { 
				color: #fff;
			}

			#toplevel_page_super-page-cache .wp-menu-name {' . implode(
			';',
			array_map(
				function ( $key, $value ) {
					return $key . ':' . $value;
				},
				array_keys( $props ),
				$props
			)
		) . '}

			@media (max-width: 782px) {
				#toplevel_page_super-page-cache .wp-menu-name {
					padding-left: 36px !important;
					max-width: 115px !important;
				}

				#toplevel_page_super-page-cache.wp-menu-open .wp-menu-name {
					max-width: 155px !important;
				}
			}
		</style>';
	}

	public function hide_menu_items() {
		$is_cache_enabled = Settings_Store::get_instance()->is_cache_enabled();
		$style            = '<style>';

		if ( ! $is_cache_enabled ) {
			$style .= '#toplevel_page_super-page-cache li:has(a[href*="' . self::PAGE_SLUG . '-settings"]),';
			$style .= '#toplevel_page_super-page-cache li:has(a[href*="' . self::PAGE_SLUG . '-import-export"]),';
			$style .= '#toplevel_page_super-page-cache li:has(a[href*="' . self::PAGE_SLUG . '-license"]) {';
			$style .= 'display: none; }';
		}

		$style .= '</style>';

		echo $style;
	}

	public function hide_all_notices() {
		if ( ! Helpers::is_spc_admin_page() ) {
			return;
		}

		$style  = '<style>';
		$style .= '#wpbody-content > *:not(#spc-dashboard) {
			display: none !important;
		}';
		$style .= '</style>';

		echo $style;
	}

	/**
	 * Add admin menu pages.
	 *
	 * @return void
	 */
	public function add_admin_menu_pages() {
		$hooks = [];

		$hooks[] = add_menu_page(
			__( 'Dashboard - Super Page Cache', 'wp-cloudflare-page-cache' ),
			__( 'Super Page Cache', 'wp-cloudflare-page-cache' ),
			'manage_options',
			self::PAGE_SLUG,
			'__return_empty_string',
			Helpers::get_menu_icon( Helpers::is_spc_admin_page() ? '#fff' : '#a7aaad' )
		);

		$hooks[] = add_submenu_page(
			self::PAGE_SLUG,
			__( 'Dashboard - Super Page Cache', 'wp-cloudflare-page-cache' ),
			__( 'Dashboard', 'wp-cloudflare-page-cache' ),
			'manage_options',
			self::PAGE_SLUG,
			function () {
				$this->render_admin_page( 'dashboard' );
			}
		);

		$hooks[] = add_submenu_page(
			self::PAGE_SLUG,
			__( 'Settings - Super Page Cache', 'wp-cloudflare-page-cache' ),
			__( 'Settings', 'wp-cloudflare-page-cache' ),
			'manage_options',
			self::PAGE_SLUG . '-settings',
			function () {
				$this->render_admin_page( 'settings' );
			}
		);

			$hooks[] = add_submenu_page(
				self::PAGE_SLUG,
				__( 'Import/Export Settings - Super Page Cache', 'wp-cloudflare-page-cache' ),
				__( 'Import/Export', 'wp-cloudflare-page-cache' ),
				'manage_options',
				self::PAGE_SLUG . '-import-export',
				function () {
					$this->render_admin_page( 'import-export' );
				}
			);

		if ( defined( 'SPC_PRO_PATH' ) ) {
			$hooks[] = add_submenu_page(
				self::PAGE_SLUG,
				__( 'License - Super Page Cache', 'wp-cloudflare-page-cache' ),
				__( 'License', 'wp-cloudflare-page-cache' ),
				'manage_options',
				self::PAGE_SLUG . '-license',
				function () {
					$this->render_admin_page( 'license' );
				}
			);
		}

		$hooks[] = add_submenu_page(
			self::PAGE_SLUG,
			__( 'Help - Super Page Cache', 'wp-cloudflare-page-cache' ),
			__( 'Help', 'wp-cloudflare-page-cache' ),
			'manage_options',
			self::PAGE_SLUG . '-help',
			function () {
				$this->render_admin_page( 'help' );
			}
		);

		foreach ( $hooks as $hook ) {
			add_action(
				"load-$hook",
				function () use ( $hook ) {
					$this->load_sdk_integrations( $hook );
				}
			);
		}
	}

	/**
	 * Render the admin page.
	 *
	 * @param string $hook The hook of the page to render.
	 *
	 * @return void
	 */
	private function render_admin_page( $hook = 'dashboard' ) {
		add_thickbox();

		echo '<div id="spc-dashboard" data-landing="' . $hook . '">
		<style>#adminmenuwrap { position: fixed !important; }</style>
		</div>';
	}

	/**
	 * Enqueue assets for the dashboard.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ) {
		if ( ! Helpers::is_spc_admin_page() ) {
			return;
		}

		Assets_Handler::enqueue_style( 'spc-dashboard', 'dashboard' );
		Assets_Handler::enqueue_script( 'spc-dashboard', 'dashboard', [], $this->get_localization() );
	}


	/**
	 * Get localization data for the dashboard.
	 *
	 * @return array
	 */
	private function get_localization() {
		return apply_filters(
			'spc_dashboard_localization',
			[
				// Core API & Security
				'api'                                 => rest_url( Rest_Server::REST_NAMESPACE ),
				'nonce'                               => wp_create_nonce( 'wp_rest' ),

				// Plugin Information
				'version'                             => SWCFPC_VERSION,
				'isPro'                               => defined( 'SPC_PRO_PATH' ),
				'licenseData'                         => get_option( $this->sdk_service->get_license_option_key(), (object) [] ),

				// Assets & UI
				'logoURL'                             => Assets_Handler::get_image_url( 'logo.svg' ),
				'i18n'                                => I18n::get_dashboard_translations(),
				'displayWizard'                       => ! Settings_Store::get_instance()->is_cache_enabled(),

				// Settings & Configuration
				'settings'                            => $this->get_settings(),
				'zoneIdList'                          => Settings_Store::get_instance()->get( Constants::ZONE_ID_LIST ),

				// File System Permissions
				'wpConfigWritable'                    => is_writable( Helpers::get_wp_config_path() ),
				'wpContentWritable'                   => is_writable( WP_CONTENT_DIR ),

				// Third Party Integrations & Conflicts
				'thirdPartyIntegrations'              => Admin::get_third_party_view_map(),
				'thirdPartyVisible'                   => Admin::should_load_third_party_tab(),
				'conflicts'                           => Notices_Handler::is_dismissed( Notices_Handler::CONFLICTS_NOTICE ) ? [] : Admin::get_conflicts( true ),
				'robinData'                           => $this->get_robin_data(),

				// Cache & Performance
				'preloaderLocked'                     => ! Preloader_Process::can_start(),
				'metrics'                             => Metrics::all(),
				'ruleNeedsRepair'                     => get_option( Constants::KEY_RULE_UPDATE_FAILED ),
				'hasOverdueJobs'                      => $this->has_overdue_jobs(),
				'invalidEncryptionState'              => Settings_Store::get_instance()->should_show_invalid_encryption_notice(),
				'cloudflareConnected'                 => Settings_Store::get_instance()->is_cloudflare_connected(),
				'testCacheUrl'                        => SWCFPC_PLUGIN_URL . 'assets/testcache.html',

				// Cron Jobs
				'cronjobURL'                          => $this->get_cronjob_url( 'preloader' ),
				'cronjobPurgeURL'                     => $this->get_cronjob_url( 'purge' ),

				// WordPress Data
				'wordpressMenus'                      => $this->get_wp_menus_options(),
				'wordpressRoles'                      => $this->get_wordpress_roles(),
				'homeURL'                             => home_url(),

				// Admin URLs & Navigation
				'mainPageURL'                         => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'pluginsPageURL'                      => add_query_arg( [ Admin::CONFLICTS_QUERY_ARG => '1' ], admin_url( 'plugins.php' ) ),
				'rootPagePrefix'                      => self::PAGE_SLUG . '-',
				'logDownloadURL'                      => add_query_arg( [ 'swcfpc_download_log' => 1 ], admin_url() ),
				'logViewURL'                          => add_query_arg( [ 'swcfpc_download_log' => 'view' ], admin_url() ),
				'configExportURL'                     => add_query_arg( [ 'swcfpc_export_config' => 1 ], admin_url() ),

				// Database Optimization
				'databaseOptimizationScheduleOptions' => Database_Optimization::get_schedule_options(),

				// External Links & Support
				'upsellURL'                           => esc_url( tsdk_translate_link( tsdk_utmify( 'https://themeisle.com/plugins/super-page-cache-pro/upgrade/', 'replace:campaign', 'spc' ) ) ),
				'directSupportURL'                    => tsdk_support_link( SWCFPC_BASEFILE ),
				'help'                                => $this->get_help_data(),
				'cloudflareSettingsURL'               => admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-settings#cloudflare' ),
			]
		);
	}

	/**
	 * Load dependencies for dashboard.
	 */
	public function load_sdk_integrations( $hook ) {
		add_filter( $this->sdk_service->get_product_key() . '_hide_license_notices', '__return_true' );
		add_filter( 'themeisle-sdk/survey/' . SWCFPC_PRODUCT_SLUG, [ $this->sdk_service, 'get_survey_metadata' ], 10, 2 );
		do_action( 'themeisle_internal_page', SWCFPC_PRODUCT_SLUG, $hook );
	}

	private function get_settings() {
		$settings_manager = new Settings_Manager();
		$data             = $settings_manager->get_fields();
		$settings_store   = Settings_Store::get_instance();

		$result = [];
		foreach ( $data as $key => $config ) {
			if ( ! isset( $config['default'] ) ) {
				$config['default'] = Settings_Manager::SETTINGS_FALLBACK_DEFAULT_TYPE_VALUE_MAP[ $config['type'] ];
			}

			$setting   = $settings_store->get_with_source( $key, $config['default'] );
			$has_value = ! empty( $setting['value'] );

			if ( $settings_manager->is_encrypted_field( $key ) ) {
				$has_value        = $settings_store->has_stored_encrypted_value( $key );
				$setting['value'] = '';
			}

			$result[ $key ] = array_merge(
				$config,
				[
					'value'      => $setting['value'],
					'hasValue'   => $has_value,
					'source'     => $setting['source'],
					'overridden' => Settings_Store::CONFIG_SOURCE_CONST === $setting['source'],
				]
			);
		}

		return $result;
	}

	/**
	 * Get the Robin Image Optimizer data.
	 *
	 * @return array<string, mixed>
	 */
	private function get_robin_data() {
		$cache_key = 'spc_robin_data';
		$data      = get_transient( $cache_key );

		if ( empty( $data ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$data = plugins_api( 'plugin_information', [ 'slug' => 'robin-image-optimizer' ] );

			if ( ! is_wp_error( $data ) ) {
				set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
			}
		}

		if ( ! is_object( $data ) ) {
			$data                  = (object) [];
			$data->num_ratings     = 124;
			$data->rating          = 88;
			$data->active_installs = 100000;
		}

		$rating          = (int) $data->rating * 5 / 100;
		$rating          = number_format( $rating, 1 );
		$active_installs = number_format( $data->active_installs );

		$installed = file_exists( WP_PLUGIN_DIR . '/robin-image-optimizer/robin-image-optimizer.php' );

		return [
			'show'           => ( ! defined( 'WRIO_PLUGIN_VERSION' ) && ! defined( 'OPTML_VERSION' ) ),
			'logo'           => Assets_Handler::get_image_url( 'robin-logo.jpg' ),
			// translators: %1$s: rating, %2$d: number of reviews.
			'ratingByline'   => sprintf( __( '%1$s out of 5 stars (%2$d reviews)', 'wp-cloudflare-page-cache' ), $rating, $data->num_ratings ),
			// translators: %s: number of active installations.
			'activeInstalls' => sprintf( __( '%s+ Active installations', 'wp-cloudflare-page-cache' ), $active_installs ),
			'cta'            => $installed ? __( 'Activate Robin Image Optimizer', 'wp-cloudflare-page-cache' ) : __( 'Install Robin Image Optimizer', 'wp-cloudflare-page-cache' ),
			'thickboxURL'    => add_query_arg(
				[
					'tab'       => 'plugin-information',
					'plugin'    => 'robin-image-optimizer',
					'TB_iframe' => 'true',
					'width'     => '600',
					'height'    => '500',
				],
				network_admin_url( 'plugin-install.php' )
			),
		];
	}

	/**
	 * Get the cronjob URL.
	 *
	 * @param string $type The type of link purge|preloader
	 *
	 * @return string
	 */
	private function get_cronjob_url( $type = 'preloader' ) {
		if ( ! in_array( $type, [ 'preloader', 'purge' ], true ) ) {
			return '';
		}

		$args = $type === 'preloader' ? [
			'swcfpc-preloader' => '1',
			'swcfpc-sec-key'   => 'replace:cf_preloader_url_secret_key',
		] : [
			'swcfpc-purge-all' => '1',
			'swcfpc-sec-key'   => 'replace:cf_purge_url_secret_key',
		];

		if ( ! Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_CACHE_BUSTER ) ) {
			$args[ SWCFPC_CACHE_BUSTER ] = '1';
		}

		return add_query_arg( $args, site_url() );
	}

	/**
	 * Get the WordPress menus options.
	 *
	 * @return array
	 */
	private function get_wp_menus_options() {
		$wordpress_menus = wp_get_nav_menus();
		if ( empty( $wordpress_menus ) || ! is_array( $wordpress_menus ) ) {
			return [];
		}

		$wordpress_menus_options = [];

		foreach ( $wordpress_menus as $menu ) {
			$wordpress_menus_options[ $menu->term_id ] = $menu->name;
		}

		return $wordpress_menus_options;
	}

	/**
	 * Get the WordPress roles.
	 *
	 * @return array
	 */
	private function get_wordpress_roles() {
		global $wp_roles;
		$wordpress_roles = [];

		foreach ( $wp_roles->roles as $role => $role_data ) {
			if ( $role === 'administrator' ) {
				continue;
			}

			$wordpress_roles[ $role ] = $role_data['name'];
		}

		return $wordpress_roles;
	}

	/**
	 * Export the plugin config as a JSON file.
	 *
	 * @return void
	 */
	public function export_config() {
		if ( ! isset( $_GET['swcfpc_export_config'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$config   = json_encode( Settings_Store::get_instance()->get_config_for_export() );
		$filename = 'swcfpc_config.json';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$filename}" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: Keep-Alive' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $config ) );

		die( $config );
	}

	/**
	 * Check if there are overdue jobs.
	 *
	 * @return bool
	 */
	private function has_overdue_jobs() {
		if ( ! class_exists( '\ActionScheduler_Store' ) ) {
			return false;
		}

		$store        = \ActionScheduler_Store::instance();
		$one_hour_ago = as_get_datetime_object();
		$one_hour_ago->modify( '-1 hour' );

		$args = array(
			'group'        => Constants::ACTION_SCHEDULER_GROUP,
			'status'       => \ActionScheduler_Store::STATUS_PENDING,
			'date'         => $one_hour_ago,
			'date_compare' => '<=',
			'per_page'     => 1,
		);

		$count = $store->query_actions( $args, 'count' );

		return $count > 0;
	}

	/**
	 * Get the help data.
	 *
	 * @return array
	 */
	private function get_help_data() {
		$empty = [
			'popular'    => [],
			'categories' => [],
		];

		$cached_data = get_transient( self::SPC_DOCS_API_CACHE_KEY );

		if ( $cached_data ) {
			return $cached_data;
		}

		try {
			$response = wp_remote_get( defined( 'SPC_DOCS_ENDPOINT' ) ? SPC_DOCS_ENDPOINT : self::SPC_DOCS_ENDPOINT );

			if ( is_wp_error( $response ) ) {
				return $empty;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $data ) || ! isset( $data['popular'], $data['categories'] ) ) {
				return $empty;
			}

			set_transient( self::SPC_DOCS_API_CACHE_KEY, $data, 12 * HOUR_IN_SECONDS );

			return $data;
		} catch ( \Exception $e ) {
			return $empty;
		}
	}
}
