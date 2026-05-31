<?php

namespace WebpConverter\Settings\Option;

/**
 * {@inheritdoc}
 */
class HtaccessRewriteFlagRedirectOption extends ServiceModeOption {

	const OPTION_NAME = 'htaccess_rewrite_flag_redirect';

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return self::OPTION_NAME;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_info(): string {
		return self::OPTION_NAME;
	}
}
