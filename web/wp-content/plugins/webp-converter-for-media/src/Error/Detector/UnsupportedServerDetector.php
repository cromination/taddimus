<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\NoticeInterface;
use WebpConverter\Error\Notice\UnsupportedPlaygroundServerNotice;

/**
 * Checks for configuration errors about unsupported servers.
 */
class UnsupportedServerDetector implements DetectorInterface {

	public function get_error(): ?NoticeInterface {
		if ( strpos( $_SERVER['SERVER_NAME'] ?? '', 'playground.wordpress.net' ) !== false ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return new UnsupportedPlaygroundServerNotice();
		}

		return null;
	}
}
