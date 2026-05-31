<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Modules\Database_Optimization;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;
use SPC\Utils\Htaccess_Writer;
use SPC\Utils\Logger;

class Settings_Manager implements Module_Interface {

	public const SETTING_TYPE_BOOLEAN  = 'bool';
	public const SETTING_TYPE_INTEGER  = 'int';
	public const SETTING_TYPE_TEXT     = 'text';
	public const SETTING_TYPE_TEXTAREA = 'textarea';
	public const SETTING_TYPE_ARRAY    = 'array';
	public const SETTING_TYPE_OBJECT   = 'object';

	public const SETTINGS_FALLBACK_DEFAULT_TYPE_VALUE_MAP = [
		self::SETTING_TYPE_INTEGER  => 0,
		self::SETTING_TYPE_BOOLEAN  => 0,
		self::SETTING_TYPE_TEXT     => '',
		self::SETTING_TYPE_TEXTAREA => [],
		self::SETTING_TYPE_ARRAY    => [],
		self::SETTING_TYPE_OBJECT   => null,
	];

	private const BASE_FIELDS = [
		Constants::SETTING_EXCLUDED_COOKIES             => [
			'type'       => self::SETTING_TYPE_TEXTAREA,
			'bust_cache' => true,
			'sync_rules' => true,
			'default'    => Constants::DEFAULT_EXCLUDED_COOKIES,
		],
		Constants::SETTING_EXCLUDED_URLS                => [
			'type'              => self::SETTING_TYPE_TEXTAREA,
			'bust_cache'        => true,
			'sync_rules'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_excluded_urls',
			'default'           => Constants::DEFAULT_EXCLUDED_URLS,
		],
		Constants::SETTING_NATIVE_LAZY_LOADING          => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 1,
		],
		Constants::SETTING_LAZY_LOADING                 => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_VIDEO_IFRAME       => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_SKIP_IMAGES        => [
			'type'       => self::SETTING_TYPE_INTEGER,
			'bust_cache' => true,
			'default'    => 2,
		],
		Constants::SETTING_LAZY_EXCLUDED                => [
			'type'       => self::SETTING_TYPE_TEXTAREA,
			'bust_cache' => true,
			'default'    => [],
		],
		Constants::SETTING_LAZY_LOAD_BG                 => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_BG_SELECTORS       => [
			'type'              => self::SETTING_TYPE_TEXTAREA,
			'bust_cache'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_background_selectors',
			'default'           => [],
		],
		Constants::SETTING_AUTO_PURGE                   => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_AUTO_PURGE_WHOLE             => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_PURGE_ON_COMMENT             => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_ENABLE_PRELOADER             => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_PRELOADER_START_ON_PURGE     => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_PRELOAD_SITEMAPS_URLS        => [
			'type'              => self::SETTING_TYPE_TEXTAREA,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_preloaded_sitemap_urls',
			'default'           => Constants::DEFAULT_PRELOADED_SITEMAPS_URLS,
		],
		Constants::SETTING_PRELOADER_NAV_MENUS          => [
			'type'    => self::SETTING_TYPE_ARRAY,
			'default' => [],
		],
		Constants::SETTING_PRELOAD_LAST_URLS            => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_PRELOAD_CRONJOB_SECRET       => [
			'type'    => self::SETTING_TYPE_TEXT,
			'default' => '',
		],
		Constants::SETTING_PREFETCH_URLS_MODE           => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_prefetch_urls_mode',
			'bust_cache'        => true,
			'default'           => Speculative_Loading::PREFETCH_MODE_OFF,
		],
		Constants::SETTING_REMOVE_CACHE_BUSTER          => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_SHOW_ADVANCED                => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BROWSER_CACHE_STATIC_ASSETS  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_KEEP_ON_DEACTIVATION         => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_ENABLE_FALLBACK_CACHE        => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_VARNISH_SUPPORT              => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_VARNISH_AUTO_PURGE           => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_VARNISH_HOSTNAME             => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => 'localhost',
		],
		Constants::SETTING_VARNISH_PORT                 => [
			'type'       => self::SETTING_TYPE_INTEGER,
			'bust_cache' => true,
			'default'    => 6081,
		],
		Constants::SETTING_VARNISH_ON_CLOWDWAYS         => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_VARNISH_PURGE_METHOD         => [
			'default'    => 'PURGE',
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
		],
		Constants::SETTING_VARNISH_PURGE_ALL_METHOD     => [
			'default'    => 'PURGE',
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
		],
		Constants::SETTING_FALLBACK_CACHE_CURL          => [
			'default'    => 0,
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
		],
		Constants::SETTING_FALLBACK_CACHE_LIFESPAN      => [
			'type'       => self::SETTING_TYPE_INTEGER,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_STALE_WHILE_REVALIDATE       => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_STALE_WHILE_REVALIDATE_TTL   => [
			'type'       => self::SETTING_TYPE_INTEGER,
			'bust_cache' => true,
			'default'    => 60,
		],
		Constants::SETTING_FALLBACK_CACHE_SAVE_HEADERS  => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_MINIFY_HTML                  => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_FALLBACK_CACHE_PREVENT_TRAILING_SLASH => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 1,
		],
		Constants::SETTING_FALLBACK_CACHE_PURGE_ON_UPGRADER_COMPLETE => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_STRIP_RESPONSE_COOKIES       => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_OVERWRITE_WITH_HTACCESS      => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_PURGE_ONLY_HTML              => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_DISABLE_PURGING_QUEUE        => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_FALLBACK_CACHE_HTTP_RESPONSE_CODE => [
			'type'       => self::SETTING_TYPE_BOOLEAN,
			'bust_cache' => true,
			'default'    => 1,
		],
		Constants::SETTING_BYPASS_404                   => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_SINGLE_POST           => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_PAGES                 => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_FRONT_PAGE            => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_HOME                  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_ARCHIVES              => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_TAGS                  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_CATEGORY              => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_FEEDS                 => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_SEARCH_PAGES          => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_AUTHOR_PAGES          => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_AMP                   => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_AJAX                  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_QUERY_VAR             => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_WP_JSON_REST          => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_BYPASS_SITEMAP               => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_ROBOTS_TXT            => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_POSTS_PER_PAGE               => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 10,
		],
		Constants::SETTING_CACHE_MAX_AGE                => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 31536000, // 1 year
		],
		Constants::SETTING_BROWSER_CACHE_MAX_AGE        => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 60, // 1 minute
		],
		Constants::SETTING_FALLBACK_CACHE_AUTO_PURGE    => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_BYPASS_BACKEND_WITH_RULE     => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],

		Constants::SETTING_LOG_ENABLED                  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_LOG_MAX_FILESIZE             => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 2,
		],
		Constants::SETTING_LOG_VERBOSITY                => [
			'type'              => self::SETTING_TYPE_INTEGER,
			'default'           => Logger::VERBOSITY_STANDARD,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_log_verbosity',
		],
		Constants::SETTING_OBJECT_CACHE_PURGE_ON_FLUSH  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_OPCACHE_PURGE_ON_FLUSH       => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_PURGE_URL_SECRET_KEY         => [
			'type'    => self::SETTING_TYPE_TEXT,
			'default' => '',
		],
		Constants::SETTING_REMOVE_PURGE_OPTION_TOOLBAR  => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_SEO_REDIRECT                 => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_PURGE_ROLES                  => [
			'type'    => self::SETTING_TYPE_ARRAY,
			'default' => [],
		],
		Constants::SETTING_HEARTBEAT_ADMIN              => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_heartbeat_mode',
			'default'           => 'default',
		],
		Constants::SETTING_HEARTBEAT_EDITOR             => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_heartbeat_mode',
			'default'           => 'default',
		],
		Constants::SETTING_HEARTBEAT_FRONTEND           => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_heartbeat_mode',
			'default'           => 'default',
		],
		Constants::SETTING_DNS_PREFETCH_DOMAINS         => [
			'type'              => self::SETTING_TYPE_TEXTAREA,
			'bust_cache'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_prefetch_domains',
			'default'           => [],
		],
		Constants::SETTING_PRECONNECT_DOMAINS           => [
			'type'              => self::SETTING_TYPE_TEXTAREA,
			'bust_cache'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_prefetch_domains',
			'default'           => [],
		],
		Constants::SETTING_AUTH_MODE                    => [
			'type'       => self::SETTING_TYPE_INTEGER,
			'bust_cache' => true,
			'default'    => SWCFPC_AUTH_MODE_API_TOKEN,
		],
		Constants::SETTING_CF_EMAIL                     => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => '',
		],
		Constants::SETTING_CF_API_KEY                   => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => '',
			'encrypted'  => true,
		],
		Constants::SETTING_CF_API_TOKEN                 => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => '',
			'encrypted'  => true,
		],
		Constants::SETTING_CF_DOMAIN_NAME               => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => '',
		],
		Constants::ZONE_ID_LIST                         => [
			'type'       => self::SETTING_TYPE_OBJECT,
			'bust_cache' => true,
			'default'    => null,
		],
		Constants::SETTING_CF_ZONE_ID                   => [
			'type'       => self::SETTING_TYPE_TEXT,
			'bust_cache' => true,
			'default'    => '',
		],
		Constants::RULESET_ID_CACHE                     => [
			'type'    => self::SETTING_TYPE_TEXT,
			'default' => '',
		],
		Constants::RULE_ID_PAGE                         => [
			'type'    => self::SETTING_TYPE_TEXT,
			'default' => '',
		],
		Constants::RULE_ID_CACHE                        => [
			'type'    => self::SETTING_TYPE_TEXT,
			'default' => '',
		],
		Constants::SETTING_OLD_BC_TTL                   => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 0,
		],
		Constants::SETTING_CF_CACHE_ENABLED             => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::ENABLE_CACHE_RULE                    => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_PREFETCH_URLS_TIMESTAMP      => [
			'type'    => self::SETTING_TYPE_INTEGER,
			'default' => 0,
		],
		Constants::SETTING_ENABLE_DATABASE_OPTIMIZATION => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_POST_REVISION_INTERVAL       => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_AUTO_DRAFT_POST_INTERVAL     => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_TRASHED_POST_INTERVAL        => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_SPAM_COMMENT_INTERVAL        => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_TRASHED_COMMENT_INTERVAL     => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_ALL_TRANSIENT_INTERVAL       => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_OPTIMIZE_TABLE_INTERVAL      => [
			'type'              => self::SETTING_TYPE_TEXT,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_database_optimization_interval',
			'default'           => Database_Optimization::NEVER,
		],
		Constants::SETTING_OPTIMIZE_GOOGLE_FONTS        => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 1,
		],
		Constants::SETTING_LOCAL_GOOGLE_FONTS           => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_ENABLE_ASSETS_MANAGER        => [
			'type'    => self::SETTING_TYPE_BOOLEAN,
			'default' => 0,
		],
		Constants::SETTING_LAZY_LOAD_BEHAVIOUR          => [
			'type'              => self::SETTING_TYPE_TEXT,
			'bust_cache'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_lazy_load_behaviour',
			'default'           => 'all',
		],
	];

	private const ALLOWED_FIELD_TYPES = [ 'type', 'bust_cache', 'default', 'sync_rules', 'encrypted' ];

	/**
	 * Initialize the settings manager.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'spc_after_settings_update', [ $this, 'handle_side_effects' ] );
	}

	/**
	 * Save additional settings.
	 *
	 * @param array{key: string, value: mixed} $settings_data An associative array of settings data.
	 *
	 * @return array{updated:list<string>, rejected:list<string>}
	 */
	public function update_settings( array $settings_data, bool $reject_overridden = false ) {
		$fields_to_update = array_filter(
			$this->get_fields(),
			function ( $args, $key ) use ( $settings_data ) {
				return isset( $settings_data[ $key ] );
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( empty( $fields_to_update ) ) {
			return [
				'updated'  => [],
				'rejected' => [],
			];
		}

		$settings = Settings_Store::get_instance();

		$rejected = [];

		foreach ( $fields_to_update as $key => $args ) {
			if ( ! isset( $settings_data[ $key ] ) ) {
				continue;
			}

			$value = $this->sanitize_setting_value( $key, $settings_data[ $key ], $settings_data );

			if ( ! array_key_exists( 'type', $args ) ) {
				continue;
			}

			if ( $reject_overridden && $settings->is_overridden( $key ) ) {
				if ( $this->is_unchanged_overridden_setting( $key, $value, $settings ) ) {
					continue;
				}

				$rejected[] = $key;
				continue;
			}

			if ( $this->should_preserve_encrypted_setting( $key, $value, $settings ) ) {
				continue;
			}

			$settings->set( $key, $value );
		}

		$changed = array_keys( $settings->get_changed_settings() );

		$settings->save();

		do_action( 'spc_after_settings_update' );

		// any of the changed settings keys have a bust_cache
		$bust_cache_fields   = array_keys( $this->get_fields( [ 'bust_cache' => true ] ) );
		$update_rules_fields = array_keys( $this->get_fields( [ 'sync_rules' => true ] ) );

		if ( count( array_intersect( $changed, $update_rules_fields ) ) > 0 ) {
			( new Cloudflare_Integration() )->update_cache_rule_if_diff();
		}

		if ( count( array_intersect( $changed, $bust_cache_fields ) ) > 0 ) {
			Cache_Controller::purge_all( false, false, true );
		}

		return [
			'updated'  => array_values( array_diff( $changed, $rejected ) ),
			'rejected' => $rejected,
		];
	}

	/**
	 * Get a single field configuration.
	 *
	 * @param string $key Setting key.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_field( string $key ) {
		$fields = $this->get_fields();

		return $fields[ $key ] ?? null;
	}

	/**
	 * Check if a field should be stored encrypted.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	public function is_encrypted_field( string $key ): bool {
		$field = $this->get_field( $key );

		return ! empty( $field['encrypted'] );
	}

	/**
	 * Sanitize a setting value using the field metadata.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Raw value.
	 * @param array<string, mixed> $all_settings All incoming settings.
	 *
	 * @return mixed
	 */
	public function sanitize_setting_value( string $key, $value, array $all_settings = [] ) {
		$args = $this->get_field( $key );

		if ( empty( $args ) ) {
			return $value;
		}

		if ( isset( $args['sanitize_callback'] ) ) {
			$value = call_user_func( $args['sanitize_callback'], $value, $all_settings );
		}

		if ( ! isset( $args['type'] ) ) {
			return $value;
		}

		switch ( $args['type'] ) {
			case self::SETTING_TYPE_BOOLEAN:
			case self::SETTING_TYPE_INTEGER:
				return (int) $value;

			case self::SETTING_TYPE_TEXTAREA:
				if ( is_array( $value ) ) {
					$lines = $value;
				} else {
					$lines = explode( "\n", (string) $value );
				}

				$lines = array_values( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $lines ) ) ) );

				return count( $lines ) > 0 ? $lines : [];

			case self::SETTING_TYPE_TEXT:
				return sanitize_text_field( (string) $value );

			case self::SETTING_TYPE_ARRAY:
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', array_map( 'trim', $value ) );
				}

				return [];

			default:
				return $value;
		}
	}

	/**
	 * Keep stored encrypted secrets when generic settings updates submit masked empty values.
	 *
	 * @param string         $key Setting key.
	 * @param mixed          $value Sanitized incoming value.
	 * @param Settings_Store $settings Settings store.
	 *
	 * @return bool
	 */
	private function should_preserve_encrypted_setting( string $key, $value, Settings_Store $settings ): bool {
		if ( ! $this->is_encrypted_field( $key ) ) {
			return false;
		}

		if ( '' !== $value ) {
			return false;
		}

		return '' !== (string) $settings->get( $key, '' ) || $settings->has_stored_encrypted_value( $key );
	}

	/**
	 * Check whether an overridden setting was submitted with the same effective value.
	 *
	 * @param string         $key      Setting key.
	 * @param mixed          $value    Sanitized incoming value.
	 * @param Settings_Store $settings Settings store.
	 *
	 * @return bool
	 */
	private function is_unchanged_overridden_setting( string $key, $value, Settings_Store $settings ): bool {
		if ( $this->is_encrypted_field( $key ) && '' === $value && '' !== (string) $settings->get( $key, '' ) ) {
			return true;
		}

		return $settings->get( $key ) === $value;
	}

	/**
	 * Alter settings default config
	 *
	 * @param mixed $config Default config.
	 *
	 * @return array
	 */
	public function alter_default_config( $config ) {
		return is_array( $config ) ? array_merge( $config, $this->get_fields( [], 'default' ) ) : $config;
	}

	/**
	 * Get fields by type.
	 *
	 * @param array $filters Filters to match.
	 * @param string $remapped_property Property to map to value.
	 *
	 * @return array
	 */
	public function get_fields( $filters = [], $remapped_property = null ) {
		$fields = self::BASE_FIELDS;

		// Dynamic default values that can't be set in the BASE_FIELDS array.
		$fields[ Constants::SETTING_NATIVE_LAZY_LOADING ]['default']     = apply_filters( 'wp_lazy_loading_enabled', true, 'img', 'spc_default_native_lazyloading' );
		$fields[ Constants::SETTING_POSTS_PER_PAGE ]['default']          = get_option( 'posts_per_page', 0 );
		$fields[ Constants::SETTING_PRELOAD_CRONJOB_SECRET ]['default']  = wp_generate_password( 20, false );
		$fields[ Constants::SETTING_PURGE_URL_SECRET_KEY ]['default']    = wp_generate_password( 20, false );
		$fields[ Constants::SETTING_PREFETCH_URLS_TIMESTAMP ]['default'] = time();
		$fields[ Constants::SETTING_CF_DOMAIN_NAME ]['default']          = Helpers::get_second_level_domain();

		$fields = apply_filters( 'spc_additional_settings_fields', $fields );

		if ( ! empty( $filters ) ) {
			$fields = wp_list_filter( $fields, $filters );
		}

		if ( is_string( $remapped_property ) && in_array( $remapped_property, self::ALLOWED_FIELD_TYPES, true ) ) {
			$fields = wp_list_pluck( $fields, $remapped_property );
		}

		return $fields;
	}

	/**
	 * Handle side effects after settings update.
	 *
	 * @return void
	 */
	public function handle_side_effects() {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$settings = Settings_Store::get_instance();

		$error = '';

		Logger::set_verbosity( (int) $settings->get( Constants::SETTING_LOG_VERBOSITY ) );

		if ( $settings->get( Constants::SETTING_LOG_ENABLED ) ) {
			Logger::enable();
		} else {
			Logger::disable();
		}

		$cloudflare_handler = new Cloudflare_Integration();

		// Not so sure about this:
		if ( $settings->get( Constants::SETTING_BYPASS_BACKEND_WITH_RULE ) ) {
			$cloudflare_handler->disable_page_cache();
		}

		if ( $settings->get( Constants::ENABLE_CACHE_RULE ) ) {
			$cloudflare_handler->pull_existing_cache_rule();

			$settings->refresh();

			if ( empty( $settings->get( Constants::RULE_ID_CACHE ) ) ) {
				$cloudflare_handler->enable_page_cache( $error );
			}
		} else {
			$cloudflare_handler->delete_cache_rule( $error );
			$cloudflare_handler->delete_legacy_page_rules( $error );
		}

		$fallback_cache_handler = $sw_cloudflare_pagecache->get_core_loader()->fallback_cache();

		if (
			! $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE ) ||
			$settings->get( Constants::SETTING_FALLBACK_CACHE_CURL )
		) {
			$fallback_cache_handler->fallback_cache_advanced_cache_disable();
		} else {
			$fallback_cache_handler->fallback_cache_advanced_cache_enable();
			$fallback_cache_handler->fallback_cache_save_config();
		}
		$database_optimization = new Database_Optimization();
		if ( $settings->get( Constants::SETTING_ENABLE_DATABASE_OPTIMIZATION ) ) {
			$database_optimization->setup_cron();
		} else {
			$database_optimization->delete_events();

		}

		Htaccess_Writer::write( $error );
	}

	/**
	 * Get the default for a field.
	 *
	 * @param string $field field ID.
	 * @param mixed $fallback fallback default, if the field is not defined.
	 *
	 * @return mixed
	 */
	public function get_default_for_field( $field, $fallback = false ) {
		$fields = $this->get_fields();

		if ( isset( $fields[ $field ], $fields[ $field ]['default'] ) ) {
			return $fields[ $field ]['default'];
		}

		return $fallback;
	}

	/**
	 * Get boolean value.
	 *
	 * @param string $key The setting key.
	 *
	 * @return bool
	 */
	public static function is_on( $key ) {
		return (bool) Settings_Store::get_instance()->get( $key );
	}
}
