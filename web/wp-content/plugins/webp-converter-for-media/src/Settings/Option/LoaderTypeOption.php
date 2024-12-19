<?php

namespace WebpConverter\Settings\Option;

use WebpConverter\Error\Notice\BypassingApacheNotice;
use WebpConverter\Loader\HtaccessBypassingLoader;
use WebpConverter\Loader\HtaccessLoader;
use WebpConverter\Loader\PassthruLoader;

/**
 * {@inheritdoc}
 */
class LoaderTypeOption extends OptionAbstract {

	const OPTION_NAME = 'loader_type';

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
		return __( 'Image loading mode', 'webp-converter-for-media' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return implode(
			' ',
			[
				__( 'By changing this mode, you can bypass some of the server configuration problems.', 'webp-converter-for-media' ),
				sprintf(
				/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
					__( 'Check out %1$sour documentation%2$s for more information.', 'webp-converter-for-media' ),
					'<a href="https://url.mattplugins.com/converter-field-loader-type-info" target="_blank">',
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
		return [
			HtaccessLoader::LOADER_TYPE          => sprintf(
			/* translators: %s: loader type */
				__( '%s (recommended)', 'webp-converter-for-media' ),
				__( 'via .htaccess', 'webp-converter-for-media' ) . ' / Nginx'
			),
			HtaccessBypassingLoader::LOADER_TYPE => sprintf(
			/* translators: %1$s: loader type, %2$S: error name */
				__( '%1$s (use when you have a problem with the %2$s error)', 'webp-converter-for-media' ),
				__( 'Bypassing Nginx', 'webp-converter-for-media' ),
				BypassingApacheNotice::ERROR_KEY
			),
			PassthruLoader::LOADER_TYPE          => sprintf(
			/* translators: %s: loader type */
				__( '%s (without rewrites in .htaccess files or the Nginx configuration)', 'webp-converter-for-media' ),
				'Pass Thru'
			),
		];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return mixed[]
	 */
	public function get_values_warnings( array $settings ): array {
		return [
			HtaccessLoader::LOADER_TYPE          => null,
			HtaccessBypassingLoader::LOADER_TYPE => sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'If you are using this alternative setting, please read %1$sour guide%2$s which explains how it works.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-field-loader-type-alert-bypassing-nginx" target="_blank">',
				'</a>'
			),
			PassthruLoader::LOADER_TYPE          => sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'If you are using this alternative setting, please read %1$sour guide%2$s which explains how it works.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-field-loader-type-alert-pass-thru" target="_blank">',
				'</a>'
			),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_value( ?array $settings = null ): string {
		return HtaccessLoader::LOADER_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate_value( $current_value, ?array $available_values = null, ?array $disabled_values = null ) {
		if ( ! array_key_exists( $current_value, $available_values ?: [] )
			|| in_array( $current_value, $disabled_values ?: [] ) ) {
			return null;
		}

		return $current_value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize_value( $current_value ) {
		$values = [ HtaccessLoader::LOADER_TYPE, HtaccessBypassingLoader::LOADER_TYPE, PassthruLoader::LOADER_TYPE ];

		return $this->validate_value(
			$current_value,
			array_combine( $values, $values )
		);
	}
}
