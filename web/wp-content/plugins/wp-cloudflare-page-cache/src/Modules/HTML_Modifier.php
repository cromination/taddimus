<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Bypass_Resolver;
use SPC\Services\HTML_Minifier;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;

class HTML_Modifier implements Module_Interface {
	private const DEFAULT_CONFIG = [
		'disable_native_lazyload' => false,
		'lazy_load'               => false,
	];

	private const TAGS_TO_MODIFY = [ 'IMG', 'IFRAME', 'VIDEO' ];

	private static $skipped_images_lazyload = 0;

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'swcfpc_normal_fallback_cache_html', [ $this, 'alter_cached_html' ], 10 );
		add_filter( 'swcfpc_curl_fallback_cache_html', [ $this, 'alter_cached_html' ], 10 );
		add_filter( 'swcfpc_normal_fallback_cache_html', [ $this, 'minify_cached_html' ], 20 );
		add_filter( 'swcfpc_curl_fallback_cache_html', [ $this, 'minify_cached_html' ], 20 );
		add_action( 'template_redirect', [ $this, 'start_live_html_buffer' ], PHP_INT_MAX );
	}

	/**
	 * Alter HTML before saving into cache.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string
	 */
	public function alter_cached_html( $html ) {
		$lazy_load       = (int) Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOADING ) === 1;
		$native_lazyload = (int) Settings_Store::get_instance()->get( Constants::SETTING_NATIVE_LAZY_LOADING ) === 1;

		if ( ! \SPC\Loader::can_process_html() || ( ! $lazy_load && $native_lazyload ) ) {
			return $html;
		}

		return $this->parse_html(
			$html,
			wp_parse_args(
				[
					'lazy_load'               => $lazy_load,
					'disable_native_lazyload' => ! $native_lazyload,
				],
				self::DEFAULT_CONFIG
			)
		);
	}

	/**
	 * Minify cached HTML. Runs after other HTML modifiers (e.g. Pro's preload-link
	 * injection) so the persisted body matches the live minified output.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string
	 */
	public function minify_cached_html( $html ) {
		if ( (int) Settings_Store::get_instance()->get( Constants::SETTING_MINIFY_HTML ) !== 1 ) {
			return $html;
		}

		return ( new HTML_Minifier() )->minify( $html );
	}

	/**
	 * Start output buffering for live cacheable HTML responses so Cloudflare receives
	 * the minified body on first render, not only the fallback-cache copy.
	 *
	 * @return void
	 */
	public function start_live_html_buffer() {
		$settings = Settings_Store::get_instance();

		if ( is_admin() || (int) $settings->get( Constants::SETTING_MINIFY_HTML ) !== 1 ) {
			return;
		}

		if ( ! $settings->is_cache_enabled() ) {
			return;
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) !== 0 ) {
			return;
		}

		if ( Bypass_Resolver::is_url_to_bypass() || Bypass_Resolver::can_i_bypass_cache() ) {
			return;
		}

		if (
			is_feed() ||
			is_robots() ||
			is_trackback() ||
			( function_exists( 'is_sitemap' ) && is_sitemap() )
		) {
			return;
		}

		ob_start( [ $this, 'minify_live_html' ] );
	}

	/**
	 * Minify the live frontend response body.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string
	 */
	public function minify_live_html( $html ) {
		if ( ! is_string( $html ) || trim( $html ) === '' ) {
			return $html;
		}

		return ( new HTML_Minifier() )->minify( $html );
	}

	/**
	 * Parse the HTML and add required attributes.
	 *
	 * @param string $html The HTML content.
	 * @param array  $config The config to use when parsing.
	 *
	 * @return string
	 */
	private function parse_html( $html, $config ) {
		$parser = new \WP_HTML_Tag_Processor( $html );

		while ( $parser->next_tag() ) {
			$current_tag = $parser->get_tag();

			if ( ! in_array( $current_tag, self::TAGS_TO_MODIFY, true ) ) {
				continue;
			}

			if ( $config['lazy_load'] && in_array( $current_tag, $this->get_lazyloadable_tags(), true ) ) {
				$this->handle_lazy_load( $parser );
			}

			if ( ( $config['disable_native_lazyload'] ) && in_array(
				$current_tag,
				[
					'IMG',
					'IFRAME',
				],
				true
			) ) {
				$parser->remove_attribute( 'loading' );
			}
		}

		return $parser->get_updated_html();
	}

	/**
	 * Handle JS delay.
	 *
	 * @param \WP_HTML_Tag_Processor $parser The parser.
	 *
	 * @return void
	 */
	public function handle_lazy_load( $parser ) {
		$tag       = $parser->get_tag();
		$to_skip   = max( (int) Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOAD_SKIP_IMAGES, 2 ), 0 );
		$behaviour = Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOAD_BEHAVIOUR, Frontend::LAZY_LOAD_BEHAVIOUR_ALL );
		if ( $tag === 'IMG' && $behaviour === Frontend::LAZY_LOAD_BEHAVIOUR_FIXED && $to_skip > self::$skipped_images_lazyload ) {
			self::$skipped_images_lazyload++;

			return;
		}

		$exclusions = array_merge(
			Settings_Store::get_instance()->get( Constants::SETTING_LAZY_EXCLUDED, [] ),
			Constants::DEFAULT_LAZY_LOAD_EXCLUSIONS
		);
		if ( $tag === 'IMG' && ! $parser->get_attribute( 'data-spc-id' ) ) {
			$id = Helpers::get_url_id( $parser->get_attribute( 'src' ) );
			$parser->set_attribute( 'data-spc-id', (string) $id );
		} else {
			$id = $parser->get_attribute( 'data-spc-id' );
		}
		$data_attributes = $parser->get_attribute_names_with_prefix( 'data-' );

		$attribute_values_to_check = array_filter(
			array_map(
				function ( $attribute ) use ( $parser ) {
					return $parser->get_attribute( $attribute );
				},
				array_merge(
					$data_attributes,
					[
						'src',
						'srcset',
						'class',
					]
				)
			),
			'is_string'
		);

		foreach ( $attribute_values_to_check as $attribute_value ) {
			if ( array_filter(
				$exclusions,
				function ( $exclusion ) use ( $attribute_value ) {
					return strpos( $attribute_value, $exclusion ) !== false;
				}
			) ) {
				return;
			}
		}
		if ( $tag === 'IMG' ) {
			$parser = apply_filters( 'spc_html_modifier_parser_img', $parser, $id );
		}

		if ( $tag === 'IMG' && ( $parser->get_attribute( 'fetchpriority' ) === 'high' || $parser->get_attribute( 'data-spc-skip-lazyload' ) ) ) {
			return;
		}

		$parser->add_class( 'lazyload' );

		$attributes_to_replace = array_filter(
			[ 'src', 'srcset' ],
			function ( $attribute ) use ( $parser ) {
				return is_string( $parser->get_attribute( $attribute ) );
			}
		);

		foreach ( $attributes_to_replace as $attribute ) {
			$parser->set_attribute( 'data-' . $attribute, $parser->get_attribute( $attribute ) );
			$parser->remove_attribute( $attribute );
		}
	}

	/**
	 * Return the tags that can be lazy loaded.
	 *
	 * @return string[]
	 */
	private function get_lazyloadable_tags() {
		return (int) Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOAD_VIDEO_IFRAME ) === 1 ? [
			'IMG',
			'VIDEO',
			'IFRAME',
		] : [ 'IMG' ];
	}
}
