<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class SupportedExtensionsOption extends OptionAbstract {

	const OPTION_NAME = 'extensions';

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
		return OptionAbstract::OPTION_TYPE_CHECKBOX;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Supported files extensions', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return __( 'Files from supported directories that will be converted to output formats.', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_available_values( array $settings ): array {
		return [
			'jpg'  => '.jpg / .jpeg',
			'png'  => '.png',
			'gif'  => '.gif',
			'webp' => sprintf(
			/* translators: %s: file extension */
				__( '%s (converting to AVIF only)', 'webp-converter-for-media' ),
				'.webp'
			),
		];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_default_value( ?array $settings = null ): array {
		return [ 'jpg', 'jpeg', 'png', 'webp' ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, ?array $available_values = null, ?array $disabled_values = null ) {
		$valid_values = [];
		if ( ! $current_value ) {
			return $valid_values;
		}

		foreach ( $current_value as $option_value ) {
			if ( array_key_exists( $option_value, $available_values ?: [] )
				&& ! in_array( $option_value, $disabled_values ?: [] ) ) {
				$valid_values[] = $option_value;
			}
		}
		if ( in_array( 'jpg', $current_value ) ) {
			$valid_values[] = 'jpeg';
		}

		return array_unique( $valid_values );
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ) {
		$values = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'png2' ];

		return $this->validate_value(
			$current_value,
			array_combine( $values, $values )
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_debug_value( array $settings ): array {
		return [ 'png2', 'png' ];
	}
}
