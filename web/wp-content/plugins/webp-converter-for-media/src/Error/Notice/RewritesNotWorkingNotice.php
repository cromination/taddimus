<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesNotWorkingNotice implements ErrorNotice {

	const ERROR_KEY = 'rewrites_not_working';

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
			__( 'It appears that redirects on your server are not working. This means that your server does not support rewrites from the .htaccess file or your server configuration is not compatible with this plugin.', 'webp-converter-for-media' ),
			sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'In this case, please, %1$scontact us%2$s. We will try to help you.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-rewrites-not-working-contact" target="_blank">',
				'</a>'
			),
		];
	}
}
