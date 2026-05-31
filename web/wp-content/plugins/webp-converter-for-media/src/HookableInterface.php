<?php

namespace WebpConverter;

/**
 * Interface for class which has action call to integrates with WordPress hooks.
 */
interface HookableInterface {

	/**
	 * Integrates with WordPress hooks.
	 */
	public function init_hooks(): void;
}
