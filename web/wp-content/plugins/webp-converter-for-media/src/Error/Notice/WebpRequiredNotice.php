<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class WebpRequiredNotice implements NoticeInterface {

	const ERROR_KEY = 'webp_required';

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
			__( 'WebP as an output format is required. In the "Output formats" option, select the WebP format.', 'webp-converter-for-media' ),
		];
	}
}
