<?php

namespace WebpConverter\Notice;

use WebpConverter\PluginData;
use WebpConverter\Service\OptionsAccessManager;
use WebpConverter\Settings\Option\AccessTokenOption;
use WebpConverter\Settings\Page\PageIntegration;

/**
 * Supports notice displayed information about the PRO version.
 */
class BlackFridayNotice extends NoticeAbstract implements NoticeInterface {

	const NOTICE_OPTION     = 'webpc_notice_bf2022';
	const NOTICE_VIEW_PATH  = 'components/notices/discount-coupon.php';
	const NOTICE_DATE_START = '2022-11-24';
	const NOTICE_DATE_END   = '2022-11-28';

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
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
		return ( ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' ) // phpcs:ignore WordPress.Security
			|| ( ( $_GET['page'] ?? '' ) === PageIntegration::SETTINGS_MENU_PAGE ) ); // phpcs:ignore WordPress.Security
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_active(): bool {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( $plugin_settings[ AccessTokenOption::OPTION_NAME ] ) {
			return false;
		}

		$option_value = OptionsAccessManager::get_option( $this->get_option_name() );
		if ( $option_value === 'yes' ) {
			return false;
		}

		$current_date = gmdate( 'Ymd' );
		return ( ( $current_date >= str_replace( '-', '', self::NOTICE_DATE_START ) )
			&& ( $current_date <= str_replace( '-', '', self::NOTICE_DATE_END ) ) );
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
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'close_action'   => self::NOTICE_OPTION,
			'coupon_code'    => 'BF2022',
			'discount_value' => '50%',
			'button_url'     => 'https://url.mattplugins.com/converter-notice-bf2022-button-read',
			'promotion_date' => self::NOTICE_DATE_END,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ajax_action_to_disable(): string {
		return self::NOTICE_OPTION;
	}
}
