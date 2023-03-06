<?php

namespace WebpConverter\Error\Notice;

/**
 * {@inheritdoc}
 */
class RewritesNotExecutedNotice implements ErrorNotice {

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
		$message     = [
			__( 'It appears that your server does not support using .htaccess files from custom locations, or it requires additional configuration for the plugin to function properly.', 'webp-converter-for-media' ),
			sprintf(
			/* translators: %s: server name */
				__( 'If you are using the %1$s server, please, contact your hosting\'s technical support (or server administrator) and provide them with the following message:', 'webp-converter-for-media' ),
				$server_name ?: 'Nginx'
			),
			sprintf(
			/* translators: %1$s: plugin name, %2$s: home URL, %3$s: anchor tag, %4$s: title of question */
				'<em>' . __( 'I am trying to configure the %1$s plugin with WebP and AVIF support. In order to do this, I need your help in adding the required rules to the Nginx configuration of my website - %2$s. More information can be found in the plugin FAQ: %3$s (in the question: %4$s)', 'webp-converter-for-media' ) . '</em>',
				'Converter for Media',
				get_home_url(),
				'<a href="https://url.mattplugins.com/converter-error-rewrites-not-executed-faq" target="_blank">https://wordpress.org/plugins/webp-converter-for-media/faq/</a>',
				'Configuration for Nginx'
			),
		];
		if ( $server_name !== null ) {
			return $message;
		}

		$message[] = implode(
			' ',
			[
				sprintf(
				/* translators: %1$s: invalid config value, %2$s: correct config value, %3$s: configuration rule */
					__( 'If you are using the Apache server, this issue is usually related to the virtual host settings in the Apache configuration. Substitute the %1$s value with %2$s in the %3$s section of the relevant .conf file for your VirtualHost.', 'webp-converter-for-media' ),
					sprintf( '<strong>%s</strong>', 'AllowOverride None' ),
					sprintf( '<strong>%s</strong>', 'AllowOverride All' ),
					sprintf( '<strong>%s</strong>', '&lt;Directory&gt;...&lt;/Directory&gt;' )
				),
				__( 'In this case, please, contact your server administrator.', 'webp-converter-for-media' ),
			]
		);
		return $message;
	}

	/**
	 * @return string|null
	 */
	private function get_nginx_server_name() {
		if ( getenv( 'IS_WPE' ) ) {
			return 'WP Engine';
		} elseif ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel' ) !== false ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return 'Flywheel';
		}

		return null;
	}
}
