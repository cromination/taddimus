<?php

namespace SPC\Services;

use SPC\Constants;
use SPC\Modules\Third_Party;
use SPC\Utils\Helpers;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Stateless predicates that decide whether the current request should be cached or bypassed.
 *
 * `is_url_to_bypass()` runs before WordPress is fully loaded (it's also called from the
 * `advanced-cache.php` drop-in). `can_i_bypass_cache()` runs later when conditional tags
 * (`is_front_page()`, `is_cart()`, etc.) are available.
 */
class Bypass_Resolver {

	/**
	 * Decide whether to bypass cache based on request-time signals (URL, headers, request method).
	 *
	 * Safe to call before `template_redirect`. Used by both the runtime PHP path and the
	 * `advanced-cache.php` drop-in.
	 *
	 * @return bool
	 */
	public static function is_url_to_bypass() {
		$settings = Settings_Store::get_instance();

		if ( Helpers::is_api_request() ) {
			Helpers::bypass_reason_header( 'API Request' );
			return true;
		}

		if ( $settings->get( Constants::SETTING_BYPASS_AMP, 0 ) > 0 && preg_match( '/(\/)((\?amp)|(amp\/))/', $_SERVER['REQUEST_URI'] ) ) {
			Helpers::bypass_reason_header( 'AMP' );
			return true;
		}

		if ( $settings->get( Constants::SETTING_BYPASS_SITEMAP, 0 ) > 0 && ( strcasecmp( $_SERVER['REQUEST_URI'], '/sitemap_index.xml' ) == 0 || preg_match( '/[a-zA-Z0-9]-sitemap.xml$/', $_SERVER['REQUEST_URI'] ) ) ) {
			Helpers::bypass_reason_header( 'Sitemap' );
			return true;
		}

		if ( $settings->get( Constants::SETTING_BYPASS_ROBOTS_TXT, 0 ) > 0 && preg_match( '/^\/robots.txt/', $_SERVER['REQUEST_URI'] ) ) {
			Helpers::bypass_reason_header( 'robots.txt' );
			return true;
		}

		$excluded_urls = $settings->get( Constants::SETTING_EXCLUDED_URLS, [] );
		if ( is_array( $excluded_urls ) && count( $excluded_urls ) > 0 ) {
			$current_url = $_SERVER['REQUEST_URI'];

			if ( isset( $_SERVER['QUERY_STRING'] ) && strlen( $_SERVER['QUERY_STRING'] ) > 0 ) {
				$current_url .= "?{$_SERVER['QUERY_STRING']}";
			}

			foreach ( $excluded_urls as $url_to_exclude ) {
				if ( Helpers::wildcard_match( $url_to_exclude, $current_url ) ) {
					Helpers::bypass_reason_header( sprintf( 'Excluded URL - %s', $url_to_exclude ) );
					return true;
				}
			}
		}

		$cache_buster = SWCFPC_CACHE_BUSTER;
		if ( isset( $_GET[ $cache_buster ] ) ) {
			Helpers::bypass_reason_header( sprintf( 'Cache Buster (%s)', $cache_buster ) );
			return true;
		}

		if ( ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) || $settings->get( Constants::SETTING_BYPASS_AJAX ) > 0 ) {
			if ( ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) || wp_doing_ajax() ) {
				Helpers::bypass_reason_header( 'AJAX Request' );
				return true;
			}
		}

		if ( wp_doing_cron() ) {
			return true;
		}

		if ( in_array( $GLOBALS['pagenow'] ?? '', [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			Helpers::bypass_reason_header( 'Login/Register' );
			return true;
		}

		return false;
	}

	/**
	 * Decide whether to bypass cache based on conditional-tag signals (page type, post meta,
	 * user state, third-party plugin pages).
	 *
	 * Must be called after `template_redirect` so that conditional tags (`is_cart()`, etc.) work.
	 *
	 * @return bool
	 */
	public static function can_i_bypass_cache() {
		global $post;

		$queried_post = null;

		if ( is_singular() ) {
			$queried_object = get_queried_object();

			if ( $queried_object instanceof \WP_Post ) {
				$queried_post = $queried_object;
			} elseif ( $post instanceof \WP_Post ) {
				$queried_post = $post;
			}
		}

		$settings = Settings_Store::get_instance();

		if ( has_filter( 'swcfpc_cache_bypass' ) && apply_filters( 'swcfpc_cache_bypass', false ) === true ) {
			Helpers::bypass_reason_header( 'swcfpc_cache_bypass filter' );
			return true;
		}

		if ( $queried_post instanceof \WP_Post && post_password_required( $queried_post->ID ) !== false ) {
			Helpers::bypass_reason_header( 'Password Protected' );
			return true;
		}

		if ( $queried_post instanceof \WP_Post && (int) get_post_meta( $post->ID, 'swcfpc_bypass_cache', true ) > 0 ) {
			Helpers::bypass_reason_header( 'Single Post Metabox' );
			return true;
		}

		if ( $settings->get( Constants::SETTING_BYPASS_QUERY_VAR, 0 ) > 0 && isset( $_SERVER['QUERY_STRING'] ) && strlen( $_SERVER['QUERY_STRING'] ) > 0 ) {
			Helpers::bypass_reason_header( 'Bypass Query Var' );
			return true;
		}

		if ( ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) || $settings->get( Constants::SETTING_BYPASS_AJAX, 0 ) > 0 ) {
			if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
				Helpers::bypass_reason_header( 'AJAX Request' );
				return true;
			}

			if ( function_exists( 'is_ajax' ) && \is_ajax() ) {
				Helpers::bypass_reason_header( 'AJAX Request' );
				return true;
			}

			if ( ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) || wp_doing_ajax() ) {
				Helpers::bypass_reason_header( 'AJAX Request' );
				return true;
			}
		}

		$edd_page_checks = [
			[ Third_Party::SETTING_EDD_BYPASS_CHECKOUT_PAGE, 'purchase_page', 'EDD Checkout Page' ],
			[ Third_Party::SETTING_EDD_BYPASS_SUCCESS_PAGE, 'success_page', 'EDD Success Page' ],
			[ Third_Party::SETTING_EDD_BYPASS_FAILURE_PAGE, 'failure_page', 'EDD Failure Page' ],
			[ Third_Party::SETTING_EDD_BYPASS_PURCHASE_HISTORY_PAGE, 'purchase_history_page', 'EDD Purchase History Page' ],
			[ Third_Party::SETTING_EDD_BYPASS_LOGIN_REDIRECT_PAGE, 'login_redirect_page', 'EDD Login Redirect Page' ],
		];

		foreach ( $edd_page_checks as [ $setting, $edd_option, $reason ] ) {
			if ( is_object( $post ) && $settings->get( $setting, 0 ) > 0 && function_exists( 'edd_get_option' ) && \edd_get_option( $edd_option, 0 ) == $post->ID ) {
				Helpers::bypass_reason_header( $reason );
				return true;
			}
		}

		$woo_page_checks = [
			[ Third_Party::SETTING_WOO_BYPASS_CART_PAGE, 'is_cart', 'WooCommerce Cart Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_ACCOUNT_PAGE, 'is_account_page', 'WooCommerce Account Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_CHECKOUT_PAGE, 'is_checkout', 'WooCommerce Checkout Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_CHECKOUT_PAY_PAGE, 'is_checkout_pay_page', 'WooCommerce Checkout Pay Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_SHOP_PAGE, 'is_shop', 'WooCommerce Shop Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_PRODUCT_PAGE, 'is_product', 'WooCommerce Product Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_PRODUCT_CAT_PAGE, 'is_product_category', 'WooCommerce Product Category Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_PRODUCT_TAG_PAGE, 'is_product_tag', 'WooCommerce Product Tag Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_PRODUCT_TAX_PAGE, 'is_product_taxonomy', 'WooCommerce Product Taxonomy Page' ],
			[ Third_Party::SETTING_WOO_BYPASS_PAGES, 'is_woocommerce', 'WooCommerce Pages' ],
		];

		foreach ( $woo_page_checks as [ $setting, $check_fn, $reason ] ) {
			if ( $settings->get( $setting, 0 ) > 0 && function_exists( $check_fn ) && $check_fn() ) {
				Helpers::bypass_reason_header( $reason );
				return true;
			}
		}

		$wp_page_checks = [
			[ Constants::SETTING_BYPASS_FRONT_PAGE, 'is_front_page', 'Front Page' ],
			[ Constants::SETTING_BYPASS_PAGES, 'is_page', 'Page' ],
			[ Constants::SETTING_BYPASS_HOME, 'is_home', 'Home' ],
			[ Constants::SETTING_BYPASS_ARCHIVES, 'is_archive', 'Archives' ],
			[ Constants::SETTING_BYPASS_TAGS, 'is_tag', 'Tag' ],
			[ Constants::SETTING_BYPASS_CATEGORY, 'is_category', 'Category' ],
			[ Constants::SETTING_BYPASS_FEEDS, 'is_feed', 'Feed' ],
			[ Constants::SETTING_BYPASS_SEARCH_PAGES, 'is_search', 'Search' ],
			[ Constants::SETTING_BYPASS_AUTHOR_PAGES, 'is_author', 'Author' ],
			[ Constants::SETTING_BYPASS_SINGLE_POST, 'is_single', 'Single Post' ],
			[ Constants::SETTING_BYPASS_404, 'is_404', '404' ],
		];

		foreach ( $wp_page_checks as [ $setting, $check_fn, $reason ] ) {
			if ( $settings->get( $setting, 0 ) > 0 && $check_fn() ) {
				Helpers::bypass_reason_header( $reason );
				return true;
			}
		}

		if ( is_user_logged_in() ) {
			Helpers::bypass_reason_header( 'Logged In' );
			return true;
		}

		$cache_buster = SWCFPC_CACHE_BUSTER;
		if ( isset( $_GET[ $cache_buster ] ) ) {
			Helpers::bypass_reason_header( sprintf( 'Cache Buster (%s)', $cache_buster ) );
			return true;
		}

		if ( is_admin() ) {
			Helpers::bypass_reason_header( 'Admin' );
			return true;
		}

		if ( $settings->get( Constants::SETTING_FALLBACK_CACHE_HTTP_RESPONSE_CODE ) ) {
			$http_status = http_response_code();

			if ( $http_status !== false && $http_status >= 400 && $http_status < 600 ) {
				Helpers::bypass_reason_header( sprintf( 'HTTP Status %d', $http_status ) );
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the cache buster query arg should be stripped from internal links and redirects.
	 *
	 * @return bool
	 */
	public static function should_remove_cache_buster() {
		return (bool) Settings_Store::get_instance()->get( Constants::SETTING_REMOVE_CACHE_BUSTER, 1 );
	}
}
