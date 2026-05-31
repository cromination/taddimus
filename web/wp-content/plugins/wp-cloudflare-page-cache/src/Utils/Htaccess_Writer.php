<?php

namespace SPC\Utils;

use SPC\Constants;
use SPC\Services\Settings_Store;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Writes the plugin's block in the site's `.htaccess` (and a parallel `nginx.conf` snippet).
 *
 * Stateless: each call re-resolves the htaccess path via `get_home_path()` and reads the latest
 * settings. Called on demand from Cloudflare connect/disconnect, settings save, and full resets.
 */
class Htaccess_Writer {

	public static function write( string &$error_msg = '' ): bool {
		$settings       = Settings_Store::get_instance();
		$cache_enabled  = $settings->is_cache_enabled();
		$htaccess_path  = self::htaccess_path();
		$htaccess_lines = [];

		if ( $settings->get( Constants::SETTING_OVERWRITE_WITH_HTACCESS, 0 ) > 0 && $cache_enabled ) {
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header unset Pragma "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header always unset Pragma "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header unset Expires "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header always unset Expires "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header unset Cache-Control "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header always unset Cache-Control "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = 'Header always set Cache-Control "expr=%{resp:x-wp-cf-super-cache-cache-control}" "expr=resp(\'x-wp-cf-super-cache-cache-control\') != \'\'"';
			$htaccess_lines[] = '</IfModule>';
		}

		if ( $settings->get( Constants::SETTING_STRIP_RESPONSE_COOKIES, 0 ) > 0 && $cache_enabled ) {
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header unset Set-Cookie "expr=resp(\'x-wp-cf-super-cache-active\') == \'1\'"';
			$htaccess_lines[] = 'Header always unset Set-Cookie "expr=resp(\'x-wp-cf-super-cache-active\') == \'1\'"';
			$htaccess_lines[] = '</IfModule>';
		}

		if ( $settings->get( Constants::SETTING_BYPASS_SITEMAP, 0 ) > 0 && $cache_enabled ) {
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0" "expr=%{CONTENT_TYPE} =~ m#(?:application/xml|text/xsl)#"';
			$htaccess_lines[] = '</IfModule>';
		}

		if ( $settings->get( Constants::SETTING_BYPASS_ROBOTS_TXT, 0 ) > 0 && $cache_enabled ) {
			$htaccess_lines[] = '<FilesMatch "robots\.txt">';
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"';
			$htaccess_lines[] = '</IfModule>';
			$htaccess_lines[] = '</FilesMatch>';
		}

		if ( $settings->get( Constants::SETTING_BROWSER_CACHE_STATIC_ASSETS, 1 ) > 0 && $cache_enabled ) {
			$htaccess_lines[] = '<FilesMatch "\.(css|js|pdf)$">';
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header set Cache-Control "public, must-revalidate, proxy-revalidate, immutable, max-age=2592000, stale-while-revalidate=86400, stale-if-error=604800"';
			$htaccess_lines[] = '</IfModule>';
			$htaccess_lines[] = '</FilesMatch>';

			$htaccess_lines[] = '<FilesMatch "\.(jpg|jpeg|png|gif|ico|eot|swf|svg|webp|avif|ttf|otf|woff|woff2|ogg|mp4|mpeg|avi|mkv|webm|mp3)$">';
			$htaccess_lines[] = '<IfModule mod_headers.c>';
			$htaccess_lines[] = 'Header set Cache-Control "public, must-revalidate, proxy-revalidate, immutable, max-age=31536000, stale-while-revalidate=86400, stale-if-error=604800"';
			$htaccess_lines[] = '</IfModule>';
			$htaccess_lines[] = '</FilesMatch>';
		}

		if ( $cache_enabled ) {
			$htaccess_lines[] = '<IfModule mod_filter.c>';
			$htaccess_lines[] = '<IfModule mod_deflate.c>';
			$htaccess_lines[] = 'AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json application/xml application/rss+xml application/atom+xml image/svg+xml';
			$htaccess_lines[] = '</IfModule>';

			$htaccess_lines[] = '<IfModule mod_brotli.c>';
			$htaccess_lines[] = 'AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json application/xml application/rss+xml application/atom+xml image/svg+xml';
			$htaccess_lines[] = '</IfModule>';
			$htaccess_lines[] = '</IfModule>';
		}

		$log_file_uri = Helpers::get_plugin_content_dir_url() . '/debug.log';

		$htaccess_lines[] = '<IfModule mod_rewrite.c>';
		$htaccess_lines[] = "RewriteCond %{REQUEST_URI} ^(.*)?{$log_file_uri}(.*)$";
		$htaccess_lines[] = 'RewriteRule ^(.*)$ - [F]';
		$htaccess_lines[] = '</IfModule>';

		$htaccess_lines[] = '<FilesMatch "wp-cron.php">';
		$htaccess_lines[] = '<IfModule mod_headers.c>';
		$htaccess_lines[] = 'Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"';
		$htaccess_lines[] = '</IfModule>';
		$htaccess_lines[] = '</FilesMatch>';

		file_put_contents( Helpers::get_plugin_content_dir() . '/nginx.conf', implode( "\n", self::get_nginx_rules() ) );

		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		if ( function_exists( 'insert_with_markers' ) && ! insert_with_markers( $htaccess_path, 'WP Cloudflare Super Page Cache', $htaccess_lines ) ) {
			// translators: %s is the path to the .htaccess file
			$error_msg = sprintf( __( 'The .htaccess file (%s) could not be edited. Check if the file has write permissions.', 'wp-cloudflare-page-cache' ), $htaccess_path );
			return false;
		}

		return true;
	}

	public static function reset(): void {
		if ( function_exists( 'insert_with_markers' ) ) {
			insert_with_markers( self::htaccess_path(), 'WP Cloudflare Super Page Cache', [] );
		}
	}

	/**
	 * @return array<int, string>
	 */
	public static function get_nginx_rules(): array {
		$settings     = Settings_Store::get_instance();
		$log_file_uri = Helpers::get_plugin_content_dir_url() . '/debug.log';
		$nginx_lines  = [];

		if ( $settings->get( Constants::SETTING_BYPASS_SITEMAP, 0 ) > 0 ) {
			$nginx_lines[] = 'location ~* \.(xml|xsl)$ { add_header Cache-Control "no-cache, no-store, must-revalidate, max-age=0"; expires -1; }';
		}

		if ( $settings->get( Constants::SETTING_BYPASS_ROBOTS_TXT, 0 ) > 0 ) {
			$nginx_lines[] = 'location /robots.txt { add_header Cache-Control "no-cache, no-store, must-revalidate, max-age=0"; expires -1; }';
		}

		if ( $settings->get( Constants::SETTING_BROWSER_CACHE_STATIC_ASSETS, 1 ) > 0 ) {
			$nginx_lines[] = 'location ~* \.(css|js|pdf)$ { add_header Cache-Control "public, must-revalidate, proxy-revalidate, immutable, max-age=2592000, stale-while-revalidate=86400, stale-if-error=604800"; expires 30d; }';
			$nginx_lines[] = 'location ~* \.(jpg|jpeg|png|gif|ico|eot|swf|svg|webp|avif|ttf|otf|woff|woff2|ogg|mp4|mpeg|avi|mkv|webm|mp3)$ { add_header Cache-Control "public, must-revalidate, proxy-revalidate, immutable, max-age=31536000, stale-while-revalidate=86400, stale-if-error=604800"; expires 365d; }';

			if ( $settings->get( Constants::SETTING_BYPASS_SITEMAP, 0 ) == 0 ) {
				$nginx_lines[] = 'location ~* \.(xml|xsl)$ { add_header Cache-Control "no-cache, no-store, must-revalidate, max-age=0"; expires -1; }';
			}
		}

		$nginx_lines[] = 'location /wp-cron.php { add_header Cache-Control "no-cache, no-store, must-revalidate, max-age=0"; expires -1; }';
		$nginx_lines[] = "location = {$log_file_uri} { access_log off; deny all; }";

		return $nginx_lines;
	}

	private static function htaccess_path(): string {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return get_home_path() . '.htaccess';
	}
}
