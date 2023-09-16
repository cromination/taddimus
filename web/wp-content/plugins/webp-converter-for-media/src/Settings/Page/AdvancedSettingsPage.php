<?php


namespace WebpConverter\Settings\Page;

use WebpConverter\Settings\Option\OptionAbstract;

/**
 * {@inheritdoc}
 */
class AdvancedSettingsPage extends GeneralSettingsPage {

	const PAGE_SLUG = 'advanced';

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): string {
		return self::PAGE_SLUG;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Advanced Settings', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_vars(): array {
		return array_merge(
			parent::get_template_vars(),
			[
				'form_options'     => $this->plugin_data->get_plugin_options( OptionAbstract::FORM_TYPE_ADVANCED ),
				'form_input_value' => OptionAbstract::FORM_TYPE_ADVANCED,
			]
		);
	}
}
