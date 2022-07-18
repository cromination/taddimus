<?php

namespace WebpConverter\Notice;

use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Page\PageIntegration;

/**
 * Supports notice asking to clear Cloudflare cache for Cloudways.
 */
class CloudwaysNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION    = 'webpc_notice_cloudways';
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
	public function get_default_value(): string {
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		$document_root = strtolower( $_SERVER['DOCUMENT_ROOT'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ( strpos( $document_root, 'cloudwaysapps.com' ) !== false ) ) {
			return ( isset( $_GET['page'] ) && ( $_GET['page'] === PageIntegration::ADMIN_MENU_PAGE ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		return false;
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
	public function get_vars_for_view(): array {
		return [
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'close_action' => self::NOTICE_OPTION,
			'service_name' => 'Cloudways',
			'steps'        => [
				sprintf(
				/* translators: %1$s: service name */
					__( 'Log in to your %1$s dashboard.', 'webp-converter-for-media' ),
					'Cloudways'
				),
				sprintf(
				/* translators: %1$s: button label */
					__( 'Click %1$s, find your website on the list and click on it.', 'webp-converter-for-media' ),
					'<strong>"Applications"</strong>'
				),
				sprintf(
				/* translators: %1$s: section name, %2$s: tab name, %3$s: button label */
					__( 'Under %1$s, go to %2$s tab and click %3$s.', 'webp-converter-for-media' ),
					'<strong>"Cloudflare"</strong>',
					'<strong>"Overview"</strong>',
					'<strong>"Purge"</strong>'
				),
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ajax_action_to_disable(): string {
		return self::NOTICE_OPTION;
	}
}
