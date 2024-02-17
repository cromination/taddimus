<?php


namespace WebpConverter\Settings\Page;

use WebpConverter\Settings\Option\OptionAbstract;

/**
 * {@inheritdoc}
 */
class ExpertSettingsPage extends GeneralSettingsPage {

	const PAGE_SLUG = 'expert';

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): string {
		return self::PAGE_SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return ( in_array(
			$_GET[ PageIntegrator::SETTINGS_PAGE_TYPE ] ?? '', // phpcs:ignore WordPress.Security
			[ self::PAGE_SLUG, DebugPage::PAGE_SLUG ]
		) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Expert Settings', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_vars(): array {
		return array_merge(
			parent::get_template_vars(),
			[
				'form_options'         => $this->plugin_data->get_plugin_options( OptionAbstract::FORM_TYPE_EXPERT ),
				'form_input_value'     => OptionAbstract::FORM_TYPE_EXPERT,
				'api_paths_url'        => null,
				'api_paths_nonce'      => null,
				'api_regenerate_url'   => null,
				'api_regenerate_nonce' => null,
			]
		);
	}
}
