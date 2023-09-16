<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class PathHtaccessNotWritableNotice implements NoticeInterface {

	const ERROR_KEY = 'path_htaccess_not_writable';

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
			/* translators: %1$s: server path */
				__( 'Unable to create or edit .htaccess file (is_readable() or is_writable() function returns false). Change directory permissions. The current path of the file is: %1$s. Please, contact your server administrator.', 'webp-converter-for-media' ),
				'<strong>' . apply_filters( 'webpc_dir_path', '', 'uploads' ) . '/.htaccess</strong>'
			),
		];
	}
}
