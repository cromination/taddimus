<?php

namespace WebpConverter\Settings\Page;

use WebpConverter\HookableInterface;
use WebpConverter\Notice\NoticeIntegration;
use WebpConverter\Notice\WelcomeNotice;
use WebpConverter\PluginInfo;
use WebpConverter\Service\ViewLoader;
use WebpConverter\Settings\AdminAssets;

/**
 * Adds plugin settings page in admin panel.
 */
class PageIntegration implements HookableInterface {

	const ADMIN_MENU_PAGE = 'webpc_admin_page';

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var ViewLoader
	 */
	private $view_loader;

	public function __construct( PluginInfo $plugin_info, ViewLoader $view_loader = null ) {
		$this->plugin_info = $plugin_info;
		$this->view_loader = $view_loader ?: new ViewLoader( $plugin_info );
	}

	/**
	 * Objects of supported plugin settings pages.
	 *
	 * @var PageInterface[]
	 */
	private $pages = [];

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'admin_menu', [ $this, 'add_settings_page_for_admin' ] );
		add_action( 'network_admin_menu', [ $this, 'add_settings_page_for_network' ] );
	}

	/**
	 * Sets integration for page.
	 *
	 * @param PageInterface $page .
	 *
	 * @return self
	 */
	public function set_page_integration( PageInterface $page ) {
		$this->pages[] = $page;

		return $this;
	}

	/**
	 * Returns URL of plugin settings page.
	 *
	 * @param string|null $action .
	 *
	 * @return string
	 */
	public static function get_settings_page_url( string $action = null ): string {
		if ( ! is_multisite() ) {
			$page_url = menu_page_url( self::ADMIN_MENU_PAGE, false );
		} else {
			$page_url = network_admin_url( 'settings.php?page=' . self::ADMIN_MENU_PAGE );
		}

		if ( $action !== null ) {
			$page_url .= '&action=' . $action;
		}
		return $page_url;
	}

	/**
	 * Adds settings page to menu for non-multisite websites.
	 *
	 * @return void
	 * @internal
	 */
	public function add_settings_page_for_admin() {
		if ( is_multisite() ) {
			return;
		}
		$this->add_settings_page( 'options-general.php' );
	}

	/**
	 * Adds settings page to menu for multisite websites.
	 *
	 * @return void
	 * @internal
	 */
	public function add_settings_page_for_network() {
		$this->add_settings_page( 'settings.php' );
	}

	/**
	 * Creates plugin settings page in WordPress Admin Dashboard.
	 *
	 * @param string $menu_page Parent menu page.
	 *
	 * @return void
	 */
	private function add_settings_page( string $menu_page ) {
		$page = add_submenu_page(
			$menu_page,
			'Converter for Media',
			'Converter for Media',
			'manage_options',
			self::ADMIN_MENU_PAGE,
			[ $this, 'load_settings_page' ]
		);
		add_action( 'load-' . $page, [ $this, 'load_scripts_for_page' ] );
	}

	/**
	 * Loads selected view on plugin settings page.
	 *
	 * @return void
	 * @internal
	 */
	public function load_settings_page() {
		$current_tab_name = $_GET['action'] ?? null; // phpcs:ignore WordPress.Security
		foreach ( $this->pages as $page ) {
			if ( $page->get_slug() === $current_tab_name ) {
				$this->view_loader->load_view(
					$page->get_template_path(),
					array_merge(
						$page->get_template_vars(),
						[
							'menu_items' => array_map(
								function ( PageInterface $settings_page ) use ( $current_tab_name ) {
									return [
										'url'       => self::get_settings_page_url( $settings_page->get_slug() ),
										'title'     => $settings_page->get_label(),
										'is_active' => ( $settings_page->get_slug() === $current_tab_name ),
									];
								},
								$this->pages
							),
						]
					)
				);
			}
		}
	}

	/**
	 * Loads assets on plugin settings page.
	 *
	 * @return void
	 * @internal
	 */
	public function load_scripts_for_page() {
		( new NoticeIntegration( $this->plugin_info, new WelcomeNotice() ) )->set_disable_value();
		( new AdminAssets( $this->plugin_info ) )->init_hooks();
	}
}
