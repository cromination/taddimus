<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Conversion\Method\GdMethod;
use WebpConverter\Conversion\Method\ImagickMethod;
use WebpConverter\Conversion\Method\MethodFactory;
use WebpConverter\Conversion\Method\RemoteMethod;
use WebpConverter\Repository\TokenRepository;

/**
 * {@inheritdoc}
 */
class ConversionMethodOption extends OptionAbstract {

	const OPTION_NAME = 'method';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	/**
	 * @var MethodFactory
	 */
	private $method_factory;

	public function __construct( TokenRepository $token_repository, MethodFactory $method_factory ) {
		$this->token_repository = $token_repository;
		$this->method_factory   = $method_factory;
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
		return OptionAbstract::FORM_TYPE_ADVANCED;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return OptionAbstract::OPTION_TYPE_RADIO;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Conversion method', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_notice_lines() {
		$notice = [
			__( 'The remote server allows you to reduce the server load, because your images are converted by our server. This option is also useful when the server does not meet all the technical requirements of the plugin.', 'webp-converter-for-media' ),
		];

		if ( $this->token_repository->get_token()->get_token_value() === null ) {
			$notice[] = sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( '%1$sUpgrade to PRO%2$s', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-field-conversion-method-info" target="_blank">',
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
		return $this->method_factory->get_methods();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_disabled_values( array $settings ): array {
		$methods           = $this->method_factory->get_methods();
		$methods_available = $this->method_factory->get_available_methods();
		return array_keys( array_diff( $methods, $methods_available ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( array $settings = null ): string {
		$methods_available = $this->method_factory->get_available_methods();
		return array_keys( $methods_available )[0] ?? '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, array $available_values = null, array $disabled_values = null ) {
		if ( ! array_key_exists( $current_value, $available_values ?: [] ) ) {
			return null;
		}

		return $current_value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ) {
		$values = [ ImagickMethod::METHOD_NAME, GdMethod::METHOD_NAME, RemoteMethod::METHOD_NAME ];

		return $this->validate_value(
			$current_value,
			array_combine( $values, $values )
		);
	}
}
