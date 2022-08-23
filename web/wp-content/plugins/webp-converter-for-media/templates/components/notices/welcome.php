<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $settings_url URL of plugin settings page (default view).
 *
 * @package Converter for Media
 */

?>
<div class="notice notice-success"
	data-notice="webp-converter-for-media"
>
	<div class="webpcContent webpcContent--notice">
		<h4>
			<?php echo esc_html( __( 'Thank you for installing our plugin Converter for Media!', 'webp-converter-for-media' ) ); ?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %s: icon heart */
					__( 'Go to the plugin settings and optimize all your images with one click! Thank you for being with us! %s', 'webp-converter-for-media' ),
					'<span class="dashicons dashicons-heart"></span>'
				)
			);
			?>
		</p>
		<div class="webpcContent__buttons">
			<a href="<?php echo esc_url( $settings_url ); ?>"
				class="webpcContent__button webpcButton webpcButton--blue webpcButton--bg"
			>
				<?php echo esc_html( __( 'Speed up my website', 'webp-converter-for-media' ) ); ?>
			</a>
		</div>
	</div>
</div>
