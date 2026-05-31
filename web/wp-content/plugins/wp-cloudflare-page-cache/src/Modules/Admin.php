<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\SDK_Integrations;
use SPC\Services\Settings_Store;
use SPC\Utils\Assets_Handler;
use SPC\Utils\Helpers;
use SPC\Utils\I18n;
use SPC\Utils\Logger;

class Admin implements Module_Interface {

	public const CONFLICTS_QUERY_ARG = 'spc_conflicts';

	/**
	 * SDK service.
	 *
	 * @var SDK_Integrations $sdk_service
	 */
	private $sdk_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->sdk_service = new SDK_Integrations();
	}

	public function init() {
		add_filter( 'plugin_row_meta', [ $this, 'add_plugin_meta_links' ], 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( SPC_PATH ), [ $this, 'add_plugin_action_links' ] );
		add_action( 'admin_init', [ $this, 'redirect_to_settings' ] );
		add_action( 'admin_notices', [ $this, 'failed_rule_update_notice' ] );
		add_action( 'admin_notices', [ $this, 'credentials_encryption_notice' ] );
		add_filter( 'all_plugins', [ $this, 'filter_conflicting_plugins' ] );

		add_filter( $this->sdk_service->get_product_key() . '_logger_data', [ $this->sdk_service, 'get_logger_data' ] );
		add_filter(
			$this->sdk_service->get_product_key() . '_about_us_metadata',
			[ $this->sdk_service, 'get_about_us_metadata' ]
		);

		add_action( 'init', [ Logger::class, 'download_handler' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'load_custom_wp_admin_styles_and_script' ] );
		add_filter( 'script_loader_tag', [ $this, 'modify_script_attributes' ], 10, 2 );

		if ( is_admin() && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			add_filter( 'post_row_actions', [ $this, 'add_post_row_actions' ], PHP_INT_MAX, 2 );
			add_filter( 'page_row_actions', [ $this, 'add_post_row_actions' ], PHP_INT_MAX, 2 );
		}

		if ( ! Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_PURGE_OPTION_TOOLBAR ) && Helpers::can_current_user_purge_cache() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'load_toolbar_styles_and_script' ] );
			add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], PHP_INT_MAX );
		}
	}

	/**
	 * Redirect to the settings page after activation.
	 */
	public function redirect_to_settings() {
		if ( ! get_option( \SW_CLOUDFLARE_PAGECACHE::REDIRECT_KEY, false ) ) {
			return;
		}

		delete_option( \SW_CLOUDFLARE_PAGECACHE::REDIRECT_KEY );
		wp_safe_redirect( admin_url( 'admin.php?page=' . Dashboard::PAGE_SLUG ) );

		exit;
	}

	/**
	 * Adds settings link to the plugins page.
	 *
	 * @param string[] $links The plugin action links.
	 *
	 * @return array|string[]
	 */
	public function add_plugin_action_links( $links ) {
		if ( is_array( $links ) ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						'page',
						Dashboard::PAGE_SLUG . '-settings',
						admin_url( 'admin.php' )
					)
				),
				__( 'Settings', 'wp-cloudflare-page-cache' )
			);
		}

		return $links;
	}

	/**
	 * Adds plugin meta links.
	 *
	 * @param array $meta_fields The plugin meta fields.
	 * @param string $file The plugin file.
	 *
	 * @return array
	 */
	public function add_plugin_meta_links( $meta_fields, $file ) {
		if ( plugin_basename( SPC_PATH ) === $file && is_array( $meta_fields ) ) {
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

			$meta_fields[] = sprintf(
				'<a href="%s" target="_blank" title="%s"><i>%s</i></a>',
				esc_url( SWCFPC_PLUGIN_REVIEWS_URL . '?rate=5#new-post' ),
				esc_html__( 'Rate', 'wp-cloudflare-page-cache' ),
				str_repeat( $svg, 5 )
			);
		}

		return $meta_fields;
	}

	/**
	 * Failed rule update notice.
	 *
	 * @return void
	 */
	public function failed_rule_update_notice() {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'super-page-cache' ) !== false ) {
			return;
		}

		if (
			! get_option( Constants::KEY_RULE_UPDATE_FAILED, false ) ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		$admin_page_url = add_query_arg(
			[
				'page' => 'super-page-cache-settings',
			],
			admin_url( 'admin.php#cloudflare' )
		);

		$logo_url = Assets_Handler::get_image_url( 'logo.svg' );

		?>
		<style>
			.notice.spc-rule {
				display: flex;
				padding: 20px;
				border-left-color: #ca6308;
				gap: 20px;
			}

			.spc-rule p {
				margin-bottom: 10px;
			}

			.spc-rule h3 {
				margin: 0 0 10px;
			}

			.spc-rule .spc-logo {
				width: 40px;
				height: 40px;
			}
		</style>

		<div class="notice notice-warning spc-rule">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="Super Page Cache" class="spc-logo">
			<div>
				<h3><?php echo esc_html( I18n::get( 'ruleFixTitle' ) ); ?></h3>
				<p>
					<?php
					echo wp_kses_post( I18n::get( 'ruleFixDescription' ) );
					?>
				</p>
				<a href="<?php echo esc_url( $admin_page_url ); ?>" class="button button-secondary">
					<?php _e( 'Settings page', 'wp-cloudflare-page-cache' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Display a notice when encrypted credentials can no longer be decrypted.
	 *
	 * @return void
	 */
	public function credentials_encryption_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! \SPC\Services\Settings_Store::get_instance()->should_show_invalid_encryption_notice() ) {
			return;
		}

		$cloudflare_settings_url = admin_url( 'admin.php?page=' . Dashboard::PAGE_SLUG . '-settings#cloudflare' );

		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'Super Page Cache could not decrypt your stored Cloudflare credentials. This usually means your WordPress secret keys changed.', 'wp-cloudflare-page-cache' ); ?>
				<a href="<?php echo esc_url( $cloudflare_settings_url ); ?>">
					<?php esc_html_e( 'Open Cloudflare settings and enter your credentials again.', 'wp-cloudflare-page-cache' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get third party compatibilities.
	 *
	 * @return array
	 */
	public static function get_third_party_view_map() {
		return apply_filters(
			'swcfpc_admin_third_party_compatibilities_view_map',
			[
				'woocommerce'       => is_plugin_active( 'woocommerce/woocommerce.php' ),
				'edd'               => is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ),
				'autoptimize'       => is_plugin_active( 'autoptimize/autoptimize.php' ),
				'w3tc'              => is_plugin_active( 'w3-total-cache/w3-total-cache.php' ),
				'litespeed_cache'   => is_plugin_active( 'litespeed-cache/litespeed-cache.php' ),
				'hummingbird'       => is_plugin_active( 'hummingbird-performance/wp-hummingbird.php' ),
				'wp_optimize'       => is_plugin_active( 'wp-optimize/wp-optimize.php' ),
				'flying_press'      => is_plugin_active( 'flying-press/flying-press.php' ),
				'wp_rocket'         => is_plugin_active( 'wp-rocket/wp-rocket.php' ),
				'wp_asset_cleanup'  => is_plugin_active( 'wp-asset-clean-up/wpacu.php' ),
				'nginx_helper'      => is_plugin_active( 'nginx-helper/nginx-helper.php' ),
				'wp_performance'    => is_plugin_active( 'wp-performance/wp-performance.php' ),
				'yasr'              => is_plugin_active( 'yet-another-stars-rating/yet-another-stars-rating.php' ) || is_plugin_active( 'yet-another-stars-rating-premium/yet-another-stars-rating.php' ),
				'swift_performance' => is_plugin_active( 'swift-performance-lite/performance.php' ) || is_plugin_active( 'swift-performance/performance.php' ),
				'siteground'        => Third_Party_Integrations::is_siteground_supercacher_enabled(),
				'wp_engine'         => Third_Party_Integrations::can_wpengine_cache_be_purged(),
				'spinup_wp'         => Third_Party_Integrations::can_spinupwp_cache_be_purged(),
				'kinsta'            => Third_Party_Integrations::can_kinsta_cache_be_purged(),
			]
		);
	}

	/**
	 * Checks if the third party tab should be loaded.
	 *
	 * @return bool
	 */
	public static function should_load_third_party_tab() {
		foreach ( self::get_third_party_view_map() as $view_id => $enabled ) {
			if ( $enabled ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter plugins to show only conflicting ones when self::CONFLICTS_QUERY_ARG is present.
	 *
	 * @param array $plugins All plugins.
	 *
	 * @return array
	 */
	public function filter_conflicting_plugins( $plugins ) {
		if ( ! is_admin() || ! isset( $_GET[ self::CONFLICTS_QUERY_ARG ] ) || $_GET[ self::CONFLICTS_QUERY_ARG ] !== '1' ) {
			return $plugins;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'plugins' ) {
			return $plugins;
		}

		$conflicting_plugins = array_keys( self::get_conflicts() );

		// Filter plugins to only show conflicting ones
		return array_intersect_key( $plugins, array_flip( $conflicting_plugins ) );
	}

	/**
	 * Get active conflicting plugins.
	 *
	 * @param bool $names_only Whether to return only the plugin names.
	 *
	 * @return array<string, string> | string[]
	 */
	public static function get_conflicts( $names_only = false ) {
		$plugins = [
			'wp-rocket/wp-rocket.php'                    => 'WP Rocket',
			'wp-optimize/wp-optimize.php'                => 'WP Optimize',
			'autoptimize/autoptimize.php'                => 'Autoptimize',
			'wp-super-cache/wp-cache.php'                => 'WP Super Cache',
			'w3-total-cache/w3-total-cache.php'          => 'W3 Total Cache',
			'litespeed-cache/litespeed-cache.php'        => 'LiteSpeed Cache',
			'wp-fastest-cache/wpFastestCache.php'        => 'WP Fastest Cache',
			'sg-cachepress/sg-cachepress.php'            => 'SiteGround Optimizer',
			'swift-performance/performance.php'          => 'Swift Performance Pro',
			'swift-performance-lite/performance.php'     => 'Swift Performance',
			'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird Performance',
		];

		$enabled = array_filter(
			$plugins,
			function ( $plugin_path ) {
				return is_plugin_active( $plugin_path );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( $names_only ) {
			return array_values( $enabled );
		}

		return $enabled;
	}

	/**
	 * Enqueue admin-side styles and scripts on admin screens.
	 */
	public function load_custom_wp_admin_styles_and_script(): void {
		if ( $this->should_skip_custom_assets() ) {
			return;
		}

		$this->register_admin_assets();

		wp_enqueue_style( 'swcfpc_admin_css' );
		wp_enqueue_script( 'swcfpc_admin_js' );

		if ( ! Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_PURGE_OPTION_TOOLBAR ) && Helpers::can_current_user_purge_cache() ) {
			$this->register_toolbar_assets();

			wp_enqueue_style( 'swcfpc_admin_css' );
			wp_enqueue_script( 'swcfpc_toolbar_js' );
		}
	}

	/**
	 * Enqueue toolbar-only styles and scripts on the frontend for logged-in users.
	 */
	public function load_toolbar_styles_and_script(): void {
		if ( $this->should_skip_custom_assets() ) {
			return;
		}

		$this->register_toolbar_assets();

		wp_enqueue_style( 'swcfpc_admin_css' );
		wp_enqueue_script( 'swcfpc_toolbar_js' );
	}

	private function register_admin_assets(): void {
		wp_register_style( 'swcfpc_admin_css', SWCFPC_PLUGIN_URL . 'assets/css/style.min.css', [], SWCFPC_VERSION );
		wp_register_script( 'swcfpc_admin_js', SWCFPC_PLUGIN_URL . 'assets/js/backend.min.js', [], SWCFPC_VERSION, true );
		wp_localize_script(
			'swcfpc_admin_js',
			'swcfpcOptions',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spc-ajax-nonce' ),
			]
		);
	}

	private function register_toolbar_assets(): void {
		wp_register_style( 'swcfpc_admin_css', SWCFPC_PLUGIN_URL . 'assets/css/style.min.css', [], SWCFPC_VERSION );
		wp_register_script( 'swcfpc_toolbar_js', SWCFPC_PLUGIN_URL . 'assets/js/toolbar.min.js', [], SWCFPC_VERSION, true );
		wp_localize_script(
			'swcfpc_toolbar_js',
			'swcfpcOptions',
			[
				'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'spc-ajax-nonce' ),
				'cacheEnabled'        => Settings_Store::get_instance()->get( Constants::SETTING_CF_CACHE_ENABLED ),
				'purgeConfirmMessage' => __( 'Purge the entire cache? All cached pages will be regenerated on the next visit, which may briefly slow down your site.', 'wp-cloudflare-page-cache' ),
			]
		);
	}

	private function should_skip_custom_assets(): bool {
		$screen = ( is_admin() && function_exists( 'get_current_screen' ) ) ? get_current_screen() : false;

		$page_action = $_GET['action'] ?? false;

		$on_oxygen_ct_builder_page = isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] === 'true';
		$on_oxygen_builder_page    = ( substr( $page_action, 0, strlen( 'oxy_render' ) ) === 'oxy_render' );

		return ( function_exists( 'amp_is_request' ) && ( ! is_admin() && amp_is_request() ) ) ||
			( function_exists( 'ampforwp_is_amp_endpoint' ) && ( ! is_admin() && ampforwp_is_amp_endpoint() ) ) ||
			( is_object( $screen ) && $screen->base === 'woofunnels_page_wfob' ) ||
			is_customize_preview() ||
			$on_oxygen_ct_builder_page ||
			$on_oxygen_builder_page;
	}

	public function modify_script_attributes( string $tag, string $handle ): string {
		$plugin_scripts = [
			'swcfpc_admin_js',
			'swcfpc_toolbar_js',
		];

		if ( ! empty( $tag ) && in_array( $handle, $plugin_scripts, true ) ) {
			return str_replace( ' id', ' defer id', $tag );
		}

		return $tag;
	}

	public function add_toolbar_items( \WP_Admin_Bar $admin_bar ): void {
		$screen = is_admin() ? get_current_screen() : false;

		if (
			( function_exists( 'amp_is_request' ) && ( ! is_admin() && amp_is_request() ) ) ||
			( function_exists( 'ampforwp_is_amp_endpoint' ) && ( ! is_admin() && ampforwp_is_amp_endpoint() ) ) ||
			( is_object( $screen ) && $screen->base === 'woofunnels_page_wfob' ) ||
			is_customize_preview()
		) {
			return;
		}

		if ( ! Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_PURGE_OPTION_TOOLBAR ) ) {

			$swpfpc_toolbar_container_url_query_arg_admin = [
				'page'              => Dashboard::PAGE_SLUG,
				SWCFPC_CACHE_BUSTER => 1,
			];

			if ( Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_CACHE_BUSTER ) ) {
				$swpfpc_toolbar_container_url_query_arg_admin = [
					'page' => Dashboard::PAGE_SLUG,
				];
			}

			$admin_bar->add_menu(
				[
					'id'    => 'wp-cloudflare-super-page-cache-toolbar-container',
					'title' => '<span class="ab-icon"></span><span class="ab-label">' . __( 'Super Page Cache', 'wp-cloudflare-page-cache' ) . '</span>',
					'href'  => current_user_can( 'manage_options' ) ? add_query_arg( $swpfpc_toolbar_container_url_query_arg_admin, admin_url( 'admin.php' ) ) : '#',
				]
			);

			if ( Settings_Store::get_instance()->get( Constants::SETTING_CF_CACHE_ENABLED ) ) {
				global $post;

				$admin_bar->add_menu(
					[
						'id'     => 'wp-cloudflare-super-page-cache-toolbar-purge-all',
						'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
						'title'  => __( 'Purge whole cache', 'wp-cloudflare-page-cache' ),
						'href'   => '#',
					]
				);

				if ( Settings_Store::get_instance()->get( Constants::SETTING_PURGE_ONLY_HTML ) ) {

					$admin_bar->add_menu(
						[
							'id'     => 'wp-cloudflare-super-page-cache-toolbar-force-purge-everything',
							'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
							'title'  => __( 'Force purge everything', 'wp-cloudflare-page-cache' ),
							'href'   => '#',
						]
					);
				}

				if ( is_object( $post ) ) {

					$admin_bar->add_menu(
						[
							'id'     => 'wp-cloudflare-super-page-cache-toolbar-purge-single',
							'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
							'title'  => __( 'Purge cache for this page only', 'wp-cloudflare-page-cache' ),
							'href'   => "#{$post->ID}",
						]
					);
				}

				if ( ! is_admin() && Settings_Store::get_instance()->get( Constants::SETTING_ENABLE_ASSETS_MANAGER ) ) {
					$admin_bar->add_menu(
						[
							'id'    => 'wp-cloudflare-super-page-cache-toolbar-asset-manager',
							'title' => '<div style="display: flex; align-items: center;"><span class="ab-icon dashicons dashicons-editor-code"></span><span class="ab-label">' . __( 'Assets Manager', 'wp-cloudflare-page-cache' ) . '</span></div>',
							'href'  => add_query_arg( Assets_Manager::ASSETS_MANAGER_QUERY_VAR, 'yes', $_SERVER['REQUEST_URI'] ),
						]
					);
				}
			}
		}
	}

	/**
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public function add_post_row_actions( array $actions, \WP_Post $post ): array {
		if ( ! in_array( $post->post_type, [ 'shop_order', 'shop_subscription' ], true ) ) {
			$actions['swcfpc_single_purge'] = '<a class="swcfpc_action_row_single_post_cache_purge" data-post_id="' . $post->ID . '" href="#" target="_blank">' . __( 'Purge Cache', 'wp-cloudflare-page-cache' ) . '</a>';
		}

		return $actions;
	}
}
