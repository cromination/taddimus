<?php

namespace SPC\Services;

use SPC\Constants;
use SPC\Modules\Settings_Manager;

class Settings_Store {
	public const CONFIG_OPTION                   = 'swcfpc_config';
	public const CONFIG_SOURCE_CONST             = 'const';
	public const CONFIG_SOURCE_DB                = 'db';
	public const CONFIG_SOURCE_DEFAULT           = 'default';
	private const ENCRYPTED_SUFFIX               = '_enc';
	private const ENCRYPTION_SENTINEL_OPTION     = 'swcfpc_encryption_sentinel';
	private const ENCRYPTION_SENTINEL_VALUE      = 'swcfpc_credentials_ok';
	private const CLOUDFLARE_ANALYTICS_CACHE_KEY = 'spc_cf_analytics';
	private const FALLBACK_CACHE_CONFIG_KEYS     = [
		Constants::SETTING_ENABLE_FALLBACK_CACHE,
		Constants::SETTING_CF_CACHE_ENABLED,
		Constants::SETTING_CACHE_MAX_AGE,
		Constants::SETTING_BROWSER_CACHE_MAX_AGE,
		Constants::SETTING_FALLBACK_CACHE_LIFESPAN,
		Constants::SETTING_STALE_WHILE_REVALIDATE,
		Constants::SETTING_STALE_WHILE_REVALIDATE_TTL,
		Constants::SETTING_FALLBACK_CACHE_SAVE_HEADERS,
		Constants::SETTING_FALLBACK_CACHE_HTTP_RESPONSE_CODE,
		Constants::SETTING_EXCLUDED_COOKIES,
		Constants::SETTING_FALLBACK_CACHE_PREVENT_TRAILING_SLASH,
		Constants::SETTING_EXCLUDED_URLS,
		'cf_excluded_url_params',
		'cache_tags',
	];

	public const EXCLUDED_FROM_EXPORT_IMPORT = [
		Constants::SETTING_ENABLE_FALLBACK_CACHE,
		Constants::SETTING_CF_ZONE_ID,
		Constants::ZONE_ID_LIST,
		Constants::SETTING_CF_EMAIL,
		Constants::SETTING_CF_API_TOKEN,
		Constants::SETTING_CF_API_KEY,
		Constants::SETTING_OLD_BC_TTL,
		Constants::RULE_ID_PAGE,
		Constants::RULE_ID_CACHE,
		Constants::RULESET_ID_CACHE,
		Constants::WORKER_ID,
		Constants::SETTING_CF_CACHE_ENABLED,
		Constants::SETTING_PRELOADER_NAV_MENUS,
	];

	/**
	 * @var Settings_Store|null
	 */
	private static $_instance = null;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var array<string, mixed>
	 */
	private $stored_config = [];

	/**
	 * @var array
	 */
	private $changed_settings = [];

	/**
	 * @var array<string, bool>
	 */
	private $unreadable_encrypted_fields = [];

	/**
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * @var bool
	 */
	private $encryption_state_valid = true;

	/**
	 * Get the instance of the Settings_Store.
	 *
	 * @return Settings_Store
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance                   = new self();
			self::$_instance->settings_manager = new Settings_Manager();
			self::$_instance->refresh();
		}
		return self::$_instance;
	}

	/**
	 * Get a setting value by key.
	 *
	 * @param string $key The setting key.
	 * @param mixed $fallback_default The fallback default value if the key is not set.
	 *
	 * @return mixed The setting value or the fallback default.
	 */
	public function get( string $key, $fallback_default = false ) {
		$setting = $this->get_with_source( $key, $fallback_default );

		return $setting['value'];
	}

	/**
	 * Get a setting value and its source.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback_default Fallback default.
	 *
	 * @return array{value:mixed,source:string}
	 */
	public function get_with_source( string $key, $fallback_default = false ): array {
		$default_value = $this->settings_manager->get_default_for_field( $key, $fallback_default );

		if ( $this->is_overridden( $key ) ) {
			return [
				'value'  => $this->get_override_value( $key ),
				'source' => self::CONFIG_SOURCE_CONST,
			];
		}

		if ( array_key_exists( $key, $this->config ) ) {
			return [
				'value'  => $this->config[ $key ],
				'source' => self::CONFIG_SOURCE_DB,
			];
		}

		return [
			'value'  => $default_value,
			'source' => self::CONFIG_SOURCE_DEFAULT,
		];
	}
	/**
	 * Check if lazyload viewport is enabled.
	 *
	 * @return bool
	 */
	public function is_lazyload_viewport_enabled() {
		return class_exists( 'SPC_Pro\Modules\Frontend' ) && $this->get( Constants::SETTING_LAZY_LOAD_BEHAVIOUR ) === \SPC_Pro\Modules\Frontend::LAZY_LOAD_BEHAVIOUR_VIEWPORT;
	}
	/**
	 * Check if unused CSS is enabled.
	 *
	 * @return bool
	 */
	public function is_unused_css_enabled() {
		return class_exists( 'SPC_Pro\Modules\Frontend' ) && (bool) $this->get( Constants::SETTING_UNUSED_CSS );
	}
	/**
	 * Check if client optimizations are enabled.
	 *
	 * @return bool
	 */
	public function is_client_optimizations_enabled() {
		return $this->is_lazyload_viewport_enabled() || $this->is_unused_css_enabled();
	}
	/**
	 * Check if Cloudflare is connected.
	 *
	 * @return bool
	 */
	public function is_cloudflare_connected() {
		return $this->has_usable_cloudflare_credentials() && $this->get_cloudflare_zone_id() !== '';
	}

	/**
	 * Get the Cloudflare zone ID.
	 *
	 * @return string
	 */
	public function get_cloudflare_zone_id() {
		if ( defined( 'SWCFPC_CF_API_ZONE_ID' ) ) {
			return SWCFPC_CF_API_ZONE_ID;
		}

		return $this->get( Constants::SETTING_CF_ZONE_ID );
	}

	/**
	 * Get the Cloudflare API key.
	 *
	 * @return string
	 */
	public function get_cloudflare_api_key() {
		if ( defined( 'SWCFPC_CF_API_KEY' ) ) {
			return SWCFPC_CF_API_KEY;
		}

		return $this->get( Constants::SETTING_CF_API_KEY );
	}

	/**
	 * Get the Cloudflare account email.
	 *
	 * @return string
	 */
	public function get_cloudflare_api_email() {
		if ( defined( 'SWCFPC_CF_API_EMAIL' ) ) {
			return SWCFPC_CF_API_EMAIL;
		}

		return $this->get( Constants::SETTING_CF_EMAIL );
	}

	/**
	 * Get the Cloudflare API token.
	 *
	 * @return string
	 */
	public function get_cloudflare_api_token() {
		if ( defined( 'SWCFPC_CF_API_TOKEN' ) ) {
			return SWCFPC_CF_API_TOKEN;
		}

		return $this->get( Constants::SETTING_CF_API_TOKEN );
	}

	/**
	 * Check whether the Cloudflare page cache is enabled.
	 *
	 * @return bool
	 */
	public function is_cache_enabled(): bool {
		return (bool) $this->get( Constants::SETTING_CF_CACHE_ENABLED, 0 );
	}

	/**
	 * Build the Cache-Control header value from configured max-age settings.
	 *
	 * @return string
	 */
	public function get_cache_control_value(): string {
		$cache_control = 's-maxage=' . $this->get( Constants::SETTING_CACHE_MAX_AGE, 604800 )
			. ', max-age=' . $this->get( Constants::SETTING_BROWSER_CACHE_MAX_AGE, 60 );
		$stale_ttl     = max( 0, (int) $this->get( Constants::SETTING_STALE_WHILE_REVALIDATE_TTL, 60 ) );

		if ( $this->is_stale_while_revalidate_active() ) {
			$cache_control .= ', stale-while-revalidate=' . $stale_ttl;
		}

		return $cache_control;
	}

	public function is_stale_while_revalidate_active(): bool {
		return (int) $this->get( Constants::SETTING_STALE_WHILE_REVALIDATE, 0 ) > 0
			&& (int) $this->get( Constants::SETTING_STALE_WHILE_REVALIDATE_TTL, 60 ) > 0;
	}

	/**
	 * @param array<string, mixed>|\WpOrg\Requests\Utility\CaseInsensitiveDictionary $headers
	 */
	public function response_has_swr_directive( $headers ): bool {
		if ( ! $this->is_stale_while_revalidate_active() ) {
			return false;
		}

		$cache_control = '';

		if ( isset( $headers['X-WP-CF-Super-Cache-Cache-Control'] ) ) {
			$cache_control = (string) $headers['X-WP-CF-Super-Cache-Cache-Control'];
		} elseif ( isset( $headers['Cache-Control'] ) ) {
			$cache_control = (string) $headers['Cache-Control'];
		}

		return stripos( $cache_control, 'stale-while-revalidate=' ) !== false;
	}

	/**
	 * Set a setting value by key.
	 *
	 * @param string $key The setting key.
	 * @param mixed $value The value to set for the key.
	 *
	 * @return $this
	 */
	public function set( string $key, $value ) {
		// Bail out if the value is the same as the current one.
		if ( isset( $this->config[ $key ] ) && $this->config[ $key ] === $value ) {
			return $this;
		}

		$this->config[ $key ]           = $value;
		$this->changed_settings[ $key ] = $value;

		return $this;
	}

	/**
	 * Delete a single stored setting.
	 *
	 * @param string $key Setting key.
	 *
	 * @return $this
	 */
	public function delete( string $key ) {
		unset( $this->config[ $key ] );
		$this->changed_settings[ $key ] = null;

		return $this;
	}

	/**
	 * Set multiple settings at once.
	 *
	 * @param array $settings The settings to set.
	 *
	 * @return $this
	 */
	public function set_multiple( array $settings ) {
		foreach ( $settings as $key => $value ) {
			$this->set( $key, $value );
		}

		return $this;
	}

	/**
	 * Get all the settings.
	 *
	 * @param bool $include_defaults Whether to include the default values.
	 *
	 * @return array The settings.
	 */
	public function get_all( $include_defaults = false ) {
		$all = [];

		if ( $include_defaults ) {
			foreach ( $this->settings_manager->get_fields() as $key => $field ) {
				$all[ $key ] = $this->get( $key );
			}

			return $all;
		}

		$keys = array_unique( array_merge( array_keys( $this->config ), array_keys( $this->get_override_settings() ) ) );

		foreach ( $keys as $key ) {
			$all[ $key ] = $this->get( $key );
		}

		return $all;
	}

	/**
	 * Get all setting values with source metadata.
	 *
	 * @param bool $include_defaults Whether to include defaults.
	 *
	 * @return array<string, array{value:mixed, source:string}>
	 */
	public function get_all_with_source( bool $include_defaults = true ): array {
		$settings = [];
		$keys     = $include_defaults
			? array_keys( $this->settings_manager->get_fields() )
			: array_unique( array_merge( array_keys( $this->config ), array_keys( $this->get_override_settings() ) ) );

		foreach ( $keys as $key ) {
			$settings[ $key ] = $this->get_with_source( $key );
		}

		return $settings;
	}

	/**
	 * Get the changed settings since the instantiation of this class.
	 */
	public function get_changed_settings() {
		return $this->changed_settings;
	}

	/**
	 * Refresh the settings from the database.
	 */
	public function refresh() {
		$this->stored_config               = get_option( self::CONFIG_OPTION, [] );
		$this->config                      = [];
		$this->unreadable_encrypted_fields = [];
		$this->encryption_state_valid      = true;

		foreach ( $this->stored_config as $key => $value ) {
			if ( ! $this->settings_manager->is_encrypted_field( $key ) ) {
				$this->config[ $key ] = $value;
				continue;
			}

			if ( ! is_string( $value ) || ! $this->is_encrypted_payload( $value ) ) {
				$this->config[ $key ] = $value;
				continue;
			}

			$decrypted = $this->decrypt_value( $value );

			if ( false === $decrypted ) {
				$this->config[ $key ]                      = '';
				$this->unreadable_encrypted_fields[ $key ] = true;
				$this->encryption_state_valid              = false;
				continue;
			}

			$this->config[ $key ] = $decrypted;
		}

		$this->validate_encryption_sentinel();

		if ( $this->should_invalidate_cloudflare_runtime_state() ) {
			$this->invalidate_cloudflare_runtime_state();
		}
	}

	/**
	 * Reset the settings.
	 *
	 * @return $this
	 */
	public function reset() {
		$this->config                      = [];
		$this->stored_config               = [];
		$this->changed_settings            = [];
		$this->unreadable_encrypted_fields = [];
		$this->encryption_state_valid      = true;

		return $this;
	}

	/**
	 * Save the config to the database.
	 */
	public function save() {
		$stored_config = $this->stored_config;

		foreach ( $this->changed_settings as $key => $value ) {
			if ( null === $value ) {
				unset( $stored_config[ $key ] );
				continue;
			}

			$stored_config[ $key ] = $this->prepare_value_for_storage( $key, $this->config[ $key ] ?? $value );
		}

		foreach ( $this->config as $key => $value ) {
			if ( isset( $this->unreadable_encrypted_fields[ $key ] ) && ! array_key_exists( $key, $this->changed_settings ) ) {
				continue;
			}

			if ( ! array_key_exists( $key, $stored_config ) || array_key_exists( $key, $this->changed_settings ) ) {
				$stored_config[ $key ] = $this->prepare_value_for_storage( $key, $value );
			}
		}

		update_option( self::CONFIG_OPTION, $stored_config );
		$this->update_encryption_sentinel( $stored_config );
		$this->changed_settings = [];
		$this->refresh();
	}

	/**
	 * Import settings from an array.
	 *
	 * @param array $data The settings to import.
	 *
	 * @return array{success:bool, imported:list<string>, rejected:list<string>}
	 */
	public function import_settings( array $data, bool $reject_overridden = false ) {

		if ( empty( $data ) ) {
			return [
				'success'  => false,
				'imported' => [],
				'rejected' => [],
			];
		}

		$fields = $this->settings_manager->get_fields();

		$data = array_filter(
			$data,
			function ( $key ) use ( $fields ) {
				return isset( $fields[ $key ] );
			},
			ARRAY_FILTER_USE_KEY
		);

		$data = $this->sanitize_for_import_export( $data );

		if ( empty( $data ) ) {
			return [
				'success'  => false,
				'imported' => [],
				'rejected' => [],
			];
		}

		$imported = [];
		$rejected = [];

		foreach ( $data as $key => $value ) {
			if ( $reject_overridden && $this->is_overridden( $key ) ) {
				$rejected[] = $key;
				continue;
			}

			$this->set( $key, $this->settings_manager->sanitize_setting_value( $key, $value, $data ) );
			$imported[] = $key;
		}

		if ( ! empty( $imported ) ) {
			$this->save();
		}

		return [
			'success'  => ! empty( $imported ),
			'imported' => $imported,
			'rejected' => $rejected,
		];
	}

	/**
	 * Check if a setting is overridden in wp-config.php.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	public function is_overridden( string $key ): bool {
		$overrides = $this->get_override_settings();

		return array_key_exists( $key, $overrides );
	}

	/**
	 * Split a payload into writable and overridden keys.
	 *
	 * @param array<string, mixed> $settings Settings payload.
	 *
	 * @return array{allowed:array<string, mixed>, rejected:list<string>}
	 */
	public function split_overridden_settings( array $settings ): array {
		$allowed  = [];
		$rejected = [];

		foreach ( $settings as $key => $value ) {
			if ( $this->is_overridden( $key ) ) {
				$rejected[] = $key;
				continue;
			}

			$allowed[ $key ] = $value;
		}

		return [
			'allowed'  => $allowed,
			'rejected' => $rejected,
		];
	}

	/**
	 * Check whether encrypted DB-backed settings can currently be read.
	 *
	 * @return bool
	 */
	public function has_invalid_encryption_state(): bool {
		return ! $this->encryption_state_valid;
	}

	/**
	 * Check whether a specific encrypted setting is currently readable.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	public function is_encrypted_setting_readable( string $key ): bool {
		if ( ! $this->settings_manager->is_encrypted_field( $key ) ) {
			return true;
		}

		return ! isset( $this->unreadable_encrypted_fields[ $key ] );
	}

	/**
	 * Check whether a setting currently has an encrypted payload stored in the database.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	public function has_stored_encrypted_value( string $key ): bool {
		if ( ! $this->settings_manager->is_encrypted_field( $key ) || ! array_key_exists( $key, $this->stored_config ) ) {
			return false;
		}

		return $this->is_encrypted_payload( $this->stored_config[ $key ] );
	}

	/**
	 * Check whether the active Cloudflare auth mode currently depends on an unreadable encrypted credential.
	 *
	 * @return bool
	 */
	public function has_unreadable_active_cloudflare_credentials(): bool {
		if ( ! $this->has_invalid_encryption_state() ) {
			return false;
		}

		$auth_mode = (int) $this->get( Constants::SETTING_AUTH_MODE );

		if ( SWCFPC_AUTH_MODE_API_TOKEN === $auth_mode ) {
			$setting = $this->get_with_source( Constants::SETTING_CF_API_TOKEN );

			return self::CONFIG_SOURCE_CONST !== $setting['source'] && ! $this->is_encrypted_setting_readable( Constants::SETTING_CF_API_TOKEN );
		}

		if ( SWCFPC_AUTH_MODE_API_KEY === $auth_mode ) {
			$key_setting = $this->get_with_source( Constants::SETTING_CF_API_KEY );

			return self::CONFIG_SOURCE_CONST !== $key_setting['source'] && ! $this->is_encrypted_setting_readable( Constants::SETTING_CF_API_KEY );
		}

		return false;
	}

	/**
	 * Check whether the unreadable-encryption warning should be shown to the user.
	 *
	 * @return bool
	 */
	public function should_show_invalid_encryption_notice(): bool {
		return $this->has_unreadable_active_cloudflare_credentials();
	}

	/**
	 * Get the settings for export.
	 *
	 * @return array
	 */
	public function get_config_for_export() {
		return $this->sanitize_for_import_export( $this->config );
	}

	/**
	 * Get the minimal runtime config used by advanced-cache.php.
	 *
	 * @return array<string, mixed>
	 */
	public function get_fallback_cache_runtime_config(): array {
		$config = [];

		foreach ( self::FALLBACK_CACHE_CONFIG_KEYS as $key ) {
			$config[ $key ] = $this->get( $key );
		}

		return $config;
	}

	/**
	 * Strip excluded settings from the settings array.
	 *
	 * @param array $settings The array of settings to be stripped.
	 *
	 * @return array
	 */
	private function sanitize_for_import_export( array $settings ) {
		foreach ( self::EXCLUDED_FROM_EXPORT_IMPORT as $key ) {
			unset( $settings[ $key ] );
		}

		return $settings;
	}

	/**
	 * Get wp-config.php overrides.
	 *
	 * @return array<string, mixed>
	 */
	private function get_override_settings(): array {
		$enabled = apply_filters( 'spc_settings_overrides_enabled', false );

		if ( ! $enabled ) {
			return [];
		}

		$overrides = apply_filters( 'spc_settings_override_map', [] );

		return is_array( $overrides ) ? $overrides : [];
	}

	/**
	 * Get a sanitized override value.
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed
	 */
	private function get_override_value( string $key ) {
		$overrides = $this->get_override_settings();

		if ( ! array_key_exists( $key, $overrides ) ) {
			return null;
		}

		return $this->settings_manager->sanitize_setting_value( $key, $overrides[ $key ], $overrides );
	}

	/**
	 * Prepare a value for DB storage.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Value.
	 *
	 * @return mixed
	 */
	private function prepare_value_for_storage( string $key, $value ) {
		if ( ! $this->settings_manager->is_encrypted_field( $key ) ) {
			return $value;
		}

		if ( '' === $value || null === $value ) {
			return '';
		}

		$encrypted = $this->encrypt_value( (string) $value );

		if ( false === $encrypted ) {
			throw new \RuntimeException( sprintf( 'Failed to encrypt setting "%s".', $key ) );
		}

		return $encrypted;
	}

	/**
	 * Validate the encryption sentinel against the current key.
	 *
	 * @return void
	 */
	private function validate_encryption_sentinel() {
		$sentinel = get_option( self::ENCRYPTION_SENTINEL_OPTION, '' );

		if ( empty( $sentinel ) || ! is_string( $sentinel ) ) {
			return;
		}

		$decrypted = $this->decrypt_value( $sentinel );

		if ( self::ENCRYPTION_SENTINEL_VALUE !== $decrypted ) {
			$this->encryption_state_valid = false;
		}
	}

	/**
	 * Determine if Cloudflare-derived runtime state should be treated as stale.
	 *
	 * @return bool
	 */
	private function should_invalidate_cloudflare_runtime_state(): bool {
		if ( ! $this->has_invalid_encryption_state() ) {
			return false;
		}

		return ! $this->has_usable_cloudflare_credentials();
	}

	/**
	 * Determine whether the effective Cloudflare credentials are usable right now.
	 *
	 * @return bool
	 */
	private function has_usable_cloudflare_credentials(): bool {
		$auth_mode = (int) $this->get( Constants::SETTING_AUTH_MODE );

		if ( SWCFPC_AUTH_MODE_API_TOKEN === $auth_mode ) {
			return '' !== (string) $this->get( Constants::SETTING_CF_API_TOKEN, '' );
		}

		if ( SWCFPC_AUTH_MODE_API_KEY === $auth_mode ) {
			return '' !== (string) $this->get( Constants::SETTING_CF_EMAIL, '' ) && '' !== (string) $this->get( Constants::SETTING_CF_API_KEY, '' );
		}

		return false;
	}

	/**
	 * Invalidate runtime state derived from Cloudflare when credentials are no longer usable.
	 *
	 * @return void
	 */
	private function invalidate_cloudflare_runtime_state(): void {
		unset( $this->config[ Constants::ZONE_ID_LIST ] );

		$this->config[ Constants::RULESET_ID_CACHE ] = '';
		$this->config[ Constants::RULE_ID_CACHE ]    = '';
		$this->config[ Constants::RULE_ID_PAGE ]     = '';

		delete_transient( self::CLOUDFLARE_ANALYTICS_CACHE_KEY );
	}

	/**
	 * Update the sentinel option that lets us detect key changes.
	 *
	 * @param array<string, mixed> $stored_config Raw stored config.
	 *
	 * @return void
	 */
	private function update_encryption_sentinel( array $stored_config ) {
		$has_encrypted_values = false;

		foreach ( $stored_config as $key => $value ) {
			if ( $this->settings_manager->is_encrypted_field( $key ) && ! empty( $value ) ) {
				$has_encrypted_values = true;
				break;
			}
		}

		if ( ! $has_encrypted_values ) {
			delete_option( self::ENCRYPTION_SENTINEL_OPTION );
			$this->encryption_state_valid = true;
			return;
		}

		$encrypted = $this->encrypt_value( self::ENCRYPTION_SENTINEL_VALUE );

		if ( false !== $encrypted ) {
			update_option( self::ENCRYPTION_SENTINEL_OPTION, $encrypted );
			$this->encryption_state_valid = true;
		}
	}

	/**
	 * Encrypt a setting value for DB storage.
	 *
	 * @param string $value Plaintext value.
	 *
	 * @return string|false
	 */
	private function encrypt_value( string $value ) {
		if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
			return false;
		}

		$key     = $this->get_encryption_key();
		$iv      = random_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$payload = openssl_encrypt( $value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $payload ) {
			return false;
		}

		$hmac = hash_hmac( 'sha256', $payload, $key, true );

		return base64_encode( $iv . $hmac . $payload ) . self::ENCRYPTED_SUFFIX;
	}

	/**
	 * Decrypt a stored encrypted value.
	 *
	 * @param string $value Encrypted payload.
	 *
	 * @return string|false
	 */
	private function decrypt_value( string $value ) {
		if ( ! function_exists( 'openssl_decrypt' ) || ! $this->is_encrypted_payload( $value ) ) {
			return false;
		}

		$key       = $this->get_encryption_key();
		$decoded   = base64_decode( substr( $value, 0, - strlen( self::ENCRYPTED_SUFFIX ) ), true );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );

		if ( false === $decoded || strlen( $decoded ) <= ( $iv_length + 32 ) ) {
			return false;
		}

		$iv         = substr( $decoded, 0, $iv_length );
		$hmac       = substr( $decoded, $iv_length, 32 );
		$ciphertext = substr( $decoded, $iv_length + 32 );
		$expected   = hash_hmac( 'sha256', $ciphertext, $key, true );

		if ( ! hash_equals( $hmac, $expected ) ) {
			return false;
		}

		return openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
	}

	/**
	 * Check whether a stored value matches the encrypted payload format.
	 *
	 * @param mixed $value Setting value.
	 *
	 * @return bool
	 */
	private function is_encrypted_payload( $value ): bool {
		return is_string( $value ) && self::ENCRYPTED_SUFFIX === substr( $value, - strlen( self::ENCRYPTED_SUFFIX ) );
	}

	/**
	 * Get the current encryption key as raw 32 bytes for AES-256.
	 *
	 * @return string
	 */
	private function get_encryption_key(): string {
		$key = defined( 'SECURE_AUTH_KEY' ) ? constant( 'SECURE_AUTH_KEY' ) : wp_salt( 'secure_auth' );

		return hash( 'sha256', (string) $key, true );
	}
}
