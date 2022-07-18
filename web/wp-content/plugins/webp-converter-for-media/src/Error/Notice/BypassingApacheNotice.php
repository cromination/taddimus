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
			__( 'Requests to images are processed by Nginx server bypassing Apache. Please log in to your hosting control panel, go to management of this website and try to find one of the following settings:', 'webp-converter-for-media' ),
			implode(
				'<br>',
				[
					sprintf(
					/* translators: %1$s: button label, %2$s: tab name, %3$s: tab name, %4$s: section name */
						__( '- for SiteGround hosting click %1$s button on the websites list -> click %2$s and %3$s tab -> find %4$s section', 'webp-converter-for-media' ),
						'"Site Tools"',
						'"Speed"',
						'"Caching"',
						'"Nginx Direct Delivery"'
					),
					sprintf(
					/* translators: %1$s: setting name, %2$s: setting name */
						__( '- %1$s, %2$s or similar in the section related to Speed or Caching', 'webp-converter-for-media' ),
						'"Nginx Direct Delivery"',
						'"Nginx Caching"'
					),
					sprintf(
					/* translators: %s: setting name */
						__( '- %s or similar in the section related to Apache and Nginx configuration', 'webp-converter-for-media' ),
						'"Smart static files processing", "Serve static files directly by Nginx"'
					),
				]
			),
			sprintf(
			/* translators: %1$s: open strong tag, %2$s: close strong tag */
				__( '%1$sIf you have any of the above settings active, you must disable them for current website.%2$s Wait a few moments after saving the changes. Optionally, instead of disabling this setting you can remove the following extensions from the list of saved to the cache: .jpg, .jpeg, .png, .gif and .webp.', 'webp-converter-for-media' ),
				'<strong>',
				'</strong>'
			),
			__( 'In case of problems with finding such settings, please contact your hosting support.', 'webp-converter-for-media' ),
		];
	}
}
