<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Conversion\Format\AvifFormat;
use WebpConverter\Repository\TokenRepository;

/**
 * {@inheritdoc}
 */
class SupportedExtensionsOption extends OptionAbstract {

	const OPTION_NAME = 'extensions';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct( TokenRepository $token_repository ) {
		$this->token_repository = $token_repository;
	}

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
			'jpg'  => '.jpg',
			'jpeg' => '.jpeg',
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
		return [ 'jpg', 'jpeg', 'png' ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_value_for_debug( array $settings ): array {
		return [ 'png2', 'png' ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_disabled_values( array $settings ): array {
		$output_formats = $settings[ OutputFormatsOption::OPTION_NAME ] ?? [];

		return ( ! in_array( AvifFormat::FORMAT_EXTENSION, $output_formats )
			|| ! $this->token_repository->get_token()->get_valid_status() )
			? [ 'webp' ]
			: [];
	}
}
