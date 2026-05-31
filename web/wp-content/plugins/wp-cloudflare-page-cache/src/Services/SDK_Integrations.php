<?php

namespace SPC\Services;

use SPC\Modules\Dashboard;
use SPC\Utils\Assets_Handler;

class SDK_Integrations {
	/**
	 * Get the survey metadata.
	 *
	 * @param array $data The data for survey in Formbrick format.
	 * @param string $page_slug The slug of the page.
	 *
	 * @return array The survey metadata.
	 */
	public function get_survey_metadata( $data, $page_slug ) {
		$free_slug_key = 'wp_cloudflare_page_cache';
		$current_time  = time();
		$install_date  = (int) get_option( $this->get_product_key() . '_install', $current_time );

		if ( defined( 'SPC_PRO_PATH' ) ) {
			$install_date = min( $install_date, (int) get_option( $free_slug_key . '_install', $current_time ) );
		}

		$install_days_number = intval( ( $current_time - $install_date ) / DAY_IN_SECONDS );

		$plugin_data    = get_plugin_data( SWCFPC_BASEFILE, false, false );
		$plugin_version = '';

		if ( ! empty( $plugin_data['Version'] ) ) {
			$plugin_version = $plugin_data['Version'];
		}

		$data = [
			'environmentId' => 'clt8lntxw0zbu5zwkn3q2ybkq',
			'attributes'    => [
				'plugin_version'      => $plugin_version,
				'install_days_number' => $install_days_number,
				'license_status'      => apply_filters( 'product_spc_license_status', 'invalid' ),
				'plan'                => apply_filters( 'product_spc_license_plan', 0 ),
			],
		];

		$license = apply_filters( 'product_spc_license_key', false );
		if ( ! empty( $license ) ) {
			$data['attributes']['license_key'] = apply_filters( 'themeisle_sdk_secret_masking', $license );
		}

		return $data;
	}

	/**
	 * Set the black friday data.
	 *
	 * @param array $configs The configuration array for the loaded products.
	 * @return array
	 */
	public function add_black_friday_data( $configs ) {
		$config = $configs['default'];

		$message   = __( 'Advanced caching rules, unused CSS removal, Cloudflare edge caching. The performance your site needs without the complexity. Exclusively for existing SPC users.', 'wp-cloudflare-page-cache' );
		$cta_label = __( 'Get SPC Pro', 'wp-cloudflare-page-cache' );

		$plan    = apply_filters( 'product_spc_license_plan', 0 );
		$license = apply_filters( 'product_spc_license_key', false );
		$status  = apply_filters( 'product_spc_license_status', false );

		$is_pro     = 'valid' === $status;
		$is_expired = 'expired' === $status || 'active-expired' === $status;

		if ( $is_pro ) {
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - up to %s off', 'wp-cloudflare-page-cache' ), '30%' );
			// translators: %1$s - discount, %2$s - discount.
			$message   = sprintf( __( 'Upgrade your SPC Pro plan: %1$s off this week. Already on the plan you need? Renew early and save up to %2$s.', 'wp-cloudflare-page-cache' ), '30%', '20%' );
			$cta_label = __( 'See your options', 'wp-cloudflare-page-cache' );
		} elseif ( $is_expired ) {
			// translators: %s is the discount percentage.
			$config['upgrade_menu_text'] = sprintf( __( 'BF Sale - %s off', 'wp-cloudflare-page-cache' ), '50%' );
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - %s off', 'wp-cloudflare-page-cache' ), '50%' );
			$message                       = __( 'Your SPC Pro features are still here, just locked. Renew at a reduced rate this week.', 'wp-cloudflare-page-cache' );
			$cta_label                     = __( 'Reactivate now', 'wp-cloudflare-page-cache' );
		} else {
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - %s off', 'wp-cloudflare-page-cache' ), '60%' );
			$config['title']               = __( 'Visualizer Pro: 60% off this week', 'wp-cloudflare-page-cache' );
			// translators: %s is the discount percentage.
			$config['upgrade_menu_text'] = sprintf( __( 'BF Sale - %s off', 'wp-cloudflare-page-cache' ), '60%' );
		}

		$url_params = array(
			'utm_term' => $is_pro ? 'plan-' . $plan : 'free',
			'lkey'     => ! empty( $license ) ? $license : false,
			'expired'  => $is_expired ? '1' : false,
		);

		$config['cta_label'] = $cta_label;
		$config['message']   = $message;
		$config['sale_url']  = add_query_arg(
			$url_params,
			tsdk_translate_link( tsdk_utmify( 'https://themeisle.link/spc-bf', 'bfcm', 'spc' ) )
		);

		$configs[ SWCFPC_PRODUCT_SLUG ] = $config;

		return $configs;
	}

	/**
	 * Get the product key based on the file path.
	 *
	 * @return string - The product key.
	 */
	public function get_product_key() {
		return str_replace( '-', '_', strtolower( trim( SWCFPC_PRODUCT_SLUG ) ) );
	}

	/**
	 * Get the license option key.
	 *
	 * @return string
	 */
	public function get_license_option_key() {
		return $this->get_product_key() . '_license_data';
	}

	/**
	 * Get the about us metadata.
	 *
	 * @return array
	 */
	public function get_about_us_metadata() {
		return [
			'location'         => Dashboard::PAGE_SLUG,
			'logo'             => Assets_Handler::get_image_url( 'logo.svg' ),
			'has_upgrade_menu' => ! defined( 'SPC_PRO_PATH' ),
			'upgrade_link'     => tsdk_translate_link( tsdk_utmify( esc_url( 'https://themeisle.com/plugins/super-page-cache-pro/upgrade/' ), 'aboutfilter', 'spc' ), 'query' ),
			'upgrade_text'     => __( 'Upgrade to PRO', 'wp-cloudflare-page-cache' ),
		];
	}
	/**
	 * Get the logger data.
	 *
	 * @param array $data The existing logger data.
	 *
	 * @return array The logger data.
	 */
	public function get_logger_data( $data ) {
		return Settings_Store::get_instance()->get_config_for_export();
	}
}
