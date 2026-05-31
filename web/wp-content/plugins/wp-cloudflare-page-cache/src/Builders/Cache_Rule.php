<?php

namespace SPC\Builders;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;
use SW_CLOUDFLARE_PAGECACHE;

/**
 * Cache Rule Builder.
 *
 * @see https://gist.github.com/isaumya/af10e4855ac83156cc210b7148135fa2
 */
class Cache_Rule {
	/**
	 * @var array $rule_parts Rule parts.
	 */
	private $rule_parts = [];

	/**
	 * @var Settings_Store $settings_store Settings store instance.
	 */
	private $settings_store;

	public function __construct() {
		$this->settings_store = Settings_Store::get_instance();
	}

	public function exclude_cookies() {
		$excluded = $this->settings_store->get( Constants::SETTING_EXCLUDED_COOKIES, [] );

		if ( ! is_array( $excluded ) ) {
			return $this;
		}

		foreach ( $excluded as $cookie ) {
			$this->rule_parts[] = sprintf( 'not http.cookie contains "%s"', trim( $cookie, '^' ) );
		}

		return $this;
	}

	/**
	 * Exclude paths from cache.
	 *
	 * @return $this
	 */
	public function exclude_paths() {
		$excluded_paths = $this->settings_store->get( Constants::SETTING_EXCLUDED_URLS, [] );

		if ( ! is_array( $excluded_paths ) ) {
			return $this;
		}

		$excluded = array_merge(
			$excluded_paths,
			[
				'/wp-admin',
				'/wp-login',
			]
		);

		$sanitized = array_map(
			function ( $path ) {
				return str_replace( [ '/*', '*' ], '', $path );
			},
			$excluded
		);

		foreach ( $sanitized as $path ) {
			$this->rule_parts[] = sprintf( 'not http.request.uri contains "%s"', $path );
		}

		return $this;
	}

	public function exclude_static_content() {
		if ( (bool) $this->settings_store->get( Constants::SETTING_BYPASS_SITEMAP, 1 ) ) {
			$this->rule_parts[] = 'not http.request.uri.path contains ".xml"';
			$this->rule_parts[] = 'not http.request.uri.path contains ".xsl"';
		}

		if ( (bool) $this->settings_store->get( Constants::SETTING_BYPASS_ROBOTS_TXT, 1 ) ) {
			$this->rule_parts[] = 'not http.request.uri.path contains "robots.txt"';
		}

		return $this;
	}

	/**
	 * Build cache rule expression.
	 *
	 * @return string Cache rule expression.
	 */
	public function build() {
		array_unshift( $this->rule_parts, $this->get_host_wildcard() );

		$expression = implode( ' and ', $this->rule_parts );

		$expression = str_replace( [ "\n", "\r", "\t" ], '', $expression ); // Remove new lines, tabs
		$expression = preg_replace( '/\s+/', ' ', $expression ); // Remove multiple spaces

		return '(' . trim( $expression ) . ')';
	}

	/**
	 * Get the host wildcard prefix.
	 *
	 * @return string
	 *
	 */
	private function get_host_wildcard() {
		return sprintf( 'http.host wildcard "%s*"', preg_replace( '#^(https?://)?#', '', Helpers::home_url() ) );
	}
}
