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
	public function get_priority(): int {
		return 10;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return self::OPTION_NAME;
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
		return __( 'List of supported files extensions', 'webp-converter-for-media' );
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
	public function get_values( array $settings ): array {
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
	public function get_default_value( array $settings = null ): array {
		return [ 'jpg', 'png' ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_debug_value( array $settings ): array {
		return [ 'png2', 'png' ];
	}

	/**
	 * @param string[] $current_value .
	 *
	 * @return string[]
	 */
	public function parse_value( $current_value ): array {
		if ( in_array( 'jpg', $current_value ) ) {
			$current_value[] = 'jpeg';
		}
		return $current_value;
	}
}
