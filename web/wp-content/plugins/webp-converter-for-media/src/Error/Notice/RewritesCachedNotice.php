<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesCachedNotice implements ErrorNotice {

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
		return [
			__( 'It appears that your server uses the cache for HTTP requests. The rules from the .htaccess file or from the Nginx configuration are not executed every time when an image is loaded, but the last redirect from cache is performed.', 'webp-converter-for-media' ),
			__( 'Please, contact your hosting\'s technical support or CDN\'s support and send them the following message:', 'webp-converter-for-media' ),
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
					/* translators: %1$s: open anchor tag, %2$s: close anchor tag, %3$s: title of question */
						__( '- the website is running on the Nginx server without support for .htaccess files and not all the steps described in %1$sthe plugin FAQ%2$s (in the question: %3$s) have been followed correctly', 'webp-converter-for-media' ),
						'<a href="https://url.mattplugins.com/converter-error-rewrites-cached-faq" target="_blank">',
						'</a>',
						'Configuration for Nginx'
					),
				]
			) . '</em>',
		];
	}
}
