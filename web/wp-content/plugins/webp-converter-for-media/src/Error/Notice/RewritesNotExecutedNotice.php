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
			__( 'Your server does not supports using .htaccess files from custom locations. Or your server requires additional configuration for the plugin to function properly.', 'webp-converter-for-media' ),
			sprintf(
			/* translators: %1$s: server name, %2$s: open strong tag, %3$s: anchor tag, %4$s: close strong tag */
				__( 'If you are using %1$s server, please contact your hosting support (or server administrator) and send them the following message: %2$s"I am trying to configure WebP Converter for Media plugin with WebP and AVIF support. In order to do this, I have been asked to contact you for help adding required code to my Nginx configuration. More information in the plugin FAQ: %3$s"%4$s', 'webp-converter-for-media' ),
				$server_name ?: 'Nginx',
				'<br><strong>',
				'<a href="https://wordpress.org/plugins/webp-converter-for-media/faq/" target="_blank">https://wordpress.org/plugins/webp-converter-for-media/faq/</a>',
				'</strong>'
			),
		];
		if ( $server_name !== null ) {
			return $message;
		}

		$message[] = implode(
			' ',
			[
				sprintf(
				/* translators: %1$s: directory tag, %2$s: invalid config value, %3$s: correct config value */
					__( 'If you are using Apache server, this issue usually related to the virtual host settings in the Apache configuration. In the .conf file appropriate for your VirtualHost, in the %1$s section, replace the value of %2$s with the value of %3$s.', 'webp-converter-for-media' ),
					sprintf( '<strong>%s</strong>', '&lt;Directory&gt;...&lt;/Directory&gt;' ),
					sprintf( '<strong>%s</strong>', 'AllowOverride None' ),
					sprintf( '<strong>%s</strong>', 'AllowOverride All' )
				),
				__( 'In this case, please contact your server administrator.', 'webp-converter-for-media' ),
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
		}

		return null;
	}
}
