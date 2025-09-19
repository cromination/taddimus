<?php
/**
 * Welcome notices manager
 *
 * @package Hestia
 */

/**
 * Class Hestia_Welcome_Notice_Manager
 */
class Hestia_Welcome_Notice_Manager {

	/**
	 * How long the notice will show since the user sees it.
	 *
	 * @var string Dismiss option key.
	 */
	const EXPIRATION = WEEK_IN_SECONDS;

	/**
	 * Singleton object.
	 *
	 * @var Hestia_Welcome_Notice_Manager|null Instance object.
	 */
	protected static $instance = null;

	/**
	 * Dismiss option key.
	 *
	 * @var string Dismiss option key.
	 */
	protected static $dismiss_key = 'ti_about_welcome_notice';

	/**
	 * Notice data
	 *
	 * @var array<string, mixed>
	 */
	private $notice_data = array();

	/**
	 * Init the OrbitFox instance.
	 *
	 * @return Hestia_Welcome_Notice_Manager|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Drop-in actions
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ), defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : 99999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notice_scripts' ) );
		add_action( 'wp_ajax_ti_about_dismiss_welcome_notice', array( $this, 'remove_notice' ) );
	}

	/**
	 * Welcome notice scripts
	 */
	public function enqueue_notice_scripts() {

		if ( get_option( self::$dismiss_key ) === 'yes' ) {
			return;
		}

		wp_enqueue_script(
			'hestia-notice-manager-scripts',
			get_template_directory_uri() . '/assets/js/admin/welcome-notice.js',
			array(),
			HESTIA_VERSION,
			true
		);

		wp_localize_script(
			'hestia-notice-manager-scripts',
			'hestiaWelcomeNotice',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'dismissNonce' => wp_create_nonce( 'remove_notice_confirmation' ),
				'dismissKey'   => self::$dismiss_key,
			)
		);
	}

	/**
	 * Set the notice data.
	 *
	 * @param array<string, mixed> $data The data.
	 * @return Hestia_Welcome_Notice_Manager
	 */
	public function set_notice_data( $data ) {
		$default = array(
			'type'           => 'default',
			'dismiss_option' => 'ti_about_welcome_notice',
			'notice_class'   => '',
		);

		$notice_data = wp_parse_args( $data, $default );
		if ( array_key_exists( 'dismiss_option', $notice_data ) ) {
			self::$dismiss_key = $notice_data['dismiss_option'];
		}
		$this->notice_data = wp_parse_args( $data, $default );

		return $this;
	}

	/**
	 * Get the theme install time.
	 *
	 * @return mixed
	 */
	private function get_theme_activated_time() {
		$directory_name = basename( get_template_directory() );
		$option_name    = str_replace( '-', '_', strtolower( trim( $directory_name ) ) ) . '_install';
		return get_option( $option_name );
	}

	/**
	 * Add notice.
	 *
	 * @return void
	 */
	public function admin_notice() {
		if ( ! isset( $this->notice_data['render_callback'] ) ) {
			return;
		}

		$current_screen = get_current_screen();
		if (
			( $current_screen->id !== 'dashboard' && $current_screen->id !== 'themes' ) ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( is_network_admin() ) ||
			( ! current_user_can( 'manage_options' ) ) ||
			( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() )
		) {
			return;
		}

		/**
		 * Backwards compatibility.
		 */
		global $current_user;
		$user_id          = $current_user->ID;
		$dismissed_notice = get_user_meta( $user_id, self::$dismiss_key, true );

		if ( $dismissed_notice === 'dismissed' ) {
			update_option( self::$dismiss_key, 'yes' );
		}

		if ( get_option( self::$dismiss_key, 'no' ) === 'yes' ) {
			return;
		}

		// Let's dismiss the notice if the user sees it for more than 1 week.
		$activated_time = $this->get_theme_activated_time();
		if ( ! empty( $activated_time ) && time() - intval( $activated_time ) > self::EXPIRATION ) {
			update_option( self::$dismiss_key, 'yes' );
			return;
		}

		$style = '
			.ti-about-notice{
				position: relative;
			}
			
			.ti-about-notice .notice-dismiss{
				position: absolute;
				z-index: 10;
			    top: 10px;
			    right: 10px;
			    padding: 10px 15px 10px 21px;
			    font-size: 13px;
			    line-height: 1.23076923;
			    text-decoration: none;
			}
			
			.ti-about-notice .notice-dismiss:before{
			    position: absolute;
			    top: 8px;
			    left: 0;
			    transition: all .1s ease-in-out;
			    background: none;
			}
			
			.ti-about-notice .notice-dismiss:hover{
				color: #00a0d2;
			}
		';

		echo '<style>' . $style . '</style>';
		echo '<div class="' . esc_attr( $this->notice_data['notice_class'] ) . ' notice ti-about-notice">';

		echo '<div class="notice-dismiss"></div>';
		call_user_func( $this->notice_data['render_callback'] );
		echo '</div>';
	}

	/**
	 * Remove notice.
	 *
	 * @return void
	 */
	public function remove_notice() {

		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['nonce'], 'remove_notice_confirmation' ) ) {
			return;
		}

		update_option( self::$dismiss_key, 'yes' );
		wp_die();
	}

	/**
	 * Deny clone.
	 *
	 * @return void
	 */
	public function __clone() {
	}

	/**
	 * Deny un-serialize.
	 *
	 * @return void
	 */
	public function __wakeup() {
	}
}
