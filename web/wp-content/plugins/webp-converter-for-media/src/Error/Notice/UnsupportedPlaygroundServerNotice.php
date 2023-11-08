<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class UnsupportedPlaygroundServerNotice implements NoticeInterface {

	const ERROR_KEY = 'unsupported_server';

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
			/* translators: %s: server name */
				__( 'The %s environment on which you launched your website does not meet the technical requirements of our plugin. Unfortunately, we have no control over the configuration of this server. Please, test the plugin on a different server and you will surely be satisfied.', 'webp-converter-for-media' ),
				'WordPress Playground'
			),
		];
	}
}
