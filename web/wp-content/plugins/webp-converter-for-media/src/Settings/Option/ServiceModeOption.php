<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class ServiceModeOption extends OptionAbstract {

	const OPTION_NAME = 'service_mode';

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
		return OptionAbstract::OPTION_TYPE_TOGGLE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return sprintf(
			'%1$s (%2$s)',
			__( 'Service mode', 'webp-converter-for-media' ),
			__( 'only upon the request from plugin\'s technical support', 'webp-converter-for-media' )
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_available_values( array $settings ) {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( array $settings = null ): string {
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, array $available_values = null, array $disabled_values = null ): string {
		return ( $current_value === 'yes' ) ? 'yes' : '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ): string {
		return $this->validate_value( $current_value );
	}
}
