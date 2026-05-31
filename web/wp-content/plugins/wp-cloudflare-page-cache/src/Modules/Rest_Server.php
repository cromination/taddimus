<?php

namespace SPC\Modules;

use SPC\Modules\Database_Optimization;
use SPC\Constants;
use SPC\Models\Asset_Rules;
use SPC\Modules\Module_Interface;
use SPC\Modules\Preloader_Process;
use SPC\Modules\Settings_Manager;
use SPC\Services\Cloudflare_Client;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Log_Parser;
use SPC\Services\SDK_Integrations;
use SPC\Services\Settings_Store;
use SPC\Utils\Cache_Tester;
use SPC\Utils\Htaccess_Writer;
use SPC\Utils\Logger;
use SPC\Services\Notices_Handler;
use SPC\Services\Varnish;
use SPC\Utils\I18n;
use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;

class Rest_Server implements Module_Interface {


	public const REST_NAMESPACE                   = 'spc/v1';
	private const ALLOWED_CRITICAL_CSS_RULE_TYPES = [ 'declarations', 'fallback', 'import', 'font-face', 'keyframes', 'other' ];
	private const MAX_CRITICAL_CSS_PAYLOAD_BYTES  = 262144;

	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/cache/purge',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'purge_cache' ],
				'permission_callback' => [ \SPC\Utils\Helpers::class, 'can_current_user_purge_cache' ],
				// TODO Args for single page.
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cache/test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'test_cache' ],
				'permission_callback' => [ \SPC\Utils\Helpers::class, 'can_current_user_purge_cache' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cache/purge-varnish',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'purge_varnish_cache' ],
				'permission_callback' => [ \SPC\Utils\Helpers::class, 'can_current_user_purge_cache' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/logs/clear',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'clear_logs' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/logs/get',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_logs' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/preloader/start',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'start_preloader' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/config/import',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import_config' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'settings' => [
						'required'          => true,
						'validate_callback' => function ( $settings ) {
							return is_array( $settings );
						},
						'sanitize_callback' => [ $this, 'sanitize_settings' ],
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/toggle-license',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'toggle_license' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'key'    => [
						'type'              => 'string',
						'sanitize_callback' => function ( $key ) {
							return (string) esc_attr( $key );
						},
						'validate_callback' => function ( $key ) {
							return is_string( $key );
						},
					],
					'action' => [
						'type'              => 'string',
						'sanitize_callback' => function ( $key ) {
							return (string) esc_attr( $key );
						},
						'validate_callback' => function ( $key ) {
							return in_array( $key, [ 'activate', 'deactivate' ], true );
						},
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/settings/reset',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'reset_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/settings/wizard',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'enable_page_cache' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/settings/update',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'settings' => [
						'required'          => true,
						'validate_callback' => function ( $settings ) {
							return is_array( $settings );
						},
						'sanitize_callback' => [ $this, 'sanitize_settings' ],
					],
				],
			],
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cloudflare/connect',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cloudflare_connect' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'auth_mode'   => [
						'required'          => true,
						'validate_callback' => function ( $auth_mode ) {
							return in_array( $auth_mode, [ 'api_key', 'api_token' ], true );
						},
					],
					'email'       => [
						'required'          => false,
						'validate_callback' => function ( $email ) {
							return is_string( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL );
						},
						'sanitize_callback' => 'sanitize_email',
					],
					'api_key'     => [
						'required'          => false,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
					'api_token'   => [
						'required'          => false,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
					'domain_name' => [
						'required'          => false,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cloudflare/disconnect',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'cloudflare_disconnect' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cloudflare/confirm-zone-id',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cloudflare_confirm_zone_id' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'zone_id' => [
						'required'          => true,
						'validate_callback' => function ( $zone_id ) {
							return is_string( $zone_id ) && ! empty( $zone_id );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/database/optimize',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'optimize_database' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'action' => array(
						'required'          => false,
						'validate_callback' => function ( $action ) {
							return is_string( $action );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/database/optimize-counts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_database_optimization_counts' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cloudflare/analytics',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'cloudflare_analytics' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cloudflare/repair-rule',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'cloudflare_repair_rule' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/notice/dismiss',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'dismiss_notice' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'key' => [
						'required'          => true,
						'validate_callback' => function ( $key ) {
							return is_string( $key );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/assets/save-rules',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_assets_rules' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'assets_data' => [
						'required'          => true,
						'validate_callback' => function ( $assets_data ) {
							return is_array( $assets_data );
						},
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/optimizations',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'optimizations' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'd' => [
						'type'     => 'integer',
						'required' => true,
					],
					'a' => [
						'type'     => 'array',
						'required' => true,
					],
					'u' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/cached-pages',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_cached_pages' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Get cached pages.
	 *
	 * @param WP_REST_Request $request Rest request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_cached_pages( WP_REST_Request $request ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$cached_pages = $sw_cloudflare_pagecache->get_core_loader()->html_cache()->get_cached_urls();

		return $this->data_response( $cached_pages );
	}

	/**
	 * Store optimization data.
	 *
	 * Expected request parameters:
	 * - d: Device type (1=mobile, 2=desktop)
	 * - a: Array of above-fold image IDs
	 * - b: Background selectors object
	 * - l: LCP data (imageId, bgSelector, bgUrls, type)
	 * - c: Critical CSS data { css: structured_css_object }
	 * - u: Profile URL/ID
	 * - p: Base64 encoded page URL
	 * - t: Timestamp
	 * - h: HMAC signature
	 *
	 * @param WP_REST_Request $request Rest request.
	 *
	 * @return WP_REST_Response
	 */
	public function optimizations( WP_REST_Request $request ) {
		if ( ! Settings_Store::get_instance()->is_client_optimizations_enabled() ) {
			return $this->message_response( 'Optimization is not enabled', 400 );
		}
		$time     = $request->get_param( 't' );
		$hmac     = $request->get_param( 'h' );
		$page_url = $request->get_param( 'p' );
		if ( empty( $time ) || empty( $hmac ) ) {
			return $this->message_response( 'Missing required parameters', 400 );
		}

		$device_type       = $request->get_param( 'd' );
		$above_fold_images = $request->get_param( 'a' );
		$critical_css      = Settings_Store::get_instance()->get( Constants::SETTING_UNUSED_CSS ) ? $request->get_param( 'c' ) : [];
		$url               = $request->get_param( 'u' );
		if ( $time < time() - DAY_IN_SECONDS ) {
			return $this->message_response( 'Invalid Signature.', 400 );
		}
		if ( wp_hash( $url . $time . $page_url, 'nonce' ) !== $hmac ) {
			return $this->message_response( 'Invalid Signature.', 400 );
		}
		$bg_selectors = $request->get_param( 'b' );
		$lcp_data     = $request->get_param( 'l' );
		$origin       = $request->get_header( 'origin' );
		if ( empty( $origin ) || ! is_allowed_http_origin( $origin ) ) {
			return $this->message_response( 'Invalid origin', 400 );
		}
		if ( empty( $device_type ) || empty( $url ) || ! is_array( $above_fold_images ) ) {
			return $this->message_response( 'Missing required parameters', 400 );
		}
		if ( count( $above_fold_images ) > 20 ) {
			return $this->message_response( 'Above fold images limit exceeded', 400 );
		}
		if ( count( $bg_selectors ) > 100 ) {
			return $this->message_response( 'Background selectors limit exceeded', 400 );
		}
		if ( $url === \SPC_Pro\Modules\PageProfiler\Profile::PLACEHOLDER ) {
			return $this->message_response( 'Missing profile parameters', 400 );
		}
		global $sw_cloudflare_pagecache;
		$lazyload_background_selectors = $sw_cloudflare_pagecache->get_core_loader()->get_modules()['frontend']->is_background_lazyload_enabled()
		? $sw_cloudflare_pagecache->get_core_loader()->get_modules()['frontend']->get_lazyload_background_selectors() : [];
		$current_selectors             = array_values( $lazyload_background_selectors );
		$sanitized_selectors           = [];
		foreach ( $bg_selectors as $selector => $above_fold_bg_selectors ) {
			if ( ! in_array( $selector, $current_selectors, true ) ) {
				return $this->message_response( 'Invalid background selector', 400 );
			}
			if ( count( $above_fold_bg_selectors ) > 100 ) {
				return $this->message_response( 'Above fold background selectors limit exceeded', 400 );
			}
			$selector                         = strip_tags( $selector );
			$sanitized_selectors[ $selector ] = [];
			foreach ( $above_fold_bg_selectors as $above_fold_bg_selector => $bg_urls ) {
				if ( count( $bg_urls ) > 3 ) {
					return $this->message_response( 'Background URLs limit exceeded', 400 );
				}
				$sanitized_selectors[ $selector ][ strip_tags( $above_fold_bg_selector ) ] = array_filter(
					array_map( 'sanitize_url', array_values( $bg_urls ) )
				);
			}
		}
		$sanitized_lcp_data = [];
		if ( ! empty( $lcp_data ) ) {
			$sanitized_lcp_data['imageId']    = sanitize_text_field( $lcp_data['i'] ?? '' );
			$sanitized_lcp_data['bgSelector'] = sanitize_text_field( $lcp_data['s'] ?? '' );
			if ( count( $lcp_data['u'] ?? [] ) > 3 ) {
				return $this->message_response( 'LCP Background URLs limit exceeded', 400 );
			}
			$sanitized_lcp_data['bgUrls'] = array_filter(
				array_map( 'sanitize_url', array_values( $lcp_data['u'] ?? [] ) )
			);
			$sanitized_lcp_data['type']   = empty( $sanitized_lcp_data['imageId'] ) ? 'bg' : 'img';
		}
		$profile      = new \SPC_Pro\Modules\PageProfiler\Profile();
		$critical_css = $this->sanitize_critical_css_payload( $critical_css );
		if ( is_wp_error( $critical_css ) ) {
			return $this->message_response( 'Invalid critical CSS payload', 400 );
		}
		$profile->store( $url, $device_type, $above_fold_images, $sanitized_selectors, $sanitized_lcp_data, $critical_css );
		if ( $profile->exists_all( $url ) ) {
			$page_url = base64_decode( $page_url );
			add_filter(
				'spc_page_profiler_dont_delete_url',
				function ( $dont_delete, $key ) use ( $url ) {
					return $url === \SPC_Pro\Modules\PageProfiler\Profile::generate_id( $key );
				},
				10,
				2
			);
			Cache_Controller::purge_urls( [ ( $page_url ) ] );
		}
		return $this->message_response( 'Above fold data stored successfully' );
	}

	/**
	 * Validate and sanitize critical CSS payload data.
	 *
	 * @param mixed $critical_css Critical CSS payload from request.
	 *
	 * @return array{css?: array<string, array<string, array{_type: string, _cssText: string, _order?: int}>>}|\WP_Error
	 */
	private function sanitize_critical_css_payload( $critical_css ) {
		if ( empty( $critical_css ) ) {
			return [];
		}
		if ( ! is_array( $critical_css ) ) {
			return new \WP_Error( 'invalid_critical_css', 'Critical CSS payload must be an array.' );
		}
		$allowed_top_level_keys = [ 'css' ];
		$unknown_top_level_keys = array_diff( array_keys( $critical_css ), $allowed_top_level_keys );
		if ( ! empty( $unknown_top_level_keys ) || ! array_key_exists( 'css', $critical_css ) || ! is_array( $critical_css['css'] ) ) {
			return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS payload shape.' );
		}
		$encoded_payload = wp_json_encode( $critical_css );
		if ( ! is_string( $encoded_payload ) || strlen( $encoded_payload ) > self::MAX_CRITICAL_CSS_PAYLOAD_BYTES ) {
			return new \WP_Error( 'invalid_critical_css', 'Critical CSS payload too large.' );
		}

		$allowed_rule_keys = [ '_type', '_cssText', '_order' ];
		$sanitized_payload = [
			'css' => [],
		];

		foreach ( $critical_css['css'] as $media_query => $selectors ) {
			if ( ! is_string( $media_query ) || ! is_array( $selectors ) || $this->contains_unsafe_css_tokens( $media_query ) ) {
				return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS media query.' );
			}
			$sanitized_payload['css'][ $media_query ] = [];
			foreach ( $selectors as $selector => $rule_data ) {
				if ( ! is_string( $selector ) || ! is_array( $rule_data ) || $this->contains_unsafe_css_tokens( $selector ) || $this->contains_disallowed_lt_character( $selector ) ) {
					return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS selector.' );
				}

				$unknown_rule_keys = array_diff( array_keys( $rule_data ), $allowed_rule_keys );
				if ( ! empty( $unknown_rule_keys ) ) {
					return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS rule keys.' );
				}

				if ( ! isset( $rule_data['_type'], $rule_data['_cssText'] ) || ! is_string( $rule_data['_type'] ) || ! is_string( $rule_data['_cssText'] ) ) {
					return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS rule data.' );
				}
				if ( ! in_array( $rule_data['_type'], self::ALLOWED_CRITICAL_CSS_RULE_TYPES, true ) ) {
					return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS rule type.' );
				}
				if ( $this->contains_unsafe_css_tokens( $rule_data['_cssText'] ) || $this->contains_disallowed_angle_brackets( $rule_data['_cssText'] ) ) {
					return new \WP_Error( 'invalid_critical_css', 'Unsafe critical CSS content.' );
				}

				$sanitized_rule = [
					'_type'    => $rule_data['_type'],
					'_cssText' => $rule_data['_cssText'],
				];
				if ( array_key_exists( '_order', $rule_data ) ) {
					if ( ! is_numeric( $rule_data['_order'] ) ) {
						return new \WP_Error( 'invalid_critical_css', 'Invalid critical CSS rule order.' );
					}
					$sanitized_rule['_order'] = (int) $rule_data['_order'];
				}
				$sanitized_payload['css'][ $media_query ][ $selector ] = $sanitized_rule;
			}
		}
		return $sanitized_payload;
	}

	/**
	 * Detect potentially dangerous non-CSS tokens that may break out of style context.
	 *
	 * @param string $value Value to validate.
	 *
	 * @return bool
	 */
	private function contains_unsafe_css_tokens( string $value ): bool {
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value ) === 1 ) {
			return true;
		}

		$unsafe_patterns = [
			'/<\s*\/\s*style\b/i',
			'/<\s*style\b/i',
			'/<\s*\/\s*script\b/i',
			'/<\s*script\b/i',
			'/<!--/i',
			'/-->/i',
			'/<\?/i',
		];

		foreach ( $unsafe_patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) === 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reject raw angle brackets unless they are media query range operators.
	 *
	 * @param string $value Value to validate.
	 *
	 * @return bool
	 */
	private function contains_disallowed_angle_brackets( string $value ): bool {
		if ( strpos( $value, '<' ) === false && strpos( $value, '>' ) === false ) {
			return false;
		}

		$value = preg_replace_callback(
			'/@media\b[^{}]*\{/i',
			static function ( array $matches ): string {
				return preg_replace( '/(?<=\S)\s*(?:<=|>=|<|>)\s*(?=\S)/', '', $matches[0] );
			},
			$value
		);
		$value = preg_replace_callback(
			'/@property\b[^{}]*\{[^{}]*\}/i',
			static function ( array $matches ): string {
				return preg_replace( '/syntax\s*:\s*(["\']).*?\1/isu', 'syntax: ""', $matches[0] );
			},
			is_string( $value ) ? $value : ''
		);

		return is_string( $value ) && ( strpos( $value, '<' ) !== false || strpos( $value, '>' ) !== false );
	}

	/**
	 * Reject raw "<" in selector keys; ">" is a valid CSS combinator and remains allowed.
	 *
	 * @param string $value Value to validate.
	 *
	 * @return bool
	 */
	private function contains_disallowed_lt_character( string $value ): bool {
		return strpos( $value, '<' ) !== false;
	}


	/**
	 * Purge the entire Cloudflare cache via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 */
	public function purge_cache( WP_REST_Request $request ) {
		Logger::log( 'cache_controller::ajax_purge_whole_cache', 'Purge whole cache' );

		$status = Cache_Controller::purge_all( false, false );

		if ( ! $status ) {
			return $this->message_response(
				__( 'Failed to purge cache. Please try again later.', 'wp-cloudflare-page-cache' ),
				500
			);
		}

		return $this->message_response( __( 'Cache purge started. This may take a moment to complete.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Purge the Varnish cache via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 */
	public function purge_varnish_cache( WP_REST_Request $request ) {
		$varish_handler = new Varnish();
		$status         = $varish_handler->purge_varnish_cache();

		if ( ! is_array( $status ) || ! isset( $status['status'] ) ) {
			// If the response is not an array or does not contain the expected status.
			return $this->message_response(
				__( 'Failed to purge Varnish cache. Please try again later.', 'wp-cloudflare-page-cache' ),
				500
			);
		}

		if ( $status['status'] === 'error' ) {
			// If the status indicates an error.
			return $this->message_response(
				$status['error'] ?? __( 'An unknown error occurred while purging Varnish cache.', 'wp-cloudflare-page-cache' ),
				500
			);
		}

		return $this->message_response( __( 'Cache purge started. This may take a moment to complete.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Test the cache functionality from browser-captured Cloudflare headers.
	 *
	 * @param WP_REST_Request $request The REST request object. Body: { headers: {...} }.
	 * @return WP_REST_Response
	 */
	public function test_cache( WP_REST_Request $request ) {
		try {
			$headers = $request->get_param( 'headers' );
			$headers = is_array( $headers ) ? $headers : [];

			$results = ( new Cache_Tester() )->run( $headers );

			return $this->data_response( $results );
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while testing the cache: ', 'wp-cloudflare-page-cache' ) . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Clear the log file.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function clear_logs( WP_REST_Request $request ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		try {
			Logger::reset();
			return $this->message_response( __( 'Logs cleared successfully.', 'wp-cloudflare-page-cache' ) );
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while clearing logs: ', 'wp-cloudflare-page-cache' ) . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Get logs.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 */
	public function get_logs( WP_REST_Request $request ) {
		return $this->data_response( ( new Log_Parser() )->get_log_data() );
	}

	public function start_preloader( WP_REST_Request $request ) {
		if ( ! Settings_Store::get_instance()->get( Constants::SETTING_ENABLE_PRELOADER ) ) {
			return $this->message_response(
				__( 'Preloader is not enabled', 'wp-cloudflare-page-cache' ),
				400
			);
		}

		if ( ! Preloader_Process::can_start() ) {
			return $this->message_response(
				__( 'Unable to start the preloader. Another preloading process is currently running.', 'wp-cloudflare-page-cache' ),
				400
			);
		}

		if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return $this->message_response(
				__( 'You cannot start the preloader while the page cache is disabled.', 'wp-cloudflare-page-cache' ),
				400
			);
		}

		Preloader_Process::start_for_all_urls();

		return $this->message_response( __( 'Preloader started successfully', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Reset all settings and configurations.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 *
	 */
	public function reset_settings( WP_REST_Request $request ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$error = '';

		$settings = Settings_Store::get_instance();

		$cloudflare_handler     = new Cloudflare_Integration();
		$html_cache_handler     = $sw_cloudflare_pagecache->get_core_loader()->html_cache();
		$fallback_cache_handler = $sw_cloudflare_pagecache->get_core_loader()->fallback_cache();

		// Purge all caches and prevent preloader to start
		Cache_Controller::purge_all( true, false, true );

		// Reset old browser cache TTL
		$cloudflare_handler->change_browser_cache_ttl( $settings->get( Constants::SETTING_OLD_BC_TTL ), $error );

		// Delete the page rule
		$page_rule_id = $settings->get( Constants::RULE_ID_PAGE );
		if ( ! empty( $page_rule_id ) ) {
			$cloudflare_handler->delete_page_rule( $page_rule_id, $error );
		}

		// Delete additional page rule if exists
		$bypass_backend_page_rule_id = $settings->get( Constants::RULE_ID_BYPASS_BACKEND );
		if ( ! empty( $bypass_backend_page_rule_id ) ) {
			$cloudflare_handler->delete_page_rule( $bypass_backend_page_rule_id, $error );
		}

		// Delete Cache Rule
		$cloudflare_handler->delete_cache_rule();
		$settings
			->set( Constants::RULESET_ID_CACHE, '' )
			->set( Constants::RULE_ID_CACHE, '' );

		// Disable fallback cache
		if ( defined( 'SWCFPC_ADVANCED_CACHE' ) ) {
			$fallback_cache_handler->fallback_cache_advanced_cache_disable();
		}

		// Delete all cached HTML pages temp files
		$html_cache_handler->delete_all_cached_urls();

		if ( $settings->get( Constants::SETTING_ENABLE_DATABASE_OPTIMIZATION ) ) {
			$database_optimization = new Database_Optimization();
			$database_optimization->delete_events();
		}

		// Restore default plugin config
		$settings
			->reset()
			->save();

		// Delete all htaccess rules
		Htaccess_Writer::reset();

		// Unschedule purge cache cron
		$timestamp = wp_next_scheduled( 'swcfpc_cache_purge_cron' );

		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, 'swcfpc_cache_purge_cron' );
			wp_clear_scheduled_hook( 'swcfpc_cache_purge_cron' );
		}

		// Reset log
		Logger::reset();
		Logger::log( 'cache_controller::reset_all', 'Reset complete' );

		if ( ! empty( $error ) ) {
			return $this->message_response(
				__( 'Settings reset completed with some errors: ', 'wp-cloudflare-page-cache' ) . $error,
				500
			);
		}

		return $this->message_response(
			__( 'Settings reset completed successfully.', 'wp-cloudflare-page-cache' )
		);
	}

	/**
	 * Enable the page cache via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function enable_page_cache( WP_REST_Request $request ) {
		global $sw_cloudflare_pagecache;

		Settings_Store::get_instance()
			->set( Constants::SETTING_CF_CACHE_ENABLED, 1 )
			->set( Constants::SETTING_ENABLE_FALLBACK_CACHE, 1 )
			->save();

		if (
			Settings_Manager::is_on( Constants::SETTING_ENABLE_FALLBACK_CACHE ) &&
			! Settings_Manager::is_on( Constants::SETTING_FALLBACK_CACHE_CURL ) &&
			! defined( 'SWCFPC_ADVANCED_CACHE' )
		) {
			$sw_cloudflare_pagecache->get_core_loader()->fallback_cache()->fallback_cache_advanced_cache_enable();
		}

		$return_array['success_msg'] = __( 'Page cache enabled successfully', 'wp-cloudflare-page-cache' );

		// Enable the fallback cache
		$sw_cloudflare_pagecache->get_core_loader()->fallback_cache()->fallback_cache_enable();

		// Set the config to enable the page cache
		Settings_Store::get_instance()
			->set( Constants::SETTING_ENABLE_FALLBACK_CACHE, 1 )
			->save();

		return $this->data_response(
			[
				'message'  => __( 'Page cache enabled successfully.', 'wp-cloudflare-page-cache' ),
				'settings' => $this->get_safe_settings_payload(),
				'meta'     => $this->get_dashboard_state_payload(),
			]
		);
	}

	/**
	 * Toggle the license key.
	 *
	 * @param WP_REST_Request $request activation request.
	 *
	 * @return WP_REST_Response
	 */
	public function toggle_license( WP_REST_Request $request ) {
		$fields = $request->get_json_params();

		if ( ! isset( $fields['key'] ) || ! isset( $fields['action'] ) ) {
			return $this->data_response(
				[
					'message' => __( 'Please refresh the page and try again.', 'wp-cloudflare-page-cache' ),
					'success' => false,
				],
				400
			);
		}

		$response = apply_filters( 'themeisle_sdk_license_process_spc', $fields['key'], $fields['action'] );

		$license_option_key = ( new SDK_Integrations() )->get_license_option_key();
		wp_cache_delete( $license_option_key, 'options' );
		$license        = get_option( $license_option_key, (object) [] );
		$license_status = ( (array) $license )['license'] ?? '';

		if ( is_wp_error( $response ) ) {
			// If activating and the license is already valid in the DB (e.g. already activated
			// on this domain), treat it the same as a fresh activation so the UI updates correctly.
			if ( $fields['action'] === 'activate' && $license_status === 'valid' ) {
				return $this->data_response(
					[
						'message' => __( 'Activated', 'wp-cloudflare-page-cache' ),
						'success' => true,
						'license' => $license,
					]
				);
			}

			return $this->message_response(
				$response->get_error_message(),
				500
			);
		}

		return $this->data_response(
			[
				'message' => $fields['action'] === 'activate' ? __( 'Activated', 'wp-cloudflare-page-cache' ) : __( 'Deactivated', 'wp-cloudflare-page-cache' ),
				'success' => true,
				'license' => $license,
			]
		);
	}

	/**
	 * Update settings via REST API.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function update_settings( WP_REST_Request $request ) {
		$data = $request->get_body();

		if ( empty( $data ) || ! is_string( $data ) ) {
			return $this->message_response(
				__( 'Invalid settings format provided.', 'wp-cloudflare-page-cache' ),
				400
			);
		}

		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['settings'] ) ) {
			return $this->message_response(
				__( 'Invalid settings format provided.', 'wp-cloudflare-page-cache' ),
				400
			);
		}

		try {
			$settings_manager = new Settings_Manager();
			$result           = $settings_manager->update_settings( $data['settings'], true );

			if ( empty( $result['updated'] ) && ! empty( $result['rejected'] ) ) {
				return $this->message_response(
					$this->get_overridden_settings_message( $result['rejected'] ),
					409
				);
			}

				return $this->data_response(
					[
						'message'  => $this->get_settings_update_message( $result['rejected'] ),
						'settings' => $this->get_safe_settings_payload(),
						'meta'     => $this->get_dashboard_state_payload(),
						'rejected' => $result['rejected'],
					]
				);
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while updating settings: ', 'wp-cloudflare-page-cache' ) . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Import settings from a JSON file.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function import_config( WP_REST_Request $request ) {
		$data = $request->get_body();

		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['settings'] ) ) {
			return $this->message_response( __( 'Invalid settings format provided.', 'wp-cloudflare-page-cache' ), 400 );
		}

		$status = Settings_Store::get_instance()->import_settings( $data['settings'], true );

		if ( ! $status['success'] && empty( $status['rejected'] ) ) {
			return $this->message_response( __( 'Failed to import settings.', 'wp-cloudflare-page-cache' ), 500 );
		}

		if ( empty( $status['imported'] ) && ! empty( $status['rejected'] ) ) {
			return $this->message_response( $this->get_overridden_settings_message( $status['rejected'] ), 409 );
		}

		return $this->data_response(
			[
				'message'  => $this->get_import_settings_message( $status['rejected'] ),
				'rejected' => $status['rejected'],
			]
		);
	}

	/**
	 * Connect to Cloudflare via API Key or API Token.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function cloudflare_connect( WP_REST_Request $request ) {
		$data = $request->get_body();
		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['auth_mode'] ) ) {
			return $this->message_response( I18n::get( 'invalidDataFormat' ), 400 );
		}

		$auth_mode = $data['auth_mode'];
		$error_msg = '';

		try {
			$settings = Settings_Store::get_instance();
			$client   = new Cloudflare_Client();

			// Set authentication mode using constants
			$auth_mode_value = $auth_mode === 'api_key' ? SWCFPC_AUTH_MODE_API_KEY : SWCFPC_AUTH_MODE_API_TOKEN;
			$auth_overrides  = [
				Constants::SETTING_AUTH_MODE => $auth_mode_value,
			];

			if ( $settings->is_overridden( Constants::SETTING_AUTH_MODE ) && (int) $settings->get( Constants::SETTING_AUTH_MODE ) !== $auth_mode_value ) {
				return $this->message_response( $this->get_overridden_settings_message( [ Constants::SETTING_AUTH_MODE ] ), 409 );
			}

			$protected_keys = [
				Constants::ZONE_ID_LIST,
			];

			if ( $auth_mode === 'api_key' ) {
				if ( ! isset( $data['email'] ) || ! isset( $data['api_key'] ) ) {
					return $this->message_response( __( 'Email and API Key are required for API Key authentication.', 'wp-cloudflare-page-cache' ), 400 );
				}

				$email   = sanitize_email( $data['email'] );
				$api_key = sanitize_text_field( $data['api_key'] );

				if ( empty( $email ) || empty( $api_key ) ) {
					return $this->message_response( __( 'Email and API Key are required for API Key authentication.', 'wp-cloudflare-page-cache' ), 400 );
				}

				$auth_overrides[ Constants::SETTING_CF_EMAIL ]   = $email;
				$auth_overrides[ Constants::SETTING_CF_API_KEY ] = $api_key;
				$protected_keys[]                                = Constants::SETTING_CF_EMAIL;
				$protected_keys[]                                = Constants::SETTING_CF_API_KEY;
			} elseif ( $auth_mode === 'api_token' ) {
				if ( ! isset( $data['api_token'] ) ) {
					return $this->message_response( __( 'API Token is required for API Token authentication.', 'wp-cloudflare-page-cache' ), 400 );
				}

				$api_token = sanitize_text_field( $data['api_token'] );

				if ( empty( $api_token ) ) {
					return $this->message_response( __( 'API Token is required for API Token authentication.', 'wp-cloudflare-page-cache' ), 400 );
				}

				$auth_overrides[ Constants::SETTING_CF_API_TOKEN ] = $api_token;
				$protected_keys[]                                  = Constants::SETTING_CF_API_TOKEN;
			} else {
				return $this->message_response( __( 'Invalid authentication mode provided.', 'wp-cloudflare-page-cache' ), 400 );
			}

			$override_check = $settings->split_overridden_settings( array_fill_keys( $protected_keys, true ) );

			if ( ! empty( $override_check['rejected'] ) ) {
				return $this->message_response( $this->get_overridden_settings_message( $override_check['rejected'] ), 409 );
			}

			// Validate credentials against Cloudflare before persisting them.
			$zone_id_list = $client->get_zone_id_list( $error_msg, $auth_overrides );

			if ( $zone_id_list === false || ! empty( $error_msg ) ) {
				if ( strpos( $error_msg, 'err code: 6003' ) !== false ) {
					$error_msg = __( 'Cloudflare rejected the authentication headers. Verify your auth mode and credentials.', 'wp-cloudflare-page-cache' );
				}

				return $this->message_response(
					__( 'Failed to connect to Cloudflare: ', 'wp-cloudflare-page-cache' ) . $error_msg,
					400
				);
			}

			if ( ! $settings->is_overridden( Constants::SETTING_AUTH_MODE ) ) {
				$settings->set( Constants::SETTING_AUTH_MODE, $auth_mode_value );
			}

			if ( $auth_mode === 'api_key' ) {
				$settings
					->set( Constants::SETTING_CF_EMAIL, $auth_overrides[ Constants::SETTING_CF_EMAIL ] )
					->set( Constants::SETTING_CF_API_KEY, $auth_overrides[ Constants::SETTING_CF_API_KEY ] );

				if ( ! $settings->is_overridden( Constants::SETTING_CF_API_TOKEN ) ) {
					$settings->set( Constants::SETTING_CF_API_TOKEN, '' );
				}

				if ( ! $settings->is_overridden( Constants::SETTING_CF_DOMAIN_NAME ) ) {
					$settings->set( Constants::SETTING_CF_DOMAIN_NAME, '' );
				}
			} else {
				$settings
					->set( Constants::SETTING_CF_API_TOKEN, $auth_overrides[ Constants::SETTING_CF_API_TOKEN ] );

				if ( ! $settings->is_overridden( Constants::SETTING_CF_EMAIL ) ) {
					$settings->set( Constants::SETTING_CF_EMAIL, '' );
				}

				if ( ! $settings->is_overridden( Constants::SETTING_CF_API_KEY ) ) {
					$settings->set( Constants::SETTING_CF_API_KEY, '' );
				}
			}

			$settings->set( Constants::ZONE_ID_LIST, $zone_id_list );

			if ( ! $settings->is_overridden( Constants::SETTING_CF_ZONE_ID ) ) {
				$current_zone_id = (string) $settings->get( Constants::SETTING_CF_ZONE_ID, '' );

				if ( '' !== $current_zone_id && ! in_array( $current_zone_id, array_values( $zone_id_list ), true ) ) {
					$settings->set( Constants::SETTING_CF_ZONE_ID, '' );
				}
			}

			// Final save
			$settings->save();

			$response_data = [
				'auth_mode' => $auth_mode,
				'settings'  => $this->get_safe_settings_payload(),
				'meta'      => $this->get_dashboard_state_payload(),
				'message'   => $auth_mode === 'api_key'
					? __( 'Cloudflare connected successfully via API Key.', 'wp-cloudflare-page-cache' )
					: __( 'Cloudflare connected successfully via API Token.', 'wp-cloudflare-page-cache' ),
			];

			return $this->data_response( $response_data );
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while connecting to Cloudflare: ', 'wp-cloudflare-page-cache' ) . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Disconnect from Cloudflare.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function cloudflare_disconnect( WP_REST_Request $request ) {
		$error    = '';
		$settings = Settings_Store::get_instance();

		$override_check = $settings->split_overridden_settings(
			array_fill_keys(
				[
					Constants::SETTING_CF_EMAIL,
					Constants::SETTING_CF_API_KEY,
					Constants::SETTING_CF_API_TOKEN,
					Constants::SETTING_CF_DOMAIN_NAME,
					Constants::ZONE_ID_LIST,
					Constants::SETTING_CF_ZONE_ID,
					Constants::RULESET_ID_CACHE,
					Constants::RULE_ID_CACHE,
					Constants::ENABLE_CACHE_RULE,
				],
				true
			)
		);

		if ( ! empty( $override_check['rejected'] ) ) {
			return $this->message_response( $this->get_overridden_settings_message( $override_check['rejected'] ), 409 );
		}

		( new Cloudflare_Integration() )->disconnect( $error );

		$settings
			->set( Constants::SETTING_CF_EMAIL, '' )
			->set( Constants::SETTING_CF_API_KEY, '' )
			->set( Constants::SETTING_CF_API_TOKEN, '' )
			->set( Constants::SETTING_CF_DOMAIN_NAME, '' )
			->set( Constants::ZONE_ID_LIST, null )
			->set( Constants::SETTING_CF_ZONE_ID, '' )
			->set( Constants::RULESET_ID_CACHE, '' )
			->set( Constants::RULE_ID_CACHE, '' )
			->set( Constants::ENABLE_CACHE_RULE, 0 )
			->save();

		return $this->data_response(
			[
				'message'  => __( 'Disconnected from Cloudflare.', 'wp-cloudflare-page-cache' ),
				'settings' => $this->get_safe_settings_payload(),
				'meta'     => $this->get_dashboard_state_payload(),
			]
		);
	}

	/**
	 * Confirm the zone ID.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function cloudflare_confirm_zone_id( WP_REST_Request $request ) {
		$data = $request->get_body();
		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['zone_id'] ) ) {
			return $this->message_response( I18n::get( 'invalidDataFormat' ), 400 );
		}

		$zone_id = $data['zone_id'];

		$settings = Settings_Store::get_instance();

		if ( $settings->is_overridden( Constants::SETTING_CF_ZONE_ID ) ) {
			return $this->message_response( $this->get_overridden_settings_message( [ Constants::SETTING_CF_ZONE_ID ] ), 409 );
		}

		$is_token_auth = $settings->get( Constants::SETTING_AUTH_MODE ) === SWCFPC_AUTH_MODE_API_TOKEN;

		if ( $is_token_auth ) {
			/**
			 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
			 */
			global $sw_cloudflare_pagecache;
			$client = new Cloudflare_Client();

			$missing_permissions = $client->verify_token_permissions( $zone_id );

			if ( is_wp_error( $missing_permissions ) ) {
				return $this->message_response( $missing_permissions->get_error_message(), 400 );
			}

			if ( ! is_array( $missing_permissions ) ) {
				return $this->message_response( __( 'Invalid response from Cloudflare.', 'wp-cloudflare-page-cache' ), 400 );
			}

			if ( ! empty( $missing_permissions ) ) {
				return $this->data_response(
					[
						'success'     => false,
						'permissions' => $missing_permissions,
					]
				);
			}
		}

		$settings->set( Constants::SETTING_CF_ZONE_ID, $zone_id )->save();

		return $this->data_response(
			[
				'message'  => __( 'Successfully connected to Cloudflare.', 'wp-cloudflare-page-cache' ),
				'settings' => $this->get_safe_settings_payload(),
				'meta'     => $this->get_dashboard_state_payload(),
			]
		);
	}

	/**
	 * Optimize the database.
	 * This method handles various database optimization tasks based on the action specified in the request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function optimize_database( WP_REST_Request $request ) {
		$data                  = $request->get_body();
		$data                  = json_decode( $data, true );
		$action                = isset( $data['action'] ) ? $data['action'] : 'all';
		$database_optimization = new Database_Optimization();

		try {
			$message = '';

			switch ( $action ) {
				case Constants::SETTING_POST_REVISION_INTERVAL:
					$message = $database_optimization->remove_revision_posts();
					break;
				case Constants::SETTING_AUTO_DRAFT_POST_INTERVAL:
					$message = $database_optimization->remove_draft_posts();
					break;
				case Constants::SETTING_TRASHED_POST_INTERVAL:
					$message = $database_optimization->remove_trashed_posts();
					break;
				case Constants::SETTING_SPAM_COMMENT_INTERVAL:
					$message = $database_optimization->remove_spam_comments();
					break;
				case Constants::SETTING_TRASHED_COMMENT_INTERVAL:
					$message = $database_optimization->remove_trashed_comments();
					break;
				case Constants::SETTING_ALL_TRANSIENT_INTERVAL:
					$message = $database_optimization->remove_transients();
					break;
				case Constants::SETTING_OPTIMIZE_TABLE_INTERVAL:
					$message = $database_optimization->optimize_database_table();
					break;
				default:
					$message = $database_optimization->remove_all();
			}

			$database_optimization->clear_cleanup_counts_cache();

			return $this->message_response( $message );
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while optimizing the database:', 'wp-cloudflare-page-cache' ) . ' ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Get database optimization preview counts.
	 *
	 * @return WP_REST_Response
	 */
	public function get_database_optimization_counts() {
		try {
			$database_optimization = new Database_Optimization();

			return $this->data_response(
				[
					'counts' => $database_optimization->get_cleanup_counts(),
				]
			);
		} catch ( \Exception $e ) {
			return $this->message_response(
				__( 'An error occurred while fetching database optimization counts:', 'wp-cloudflare-page-cache' ) . ' ' . $e->getMessage(),
				500
			);
		}
	}

	/**
	 * Get the Cloudflare analytics for the last 24 hours.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function cloudflare_analytics( WP_REST_Request $request ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$client = new Cloudflare_Client();

		$analytics = $client->get_analytics();

		// We don't want to show an error message to the user.
		if ( is_wp_error( $analytics ) ) {
			return $this->data_response( [] );
		}

		return $this->data_response(
			$analytics
		);
	}

	/**
	 * Repair the Cloudflare rule.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function cloudflare_repair_rule( WP_REST_Request $request ) {
		/**
		 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
		 */
		global $sw_cloudflare_pagecache;

		$error = '';

		$status = ( new Cloudflare_Integration() )->reset_cf_rule( $error );

		if ( ! empty( $error ) ) {
			return $this->message_response( $error, 400 );
		}

		if ( ! $status ) {
			return $this->message_response( __( 'Failed to repair the Cloudflare rule.', 'wp-cloudflare-page-cache' ), 400 );
		}

		delete_option( Constants::KEY_RULE_UPDATE_FAILED );

		return $this->message_response( __( 'Cloudflare rule repaired successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Dismiss a notice.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function dismiss_notice( WP_REST_Request $request ) {
		$data = $request->get_body();
		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['key'] ) ) {
			return $this->message_response( I18n::get( 'invalidDataFormat' ), 400 );
		}

		$dismiss = Notices_Handler::dismiss( $data['key'] );

		if ( ! $dismiss ) {
			return $this->message_response( __( 'Failed to dismiss notice.', 'wp-cloudflare-page-cache' ), 400 );
		}

		return $this->message_response( __( 'Notice dismissed successfully.', 'wp-cloudflare-page-cache' ) );
	}

	/**
	 * Save assets rules.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function save_assets_rules( WP_REST_Request $request ) {
		$data = $request->get_body();

		$data = json_decode( $data, true );

		if ( ! is_array( $data ) || ! isset( $data['assets_data'] ) ) {
			return $this->message_response( I18n::get( 'invalidDataFormat' ), 400 );
		}

		$assets_data = $data['assets_data'];

		if ( ! is_array( $assets_data ) || empty( $assets_data ) ) {
			return $this->message_response( __( 'No changes to save.', 'wp-cloudflare-page-cache' ), 400 );
		}

		$saved_count = 0;

		foreach ( $assets_data as $asset_hash => $asset_data ) {
			$result = Asset_Rules::upsert_asset_rules( $asset_hash, $asset_data );

			if ( $result !== false ) {
				$saved_count++;
			}
		}

		Cache_Controller::purge_all( false, false );

		$message = __( 'No changes to save.', 'wp-cloudflare-page-cache' );

		if ( $saved_count > 0 ) {
			$message = $saved_count > 1 ? sprintf(
				// translators: %d is the number of assets with saved rules.
				__( 'Saved rules for %d assets.', 'wp-cloudflare-page-cache' ),
				$saved_count
			) : sprintf(
				__( 'Saved rules for one asset.', 'wp-cloudflare-page-cache' ),
				$saved_count
			);
		}

		return $this->message_response( $message );
	}

	/**
	 * Sanitize incoming settings via REST API.
	 *
	 * @param array $settings The incoming settings.
	 *
	 * @return array
	 */
	public function sanitize_settings( array $settings ) {
		$settings_manager = new Settings_Manager();
		$valid_fields     = array_keys( $settings_manager->get_fields() );

		return array_intersect_key( $settings, array_flip( $valid_fields ) );
	}

	/**
	 * Send a response with a message and status code.
	 *
	 * @param string $message The message to send.
	 * @param int $status_code The HTTP status code (default is 200).
	 *
	 * @return WP_REST_Response
	 */
	final protected function message_response( string $message, int $status_code = 200 ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success' => $status_code === 200,
				'message' => $message,
			],
			$status_code
		);
	}

	/**
	 * Send a response with data and status code.
	 *
	 * @param array $data The data to send.
	 * @param int $status_code The HTTP status code (default is 200).
	 *
	 * @return WP_REST_Response
	 */
	final protected function data_response( array $data = [], int $status_code = 200 ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success' => $status_code === 200,
				'message' => $data['message'] ?? '',
				'data'    => $data,
			],
			$status_code
		);
	}

	/**
	 * Build a frontend-safe settings payload that does not expose sensitive values.
	 *
	 * @return array<string, mixed>
	 */
	private function get_safe_settings_payload(): array {
		$settings_manager = new Settings_Manager();
		$settings         = Settings_Store::get_instance()->get_all();

		foreach ( array_keys( $settings ) as $key ) {
			if ( $settings_manager->is_encrypted_field( $key ) ) {
				$settings[ $key ] = '';
			}
		}

		return $settings;
	}

	/**
	 * Build a lightweight runtime state payload for the dashboard app.
	 *
	 * @return array<string, bool>
	 */
	private function get_dashboard_state_payload(): array {
		$settings = Settings_Store::get_instance();

		return [
			'cloudflareConnected'    => $settings->is_cloudflare_connected(),
			'invalidEncryptionState' => $settings->should_show_invalid_encryption_notice(),
		];
	}

	/**
	 * Build a consistent message for overridden settings.
	 *
	 * @param list<string> $keys Rejected keys.
	 *
	 * @return string
	 */
	private function get_overridden_settings_message( array $keys ): string {
		return sprintf(
			/* translators: %s: comma-separated settings keys. */
			__( 'These settings are managed by wp-config.php and cannot be changed here: %s', 'wp-cloudflare-page-cache' ),
			implode( ', ', $keys )
		);
	}

	/**
	 * Build the settings update success message.
	 *
	 * @param list<string> $rejected Rejected keys.
	 *
	 * @return string
	 */
	private function get_settings_update_message( array $rejected ): string {
		if ( empty( $rejected ) ) {
			return __( 'Settings updated successfully.', 'wp-cloudflare-page-cache' );
		}

		return __( 'Settings updated successfully. Some wp-config.php managed settings were skipped.', 'wp-cloudflare-page-cache' );
	}

	/**
	 * Build the config import success message.
	 *
	 * @param list<string> $rejected Rejected keys.
	 *
	 * @return string
	 */
	private function get_import_settings_message( array $rejected ): string {
		if ( empty( $rejected ) ) {
			return __( 'Settings imported successfully.', 'wp-cloudflare-page-cache' );
		}

		return __( 'Settings imported successfully. Some wp-config.php managed settings were skipped.', 'wp-cloudflare-page-cache' );
	}
}
