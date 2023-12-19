<?php

namespace WebpConverter\Error\Notice;

use WebpConverter\Service\EnvDetector;

/**
 * {@inheritdoc}
 */
class RewritesCachedNotice implements NoticeInterface {

	const ERROR_KEY = 'rewrites_cached';

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
		$message = [
			__( 'It appears that your server uses the cache for HTTP requests. The rules from the .htaccess file or from the Nginx configuration are not executed every time when an image is loaded, but the last redirect from cache is performed.', 'webp-converter-for-media' ),
		];

		if ( EnvDetector::is_cdn_bunny() ) {
			$message[] = sprintf(
			/* translators: %1$s: open strong tag, %2$s: service name, %3$s: close strong tag, %4$s: open anchor tag, %5$s: close anchor tag */
				__( '%1$sIf you are using the %2$s service%3$s, please follow %4$sour manual%5$s first to allow the plugin to work properly.', 'webp-converter-for-media' ),
				'<strong>',
				'BunnyCDN',
				'</strong>',
				'<a href="https://url.mattplugins.com/converter-error-rewrites-cached-bunny-instruction" target="_blank">',
				'</a>'
			);
		}

		$message[] = implode(
			'',
			[
				'<em><strong>' . __( 'Please, contact your hosting\'s technical support or CDN\'s support and send them the following message:', 'webp-converter-for-media' ) . '</strong></em>',
				'<em>' . implode(
					'<br>',
					[
						sprintf(
						/* translators: %1$s: home URL */
							__( 'I have a problem with the cache for HTTP requests on my website - %1$s. This prevents JPEG or PNG files from being dynamically redirected to WebP or AVIF, depending on whether the browser supports the format. Here are potential sources of this issue:', 'webp-converter-for-media' ),
							get_home_url()
						),
						sprintf(
						/* translators: %1$s: header name, %2$s: additional information */
							__( '- the server or CDN server does not support the %1$s HTTP header or handles it incorrectly (%2$s)', 'webp-converter-for-media' ),
							'<strong>"Vary: Accept"</strong>',
							__( 'the cache for redirects should be based not only on the URL to the file, but also on the value of the Accept header sent by the browser', 'webp-converter-for-media' )
						),
						sprintf(
						/* translators: %1$s: header name, %2$s: additional information */
							__( '- the server or CDN server does not support the %1$s HTTP header or handles it incorrectly (%2$s)', 'webp-converter-for-media' ),
							'<strong>"Cache-Control: private"</strong>',
							__( 'this header should be able to disable caching for static files on the CDN server or proxy server', 'webp-converter-for-media' )
						),
						sprintf(
						/* translators: %s: anchor tag */
							__( '- the website is running on the Nginx server without support for .htaccess files and not all the steps described in our instruction (%s) have been followed correctly', 'webp-converter-for-media' ),
							'<a href="https://url.mattplugins.com/converter-error-rewrites-cached-nginx-instruction" target="_blank">https://url.mattplugins.com/converter-error-rewrites-cached-nginx-instruction</a>'
						),
					]
				) . '</em>',
			]
		);

		return $message;
	}
}
