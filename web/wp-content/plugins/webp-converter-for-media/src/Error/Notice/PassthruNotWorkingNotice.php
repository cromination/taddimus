<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class PassthruNotWorkingNotice implements NoticeInterface {

	const ERROR_KEY = 'passthru_not_working';

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
			/* translators: %1$s: loader name */
				__( 'The %1$s loading mode is not compatible with your server. Sorry for the inconvenience.', 'webp-converter-for-media' ),
				'Pass Thru'
			),
		];
	}
}
