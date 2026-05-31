<?php

namespace SPC\Utils;

use SPC\Constants;
use SPC\Modules\Dashboard;
use SPC\Services\Settings_Store;

class Helpers {

	private const BYPASS_CACHE_REASON_HEADER = 'X-WP-CF-Super-Cache-Disabled-Reason';

	/**
	 * Check if the request is a cacheable request
	 *
	 * @param string $reason The reason why the request is not cacheable.
	 *
	 * @return void
	 */
	public static function bypass_reason_header( $reason = '' ) {
		if ( empty( $reason ) ) {
			return;
		}

		header( sprintf( '%s: %s', self::BYPASS_CACHE_REASON_HEADER, $reason ) );
	}

	/**
	 * Checks if we have the cache bypass reason header.
	 *
	 * @return bool
	 */
	public static function has_cache_bypass_reason_header() {
		$headers = array_map(
			function ( $header ) {
				return strstr( $header, ':', true );
			},
			headers_list()
		);

		return in_array( self::BYPASS_CACHE_REASON_HEADER, $headers, true );
	}

	/**
	 * Get the second level domain of the site.
	 *
	 * @return string
	 */
	public static function get_second_level_domain() {
		$site_hostname = parse_url( home_url(), PHP_URL_HOST );

		if ( is_null( $site_hostname ) ) {
			return '';
		}

		// get the domain name from the hostname
		$site_domain = preg_replace( '/^www\./', '', $site_hostname );

		return $site_domain;
	}

	/**
	 * Get the menu icon.
	 *
	 * @param string $fill The fill color of the icon.
	 *
	 * @return string
	 */
	public static function get_menu_icon( $fill = '#a7aaad' ) {
		$svg = '<svg width="185" height="229" viewBox="0 0 185 229" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#spc-clip-path)"><path d="M12.322 180.443L160.356 210.236L159.512 114.191H166.264L144.489 88.8715V189.305L35.7846 165.167C31.7335 164.492 29.286 158.078 29.286 153.942V128.623V36.8825L139.003 60.5139V43.0435L12.4064 15.6143L12.322 180.443Z" fill="' . esc_attr( $fill ) . '"/><path d="M134.446 124.992V161.114C134.469 162.372 134.21 163.618 133.69 164.763C133.169 165.907 132.399 166.921 131.437 167.73C130.474 168.539 129.343 169.122 128.126 169.438C126.909 169.754 125.637 169.793 124.402 169.554L42.1988 150.733L38.3165 143.475L42.1988 142.884L36.0377 131.913L115.962 149.89V131.322L46.0811 116.721C44.1149 116.362 42.3402 115.316 41.0729 113.771C39.8055 112.225 39.1278 110.28 39.1604 108.281V69.7116C39.1289 68.4282 39.3906 67.1545 39.9255 65.9875C40.4605 64.8204 41.2546 63.7908 42.2475 62.977C43.2403 62.1631 44.4058 61.5865 45.6551 61.291C46.9044 60.9956 48.2047 60.989 49.457 61.2718L133.855 78.9953L124.74 87.4351L128.875 87.9415L122.039 96.9721L55.7868 83.1308V101.192L128.031 116.721C129.875 117.177 131.511 118.241 132.675 119.742C133.839 121.243 134.463 123.093 134.446 124.992Z" fill="' . esc_attr( $fill ) . '"/><path d="M166.686 121.026V219.097L6.49863 184.325V8.35538L138.919 36.3755V29.1172L0 0L0.75958 190.992L173.438 228.38L173.016 138.581H185L166.686 121.026Z" fill="' . esc_attr( $fill ) . '"/></g><defs><clipPath id="spc-clip-path"><rect width="185" height="228.38" fill="white"/></clipPath></defs></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Check if the current page is a SPC admin page.
	 *
	 * @return bool
	 */
	public static function is_spc_admin_page() {
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		// About page is not a SPC page.
		if ( strpos( $_GET['page'], 'ti-about' ) === 0 ) {
			return false;
		}

		if ( strpos( $_GET['page'], Dashboard::PAGE_SLUG ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the plugin content directory path.
	 *
	 * @return string
	 */
	public static function get_plugin_content_dir() {
		$parts = parse_url( home_url() );

		return WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$parts['host']}";
	}

	/**
	 * Create the plugin content directory tree and its nginx.conf file.
	 *
	 * @return void
	 */
	public static function create_plugin_content_dir() {
		$parts = parse_url( home_url() );
		$path  = WP_CONTENT_DIR . '/wp-cloudflare-super-page-cache/';

		if ( ! file_exists( $path ) && wp_mkdir_p( $path ) ) {
			file_put_contents( "{$path}index.php", '<?php // Silence is golden' );
		}

		$path .= $parts['host'];

		if ( ! file_exists( $path ) && wp_mkdir_p( $path ) ) {
			file_put_contents( "{$path}/index.php", '<?php // Silence is golden' );
		}

		$nginx_conf = "{$path}/nginx.conf";

		if ( ! file_exists( $nginx_conf ) ) {
			file_put_contents( $nginx_conf, '' );
		}
	}

	/**
	 * Delete the plugin's host-scoped content directory recursively.
	 *
	 * @return void
	 */
	public static function delete_plugin_content_dir() {
		$path = self::get_plugin_content_dir();

		if ( file_exists( $path ) ) {
			self::delete_directory_recursive( $path );
		}
	}

	/**
	 * Delete a directory and everything under it.
	 *
	 * @param string $dir Directory to delete.
	 *
	 * @return bool
	 */
	public static function delete_directory_recursive( $dir ) {
		if ( ! class_exists( 'RecursiveDirectoryIterator' ) || ! class_exists( 'RecursiveIteratorIterator' ) ) {
			return false;
		}

		$it    = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}

		rmdir( $dir );

		return true;
	}

	/**
	 * Get the plugin content directory url.
	 *
	 * @return string
	 */
	public static function get_plugin_content_dir_url() {
		$parts = parse_url( home_url() );

		return str_replace(
			[
				"https://{$parts['host']}",
				"http://{$parts['host']}",
			],
			'',
			content_url( "wp-cloudflare-super-page-cache/{$parts['host']}" )
		);
	}

	/**
	 * Get the current url.
	 *
	 * @return string
	 */
	public static function get_current_url() {
		return function_exists( 'swcfpc_normalize_url' ) ? swcfpc_normalize_url( null ) : null;
	}
	/**
	 * Get the current full url.
	 *
	 * @return string
	 */
	public static function get_current_absolute_url() {
		$current_url = self::get_current_url();
		$host        = $_SERVER['HTTP_HOST'] ?? '';
		if ( empty( $host ) ) {
			return $current_url;
		}
		/**
		 * If the current url does not start with the host, add the host to the current url.
		 * The scheme is just to normalize the url.
		 */
		return strpos( $current_url, $host ) === 0 ? "http://{$current_url}" : "http://{$host}/{$current_url}";
	}
	/**
	 * Get the url id.
	 *
	 * @param string $url The url.
	 *
	 * @return int
	 */
	public static function get_url_id( $url ) {
		srand( crc32( $url ) );
		$random_id = rand();
		srand();
		return $random_id;
	}

	/**
	 * Get the wp-config.php path.
	 *
	 * wp-config.php can be placed one level up from the root directory.
	 *
	 * @return string
	 */
	public static function get_wp_config_path() {
		if ( is_file( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}

		if ( is_file( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			return dirname( ABSPATH ) . '/wp-config.php';
		}

		return ABSPATH . 'wp-config.php';
	}

	/**
	 * Check if the current page is the login page.
	 *
	 * @return bool
	 */
	public static function is_login_page() {
		return in_array( $GLOBALS['pagenow'] ?? '', [ 'wp-login.php', 'wp-register.php' ], true );
	}

	/**
	 * Check if the current URL has a trailing slash.
	 *
	 * @return bool
	 */
	public static function does_current_url_have_trailing_slash() {
		return (bool) preg_match( '/\/$/', $_SERVER['REQUEST_URI'] ?? '' );
	}

	/**
	 * Check if the current request is an API request.
	 *
	 * @return bool
	 */
	public static function is_api_request() {
		$rest_base    = trim( parse_url( rest_url(), PHP_URL_PATH ), '/' );
		$request_path = trim( $_SERVER['REQUEST_URI'] ?? '', '/' );

		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || strpos( $request_path, $rest_base ) === 0 ) {
			return true;
		}

		if ( strpos( $request_path, 'wc-api' ) === 0 ) {
			return true;
		}

		if ( strpos( $request_path, 'edd-api' ) === 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Case-insensitive wildcard match (`*` expands to `.*`).
	 *
	 * @param string $pattern The pattern to match.
	 * @param string $subject The subject to match.
	 *
	 * @return bool
	 */
	public static function wildcard_match( $pattern, $subject ) {
		$pattern = '#^' . preg_quote( $pattern ) . '$#i';
		$pattern = str_replace( '\*', '.*', $pattern );

		return (bool) preg_match( $pattern, $subject );
	}

	/**
	 * Rebuild a URL from a `parse_url()` result.
	 *
	 * @param array<string, string|int> $parsed_url The parsed URL.
	 *
	 * @return string
	 */
	public static function get_unparsed_url( $parsed_url ) {
		$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
		$pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass     = ( $user || $pass ) ? "$pass@" : '';
		$path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
		$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

		return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
	}

	/**
	 * Get the list of query params to ignore when computing the cache key.
	 *
	 * @return array<int, string>
	 */
	public static function get_ignored_query_params() {
		return apply_filters( 'swcfpc_ignored_query_params', Constants::IGNORED_QUERY_PARAMS );
	}

	/**
	 * Get the home URL, with multisite + scheme/pagenow handling.
	 *
	 * @param int|null    $blog_id The blog ID.
	 * @param string      $path    The path.
	 * @param string|null $scheme  The scheme.
	 *
	 * @return string
	 */
	public static function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
		global $pagenow;

		if ( empty( $blog_id ) || ! is_multisite() ) {
			$url = get_option( 'home' );
		} else {
			switch_to_blog( $blog_id );
			$url = get_option( 'home' );
			restore_current_blog();
		}

		if ( ! in_array( $scheme, [ 'http', 'https', 'relative' ], true ) ) {
			if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {
				$scheme = 'https';
			} else {
				$scheme = parse_url( $url, PHP_URL_SCHEME );
			}
		}

		$url = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return $url;
	}

	/**
	 * Get the home URL for the current blog.
	 *
	 * @param string      $path   The path.
	 * @param string|null $scheme The scheme.
	 *
	 * @return string
	 */
	public static function home_url( $path = '', $scheme = null ) {
		return self::get_home_url( null, $path, $scheme );
	}

	/**
	 * Check if a URL points to a different host than the current site.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_external_link( $url ) {
		$source = parse_url( home_url() );
		$target = parse_url( $url );

		if ( ! $source || empty( $source['host'] ) || ! $target || empty( $target['host'] ) ) {
			return false;
		}

		return strcasecmp( $target['host'], $source['host'] ) !== 0;
	}

	/**
	 * Check if the current user can purge the cache.
	 *
	 * @return bool
	 */
	public static function can_current_user_purge_cache() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$allowed_roles = Settings_Store::get_instance()->get( Constants::SETTING_PURGE_ROLES );
		if ( count( $allowed_roles ) < 1 ) {
			return false;
		}

		$user = wp_get_current_user();
		foreach ( $allowed_roles as $role_name ) {
			if ( in_array( $role_name, (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
