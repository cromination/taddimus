<?php
/**
 * Hestia Additional Views.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Header_Layout_Manager
 */
class Hestia_Additional_Views extends Hestia_Abstract_Main {
	/**
	 * Init layout manager.
	 */
	public function init() {
		add_action( 'hestia_after_single_post_article', array( $this, 'post_after_article' ) );

		add_action( 'hestia_blog_social_icons', array( $this, 'social_icons' ) );

		add_action( 'wp_footer', array( $this, 'scroll_to_top' ) );

		add_action( 'hestia_blog_related_posts', array( $this, 'related_posts' ) );

		add_action( 'hestia_before_header_hook', array( $this, 'hidden_sidebars' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_inline_icon_styles' ) );
	}

	/**
	 * Add inline styles for icons.
	 */
	public function add_inline_icon_styles() {
		wp_add_inline_style( 'hestia_style', hestia_minimize_css( $this->icon_inline_style() ) );
	}

	/**
	 * Icon styles.
	 */
	private function icon_inline_style() {
		$icon_padding        = json_decode( get_theme_mod( 'hestia_scroll_button_padding_dimensions', '' ), true );
		$icon_size           = json_decode( get_theme_mod( 'hestia_scroll_icon_size', '' ), true );
		$border_radius       = get_theme_mod( 'hestia_scroll_button_border_radius', 50 );
		$icon_color          = get_theme_mod( 'hestia_scroll_icon_color', '#fff' );
		$icon_hover_color    = get_theme_mod( 'hestia_scroll_icon_hover_color', '#fff' );
		$icon_bg_color       = get_theme_mod( 'hestia_scroll_icon_bg_color', '#999' );
		$icon_bg_hover_color = get_theme_mod( 'hestia_scroll_icon_bg_hover_color', '#999' );

		$devices = array(
			'mobile'  => '',
			'tablet'  => 'min-width: 480px',
			'desktop' => 'min-width: 768px',
		);

		$css = '';

		foreach ( $devices as $device => $media_query ) {
			$padding_css = $this->get_icon_padding_css( $icon_padding, $device );
			$size_css    = $this->get_icon_size_css( $icon_size, $device );

			if ( $media_query ) {
				$css .= "@media( $media_query ) { $padding_css $size_css }";
			} else {
				$css .= $padding_css . $size_css;
			}
		}

		$css .= '
		.hestia-scroll-to-top {
			border-radius : ' . esc_attr( $border_radius ) . '%;
			background-color: ' . esc_attr( $icon_bg_color ) . ';
		}
		.hestia-scroll-to-top:hover {
			background-color: ' . esc_attr( $icon_bg_hover_color ) . ';
		}
		.hestia-scroll-to-top:hover svg, .hestia-scroll-to-top:hover p {
			color: ' . esc_attr( $icon_hover_color ) . ';
		}
		.hestia-scroll-to-top svg, .hestia-scroll-to-top p {
			color: ' . esc_attr( $icon_color ) . ';
		}
		';
		return $css;
	}

	/**
	 * Get icon padding css.
	 *
	 * @param array  $property padding property value.
	 * @param string $device Device type.
	 * @return string
	 */
	private function get_icon_padding_css( $property, $device ) {
		if ( empty( $property[ $device ] ) ) {
			return '';
		}

		$values = json_decode( $property[ $device ], true );
		if ( ! is_array( $values ) ) {
			return '';
		}

		$css = '.hestia-scroll-to-top {';
		foreach ( $values as $key => $value ) {
			$side = str_replace( $device . '_', '', $key );
			$css .= "padding-$side: " . intval( $value ) . 'px;';
		}
		$css .= '}';

		return $css;
	}

	/**
	 * Get icon size css.
	 *
	 * @param array  $property padding property value.
	 * @param string $device Device type.
	 * @return string
	 */
	private function get_icon_size_css( $property, $device ) {
		if ( empty( $property[ $device ] ) ) {
			return '';
		}

		$size = intval( $property[ $device ] );

		return ".hestia-scroll-to-top svg, .hestia-scroll-to-top img { width: {$size}px; height: {$size}px; }";
	}

	/**
	 * Social sharing icons for single view.
	 *
	 * @since Hestia 1.0
	 */
	public function social_icons() {
		$enabled_socials = get_theme_mod( 'hestia_enable_sharing_icons', true );
		if ( (bool) $enabled_socials !== true ) {
			return;
		}

		$post_link  = esc_url( get_the_permalink() );
		$post_title = get_the_title();

		$facebook_url = add_query_arg(
			array(
				'u' => $post_link,
			),
			'https://www.facebook.com/sharer.php'
		);

		$twitter_url = add_query_arg(
			array(
				'url'  => $post_link,
				'text' => rawurlencode( html_entity_decode( wp_strip_all_tags( $post_title ), ENT_COMPAT, 'UTF-8' ) ),
			),
			'https://x.com/share'
		);

		$email_title = str_replace( '&', '%26', $post_title );

		$email_url = add_query_arg(
			array(
				'subject' => wp_strip_all_tags( $email_title ),
				'body'    => $post_link,
			),
			'mailto:'
		);

		$social_links = '
        <div class="col-md-6">
            <div class="entry-social">
                <a target="_blank" rel="tooltip"
                   data-original-title="' . esc_attr__( 'Share on Facebook', 'hestia' ) . '"
                   class="btn btn-just-icon btn-round btn-facebook"
                   href="' . esc_url( $facebook_url ) . '">
                   <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="20" height="17"><path fill="currentColor" d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"></path></svg>
                </a>
                
                <a target="_blank" rel="tooltip"
                   data-original-title="' . esc_attr__( 'Share on X', 'hestia' ) . '"
                   class="btn btn-just-icon btn-round btn-twitter"
                   href="' . esc_url( $twitter_url ) . '">
                   <svg width="20" height="17" viewBox="0 0 1200 1227" fill="none" xmlns="http://www.w3.org/2000/svg">
                   <path d="M714.163 519.284L1160.89 0H1055.03L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.491 750.218L842.672 1226.37H1200L714.137 519.284H714.163ZM569.165 687.828L521.697 619.934L144.011 79.6944H306.615L611.412 515.685L658.88 583.579L1055.08 1150.3H892.476L569.165 687.854V687.828Z" fill="#FFFFFF"/>
                   </svg>

                </a>
                
                <a rel="tooltip"
                   data-original-title=" ' . esc_attr__( 'Share on Email', 'hestia' ) . '"
                   class="btn btn-just-icon btn-round"
                   href="' . esc_url( $email_url ) . '">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="17"><path fill="currentColor" d="M502.3 190.8c3.9-3.1 9.7-.2 9.7 4.7V400c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V195.6c0-5 5.7-7.8 9.7-4.7 22.4 17.4 52.1 39.5 154.1 113.6 21.1 15.4 56.7 47.8 92.2 47.6 35.7.3 72-32.8 92.3-47.6 102-74.1 131.6-96.3 154-113.7zM256 320c23.2.4 56.6-29.2 73.4-41.4 132.7-96.3 142.8-104.7 173.4-128.7 5.8-4.5 9.2-11.5 9.2-18.9v-19c0-26.5-21.5-48-48-48H48C21.5 64 0 85.5 0 112v19c0 7.4 3.4 14.3 9.2 18.9 30.6 23.9 40.7 32.4 173.4 128.7 16.8 12.2 50.2 41.8 73.4 41.4z"></path></svg>
               </a>
            </div>
		</div>';
		echo apply_filters( 'hestia_filter_blog_social_icons', $social_links );
	}

	/**
	 * Single post after article.
	 */
	public function post_after_article() {
		global $post;
		$categories           = get_the_category( $post->ID );
		$enable_categories    = get_theme_mod( 'hestia_enable_categories', true );
		$enable_tags          = get_theme_mod( 'hestia_enable_tags', true );
		$enable_shareing_icon = get_theme_mod( 'hestia_enable_sharing_icons', true );
		?>

		<div class="section section-blog-info">
			<div class="row">
				<?php if ( $enable_categories || $enable_tags || $enable_shareing_icon ) : ?>
					<div class="col-md-6">
						<?php if ( $enable_categories ) : ?>
							<div class="entry-categories"><?php esc_html_e( 'Categories:', 'hestia' ); ?>
								<?php
								foreach ( $categories as $category ) {
									echo '<span class="label label-primary"><a href="' . esc_url( get_category_link( $category->term_id ) ) . '">' . esc_html( $category->name ) . '</a></span>';
								}
								?>
							</div>
						<?php endif; ?>
						<?php $enable_tags ? the_tags( '<div class="entry-tags">' . esc_html__( 'Tags:', 'hestia' ) . ' ' . '<span class="entry-tag">', '</span><span class="entry-tag">', '</span></div>' ) : ''; ?>
					</div>
					<?php do_action( 'hestia_blog_social_icons' ); ?>
				<?php endif; ?>
			</div>
			<hr>
			<?php
			$this->maybe_render_author_box();
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>
		</div>
		<?php
	}


	/**
	 * Render the author box.
	 */
	private function maybe_render_author_box() {
		$author_description = get_the_author_meta( 'description' );
		if ( empty( $author_description ) ) {
			return;
		}
		?>
		<div class="card card-profile card-plain">
			<div class="row">
				<div class="col-md-2">
					<div class="card-avatar">
						<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"
								title="<?php echo esc_attr( get_the_author() ); ?>"><?php echo get_avatar( get_the_author_meta( 'ID' ), 100 ); ?></a>
					</div>
				</div>
				<div class="col-md-10">
					<h4 class="card-title"><?php the_author(); ?></h4>
					<p class="description"><?php the_author_meta( 'description' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display scroll to top button.
	 *
	 * @since 1.1.54
	 */
	public function scroll_to_top() {
		$hestia_enable_scroll_to_top = get_theme_mod( 'hestia_enable_scroll_to_top', apply_filters( 'hestia_scroll_to_top_default', 0 ) );
		if ( (bool) $hestia_enable_scroll_to_top === false ) {
			return;
		}
		$icon_type      = get_theme_mod( 'hestia_scroll_to_top_icon_type', 'icon' );
		$button_side    = get_theme_mod( 'hestia_scroll_to_top_side', 'right' );
		$scroll_icons   = hestia_scroll_icons();
		$scroll_icon    = get_theme_mod( 'hestia_scroll_to_top_icon', 'stt-icon-style-1' );
		$scroll_icon    = $scroll_icons[ $scroll_icon ];
		$scroll_label   = get_theme_mod( 'hestia_scroll_to_top_label', '' );
		$hide_on_mobile = get_theme_mod( 'hestia_scroll_to_top_hide_mobile', false );
		$image_url      = get_theme_mod( 'hestia_scroll_to_top_image', '' );

		if ( empty( $scroll_icon ) ) {
			return;
		}
		$button_class  = esc_attr( 'right' === $button_side ? 'hestia-scroll-right ' : 'hestia-scroll-left ' );
		$button_class .= $hide_on_mobile ? 'hestia-scroll-hide-on-mobile ' : '';
		?>

		<button class="hestia-scroll-to-top <?php echo esc_attr( $button_class ); ?>" title="<?php esc_attr_e( 'Enable Scroll to Top', 'hestia' ); ?>">
			<?php if ( 'icon' === $icon_type ) { ?>
				<?php echo wp_kses( $scroll_icon, hestia_allow_icon_tag() ); ?>
			<?php } elseif ( 'image' === $icon_type && ! empty( $image_url ) ) { ?>
				<img src="<?php echo esc_attr( $image_url ); ?>" alt="<?php esc_attr_e( 'Enable Scroll to Top', 'hestia' ); ?>">
			<?php } ?>
			<?php if ( ! empty( $scroll_label ) ) { ?>
				<p><?php echo esc_html( $scroll_label ); ?></p>
			<?php } ?>
		</button>
		<?php
	}

	/**
	 * Related posts for single view.
	 *
	 * @since Hestia 1.0
	 */
	public function related_posts() {
		$enable_related_posts = get_theme_mod( 'hestia_enable_related_posts', true );

		if ( ! $enable_related_posts ) {
			return;
		}

		global $post;
		$cats = wp_get_object_terms(
			$post->ID,
			'category',
			array(
				'fields' => 'ids',
			)
		);
		$args = array(
			'posts_per_page'      => 3,
			'cat'                 => $cats,
			'orderby'             => 'date',
			'ignore_sticky_posts' => true,
			'post__not_in'        => array( $post->ID ),
		);

		if ( function_exists( 'yoast_get_primary_term_id' ) && true === apply_filters( 'hestia_related_posts_by_yoast_primary_term', true ) ) {
			$yoast_primary_term_id = yoast_get_primary_term_id();
			if ( $yoast_primary_term_id > 0 ) {
				$args['category__in'] = array( $yoast_primary_term_id );
			}
		}

		$allowed_html = array(
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'i'      => array(
				'class' => array(),
			),
			'span'   => array(),
		);

		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) :
			?>
			<div class="section related-posts">
				<div class="container">
					<div class="row">
						<div class="col-md-12">
							<h2 class="hestia-title text-center"><?php echo apply_filters( 'hestia_related_posts_title', esc_html__( 'Related Posts', 'hestia' ) ); ?></h2>
							<div class="row">
								<?php
								while ( $loop->have_posts() ) :
									$loop->the_post();
									?>
									<div class="col-md-4">
										<div class="card card-blog">
											<?php if ( has_post_thumbnail() ) : ?>
												<div class="card-image">
													<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
														<?php the_post_thumbnail( 'hestia-blog' ); ?>
													</a>
												</div>
											<?php endif; ?>
											<div class="content">
												<span class="category text-info"><?php echo hestia_category( false ); ?></span>
												<h4 class="card-title">
													<a class="blog-item-title-link" href="<?php echo esc_url( get_permalink() ); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark">
														<?php echo wp_kses( force_balance_tags( get_the_title() ), $allowed_html ); ?>
													</a>
												</h4>
												<p class="card-description"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
											</div>
										</div>
									</div>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		endif;
	}

	/**
	 * Display the hidden sidebars to enable the customizer panels.
	 */
	public function hidden_sidebars() {
		echo '<div style="display: none">';
		if ( is_customize_preview() ) {
			dynamic_sidebar( 'sidebar-top-bar' );
			dynamic_sidebar( 'header-sidebar' );
			dynamic_sidebar( 'subscribe-widgets' );
			dynamic_sidebar( 'sidebar-big-title' );
		}
		echo '</div>';
	}

}
