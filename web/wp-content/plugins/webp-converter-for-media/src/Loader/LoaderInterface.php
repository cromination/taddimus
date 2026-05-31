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
	 */
	public function init_admin_hooks(): void;

	/**
	 * Integrates with WordPress hooks.
	 */
	public function init_front_end_hooks(): void;

	/**
	 * Initializes actions for activating loader.
	 *
	 * @param bool $is_debug Is debugging?
	 */
	public function activate_loader( bool $is_debug = false ): void;

	/**
	 * Initializes actions for deactivating loader.
	 */
	public function deactivate_loader(): void;
}
