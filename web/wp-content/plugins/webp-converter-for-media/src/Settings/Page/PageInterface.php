<?php

namespace WebpConverter\Settings\Page;

/**
 * Interface for class that supports tab in plugin settings page.
 */
interface PageInterface {

	/**
	 * @return string|null
	 */
	public function get_slug();

	/**
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * @return string
	 */
	public function get_template_path(): string;

	/**
	 * @return mixed[]
	 */
	public function get_template_vars(): array;
}
