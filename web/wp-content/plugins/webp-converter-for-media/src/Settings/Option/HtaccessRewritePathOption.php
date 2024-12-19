<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class HtaccessRewritePathOption extends OptionAbstract {

	const OPTION_NAME = 'htaccess_rewrite_path';

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return self::OPTION_NAME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_form_name(): string {
		return OptionAbstract::FORM_TYPE_EXPERT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return OptionAbstract::OPTION_TYPE_INPUT;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return self::OPTION_NAME;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_available_values( array $settings ): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( ?array $settings = null ): string {
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, ?array $available_values = null, ?array $disabled_values = null ): string {
		return sanitize_text_field( $current_value );
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ): string {
		return $this->validate_value( $current_value );
	}
}
