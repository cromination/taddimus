<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Conversion\Format\WebpFormat;
use WebpConverter\Repository\TokenRepository;

/**
 * {@inheritdoc}
 */
class OutputFormatsOption extends OptionAbstract {

	const OPTION_NAME = 'output_formats';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var ConversionMethodOption
	 */
	private $conversion_method_option;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	public function __construct( TokenRepository $token_repository, FormatFactory $format_factory, ConversionMethodOption $conversion_method_option ) {
		$this->token_repository         = $token_repository;
		$this->conversion_method_option = $conversion_method_option;
		$this->format_factory           = $format_factory;
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
		return __( 'Supported output formats', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_notice_lines(): ?array {
		$notice = [
			__( 'The AVIF format is the successor to the WebP format. Images converted to the AVIF format weigh about 50% less than images converted only to the WebP format, while maintaining better image quality.', 'webp-converter-for-media' ),
		];

		if ( $this->token_repository->get_token()->get_token_value() === null ) {
			$notice[] = sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( '%1$sUpgrade to PRO%2$s', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-field-output-formats-info" target="_blank">',
				' <span class="dashicons dashicons-external"></span></a>'
			);
		}
		return $notice;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_available_values( array $settings ): array {
		return $this->format_factory->get_formats();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_disabled_values( array $settings ): array {
		$method = $settings[ ConversionMethodOption::OPTION_NAME ] ?? null;
		if ( ! $method || in_array( $method, $this->conversion_method_option->get_disabled_values( $settings ) ) ) {
			$method = $this->conversion_method_option->get_default_value( $settings );
		}
		$formats           = $this->format_factory->get_formats();
		$formats_available = $this->format_factory->get_available_formats( $method );

		return array_keys( array_diff( $formats, $formats_available ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_default_value( ?array $settings = null ): array {
		$method = $settings[ ConversionMethodOption::OPTION_NAME ] ?? null;
		if ( ! $method ) {
			$method = $this->conversion_method_option->get_default_value( $settings );
		}
		$formats = array_keys( $this->format_factory->get_available_formats( $method ) );

		return ( in_array( WebpFormat::FORMAT_EXTENSION, $formats ) ) ? [ WebpFormat::FORMAT_EXTENSION ] : [];
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

		return $valid_values;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ) {
		$values = [ WebpFormat::FORMAT_EXTENSION, AvifFormat::FORMAT_EXTENSION ];

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
		return [ WebpFormat::FORMAT_EXTENSION, AvifFormat::FORMAT_EXTENSION ];
	}
}
