<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class AccessTokenInvalidNotice implements NoticeInterface {

	const ERROR_KEY = 'token_invalid';

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
			/* translators: %1$s: field label, %2$s: button label */
				__( 'It appears that the value of the %1$s field is invalid or your subscription has expired. To use the service, please, check your subscription and click the %2$s button again.', 'webp-converter-for-media' ),
				__( 'Access Token', 'webp-converter-for-media' ),
				__( 'Activate Token', 'webp-converter-for-media' )
			),
			sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'To manage your subscriptions, please visit %1$sour website%2$s.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-token-invalid-panel" target="_blank">',
				'</a>'
			),
		];
	}
}
