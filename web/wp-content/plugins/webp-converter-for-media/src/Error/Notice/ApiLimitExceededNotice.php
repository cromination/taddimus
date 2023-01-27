<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class ApiLimitExceededNotice implements ErrorNotice {

	const ERROR_KEY = 'token_limit';

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
			__( 'It appears that you have reached the maximum number of image conversions for your current billing period. To continue using the service, we recommend upgrading to a higher plan.', 'webp-converter-for-media' ),
			sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'To manage your subscriptions, please visit %1$sour website%2$s.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-token-limit-panel" target="_blank">',
				'</a>'
			),
		];
	}
}
