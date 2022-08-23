<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Repository\TokenRepository;
use WebpConverter\WebpConverterConstants;

/**
 * {@inheritdoc}
 */
class ImageResizeOption extends OptionAbstract {

	const OPTION_NAME = 'image_resize';

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
		return 70;
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
		return OptionAbstract::OPTION_TYPE_IMAGE_SIZE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Maximum image dimensions', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		$message = __( 'Resize large images to maximum dimensions in pixels during image conversion, keeping the original aspect ratio', 'webp-converter-for-media' );

		if ( ! $this->token_repository->get_token()->get_valid_status() ) {
			return sprintf(
			/* translators: %1$s: option name, %2$s: open anchor tag, %3$s: close anchor tag */
				__( '%1$s (available in %2$sthe PRO version%3$s)', 'webp-converter-for-media' ),
				$message,
				'<a href="' . esc_url( sprintf( WebpConverterConstants::UPGRADE_PRO_PREFIX_URL, 'field-image-resize-upgrade' ) ) . '" target="_blank">',
				'</a>'
			);
		}
		return $message;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_notice_lines() {
		$sizes = wp_get_additional_image_sizes();
		usort(
			$sizes,
			function ( $a, $b ) {
				if ( $a['width'] === $a['height'] ) {
					return $b['height'] - $a['height'];
				}
				return $b['width'] - $a['width'];
			}
		);

		$notice = [
			sprintf(
			/* translators: %1$s: width value, %2$s: height value */
				__( 'Reduce even more the weight of converted images that are larger than their largest image size (thumbnail size) using in your theme. The recommended value for you is %1$s x %2$s pixels.', 'webp-converter-for-media' ),
				$sizes[0]['width'] ?? 0,
				$sizes[0]['height'] ?? 0
			),
		];

		if ( $this->token_repository->get_token()->get_token_value() === null ) {
			$notice[] = sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( '%1$sUpgrade to PRO%2$s', 'webp-converter-for-media' ),
				'<a href="' . esc_url( sprintf( WebpConverterConstants::UPGRADE_PRO_PREFIX_URL, 'field-image-resize-info' ) ) . '" target="_blank">',
				'<span class="dashicons dashicons-arrow-right-alt"></span></a>'
			);
		}
		return $notice;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_values( array $settings ): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_default_value( array $settings = null ): array {
		return [ '', '', '' ];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_disabled_values( array $settings ): array {
		if ( ! $this->token_repository->get_token()->get_valid_status() ) {
			return [ 'yes' ];
		}
		return [];
	}
}
