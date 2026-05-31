<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Bypass_Resolver;
use SPC\Services\Settings_Store;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Cache buster query arg orchestration for logged-in users.
 *
 * The cache buster (a query arg appended to URLs) prevents the CDN edge from serving
 * a logged-in user a cached response intended for an anonymous visitor. Lifecycle:
 *   - JS injection (`inject_cache_buster_js_code`) appends `?<buster>=1` to internal links
 *     for logged-in users so subsequent navigation bypasses the edge cache.
 *   - `wp_redirect_filter` does the same on server-side redirects.
 *   - `redirect_301_real_url` 301-redirects search-engine crawlers that landed on a buster
 *     URL back to the canonical (busted-arg-stripped) URL.
 */
class Cache_Buster implements Module_Interface {

	public function init() {
		// SEO redirect for URLs indexed with the cache buster query arg.
		if ( Settings_Store::get_instance()->get( Constants::SETTING_SEO_REDIRECT, 1 ) > 0 ) {
			add_action( 'init', [ $this, 'redirect_301_real_url' ], 0 );
		}

		add_action( 'wp_footer', [ $this, 'inject_cache_buster_js_code' ], PHP_INT_MAX );
		add_action( 'admin_footer', [ $this, 'inject_cache_buster_js_code' ], PHP_INT_MAX );

		// Append the cache buster to server-side redirects for logged-in users.
		add_filter( 'wp_redirect', [ $this, 'wp_redirect_filter' ], PHP_INT_MAX, 2 );
	}

	/**
	 * 301 anonymous visitors that hit a URL containing the cache buster back to the
	 * canonical (stripped) URL. Search engines that indexed a busted URL get cleaned up.
	 *
	 * @return void
	 */
	public function redirect_301_real_url() {
		if ( ! is_user_logged_in() && ( isset( $_GET['swcfpc-preloader'] ) || isset( $_GET['swcfpc-purge-all'] ) ) ) {
			return;
		}

		if ( is_user_logged_in() || empty( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}

		if ( strpos( $_SERVER['QUERY_STRING'], SWCFPC_CACHE_BUSTER ) === false ) {
			return;
		}

		$parts       = parse_url( home_url() );
		$current_uri = "{$parts['scheme']}://{$parts['host']}" . add_query_arg( null, null );

		$parsed       = parse_url( $current_uri );
		$query_string = $parsed['query'];

		parse_str( $query_string, $params );
		unset( $params[ SWCFPC_CACHE_BUSTER ] );
		$query_string = http_build_query( $params );

		$current_uri = "{$parts['scheme']}://{$parts['host']}";
		if ( isset( $parsed['path'] ) ) {
			$current_uri .= $parsed['path'];
		}
		if ( strlen( $query_string ) > 0 ) {
			$current_uri .= "?{$query_string}";
		}

		wp_redirect( $current_uri, 301 );
		die();
	}

	/**
	 * Append the cache buster to outgoing redirects for logged-in users so the CDN
	 * doesn't serve them a cached anonymous response after the redirect.
	 *
	 * @param string $location
	 * @param int    $status
	 * @return string
	 */
	public function wp_redirect_filter( $location, $status ) {
		$settings = Settings_Store::get_instance();

		if ( Bypass_Resolver::should_remove_cache_buster() ) {
			return $location;
		}

		if ( apply_filters( 'swcfpc_bypass_redirect_cache_buster', false, $location ) === true ) {
			return $location;
		}

		if ( ! $settings->is_cache_enabled() ) {
			return $location;
		}

		if ( ! is_user_logged_in() ) {
			return $location;
		}

		$cache_buster = SWCFPC_CACHE_BUSTER;

		if ( strpos( $location, $cache_buster ) === false ) {
			$location = add_query_arg( [ $cache_buster => '1' ], $location );
		}

		return $location;
	}

	/**
	 * Inject the JS that rewrites internal links to include the cache buster query arg
	 * for logged-in users. Frontend uses `a` selector; admin uses a curated selector list
	 * targeting the toolbar / preview / publish-success links so we don't touch every link
	 * in the admin UI.
	 *
	 * @return void
	 */
	public function inject_cache_buster_js_code() {
		if ( ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		if ( Bypass_Resolver::should_remove_cache_buster() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! is_admin() && ( ( function_exists( 'amp_is_request' ) && \amp_is_request() ) || ( function_exists( 'ampforwp_is_amp_endpoint' ) && \ampforwp_is_amp_endpoint() ) ) ) {
			return;
		}

		$cache_buster = SWCFPC_CACHE_BUSTER;
		$selectors    = 'a';

		if ( is_admin() ) {
			$selectors = '#wp-admin-bar-my-sites-list a, #wp-admin-bar-site-name a, #wp-admin-bar-view-site a, #wp-admin-bar-view a, .row-actions a, .preview, #sample-permalink a, #message a, #editor .is-link, #editor .editor-post-preview, #editor .editor-post-permalink__link, .edit-post-post-link__preview-link-container .edit-post-post-link__link';
		}

		?>
		<script id="swcfpc">
			var swcfpc_adjust_internal_links = function(selectors_txt) {

			const comp = new RegExp(location.host);
			const current_url = window.location.href.split("#")[0];

			[].forEach.call(document.querySelectorAll(selectors_txt), function(el) {

				if (comp.test(el.href) && !el.href.includes("<?php echo $cache_buster; ?>=1") && !el.href.startsWith("#") && !el.href.startsWith(current_url + "#")) {

				if (el.href.indexOf('#') != -1) {

					const link_split = el.href.split("#");
					el.href = link_split[0];
					el.href += (el.href.indexOf('?') != -1 ? "&<?php echo $cache_buster; ?>=1" : "?<?php echo $cache_buster; ?>=1");
					el.href += "#" + link_split[1];

				} else {
					el.href += (el.href.indexOf('?') != -1 ? "&<?php echo $cache_buster; ?>=1" : "?<?php echo $cache_buster; ?>=1");
				}

				}

			});

			}

			document.addEventListener("DOMContentLoaded", function() {
			swcfpc_adjust_internal_links("<?php echo $selectors; ?>");
			});

			window.addEventListener("load", function() {
			swcfpc_adjust_internal_links("<?php echo $selectors; ?>");
			});

			setInterval(function() {
			swcfpc_adjust_internal_links("<?php echo $selectors; ?>");
			}, 3000);

			var swcfpc_wordpress_btn_publish = document.querySelector(".editor-post-publish-button__button");

			if (swcfpc_wordpress_btn_publish !== undefined && swcfpc_wordpress_btn_publish !== null) {

			swcfpc_wordpress_btn_publish.addEventListener('click', function() {

				var swcfpc_wordpress_edited_post_interval = setInterval(function() {

				var swcfpc_wordpress_edited_post_link = document.querySelector(".components-snackbar__action");

				if (swcfpc_wordpress_edited_post_link !== undefined) {
					swcfpc_adjust_internal_links(".components-snackbar__action");
					clearInterval(swcfpc_wordpress_edited_post_interval);
				}

				}, 100);

			}, false);

			}
		</script>
		<?php
	}
}
