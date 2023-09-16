<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Conversion\Directory\DirectoryFactory;

/**
 * {@inheritdoc}
 */
class SupportedDirectoriesOption extends OptionAbstract {

	const OPTION_NAME = 'dirs';

	/**
	 * @var DirectoryFactory
	 */
	private $directory_factory;

	public function __construct( DirectoryFactory $directory_factory ) {
		$this->directory_factory = $directory_factory;
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
	public function get_form_name(): string {
		return OptionAbstract::FORM_TYPE_BASIC;
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
		return __( 'Supported directories', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return __( 'Files from these directories will be converted to output formats.', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_available_values( array $settings ): array {
		return $this->directory_factory->get_directories();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_default_value( array $settings = null ): array {
		return [ 'uploads' ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, array $available_values = null, array $disabled_values = null ) {
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

		return $valid_values;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ) {
		return $this->validate_value(
			$current_value,
			$this->get_available_values( [] )
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_debug_value( array $settings ): array {
		return [ 'uploads' ];
	}
}
