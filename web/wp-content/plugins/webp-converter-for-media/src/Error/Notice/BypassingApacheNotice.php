<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class BypassingApacheNotice implements ErrorNotice {

	const ERROR_KEY = 'bypassing_apache';

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
			__( 'Requests to images are processed by your server bypassing Apache. When loading images, rules from the .htaccess file are not executed. Change the server settings to handle the rules in the .htaccess file when loading static files.', 'webp-converter-for-media' ),
			implode(
				'<br> - ',
				[
					__( 'Potential settings in the server or hosting configuration (usually you will find them in your hosting control panel) that may be causing this issue:', 'webp-converter-for-media' ),
					sprintf(
					/* translators: %s: setting name */
						__( '%s or similar in the section related to Apache and Nginx configuration (instead of disabling this setting you can remove the following extensions from the list of files handled only by Nginx: .jpg, .jpeg, .png and .gif)', 'webp-converter-for-media' ),
						'"Smart static files processing", "Serve static files directly by Nginx"'
					),
					sprintf(
					/* translators: %s: setting name */
						__( '%s or similar in the section related to speed or caching', 'webp-converter-for-media' ),
						'"Nginx Direct Delivery"'
					),
					sprintf(
					/* translators: %s: setting name */
						__( '%s or similar (instead of disabling this setting you can remove the following extensions from the list of saved to the cache: .jpg, .jpeg, .png and .gif)', 'webp-converter-for-media' ),
						'"Nginx Caching"'
					),
				]
			),
			__( 'If you have any of the above settings active, you must disable them for .htaccess rules to work properly.', 'webp-converter-for-media' ),
			__( 'In most cases, you will find such settings or similar in your hosting control panel and you can change it yourself. In case of problems with finding such settings, please contact your server administrator.', 'webp-converter-for-media' ),
		];
	}
}
