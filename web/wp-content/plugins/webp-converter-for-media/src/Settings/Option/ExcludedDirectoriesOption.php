<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class ExcludedDirectoriesOption extends OptionAbstract {

	const OPTION_NAME = 'excluded_dirs';

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
		return OptionAbstract::FORM_TYPE_ADVANCED;
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
		return __( 'Excluded directories', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return __( 'Directory names separated by a comma that will be skipped during image conversion.', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_placeholder() {
		return 'directory-1,directory-2';
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
	public function get_valid_value( $current_value, array $available_values = null, array $disabled_values = null ): string {
		$valid_values = explode( ',', str_replace( [ '/', '\\' ], '', $current_value ) );
		$valid_values = array_map( 'trim', $valid_values );
		return implode( ',', $valid_values );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( array $settings = null ): string {
		return '';
	}
}
