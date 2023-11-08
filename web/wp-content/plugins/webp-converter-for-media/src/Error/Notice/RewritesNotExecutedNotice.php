<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesNotExecutedNotice implements NoticeInterface {

	const ERROR_KEY = 'rewrites_not_executed';

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
		$server_name = $this->get_nginx_server_name();
		$message     = [];

		if ( $server_name === null ) {
			$message[] = __( 'It appears that your server does not support using .htaccess files from custom locations, or it requires additional configuration for the plugin to function properly.', 'webp-converter-for-media' );
			$message[] = sprintf(
			/* translators: %1$s: open anchor tag, %2$s: close anchor tag */
				__( 'Please check %1$sour instructions%2$s which should help you solve your problem. This will allow the plugin to function properly.', 'webp-converter-for-media' ),
				'<a href="https://url.mattplugins.com/converter-error-rewrites-not-executed-instructions" target="_blank">',
				'</a>'
			);
		} else {
			$message[] = sprintf(
			/* translators: %1$s: server name */
				__( 'For the %1$s server, please contact your hosting\'s technical support to allow the plugin to function properly. Your server needs additional configuration, but your hosting\'s technical support will take care of it for you. Please, send them the following message:', 'webp-converter-for-media' ),
				$server_name
			);
			$message[] = sprintf(
			/* translators: %1$s: plugin name, %2$s: home URL, %3$s: anchor tag, %4$s: title of question */
				'<em>' . __( 'I am trying to configure the %1$s plugin that supports the WebP and AVIF format. I need your help in adding the required rules to the Nginx configuration of my website - %2$s. You can find more information in the plugin FAQ: %3$s (in the question: %4$s)', 'webp-converter-for-media' ) . '</em>',
				'Converter for Media',
				get_home_url(),
				'<a href="https://url.mattplugins.com/converter-error-rewrites-not-executed-faq" target="_blank">https://wordpress.org/plugins/webp-converter-for-media/faq/</a>',
				'Configuration for Nginx'
			);
		}

		return $message;
	}

	/**
	 * @return string|null
	 */
	private function get_nginx_server_name() {
		if ( getenv( 'IS_WPE' ) ) {
			return 'WP Engine';
		} elseif ( strpos( strtolower( $_SERVER['SERVER_SOFTWARE'] ?? '' ), 'nginx' ) !== false ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return 'Nginx';
		}

		return null;
	}
}
