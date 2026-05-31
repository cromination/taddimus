<?php

use SPC\Constants;
use SPC\Modules\Frontend;
use SPC\Modules\Third_Party;
use SPC\Services\Cloudflare_Integration;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;

return new class extends \ThemeisleSDK\Modules\Abstract_Migration {
	public function should_run() {
		$previous = (string) get_option( 'swcfpc_previous_version', '' );

		return '' !== $previous && version_compare( $previous, '5.1.2', '<' );
	}

	public function up() {
		$previous = (string) get_option( 'swcfpc_previous_version', '' );
		$store    = Settings_Store::get_instance();

		if ( version_compare( $previous, '4.5', '<' ) ) {
			Logger::log( 'migration::legacy', 'Applying 4.5 defaults and excluded URLs.' );

			$store->set( Constants::SETTING_FALLBACK_CACHE_PURGE_ON_UPGRADER_COMPLETE, false );
			$store->set( Constants::SETTING_BYPASS_WP_JSON_REST, 0 );
			$store->set( Third_Party::SETTING_WOO_BYPASS_ACCOUNT_PAGE, 1 );
			$store->set( Constants::SETTING_KEEP_ON_DEACTIVATION, 1 );

			$excluded_urls = $store->get( Constants::SETTING_EXCLUDED_URLS );

			if ( is_array( $excluded_urls ) ) {
				foreach ( [ '/my-account*', '/wc-api/*', '/edd-api/*', '/wp-json*' ] as $pattern ) {
					if ( ! in_array( $pattern, $excluded_urls, true ) ) {
						$excluded_urls[] = $pattern;
					}
				}

				$store->set( Constants::SETTING_EXCLUDED_URLS, $excluded_urls );
			}

			$store->save();
		}

		if ( version_compare( $previous, '4.5.6', '<' ) ) {
			Logger::log( 'migration::legacy', 'Removing double serialization for swcfpc_config and swcfpc_fc_ttl_registry.' );

			$serialized_config = get_option( 'swcfpc_config', false );

			if ( is_string( $serialized_config ) && is_serialized( $serialized_config ) ) {
				update_option( 'swcfpc_config', maybe_unserialize( $serialized_config ) );
			}

			$serialized_ttl = get_option( 'swcfpc_fc_ttl_registry', false );

			if ( is_string( $serialized_ttl ) && is_serialized( $serialized_ttl ) ) {
				update_option( 'swcfpc_fc_ttl_registry', serialize( maybe_unserialize( $serialized_ttl ) ) );
			}
		}

		if ( version_compare( $previous, '4.6.1', '<' ) ) {
			Logger::log( 'migration::legacy', 'Scheduling 4.6.1 Cloudflare page cache toggle.' );

			$this->schedule_cloudflare_page_cache_toggle();
		}

		if ( version_compare( $previous, '4.7.3', '<' ) ) {
			Logger::log( 'migration::legacy', 'Scheduling 4.7.3 Cloudflare page cache toggle.' );

			$this->schedule_cloudflare_page_cache_toggle();
		}

		if ( version_compare( $previous, '5.0.5', '<' ) ) {
			Logger::log( 'migration::legacy', 'Updating excluded cookies list (5.0.5).' );

			$new_values = [
				'wordpress',
				'comment_',
				'woocommerce_',
				'xf_',
				'edd_',
				'jetpack',
				'yith_wcwl_session_',
				'yith_wrvp_',
				'wpsc_',
				'ecwid',
				'ec_',
				'bookly',
			];

			$old_setting = $store->get( Constants::SETTING_EXCLUDED_COOKIES, Constants::DEFAULT_EXCLUDED_COOKIES );

			if ( ! is_array( $old_setting ) ) {
				$old_setting = [];
			}

			$old_setting = array_filter(
				$old_setting,
				static function ( $cookie ) use ( $new_values ) {
					return ! in_array( trim( $cookie, '^' ), $new_values, true );
				}
			);

			$store
				->set( Constants::SETTING_EXCLUDED_COOKIES, array_unique( array_merge( $old_setting, $new_values ) ) )
				->save();
		}

		if ( version_compare( $previous, '5.0.6', '<' ) ) {
			Logger::log( 'migration::legacy', 'Pruning legacy "wp-" patterns from excluded cookies (5.0.6).' );

			$cookies = $store->get( Constants::SETTING_EXCLUDED_COOKIES, Constants::DEFAULT_EXCLUDED_COOKIES );

			if ( is_array( $cookies ) ) {
				$cookies = array_filter(
					$cookies,
					static function ( $cookie ) {
						return ! in_array( $cookie, [ 'wp-', '^wp-' ], true );
					}
				);

				$store
					->set( Constants::SETTING_EXCLUDED_COOKIES, $cookies )
					->save();
			}
		}

		if ( version_compare( $previous, '5.1.0', '<' ) ) {
			Logger::log( 'migration::legacy', 'Setting lazy-load behaviour from skip-images flag (5.1.0).' );

			$skip_images = $store->get( Constants::SETTING_LAZY_LOAD_SKIP_IMAGES, 2 );
			$behaviour   = $skip_images > 0 ? Frontend::LAZY_LOAD_BEHAVIOUR_FIXED : Frontend::LAZY_LOAD_BEHAVIOUR_ALL;

			$store->set( Constants::SETTING_LAZY_LOAD_BEHAVIOUR, $behaviour )->save();
		}

		if ( version_compare( $previous, '5.1.2', '<' ) ) {
			if ( ! (bool) $store->get( Constants::ENABLE_CACHE_RULE ) && '' !== (string) $store->get( Constants::RULE_ID_CACHE, '' ) ) {
				Logger::log( 'migration::legacy', 'Enabling cache rule flag for installs with an existing rule ID (5.1.2).' );

				$store->set( Constants::ENABLE_CACHE_RULE, 1 )->save();
			}
		}
	}

	private function schedule_cloudflare_page_cache_toggle(): void {
		add_action(
			'shutdown',
			static function () {
				$cloudflare = new Cloudflare_Integration();
				$error      = '';

				$cloudflare->disable_page_cache( $error );
				$cloudflare->enable_page_cache( $error );
			},
			PHP_INT_MAX
		);
	}
};
