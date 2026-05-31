<?php

namespace WebpConverter\Loader;

use WebpConverter\HookableInterface;

/**
 * Adds integration with active method of loading images.
 */
class LoaderIntegrator implements HookableInterface {

	private LoaderInterface $loader;

	public function __construct( LoaderInterface $loader ) {
		$this->loader = $loader;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks(): void {
		add_action( LoaderAbstract::ACTION_NAME, [ $this, 'refresh_active_loader' ], 20, 2 );
		add_action( LoaderAbstract::ACTION_NAME, [ $this, 'refresh_inactive_loader' ] );
		add_action( 'webpc_settings_page_loaded', [ $this, 'load_admin_actions' ], 0 );
		add_action( 'init', [ $this, 'load_front_end_actions' ], 0 );
	}

	/**
	 * @param bool $is_active .
	 * @param bool $is_debug  .
	 *
	 * @internal
	 */
	public function refresh_active_loader( bool $is_active, bool $is_debug = false ): void {
		if ( $is_active && $this->loader->is_active_loader() ) {
			$this->loader->activate_loader( $is_debug );
		}
	}

	/**
	 * @param bool $is_active .
	 *
	 * @internal
	 */
	public function refresh_inactive_loader( bool $is_active ): void {
		if ( ! $is_active || ! $this->loader->is_active_loader() ) {
			$this->loader->deactivate_loader();
		}
	}

	/**
	 * @internal
	 */
	public function load_admin_actions(): void {
		if ( $this->loader->is_active_loader() ) {
			$this->loader->init_admin_hooks();
		}
	}

	/**
	 * @internal
	 */
	public function load_front_end_actions(): void {
		if ( $this->loader->is_active_loader() && ( apply_filters( 'webpc_server_errors', [], true ) === [] ) ) {
			$this->loader->init_front_end_hooks();
		}
	}
}
