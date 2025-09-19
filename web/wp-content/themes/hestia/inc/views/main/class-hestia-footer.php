<?php
/**
 * Footer Main Manager
 *
 * @package Hestia
 */

/**
 * Class Hestia_Footer
 */
class Hestia_Footer extends Hestia_Abstract_Main {

	/**
	 * Initialization of the feature.
	 */
	public function init() {
		add_action( 'hestia_do_footer', array( $this, 'the_footer_content' ) );
		add_filter( 'wp_nav_menu_args', array( $this, 'modify_footer_menu_classes' ) );
		add_action( 'hestia_do_bottom_footer_content', array( $this, 'bottom_footer_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'footer_style' ) );
	}

	/**
	 * Get an array of footer sidevars slugs.
	 *
	 * @return array
	 */
	private function get_footer_sidebars() {
		$footer_sidebars_array = array(
			'footer-one-widgets',
			'footer-two-widgets',
			'footer-three-widgets',
			'footer-four-widgets',
		);
		$number_of_sidebars    = get_theme_mod( 'hestia_nr_footer_widgets', '3' );
		$footer_sidebars_array = array_slice( $footer_sidebars_array, 0, $number_of_sidebars );

		return $footer_sidebars_array;
	}

	/**
	 * Render the footer sidebars.
	 */
	private function render_footer_sidebars() {
		if ( ! $this->does_footer_have_widgets() ) {
			return;
		}

		$sidebars = $this->get_footer_sidebars();
		if ( empty( $sidebars ) ) {
			return;
		} ?>

		<div class="content">
			<div class="row">
				<?php
				foreach ( $sidebars as $footer_sidebar ) {
					if ( is_active_sidebar( $footer_sidebar ) ) {
						echo '<div class="' . esc_attr( $this->the_sidebars_class() ) . '">';
						dynamic_sidebar( $footer_sidebar );
						echo '</div>';
					}
				}
				?>
			</div>
		</div>
		<hr/>
		<?php
	}

	/**
	 * Function to display footer content.
	 *
	 * @since  1.1.24
	 * @access public
	 */
	public function the_footer_content() {

		if ( apply_filters( 'hestia_filter_components_toggle', false, 'footer' ) === true ) {
			return;
		}

		$hestia_general_credits = get_theme_mod(
			'hestia_general_credits',
			sprintf(
			/* translators: %1$s is Theme Name, %2$s is WordPress */
				esc_html__( '%1$s | Developed by %2$s', 'hestia' ),
				esc_html__( 'Hestia', 'hestia' ),
				/* translators: %1$s is URL, %2$s is WordPress */
				sprintf(
					'<a href="%1$s" rel="nofollow">%2$s</a>',
					esc_url( __( 'https://themeisle.com', 'hestia' ) ),
					'ThemeIsle'
				)
			)
		);

		if ( ! $this->does_footer_have_widgets() && ! has_nav_menu( 'footer' ) && empty( $hestia_general_credits ) ) {
			return;
		}

		hestia_before_footer_trigger();

		?>
		<footer class="footer <?php echo esc_attr( $this->the_footer_class() ); ?> footer-big">
			<?php hestia_before_footer_content_trigger(); ?>
			<div class="container">
				<?php hestia_before_footer_widgets_trigger(); ?>
				<?php $this->render_footer_sidebars(); ?>
				<?php hestia_after_footer_widgets_trigger(); ?>
				<?php $this->wrapped_bottom_footer_content(); ?>
			</div>
			<?php hestia_after_footer_content_trigger(); ?>
		</footer>
		<?php
		hestia_after_footer_trigger();
	}

	/**
	 * Filter footer menu classes to account for alignment.
	 *
	 * @param string $classes the footer classes.
	 *
	 * @return mixed
	 */
	public function modify_footer_menu_classes( $classes ) {
		if ( 'footer' !== $classes['theme_location'] ) {
			return $classes;
		}
		$classes['menu_class'] .= ' ' . $this->add_footer_menu_alignment_class();

		return $classes;
	}

	/**
	 * Function to display footer copyright and footer menu.
	 */
	private function wrapped_bottom_footer_content() {
		echo '<div class="hestia-bottom-footer-content">';
		do_action( 'hestia_do_bottom_footer_content' );
		echo '</div>';
	}

	/**
	 * Function to display footer copyright and footer menu.
	 * Also used as callback for selective refresh.
	 */
	public function bottom_footer_content() {
		$hestia_general_credits = sprintf(
		/* translators: %1$s is Theme Name, %2$s is WordPress */
			esc_html__( '%1$s | Developed by %2$s', 'hestia' ),
			esc_html__( 'Hestia', 'hestia' ),
			/* translators: %1$s is URL, %2$s is WordPress */
			sprintf(
				'<a href="%1$s" rel="nofollow">%2$s</a>',
				esc_url( __( 'https://themeisle.com', 'hestia' ) ),
				'ThemeIsle'
			)
		);

		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'depth'          => 1,
					'container'      => 'ul',
					'menu_class'     => 'footer-menu',
				)
			);
		}

		echo '<div class="copyright ' . esc_attr( $this->add_footer_copyright_alignment_class() ) . '">';
		echo wp_kses_post( $hestia_general_credits );
		echo '</div>';
	}

	/**
	 * Add the footer copyright alignment class.
	 *
	 * @return string
	 */
	protected function add_footer_copyright_alignment_class() {
		$hestia_copyright_alignment = get_theme_mod( 'hestia_copyright_alignment', 'right' );
		if ( $hestia_copyright_alignment === 'left' ) {
			return 'pull-left';
		}
		if ( $hestia_copyright_alignment === 'center' ) {
			return 'hestia-center';
		}

		return 'pull-right';
	}

	/**
	 * Add the footer menu alignment class.
	 *
	 * @return string
	 */
	private function add_footer_menu_alignment_class() {
		$hestia_copyright_alignment = get_theme_mod( 'hestia_copyright_alignment', 'right' );
		if ( $hestia_copyright_alignment === 'left' ) {
			return 'pull-right';
		}
		if ( $hestia_copyright_alignment === 'center' ) {
			return 'hestia-center';
		}

		return 'pull-left';
	}

	/**
	 * Utility to get the footer class for color changes.
	 */
	private function the_footer_class() {
		$background_color = get_theme_mod( 'hestia_footer_background_color', '#323437' );
		$class            = 'footer-black';

		if ( $background_color !== '#323437' ) {
			$class = '';
		}

		return $class;
	}

	/**
	 * Get the sidebars class.
	 *
	 * @return string the sidebar class
	 */
	private function the_sidebars_class() {
		$number_of_sidebars = get_theme_mod( 'hestia_nr_footer_widgets', '3' );

		if ( empty( $number_of_sidebars ) ) {
			return 'col-md-4';
		}

		$suffix = abs( 12 / $number_of_sidebars );
		$class  = 'col-md-' . $suffix;

		return $class;
	}

	/**
	 * Utility to check if any of the footer sidebars have widgets.
	 *
	 * @return bool
	 */
	private function does_footer_have_widgets() {
		$sidebars = $this->get_footer_sidebars();
		if ( empty( $sidebars ) ) {
			return false;
		}

		foreach ( $sidebars as $footer_sidebar ) {
			$has_widgets = is_active_sidebar( $footer_sidebar );
			if ( $has_widgets ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add footer style.
	 */
	public function footer_style() {
		wp_add_inline_style( 'hestia_style', hestia_minimize_css( $this->footer_css() ) );
	}

	/**
	 * Footer css.
	 */
	private function footer_css() {
		$custom_css = '';

		$footer_background_color  = get_theme_mod( 'hestia_footer_background_color', '#323437' );
		$footer_text_color        = get_theme_mod( 'hestia_footer_text_color', '#fff' );
		$footer_widget_text_color = get_theme_mod( 'hestia_footer_widget_text_color', '#5e5e5e' );

		if ( '#323437' !== $footer_background_color ) {
			$selector = 'footer.footer';
		} else {
			$selector = 'footer.footer.footer-black';
		}

		if ( ! empty( $footer_background_color ) ) {
			$custom_css .= $selector . ' { background: ' . esc_html( $footer_background_color ) . '}';
		}

		if ( ! empty( $footer_text_color ) ) {
			$custom_css .= $selector . '.footer-big { color: ' . esc_html( $footer_text_color ) . ' }';
			$custom_css .= $selector . ' a { color: ' . esc_html( $footer_text_color ) . '}';
		}

		if ( ! empty( $footer_widget_text_color ) ) {
			$custom_css .= $selector . ' hr { border-color: ' . esc_html( $footer_widget_text_color ) . '}';
			$custom_css .= '.footer-big p, .widget, .widget code, .widget pre { color: ' . esc_html( $footer_widget_text_color ) . '}';
		}

		return $custom_css;
	}
}
