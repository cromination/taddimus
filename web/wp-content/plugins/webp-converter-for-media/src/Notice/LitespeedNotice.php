<?php

namespace WebpConverter\Notice;

use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Page\PageIntegration;

/**
 * Supports notice asking to clear CDN cache for Cloudflare.
 */
class LitespeedNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION      = 'webpc_notice_litespeed';
	const NOTICE_VIEW_PATH   = 'components/notices/litespeed.php';
	const NOTICE_ACTION_NAME = 'webpc_notice_cloudflare';

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
		if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ) {
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
			'close_action' => self::NOTICE_ACTION_NAME,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ajax_action_to_disable(): string {
		return self::NOTICE_ACTION_NAME;
	}
}
