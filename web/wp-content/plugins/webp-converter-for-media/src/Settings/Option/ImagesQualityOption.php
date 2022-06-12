<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class ImagesQualityOption extends OptionAbstract {

	const OPTION_NAME = 'quality';

	/**
	 * {@inheritdoc}
	 */
	public function get_priority(): int {
		return 60;
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
		return OptionAbstract::OPTION_TYPE_QUALITY;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Images quality', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return __( 'Adjust the quality of the images being converted. Remember that higher quality also means larger file sizes. The recommended value is 85%.', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string[]
	 */
	public function get_values( array $settings ): array {
		$levels = apply_filters(
			'webpc_option_quality_levels',
			[ '75', '80', '85', '90', '95', '100' ]
		);

		$values = [];
		foreach ( $levels as $level ) {
			$level_value = (int) $level;
			if ( ( $level_value > 0 ) && ( $level_value <= 100 ) ) {
				$values[ $level_value ] = sprintf( '%s%%', $level_value );
			}
		}
		ksort( $values );
		return $values;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( array $settings = null ): string {
		return '85';
	}
}
