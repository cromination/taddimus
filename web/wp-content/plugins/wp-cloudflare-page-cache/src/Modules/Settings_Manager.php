<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Utils\Sanitization;

class Settings_Manager implements Module_Interface {
	private const BASE_FIELDS         = [
		Constants::SETTING_EXCLUDED_COOKIES            => [
			'type'       => 'textarea',
			'bust_cache' => true,
			'sync_rules' => true,
			'default'    => Constants::DEFAULT_EXCLUDED_COOKIES,
		],
		Constants::SETTING_EXCLUDED_URLS               => [
			'type'              => 'textarea',
			'bust_cache'        => true,
			'sync_rules'        => true,
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_excluded_urls',
			'default'           => Constants::DEFAULT_EXCLUDED_URLS,
		],
		Constants::SETTING_NATIVE_LAZY_LOADING         => [
			'type'       => 'bool',
			'bust_cache' => true,
			'default'    => 1,
		],
		Constants::SETTING_LAZY_LOADING                => [
			'type'       => 'bool',
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_VIDEO_IFRAME      => [
			'type'       => 'bool',
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_SKIP_IMAGES       => [
			'type'       => 'int',
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_EXCLUDED               => [
			'type'       => 'textarea',
			'bust_cache' => true,
			'default'    => [],
		],
		Constants::SETTING_LAZY_LOAD_BG                => [
			'type'       => 'bool',
			'bust_cache' => true,
			'default'    => 0,
		],
		Constants::SETTING_LAZY_LOAD_BG_SELECTORS      => [
			'type'       => 'textarea',
			'bust_cache' => true,
			'default'    => [],
		],
		Constants::SETTING_AUTO_PURGE                  => [
			'type'    => 'bool',
			'default' => 1,
		],
		Constants::SETTING_AUTO_PURGE_WHOLE            => [
			'type'    => 'bool',
			'default' => 0,
		],
		Constants::SETTING_PURGE_ON_COMMENT            => [
			'type'    => 'bool',
			'default' => 1,
		],
		Constants::SETTING_PRELOAD_SITEMAPS_URLS       => [
			'type'              => 'textarea',
			'sanitize_callback' => 'SPC\Utils\Sanitization::sanitize_preloaded_sitemap_urls',
			'default'           => Constants::DEFAULT_PRELOADED_SITEMAPS_URLS,
		],
		Constants::SETTING_PREFETCH_ON_HOVER           => [
			'type'       => 'bool',
			'bust_cache' => true,
			'default'    => 1,
		],
		Constants::SETTING_REMOVE_CACHE_BUSTER         => [
			'type'    => 'bool',
			'default' => 1,
		],
		Constants::SETTING_SHOW_ADVANCED               => [
			'type'    => 'bool',
			'default' => 0,
		],
		Constants::SETTING_BROWSER_CACHE_STATIC_ASSETS => [
			'type'    => 'bool',
			'default' => 1,
		],
	];
	private const ALLOWED_FIELD_TYPES = [ 'type', 'bust_cache', 'default', 'sync_rules' ];

	public function init() {
		add_action( 'swcfpc_after_settings_update', [ $this, 'update_additional_settings' ] );
		add_filter( 'swcfpc_main_config_defaults', [ $this, 'alter_default_config' ] );
		add_filter( 'swcfpc_pre_set_setting_value', [ $this, 'pre_save_excluded_urls' ], 10, 3 );
	}

	/**
	 * Save additional settings.
	 *
	 * @param array $post_data $_POST data from backend settings page.
	 *
	 * @return void
	 */
	public function update_additional_settings( $post_data ) {
		if ( ! is_array( $post_data ) ) {
			return $post_data;
		}

		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$fields_to_update = array_filter(
			$this->get_fields(),
			function ( $args, $key ) use ( $post_data ) {
				return isset( $post_data[ 'swcfpc_' . $key ] );
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( empty( $fields_to_update ) ) {
			return;
		}

		$previous_settings = $sw_cloudflare_pagecache->get_config();
		$will_bust_cache   = false;
		$will_update_rules = false;

		foreach ( $fields_to_update as $key => $args ) {
			if ( ! isset( $post_data[ 'swcfpc_' . $key ] ) ) {
				continue;
			}

			$value = $post_data[ 'swcfpc_' . $key ];

			if ( isset( $args['sanitize_callback'] ) ) {
				$value = call_user_func( $args['sanitize_callback'], $value, $post_data );
			}

			switch ( $args['type'] ) {
				case 'bool':
				case 'int':
					$sw_cloudflare_pagecache->set_single_config( $key, (int) $value );
					break;

				case 'textarea':
					$value = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( "\n", (string) $value ) ) ) );
					$sw_cloudflare_pagecache->set_single_config( $key, count( $value ) > 0 ? $value : [] );
					break;
				default:
					break;
			}

			if ( isset( $previous_settings[ $key ] ) && $previous_settings[ $key ] !== $value ) {
				if ( isset( $args['bust_cache'] ) && $args['bust_cache'] === true ) {
					$will_bust_cache = true;
				}

				if ( isset( $args['sync_rules'] ) && $args['sync_rules'] === true ) {
					$will_update_rules = true;
				}
			}
		}

		if ( $will_update_rules ) {
			$sw_cloudflare_pagecache->get_cloudflare_handler()->update_cache_rule_if_diff();
		}

		if ( $will_bust_cache ) {
			$sw_cloudflare_pagecache->get_cache_controller()->purge_all( false, false, true );
		}
	}

	/**
	 * Alter settings default config
	 *
	 * @param array $config Default config.
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
		$fields = apply_filters( 'spc_additional_settings_fields', self::BASE_FIELDS );

		// Use WordPress core filter to determine if lazy loading is enabled by default.
		$fields[ Constants::SETTING_NATIVE_LAZY_LOADING ]['default'] = apply_filters( 'wp_lazy_loading_enabled', true, 'img', 'spc_default_native_lazyloading' );

		if ( is_array( $filters ) && ! empty( $filters ) ) {
			$fields = wp_list_filter( $fields, $filters );
		}

		if ( is_string( $remapped_property ) && in_array( $remapped_property, self::ALLOWED_FIELD_TYPES, true ) ) {
			$fields = wp_list_pluck( $fields, $remapped_property );
		}

		return $fields;
	}

	/**
	 * Get the default for a field.
	 *
	 * @param string $field field ID.
	 * @param mixed $fallback fallback default, if the field is not defined.
	 *
	 * @return mixed
	 */
	public static function get_default_for_field( $field, $fallback ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;
		$fields = self::BASE_FIELDS;

		if ( isset( $fields[ $field ], $fields[ $field ]['default'] ) ) {
			return $fields[ $field ]['default'];
		}

		$legacy_config = $sw_cloudflare_pagecache->get_default_config();

		if ( is_array( $legacy_config ) && isset( $legacy_config[ $field ] ) ) {
			return $legacy_config[ $field ];
		}

		return $fallback;
	}

	/**
	 * Get boolean value/
	 *
	 * @param string $key The setting key.
	 *
	 * @return int
	 */
	public static function is_on( $key ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$default_legacy   = $sw_cloudflare_pagecache->get_default_config();
		$fallback_default = isset( $default_legacy[ $key ] ) ? $default_legacy[ $key ] : false;

		$value = $sw_cloudflare_pagecache->get_single_config( $key, self::get_default_for_field( $key, $fallback_default ) );

		return (int) $value > 0;
	}
}
