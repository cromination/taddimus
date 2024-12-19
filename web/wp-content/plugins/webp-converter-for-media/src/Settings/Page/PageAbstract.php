<?php

namespace WebpConverter\Settings\Page;

/**
 * Abstract class for class that supports tab in plugin settings page.
 */
abstract class PageAbstract implements PageInterface {

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_menu_parent(): string {
		return PageIntegrator::SETTINGS_MENU_PAGE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_menu_url(): ?string {
		return PageIntegrator::get_settings_page_url( $this->get_slug() );
	}
}
