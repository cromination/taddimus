<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
 * @var string $settings_url URL of plugin settings page (default view).
 *
 * @package Converter for Media
 */

?>
<div class="notice notice-success is-dismissible"
	data-notice="webp-converter-for-media"
	data-notice-action="<?php echo esc_attr( $close_action ); ?>"
	data-notice-url="<?php echo esc_url( $ajax_url ); ?>"
>
	<div class="webpcContent webpcContent--notice">
		<h4>
			<?php echo esc_html( __( 'Thank you for installing our Converter for Media plugin!', 'webp-converter-for-media' ) ); ?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1$s: button label, %2$s: icon heart */
					__( 'Optimize all your images by click the "%1$s" button in the plugin settings. It\'s so easy! %2$s', 'webp-converter-for-media' ),
					__( 'Start Bulk Optimization', 'webp-converter-for-media' ),
					'<span class="dashicons dashicons-heart"></span>'
				)
			);
			?>
		</p>
		<div class="webpcContent__buttons">
			<a href="<?php echo esc_url( $settings_url ); ?>"
				class="webpcContent__button webpcButton webpcButton--blue webpcButton--bg"
			>
				<?php echo esc_html( __( 'Go to the plugin settings', 'webp-converter-for-media' ) ); ?>
			</a>
			<a href="https://url.mattplugins.com/converter-notice-welcome-button-video" target="_blank"
				class="webpcContent__button webpcButton webpcButton--blue"
			>
				<?php echo esc_html( __( 'Meet the plugin', 'webp-converter-for-media' ) ); ?>
			</a>
		</div>
		<img src="https://mattplugins.com/images/matt-plugins-logo.png" alt="">
	</div>
</div>
