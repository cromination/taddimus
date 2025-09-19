<?php
/**
 * Handles the dashboard UI.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Dashboard
 *
 * @package Hestia
 */
class Hestia_Dashboard {

	/**
	 * About Page instance
	 *
	 * @var Hestia_Dashboard|null
	 */
	public static $instance;

	/**
	 * About page content that should be rendered.
	 *
	 * @var array<string, mixed>
	 */
	public $config = array();

	/**
	 * Current theme args
	 *
	 * @var array<string, mixed>
	 */
	private $theme_args = array();

	/**
	 * Initialize the module.
	 *
	 * @param array<string, mixed> $config The configuration array.
	 *
	 * @return void
	 */
	public static function init( $config ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Hestia_Dashboard ) ) {
			$instance = new Hestia_Dashboard();
			if ( ! empty( $config ) && is_array( $config ) ) {
				$instance->config = apply_filters( 'ti_about_config_filter', $config );
				$instance->setup_config();
				$instance->setup_actions();
				$instance->set_recommended_plugins_visibility();

			}
			self::$instance = $instance;
		}
	}

	/**
	 * Set up the class props based on current theme
	 *
	 * @retun void
	 */
	private function setup_config() {

		$theme = wp_get_theme();

		$this->theme_args['name']        = apply_filters( 'ti_wl_theme_name', $theme->__get( 'Name' ) );
		$this->theme_args['template']    = $theme->get( 'Template' );
		$this->theme_args['version']     = $theme->__get( 'Version' );
		$this->theme_args['description'] = apply_filters( 'ti_wl_theme_description', $theme->__get( 'Description' ) );
		$this->theme_args['slug']        = $theme->__get( 'stylesheet' );
	}

	/**
	 * Set up the actions used for this page.
	 *
	 * @return void
	 */
	public function setup_actions() {

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action(
			'wp_ajax_update_recommended_plugins_visibility',
			array(
				$this,
				'update_recommended_plugins_visibility',
			)
		);
	}

	/**
	 * Set an option with recommended plugins slugs and visibility
	 * based on visibility flag the plugin should be shown/hidden in recommended_plugins tab.
	 *
	 * @return void
	 */
	public function set_recommended_plugins_visibility() {
		$recommended_plugins = get_theme_mod( 'ti_about_recommended_plugins' );
		if ( ! empty( $recommended_plugins ) ) {
			return;
		}
		$required_plugins           = $this->get_recommended_plugins();
		$required_plugins_visbility = array();
		foreach ( $required_plugins as $slug => $req_plugin ) {
			$required_plugins_visbility[ $slug ] = 'visible';
		}
		set_theme_mod( 'ti_about_recommended_plugins', $required_plugins_visbility );
	}

	/**
	 * Get the list of recommended plugins.
	 *
	 * @return array<string,mixed> - Either recommended plugins or empty array.
	 */
	public function get_recommended_plugins() {
		foreach ( $this->config as $index => $content ) {
			if ( isset( $content['type'] ) && 'recommended_actions' === $content['type'] ) {
				return $content['plugins'];
			}
		}

		return array();
	}

	/**
	 * Register the menu page under Appearance menu.
	 *
	 * @return void
	 */
	public function register() {
		$theme = $this->theme_args;

		if ( empty( $theme['name'] ) || empty( $theme['slug'] ) ) {
			return;
		}

		$page_title = $theme['name'] . ' ' . __( 'Options', 'hestia' ) . ' ';

		$menu_name        = $theme['name'] . ' ' . __( 'Options', 'hestia' ) . ' ';
		$required_actions = $this->get_recommended_actions_left();
		if ( $required_actions > 0 ) {
			$menu_name .= '<span class="badge-action-count update-plugins">' . esc_html( $required_actions ) . '</span>';
		}

		$theme_page = ! empty( $theme['template'] ) ? $theme['template'] . '-welcome' : $theme['slug'] . '-welcome';
		add_theme_page(
			$page_title,
			$menu_name,
			'activate_plugins',
			$theme_page,
			array(
				$this,
				'render',
			)
		);
	}

	/**
	 * Utility function for checking the number of recommended actions uncompleted
	 *
	 * @return int $actions_left - the number of uncompleted recommended actions.
	 */
	public function get_recommended_actions_left() {
		$actions_left        = 0;
		$recommended_plugins = get_theme_mod( 'ti_about_recommended_plugins' );

		if ( empty( $recommended_plugins ) ) {
			return $actions_left;
		}

		foreach ( $recommended_plugins as $slug => $visibility ) {
			if (
				'visible' !== $visibility ||
				'deactivate' === Hestia_Dashboard_Plugin_Helper::instance()->check_plugin_state( $slug )
			) {
				continue;
			}
			$actions_left += 1;
		}

		return $actions_left;
	}

	/**
	 * Instantiate the render class which will render all the tabs based on config.
	 *
	 * @return void
	 */
	public function render() {
		new Hestia_Dashboard_Render( $this->theme_args, $this->config, $this );
	}

	/**
	 * Load css and scripts for the about page.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) ) {
			return;
		}
		$theme      = $this->theme_args;
		$theme_page = ! empty( $theme['template'] ) ? $theme['template'] . '-welcome' : $theme['slug'] . '-welcome';
		if ( $screen->id !== 'appearance_page_' . $theme_page ) {
			return;
		}

		$this->load_page_deps();
	}

	/**
	 * Load the scripts and styles for the page.
	 *
	 * @return void
	 */
	public function load_page_deps() {
		wp_enqueue_style( 'ti-about-style', get_template_directory_uri() . '/assets/css/dashboard.css', array(), HESTIA_VERSION );
		wp_register_script(
			'ti-about-scripts',
			get_template_directory_uri() . '/assets/js/dashboard.min.js',
			array(
				'jquery',
			),
			HESTIA_VERSION,
			true
		);

		wp_localize_script(
			'ti-about-scripts',
			'tiAboutPageObject',
			array(
				'nr_actions_required' => $this->get_recommended_actions_left(),
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'ti-about-nonce' ),
				'template_directory'  => get_template_directory_uri(),
				'activating_string'   => esc_html__( 'Activating', 'hestia' ),
			)
		);

		wp_enqueue_script( 'ti-about-scripts' );
		Hestia_Dashboard_Plugin_Helper::instance()->enqueue_scripts();
	}

	/**
	 * Update recommended plugins visibility flag if the user dismiss one of them.
	 *
	 * @return void
	 */
	public function update_recommended_plugins_visibility() {
		if (
			! isset( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ti-about-nonce' ) ||
			! isset( $_POST['slug'] )
		) {
			return;
		}
		$recommended_plugins = get_theme_mod( 'ti_about_recommended_plugins' );

		$plugin_to_update                         = sanitize_text_field( wp_unslash( $_POST['slug'] ) );
		$recommended_plugins[ $plugin_to_update ] = 'hidden';

		set_theme_mod( 'ti_about_recommended_plugins', $recommended_plugins );

		$required_actions_left = array( 'required_actions' => $this->get_recommended_actions_left() );
		wp_send_json( $required_actions_left );
	}
}
