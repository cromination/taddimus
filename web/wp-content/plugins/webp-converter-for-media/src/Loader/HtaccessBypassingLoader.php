<?php

namespace WebpConverter\Loader;

use WebpConverter\Settings\Option\SupportedExtensionsOption;

/**
 * Supports method of loading images using rewrites from .htaccess file.
 */
class HtaccessBypassingLoader extends HtaccessLoader {

	const LOADER_TYPE     = 'htaccess_bypassing';
	const FILENAME_SUFFIX = '-optimized';

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return self::LOADER_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_admin_hooks() {
		add_filter( 'webpc_debug_image_url', [ $this, 'update_image_urls' ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_front_end_hooks() {
		add_action( 'init', [ $this, 'start_buffering' ] );
	}

	/**
	 * @return void
	 * @internal
	 */
	public function start_buffering() {
		if ( ! ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( ! is_admin() && ! is_network_admin() ) ) ) {
			return;
		}

		ob_start(
			function ( $buffer ) {
				return $this->update_image_urls( $buffer );
			}
		);
	}

	/**
	 * Replaces URLs to source images in output buffer.
	 *
	 * @param string $buffer Contents of output buffer.
	 *
	 * @return string Contents of output buffer.
	 * @internal
	 */
	public function update_image_urls( string $buffer ): string {
		$settings   = $this->plugin_data->get_plugin_settings();
		$extensions = implode( '|', $settings[ SupportedExtensionsOption::OPTION_NAME ] );
		if ( ! $extensions ) {
			return $buffer;
		}

		$path_dir_uploads = apply_filters( 'webpc_dir_name', '', 'uploads' );
		return preg_replace_callback(
			'/((?:\/' . str_replace( '/', '\\/', $path_dir_uploads ) . '\/)(?:.*?))\.(' . $extensions . ')/',
			function ( $matches ) {
				return str_replace( self::FILENAME_SUFFIX, '', $matches[1] ) . self::FILENAME_SUFFIX . '.' . $matches[2];
			},
			$buffer
		) ?: $buffer;
	}

	/**
	 * @param mixed[] $settings Plugin settings.
	 *
	 * @return string[]
	 */
	protected function get_rules_to_wp_content( array $settings ): array {
		return [
			$this->get_suffix_redirect_rules( $settings ),
			$this->get_mod_rewrite_rules( $settings ),
			$this->get_mod_headers_rules( $settings ),
		];
	}

	/**
	 * Generates redirects for suffixed URLs.
	 *
	 * @param mixed[] $settings Plugin settings.
	 *
	 * @return string Rules for .htaccess file.
	 */
	private function get_suffix_redirect_rules( array $settings ): string {
		$content    = '';
		$extensions = implode( '|', $settings[ SupportedExtensionsOption::OPTION_NAME ] ) ?: '.+';

		$content .= '<IfModule mod_rewrite.c>' . PHP_EOL;
		$content .= '  RewriteEngine On' . PHP_EOL;
		$content .= '  RewriteCond %{REQUEST_FILENAME} !-f' . PHP_EOL;
		$content .= '  RewriteRule ^(.+)' . self::FILENAME_SUFFIX . '\.(' . $extensions . ')$ $1.$2 [NC]' . PHP_EOL;
		$content .= '</IfModule>';

		return $content;
	}
}
