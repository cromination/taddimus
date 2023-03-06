<?php

namespace WebpConverter\Loader;

/**
 * Interface for class that supports method of loading images.
 */
interface LoaderInterface {

	/**
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Returns status if loader is active.
	 *
	 * @return bool
	 */
	public function is_active_loader(): bool;

	/**
	 * Integrates with WordPress hooks.
	 *
	 * @return void
	 */
	public function init_admin_hooks();

	/**
	 * Integrates with WordPress hooks.
	 *
	 * @return void
	 */
	public function init_front_end_hooks();

	/**
	 * Initializes actions for activating loader.
	 *
	 * @param bool $is_debug Is debugging?
	 *
	 * @return void
	 */
	public function activate_loader( bool $is_debug = false );

	/**
	 * Initializes actions for deactivating loader.
	 *
	 * @return void
	 */
	public function deactivate_loader();
}
