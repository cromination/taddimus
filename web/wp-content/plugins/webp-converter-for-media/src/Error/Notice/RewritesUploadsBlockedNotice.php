<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesUploadsBlockedNotice implements ErrorNotice {

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
				__( 'It appears that mod_rewrite is blocked on your server for the %1$s directory. The blocking issue applies to rewrites from the .htaccess file for files from the %1$s directory. Please, ask your hosting\'s technical support to unblock rewrites from the .htaccess file for files in the %1$s directory.', 'webp-converter-for-media' ),
				'/wp-content/uploads/'
			),
			__( 'In this case, please, contact your server administrator.', 'webp-converter-for-media' ),
		];
	}
}
