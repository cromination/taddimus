<?php

namespace SPC\Utils;

use SPC\Modules\Database_Optimization;
use SPC\Modules\Frontend;
use SPC\Modules\Speculative_Loading;
use SPC\Modules\Third_Party;

class Sanitization {
	/**
	 * Sanitize a Heartbeat mode setting.
	 *
	 * @param mixed $value The mode to sanitize.
	 *
	 * @return string
	 */
	public static function sanitize_heartbeat_mode( $value ) {
		$allowed_modes = [ 'default', 'reduced', 'disabled' ];

		if ( ! is_string( $value ) ) {
			return 'default';
		}

		$value = sanitize_text_field( $value );

		return in_array( $value, $allowed_modes, true ) ? $value : 'default';
	}

	/**
	 * Whitelist the auto-prefetch mode value to one of off|hover|viewport.
	 *
	 * @param mixed $value Incoming value from settings save.
	 *
	 * @return string
	 */
	public static function sanitize_prefetch_urls_mode( $value ) {
		$allowed = [
			Speculative_Loading::PREFETCH_MODE_OFF,
			Speculative_Loading::PREFETCH_MODE_HOVER,
			Speculative_Loading::PREFETCH_MODE_VIEWPORT,
		];

		return in_array( $value, $allowed, true ) ? (string) $value : Speculative_Loading::PREFETCH_MODE_OFF;
	}

	/**
	 * Sanitize excluded URLs.
	 *
	 * @param mixed $value the initial value to be sanitized.
	 * @param array $other_settings the other incoming settings.
	 *
	 * @return string
	 */
	public static function sanitize_excluded_urls( $value, $other_settings = [] ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = explode( "\n", $value );

		if ( isset( $other_settings[ Third_Party::SETTING_WOO_BYPASS_CHECKOUT_PAGE ] ) && (bool) $other_settings[ Third_Party::SETTING_WOO_BYPASS_CHECKOUT_PAGE ] && function_exists( 'wc_get_checkout_url' ) ) {
			$value[] = wc_get_checkout_url() . '*';
		}

		if ( isset( $other_settings[ Third_Party::SETTING_WOO_BYPASS_CART_PAGE ] ) && (bool) $other_settings[ Third_Party::SETTING_WOO_BYPASS_CART_PAGE ] && function_exists( 'wc_get_cart_url' ) ) {
			$value[] = wc_get_cart_url() . '*';
		}

		if ( isset( $other_settings[ Third_Party::SETTING_EDD_BYPASS_CHECKOUT_PAGE ] ) && (bool) $other_settings[ Third_Party::SETTING_EDD_BYPASS_CHECKOUT_PAGE ] && function_exists( 'edd_get_checkout_uri' ) ) {
			$value[] = edd_get_checkout_uri() . '*';
		}

		$filtered_urls = array_unique(
			array_filter(
				array_map(
					[ self::class, 'normalize_excluded_url_line' ],
					$value
				)
			)
		);

		return join( "\n", $filtered_urls );
	}

	/**
	 * Sanitize background lazy-load selectors for settings save flow.
	 *
	 * @param mixed $value Selector lines as a textarea string or array.
	 * @param array<string, mixed> $other_settings Unused. Kept for settings sanitize callback compatibility.
	 *
	 * @return string
	 */
	public static function sanitize_background_selectors( $value, $other_settings = [] ) {
		return join( "\n", self::sanitize_background_selectors_array( $value ) );
	}

	/**
	 * Sanitize background lazy-load selectors for runtime usage.
	 *
	 * @param mixed $value Selector lines as string (newline-separated) or array.
	 *
	 * @return array<string>
	 */
	public static function sanitize_background_selectors_array( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( "\n", $value );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_unique(
				array_filter(
					array_map(
						[ self::class, 'sanitize_background_selector_line' ],
						$value
					)
				)
			)
		);
	}

	/**
	 * Normalize and validate a single background lazy-load selector.
	 *
	 * @param mixed $selector CSS selector line.
	 *
	 * @return string
	 */
	private static function sanitize_background_selector_line( $selector ): string {
		if ( ! is_string( $selector ) ) {
			return '';
		}

		$selector = trim( $selector );
		if ( $selector === '' ) {
			return '';
		}

		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $selector ) === 1 ) {
			return '';
		}

		if ( self::contains_unsafe_background_selector_tokens( $selector ) ) {
			return '';
		}

		return $selector;
	}

	/**
	 * Detect unsafe structural tokens for background selector lines.
	 *
	 * @param string $selector Selector value.
	 *
	 * @return bool
	 */
	private static function contains_unsafe_background_selector_tokens( string $selector ): bool {
		return strpos( $selector, '<' ) !== false
			|| strpos( $selector, '`' ) !== false
			|| strpos( $selector, ';' ) !== false
			|| strpos( $selector, '{' ) !== false
			|| strpos( $selector, '}' ) !== false;
	}

	/**
	 * Sanitize excluded URL patterns for prefetch runtime usage.
	 *
	 * @param mixed $value URL patterns as string (newline-separated) or array.
	 *
	 * @return array<string>
	 */
	public static function sanitize_prefetch_excluded_urls_array( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( "\n", $value );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_unique(
				array_filter(
					array_map(
						[ self::class, 'sanitize_prefetch_excluded_url_line' ],
						$value
					)
				)
			)
		);
	}

	/**
	 * Normalize and validate a single excluded URL pattern for prefetch runtime usage.
	 *
	 * @param mixed $url URL pattern.
	 *
	 * @return string
	 */
	private static function sanitize_prefetch_excluded_url_line( $url ): string {
		$uri = self::normalize_excluded_url_line( $url );
		if ( $uri === '' ) {
			return '';
		}

		if ( self::contains_unsafe_prefetch_excluded_url_characters( $uri ) ) {
			return '';
		}

		return $uri;
	}

	/**
	 * Normalize a single excluded URL pattern.
	 *
	 * @param mixed $url URL pattern.
	 *
	 * @return string
	 */
	private static function normalize_excluded_url_line( $url ): string {
		if ( ! is_string( $url ) ) {
			return '';
		}

		$url = trim( $url );
		if ( $url === '' ) {
			return '';
		}

		$parsed = parse_url( str_replace( [ "\r", "\n" ], '', $url ) );
		if ( ! $parsed || ! isset( $parsed['path'] ) ) {
			return '';
		}

		$uri = $parsed['path'];
		if ( strlen( $uri ) > 1 && $uri[ strlen( $uri ) - 1 ] !== '/' && $uri[ strlen( $uri ) - 1 ] !== '*' ) {
			$uri .= '/';
		}

		if ( isset( $parsed['query'] ) ) {
			$uri .= "?{$parsed['query']}";
		}

		return $uri;
	}

	/**
	 * Detect unsafe characters in prefetch excluded URL patterns.
	 *
	 * @param string $value Excluded URL value.
	 *
	 * @return bool
	 */
	private static function contains_unsafe_prefetch_excluded_url_characters( string $value ): bool {
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value ) === 1 ) {
			return true;
		}

		return strpos( $value, '<' ) !== false
			|| strpos( $value, '>' ) !== false
			|| strpos( $value, '\'' ) !== false
			|| strpos( $value, '"' ) !== false
			|| strpos( $value, '`' ) !== false;
	}

	/**
	 * Sanitize a list of external domains for the dns-prefetch / preconnect resource hint settings.
	 *
	 * Accepts a newline-separated string or an array. Returns a deduped, newline-separated string
	 * of normalized hostnames. Strips schemes and paths; rejects IPs, single-label hosts, own-host entries.
	 *
	 * @param mixed $value Domain list as newline-separated string or array.
	 * @param array<string, mixed> $other_settings Unused. Kept for settings sanitize callback compatibility.
	 *
	 * @return string Newline-separated, deduplicated list of valid hostnames.
	 */
	public static function sanitize_prefetch_domains( $value, $other_settings = [] ): string {
		if ( is_string( $value ) ) {
			$value = explode( "\n", $value );
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		$home_host = self::get_prefetch_home_host();
		$hosts     = [];

		foreach ( $value as $line ) {
			$host = self::normalize_prefetch_domain_line( $line );
			if ( $host === '' || $host === $home_host ) {
				continue;
			}
			$hosts[] = $host;
		}

		return join( "\n", array_unique( $hosts ) );
	}

	/**
	 * Normalize and validate a single prefetch / preconnect domain entry.
	 *
	 * @param mixed $line Raw input line.
	 *
	 * @return string Normalized hostname, or empty string if rejected.
	 */
	private static function normalize_prefetch_domain_line( $line ) {
		if ( ! is_string( $line ) ) {
			return '';
		}

		$line = sanitize_text_field( $line );
		if ( strpos( $line, '//' ) === false ) {
			$line = '//' . $line;
		}

		$host = strtolower( (string) wp_parse_url( $line, PHP_URL_HOST ) );

		if (
			strpos( $host, '.' ) === false
			|| filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) === false
			|| filter_var( $host, FILTER_VALIDATE_IP ) !== false
		) {
			return '';
		}

		return $host;
	}

	/**
	 * Resolve the current site's host for own-host stripping.
	 *
	 * @return string Lowercase host, or empty string when unavailable.
	 */
	private static function get_prefetch_home_host() {
		$parts = wp_parse_url( home_url() );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}

		return strtolower( $parts['host'] );
	}

	/**
	 * Sanitize preloaded sitemap URLs.
	 *
	 * @param mixed $value the initial value to be sanitized.
	 * @param array $other_settings the other incoming settings.
	 *
	 * @return string
	 */
	public static function sanitize_preloaded_sitemap_urls( $value, $other_settings = [] ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$sitemap_urls        = [];
		$parsed_sitemap_urls = explode( "\n", $value );

		foreach ( $parsed_sitemap_urls as $single_sitemap_url ) {

			$parsed_sitemap_url = parse_url( str_replace( [ "\r", "\n" ], '', $single_sitemap_url ) );

			if ( $parsed_sitemap_url && isset( $parsed_sitemap_url['path'] ) ) {

				$uri = $parsed_sitemap_url['path'];

				if ( strtolower( substr( $uri, - 3 ) ) === 'xml' ) {

					if ( isset( $parsed_sitemap_url['query'] ) ) {
						$uri .= "?{$parsed_sitemap_url['query']}";
					}

					$sitemap_urls[] = $uri;
				}
			}
		}

		return join( "\n", $sitemap_urls );
	}

	/**
	 * Sanitize Log Verbosity.
	 *
	 * @param mixed $value the initial value to be sanitized.
	 *
	 * @return int
	 */
	public static function sanitize_log_verbosity( $value, $other_settings ) {
		$value = (int) $value;

		if ( ! in_array( $value, [ Logger::VERBOSITY_STANDARD, Logger::VERBOSITY_HIGH ], true ) ) {
			return Logger::VERBOSITY_STANDARD;
		}

		return $value;
	}

	/**
	 * Sanitize database optimization interval.
	 *
	 * @param mixed $value the initial value to be sanitized.
	 * @param array $other_settings the other incoming settings.
	 * @return string
	 */
	public static function sanitize_database_optimization_interval( $value, $other_settings ) {
		if ( ! isset( Database_Optimization::get_schedule_options()[ $value ] ) ) {
			return Database_Optimization::NEVER;
		}

		return $value;
	}
	public static function sanitize_lazy_load_behaviour( $value, $other_settings ) {

		if ( ! isset( Frontend::get_lazyload_behaviours()[ $value ] ) ) {
			return Frontend::LAZY_LOAD_BEHAVIOUR_ALL;
		}

		return $value;
	}
}
