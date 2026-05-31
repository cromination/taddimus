<?php

namespace WebpConverter\Settings;

use WebpConverter\HookableInterface;
use WebpConverter\PluginInfo;

/**
 * Initializes loading of assets in admin panel.
 */
class AdminAssetsLoader implements HookableInterface {

	const CSS_FILE_PATH = 'assets/build/css/styles.css';
	const JS_FILE_PATH  = 'assets/build/js/scripts.js';

	private PluginInfo $plugin_info;

	public function __construct( PluginInfo $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'load_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Loads CSS assets.
	 *
	 * @internal
	 */
	public function load_styles(): void {
		wp_register_style(
			'converter-for-media',
			$this->plugin_info->get_plugin_directory_url() . self::CSS_FILE_PATH,
			[],
			$this->plugin_info->get_plugin_version()
		);
		wp_enqueue_style( 'converter-for-media' );
	}

	/**
	 * Loads JavaScript assets.
	 *
	 * @internal
	 */
	public function load_scripts(): void {
		wp_register_script(
			'converter-for-media',
			$this->plugin_info->get_plugin_directory_url() . self::JS_FILE_PATH,
			[],
			$this->plugin_info->get_plugin_version(),
			true
		);
		wp_enqueue_script( 'converter-for-media' );
	}
}
