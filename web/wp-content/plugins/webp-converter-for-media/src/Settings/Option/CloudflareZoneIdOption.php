<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class CloudflareZoneIdOption extends OptionAbstract {

	const OPTION_NAME = 'cloudflare_zone_id';

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
		return OptionAbstract::FORM_TYPE_CDN;
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
		return 'Cloudflare Zone ID';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return implode(
			' ',
			[
				sprintf(
				/* translators: %s: service name */
					__( 'Optionally, fill in if you are using %s.', 'webp-converter-for-media' ),
					'Cloudflare'
				),
				sprintf(
				/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
					__( 'Check out %1$sour documentation%2$s for more information.', 'webp-converter-for-media' ),
					'<a href="https://url.mattplugins.com/converter-field-cloudflare-info" target="_blank">',
					'</a>'
				),
			]
		);
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
	public function get_default_value( ?array $settings = null ): string {
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, ?array $available_values = null, ?array $disabled_values = null ): string {
		return sanitize_text_field( $current_value );
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ): string {
		return $this->validate_value( $current_value );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_public_value( $current_value = null ) {
		if ( $current_value === null ) {
			return $current_value;
		}

		return str_repeat( '*', max( strlen( $current_value ), 0 ) );
	}
}
