<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Helpers;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class Preloader_Process implements Module_Interface {
	private const LOCK_OPTION          = 'swcfpc_preloader_lock';
	private const LOCK_TIMEOUT_MINUTES = 15;

	public function init(): void {
		add_action( 'spc_preloader_job', array( $this, 'preloader_jobs' ) );
		add_action( 'spc_preloader_completed', array( $this, 'preloader_completed' ) );
	}

	public static function can_start(): bool {
		$lock = (int) get_option( self::LOCK_OPTION, 0 );

		return $lock === 0 || ( ( time() - $lock ) / 60 ) > self::LOCK_TIMEOUT_MINUTES;
	}

	public static function lock(): void {
		update_option( self::LOCK_OPTION, time() );
	}

	public static function unlock(): void {
		update_option( self::LOCK_OPTION, 0 );
	}

	/**
	 * @param array<int, string> $urls
	 */
	public static function enqueue_urls( array $urls ): void {
		if ( ! self::can_start() ) {
			Logger::log( 'preloader::enqueue_urls', 'Unable to start the preloader. Another preloading process is currently running.' );

			return;
		}

		self::lock();

		$count = count( $urls );
		Logger::log( 'preloader::enqueue_urls', "Adding {$count} URLs to preloader queue" );
		Logger::log( 'preloader::enqueue_urls', 'Adding these URLs to preloader queue: ' . print_r( $urls, true ), true );

		foreach ( $urls as $url ) {
			as_enqueue_async_action( 'spc_preloader_job', array( 'url' => $url ), Constants::ACTION_SCHEDULER_GROUP );
		}

		as_schedule_single_action( time() + 60, 'spc_preloader_completed', array(), Constants::ACTION_SCHEDULER_GROUP );
	}

	/**
	 * @param string $item
	 */
	public function preloader_jobs( $item ): void {
		if ( empty( $item ) ) {
			Logger::log( 'preloader::preloader_jobs', 'Unable to find a valid URL to preload. Exit.' );

			return;
		}

		Logger::log( 'preloader::preloader_jobs', 'Preloading URL ' . esc_url_raw( $item ) );

		$args = [
			'timeout'    => defined( 'SWCFPC_CURL_TIMEOUT' ) ? SWCFPC_CURL_TIMEOUT : 10,
			'blocking'   => true,
			'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
			'sslverify'  => false,
			'headers'    => [
				'Accept' => 'text/html',
			],
		];

		$response = wp_remote_get( esc_url_raw( $item ), $args );

		$status = wp_remote_retrieve_response_code( $response );

		if ( $status !== 200 ) {
			Logger::log( 'preloader::preloader_jobs', 'Error preloading URL ' . esc_url_raw( $item ) . '. Status: ' . $status );
		} else {
			Logger::log( 'preloader::preloader_jobs', 'URL ' . esc_url_raw( $item ) . ' preloaded successfully.' );
		}

		sleep( 2 );
	}

	public function preloader_completed(): void {
		self::unlock();

		Logger::log( 'preloader::preloader_completed', 'Preloading complete' );
	}

	/**
	 * Enqueue a pre-filtered list of URLs for preloading.
	 *
	 * @param array<int, string> $urls
	 */
	public static function start_for_urls( array $urls ): void {
		$max_urls = defined( 'SWCFPC_PRELOADER_MAX_POST_NUMBER' ) ? SWCFPC_PRELOADER_MAX_POST_NUMBER : 50;

		$urls = array_unique( array_filter( $urls ) );
		$urls = array_values(
			array_filter(
				$urls,
				static function ( $url ) {
					return ! Helpers::is_external_link( $url );
				}
			)
		);
		$urls = array_slice( $urls, 0, $max_urls );

		self::enqueue_urls( $urls );
	}

	/**
	 * Build the site-wide preloader list (nav menus + sitemaps + last-published posts)
	 * and enqueue it.
	 */
	public static function start_for_all_urls(): void {
		$settings = Settings_Store::get_instance();
		$home_url = home_url( '/' );
		$urls     = [];

		// Preload all registered navigation menu locations URLs.
		$wordpress_menus = $settings->get( Constants::SETTING_PRELOADER_NAV_MENUS, [] );
		if ( is_array( $wordpress_menus ) && count( $wordpress_menus ) > 0 ) {
			foreach ( $wordpress_menus as $nav_menu_id ) {
				$single_menu_items = wp_get_nav_menu_items( $nav_menu_id );

				if ( ! $single_menu_items ) {
					continue;
				}

				foreach ( $single_menu_items as $menu_item ) {
					if ( in_array( $menu_item->url, $urls, true ) ) {
						continue;
					}

					if ( $menu_item->url && Helpers::is_external_link( $menu_item->url ) ) {
						continue;
					}

					if ( $menu_item->type === 'post_type' && $menu_item->url && strlen( $menu_item->url ) > 0 ) {
						$scheme = substr( strtolower( $menu_item->url ), 0, 6 );
						if ( $scheme === 'https:' || substr( strtolower( $menu_item->url ), 0, 5 ) === 'http:' ) {
							$urls[] = $menu_item->url;
							continue;
						}
					}

					if ( $menu_item->url && strcasecmp( substr( $menu_item->url, 0, strlen( $home_url ) - 1 ), $home_url ) === 0 ) {
						$urls[] = $menu_item->url;
					}
				}
			}
		}

		// Preload URLs in sitemaps.
		$sitemap_urls = $settings->get( Constants::SETTING_PRELOAD_SITEMAPS_URLS, [] );
		if ( is_array( $sitemap_urls ) && count( $sitemap_urls ) > 0 ) {
			foreach ( $sitemap_urls as $single_sitemap_url ) {
				$single_sitemap_url = home_url( $single_sitemap_url );

				Logger::log( 'preloader::start_for_all_urls', "Preload sitemap {$single_sitemap_url}" );

				$response = wp_remote_post(
					esc_url_raw( $single_sitemap_url ),
					[
						'timeout'    => defined( 'SWCFPC_CURL_TIMEOUT' ) ? SWCFPC_CURL_TIMEOUT : 10,
						'sslverify'  => false,
						'blocking'   => true,
						'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0',
					]
				);

				if ( is_wp_error( $response ) ) {
					// translators: %1$s is the name of the sitemap, %2$s is the error message
					$error = sprintf( __( 'Connection error while retrieving the sitemap %1$s: %2$s', 'wp-cloudflare-page-cache' ), $single_sitemap_url, $response->get_error_message() );
					Logger::log( 'preloader::start_for_all_urls', "Error wp_remote_post: {$error}" );
					continue;
				}

				if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
					if ( (bool) get_option( 'blog_public' ) === false ) {
						Logger::log( 'preloader::start_for_all_urls', "The sitemap at {$single_sitemap_url} is not available, as WordPress only generates sitemaps for public blogs. Sitemap preloading has been skipped." );
						continue;
					}
					Logger::log( 'preloader::start_for_all_urls', "Response code for {$single_sitemap_url} is not 200. Response code: " . wp_remote_retrieve_response_code( $response ) );
					continue;
				}

				$response_body = wp_remote_retrieve_body( $response );

				if ( strlen( $response_body ) === 0 ) {
					Logger::log( 'preloader::start_for_all_urls', "Empty response body for sitemap {$single_sitemap_url}" );
					continue;
				}

				libxml_use_internal_errors( true );

				$xml = simplexml_load_string( $response_body );

				if ( $xml === false ) {
					foreach ( libxml_get_errors() as $single_xml_error ) {
						Logger::log( 'preloader::start_for_all_urls', "Invalid XML for sitemap {$single_sitemap_url}: {$single_xml_error->message}" );
					}

					libxml_clear_errors();
					continue;
				}

				if ( isset( $xml->url ) && ! empty( $xml->url ) ) {
					foreach ( $xml->url as $url_list ) {
						if ( ! isset( $url_list->loc ) || empty( $url_list->loc ) || in_array( $url_list->loc, $urls, true ) || Helpers::is_external_link( (string) $url_list->loc ) ) {
							continue;
						}

						$urls[] = $url_list->loc->__toString();
					}
				}
			}
		}

		// Preload last published posts.
		if ( $settings->get( Constants::SETTING_PRELOAD_LAST_URLS, 0 ) > 0 ) {
			$post_types       = [ 'post', 'page' ];
			$other_post_types = get_post_types(
				[
					'public'             => true,
					'_builtin'           => false,
					'publicly_queryable' => true,
				]
			);

			foreach ( $other_post_types as $single_post_type ) {
				$post_types[] = $single_post_type;
			}

			$post_types = array_diff( $post_types, Constants::PRELOAD_EXCLUDED_POST_TYPES );

			Logger::log( 'preloader::start_for_all_urls', 'Getting last published posts for post types: ' . print_r( $post_types, true ) );

			$all_posts = get_posts(
				[
					'fields'      => 'ids',
					'numberposts' => 20,
					'post_type'   => $post_types,
					'orderby'     => 'date',
					'order'       => 'DESC',
				]
			);

			foreach ( $all_posts as $post ) {
				$permalink = get_permalink( $post );

				if ( $permalink !== false && ! in_array( $permalink, $urls, true ) && strlen( $permalink ) > 0 ) {
					$urls[] = $permalink;
				}
			}
		}

		if ( count( $urls ) === 0 ) {
			Logger::log( 'preloader::start_for_all_urls', 'Nothing to preload' );
			return;
		}

		if ( ! in_array( $home_url, $urls, true ) ) {
			$urls[] = $home_url;
		}

		self::start_for_urls( $urls );
	}
}
