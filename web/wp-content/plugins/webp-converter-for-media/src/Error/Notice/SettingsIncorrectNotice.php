<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class SettingsIncorrectNotice implements NoticeInterface {

	const ERROR_KEY = 'settings_incorrect';

	/**
	 * @var string
	 */
	private $field_label;

	/**
	 * @var string
	 */
	private $settings_tab_label;

	public function __construct( string $field_label, string $settings_tab_label ) {
		$this->field_label        = $field_label;
		$this->settings_tab_label = $settings_tab_label;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_key(): string {
		return self::ERROR_KEY;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_message(): array {
		return [
			sprintf(
			/* translators: %1$s: field label, %2$s: close anchor tag */
				__( 'The %1$s field in the %2$s tab is required. Please select at least one option. Please review these settings and save again.', 'webp-converter-for-media' ),
				'"' . $this->field_label . '"',
				'"' . $this->settings_tab_label . '"'
			),
		];
	}
}
