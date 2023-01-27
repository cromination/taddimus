<?php

namespace WebpConverter\Notice;

use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Page\PageIntegration;

/**
 * Supports notice asking to clear CDN cache for Cloudflare.
 */
class LitespeedNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION    = 'webpc_notice_litespeed';
	const NOTICE_VIEW_PATH = 'components/notices/clear-cache.php';

	/**
	 * {@inheritdoc}
	 */
	public function get_option_name(): string {
		return self::NOTICE_OPTION;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return ( ( $_GET['page'] ?? '' ) === PageIntegration::SETTINGS_MENU_PAGE ); // phpcs:ignore WordPress.Security
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active(): bool {
		$option_value = OptionsAccessManager::get_option( $this->get_option_name() );
		return ( $option_value !== 'yes' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_disable_value(): string {
		return 'yes';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_output_path(): string {
		return self::NOTICE_VIEW_PATH;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_vars_for_view() {
		$ls_api_key            = ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) )
			? ( ( is_multisite() ) ? get_site_option( 'litespeed.conf.api_key', '' ) : get_option( 'litespeed.conf.api_key', '' ) )
			: '';
		$ls_image_optimization = ( $ls_api_key !== '' ) && ( ( is_multisite() ) ? get_site_option( 'litespeed.conf.img_optm-ori' ) : get_option( 'litespeed.conf.img_optm-ori' ) );
		$ls_webp_replacement   = ( $ls_api_key !== '' ) && ( ( is_multisite() ) ? get_site_option( 'litespeed.conf.img_optm-webp' ) : get_option( 'litespeed.conf.img_optm-webp' ) );

		$steps = [];

		if ( $ls_image_optimization || $ls_webp_replacement ) {
			$steps[] = sprintf(
			/* translators: %1$s: settings page, %2$s: plugin name */
				__( 'Find the %1$s settings for the %2$s plugin.', 'webp-converter-for-media' ),
				'<strong>"Image Optimization"</strong>',
				'<strong>"LiteSpeed Cache"</strong>'
			);
		}
		if ( $ls_image_optimization ) {
			$steps[] = sprintf(
			/* translators: %1$s: option label */
				__( 'Disable the %1$s option.', 'webp-converter-for-media' ),
				'<strong>"Optimize Original Images"</strong>'
			);
		}
		if ( $ls_webp_replacement ) {
			$steps[] = sprintf(
			/* translators: %1$s: option label */
				__( 'Disable the %1$s option.', 'webp-converter-for-media' ),
				'<strong>"Image WebP Replacement"</strong>'
			);
		}
		if ( $ls_image_optimization || $ls_webp_replacement ) {
			$steps[] = sprintf(
			/* translators: %1$s: button label */
				__( 'Click %1$s.', 'webp-converter-for-media' ),
				'<strong>"Save Changes"</strong>'
			);
			$steps[] = sprintf(
			/* translators: %1$s: button label */
				__( 'Look for the %1$s icon in the top admin bar.', 'webp-converter-for-media' ),
				'<strong>"LiteSpeed Cache Purge All - LSCache"</strong>'
			);
			$steps[] = sprintf(
			/* translators: %1$s: button label */
				__( 'Click %1$s.', 'webp-converter-for-media' ),
				'<strong>"Purge All"</strong>'
			);
		}

		if ( ! $steps && ( strpos( strtolower( $_SERVER['SERVER_SOFTWARE'] ?? '' ), 'litespeed' ) !== false ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$steps[] = __( 'Log in to the management panel of your hosting.', 'webp-converter-for-media' );
			$steps[] = sprintf(
			/* translators: %1$s: option name */
				__( 'Find the %1$s section.', 'webp-converter-for-media' ),
				'<strong>"LiteSpeed Web Cache Manager"</strong>'
			);
			$steps[] = sprintf(
			/* translators: %1$s: button label */
				__( 'Click %1$s.', 'webp-converter-for-media' ),
				'<strong>"Flush All"</strong>'
			);
		}

		if ( ! $steps ) {
			return null;
		}

		return [
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'close_action' => self::NOTICE_OPTION,
			'service_name' => 'LiteSpeed Cache',
			'steps'        => $steps,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ajax_action_to_disable(): string {
		return self::NOTICE_OPTION;
	}
}
