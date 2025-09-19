<?php
/**
 * The admin class that handles all the dashboard integration.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Admin
 */
class Hestia_Admin {

	/**
	 * Theme name
	 *
	 * @var string $theme_name Theme name.
	 */
	public $theme_name = '';

	/**
	 * Theme slug
	 *
	 * @var string $theme_slug Theme slug.
	 */
	public $theme_slug = '';

	/**
	 * About page config.
	 *
	 * @var array $config About us page config.
	 */
	private $config = array();

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
	}

	/**
	 * Register about us page.
	 */
	public function register_about_page() {
		$theme_args       = wp_get_theme();
		$this->theme_name = apply_filters( 'ti_wl_theme_name', $theme_args->__get( 'Name' ) );
		$this->theme_slug = null !== $theme_args->__get( 'Template' ) ? $theme_args->__get( 'Template' ) : $theme_args->__get( 'stylesheet' );

		if ( class_exists( 'Ti_White_Label_Markup' ) && Ti_White_Label_Markup::is_theme_whitelabeld() ) {
			return;
		}

		add_filter(
			str_replace( '-', '_', $this->theme_slug ) . '_about_us_metadata',
			function () {
				return array(
					'location'         => $this->theme_slug . '-welcome',
					'logo'             => get_template_directory_uri() . '/assets/img/logo.svg',
					'has_upgrade_menu' => ! defined( 'HESTIA_PRO_FLAG' ) || ! hestia_is_license_valid(),
					'upgrade_link'     => tsdk_translate_link( tsdk_utmify( esc_url( 'https://themeisle.com/themes/hestia/upgrade/' ), 'aboutfilter', 'hastiadashboard' ), 'query' ),
					'upgrade_text'     => __( 'Upgrade Now', 'hestia' ),
				);
			}
		);
	}

	/**
	 * Add the about page.
	 */
	public function prepare_ti_about_config() {
		/*
		 * About page instance
		 */
		$this->config = array(
			'footer_messages'     => array(
				'type'     => 'custom',
				'messages' => array(
					array(
						// translators: %s - theme name
						'heading'   => sprintf( __( '%s Community', 'hestia' ), $this->theme_name ),
						// translators: %s - theme name
						'text'      => sprintf( __( 'Join the community of %s users. Get connected, share opinions, ask questions and help each other!', 'hestia' ), $this->theme_name ),
						'link_text' => __( 'Join our Facebook Group', 'hestia' ),
						'link'      => apply_filters( 'ti_wl_agency_url', 'https://www.facebook.com/groups/2024469201114053/' ),
						'blank'     => true,
					),
					array(
						'heading'   => __( 'Leave us a review', 'hestia' ),
						// translators: %s - theme name
						'text'      => sprintf( __( 'Are you are enjoying %s? We would love to hear your feedback.', 'hestia' ), $this->theme_name ),
						'link_text' => __( 'Submit a review', 'hestia' ),
						'link'      => apply_filters( 'ti_wl_agency_url', 'https://wordpress.org/support/theme/hestia/reviews/#new-post' ),
						'blank'     => true,
					),
					array(
						'heading'   => __( 'Contact Support', 'hestia' ),
						// translators: %s - theme name
						'text'      => esc_html__( 'We want to make sure you have the best experience using Hestia, and that is why we have gathered all the necessary information here for you. We hope you will enjoy using Hestia as much as we enjoy creating great products.', 'hestia' ),
						'link_text' => __( 'Contact Support', 'hestia' ),
						'link'      => apply_filters( 'hestia_contact_support_link', 'https://wordpress.org/support/theme/hestia/' ),
						'blank'     => true,
					),
				),
			),
			'getting_started'     => array(
				'type'    => 'columns-2',
				'title'   => __( 'Getting Started', 'hestia' ),
				'video'   => array(
					'url'     => 'https://www.youtube-nocookie.com/embed/bpom4SSyo-8?si=b563iAwrWJTyors-',
					'heading' => esc_html__( 'Get started here', 'hestia' ),
				),
				'content' => array(
					array(
						'title'  => esc_html__( 'Read full documentation', 'hestia' ),
						'text'   => esc_html__( 'Need more details? Please check our full documentation for detailed information on how to use Hestia.', 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'Documentation', 'hestia' ),
							'link'      => 'https://docs.themeisle.com/article/753-hestia-doc?utm_medium=customizer&utm_source=button&utm_campaign=documentation',
							'is_button' => false,
							'blank'     => true,
						),
					),
					array(
						'title'  => esc_html__( 'Go to the Customizer', 'hestia' ),
						'text'   => esc_html__( 'Using the WordPress Customizer you can easily customize every aspect of the theme.', 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'Go to the Customizer', 'hestia' ),
							'link'      => esc_url( admin_url( 'customize.php' ) ),
							'is_button' => true,
							'blank'     => true,
						),
					),
				),
			),
			'recommended_actions' => array(
				'type'    => 'recommended_actions',
				'title'   => __( 'Recommended Actions', 'hestia' ),
				'plugins' => array(
					'themeisle-companion' => array(
						'name'        => 'OrbitFox by ThemeIsle',
						'slug'        => 'themeisle-companion',
						'description' => __( 'It is highly recommended that you install the companion plugin to have access to the Frontpage features, Team and Testimonials sections.', 'hestia' ),
					),
					'otter-blocks'        => array(
						'name'        => 'Otter Blocks by ThemeIsle',
						'slug'        => 'otter-blocks',
						'description' => __( 'Quickly create WordPress pages with 20+ blocks, 100+ ready-to-import designs, and advanced editor extensions.', 'hestia' ),
					),
				),
			),
			'recommended_plugins' => array(
				'type'     => 'plugins',
				'title'    => esc_html__( 'Useful Plugins', 'hestia' ),
				'plugins'  => array(
					'optimole-wp',
					'themeisle-companion',
					'feedzy-rss-feeds',
					'otter-blocks',
					'visualizer',
					'wp-maintenance-mode',
					'wp-cloudflare-page-cache',
					'translatepress-multilingual',
					'multiple-pages-generator-by-porthas',
				),
				'external' => array(
					'wp-landing-kit' => array(
						'banners'           => array( 'low' => trailingslashit( get_template_directory_uri() ) . 'assets/img/wp-landing.jpg' ),
						'name'              => 'WP Landing Kit',
						'short_description' => 'Turn WordPress into a landing page powerhouse with Landing Kit. Map domains to pages or any other published resource.',
						'author'            => 'Themeisle',
						'url'               => 'https://wplandingkit.com/?utm_medium=nevedashboard&utm_source=recommendedplugins&utm_campaign=hestia',
						'premium'           => true,
					),
				),
			),
			'support'             => array(
				'type'    => 'columns-3',
				'title'   => __( 'Documentation', 'hestia' ),
				'content' => array(
					array(
						'icon'   => 'dashicons dashicons-book-alt',
						'title'  => esc_html__( 'Documentation', 'hestia' ),
						'text'   => esc_html__( 'Need more details? Please check our full documentation for detailed information on how to use Hestia.', 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'Read full documentation', 'hestia' ),
							'link'      => 'https://docs.themeisle.com/article/753-hestia-doc?utm_medium=customizer&utm_source=button&utm_campaign=documentation',
							'is_button' => false,
							'blank'     => true,
						),
					),
					array(
						'icon'   => 'dashicons dashicons-admin-customizer',
						'title'  => esc_html__( 'Create a child theme', 'hestia' ),
						'text'   => esc_html__( "If you want to make changes to the theme's files, those changes are likely to be overwritten when you next update the theme. In order to prevent that from happening, you need to create a child theme. For this, please follow the documentation below.", 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'View how to do this', 'hestia' ),
							'link'      => 'https://docs.themeisle.com/article/656-how-to-create-a-child-theme-for-hestia',
							'is_button' => false,
							'blank'     => true,
						),
					),
					array(
						'icon'   => 'dashicons dashicons-controls-skipforward',
						'title'  => esc_html__( 'Speed up your site', 'hestia' ),
						'text'   => esc_html__( 'If you find yourself in a situation where everything on your site is running very slowly, you might consider having a look at the documentation below where you will find the most common issues causing this and possible solutions for each of the issues.', 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'View how to do this', 'hestia' ),
							'link'      => 'http://docs.themeisle.com/article/63-speed-up-your-wordpress-site',
							'is_button' => false,
							'blank'     => true,
						),
					),
					array(
						'icon'   => 'dashicons dashicons-images-alt2',
						'title'  => esc_html__( 'Build a landing page with a drag-and-drop content builder', 'hestia' ),
						'text'   => esc_html__( 'In the documentation below you will find an easy way to build a great looking landing page using a drag-and-drop content builder plugin.', 'hestia' ),
						'button' => array(
							'label'     => esc_html__( 'View how to do this', 'hestia' ),
							'link'      => 'http://docs.themeisle.com/article/219-how-to-build-a-landing-page-with-a-drag-and-drop-content-builder',
							'is_button' => false,
							'blank'     => true,
						),
					),
				),
			),
			'changelog'           => array(
				'type'  => 'changelog',
				'title' => __( 'Changelog', 'hestia' ),
			),
		);

		$has_pro = defined( 'HESTIA_PRO_FLAG' );
		if ( ! $has_pro ) {
			$this->config['custom_tabs'] = array(
				'free_pro' => array(
					'title'           => __( 'Free vs PRO', 'hestia' ),
					'render_callback' => array( $this, 'free_pro_render' ),
				),
			);
		}

		$this->config = apply_filters( 'ti_about_config_filter', $this->config );
	}

	/**
	 * Check if we should show the Welcome notice in Theme page.
	 *
	 * @return void
	 */
	public function should_show_welcome_notice() {
		$has_pro = defined( 'HESTIA_PRO_FLAG' );
		if ( ! $has_pro && ! (bool) get_option( 'fresh_site', false ) ) {
			return;
		}

		Hestia_Welcome_Notice_Manager::instance()->set_notice_data(
			array(
				'type'            => 'custom',
				'notice_class'    => 'ti-welcome-notice notice notice-info',
				'dismiss_option'  => $has_pro ? 'hestia_pro_welcome_notice_dismissed' : 'hestia_notice_dismissed',
				'render_callback' => array( $this, 'welcome_notice_content' ),
			)
		)->init();
	}


	/**
	 * Register admin menu pages.
	 */
	public function register_menu_pages() {
		$theme_page = $this->theme_slug . '-welcome';
		$icon       = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDEwOC44IDEwMi41Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBmaWxsOiAjZmZmOwogICAgICAgIHN0cm9rZS13aWR0aDogMHB4OwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0wLDB2MTAyLjVoMTA4LjhWMEgwWk04OC43LDMyLjloLTI0Ljd2MTMuN2gxNC41djkuNGgtMTQuOXYxNC4xaDI1LjF2MTAuMWgtMzUuNnYtMjQuNWgtMjEuNnYyMy43aC0xMS41VjIyLjNoMTEuNHYyNC4zaDIydi0yNC4zaDM1LjN2MTAuNloiLz4KPC9zdmc+';
		$priority   = apply_filters( 'hestia_menu_priority', 59 );

		if ( class_exists( 'Ti_White_Label_Markup' ) && Ti_White_Label_Markup::is_theme_whitelabeld() ) {
			$icon = 'dashicons-admin-appearance';
		}

		add_menu_page(
			$this->theme_name,
			$this->theme_name,
			'manage_options',
			$theme_page,
			array( $this, 'render_welcome_page' ),
			$icon,
			$priority
		);

		add_submenu_page(
			$theme_page,
			// translators: %s - Theme name
			sprintf( __( '%s Options', 'hestia' ), $this->theme_name ),
			// translators: %s - Theme name
			sprintf( __( '%s Options', 'hestia' ), $this->theme_name ),
			'manage_options',
			$theme_page,
			array( $this, 'render_welcome_page' ),
			0
		);

		$this->copy_customizer_page( $theme_page );
	}

	/**
	 * Copy the customizer page to the dashboard.
	 *
	 * @param string $theme_page The theme page slug.
	 *
	 * @return void
	 */
	private function copy_customizer_page( $theme_page ) {
		global $submenu;
		if ( ! isset( $submenu['themes.php'] ) ) {
			return;
		}
		$themes_menu = $submenu['themes.php'];
		if ( empty( $themes_menu ) ) {
			return;
		}
		$customize_pos = array_search( 'customize', array_column( $themes_menu, 1 ), true );
		if ( false === $customize_pos ) {
			return;
		}
		$themes_page_keys = array_keys( $themes_menu );
		if ( ! isset( $themes_page_keys[ $customize_pos ] ) ) {
			return;
		}

		$customizer_menu_item = array_splice( $themes_menu, $customize_pos, 1 );
		$customizer_menu_item = reset( $customizer_menu_item );
		if ( empty( $customizer_menu_item ) ) {
			return;
		}

		add_submenu_page(
			$theme_page,
			$customizer_menu_item[0],
			$customizer_menu_item[0],
			'manage_options',
			'customize.php',
			'',
			1
		);
	}

	/**
	 * Render the application stub.
	 *
	 * @return void
	 */
	public function render_welcome_page() {
		$theme = wp_get_theme();

		$theme_args['name']        = apply_filters( 'ti_wl_theme_name', $theme->__get( 'Name' ) );
		$theme_args['template']    = $theme->get( 'Template' );
		$theme_args['version']     = $theme->__get( 'Version' );
		$theme_args['description'] = apply_filters( 'ti_wl_theme_description', $theme->__get( 'Description' ) );
		$theme_args['slug']        = $theme->__get( 'stylesheet' );

		new Hestia_Dashboard_Render( $theme_args, $this->config, new Hestia_Dashboard() );
	}

	/**
	 * Welcome notice dismiss.
	 *
	 * @see Hestia_Core::define_hooks()
	 *
	 * @hooked themeisle_ob_after_customizer_import
	 */
	public function dismiss_welcome_notice() {
		// $dismiss_option = defined( 'HESTIA_PRO_FLAG' ) ? 'hestia_pro_welcome_notice_dismissed' : 'hestia_notice_dismissed';

		// update_option( $dismiss_option, 'yes' );
	}


	/**
	 * Free vs Pro tab content
	 */
	public function free_pro_render() {
		$free_pro = array(
			'free_theme_name'     => 'Hestia',
			'pro_theme_name'      => 'Hestia Pro',
			'pro_theme_link'      => apply_filters( 'hestia_upgrade_link_from_child_theme_filter', tsdk_translate_link( tsdk_utmify( 'https://themeisle.com/themes/hestia/upgrade/', 'freevspro', 'abouthestia' ), 'query' ) ),
			/* translators: s - theme name */
			'get_pro_theme_label' => sprintf( __( 'Get %s now!', 'hestia' ), 'Hestia Pro' ),
			'banner_link'         => 'http://docs.themeisle.com/article/647-what-is-the-difference-between-hestia-and-hestia-pro',
			'banner_src'          => get_template_directory_uri() . '/assets/img/free_vs_pro_banner.png',
			'features_type'       => 'table',
			'features_img'        => get_template_directory_uri() . '/assets/img/upgrade.png',
			'features'            => array(
				array(
					'title'       => __( 'Mobile friendly', 'hestia' ),
					'description' => __( 'Responsive layout. Works on every device.', 'hestia' ),
					'is_in_lite'  => true,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'WooCommerce Compatible', 'hestia' ),
					'description' => __( 'Ready for e-commerce. You can build an online store here.', 'hestia' ),
					'is_in_lite'  => true,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Frontpage Sections', 'hestia' ),
					'description' => __( 'Big title, Features, About, Team, Testimonials, Subscribe, Blog, Contact', 'hestia' ),
					'is_in_lite'  => true,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Background image', 'hestia' ),
					'description' => __( 'You can use any background image you want.', 'hestia' ),
					'is_in_lite'  => true,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Section Reordering', 'hestia' ),
					'description' => __( 'The ability to reorganize your Frontpage Sections more easily and quickly.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Shortcodes for each section', 'hestia' ),
					'description' => __( 'Display a frontpage section wherever you like by adding its shortcode in page or post content.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Header Slider', 'hestia' ),
					'description' => __( 'You will be able to add more content to your site header with an awesome slider.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Fully Customizable Colors', 'hestia' ),
					'description' => __( 'Change colors for the header overlay, header text and navbar.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Jetpack Portfolio', 'hestia' ),
					'description' => __( 'Portfolio section with two possible layouts.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Pricing Plans Section', 'hestia' ),
					'description' => __( 'A fully customizable pricing plans section.', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
				array(
					'title'       => __( 'Quality Support', 'hestia' ),
					'description' => __( '24/7 HelpDesk Professional Support', 'hestia' ),
					'is_in_lite'  => false,
					'is_in_pro'   => true,
				),
			),
		);

		$output = '';

		if ( ! empty( $free_pro ) ) {
			if ( ! empty( $free_pro['features_type'] ) ) {
				echo '<div class="feature-section">';
				echo '<div id="free_pro" class="ti-about-page-tab-pane ti-about-page-fre-pro">';
				switch ( $free_pro['features_type'] ) {
					case 'image':
						if ( ! empty( $free_pro['features_img'] ) ) {
							$output .= '<img src="' . $free_pro['features_img'] . '">';
							if ( ! empty( $free_pro['pro_theme_link'] ) && ! empty( $free_pro['get_pro_theme_label'] ) ) {
								$output .= '<a href="' . esc_url( $free_pro['pro_theme_link'] ) . '" target="_blank" class="button button-primary button-hero">' . wp_kses_post( $free_pro['get_pro_theme_label'] ) . '</a>';
							}
						}
						break;
					case 'table':
						if ( ! empty( $free_pro['features'] ) ) {
							$output .= '<table class="free-pro-table">';
							$output .= '<thead>';
							$output .= '<tr class="ti-about-page-text-right">';
							$output .= '<th></th>';
							$output .= '<th>' . esc_html( $free_pro['free_theme_name'] ) . '</th>';
							$output .= '<th>' . esc_html( $free_pro['pro_theme_name'] ) . '</th>';
							$output .= '</tr>';
							$output .= '</thead>';
							$output .= '<tbody>';
							foreach ( $free_pro['features'] as $feature ) {
								$output .= '<tr>';
								if ( ! empty( $feature['title'] ) || ! empty( $feature['description'] ) ) {
									$output .= '<td>';
									$output .= $this->get_feature_title_and_description( $feature );
									$output .= '</td>';
								}
								if ( ! empty( $feature['is_in_lite'] ) && ( (bool) $feature['is_in_lite'] === true ) ) {
									$output .= '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
								} else {
									$output .= '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
								}
								if ( ! empty( $feature['is_in_pro'] ) && ( (bool) $feature['is_in_pro'] === true ) ) {
									$output .= '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
								} else {
									$output .= '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
								}
								echo '</tr>';
							}

							if ( ! empty( $free_pro['pro_theme_link'] ) && ! empty( $free_pro['get_pro_theme_label'] ) ) {
								$output .= '<tr>';
								$output .= '<td>';
								if ( ! empty( $free_pro['banner_link'] ) && ! empty( $free_pro['banner_src'] ) ) {
									$output .= '<a target="_blank" href="' . $free_pro['banner_link'] . '"><img src="' . $free_pro['banner_src'] . '" class="free_vs_pro_banner"></a>';
								}
								$output .= '</td>';
								$output .= '<td colspan="2" class="ti-about-page-text-right"><a href="' . esc_url( $free_pro['pro_theme_link'] ) . '" target="_blank" class="button button-primary button-hero">' . wp_kses_post( $free_pro['get_pro_theme_label'] ) . '</a></td>';
								$output .= '</tr>';
							}
							$output .= '</tbody>';
							$output .= '</table>';
						}
						break;
				}
				echo $output;
				echo '</div>';
				echo '</div>';
			}
		} // End if().
	}

	/**
	 * Display feature title and description
	 *
	 * @param array $feature Feature data.
	 */
	public function get_feature_title_and_description( $feature ) {
		$output = '';
		if ( ! empty( $feature['title'] ) ) {
			$output .= '<h3>' . wp_kses_post( $feature['title'] ) . '</h3>';
		}
		if ( ! empty( $feature['description'] ) ) {
			$output .= '<p>' . wp_kses_post( $feature['description'] ) . '</p>';
		}

		return $output;
	}

	/**
	 * Enqueue Customizer Script.
	 */
	public function enqueue_customizer_script() {
		wp_enqueue_script(
			'hestia-customizer-preview',
			get_template_directory_uri() . '/assets/js/admin/customizer.js',
			array(
				'jquery',
			),
			HESTIA_VERSION,
			true
		);
	}

	/**
	 * Enqueue customizer controls script.
	 */
	public function enqueue_customizer_controls() {
		wp_enqueue_style( 'hestia-customizer-style', get_template_directory_uri() . '/assets/css/customizer-style' . ( ( HESTIA_DEBUG ) ? '' : '.min' ) . '.css', array(), HESTIA_VERSION );
		wp_enqueue_script(
			'hestia_customize_controls',
			get_template_directory_uri() . '/assets/js/admin/customizer-controls.min.js',
			array(
				'jquery',
				'wp-color-picker',
			),
			HESTIA_VERSION,
			true
		);
		wp_localize_script(
			'hestia_customize_controls',
			'imageObject',
			array(
				'imagenonce' => wp_create_nonce( 'image_nonce' ),
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			)
		);

		$is_black_friday = apply_filters( 'themeisle_sdk_is_black_friday_sale', false );
		if ( class_exists( 'Ti_White_Label_Markup' ) && Ti_White_Label_Markup::is_theme_whitelabeld() ) {
			$is_black_friday = false;
		}

		if ( $is_black_friday ) {
			wp_localize_script(
				'hestia_customize_controls',
				'hestiaSaleEvents',
				array(
					'customizerBannerUrl'      => esc_url_raw( get_template_directory_uri() . '/assets/img/black-friday-customizer.png' ),
					'customizerBannerStoreUrl' => esc_url_raw( ( $this->get_black_friday_data() )['sale_url'] ),
				)
			);
		}
	}

	/**
	 * Add inline style for editor.
	 *
	 * @param string $init Setup TinyMCE.
	 *
	 * @return mixed
	 */
	public function editor_inline_style( $init ) {
		$editor_style = $this->admin_editor_inline_style();
		if ( wp_default_editor() === 'tinymce' ) {
			$init['content_style'] = $editor_style;
		}

		return $init;
	}

	/**
	 * Add custom inline style for editor.
	 *
	 * @return string
	 */
	private function admin_editor_inline_style() {

		$accent_color  = get_theme_mod( 'accent_color', apply_filters( 'hestia_accent_color_default', '#e91e63' ) );
		$headings_font = get_theme_mod( 'hestia_headings_font' );
		$body_font     = get_theme_mod( 'hestia_body_font' );

		$custom_css = '';

		// Load google font.
		if ( ! empty( $body_font ) ) {
			$body_font_url = hestia_get_local_webfont_url( 'https://fonts.googleapis.com/css?family=' . esc_attr( $body_font ) );
			$custom_css   .= '@import url(' . $body_font_url . ');';
		}
		if ( ! empty( $headings_font ) ) {
			$headings_font_url = hestia_get_local_webfont_url( 'https://fonts.googleapis.com/css?family=' . esc_attr( $headings_font ) );
			$custom_css       .= '@import url(' . $headings_font_url . ');';
		}
		// Check if accent color is exists.
		if ( ! empty( $accent_color ) ) {
			$custom_css .= 'body:not(.elementorwpeditor)#tinymce .mce-content-body a { color: ' . esc_attr( $accent_color ) . '; }';
		}

		// Check if font family for body exists.
		if ( ! empty( $body_font ) ) {
			$custom_css .= 'body:not(.elementorwpeditor)#tinymce, body:not(.elementorwpeditor)#tinymce p { font-family: ' . esc_attr( $body_font ) . ' !important; }';
		}

		// Check if font family for headings exists.
		if ( ! empty( $headings_font ) ) {
			$custom_css .= 'body:not(.elementorwpeditor)#tinymce h1, body:not(.elementorwpeditor)#tinymce h2, body:not(.elementorwpeditor)#tinymce h3, body:not(.elementorwpeditor)#tinymce h4, body:not(.elementorwpeditor)#tinymce h5, body:not(.elementorwpeditor)#tinymce h6 { font-family: ' . esc_attr( $headings_font ) . ' !important; }';
		}

		return $custom_css;
	}

	/**
	 * If conditions are fulfilled this will add the front-page import logic.
	 */
	function add_zerif_frontpage_import() {
		$imported_flag = get_theme_mod( 'zerif_frontpage_was_imported', 'not-zerif' );
		if ( $imported_flag === 'yes' || $imported_flag === 'not-zerif' ) {
			return;
		}
	}

	/**
	 * In case the old theme wasn't Zerif, mark the importer flag to avoid printing the import notice.
	 */
	public function maybe_switched_from_zerif() {
		$old_theme = strtolower( get_option( 'theme_switched' ) );

		$content_imported = get_theme_mod( 'zerif_frontpage_was_imported', 'not-zerif' );
		if ( $content_imported === 'yes' ) {
			return;
		}

		if ( $content_imported === 'not-zerif' && in_array( $old_theme, array( 'zerif-pro', 'zerif-lite' ), true ) ) {
			set_theme_mod( 'zerif_frontpage_was_imported', 'no' );
		}
		if ( ! in_array( $old_theme, array( 'zerif-pro', 'zerif-lite' ), true ) ) {
			set_theme_mod( 'zerif_frontpage_was_imported', 'not-zerif' );
		}
	}

	/**
	 * Render welcome notice content
	 */
	public function welcome_notice_content() {
		$theme_args     = wp_get_theme();
		$name           = apply_filters( 'ti_wl_theme_name', $theme_args->__get( 'Name' ) );
		$has_onboarding = class_exists( 'Themeisle_Onboarding', false );

		/* translators: %s - theme name */
		$heading     = str_replace( ' - Version', '', sprintf( __( 'Welcome to %s! - Version', 'hestia' ), $name ) . ' ' );
		$screenshot  = get_template_directory_uri() . '/assets/img/notice.png';
		$description = __( 'Using the WordPress Customizer you can easily customize every aspect of the theme.', 'hestia' );
		$button_text = __( 'Go to the Customizer', 'hestia' );
		$button_link = admin_url( 'customize.php' );

		if ( $has_onboarding ) {
			$template    = $theme_args->get( 'Template' );
			$slug        = $theme_args->__get( 'stylesheet' );
			$description = sprintf(
				/* translators: %s - theme name */
				__( '%s is now installed and ready to use. We\'ve assembled some links to get you started.', 'hestia' ),
				'<strong>' . $name . '</strong>'
			);
			$theme_page  = ! empty( $template ) ? $template . '-welcome' : $slug . '-welcome';
			$button_text = __( 'Import Demo Content', 'hestia' );
			$button_link = esc_url( admin_url( 'themes.php?page=' . $theme_page . '&onboarding=yes&readyimport=hestia-default#sites_library' ) );
		}

		$style = '
		<style>
		.ti-welcome-notice .notice-content{ display: flex; gap: 48px; width: 100%; }
		.ti-welcome-notice p { font-size: 15px; line-height: 1.6; max-width: 700px; }
		.ti-welcome-notice .notice-title { font-weight: 600; line-height: 1.5; max-width: 700px; }
		.ti-welcome-notice { border-left: 1px solid #c3c4c7; padding: 20px 0; position: relative; }
		.ti-welcome-notice .actions { align-items: center; display: flex; gap: 10px; margin-top: 20px; }
		.ti-welcome-notice .notice-copy { display: flex; flex-direction: column; justify-content: center; padding: 30px 0; }
		.ti-welcome-notice img { align-self: flex-end; display: block; flex-shrink: 1; max-height: 300px; max-width: 500px; width: auto; }
		@media(max-width: 1200px) {
    	    .ti-welcome-notice .notice-content{ flex-direction: column-reverse; gap: 0; text-align: center; }
			.ti-welcome-notice .notice-copy { align-items: center; padding: 20px 0; }
			.ti-welcome-notice img { margin: 0 auto; max-width: 80%; }
		}
		</style>';

		echo $style;
		echo '<div class="notice-content">';
		echo '<img class="image" src="' . esc_url( $screenshot ) . '"/>';
		echo '<div class="notice-copy">';
		echo '<h1 class="notice-title">' . esc_html( $heading ) . ' 🎉</h1>';
		echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
		echo '<div class="actions">';
		echo '<a href="' . esc_url( $button_link ) . '" class="button button-primary button-hero">';
		echo esc_html( $button_text );
		echo '</a>';
		if ( $has_onboarding ) {
			echo '<a href="' . esc_url( admin_url( 'customize.php' ) ) . '" class="button button-link button-hero">';
			echo esc_html( __( 'Go to the Customizer', 'hestia' ) );
			echo '</a>';
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Load site import module.
	 */
	public function load_site_import() {
		if ( class_exists( 'Themeisle_Onboarding', false ) ) {
			Themeisle_Onboarding::instance();
		}
	}

	/**
	 * Get the plan category for the product plan ID.
	 *
	 * @param object $license_data The license data.
	 *
	 * @return int
	 */
	private function plan_category( $license_data ) {

		if ( ! isset( $license_data->plan ) || ! is_numeric( $license_data->plan ) ) {
			return 0; // Free
		}

		$plan             = (int) $license_data->plan;
		$current_category = -1; // Unknown category.

		$categories = array(
			'1' => array( 1, 4, 9 ), // Personal
			'2' => array( 2, 5, 8 ), // Business/Developer
			'3' => array( 3, 6, 7, 10 ), // Agency
		);

		foreach ( $categories as $category => $plans ) {
			if ( in_array( $plan, $plans, true ) ) {
				$current_category = (int) $category;
				break;
			}
		}

		return $current_category;
	}

	/**
	 * Get the data used for the survey.
	 *
	 * @param array  $data The survey data in Formbricks format.
	 * @param string $page_slug The slug of the page.
	 *
	 * @return array
	 */
	public function get_survey_metadata( $data, $page_slug ) {
		$license_saved       = get_option( 'hestia_pro_license_data', array() );
		$install_time        = min( get_option( 'hestia_pro_install', time() ), get_option( 'hestia_install', time() ) );
		$install_days_number = intval( ( time() - $install_time ) / DAY_IN_SECONDS );

		$data = array(
			'environmentId' => 'clskegyyx7syhpodwo7llnqg6',
			'attributes'    => array(
				'license_status'      => ! empty( $license_saved->license ) ? $license_saved->license : 'invalid',
				'install_days_number' => $install_days_number,
				'version'             => HESTIA_VERSION,
				'plan'                => $this->plan_category( $license_saved ),
			),
		);

		if ( 1 >= $data['attributes']['plan'] ) {
			do_action( 'themeisle_sdk_load_banner', 'hestia' );
		}

		return $data;
	}

	/**
	 * Initialize dependencies for Hestia Options.
	 */
	public function hestia_options_init() {
		if ( class_exists( 'Ti_White_Label_Markup' ) && Ti_White_Label_Markup::is_theme_whitelabeld() ) {
			add_filter(
				'themeisle_sdk_blackfriday_data',
				function( $configs ) {
					return array();
				},
				1000
			);
		} else {
			add_filter( 'themeisle_sdk_blackfriday_data', array( $this, 'add_black_friday_data' ) );
		}
		add_filter( 'themeisle-sdk/survey/' . HESTIA_PRODUCT_SLUG, array( $this, 'get_survey_metadata' ), 10, 2 );

		$screen = get_current_screen();

		if ( 'customize' === $screen->id ) {
			do_action( 'themeisle_internal_page', HESTIA_PRODUCT_SLUG, 'customize' );
		}

		if ( 'toplevel_page_' . $this->theme_slug . '-welcome' === $screen->id ) {
			( new Hestia_Dashboard() )->load_page_deps();
		}

		if ( ! in_array(
			$screen->base,
			array(
				'appearance_page_hestia-welcome',
				'appearance_page_hestia-pro-welcome',
			),
			true
		) ) {
			return;
		}

		do_action( 'themeisle_internal_page', HESTIA_PRODUCT_SLUG, 'settings' );
	}

	/**
	 * Get the welcome metadata.
	 *
	 * @return array
	 *
	 * @hooked hestia_welcome_metadata
	 */
	public function get_welcome_metadata() {
		return array(
			'is_enabled' => ! defined( 'HESTIA_PRO_FLAG' ),
			'pro_name'   => 'Hestia Pro',
			'logo'       => get_template_directory_uri() . '/assets/img/logo.svg',
			'cta_link'   => tsdk_translate_link( tsdk_utmify( 'https://themeisle.com/themes/hestia/upgrade/?discount=LOYALUSER583&dvalue=60#pricing', 'hestia-welcome', 'notice' ), 'query' ),
		);
	}

	/**
	 * Get Black Friday data.
	 *
	 * @param array $config The configuration for the loaded product.
	 *
	 * @return array
	 */
	private function get_black_friday_data( $config = array() ) {
		// translators: %1$s - HTML tag, %2$s - discount, %3$s - HTML tag, %4$s - product name.
		$message_template = __( 'Our biggest sale of the year: %1$sup to %2$s OFF%3$s on %4$s. Don\'t miss this limited-time offer.', 'hestia' );
		$product_label    = 'Hestia';
		$discount         = '70%';

		$plan    = apply_filters( 'product_hestia_license_plan', 0 );
		$license = apply_filters( 'product_hestia_license_key', false );
		$is_pro  = 0 < $plan;

		if ( $is_pro ) {
			// translators: %1$s - HTML tag, %2$s - discount, %3$s - HTML tag, %4$s - product name.
			$message_template = __( 'Get %1$sup to %2$s off%3$s when you upgrade your %4$s plan or renew early.', 'hestia' );
			$product_label    = 'Hestia Pro';
			$discount         = '30%';
		}

		$product_label = sprintf( '<strong>%s</strong>', $product_label );
		$url_params    = array(
			'utm_term' => $is_pro ? 'plan-' . $plan : 'free',
			'lkey'     => ! empty( $license ) ? $license : false,
		);

		$config['message']  = sprintf( $message_template, '<strong>', $discount, '</strong>', $product_label );
		$config['sale_url'] = add_query_arg(
			$url_params,
			tsdk_translate_link( tsdk_utmify( 'https://themeisle.link/hestia-bf', 'bfcm', 'hestia' ) )
		);

		return $config;
	}

	/**
	 * Add Black Friday data.
	 *
	 * @param array $configs The configuration array for the loaded products.
	 *
	 * @return array
	 */
	public function add_black_friday_data( $configs ) {
		$configs[ HESTIA_PRODUCT_SLUG ] = $this->get_black_friday_data( $configs['default'] );

		return $configs;
	}

	/**
	 * Run after plugin activate while site libray import.
	 */
	public function hestia_after_plugin_activation( $plugin_slug ) {
		if ( 'otter-blocks' === $plugin_slug ) {
			update_option( 'themeisle_blocks_settings_redirect', '' );
		}
	}
}
