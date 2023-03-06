<?php

namespace WebpConverter\Loader;

use WebpConverter\HookableInterface;

/**
 * Adds integration with active method of loading images.
 */
class LoaderIntegration implements HookableInterface {

	/**
	 * Object of image loader method.
	 *
	 * @var LoaderInterface
	 */
	private $loader;

	public function __construct( LoaderInterface $loader ) {
		$this->loader = $loader;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( LoaderAbstract::ACTION_NAME, [ $this, 'refresh_active_loader' ], 20, 2 );
		add_action( LoaderAbstract::ACTION_NAME, [ $this, 'refresh_inactive_loader' ] );
		add_action( 'webpc_settings_page_loaded', [ $this, 'load_admin_actions' ], 0 );
		add_action( 'init', [ $this, 'load_front_end_actions' ], 0 );
	}

	/**
	 * @param bool $is_active .
	 * @param bool $is_debug  .
	 *
	 * @return void
	 * @internal
	 */
	public function refresh_active_loader( bool $is_active, bool $is_debug = false ) {
		if ( $is_active && $this->loader->is_active_loader() ) {
			$this->loader->activate_loader( $is_debug );
		}
	}

	/**
	 * @param bool $is_active .
	 *
	 * @return void
	 * @internal
	 */
	public function refresh_inactive_loader( bool $is_active ) {
		if ( ! $is_active || ! $this->loader->is_active_loader() ) {
			$this->loader->deactivate_loader();
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function load_admin_actions() {
		if ( $this->loader->is_active_loader() ) {
			$this->loader->init_admin_hooks();
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function load_front_end_actions() {
		if ( $this->loader->is_active_loader() && ( apply_filters( 'webpc_server_errors', [], true ) === [] ) ) {
			$this->loader->init_front_end_hooks();
		}
	}
}
