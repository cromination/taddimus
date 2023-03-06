<?php

namespace WebpConverter\Error\Notice;

use WebpConverter\Settings\Page\AdvancedSettingsPage;
use WebpConverter\Settings\Page\PageIntegration;

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
			__( 'It appears that the requests for images on your website are being processed by the Nginx server, bypassing Apache.', 'webp-converter-for-media' ),
			implode(
				'<br>',
				[
					sprintf(
					/* translators: %1$s: open strong tag, %2$s: close strong tag */
						__( 'To fix this issue, please, log in to your hosting control panel and navigate to the management of your website. Look for one of the following settings %1$sand disable it if it is active%2$s:', 'webp-converter-for-media' ),
						'<strong>',
						'</strong>'
					),
					sprintf(
					/* translators: %1$s: button label, %2$s: tab name, %3$s: tab name, %4$s: section name */
						__( '- for SiteGround hosting click the %1$s button on the list of websites -> click %2$s and the %3$s tab -> find the %4$s section', 'webp-converter-for-media' ),
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
			/* translators: %1$s: anchor tag, %2$s: title of question */
				__( 'If you have direct access to the Nginx configuration, please, make the changes described in the plugin FAQ: %1$s (in the question: %2$s).', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-bypassing-apache-faq" target="_blank">https://wordpress.org/plugins/webp-converter-for-media/faq/</a>',
				'Configuration for Nginx Proxy'
			),
			__( 'If you have trouble finding these settings, please, contact your hosting\'s technical support and provide them with the following message:', 'webp-converter-for-media' ),
			'<em>' . implode(
				' ',
				[
					sprintf(
					/* translators: %1$s: setting name, %2$s: setting name, %3$s: home URL */
						__( 'I would like to disable %1$s (or %2$s) for static content files like .jpg, .jpeg, .png, .gif and .webp on my website - %3$s. These files should have been handled by the Apache server instead of Nginx.', 'webp-converter-for-media' ),
						'Nginx Caching / Nginx Reverse Proxy',
						'Nginx Direct Delivery',
						get_home_url()
					),
					sprintf(
					/* translators: %1$s: anchor tag, %2$s: title of question */
						__( 'If you have direct access to the Nginx configuration, please, make the changes described in the plugin FAQ: %1$s (in the question: %2$s).', 'webp-converter-for-media' ),
						'<a href="https://url.mattplugins.com/converter-error-bypassing-apache-faq" target="_blank">https://wordpress.org/plugins/webp-converter-for-media/faq/</a>',
						'Configuration for Nginx Proxy'
					),
				]
			) . '</em>',
			sprintf(
			/* translators: %1$s: open strong tag, %2$s: close strong tag, %3$s: field value, %4$s: field label, %5$s: open anchor tag, %6$s: close anchor tag */
				__( '%1$sThe alternative solution to avoid this problem%2$s may be to set the %3$s option for the %4$s field in %5$sthe Advanced Settings tab%6$s.', 'webp-converter-for-media' ),
				'<strong id="bypassing-notice" class="webpcContent__tip">',
				'</strong>',
				'"' . __( 'Bypassing Nginx', 'webp-converter-for-media' ) . '"',
				'"' . __( 'Image loading mode', 'webp-converter-for-media' ) . '"',
				'<a href="' . esc_attr( PageIntegration::get_settings_page_url( AdvancedSettingsPage::PAGE_SLUG ) ) . '#bypassing-notice">',
				'</a>'
			),
		];
	}
}
