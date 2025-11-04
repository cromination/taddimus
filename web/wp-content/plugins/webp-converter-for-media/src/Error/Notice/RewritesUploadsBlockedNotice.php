<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesUploadsBlockedNotice implements NoticeInterface {

	const ERROR_KEY = 'rewrites_uploads_blocked';

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
			/* translators: %1$s: directory path */
				__( 'It appears that mod_rewrite is blocked on your server for the %1$s directory. The blocking issue applies to rewrites from the .htaccess file for files from the %1$s directory.', 'webp-converter-for-media' ),
				'/wp-content/uploads/'
			),
			sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'Please check %1$sour instruction%2$s which should help you solve your problem. This will allow the plugin to function properly.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-rewrites-uploads-blocked-instruction" target="_blank">',
				'</a>'
			),
		];
	}
}
