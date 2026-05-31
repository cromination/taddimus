<?php

namespace SPC;

use SPC\Modules\Admin;
use SPC\Modules\Assets_Manager;
use SPC\Modules\Cache_Buster;
use SPC\Modules\Cache_Controller;
use SPC\Modules\Cache_Invalidation_Hooks;
use SPC\Modules\Dashboard;
use SPC\Modules\Fallback_Cache;
use SPC\Modules\Font_Optimizer;
use SPC\Modules\Page_Settings_Metabox;
use SPC\Modules\Database_Optimization;
use SPC\Modules\Frontend;
use SPC\Modules\Heartbeat_Control;
use SPC\Modules\Html_Cache;
use SPC\Modules\HTML_Modifier;
use SPC\Modules\Module_Interface;
use SPC\Modules\Rest_Server;
use SPC\Modules\Settings_Manager;
use SPC\Modules\Speculative_Loading;
use SPC\Modules\Third_Party;
use SPC\Modules\Third_Party_Integrations;
use SPC\Services\Settings_Store;
use SPC\Modules\Metrics_Cleanup;
use SPC\Modules\Preloader_Process;
use SPC\Modules\WP_CLI;

class Loader {
	public const PRODUCT_KEY = 'wp_cloudflare_page_cache';

	/**
	 * Modules.
	 *
	 * @var array $modules Pro modules.
	 */
	private $modules = [];

	/**
	 * Resolve the shared Loader from the global plugin instance.
	 *
	 * Mirrors the {@see \SPC\Services\Settings_Store::get_instance()} pattern so static
	 * facades (e.g. `Cache_Controller::purge_all()`) can reach module instances without
	 * threading the `$sw_cloudflare_pagecache->get_core_loader()` chain through every caller.
	 *
	 * @return self
	 */
	public static function get() {
		global $sw_cloudflare_pagecache;

		return $sw_cloudflare_pagecache->get_core_loader();
	}

	/**
	 * Loader constructor.
	 */
	public function __construct() {
		$this->modules['cache_controller']         = new Cache_Controller();
		$this->modules['cache_invalidation_hooks'] = new Cache_Invalidation_Hooks();
		$this->modules['cache_buster']             = new Cache_Buster();
		$this->modules['html_cache']               = new Html_Cache();
		$this->modules['third_party_integrations'] = new Third_Party_Integrations();
		$this->modules['preloader_process']        = new Preloader_Process();
		$this->modules['fallback_cache']           = new Fallback_Cache();
		$this->modules['frontend']                 = new Frontend();
		$this->modules['heartbeat_control']        = new Heartbeat_Control();
		$this->modules['speculative_loading']      = new Speculative_Loading();
		$this->modules['html_modifier']            = new HTML_Modifier();
		$this->modules['settings_manager']         = new Settings_Manager();
		$this->modules['admin']                    = new Admin();
		$this->modules['dashboard']                = new Dashboard();
		$this->modules['rest_server']              = new Rest_Server();
		$this->modules['third_party']              = new Third_Party();
		$this->modules['metrics_cleanup']          = new Metrics_Cleanup();
		$this->modules['assets_manager']           = new Assets_Manager();
		$this->modules['page_settings_metabox']    = new Page_Settings_Metabox();

		if ( Settings_Store::get_instance()->get( Constants::SETTING_ENABLE_DATABASE_OPTIMIZATION ) ) {
			$this->modules['database_optimization'] = new Database_Optimization();
		}
		$this->modules['font_optimizer'] = new Font_Optimizer();

		if ( defined( 'WP_CLI' ) && \WP_CLI && class_exists( 'WP_CLI_Command' ) ) {
			$this->modules['wp_cli'] = new WP_CLI();
		}
	}

	/**
	 * Get the modules.
	 *
	 * @return array<string, Module_Interface>
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Get the frontend module.
	 *
	 * @return Frontend
	 */
	public function get_frontend_module() {
		return $this->modules['frontend'];
	}

	/**
	 * Get the shared Cache_Controller module instance.
	 *
	 * @return Cache_Controller
	 */
	public function cache_controller() {
		return $this->modules['cache_controller'];
	}

	/**
	 * Get the shared Html_Cache module instance.
	 *
	 * @return Html_Cache
	 */
	public function html_cache() {
		return $this->modules['html_cache'];
	}

	/**
	 * Get the shared Fallback_Cache module instance.
	 *
	 * @return Fallback_Cache
	 */
	public function fallback_cache() {
		return $this->modules['fallback_cache'];
	}

	/**
	 * Load the modules.
	 *
	 * @return void
	 */
	public function load_modules() {
		$modules = apply_filters( 'spc_loaded_modules', $this->modules );

		$valid_modules = array_filter(
			$modules,
			function ( $module ) {
				return $module instanceof Module_Interface;
			}
		);

		array_walk(
			$valid_modules,
			function ( $module ) {
				$module->init();
			}
		);
	}

	/**
	 * Check if we should load the HTML modifier.
	 *
	 * @return bool
	 */
	public static function can_process_html() {
		return version_compare( get_bloginfo( 'version' ), '6.2', '>=' ) && class_exists( 'WP_HTML_Tag_Processor' );
	}

	/**
	 * Check if the current page is cached.
	 *
	 * @return bool
	 */
	public static function is_cached_page() {
		return function_exists( 'swcfpc_is_this_page_cachable' ) && \swcfpc_is_this_page_cachable();
	}
}
