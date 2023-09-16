<?php

namespace WebpConverter\Notice;

use WebpConverter\PluginData;
use WebpConverter\Repository\TokenRepository;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\Page\PageIntegrator;

/**
 * Supports notice displayed information about invalid Access Token.
 */
class TokenInactiveNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION    = 'webpc_notice_token_invalid';
	const NOTICE_VIEW_PATH = 'components/notices/token-invalid.php';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var TokenRepository
	 */
	private $token_repository;

	public function __construct( PluginData $plugin_data, TokenRepository $token_repository ) {
		$this->plugin_data      = $plugin_data;
		$this->token_repository = $token_repository;
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
	public function is_available(): bool {
		return ( ! in_array(
			( $_GET['page'] ?? '' ), // phpcs:ignore WordPress.Security
			[ PageIntegrator::SETTINGS_MENU_PAGE, PageIntegrator::UPLOAD_MENU_PAGE ]
		) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active(): bool {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( ! $plugin_settings[ AccessTokenOption::OPTION_NAME ] ) {
			return false;
		} elseif ( $this->token_repository->get_token()->is_active() ) {
			return false;
		}

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
	 *
	 * @return mixed[]
	 */
	public function get_vars_for_view(): array {
		return [
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'close_action' => self::NOTICE_OPTION,
			'settings_url' => PageIntegrator::get_settings_page_url(),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ajax_action_to_disable(): string {
		return self::NOTICE_OPTION;
	}
}
