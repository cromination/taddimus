<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class CloudflareSettingsIncorrectNotice implements ErrorNotice {

	const ERROR_KEY = 'settings_cloudflare';

	/**
	 * {@inheritdoc}
	 */
	public function get_key(): string {
		return self::ERROR_KEY;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_message(): array {
		return [
			sprintf(
			/* translators: %s: field labels */
				__( 'Incorrect values were given in the plugin settings in the fields: %s.', 'webp-converter-for-media' ),
				implode( ', ', [ 'Cloudflare Zone ID', 'Cloudflare API Token' ] )
			),
			sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'Please, read %1$sour manual%2$s and follow the steps there.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-cloudflare-settings-docs" target="_blank">',
				'</a>'
			),
		];
	}
}
