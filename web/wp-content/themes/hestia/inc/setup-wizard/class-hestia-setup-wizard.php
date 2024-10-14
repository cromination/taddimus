<?php
/**
 * The class for handle setup wizard stuff.
 *
 * @package hestia
 *
 * @since 3.1
 */

/**
 * Setup wizard main class.
 */
class Hestia_Setup_Wizard {

	/**
	 * Parent menu slug.
	 */
	const PARENT_SLUG = 'themes.php';

	/**
	 * Option name.
	 */
	const OPTION_NAME = 'hestia_wizard_dismissed';

	/**
	 * Fresh site
	 *
	 * @var $is_wizard_dismissed bool
	 */
	private $is_wizard_dismissed = false;

	/**
	 * Post wizard data.
	 *
	 * @var $wizard_data array
	 */
	private $wizard_data = array();

	/**
	 * Constructor.
	 *
	 * @since 3.1
	 *
	 * @access public
	 */
	public function init() {
		add_filter( 'admin_body_class', array( $this, 'add_wizard_classes' ) );
		add_action( 'after_setup_theme', array( $this, 'hestia_after_setup_theme' ) );
		add_action( 'admin_action_hestia_dismiss_wizard', array( $this, 'dismiss_wizard' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), PHP_INT_MAX );
		add_action( 'wp_ajax_hestia_wizard_step_process', array( $this, 'hestia_wizard_step_process' ) );
		add_action( 'admin_footer', array( $this, 'add_inline_style' ) );
		add_action( 'switch_theme', array( $this, 'hestia_handle_switch_theme' ) );

		$this->is_wizard_dismissed = get_option( self::OPTION_NAME, 0 );
	}

	/**
	 * Delete the wizard dismissed flag when the user switch the theme.
	 */
	public function hestia_handle_switch_theme() {
		delete_option( 'hestia_wizard_dismissed' );
	}

	/**
	 * Set wizard dismissed flag.
	 */
	public function hestia_after_setup_theme() {
		global $pagenow;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'themes.php' === $pagenow && isset( $_GET['activated'] ) && ! (bool) get_option( 'fresh_site', false ) ) {
			if ( ! $this->is_wizard_dismissed ) {
				$this->is_wizard_dismissed = update_option( self::OPTION_NAME, false );
				wp_redirect( add_query_arg( 'page', 'hestia-setup-wizard', admin_url( 'admin.php' ) ) );
				exit;
			}
		}
	}

	/**
	 * Registers admin menu.
	 *
	 * @since 3.1
	 *
	 * @access public
	 */
	public function register_admin_menu() {
		if ( ! $this->is_wizard_dismissed ) {
			$hook = add_submenu_page(
				self::PARENT_SLUG,
				__( 'Setup Wizard', 'hestia' ),
				__( 'Setup Wizard', 'hestia' ),
				'manage_options',
				'hestia-setup-wizard',
				array(
					$this,
					'hestia_setup_wizard_page',
				)
			);
			add_action( "load-$hook", array( $this, 'hestia_load_setup_wizard_page' ) );
		}
	}

	/**
	 * Method to register the setup wizard page.
	 *
	 * @access public
	 */
	public function hestia_setup_wizard_page() {
		include __DIR__ . '/template-setup-wizard.php';
	}

	/**
	 * Add classes to make the wizard full screen.
	 *
	 * @param string $classes Body classes.
	 *
	 * @return string
	 */
	public function add_wizard_classes( $classes ) {
		if ( ! $this->is_wizard_dismissed ) {
			$classes .= ' hestia-wizard-fullscreen';
		}

		return trim( $classes );
	}

	/**
	 * Load setup wizard page.
	 *
	 * @access public
	 */
	public function hestia_load_setup_wizard_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'hestia-setup-wizard' === $_GET['page'] ) {
			remove_all_actions( 'admin_notices' );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'hestia_enqueue_setup_wizard_scripts' ) );
	}

	/**
	 * Enqueue setup wizard required scripts.
	 *
	 * @access public
	 */
	public function hestia_enqueue_setup_wizard_scripts() {
		wp_enqueue_media();
		wp_enqueue_style( 'jquery-smart-wizard', get_template_directory_uri() . '/assets/jquery-smartwizard/css/smart_wizard_all' . ( ( HESTIA_DEBUG ) ? '' : '.min' ) . '.css', array(), HESTIA_VERSION );
		wp_enqueue_style( 'hestia-setup-wizard', get_template_directory_uri() . '/assets/css/setup-wizard' . ( ( HESTIA_DEBUG ) ? '' : '.min' ) . '.css', array(), HESTIA_VERSION, 'all' );

		wp_enqueue_script(
			'jquery-smart-wizard',
			get_template_directory_uri() . '/assets/jquery-smartwizard/js/jquery.smartWizard' . ( ( HESTIA_DEBUG ) ? '' : '.min' ) . '.js',
			array(
				'jquery',
				'clipboard',
			),
			HESTIA_VERSION,
			true
		);
		wp_enqueue_script( 'hestia-setup-wizard', get_template_directory_uri() . '/assets/js/setup-wizard.min.js', array( 'jquery' ), HESTIA_VERSION, true );
		wp_localize_script(
			'hestia-setup-wizard',
			'hestiaSetupWizardData',
			array(
				'adminPage'     => add_query_arg( 'page', self::PARENT_SLUG, admin_url( 'admin.php' ) ),
				'ajax'          => array(
					'url'      => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'hestia-setup-wizard' ),
				),
				'errorMessages' => array(
					'requiredEmail' => __( 'This field is required.', 'hestia' ),
					'invalidEmail'  => __( 'Please enter a valid email address.', 'hestia' ),
				),
			)
		);
	}

	/**
	 * Dismiss setup wizard.
	 *
	 * @param bool $redirect_to_dashboard Redirect to dashboard.
	 *
	 * @return bool|void
	 */
	public function dismiss_wizard( $redirect_to_dashboard = true ) {
		// Prevent non-admins from accessing this action. Protect against CSRF.
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( false !== $redirect_to_dashboard ) {
				wp_safe_redirect( admin_url( 'index.php' ) );
				exit;
			}

			return false;
		}

		// Prevent requests without a valid nonce.
		if ( ! isset( $_GET['nonce'] ) || false === wp_verify_nonce( $_GET['nonce'], 'hestia_dismiss_wizard' ) ) {
			if ( false !== $redirect_to_dashboard ) {
				wp_safe_redirect( admin_url( 'index.php' ) );
				exit;
			}

			return false;
		}

		update_option( self::OPTION_NAME, 1 );
		if ( false !== $redirect_to_dashboard ) {
			wp_safe_redirect( admin_url( 'index.php' ) );
			exit;
		}

		return true;
	}

	/**
	 * Setup wizard process.
	 */
	public function hestia_wizard_step_process() {
		check_ajax_referer( 'hestia-setup-wizard', 'security' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$this->wizard_data = ! empty( $_POST['wizard'] ) ? $this->sanitize_wizard_data( $_POST['wizard'] ) : array();
		$action            = ! empty( $_POST['_action'] ) ? sanitize_text_field( $_POST['_action'] ) : '';

		switch ( $action ) {
			case 'hestia_homepage_setting':
				$this->set_homepage_setting();
				break;
			case 'hestia_install_plugins':
				$this->hestia_install_plugins();
				break;
			case 'hestia_newsletter_subscribe':
				$this->hestia_newsletter_subscribe();
				break;
			default:
				wp_send_json(
					array(
						'status'  => 0,
						'message' => __( 'Something went wrong while saving the wizard data.', 'hestia' ),
					)
				);
				break;
		}
	}

	/**
	 * Set homepage settings..
	 *
	 * @return void
	 */
	private function set_homepage_setting() {
		if ( ! isset( $this->wizard_data['show_on_front'] ) ) {
			wp_send_json(
				array(
					'status'  => 0,
					'message' => __( 'Invalid request.', 'hestia' ),
				)
			);
			exit;
		}

		update_option( 'show_on_front', $this->wizard_data['show_on_front'] );

		if ( $this->wizard_data['show_on_front'] !== 'page' ) {
			wp_send_json( array( 'status' => 1 ) );
			exit;
		}

		$post_args = array_merge(
			require HESTIA_PHP_INCLUDE . 'compatibility/starter-content/home.php',
			array(
				'post_status' => 'publish',
			)
		);
		$page_id   = wp_insert_post( $post_args );

		if ( is_wp_error( $page_id ) ) {
			wp_send_json(
				array(
					'status'  => 0,
					'message' => $page_id->get_error_message(),
				)
			);
			exit;
		}

		update_option( 'page_on_front', $page_id );
		wp_send_json( array( 'status' => 1 ) );
		exit;
	}

	/**
	 * Install recommendations plugins.
	 *
	 * @return void
	 */
	private function hestia_install_plugins() {
		if ( ! empty( $this->wizard_data['install_plugin'] ) ) {
			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json(
					array(
						'status'  => 0,
						'message' => __( 'Sorry, you are not allowed to install plugins on this site.', 'hestia' ),
					)
				);
			}
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			foreach ( $this->wizard_data['install_plugin'] as $slug ) {
				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => sanitize_key( wp_unslash( $slug ) ),
						'fields' => array(
							'sections' => false,
						),
					)
				);

				if ( is_wp_error( $api ) ) {
					wp_send_json(
						array(
							'status'  => 0,
							'message' => $api->get_error_message(),
						)
					);
				}

				$skin     = new WP_Ajax_Upgrader_Skin();
				$upgrader = new Plugin_Upgrader( $skin );
				$result   = $upgrader->install( $api->download_link );

				if ( is_wp_error( $result ) ) {
					wp_send_json(
						array(
							'status'  => 0,
							'message' => $api->get_error_message(),
						)
					);
				} elseif ( is_wp_error( $skin->result ) ) {
					if ( 'folder_exists' !== $skin->result->get_error_code() ) {
						wp_send_json(
							array(
								'status'  => 0,
								'message' => $skin->result->get_error_message(),
							)
						);
					}
				} elseif ( $skin->get_errors()->has_errors() ) {
					if ( 'folder_exists' !== $skin->get_errors()->get_error_code() ) {
						wp_send_json(
							array(
								'status'  => 0,
								'message' => $skin->get_errors()->get_error_message(),
							)
						);
					}
				} elseif ( is_null( $result ) ) {
					global $wp_filesystem;
					$status            = array();
					$status['message'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'hestia' );

					// Pass through the error from WP_Filesystem if one was raised.
					if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
						$status['message'] = esc_html( $wp_filesystem->errors->get_error_message() );
					}

					wp_send_json( $status );
				}

				activate_plugin( "$slug/$slug.php" );
				if ( 'optimole-wp' === $slug ) {
					delete_transient( 'optml_fresh_install' );
				}
				if ( 'otter-blocks' === $slug ) {
					update_option( 'themeisle_blocks_settings_redirect', false );
				}
			}
		}
		wp_send_json( array( 'status' => 1 ) );
	}

	/**
	 * Subscribe to newsletter.
	 *
	 * @return void
	 */
	private function hestia_newsletter_subscribe() {
		$email = $this->wizard_data['email'];
		if ( is_email( $email ) ) {
			$request_res = wp_remote_post(
				'https://api.themeisle.com/tracking/subscribe',
				array(
					'timeout' => 100,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Cache-Control' => 'no-cache',
						'Accept'        => 'application/json, */*;q=0.1',
					),
					'body'    => wp_json_encode(
						array(
							'slug'  => 'hestia',
							'site'  => home_url(),
							'email' => $email,
							'data'  => array(
								'segment' => array(),
							),
						)
					),
				)
			);
			if ( ! is_wp_error( $request_res ) ) {
				$body = json_decode( wp_remote_retrieve_body( $request_res ) );
				if ( 'success' === $body->code ) {
					wp_send_json(
						array(
							'status' => 1,
						)
					);
				}
			}
			wp_send_json(
				array(
					'status'  => 0,
					'message' => __( 'Something went wrong please try again.', 'hestia' ),
				)
			);
		} else {
			wp_send_json(
				array(
					'status'  => 0,
					'message' => __( 'Please enter a valid email address.', 'hestia' ),
				)
			);
		}
	}

	/**
	 * Add inline style.
	 */
	public function add_inline_style() {
		if ( ! $this->is_wizard_dismissed ) { ?>
			<style type="text/css">
				#adminmenu a[href$="?page=hestia-setup-wizard"] {
					display: none;
				}
			</style>
			<?php
		}
	}

	/**
	 * Filter postdata.
	 *
	 * @param array $postdata Post data.
	 *
	 * @return array
	 */
	private function sanitize_wizard_data( $postdata ) {
		$postdata = array_map(
			function ( $data ) {
				if ( is_array( $data ) ) {
					return $this->sanitize_wizard_data( $data );
				}
				$data = wp_unslash( $data );
				if ( is_numeric( $data ) ) {
					return (int) $data;
				}

				return sanitize_text_field( $data );
			},
			$postdata
		);

		if ( isset( $postdata['install_plugin'] ) && is_array( $postdata['install_plugin'] ) ) {
			$plugins = array();

			foreach ( $postdata['install_plugin'] as $plugin ) {
				$plugins = array_merge( $plugins, explode( '|', $plugin ) );
			}

			$postdata['install_plugin'] = $plugins;
		}

		return array_filter( $postdata );
	}

	/**
	 * Disallow object clone
	 *
	 * @access public
	 * @return void
	 * @since  3.1
	 */
	public function __clone() {
	}

	/**
	 * Disable un-serializing
	 *
	 * @access public
	 * @return void
	 * @since  3.1
	 */
	public function __wakeup() {
	}
}
