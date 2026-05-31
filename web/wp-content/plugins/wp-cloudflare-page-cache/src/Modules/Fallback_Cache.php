<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;
use SPC\Utils\Logger;

class Fallback_Cache implements Module_Interface {
	private const ENTRY_STATE_MISSING = 'missing';
	private const ENTRY_STATE_FRESH   = 'fresh';
	private const ENTRY_STATE_STALE   = 'stale';
	private const ENTRY_STATE_EXPIRED = 'expired';
	private const REFRESH_USER_AGENT  = 'ua-swcfpc-fc-refresh';
	private const WARMER_USER_AGENT   = 'ua-swcfpc-fc';
	private const REFRESH_LOCK_TTL    = 30;

	/**
	 * @var array<string, mixed>|false
	 */
	private $fallback_cache_ttl_registry = false;

	private bool $fallback_cache = false;

	public function init() {
		$settings = Settings_Store::get_instance();

		$fb_enabled    = (int) $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE, 0 ) > 0;
		$fb_curl       = (int) $settings->get( Constants::SETTING_FALLBACK_CACHE_CURL, 0 ) > 0;
		$cache_enabled = (int) $settings->get( Constants::SETTING_CF_CACHE_ENABLED, 0 ) > 0;

		if ( $fb_enabled ) {
			if ( ! $this->fallback_cache_init_ttl_registry() ) {
				$this->fallback_cache_ttl_registry = [];
				$this->fallback_cache_update_ttl_registry();
			}

			if ( (int) $settings->get( Constants::SETTING_FALLBACK_CACHE_LIFESPAN, 0 ) > 0 ) {
				$this->fallback_cache_delete_expired_pages();
			}
		}

		if ( ! $cache_enabled || ! $fb_enabled || $fb_curl ) {
			$this->fallback_cache_advanced_cache_disable();
		}

		if ( $cache_enabled && $fb_enabled && ! $fb_curl ) {
			$this->fallback_cache_advanced_cache_enable();
		}

		if ( $fb_enabled && ! is_admin() && ! Helpers::is_login_page() && $fb_curl ) {
			if ( $this->is_refresh_request() ) {
				ob_start();
			}

			add_action( 'shutdown', [ $this, 'shutdown_add_url_to_cache' ], PHP_INT_MAX );
		}

		$this->maybe_serve_cached_page();
	}

	/**
	 * @return void
	 */
	private function maybe_serve_cached_page() {
		$settings = Settings_Store::get_instance();

		$is_cli         = defined( 'WP_CLI' ) && WP_CLI;
		$is_get_request = isset( $_SERVER['REQUEST_METHOD'] ) && strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) === 0;

		if (
			! $is_cli
			&& $is_get_request
			&& ! is_admin()
			&& ! Helpers::is_login_page()
			&& $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE )
			&& $settings->is_cache_enabled()
			&& ! wp_doing_cron()
		) {
			$this->fallback_cache_retrive_current_page();
		}
	}

	/**
	 * @return void
	 */
	public function fallback_cache_enable() {
		$this->fallback_cache = true;
	}


	/**
	 * @return void
	 */
	public function fallback_cache_disable() {
		$this->fallback_cache = false;
	}


	/**
	 * @return void
	 */
	public function fallback_cache_save_config() {
		$cache_path = Helpers::get_plugin_content_dir() . '/';

		file_put_contents( "{$cache_path}main_config.php", '<?php $swcfpc_config=\'' . addslashes( json_encode( Settings_Store::get_instance()->get_fallback_cache_runtime_config() ) ) . '\'; ?>' );

		if ( is_array( $this->fallback_cache_ttl_registry ) ) {
			file_put_contents( "{$cache_path}ttl_registry.json", json_encode( $this->fallback_cache_ttl_registry ) );
		}
	}

	/**
	 * @return void
	 */
	public function fallback_cache_delete_config() {
		$cache_path = Helpers::get_plugin_content_dir() . '/';

		if ( file_exists( "{$cache_path}ttl_registry.json" ) ) {
			@unlink( "{$cache_path}ttl_registry.json" );
		}

		if ( file_exists( "{$cache_path}ttl_registry.php" ) ) {
			@unlink( "{$cache_path}ttl_registry.php" );
		}

		if ( file_exists( "{$cache_path}main_config.php" ) ) {
			@unlink( "{$cache_path}main_config.php" );
		}
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_advanced_cache_enable( bool $force_wp_cache = false ) {

		$this->fallback_cache_init_directory();

		$advanced_cache_dest = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( ! defined( 'SWCFPC_ADVANCED_CACHE' ) || ! file_exists( $advanced_cache_dest ) ) {

			$advanced_cache_source = SWCFPC_PLUGIN_PATH . 'assets/advanced-cache.php';

			if ( file_exists( $advanced_cache_dest ) && ! @unlink( $advanced_cache_dest ) ) {
				Logger::log( 'fallback_cache::fallback_cache_advanced_cache_enable', 'Unable to remove the old advanced-cache.php from wp-content directory' );
				return false;
			}

			if ( file_put_contents( $advanced_cache_dest, file_get_contents( $advanced_cache_source ) ) === false ) {
				Logger::log( 'fallback_cache::fallback_cache_advanced_cache_enable', 'Unable to copy advanced-cache.php to wp-content directory' );
				return false;
			}

			if ( $force_wp_cache || ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && WP_CACHE === false ) ) {

				if ( ! $this->fallback_cache_add_define_cache_wp_config() ) {
					return false;
				}
			}

			$this->fallback_cache_save_config();

		} else {

			$this->fallback_cache_save_config();

			if ( ! defined( 'WP_CACHE' ) || ( defined( 'WP_CACHE' ) && WP_CACHE === false ) ) {

				if ( ! $this->fallback_cache_add_define_cache_wp_config() ) {
					return false;
				}
			}
		}

		do_action( 'swcfpc_advanced_cache_after_enable' );

		return true;
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_advanced_cache_disable() {

		if ( defined( 'SWCFPC_ADVANCED_CACHE' ) ) {

			if ( file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
				@unlink( WP_CONTENT_DIR . '/advanced-cache.php' );
			}

			$this->fallback_cache_delete_config();

			if ( ! is_multisite() && defined( 'WP_CACHE' ) ) {
				$this->fallback_cache_add_define_cache_wp_config( false );
			}

			$this->fallback_cache_purge_all();

		}

		do_action( 'swcfpc_advanced_cache_after_disable' );

		return true;
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_add_define_cache_wp_config( bool $turn_it_on = true ) {

		if ( wp_doing_ajax() ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'AJAX Request' );
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'AJAX Request' );
		}

		if ( $turn_it_on && defined( 'WP_CACHE' ) && WP_CACHE ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'WP_CACHE already defined' );
			return false;
		}

		if ( defined( 'IS_PRESSABLE' ) && IS_PRESSABLE ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'IS_PRESSABLE defined' );
			return false;
		}

		$config_file_path = Helpers::get_wp_config_path();

		if ( ! file_exists( $config_file_path ) ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'Unable to find wp-config.php' );
			return false;
		}

		if ( ! $this->fallback_cache_is_wp_config_writable() ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'wp-config.php is not writable' );
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$config_contents = $wp_filesystem->get_contents( $config_file_path );
		if ( $config_contents === false ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'Unable to read wp-config.php' );
			return false;
		}
		if ( empty( $config_contents ) ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'wp-config.php is empty' );
			return false;
		}
		$config_file = preg_split( '/\R/u', $config_contents );

		if ( ! $config_file ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'Unable to parse wp-config file.' );
			return false;
		}

		$config_file_count = count( $config_file );

		$turn_it_on = $turn_it_on ? 'true' : 'false';

		/**
		 * Filter allow to change the value of WP_CACHE constant
		 *
		 * @since 2.1
		 *
		 * @param string $turn_it_on The value of WP_CACHE constant.
		 */
		$turn_it_on = apply_filters( 'swcfpc_set_wp_cache_define', $turn_it_on );

		$is_wp_cache_exist = false;

		$constant = "define('WP_CACHE', {$turn_it_on}); // Added by WP Cloudflare Super Page Cache";

		for ( $i = 0; $i < $config_file_count; ++$i ) {

			if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',(.*)\)/', $config_file[ $i ], $match ) ) {
				continue;
			}

			if ( 'WP_CACHE' === $match[1] ) {

				$is_wp_cache_exist = true;
			}
		}

		if ( ! $is_wp_cache_exist && 'true' === $turn_it_on ) {
			$new_config_contents = preg_replace(
				'/(<\?php)/i',
				"<?php\r\n{$constant}\r\n",
				$config_contents,
				1
			);
		} else {
			$new_config_contents = preg_replace( '/^\s*define\(\s*\'WP_CACHE\'\s*,\s*([^\s\)]*)\s*\).+/m', $constant, $config_contents );
		}

		if ( $new_config_contents === null ) {
			Logger::log( 'fallback_cache::fallback_cache_add_define_cache_wp_config', 'preg_replace failed with error: ' . preg_last_error() );
			return false;
		}

		return $wp_filesystem->put_contents( $config_file_path, $new_config_contents, FS_CHMOD_FILE );
	}

	/**
	 * @return bool
	 */
	public function fallback_cache_is_wp_config_writable() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;
		$config_file_path = Helpers::get_wp_config_path();

		return $wp_filesystem->is_writable( $config_file_path );
	}

	/**
	 * @return bool
	 */
	public function fallback_cache_is_wp_content_writable() {
		return is_writable( WP_CONTENT_DIR );
	}

	/**
	 * @return void
	 */
	public function shutdown_add_url_to_cache() {
		$this->fallback_cache_add_current_url_to_cache();
	}

	public function shutdown_trigger_async_refresh( string $cache_key, string $cache_path ): void {
		$this->trigger_async_refresh( $cache_key, $cache_path );
	}

	/**
	 * @return void
	 */
	public function fallback_cache_add_current_url_to_cache( ?string $url = null, bool $force_cache = false ) {
		if ( ! $force_cache && (
				! $this->fallback_cache || // Cache not enabled
				$this->fallback_cache_is_url_to_exclude() || // URL excluded
				! isset( $_SERVER['REQUEST_METHOD'] ) || // No request method
				strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) !== 0 // Not GET
			) ) {
			return;
		}

		if ( $this->is_cache_warmer_request() ) {
			return;
		}

		$cache_path = $this->fallback_cache_init_directory();
		$cache_key  = $this->fallback_cache_get_current_page_cache_key( $url );

		if ( false === $cache_key ) {
			return;
		}

		$entry_state = $this->fallback_cache_get_entry_state( $cache_key, $cache_path );

		if ( $this->is_refresh_request() && self::ENTRY_STATE_FRESH === $entry_state ) {
			$this->release_refresh_lock( $cache_key, $cache_path );
			return;
		}

		if ( self::ENTRY_STATE_FRESH === $entry_state ) {
			return;
		}

		if ( $this->is_refresh_request() ) {
			$this->store_current_response_body( $cache_path, $cache_key );
			return;
		}

		if ( $this->refresh_lock_exists( $cache_key, $cache_path ) ) {
			return;
		}

		// absolute URI in multisite aware environment
		if ( empty( $url ) ) {
			$parts = parse_url( home_url() );
			$url   = "{$parts['scheme']}://{$parts['host']}" . add_query_arg( null, null );
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'    => defined( 'SWCFPC_CURL_TIMEOUT' ) ? SWCFPC_CURL_TIMEOUT : 10,
				'sslverify'  => false,
				'user-agent' => self::WARMER_USER_AGENT,
			]
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			return;
		}

		$metadata = $this->build_cache_entry_metadata();

		$body = wp_remote_retrieve_body( $response );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$body .= "\n<!-- Page retrieved from Super Page Cache fallback cache via cURL - page generated @ " . gmdate( 'Y-m-d H:i:s' ) . ' - fallback cache expiration @ ' . ( 0 < $metadata['fresh_until'] ? gmdate( 'Y-m-d H:i:s', $metadata['fresh_until'] ) : 'never expires' ) . " - cache key {$cache_key} -->";
		}

		$body = apply_filters( 'swcfpc_curl_fallback_cache_html', $body, $cache_key );

		if ( ! is_string( $body ) ) {
			return;
		}

		file_put_contents( $cache_path . $cache_key, $body );

		$this->fallback_cache_set_single_ttl( $cache_key, $metadata );
		$this->fallback_cache_update_ttl_registry();
		$this->release_refresh_lock( $cache_key, $cache_path );

		if ( Settings_Store::get_instance()->get( Constants::SETTING_FALLBACK_CACHE_SAVE_HEADERS, 0 ) > 0 ) {
			$this->fallback_cache_save_headers( $cache_path, $cache_key );
		}
	}

	private function store_current_response_body( string $cache_path, string $cache_key ): void {
		$captured_response_body = ob_get_level() > 0 ? ob_get_clean() : '';

		if ( ! is_string( $captured_response_body ) || '' === $captured_response_body ) {
			$this->release_refresh_lock( $cache_key, $cache_path );
			return;
		}

		if ( Settings_Store::get_instance()->get( Constants::SETTING_FALLBACK_CACHE_HTTP_RESPONSE_CODE, 0 ) > 0 ) {
			$http_status = http_response_code();

			if ( false !== $http_status && $http_status >= 400 && $http_status < 600 ) {
				$this->release_refresh_lock( $cache_key, $cache_path );
				return;
			}
		}

		if ( ! $this->refresh_lock_exists( $cache_key, $cache_path ) ) {
			return;
		}

		$metadata = $this->build_cache_entry_metadata();
		$body     = $captured_response_body;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$body .= "\n<!-- Page retrieved from Super Page Cache fallback cache - page generated @ " . gmdate( 'Y-m-d H:i:s' ) . ' - fallback cache expiration @ ' . ( 0 < $metadata['fresh_until'] ? gmdate( 'Y-m-d H:i:s', $metadata['fresh_until'] ) : 'never expires' ) . " - cache key {$cache_key} -->";
		}

		$body = apply_filters( 'swcfpc_normal_fallback_cache_html', $body, $cache_key );

		if ( ! is_string( $body ) ) {
			$this->release_refresh_lock( $cache_key, $cache_path );
			return;
		}

		file_put_contents( $cache_path . $cache_key, $body );

		$this->fallback_cache_set_single_ttl( $cache_key, $metadata );
		$this->fallback_cache_update_ttl_registry();
		$this->release_refresh_lock( $cache_key, $cache_path );

		if ( Settings_Store::get_instance()->get( Constants::SETTING_FALLBACK_CACHE_SAVE_HEADERS, 0 ) > 0 ) {
			$this->fallback_cache_save_headers( $cache_path, $cache_key );
		}
	}


	/**
	 * @return string
	 */
	public function fallback_cache_remove_url_parameters( string $url ) {
		$url_parsed       = parse_url( $url );
		$url_query_params = [];

		if ( array_key_exists( 'query', $url_parsed ) ) {

			if ( $url_parsed['query'] === '' ) {

				$url = substr( trim( $url ), 0, -1 );

			} else {

				parse_str( $url_parsed['query'], $url_query_params );

				$ignored_query_params = Helpers::get_ignored_query_params();

				foreach ( $ignored_query_params as $ignored_query_param ) {

					if ( array_key_exists( $ignored_query_param, $url_query_params ) ) {

						unset( $url_query_params[ $ignored_query_param ] );
					}
				}

				if ( count( $url_query_params ) > 0 ) {

					$new_url_query_params = http_build_query( $url_query_params );
					$url_parsed['query']  = $new_url_query_params;

				} else {
					unset( $url_parsed['query'] );
				}

				$url = Helpers::get_unparsed_url( $url_parsed );
			}
		}

		return $url;
	}


	/**
	 * @return string|false
	 */
	public function fallback_cache_get_current_page_cache_key( ?string $url = null ) {
		$replacements = [ '://', '/', '?', '#', '&', '.', ',', '@', '-', '\'', '"', '%', ' ', '\\', '=' ];

		if ( ! is_null( $url ) ) {

			$parts = parse_url( strtolower( $url ) );

			if ( ! $parts ) {
				return false;
			}

			$current_uri = isset( $parts['path'] ) ? $parts['path'] : '/';

			if ( isset( $parts['query'] ) ) {
				$current_uri .= "?{$parts['query']}";
			}

			if ( $current_uri == '/' ) {
				$current_uri = $parts['host'];
			}
		} else {
			$current_uri = $_SERVER['REQUEST_URI'];

			if ( $current_uri == '/' ) {
				$current_uri = $_SERVER['HTTP_HOST'];
			}

			$current_uri = trim( $current_uri, '/' );

			if ( strpos( $current_uri, '?' ) === 0 ) {
				$current_uri = $_SERVER['HTTP_HOST'] . $current_uri;
			}
		}

		$cache_key = str_replace( $replacements, '_', $this->fallback_cache_remove_url_parameters( $current_uri ) );
		$cache_key = trim( $cache_key, '_' );
		$cache_key = sha1( $cache_key );

		return $cache_key . '.html';
	}


	/**
	 * @return string
	 */
	public function fallback_cache_init_directory() {

		$cache_path = Helpers::get_plugin_content_dir() . '/fallback_cache/';

		if ( ! file_exists( $cache_path ) ) {
			wp_mkdir_p( $cache_path );
		}

		if ( file_exists( $cache_path ) && ! file_exists( "{$cache_path}index.php" ) ) {
			file_put_contents( "{$cache_path}index.php", '<?php // Silence is golden' );
		}

		return $cache_path;
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_init_ttl_registry() {

		$this->fallback_cache_ttl_registry = get_option( 'swcfpc_fc_ttl_registry', false );

		if ( ! $this->fallback_cache_ttl_registry ) {
			return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	public function fallback_cache_update_ttl_registry() {
		update_option( 'swcfpc_fc_ttl_registry', $this->fallback_cache_ttl_registry );
	}

	/**
	 * @param mixed $value
	 * @return void
	 */
	public function fallback_cache_set_single_ttl( string $name, $value ) {

		if ( ! is_array( $this->fallback_cache_ttl_registry ) ) {
			$this->fallback_cache_ttl_registry = [];
		}

		if ( is_array( $value ) ) {
			$this->fallback_cache_ttl_registry[ trim( $name ) ] = $value;
		} else {
			$this->fallback_cache_ttl_registry[ trim( $name ) ] = (int) $value;
		}
	}

	/**
	 * @param mixed $default_value
	 * @return mixed
	 */
	public function fallback_cache_get_single_ttl( string $name, $default_value = false ) {
		if ( ! is_array( $this->fallback_cache_ttl_registry ) || ! isset( $this->fallback_cache_ttl_registry[ $name ] ) ) {
			return $default_value;
		}

		if ( is_array( $this->fallback_cache_ttl_registry[ $name ] ) ) {
			return $this->fallback_cache_ttl_registry[ $name ];
		}

		return (int) $this->fallback_cache_ttl_registry[ $name ];
	}

	/**
	 * @return bool
	 */
	public function fallback_cache_is_expired_page( string $cache_key ) {
		return self::ENTRY_STATE_EXPIRED === $this->fallback_cache_get_entry_state( $cache_key );
	}


	/**
	 * @return void
	 */
	public function fallback_cache_delete_expired_pages() {
		if ( is_array( $this->fallback_cache_ttl_registry ) ) {

			$cache_path = $this->fallback_cache_init_directory();

			foreach ( $this->fallback_cache_ttl_registry as $cache_key => $ttl ) {

				if ( self::ENTRY_STATE_EXPIRED === $this->fallback_cache_get_entry_state( $cache_key, $cache_path ) ) {
					if ( file_exists( $cache_path . $cache_key ) ) {
						@unlink( $cache_path . $cache_key );
					}

					@unlink( "{$cache_path}{$cache_key}.headers.json" );
					$this->release_refresh_lock( $cache_key, $cache_path );
					unset( $this->fallback_cache_ttl_registry[ $cache_key ] );

					do_action( 'swcfpc_fallback_cache_expired_url', $cache_key );
				}
			}

			$this->fallback_cache_update_ttl_registry();
		}
	}


	/**
	 * @return void
	 */
	public function fallback_cache_purge_all() {

		$cache_path = $this->fallback_cache_init_directory();

		$files = glob( $cache_path . '/*' );

		foreach ( $files as $file ) {

			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}

		$this->fallback_cache_ttl_registry = [];
		$this->fallback_cache_update_ttl_registry();
		do_action( 'swcfpc_fallback_cache_purged_all' );
	}


	/**
	 * @param array<int, string>|mixed $urls
	 * @return void
	 */
	public function fallback_cache_purge_urls( $urls ) {

		$cache_path = $this->fallback_cache_init_directory();

		if ( is_array( $urls ) ) {

			foreach ( $urls as $single_url ) {

				$cache_key = $this->fallback_cache_get_current_page_cache_key( $single_url );

				if ( false === $cache_key ) {
					continue;
				}

				$headers_file = "{$cache_path}{$cache_key}.headers.json";
				$lock_file    = $this->get_refresh_lock_path( $cache_key, $cache_path );

				if ( file_exists( $cache_path . $cache_key ) ) {
					@unlink( $cache_path . $cache_key );
				}

				if ( is_array( $this->fallback_cache_ttl_registry ) ) {
					unset( $this->fallback_cache_ttl_registry[ $cache_key ] );
				}

				if ( file_exists( $headers_file ) ) {
					@unlink( $headers_file );
				}

				if ( file_exists( $lock_file ) ) {
					@unlink( $lock_file );
				}
				do_action( 'swcfpc_fallback_cache_purged_url', $cache_key );
			}

			$this->fallback_cache_update_ttl_registry();

		}
	}

	/**
	 * @return bool
	 */
	public function fallback_cache_retrive_current_page() {
		$cache_path = $this->fallback_cache_init_directory();
		$cache_key  = $this->fallback_cache_get_current_page_cache_key();

		if ( $this->should_prevent_cache_because_of_trailingslash() ) {
			Helpers::bypass_reason_header( 'Not a slashed URL' );

			return false;
		}

		if ( $this->fallback_cache_is_cookie_to_exclude() ) {
			Helpers::bypass_reason_header( 'Excluded cookie' );

			return false;
		}

		if ( $this->is_refresh_request() ) {
			return false;
		}

		$entry_state = $this->fallback_cache_get_entry_state( $cache_key, $cache_path );

		if ( self::ENTRY_STATE_FRESH === $entry_state || self::ENTRY_STATE_STALE === $entry_state ) {

			$settings       = Settings_Store::get_instance();
			$stored_headers = $this->fallback_cache_get_stored_headers( $cache_path, $cache_key );

			if ( $settings->get( Constants::SETTING_STRIP_RESPONSE_COOKIES, 0 ) > 0 ) {
				header_remove( 'Set-Cookie' );
			}

			header_remove( 'Pragma' );
			header_remove( 'Expires' );
			header_remove( 'Cache-Control' );
			header_remove( 'X-WP-CF-Super-Cache-Disabled-Reason' );
			header( 'Cache-Control: ' . $settings->get_cache_control_value() );
			header( 'X-WP-SPC-Disk-Cache: ' . ( self::ENTRY_STATE_FRESH === $entry_state ? 'HIT' : strtoupper( $entry_state ) ) );
			header( 'X-WP-CF-Super-Cache-Active: 1' );
			header( 'X-WP-CF-Super-Cache-Cache-Control: ' . $settings->get_cache_control_value() );

			if ( $stored_headers ) {

				foreach ( $stored_headers as $single_header ) {
					header( $single_header, false );
				}
			}

			if ( self::ENTRY_STATE_STALE === $entry_state ) {
				add_action(
					'shutdown',
					function () use ( $cache_key, $cache_path ) {
						$this->shutdown_trigger_async_refresh( $cache_key, $cache_path );
					},
					PHP_INT_MAX - 1
				);
			}

			die( file_get_contents( $cache_path . $cache_key ) );

		}

		return false;
	}

	/**
	 * Check if the current page is cached and not expired.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return bool
	 */
	public function fallback_cache_check_cached_page( string $url ) {
		$cache_path = $this->fallback_cache_init_directory();
		$cache_key  = $this->fallback_cache_get_current_page_cache_key( $url );

		if ( false === $cache_key ) {
			return false;
		}

		return in_array( $this->fallback_cache_get_entry_state( $cache_key, $cache_path ), [ self::ENTRY_STATE_FRESH, self::ENTRY_STATE_STALE ], true );
	}

	/**
	 * @return array{fresh_until:int, stale_until:int}
	 */
	public function build_cache_entry_metadata(): array {
		$lifespan = (int) Settings_Store::get_instance()->get( Constants::SETTING_FALLBACK_CACHE_LIFESPAN, 0 );

		if ( 0 === $lifespan ) {
			return [
				'fresh_until' => 0,
				'stale_until' => 0,
			];
		}

		$fresh_until = time() + $lifespan;
		$stale_until = $fresh_until;

		if ( (int) Settings_Store::get_instance()->get( Constants::SETTING_STALE_WHILE_REVALIDATE, 0 ) > 0 ) {
			$stale_until += max( 0, (int) Settings_Store::get_instance()->get( Constants::SETTING_STALE_WHILE_REVALIDATE_TTL, 60 ) );
		}

		return [
			'fresh_until' => $fresh_until,
			'stale_until' => $stale_until,
		];
	}

	/**
	 * @param mixed $metadata
	 * @return array{fresh_until:int, stale_until:int}
	 */
	public function normalize_cache_entry_metadata( $metadata ): array {
		if ( is_array( $metadata ) ) {
			$fresh_until = isset( $metadata['fresh_until'] ) ? (int) $metadata['fresh_until'] : 0;
			$stale_until = isset( $metadata['stale_until'] ) ? (int) $metadata['stale_until'] : $fresh_until;

			return [
				'fresh_until' => $fresh_until,
				'stale_until' => max( $fresh_until, $stale_until ),
			];
		}

		$legacy_ttl = (int) $metadata;

		return [
			'fresh_until' => $legacy_ttl,
			'stale_until' => $legacy_ttl,
		];
	}

	public function fallback_cache_get_entry_state( string $cache_key, ?string $cache_path = null ): string {
		if ( null === $cache_path ) {
			$cache_path = $this->fallback_cache_init_directory();
		}

		if ( ! file_exists( $cache_path . $cache_key ) ) {
			return self::ENTRY_STATE_MISSING;
		}

		$metadata    = $this->normalize_cache_entry_metadata( $this->fallback_cache_get_single_ttl( $cache_key, 0 ) );
		$current_ttl = $metadata['fresh_until'];
		$stale_ttl   = $metadata['stale_until'];

		if ( 0 === $current_ttl ) {
			return self::ENTRY_STATE_FRESH;
		}

		$now = time();

		if ( $now <= $current_ttl ) {
			return self::ENTRY_STATE_FRESH;
		}

		if ( Settings_Store::get_instance()->is_stale_while_revalidate_active() && $now <= $stale_ttl ) {
			return self::ENTRY_STATE_STALE;
		}

		return self::ENTRY_STATE_EXPIRED;
	}

	private function is_refresh_request(): bool {
		return isset( $_SERVER['HTTP_USER_AGENT'] )
			&& strcasecmp( (string) $_SERVER['HTTP_USER_AGENT'], self::REFRESH_USER_AGENT ) === 0
			&& $this->is_loopback_request();
	}

	private function is_cache_warmer_request(): bool {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) && 0 === strcasecmp( (string) $_SERVER['HTTP_USER_AGENT'], self::WARMER_USER_AGENT );
	}

	private function trigger_async_refresh( string $cache_key, string $cache_path ): void {
		if ( ! $this->acquire_refresh_lock( $cache_key, $cache_path ) ) {
			return;
		}

		$request_args = $this->get_loopback_refresh_request_args();

		if ( [] === $request_args ) {
			$this->release_refresh_lock( $cache_key, $cache_path );
			return;
		}

		$response = wp_remote_get(
			$request_args['url'],
			$request_args['args']
		);

		if ( is_wp_error( $response ) ) {
			$this->release_refresh_lock( $cache_key, $cache_path );
		}
	}

	private function get_refresh_lock_path( string $cache_key, ?string $cache_path = null ): string {
		if ( null === $cache_path ) {
			$cache_path = $this->fallback_cache_init_directory();
		}

		return "{$cache_path}{$cache_key}.refresh.lock";
	}

	private function acquire_refresh_lock( string $cache_key, ?string $cache_path = null ): bool {
		$lock_path = $this->get_refresh_lock_path( $cache_key, $cache_path );
		$handle    = @fopen( $lock_path, 'x' );

		if ( false !== $handle ) {
			fwrite( $handle, (string) time() );
			fclose( $handle );

			return true;
		}

		if ( file_exists( $lock_path ) ) {
			$lock_time = (int) file_get_contents( $lock_path );

			if ( $lock_time > 0 && ( time() - $lock_time ) < self::REFRESH_LOCK_TTL ) {
				return false;
			}

			@unlink( $lock_path );
		}

		$handle = @fopen( $lock_path, 'x' );

		if ( false === $handle ) {
			return false;
		}

		fwrite( $handle, (string) time() );
		fclose( $handle );

		return true;
	}

	private function release_refresh_lock( string $cache_key, ?string $cache_path = null ): void {
		$lock_path = $this->get_refresh_lock_path( $cache_key, $cache_path );

		if ( file_exists( $lock_path ) ) {
			@unlink( $lock_path );
		}
	}

	private function refresh_lock_exists( string $cache_key, ?string $cache_path = null ): bool {
		return file_exists( $this->get_refresh_lock_path( $cache_key, $cache_path ) );
	}

	/**
	 * @return array{url: string, args: array{timeout: float, blocking: bool, sslverify: bool, headers: array{Host: string}, 'user-agent': string}}|array{}
	 */
	private function get_loopback_refresh_request_args(): array {
		$host = $this->get_sanitized_request_host();
		$uri  = $this->get_sanitized_request_uri();

		if ( '' === $host || '' === $uri ) {
			return [];
		}

		$scheme       = is_ssl() ? 'https' : 'http';
		$port         = isset( $_SERVER['SERVER_PORT'] ) ? (int) $_SERVER['SERVER_PORT'] : ( 'https' === $scheme ? 443 : 80 );
		$server_host  = isset( $_SERVER['SERVER_ADDR'] ) ? (string) $_SERVER['SERVER_ADDR'] : '127.0.0.1';
		$loopback_url = $this->normalize_loopback_host_for_url( $server_host );

		return [
			'url'  => "{$scheme}://{$loopback_url}:{$port}{$uri}",
			'args' => [
				'timeout'    => 0.2,
				'blocking'   => false,
				'sslverify'  => false,
				'headers'    => [
					'Host' => $this->get_sanitized_request_host(),
				],
				'user-agent' => self::REFRESH_USER_AGENT,
			],
		];
	}

	private function get_sanitized_request_host(): string {
		$configured_host = $this->get_configured_request_host();

		if ( '' !== $configured_host ) {
			return $configured_host;
		}

		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			return '';
		}

		$host = preg_replace( '/[\x00-\x1F\x7F].*/', '', (string) $_SERVER['HTTP_HOST'] );

		if ( ! is_string( $host ) || '' === $host || ! preg_match( '/^[A-Za-z0-9.\-:\[\]]+$/', $host ) ) {
			return '';
		}

		return $host;
	}

	private function get_configured_request_host(): string {
		$url_parts = wp_parse_url( home_url() );

		if ( ! is_array( $url_parts ) || empty( $url_parts['host'] ) ) {
			return '';
		}

		$host = (string) $url_parts['host'];

		if ( isset( $url_parts['port'] ) ) {
			if ( false !== strpos( $host, ':' ) && '[' !== $host[0] ) {
				$host = '[' . $host . ']';
			}

			$host .= ':' . (int) $url_parts['port'];
		}

		if ( ! preg_match( '/^[A-Za-z0-9.\-:\[\]]+$/', $host ) ) {
			return '';
		}

		return $host;
	}

	private function get_sanitized_request_uri(): string {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		$uri = preg_replace( '/[\x00-\x1F\x7F].*/', '', (string) $_SERVER['REQUEST_URI'] );

		if ( ! is_string( $uri ) || '' === $uri || strpos( $uri, '/' ) !== 0 ) {
			return '';
		}

		return $uri;
	}

	private function normalize_loopback_host_for_url( string $host ): string {
		if ( '' !== $host && false !== strpos( $host, ':' ) && '[' !== $host[0] ) {
			return '[' . $host . ']';
		}

		return $host;
	}

	private function is_loopback_request(): bool {
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return false;
		}

		$remote_addr  = (string) $_SERVER['REMOTE_ADDR'];
		$loopback_ips = [ '127.0.0.1', '::1' ];

		if ( in_array( $remote_addr, $loopback_ips, true ) ) {
			return true;
		}

		if ( ! empty( $_SERVER['SERVER_ADDR'] ) && $remote_addr === (string) $_SERVER['SERVER_ADDR'] ) {
			return true;
		}

		return false;
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_save_headers( string $cache_path, string $cache_key ) {

		$headers_file = "{$cache_path}{$cache_key}.headers.json";

		$headers_list  = headers_list();
		$headers_count = count( $headers_list );

		for ( $i = 0; $i < $headers_count; ++$i ) {

			list($header_name, $header_value) = explode( ':', $headers_list[ $i ] );

			if (
				strcasecmp( $header_name, 'cache-control' ) == 0 ||
				strcasecmp( $header_name, 'set-cookie' ) == 0 ||
				strcasecmp( $header_name, 'X-WP-CF-Super-Cache-Disabled-Reason' ) == 0 ||
				strcasecmp( substr( $header_name, 0, 19 ), 'X-WP-CF-Super-Cache' ) == 0
			) {
				unset( $headers_list[ $i ] );
				continue;
			}
		}

		if ( count( $headers_list ) == 0 ) {

			if ( file_exists( $headers_file ) ) {
				@unlink( $headers_file );
			}

			return false;

		}

		file_put_contents( $headers_file, json_encode( $headers_list ) );

		return true;
	}


	/**
	 * @return array<int, string>|false
	 */
	public function fallback_cache_get_stored_headers( string $cache_path, string $cache_key ) {

		$headers_file = "{$cache_path}{$cache_key}.headers.json";

		if ( file_exists( $headers_file ) ) {

			$swcfpc_headers = json_decode( file_get_contents( $headers_file ), true );

			if ( is_array( $swcfpc_headers ) && count( $swcfpc_headers ) > 0 ) {
				return $swcfpc_headers;
			}
		}

		return false;
	}


	/**
	 * @return bool
	 */
	public function fallback_cache_is_cookie_to_exclude() {

		if ( count( $_COOKIE ) == 0 ) {
			return false;
		}

		$excluded_cookies = Settings_Store::get_instance()->get( Constants::SETTING_EXCLUDED_COOKIES, [] );

		if ( count( $excluded_cookies ) == 0 ) {
			return false;
		}

		$cookies = array_filter(
			array_keys( $_COOKIE ),
			function ( $cookie_name ) {
				return $cookie_name !== 'wordpress_test_cookie';
			}
		);

		foreach ( $excluded_cookies as $single_cookie ) {
			if ( count( preg_grep( "#{$single_cookie}#", $cookies ) ) > 0 ) {
				Helpers::bypass_reason_header( sprintf( 'Cookie - %s', $single_cookie ) );

				return true;
			}
		}

		return false;
	}


	/**
	 * @param string|false $url
	 * @return bool
	 */
	public function fallback_cache_is_url_to_exclude( $url = false ) {
		if ( $this->should_prevent_cache_because_of_trailingslash() ) {
			return true;
		}

		$excluded_urls = Settings_Store::get_instance()->get( Constants::SETTING_EXCLUDED_URLS, [] );

		if ( is_array( $excluded_urls ) && count( $excluded_urls ) > 0 ) {

			if ( $url === false ) {

				$current_url = $_SERVER['REQUEST_URI'];

				if ( isset( $_SERVER['QUERY_STRING'] ) && strlen( $_SERVER['QUERY_STRING'] ) > 0 ) {
					$current_url .= "?{$_SERVER['QUERY_STRING']}";
				}
			} else {
				$current_url = $url;
			}

			foreach ( $excluded_urls as $url_to_exclude ) {
				if ( Helpers::wildcard_match( $url_to_exclude, $current_url ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the current URL should be prevented from being cached or delivered from cache because of the trailing slash.
	 *
	 * @return bool
	 */
	private function should_prevent_cache_because_of_trailingslash() {
		$has_trailing_slash    = Helpers::does_current_url_have_trailing_slash();
		$prevent_trailingslash = Settings_Store::get_instance()->get( Constants::SETTING_FALLBACK_CACHE_PREVENT_TRAILING_SLASH, 1 ) > 0;
		$skip_unslashed        = apply_filters( 'swcfpc_fallback_cache_skip_unslashed', true, $_SERVER['REQUEST_URI'] );

		return $prevent_trailingslash && ! $has_trailing_slash && $skip_unslashed;
	}
}
