<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class CurlFunctionDisabledNotice implements NoticeInterface {

	const ERROR_KEY = 'curl_function_disabled';

	/**
	 * @var string
	 */
	private $disabled_function;

	public function __construct( string $disabled_function ) {
		$this->disabled_function = $disabled_function;
	}

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
			/* translators: %s: PHP function name */
				__( 'It appears that either the %s function is disabled on your server (it is listed in the disable_functions list in your PHP configuration) or the cURL library in PHP is not available on your server. This PHP function is required for the plugin to function properly.', 'webp-converter-for-media' ),
				$this->disabled_function
			),
			__( 'In this case, please, contact your server administrator.', 'webp-converter-for-media' ),
		];
	}
}
