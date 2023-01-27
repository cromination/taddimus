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
			__( 'It appears that requests for images on your website are being processed by the Nginx server, bypassing Apache.', 'webp-converter-for-media' ),
			implode(
				'<br>',
				[
					sprintf(
					/* translators: %1$s: open strong tag, %2$s: close strong tag */
						__( 'To fix this issue, please log in to your hosting control panel and navigate to the management of your website. Look for one of the following settings %1$sand disable it if it is active%2$s:', 'webp-converter-for-media' ),
						'<strong>',
						'</strong>'
					),
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
			__( 'If you have trouble finding these settings, please contact your hosting support and provide them with the following message:', 'webp-converter-for-media' ),
			sprintf(
			/* translators: %1$s: setting name, %2$s: setting name, %3$s: home URL */
				'<em>' . __( 'I would like to disable %1$s (or %2$s) for static content files like .jpg, .jpeg, .png, .gif and .webp on my website - %3$s. These files should have been handled by Apache server instead of Nginx. I need help on this matter.', 'webp-converter-for-media' ) . '</em>',
				'Nginx Caching',
				'Nginx Direct Delivery',
				get_home_url()
			),
		];
	}
}
