<?php

namespace WebpConverter\Notice;

use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\OptionsAccessManager;

/**
 * Supports notice displayed as information about AVIF support.
 */
class AvifSupportNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION      = 'webpc_notice_avif_support';
	const NOTICE_VIEW_PATH   = 'components/notices/avif-support.php';
	const NOTICE_ACTION_NAME = 'webpc_notice_avif_support';

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct( TokenRepository $token_repository = null ) {
		$this->token_repository = $token_repository ?: new TokenRepository();
	}

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
		return (string) strtotime( sprintf( '+ %s days', rand( 1, 14 ) ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active(): bool {
		if ( $this->token_repository->get_token()->get_valid_status() ) {
			return false;
		}

		$option_value = OptionsAccessManager::get_option( $this->get_option_name() );
		return ( ( $option_value !== null ) && ( $option_value < time() ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_disable_value(): string {
		$is_permanent = ( isset( $_REQUEST['is_permanently'] ) && $_REQUEST['is_permanently'] ); // phpcs:ignore
		return (string) strtotime( ( $is_permanent ) ? '+1 year' : '+ 1 month' );
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
