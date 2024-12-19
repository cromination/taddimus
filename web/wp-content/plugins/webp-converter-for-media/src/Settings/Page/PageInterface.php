<?php

namespace WebpConverter\Settings\Page;

/**
 * Interface for class that supports tab in plugin settings page.
 */
interface PageInterface {

	/**
	 * @return string|null
	 */
	public function get_slug(): ?string;

	/**
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * @return string
	 */
	public function get_menu_parent(): string;

	/**
	 * @return string|null
	 */
	public function get_menu_url(): ?string;

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

	/**
	 * @return void
	 */
	public function do_action_before_load();

	/**
	 * @return void
	 */
	public function do_action_after_load();
}
