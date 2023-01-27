<?php
/**
 * Notice displayed in admin panel.
 *
 * @var string $ajax_url     URL of admin-ajax.
 * @var string $close_action Action using in WP Ajax.
 * @var string $settings_url URL of the plugin settings.
 *
 * @package Converter for Media
 */

?>
<div class="notice notice-error is-dismissible"
	data-notice="webp-converter-for-media"
	data-notice-action="<?php echo esc_attr( $close_action ); ?>"
	data-notice-url="<?php echo esc_url( $ajax_url ); ?>"
>
	<div class="webpcContent webpcContent--notice">
		<h4>
			<?php echo esc_html( __( 'Check the status of the Converter for Media plugin!', 'webp-converter-for-media' ) ); ?>
		</h4>
		<p>
			<?php
			echo wp_kses_post(
				__( 'It appears that your subscription has expired or you have reached the maximum number of image conversions for your current billing period. To continue using the service, please check your subscription status.', 'webp-converter-for-media' )
			);
			?>
		</p>
		<div class="webpcContent__buttons">
			<a href="<?php echo esc_url( $settings_url ); ?>"
				class="webpcContent__button webpcButton webpcButton--blue webpcButton--bg"
			>
				<?php echo esc_html( __( 'Go to the plugin settings', 'webp-converter-for-media' ) ); ?>
			</a>
		</div>
	</div>
</div>
